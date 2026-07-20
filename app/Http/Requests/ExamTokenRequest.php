<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ExamToken;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamTokenRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('token')) {
            $normalizedToken = preg_replace('/\D+/', '', (string) $this->input('token'));
            $this->merge([
                'token' => (string) ($normalizedToken ?? ''),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ExamToken|null $examToken */
        $examToken = $this->route('token');
        $tokenId = $examToken?->id;

        return [
            'session_id' => ['required', 'integer', 'exists:exam_sessions,id', Rule::unique('exam_tokens', 'session_id')->ignore($tokenId)],
            'token' => ['required', 'digits:6', Rule::unique('exam_tokens', 'token')->ignore($tokenId)],
            'is_active' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'token.digits' => 'Token tes harus berupa 6 digit angka.',
            'session_id.unique' => 'Sesi ini sudah memiliki token. Silakan ubah token yang sudah ada.',
        ];
    }
}
