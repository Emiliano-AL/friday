<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ImageOptimizer
{
    public const int MAX_DIMENSION = 2560;

    public const int THUMBNAIL_DIMENSION = 400;

    public const int MAX_PIXELS = 40_000_000;

    /**
     * Shrink, re-encode in the same format and thumbnail an uploaded image with GD.
     *
     * Re-encoding strips EXIF metadata (GPS included) after applying its orientation.
     * The original bytes are kept only when re-encoding would not make a metadata-free
     * image any smaller; an image carrying EXIF is always re-encoded, even when that
     * makes it heavier, because removing the location wins over saving bytes.
     * GIFs are returned untouched so animations survive.
     *
     * @return array{contents: string, thumbnail: string, width: int, height: int, size: int}
     *
     * @throws ValidationException
     */
    public function optimize(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $info = $path === false ? false : @getimagesize($path);

        if ($info === false) {
            throw ValidationException::withMessages(['file' => 'La imagen no se pudo procesar.']);
        }

        [$width, $height, $type] = $info;

        if ($width * $height > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['file' => 'La imagen supera 40 megapíxeles.']);
        }

        $original = (string) file_get_contents($path);

        if ($type === IMAGETYPE_GIF) {
            return ['contents' => $original, 'thumbnail' => $original, 'width' => $width, 'height' => $height, 'size' => strlen($original)];
        }

        ini_set('memory_limit', '512M');

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };

        if ($image === false) {
            throw ValidationException::withMessages(['file' => 'La imagen no se pudo procesar.']);
        }

        $exif = $type === IMAGETYPE_JPEG ? (@exif_read_data($path) ?: []) : [];
        $hasMetadata = str_contains((string) ($exif['SectionsFound'] ?? ''), 'IFD0');
        $rotated = $this->applyOrientation($image, (int) ($exif['Orientation'] ?? 1));
        $resized = $this->fit($rotated, self::MAX_DIMENSION);

        $contents = $this->encode($resized, $type);

        if ($resized === $image && ! $hasMetadata && strlen($contents) >= strlen($original)) {
            $contents = $original;
        }

        $thumbnailImage = $this->fit($resized, self::THUMBNAIL_DIMENSION);
        $thumbnail = $this->encode($thumbnailImage, $type);

        return [
            'contents' => $contents,
            'thumbnail' => $thumbnail,
            'width' => imagesx($resized),
            'height' => imagesy($resized),
            'size' => strlen($contents),
        ];
    }

    /**
     * Rotate the image according to its EXIF orientation (mirrored orientations are left as is).
     */
    private function applyOrientation(GdImage $image, int $orientation): GdImage
    {
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    /**
     * Scale the image down so its longest side is at most the given size, keeping transparency.
     */
    private function fit(GdImage $image, int $maxDimension): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= $maxDimension) {
            return $image;
        }

        $ratio = $maxDimension / max($width, $height);
        $scaled = imagecreatetruecolor(max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, imagesx($scaled), imagesy($scaled), $width, $height);

        return $scaled;
    }

    /**
     * Encode the image in the given format with web-friendly compression.
     */
    private function encode(GdImage $image, int $type): string
    {
        imagesavealpha($image, true);

        ob_start();

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, null, 82),
            IMAGETYPE_PNG => imagepng($image, null, 9),
            IMAGETYPE_WEBP => imagewebp($image, null, 80),
        };

        return (string) ob_get_clean();
    }
}
