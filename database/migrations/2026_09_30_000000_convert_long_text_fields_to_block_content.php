<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts existing plain-text long-form content (task and project
     * descriptions, sprint goals) into EditorJS block structure. Idempotent:
     * values that already are block JSON are left untouched.
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
     * No content reversal: the conversion preserves the visible text (wrapped
     * in a single paragraph block), so rolling back would only drop structure.
     */
    public function down(): void
    {
        // Irreversible by design; visible text is preserved in the blocks.
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

                    if ($this->isBlocksJson($value)) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->id)->update([
                        $column => json_encode(
                            [
                                'blocks' => [
                                    [
                                        'type' => 'paragraph',
                                        'data' => ['text' => $value],
                                    ],
                                ],
                            ],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        ),
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
};
