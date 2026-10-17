<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentType;
use App\Http\Requests\StoreProjectAttachmentFileRequest;
use App\Http\Requests\StoreProjectAttachmentLinkRequest;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Support\ImageOptimizer;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProjectAttachmentController extends Controller
{
    /**
     * Store an uploaded document or image (member of an active project).
     *
     * Images are optimized before being stored next to their thumbnail; the
     * project quota is checked against the final, optimized size.
     */
    public function storeFile(StoreProjectAttachmentFileRequest $request, Project $project, ImageOptimizer $optimizer): RedirectResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('create', [ProjectAttachment::class, $project]);

        $file = $request->file('file');
        $originalName = mb_substr($file->getClientOriginalName(), 0, 255);
        $basePath = ProjectAttachment::directoryFor($project).'/'.Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());

        $attributes = [
            'user_id' => $request->user()->id,
            'title' => $originalName,
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'path' => "{$basePath}.{$extension}",
        ];

        if ($request->isImage()) {
            $image = $optimizer->optimize($file);

            $attributes += [
                'type' => AttachmentType::Image,
                'thumbnail_path' => "{$basePath}_thumb.{$extension}",
                'size' => $image['size'],
                'width' => $image['width'],
                'height' => $image['height'],
            ];
            $writes = [
                $attributes['path'] => $image['contents'],
                $attributes['thumbnail_path'] => $image['thumbnail'],
            ];
        } else {
            $attributes += [
                'type' => AttachmentType::Document,
                'size' => $file->getSize(),
            ];
            $writes = [$attributes['path'] => $file];
        }

        DB::transaction(function () use ($project, $attributes, $writes) {
            $this->ensureWithinQuota($project, $attributes['size'], 'file');

            try {
                foreach ($writes as $path => $contents) {
                    $stored = is_string($contents)
                        ? Storage::put($path, $contents)
                        : Storage::putFileAs(dirname($path), $contents, basename($path));

                    if ($stored === false) {
                        throw new RuntimeException("Could not store attachment file [{$path}].");
                    }
                }

                $project->attachments()->create($attributes);
            } catch (Throwable $exception) {
                Storage::delete(array_keys($writes));

                throw $exception;
            }
        });

        return redirect()->route('projects.show', $project)->with('success', 'Archivo subido.');
    }

    /**
     * Store a reference link (member of an active project).
     */
    public function storeLink(StoreProjectAttachmentLinkRequest $request, Project $project): RedirectResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('create', [ProjectAttachment::class, $project]);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $project, $validated) {
            $this->ensureWithinQuota($project, 0, 'url');

            $project->attachments()->create([
                'user_id' => $request->user()->id,
                'type' => AttachmentType::Link,
                'url' => $validated['url'],
                'title' => ($validated['title'] ?? '')
                    ?: Str::after((string) parse_url($validated['url'], PHP_URL_HOST), 'www.'),
            ]);
        });

        return redirect()->route('projects.show', $project)->with('success', 'Link agregado.');
    }

    /**
     * Redirect a member to a short-lived URL of the stored file (or its thumbnail).
     *
     * The local disk ignores the download file name of temporary URLs, so local
     * downloads are streamed with the original name instead.
     */
    public function show(Request $request, Project $project, ProjectAttachment $attachment): RedirectResponse|StreamedResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        $this->ensureAttachmentBelongsToProject($project, $attachment);

        $this->authorize('view', $attachment);

        $path = $request->boolean('thumbnail') ? $attachment->thumbnail_path : $attachment->path;

        abort_if($path === null, 404);

        if ($request->boolean('download') && Storage::disk() instanceof LocalFilesystemAdapter) {
            return Storage::download($path, $attachment->original_name);
        }

        $disposition = HeaderUtils::makeDisposition(
            $request->boolean('download') ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
            (string) $attachment->original_name,
            Str::ascii((string) $attachment->original_name),
        );

        return redirect()->away(Storage::temporaryUrl($path, now()->addMinutes(5), [
            'ResponseContentDisposition' => $disposition,
        ]));
    }

    /**
     * Rename the attachment (author or owner of an active project).
     */
    public function update(Request $request, Project $project, ProjectAttachment $attachment): RedirectResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        $this->ensureAttachmentBelongsToProject($project, $attachment);

        $this->ensureProjectIsActive($project);

        $this->authorize('update', $attachment);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
        ]);

        $attachment->update($validated);

        return redirect()->route('projects.show', $project)->with('success', 'Adjunto actualizado.');
    }

    /**
     * Remove the attachment and its stored files (author or owner of an active project).
     */
    public function destroy(Request $request, Project $project, ProjectAttachment $attachment): RedirectResponse
    {
        abort_unless($project->isMember($request->user()), 404);

        $this->ensureAttachmentBelongsToProject($project, $attachment);

        $this->ensureProjectIsActive($project);

        $this->authorize('delete', $attachment);

        Storage::delete(array_filter([$attachment->path, $attachment->thumbnail_path]));

        $attachment->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Adjunto eliminado.');
    }

    /**
     * Reject the attachment when the project already holds 30 attachments or would exceed 200 MB.
     *
     * Locks the project row so concurrent uploads cannot both slip under the limits.
     *
     * @throws ValidationException
     */
    private function ensureWithinQuota(Project $project, int $bytes, string $field): void
    {
        Project::query()->whereKey($project->id)->lockForUpdate()->first();

        if ($project->attachments()->count() >= ProjectAttachment::MAX_PER_PROJECT) {
            throw ValidationException::withMessages([$field => 'El proyecto alcanzó el máximo de 30 adjuntos.']);
        }

        if ($project->attachments()->sum('size') + $bytes > ProjectAttachment::MAX_BYTES_PER_PROJECT) {
            throw ValidationException::withMessages([$field => 'El proyecto alcanzó el máximo de 200 MB de archivos.']);
        }
    }

    private function ensureAttachmentBelongsToProject(Project $project, ProjectAttachment $attachment): void
    {
        abort_unless($attachment->project_id === $project->id, 404);
    }

    private function ensureProjectIsActive(Project $project): void
    {
        if (! $project->status->isActive()) {
            back()->withErrors([
                'project' => 'El proyecto no admite cambios en su estado actual.',
            ])->throwResponse();
        }
    }
}
