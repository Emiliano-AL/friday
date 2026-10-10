<?php

namespace App\Http\Requests;

use App\Enums\ProjectColor;
use App\Enums\ProjectIcon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Ownership is enforced by the controller (404 for non-owners per
     * contract); the active-project rule lives in ProjectPolicy::update.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $key = $this->input('key');

        if (is_string($key)) {
            $this->merge(['key' => strtoupper(trim($key))]);
        }
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
            'description' => ['nullable', 'string', 'max:50000'],
            'key' => [
                'nullable',
                'string',
                'min:2',
                'max:5',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('projects', 'key')
                    ->where(fn ($query) => $query->where('owner_id', $this->user()->id))
                    ->ignore($this->route('project')),
            ],
            'color' => ['nullable', Rule::enum(ProjectColor::class)],
            'icon' => ['nullable', Rule::enum(ProjectIcon::class)],
            'target_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.max' => 'El contenido no puede superar los 50000 caracteres.',
            'key.min' => 'La clave debe tener al menos 2 caracteres.',
            'key.max' => 'La clave no puede tener más de 5 caracteres.',
            'key.regex' => 'La clave solo puede contener letras y números.',
            'key.unique' => 'Ya tienes un proyecto con esta clave.',
        ];
    }
}
