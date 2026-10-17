<?php

namespace Database\Factories;

use App\Enums\AttachmentType;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectAttachment>
 */
class ProjectAttachmentFactory extends Factory
{
    /**
     * Define the model's default state (a link).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $url = fake()->url();

        return [
            'project_id' => Project::factory(),
            'user_id' => User::factory(),
            'type' => AttachmentType::Link,
            'title' => (string) parse_url($url, PHP_URL_HOST),
            'url' => $url,
        ];
    }

    /**
     * Indicate that the attachment is a link.
     */
    public function link(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AttachmentType::Link,
        ]);
    }

    /**
     * Indicate that the attachment is a stored PDF document.
     */
    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AttachmentType::Document,
            'title' => 'brief.pdf',
            'url' => null,
            'path' => 'projects/{project}/attachments/'.Str::uuid().'.pdf',
            'original_name' => 'brief.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);
    }

    /**
     * Indicate that the attachment is a stored PNG image.
     */
    public function image(): static
    {
        return $this->state(function (array $attributes) {
            $base = 'projects/{project}/attachments/'.Str::uuid();

            return [
                'type' => AttachmentType::Image,
                'title' => 'mockup.png',
                'url' => null,
                'path' => "{$base}.png",
                'thumbnail_path' => "{$base}_thumb.png",
                'original_name' => 'mockup.png',
                'mime_type' => 'image/png',
                'size' => 2048,
                'width' => 800,
                'height' => 600,
            ];
        });
    }

    /**
     * Point stored file paths at the resolved project's directory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ProjectAttachment $attachment) {
            foreach (['path', 'thumbnail_path'] as $column) {
                if ($attachment->{$column} !== null) {
                    $attachment->{$column} = str_replace('{project}', (string) $attachment->project_id, $attachment->{$column});
                }
            }
        });
    }
}
