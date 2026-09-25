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

class UpdateTaskRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(TaskType::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'assignee_id' => [
                'nullable',
                'exists:users,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $assignee = User::query()->whereKey($value)->first();

                    if ($assignee !== null && ! $this->project()->isMember($assignee)) {
                        $fail('El responsable debe ser miembro del proyecto.');
                    }
                },
            ],
            'sprint_id' => [
                'nullable',
                'exists:sprints,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $sprint = Sprint::query()->whereKey($value)->first();

                    if ($sprint !== null && $sprint->project_id !== $this->project()->id) {
                        $fail('El sprint debe pertenecer al mismo proyecto que la tarea.');
                    }
                },
            ],
        ];
    }

    /**
     * Get the project from the route parameters.
     */
    private function project(): Project
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $project;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título de la tarea es obligatorio.',
            'type.enum' => 'El tipo de tarea no es válido.',
            'priority.enum' => 'La prioridad de la tarea no es válida.',
            'status.enum' => 'El estado de la tarea no es válido.',
        ];
    }
}
