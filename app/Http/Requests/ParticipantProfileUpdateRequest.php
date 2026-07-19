<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantProfileUpdateRequest extends FormRequest
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
        $participant = $this->attributes->get('participant');
        $participantId = $participant?->id;

        return [
            'nik' => ['required', 'string', 'max:30', Rule::unique('participants', 'nik')->ignore($participantId)],
            'name' => ['required', 'string', 'max:255'],
            'birth_place' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'address' => ['required', 'string', 'max:2000'],
            'purpose' => ['required', 'string', 'max:255'],
        ];
    }
}

