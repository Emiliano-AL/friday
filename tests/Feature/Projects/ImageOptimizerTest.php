<?php

use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Write a real image of the given size and format to a temp file and wrap it as an upload.
 */
function realImageUpload(int $width, int $height, string $format, int $quality = 100): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);

    for ($x = 0; $x < $width; $x += 40) {
        imagefilledrectangle($image, $x, 0, $x + 20, $height, imagecolorallocate($image, $x % 255, 120, 200));
    }

    $path = tempnam(sys_get_temp_dir(), 'img').".{$format}";

    match ($format) {
        'jpg' => imagejpeg($image, $path, $quality),
        'png' => imagepng($image, $path),
        'webp' => imagewebp($image, $path, $quality),
    };

    return new UploadedFile($path, "photo.{$format}", null, null, true);
}

test('large jpg images are scaled to 2560px on their longest side and get lighter', function () {
    $upload = realImageUpload(4000, 3000, 'jpg');

    $result = (new ImageOptimizer)->optimize($upload);

    expect($result['width'])->toBe(2560)
        ->and($result['height'])->toBe(1920)
        ->and($result['size'])->toBeLessThan($upload->getSize())
        ->and(getimagesizefromstring($result['contents'])[2])->toBe(IMAGETYPE_JPEG);
});

test('thumbnails are scaled to 400px on their longest side', function () {
    $result = (new ImageOptimizer)->optimize(realImageUpload(1200, 600, 'jpg'));

    expect(getimagesizefromstring($result['thumbnail']))->toMatchArray([0 => 400, 1 => 200]);
});

test('png images keep their format and transparency', function () {
    $image = imagecreatetruecolor(3000, 1000);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    $path = tempnam(sys_get_temp_dir(), 'img').'.png';
    imagepng($image, $path);

    $result = (new ImageOptimizer)->optimize(new UploadedFile($path, 'logo.png', null, null, true));
    $stored = imagecreatefromstring($result['contents']);

    expect(getimagesizefromstring($result['contents'])[2])->toBe(IMAGETYPE_PNG)
        ->and($result['width'])->toBe(2560)
        ->and(imagecolorsforindex($stored, imagecolorat($stored, 10, 10))['alpha'])->toBe(127);
});

test('webp images stay webp', function () {
    $result = (new ImageOptimizer)->optimize(realImageUpload(3000, 3000, 'webp'));

    expect(getimagesizefromstring($result['contents'])[2])->toBe(IMAGETYPE_WEBP)
        ->and($result['width'])->toBe(2560);
});

test('small images keep their original bytes when re-encoding would not make them lighter', function () {
    $upload = realImageUpload(200, 200, 'jpg', 10);

    $result = (new ImageOptimizer)->optimize($upload);

    expect($result['contents'])->toBe(file_get_contents($upload->getRealPath()));
});

test('gifs are kept untouched', function () {
    $upload = UploadedFile::fake()->image('anim.gif', 3000, 100);

    $result = (new ImageOptimizer)->optimize($upload);

    expect($result['contents'])->toBe(file_get_contents($upload->getRealPath()))
        ->and($result['thumbnail'])->toBe($result['contents']);
});

test('jpg images are rotated according to their exif orientation')
    ->todo('GD cannot write EXIF; covered by the manual quickstart check with a phone photo.');

test('images above 40 megapixels are rejected', function () {
    (new ImageOptimizer)->optimize(realImageUpload(8000, 5001, 'jpg', 10));
})->throws(ValidationException::class, 'La imagen supera 40 megapíxeles.');

test('corrupt images are rejected', function () {
    $path = tempnam(sys_get_temp_dir(), 'img').'.jpg';
    file_put_contents($path, 'not really a jpeg');

    (new ImageOptimizer)->optimize(new UploadedFile($path, 'broken.jpg', null, null, true));
})->throws(ValidationException::class, 'La imagen no se pudo procesar.');
