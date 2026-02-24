<?php

namespace App\Http\Requests;

use App\Models\ChoreCompletion;
use Illuminate\Foundation\Http\FormRequest;

class ApproveChoreCompletionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $completion = $this->route('choreCompletion');

        return $completion instanceof ChoreCompletion
            && ($this->user()?->can('approve', $completion) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supervisor_adjusted_points' => ['nullable', 'integer', 'min:0'],
            'approval_comment' => ['nullable', 'string'],
        ];
    }
}
