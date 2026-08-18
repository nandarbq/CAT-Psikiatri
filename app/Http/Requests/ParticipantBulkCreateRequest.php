<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantBulkCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*.password' => ['required', 'string', 'min:8', 'max:100'],
        ];

        foreach (ParticipantRequest::sharedFieldRules() as $field => $fieldRules) {
            $rules["participants.*.{$field}"] = $fieldRules;
        }

        $rules['participants.*.nik'] = array_merge($rules['participants.*.nik'], [
            'distinct',
            Rule::unique('participants', 'nik'),
        ]);
        $rules['participants.*.username'] = array_merge($rules['participants.*.username'], [
            'distinct',
            Rule::unique('participants', 'username'),
        ]);

        return $rules;
    }
}
