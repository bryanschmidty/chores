<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChoreInstanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->choreInstance) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $householdId = $this->user()?->household_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_points' => ['required', 'integer', 'min:1'],
            'assigned_to_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('household_id', $householdId),
            ],
            'deadline_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
