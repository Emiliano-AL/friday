<?php

use Illuminate\Support\Facades\File;

/**
 * Collect every case-sensitive "Friday" occurrence in user-facing sources.
 *
 * @return list<string>
 */
function legacyBrandOccurrences(): array
{
    $sources = [
        [resource_path('js'), ['vue', 'ts']],
        [resource_path('views'), ['php']],
        [app_path('Mail'), ['php']],
    ];

    $occurrences = [];

    foreach ($sources as [$directory, $extensions]) {
        foreach (File::allFiles($directory) as $file) {
            if (! in_array($file->getExtension(), $extensions, true)) {
                continue;
            }

            foreach (file($file->getPathname()) as $index => $line) {
                if (preg_match('/\bFriday\b/', $line) === 1) {
                    $occurrences[] = str_replace(base_path().'/', '', $file->getPathname()).':'.($index + 1);
                }
            }
        }
    }

    return $occurrences;
}

test('user-facing sources never name the product with the Friday codename', function () {
    expect(legacyBrandOccurrences())->toBe([]);
});

test('internal friday identifiers are preserved so saved preferences survive', function () {
    expect(File::get(resource_path('js/Components/Tasks/Board/TaskBoard.vue')))
        ->toContain("'friday:kanban-done-collapsed'");
});
