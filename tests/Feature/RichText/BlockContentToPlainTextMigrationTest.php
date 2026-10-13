<?php

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;

function runBlockContentConversion(): void
{
    $migration = include database_path('migrations/2026_10_02_000000_convert_block_content_to_plain_text.php');

    $migration->up();
}

test('editorjs block json is flattened to plain text in all three fields', function () {
    $content = json_encode([
        'time' => 1727654400000,
        'blocks' => [
            ['type' => 'header', 'data' => ['text' => '<b>Título</b> del proyecto', 'level' => 2]],
            ['type' => 'paragraph', 'data' => ['text' => 'Ver <a href="https://example.com">documentación</a> aquí']],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => ['<b>Primero</b>', 'Segundo']]],
            ['type' => 'quote', 'data' => ['text' => 'Cita <i>importante</i>', 'caption' => '— Autor']],
            ['type' => 'code', 'data' => ['code' => 'echo "hola";']],
        ],
        'version' => '2.31',
    ], JSON_THROW_ON_ERROR);

    $expected = implode("\n", [
        'Título del proyecto',
        'Ver documentación aquí',
        "Primero\nSegundo",
        "Cita importante\n— Autor",
        'echo "hola";',
    ]);

    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => $content]);
    $sprint = Sprint::factory()->for($project)->create(['goal' => $content]);
    $task = Task::factory()->for($project)->create(['description' => $content]);

    runBlockContentConversion();

    expect($project->refresh()->description)->toBe($expected);
    expect($sprint->refresh()->goal)->toBe($expected);
    expect($task->refresh()->description)->toBe($expected);
});

test('blocks that flatten to an empty string become an empty string', function () {
    $content = json_encode([
        'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => '<b></b>']],
            ['type' => 'delimiter', 'data' => []],
        ],
    ], JSON_THROW_ON_ERROR);

    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => $content]);

    runBlockContentConversion();

    expect($project->refresh()->description)->toBe('');
});

test('non block json strings and plain text are left untouched', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => '{"type":"doc","content":[{"type":"paragraph"}]}']);
    $sprint = Sprint::factory()->for($project)->create(['goal' => 'Texto plano ya migrado']);
    $task = Task::factory()->for($project)->create(['description' => '{"just":"an","object":true}']);

    runBlockContentConversion();

    expect($project->refresh()->description)->toBe('{"type":"doc","content":[{"type":"paragraph"}]}');
    expect($sprint->refresh()->goal)->toBe('Texto plano ya migrado');
    expect($task->refresh()->description)->toBe('{"just":"an","object":true}');
});

test('null values stay null and the conversion is idempotent', function () {
    $content = json_encode([
        'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Párrafo']],
        ],
    ], JSON_THROW_ON_ERROR);

    $user = User::factory()->create();
    $project = Project::factory()->for($user, 'owner')->create(['description' => null]);
    $sprint = Sprint::factory()->for($project)->create(['goal' => $content]);
    $task = Task::factory()->for($project)->create(['description' => $content]);

    runBlockContentConversion();
    $firstPassSprint = $sprint->refresh()->goal;
    $firstPassTask = $task->refresh()->description;

    runBlockContentConversion();

    expect($project->refresh()->description)->toBeNull();
    expect($sprint->refresh()->goal)->toBe('Párrafo')->toBe($firstPassSprint);
    expect($task->refresh()->description)->toBe('Párrafo')->toBe($firstPassTask);
});
