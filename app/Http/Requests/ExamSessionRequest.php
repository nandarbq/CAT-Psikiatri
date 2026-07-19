<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ExamSession;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamSessionRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:360'],
            'status' => ['required', 'boolean'],
            'access_mode' => ['required', Rule::in([
                ExamSession::ACCESS_MODE_ONLINE,
                ExamSession::ACCESS_MODE_ONSITE_RADIUS,
            ])],
            'instructions' => ['nullable', 'string', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $accessMode = $this->input('access_mode', ExamSession::ACCESS_MODE_ONLINE);

        $this->merge([
            'access_mode' => $accessMode,
        ]);
    }
}
