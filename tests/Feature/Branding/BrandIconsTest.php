<?php

use Illuminate\Support\Facades\File;

/**
 * Read the image widths declared in an .ico file's directory.
 *
 * @return list<int>
 */
function icoWidths(string $path): array
{
    $bytes = file_get_contents($path);
    ['type' => $type, 'count' => $count] = unpack('vreserved/vtype/vcount', $bytes);

    expect($type)->toBe(1);

    return array_map(
        fn (int $index): int => ord($bytes[6 + $index * 16]) ?: 256,
        range(0, $count - 1),
    );
}

test('the svg favicon is the Eunox mark, not the Laravel logo', function () {
    $svg = file_get_contents(public_path('favicon.svg'));

    expect($svg)->toContain('#5B5BD6')
        ->toContain('M4 4h7a4 4 0 0 1 4 4v1H8')
        ->not->toContain('#FF2D20');
});

test('the ico favicon includes 16, 32 and 48 pixel sizes', function () {
    expect(icoWidths(public_path('favicon.ico')))->toContain(16, 32, 48);
});

test('every page declares the brand favicons', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('/favicon.svg', false)
        ->assertSee('/favicon.ico', false);
});

test('home screen icons have the expected sizes', function (string $file, int $size) {
    [$width, $height] = getimagesize(public_path($file));

    expect([$width, $height])->toBe([$size, $size]);
})->with([
    ['apple-touch-icon.png', 180],
    ['icon-192.png', 192],
    ['icon-512.png', 512],
]);

test('the apple touch icon has no transparency', function () {
    // PNG color type lives in the IHDR chunk; 2 = truecolor without alpha.
    $colorType = ord(file_get_contents(public_path('apple-touch-icon.png'))[25]);

    expect($colorType)->toBe(2);
});

test('the web manifest names Eunox and lists existing icons', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['name'])->toBe('Eunox')
        ->and($manifest['short_name'])->toBe('Eunox')
        ->and($manifest['theme_color'])->toBe('#5B5BD6')
        ->and($manifest['icons'])->toHaveCount(2);

    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }
});

test('pages declare the manifest and theme color', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<link rel="manifest" href="/site.webmanifest">', false)
        ->assertSee('<meta name="theme-color" content="#5B5BD6">', false)
        ->assertSee('/apple-touch-icon.png', false);
});

test('the brand mark path lives only in AppLogo', function () {
    $files = collect(File::allFiles(resource_path('js')))
        ->filter(fn ($file) => in_array($file->getExtension(), ['vue', 'ts'], true))
        ->filter(fn ($file) => str_contains($file->getContents(), 'M4 4h7a4 4 0 0 1 4 4v1H8'))
        ->map(fn ($file) => str_replace(base_path().'/', '', $file->getPathname()))
        ->values()
        ->all();

    expect($files)->toBe(['resources/js/Components/AppShell/AppLogo.vue']);
});
