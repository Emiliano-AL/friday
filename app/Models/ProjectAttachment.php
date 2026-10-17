<?php

namespace App\Models;

use App\Enums\AttachmentType;
use Carbon\CarbonImmutable;
use Database\Factories\ProjectAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $user_id
 * @property AttachmentType $type
 * @property string $title
 * @property string|null $url
 * @property string|null $path
 * @property string|null $thumbnail_path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $size
 * @property int|null $width
 * @property int|null $height
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Project $project
 * @property-read User|null $user
 */
#[Fillable(['project_id', 'user_id', 'type', 'title', 'url', 'path', 'thumbnail_path', 'original_name', 'mime_type', 'size', 'width', 'height'])]
class ProjectAttachment extends Model
{
    /** @use HasFactory<ProjectAttachmentFactory> */
    use HasFactory;

    public const int MAX_PER_PROJECT = 30;

    public const int MAX_BYTES_PER_PROJECT = 200 * 1024 * 1024;

    public const int IMAGE_MAX_KILOBYTES = 5 * 1024;

    public const int DOCUMENT_MAX_KILOBYTES = 15 * 1024;

    /** @var array<int, string> */
    public const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /** @var array<int, string> */
    public const array DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'md', 'csv'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AttachmentType::class,
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the member who added the attachment (null once their account is deleted).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the directory holding every stored file of the given project.
     */
    public static function directoryFor(Project $project): string
    {
        return "projects/{$project->id}/attachments";
    }
}
