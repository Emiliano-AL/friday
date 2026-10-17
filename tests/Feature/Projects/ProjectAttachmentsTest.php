<?php

use App\Enums\AttachmentType;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

/**
 * Create a project with an extra collaborator.
 *
 * @return array{0: Project, 1: User}
 */
function projectWithCollaborator(): array
{
    $collaborator = User::factory()->create();
    $project = Project::factory()->hasAttached($collaborator, [], 'members')->create();

    return [$project, $collaborator];
}

/**
 * Wrap a file with an OLE compound-document header (legacy .doc/.xls/.ppt) as an upload.
 */
function legacyOfficeUpload(string $name): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'ole');
    file_put_contents($path, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\x00", 1024));

    return new UploadedFile($path, $name, null, null, true);
}

// Uploading files

test('collaborators can upload documents', function () {
    [$project, $collaborator] = projectWithCollaborator();

    $response = $this->actingAs($collaborator)->post("/projects/{$project->id}/attachments/files", [
        'file' => UploadedFile::fake()->create('brief.pdf', 500, 'application/pdf'),
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));
    $attachment = ProjectAttachment::sole();
    expect($attachment)
        ->type->toBe(AttachmentType::Document)
        ->title->toBe('brief.pdf')
        ->original_name->toBe('brief.pdf')
        ->user_id->toBe($collaborator->id)
        ->project_id->toBe($project->id)
        ->path->toStartWith("projects/{$project->id}/attachments/");
    Storage::assertExists($attachment->path);
});

test('owners can upload images, stored with a thumbnail', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->owner)->post("/projects/{$project->id}/attachments/files", [
        'file' => UploadedFile::fake()->image('mock.png', 800, 600),
    ]);

    $attachment = ProjectAttachment::sole();
    expect($attachment)
        ->type->toBe(AttachmentType::Image)
        ->width->toBe(800)
        ->height->toBe(600);
    Storage::assertExists([$attachment->path, $attachment->thumbnail_path]);
});

test('large images are stored scaled down to 2560px', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->owner)->post("/projects/{$project->id}/attachments/files", [
        'file' => UploadedFile::fake()->image('photo.jpg', 4000, 3000),
    ]);

    $attachment = ProjectAttachment::sole();
    expect(getimagesizefromstring(Storage::get($attachment->path)))->toMatchArray([0 => 2560, 1 => 1920])
        ->and($attachment->size)->toBe(Storage::size($attachment->path));
});

test('non members cannot upload files', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post("/projects/{$project->id}/attachments/files", ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
        ->assertNotFound();

    expect(ProjectAttachment::count())->toBe(0);
});

test('files cannot be uploaded to inactive projects', function () {
    $project = Project::factory()->archived()->create();

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/files", ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors(['project' => 'El proyecto no admite cambios en su estado actual.']);

    expect(ProjectAttachment::count())->toBe(0);
});

// Size, type and quota limits

test('files over their size limit are rejected', function (UploadedFile $file, string $message) {
    $project = Project::factory()->create();

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/files", ['file' => $file])
        ->assertSessionHasErrors(['file' => $message]);

    expect(ProjectAttachment::count())->toBe(0);
    expect(Storage::allFiles())->toBeEmpty();
})->with([
    'image over 5 MB' => fn () => [UploadedFile::fake()->image('big.jpg')->size(5121), 'Las imágenes no pueden superar 5 MB.'],
    'document over 15 MB' => fn () => [UploadedFile::fake()->create('big.pdf', 15361, 'application/pdf'), 'Los documentos no pueden superar 15 MB.'],
]);

test('files of a type that is not allowed are rejected', function (UploadedFile $file) {
    $project = Project::factory()->create();

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/files", ['file' => $file])
        ->assertSessionHasErrors(['file' => 'Solo se permiten documentos (PDF, Word, Excel, PowerPoint, TXT, MD, CSV) e imágenes (JPG, PNG, WebP, GIF).']);

    expect(ProjectAttachment::count())->toBe(0);
})->with([
    'zip archive' => fn () => UploadedFile::fake()->create('code.zip', 10, 'application/zip'),
    'video' => fn () => UploadedFile::fake()->create('clip.mp4', 10, 'video/mp4'),
    'executable renamed to pdf' => function () {
        $path = tempnam(sys_get_temp_dir(), 'exe');
        file_put_contents($path, "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff".str_repeat("\x00", 64));

        return new UploadedFile($path, 'malware.pdf', null, null, true);
    },
    'legacy office content renamed to pdf' => fn () => legacyOfficeUpload('report.pdf'),
]);

test('legacy office documents detected as generic ole storage are accepted', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/files", ['file' => legacyOfficeUpload('budget.xls')])
        ->assertSessionHasNoErrors();

    expect(ProjectAttachment::sole())
        ->type->toBe(AttachmentType::Document)
        ->original_name->toBe('budget.xls');
});

test('projects accept at most 30 attachments', function () {
    $project = Project::factory()->create();
    ProjectAttachment::factory()->count(30)->for($project)->create();

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/files", ['file' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors(['file' => 'El proyecto alcanzó el máximo de 30 adjuntos.']);

    expect($project->attachments()->count())->toBe(30);
    expect(Storage::allFiles())->toBeEmpty();
});

test('projects accept at most 200 MB of files', function () {
    $project = Project::factory()->create();
    ProjectAttachment::factory()->document()->for($project)->create(['size' => 200 * 1024 * 1024 - 1024]);

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/files", ['file' => UploadedFile::fake()->create('a.pdf', 2, 'application/pdf')])
        ->assertSessionHasErrors(['file' => 'El proyecto alcanzó el máximo de 200 MB de archivos.']);

    expect($project->attachments()->count())->toBe(1);
    expect(Storage::allFiles())->toBeEmpty();
});

// Links

test('members can add links with a title', function () {
    [$project, $collaborator] = projectWithCollaborator();

    $response = $this->actingAs($collaborator)->post("/projects/{$project->id}/attachments/links", [
        'url' => 'https://www.figma.com/file/abc',
        'title' => 'Diseño',
    ]);

    $response->assertRedirect(route('projects.show', $project, absolute: false));
    expect(ProjectAttachment::sole())
        ->type->toBe(AttachmentType::Link)
        ->url->toBe('https://www.figma.com/file/abc')
        ->title->toBe('Diseño')
        ->user_id->toBe($collaborator->id);
});

test('links without a title use their domain', function () {
    $project = Project::factory()->create();

    $this->actingAs($project->owner)->post("/projects/{$project->id}/attachments/links", [
        'url' => 'https://www.figma.com/file/abc',
    ]);

    expect(ProjectAttachment::sole()->title)->toBe('figma.com');
});

test('invalid links are rejected', function (array $payload, string $field, string $message) {
    $project = Project::factory()->create();

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/links", $payload)
        ->assertSessionHasErrors([$field => $message]);

    expect(ProjectAttachment::count())->toBe(0);
})->with([
    'missing url' => [[], 'url', 'La URL es obligatoria.'],
    'javascript scheme' => [['url' => 'javascript:alert(1)'], 'url', 'Ingresa una URL válida que empiece con http:// o https://.'],
    'ftp scheme' => [['url' => 'ftp://example.com/file'], 'url', 'Ingresa una URL válida que empiece con http:// o https://.'],
    'not a url' => [['url' => 'not a url'], 'url', 'Ingresa una URL válida que empiece con http:// o https://.'],
    'url too long' => [['url' => 'https://example.com/'.str_repeat('a', 2030)], 'url', 'La URL no puede superar los 2048 caracteres.'],
    'title too long' => [['url' => 'https://example.com', 'title' => str_repeat('a', 256)], 'title', 'El título no puede superar los 255 caracteres.'],
]);

test('links count toward the 30 attachments limit', function () {
    $project = Project::factory()->create();
    ProjectAttachment::factory()->count(30)->for($project)->create();

    $this->actingAs($project->owner)
        ->post("/projects/{$project->id}/attachments/links", ['url' => 'https://example.com'])
        ->assertSessionHasErrors(['url' => 'El proyecto alcanzó el máximo de 30 adjuntos.']);

    expect($project->attachments()->count())->toBe(30);
});

test('non members cannot add links', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post("/projects/{$project->id}/attachments/links", ['url' => 'https://example.com'])
        ->assertNotFound();
});

// Opening files

test('members are redirected to a temporary url of the file', function () {
    [$project, $collaborator] = projectWithCollaborator();
    $attachment = ProjectAttachment::factory()->image()->for($project)->create();

    $this->actingAs($collaborator)
        ->get("/projects/{$project->id}/attachments/{$attachment->id}")
        ->assertRedirectContains($attachment->path);

    $this->actingAs($collaborator)
        ->get("/projects/{$project->id}/attachments/{$attachment->id}?thumbnail=1")
        ->assertRedirectContains($attachment->thumbnail_path);
});

test('downloads from the local disk use the original file name', function () {
    $attachment = ProjectAttachment::factory()->document()->create(['original_name' => 'Propuesta final.pdf']);
    Storage::put($attachment->path, 'pdf');

    $this->actingAs($attachment->project->owner)
        ->get("/projects/{$attachment->project_id}/attachments/{$attachment->id}?download=1")
        ->assertDownload('Propuesta final.pdf');
});

test('archived projects still let members open their files', function () {
    $project = Project::factory()->archived()->create();
    $attachment = ProjectAttachment::factory()->document()->for($project)->create();

    $this->actingAs($project->owner)
        ->get("/projects/{$project->id}/attachments/{$attachment->id}")
        ->assertRedirectContains($attachment->path);
});

test('files cannot be opened by non members or through another project', function () {
    $attachment = ProjectAttachment::factory()->document()->create();
    $otherProject = Project::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get("/projects/{$attachment->project_id}/attachments/{$attachment->id}")
        ->assertNotFound();

    $this->actingAs($otherProject->owner)
        ->get("/projects/{$otherProject->id}/attachments/{$attachment->id}")
        ->assertNotFound();
});

test('links have no file to open', function () {
    $attachment = ProjectAttachment::factory()->link()->create();

    $this->actingAs($attachment->project->owner)
        ->get("/projects/{$attachment->project_id}/attachments/{$attachment->id}")
        ->assertNotFound();
});

// Renaming and deleting

test('authors and owners can rename attachments', function (string $actor) {
    [$project, $collaborator] = projectWithCollaborator();
    $attachment = ProjectAttachment::factory()->for($project)->for($collaborator)->create();
    $user = $actor === 'author' ? $collaborator : $project->owner;

    $this->actingAs($user)
        ->put("/projects/{$project->id}/attachments/{$attachment->id}", ['title' => 'Nuevo nombre'])
        ->assertRedirect(route('projects.show', $project, absolute: false));

    expect($attachment->refresh()->title)->toBe('Nuevo nombre');
})->with(['author', 'owner']);

test('renaming requires a title of at most 255 characters', function (string $title, string $message) {
    $attachment = ProjectAttachment::factory()->create();

    $this->actingAs($attachment->project->owner)
        ->put("/projects/{$attachment->project_id}/attachments/{$attachment->id}", ['title' => $title])
        ->assertSessionHasErrors(['title' => $message]);
})->with([
    'empty' => ['', 'El título es obligatorio.'],
    'too long' => [str_repeat('a', 256), 'El título no puede superar los 255 caracteres.'],
]);

test('authors and owners can delete attachments and their files', function (string $actor) {
    [$project, $collaborator] = projectWithCollaborator();
    $attachment = ProjectAttachment::factory()->image()->for($project)->for($collaborator)->create();
    Storage::put($attachment->path, 'image');
    Storage::put($attachment->thumbnail_path, 'thumb');
    $user = $actor === 'author' ? $collaborator : $project->owner;

    $this->actingAs($user)
        ->delete("/projects/{$project->id}/attachments/{$attachment->id}")
        ->assertRedirect(route('projects.show', $project, absolute: false));

    $this->assertModelMissing($attachment);
    Storage::assertMissing([$attachment->path, $attachment->thumbnail_path]);
})->with(['author', 'owner']);

test('other collaborators cannot rename or delete an attachment', function () {
    [$project, $collaborator] = projectWithCollaborator();
    $attachment = ProjectAttachment::factory()->for($project)->for($project->owner)->create();

    $this->actingAs($collaborator)
        ->put("/projects/{$project->id}/attachments/{$attachment->id}", ['title' => 'Nuevo'])
        ->assertForbidden();
    $this->actingAs($collaborator)
        ->delete("/projects/{$project->id}/attachments/{$attachment->id}")
        ->assertForbidden();

    $this->assertModelExists($attachment);
    expect($attachment->refresh()->title)->not->toBe('Nuevo');
});

test('attachments of inactive projects cannot be renamed or deleted', function () {
    $project = Project::factory()->archived()->create();
    $attachment = ProjectAttachment::factory()->for($project)->for($project->owner)->create();

    $this->actingAs($project->owner)
        ->put("/projects/{$project->id}/attachments/{$attachment->id}", ['title' => 'Nuevo'])
        ->assertSessionHasErrors('project');
    $this->actingAs($project->owner)
        ->delete("/projects/{$project->id}/attachments/{$attachment->id}")
        ->assertSessionHasErrors('project');

    $this->assertModelExists($attachment);
});

test('deleting a project removes its attachments and stored files', function () {
    $project = Project::factory()->create();
    $attachment = ProjectAttachment::factory()->document()->for($project)->create();
    Storage::put($attachment->path, 'pdf');

    $this->actingAs($project->owner)->delete("/projects/{$project->id}");

    $this->assertModelMissing($attachment);
    expect(Storage::allFiles("projects/{$project->id}"))->toBeEmpty();
});

test('removed collaborators keep their attachments in the project but lose access', function () {
    [$project, $collaborator] = projectWithCollaborator();
    $attachment = ProjectAttachment::factory()->document()->for($project)->for($collaborator)->create();

    $this->actingAs($project->owner)->delete("/projects/{$project->id}/members/{$collaborator->id}");

    $this->assertModelExists($attachment);
    $this->actingAs($collaborator)
        ->get("/projects/{$project->id}/attachments/{$attachment->id}")
        ->assertNotFound();
});
