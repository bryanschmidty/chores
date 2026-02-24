<?php

namespace App\Http\Requests;

use App\Models\WeeklyClaim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeeklyClaimRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', WeeklyClaim::class) ?? false;
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
            'chore_template_id' => [
                'required',
                Rule::exists('chore_templates', 'id')->where('household_id', $householdId),
            ],
            'assigned_to_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('household_id', $householdId),
            ],
        ];
    }
}
