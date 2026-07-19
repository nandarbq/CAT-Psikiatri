<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantRequest extends FormRequest
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
        /** @var Participant|null $participant */
        $participant = $this->route('participant');
        $participantId = $participant?->id;
        $isCreate = $participantId === null;

        return [
            'session_id' => ['required', 'integer', 'exists:exam_sessions,id'],
            'nik' => ['required', 'string', 'max:30', Rule::unique('participants', 'nik')->ignore($participantId)],
            'username' => ['required', 'string', 'max:50', Rule::unique('participants', 'username')->ignore($participantId)],
            'password' => [$participantId === null ? 'required' : 'nullable', 'string', 'min:8', 'max:100'],
            'code' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:2000'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'is_finished' => [$isCreate ? 'nullable' : 'required', 'boolean'],
        ];
    }
}
