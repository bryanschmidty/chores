<?php

namespace App\Http\Requests;

use App\Models\ChoreInstance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreChoreCompletionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['parent', 'kid', 'supervisor']) ?? false;
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
            'chore_instance_id' => [
                'required',
                Rule::exists('chore_instances', 'id')->where('household_id', $householdId),
            ],
            'helper_user_ids' => ['nullable', 'array'],
            'helper_user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('household_id', $householdId),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $helperUserIds = collect($this->input('helper_user_ids', []))
                ->map(static fn (mixed $userId): int => (int) $userId)
                ->all();

            if (in_array((int) $this->user()->id, $helperUserIds, true)) {
                $validator->errors()->add('helper_user_ids', 'Primary doer cannot also be a helper.');
            }

            $choreInstanceId = (int) $this->input('chore_instance_id');
            if ($choreInstanceId <= 0) {
                return;
            }

            $choreInstance = ChoreInstance::query()->find($choreInstanceId);
            if ($choreInstance === null) {
                return;
            }

            if ($choreInstance->household_id !== $this->user()->household_id) {
                $validator->errors()->add('chore_instance_id', 'The selected chore is invalid.');
            }
        });
    }
}
