<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $storing = $this->isMethod('post');
        $standalone = $this->input('project_id') === null;

        return [
            'title' => [$storing ? 'required' : 'sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:50000'],
            'type' => ['sometimes', Rule::enum(TaskType::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'project_id' => ['nullable', 'exists:projects,id'],
            'assignee_id' => [
                $storing && $standalone ? 'required' : 'nullable',
                'exists:users,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->validateAssignee($value, $fail);
                },
            ],
            'sprint_id' => [
                'nullable',
                'exists:sprints,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->validateSprint($value, $fail);
                },
            ],
        ];
    }

    /**
     * @param  Closure(string): void  $fail
     */
    private function validateAssignee(mixed $value, Closure $fail): void
    {
        $projectId = $this->input('project_id');

        if ($value === null) {
            if ($projectId === null && $this->isMethod('post')) {
                $fail('Asigna la tarea a alguien.');
            }

            return;
        }

        if ($projectId === null) {
            if ((int) $value !== $this->user()->id) {
                $fail('Las tareas sin proyecto solo puedes asignártelas a ti.');
            }

            return;
        }

        $assignee = User::query()->whereKey($value)->first();
        $project = Project::query()->whereKey($projectId)->first();

        if ($assignee !== null && $project !== null && ! $project->isMember($assignee)) {
            $fail('El responsable debe ser miembro del proyecto.');
        }
    }

    /**
     * @param  Closure(string): void  $fail
     */
    private function validateSprint(mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $projectId = $this->input('project_id');

        if ($projectId === null) {
            $fail('Una tarea sin proyecto no puede tener sprint.');

            return;
        }

        $sprint = Sprint::query()->whereKey($value)->first();

        if ($sprint !== null && (int) $sprint->project_id !== (int) $projectId) {
            $fail('El sprint debe pertenecer al mismo proyecto que la tarea.');
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título de la tarea es obligatorio.',
            'description.max' => 'El contenido no puede superar los 50000 caracteres.',
            'type.enum' => 'El tipo de tarea no es válido.',
            'priority.enum' => 'La prioridad de la tarea no es válida.',
            'status.enum' => 'El estado de la tarea no es válido.',
        ];
    }
}
