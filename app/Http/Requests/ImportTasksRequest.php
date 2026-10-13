<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validates and analyzes a Markdown file in the "Formato de Tareas" for bulk import.
 */
class ImportTasksRequest extends FormRequest
{
    public const int MAX_TASKS = 200;

    public const int MAX_BYTES = 1048576;

    private const array FIELDS = ['tipo' => 'Tipo', 'prioridad' => 'Prioridad', 'responsable' => 'Responsable', 'descripcion' => 'Descripción'];

    private const array TYPES = ['tarea' => TaskType::Other, 'feature' => TaskType::Feature, 'bug' => TaskType::Bug, 'test' => TaskType::Test];

    private const array PRIORITIES = ['baja' => TaskPriority::Low, 'media' => TaskPriority::Medium, 'alta' => TaskPriority::High, 'urgente' => TaskPriority::Urgent];

    private const array TYPE_LABELS = ['other' => 'Tarea', 'feature' => 'Feature', 'bug' => 'Bug', 'test' => 'Test'];

    /**
     * Determine if the user is authorized to make this request (project access is checked by the controller).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                fn (string $attribute, mixed $value, Closure $fail) => is_string($value) && strlen($value) > self::MAX_BYTES
                    ? $fail('El archivo no puede superar 1 MB.')
                    : null,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Selecciona o pega un archivo de tareas.',
        ];
    }

    /**
     * Parse the file line by line into raw tasks and excluded items.
     *
     * @return array{tasks: list<array{line: int, title: string, fields: array<string, string>, section: string|null}>, excluded: list<array{name: string, reason: string}>}
     */
    public function parsedTasks(): array
    {
        $tasks = [];
        $excluded = [];
        $section = null;
        $inExcluded = false;
        $current = null;
        $lastField = null;

        foreach (preg_split('/\R/u', (string) $this->input('content')) as $index => $rawLine) {
            $line = trim($rawLine);

            if (preg_match('/^##(?!#)\s*(.*)$/u', $line, $match)) {
                $heading = $this->normalize($match[1]);
                $inExcluded = str_starts_with($heading, 'no incluidos');
                $section = preg_match('/^prioridad\s+(\S+)/u', $heading, $priority) ? $priority[1] : null;
                $current = null;

                continue;
            }

            if (preg_match('/^###(?!#)\s*(.*)$/u', $line, $match)) {
                $tasks[] = [
                    'line' => $index + 1,
                    'title' => trim(preg_replace('/^\*\*(.*)\*\*$/u', '$1', trim($match[1]))),
                    'fields' => [],
                    'section' => $section,
                ];
                $current = array_key_last($tasks);
                $lastField = null;

                continue;
            }

            if ($inExcluded && preg_match('/^[-*]\s+\*\*(.+?)\*\*\s*(?:\([^)]*\))?\s*:?\s*(.*)$/u', $line, $match)) {
                $excluded[] = ['name' => trim($match[1]), 'reason' => rtrim(trim($match[2]), '.')];

                continue;
            }

            if ($current === null || $inExcluded || $line === '' || $line === '---' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^[-*]\s+\*\*([^*]+?)\s*:?\s*\*\*\s*:?\s*(.*)$/u', $line, $match)) {
                $lastField = $this->normalize(rtrim($match[1], ':'));
                $tasks[$current]['fields'][$lastField] = trim($match[2]);

                continue;
            }

            if ($lastField !== null) {
                $tasks[$current]['fields'][$lastField] = trim($tasks[$current]['fields'][$lastField].' '.$line);
            }
        }

        if (count($tasks) === 0) {
            throw ValidationException::withMessages(['content' => 'No encontramos tareas con el formato esperado.']);
        }

        if (count($tasks) > self::MAX_TASKS) {
            throw ValidationException::withMessages(['content' => 'El archivo tiene más de '.self::MAX_TASKS.' tareas; divídelo en varios.']);
        }

        return ['tasks' => $tasks, 'excluded' => $excluded];
    }

    /**
     * Build the import preview for a destination (a project, or the user's standalone tasks).
     *
     * @return array{destination: array{projectId: int|null, label: string}, tasks: list<array<string, mixed>>, excluded: list<array{name: string, reason: string}>, counts: array{importable: int, total: int}}
     */
    public function analyze(?Project $project, User $user): array
    {
        $parsed = $this->parsedTasks();
        $people = $project
            ? $project->members()->get()->prepend($project->owner)->unique('id')
            : collect([$user]);
        $existing = $this->existingTitles($project, $user);
        $seen = [];

        $tasks = array_map(function (array $raw) use ($people, $existing, &$seen, $project) {
            $errors = [];
            $warnings = [];
            $fields = $raw['fields'];
            $title = $raw['title'];

            if ($title === '') {
                $errors[] = 'Falta el nombre de la tarea.';
            } elseif (mb_strlen($title) > 255) {
                $errors[] = 'El nombre supera 255 caracteres.';
            }

            foreach (self::FIELDS as $key => $label) {
                if (($fields[$key] ?? '') === '') {
                    $errors[] = "Falta el campo {$label}.";
                }
            }

            $type = self::TYPES[$this->normalize($fields['tipo'] ?? '')] ?? null;

            if (($fields['tipo'] ?? '') !== '' && $type === null) {
                $errors[] = "Tipo «{$fields['tipo']}» no es válido (Tarea, Feature, Bug, Test).";
            }

            $priority = self::PRIORITIES[$this->normalize($fields['prioridad'] ?? '')] ?? null;

            if (($fields['prioridad'] ?? '') !== '' && $priority === null) {
                $errors[] = "Prioridad «{$fields['prioridad']}» no es válida (Baja, Media, Alta, Urgente).";
            }

            if ($priority !== null && $raw['section'] !== null && $raw['section'] !== $this->normalize($fields['prioridad'])) {
                $warnings[] = "La prioridad {$priority->label()} no coincide con la sección ".Str::ucfirst($raw['section'])."; se usará {$priority->label()}.";
            }

            $assignee = null;

            if (($fields['responsable'] ?? '') !== '') {
                [$assignee, $warning] = $this->resolveResponsible($fields['responsable'], $people, $project !== null);

                if ($warning !== null) {
                    $warnings[] = $warning;
                }
            }

            $description = $fields['descripcion'] ?? '';

            if ($description !== '' && ! str_contains($description, '(Origen:')) {
                $warnings[] = 'La descripción no incluye (Origen: ...).';
            }

            $key = $this->titleKey($title);
            $state = match (true) {
                $errors !== [] => 'error',
                isset($existing[$key]) => 'duplicate',
                isset($seen[$key]) => 'repeated',
                $warnings !== [] => 'warning',
                default => 'ok',
            };

            if ($state === 'duplicate') {
                $warnings = ['Ya existe una tarea con este nombre.'];
            } elseif ($state === 'repeated') {
                $warnings = ['Nombre repetido en el archivo; se importa solo la primera.'];
            }

            $seen[$key] = true;

            return [
                'line' => $raw['line'],
                'title' => $title,
                'type' => $type?->value,
                'typeLabel' => $type ? self::TYPE_LABELS[$type->value] : null,
                'priority' => $priority?->value,
                'priorityLabel' => $priority?->label(),
                'assignee' => $assignee ? ['id' => $assignee->id, 'name' => $assignee->name] : null,
                'responsibleText' => $fields['responsable'] ?? null,
                'description' => $description,
                'excerpt' => Str::limit(str_replace('`', '', $description), 140),
                'state' => $state,
                'messages' => [...$errors, ...$warnings],
            ];
        }, $parsed['tasks']);

        $importable = count(array_filter($tasks, fn (array $task) => in_array($task['state'], ['ok', 'warning'], true)));

        return [
            'destination' => [
                'projectId' => $project?->id,
                'label' => $project?->title ?? 'Backlog general',
            ],
            'tasks' => $tasks,
            'excluded' => $parsed['excluded'],
            'counts' => ['importable' => $importable, 'total' => count($tasks)],
        ];
    }

    /**
     * Convert a description into a Tiptap document, rendering `backticks` as inline code.
     */
    public static function toTiptap(string $text): string
    {
        $nodes = array_map(
            fn (string $part) => preg_match('/^`([^`]+)`$/u', $part, $code)
                ? ['type' => 'text', 'text' => $code[1], 'marks' => [['type' => 'code']]]
                : ['type' => 'text', 'text' => $part],
            preg_split('/(`[^`]+`)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY),
        );

        return json_encode(
            ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => $nodes]]],
            JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * Match the responsible by full name or first name (case- and accent-insensitive).
     *
     * @param  Collection<int, User>  $people
     * @return array{0: User|null, 1: string|null}
     */
    private function resolveResponsible(string $name, Collection $people, bool $inProject): array
    {
        $wanted = $this->normalize($name);
        $matches = $people->filter(function (User $person) use ($wanted) {
            $full = $this->normalize($person->name);

            return $full === $wanted || Str::before($full, ' ') === $wanted;
        });

        return match ($matches->count()) {
            1 => [$matches->first(), null],
            0 => [null, $inProject
                ? "No encontramos a «{$name}» en el proyecto; quedará sin responsable."
                : "Las tareas sueltas solo pueden asignarse a ti; «{$name}» quedará sin responsable."],
            default => [null, "Hay varias personas llamadas «{$name}»; quedará sin responsable."],
        };
    }

    /**
     * @return array<string, true>
     */
    private function existingTitles(?Project $project, User $user): array
    {
        $query = $project
            ? $project->tasks()
            : Task::query()->whereNull('project_id')->where(fn ($query) => $query
                ->where('author_id', $user->id)
                ->orWhere('assignee_id', $user->id));

        return $query->pluck('title')
            ->mapWithKeys(fn (string $title) => [$this->titleKey($title) => true])
            ->all();
    }

    private function titleKey(string $title): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $title)));
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }
}
