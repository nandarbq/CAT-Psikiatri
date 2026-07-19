<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\ExamToken;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StartExamService
{
    public function __construct(private readonly ValidateSessionService $validateSessionService)
    {
    }

    public function startOrResume(Participant $participant, string $token, Request $request): ExamAttempt
    {
        $tokenBypassEnabled = (bool) config('exam.token_bypass_enabled', false);
        $tokenRecord = null;

        if (! $tokenBypassEnabled) {
            $normalizedToken = trim($token);
            $tokenRecord = ExamToken::query()
                ->where('session_id', $participant->session_id)
                ->where('token', $normalizedToken)
                ->first();

            if ($tokenRecord === null) {
                throw ValidationException::withMessages([
                    'token' => 'Token ujian tidak valid.',
                ]);
            }

            if (! $tokenRecord->is_active) {
                throw ValidationException::withMessages([
                    'token' => 'Token ujian nonaktif. Hubungi admin.',
                ]);
            }

            if ($tokenRecord->expires_at !== null && now()->greaterThan($tokenRecord->expires_at)) {
                throw ValidationException::withMessages([
                    'token' => 'Token ujian sudah kedaluwarsa.',
                ]);
            }
        }

        $this->validateSessionService->ensureSessionActive($participant->session);
        $this->validateSessionService->ensureSessionLocationAccess(
            $participant->session,
            $this->toNullableFloat($request->input('latitude')),
            $this->toNullableFloat($request->input('longitude')),
            $this->toNullableFloat($request->input('accuracy')),
        );

        return DB::transaction(function () use ($participant, $request, $tokenRecord): ExamAttempt {
            $attempt = ExamAttempt::query()
                ->where('participant_id', $participant->id)
                ->where('session_id', $participant->session_id)
                ->first();

            if ($tokenRecord instanceof ExamToken) {
                $tokenRecord->forceFill(['last_used_at' => now()])->save();
            }

            if ($attempt !== null) {
                if ($attempt->is_submitted) {
                    throw ValidationException::withMessages([
                        'attempt' => 'Peserta sudah pernah mengikuti tes pada sesi ini dan tidak dapat mengulang.',
                    ]);
                }

                $this->validateSessionService->ensureAttemptAccessible($attempt->load('session'));
                $attempt->forceFill(['session_token' => Str::random(80)])->save();

                return $attempt;
            }

            return ExamAttempt::query()->create([
                'participant_id' => $participant->id,
                'session_id' => $participant->session_id,
                'started_at' => now(),
                'current_question' => 1,
                'is_submitted' => false,
                'session_token' => Str::random(80),
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ])->load('session');
        });
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
