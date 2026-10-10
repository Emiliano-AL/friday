<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts EditorJS block JSON (saved by the previous editor) into plain
     * visible text in the long-form content columns. Idempotent: values that
     * are already plain text or Tiptap docs are left untouched.
     */
    public function up(): void
    {
        $this->convertColumn('projects', 'description');
        $this->convertColumn('tasks', 'description');
        $this->convertColumn('sprints', 'goal');
    }

    /**
     * Reverse the migrations.
     *
     * No content reversal: the conversion keeps the visible text (flattened),
     * so rolling back would only drop the block structure.
     */
    public function down(): void
    {
        // Irreversible by design; visible text is preserved in the columns.
    }

    private function convertColumn(string $table, string $column): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    $value = $row->{$column};

                    if (! $this->isBlocksJson($value)) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->id)->update([
                        $column => $this->blocksToPlainText($value),
                    ]);
                }
            });
    }

    private function isBlocksJson(mixed $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            && isset($decoded['blocks'])
            && is_array($decoded['blocks']);
    }

    private function blocksToPlainText(string $json): string
    {
        $decoded = json_decode($json, true);

        $parts = [];

        foreach ($decoded['blocks'] as $block) {
            $text = $this->blockToPlainText($block);

            if (trim($text) !== '') {
                $parts[] = $text;
            }
        }

        $plain = trim(implode("\n", $parts));

        return $plain === '' ? '' : $plain;
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function blockToPlainText(array $block): string
    {
        $type = $block['type'] ?? '';
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        return match ($type) {
            'paragraph', 'header' => $this->stripInlineMarkup($data['text'] ?? ''),
            'list' => $this->listToPlainText($data),
            'quote' => $this->quoteToPlainText($data),
            'code' => is_string($data['code'] ?? null) ? $data['code'] : '',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function listToPlainText(array $data): string
    {
        if (! is_array($data['items'] ?? null)) {
            return '';
        }

        return implode("\n", array_map(
            fn ($item) => $this->stripInlineMarkup($item),
            $data['items'],
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function quoteToPlainText(array $data): string
    {
        $parts = [$this->stripInlineMarkup($data['text'] ?? '')];

        $caption = $this->stripInlineMarkup($data['caption'] ?? '');

        if (trim($caption) !== '') {
            $parts[] = $caption;
        }

        return implode("\n", $parts);
    }

    private function stripInlineMarkup(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return strip_tags($value);
    }
};
