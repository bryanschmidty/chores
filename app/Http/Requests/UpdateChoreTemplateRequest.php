<?php

namespace App\Http\Requests;

use App\Enums\RecurrenceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChoreTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->choreTemplate) ?? false;
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
            'points' => ['required', 'integer', 'min:1'],
            'recurrence_type' => ['required', Rule::in(array_column(RecurrenceType::cases(), 'value'))],
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'max:365'],
            'recurrence_weekdays' => ['nullable', 'array'],
            'recurrence_weekdays.*' => ['integer', 'between:0,6'],
            'default_assignee_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('household_id', $householdId),
            ],
            'current_week_claim_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('household_id', $householdId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
