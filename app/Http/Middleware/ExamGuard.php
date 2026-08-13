<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ExamAttempt;
use App\Services\ValidateSessionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ExamGuard
{
    public function __construct(
        private readonly ValidateSessionService $validateSessionService
    ) {
    }

    /**
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $participantId = (int) $request->session()->get('participant_id', 0);
        $attemptId = (int) $request->session()->get('exam_attempt_id', 0);
        $sessionToken = (string) $request->session()->get(
            'exam_session_token',
            ''
        );

        if ($participantId === 0) {
            return $this->deny(
                $request,
                'Sesi peserta tidak valid. Silakan login ulang.'
            );
        }

        $relations = $request->expectsJson()
            ? ['session.module']
            : ['session.module', 'participant'];

        $attempt = $attemptId > 0
            ? ExamAttempt::query()
                ->with($relations)
                ->find($attemptId)
            : null;

        if (
            $attempt === null ||
            (int) $attempt->participant_id !== $participantId
        ) {
            $attempt = ExamAttempt::query()
                ->with($relations)
                ->where('participant_id', $participantId)
                ->where('is_submitted', false)
                ->latest('id')
                ->first();
        }

        if (
            $attempt === null ||
            (int) $attempt->participant_id !== $participantId
        ) {
            return $this->deny(
                $request,
                'Akses tes tidak valid. Silakan mulai lagi dari halaman akses tes.'
            );
        }

        $attemptSessionToken = is_string($attempt->session_token ?? null)
            ? trim((string) $attempt->session_token)
            : '';

        if ($attemptSessionToken === '') {
            $attemptSessionToken = Str::random(80);

            $attempt->forceFill([
                'session_token' => $attemptSessionToken,
            ])->save();
        }

        $isFirstRequestAfterLogin = $sessionToken === '';

        if (
            ! $isFirstRequestAfterLogin &&
            ! hash_equals($attemptSessionToken, $sessionToken)
        ) {
            return $this->deny(
                $request,
                'Token sesi tidak valid. Silakan login ulang atau mulai ulang tes.'
            );
        }

        if (
            $attemptId !== (int) $attempt->id ||
            $sessionToken === ''
        ) {
            $request->session()->put(
                'exam_attempt_id',
                (int) $attempt->id
            );

            $request->session()->put(
                'exam_session_token',
                $attemptSessionToken
            );

            $request->session()->save();
        }

        try {
            $this->validateSessionService
                ->ensureAttemptAccessible($attempt);
        } catch (ValidationException $exception) {
            return $this->deny(
                $request,
                collect($exception->errors())
                    ->flatten()
                    ->first() ?? 'Akses tes ditolak.'
            );
        }

    
        $isSubmitRoute = $request->routeIs('exam.submit');

        if (! $isSubmitRoute) {
            $denied = $this->enforceLocationWithGracePeriod(
                $request,
                $attempt
            );

            if ($denied !== null) {
                return $denied;
            }
        }

        $request->attributes->set('examAttempt', $attempt);

        return $next($request);
    }


    private function enforceLocationWithGracePeriod(
        Request $request,
        ExamAttempt $attempt
    ): JsonResponse|RedirectResponse|null {
        [$latitude, $longitude, $accuracy] =
            $this->extractLocation($request);

        try {
            $this->validateSessionService
                ->ensureSessionLocationAccess(
                    $attempt->session,
                    $latitude,
                    $longitude,
                    $accuracy
                );

            
            $request->session()->forget(
                'exam_geo_out_of_range_since'
            );

            return null;

        } catch (ValidationException $exception) {
            $request->session()->put(
                'exam_geo_out_of_range_since',
                now()->timestamp
            );
            $request->session()->save();

            return $this->deny(
                $request,
                collect($exception->errors())
                    ->flatten()
                    ->first() ?? 'Akses tes ditolak.'
            );
        }
    }

    private function deny(
        Request $request,
        string $message
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        $request->session()->forget([
            'exam_attempt_id',
            'exam_session_token',
        ]);

        return redirect()
            ->route('exam.access')
            ->withErrors([
                'session' => $message,
            ]);
    }

    
    private function extractLocation(Request $request): array
    {
        $latitude = $this->toNullableFloat(
            $request->header('X-Exam-Latitude')
        );

        $longitude = $this->toNullableFloat(
            $request->header('X-Exam-Longitude')
        );

        $accuracy = $this->toNullableFloat(
            $request->header('X-Exam-Accuracy')
        );

      
        if (
            $latitude !== null &&
            $longitude !== null
        ) {
            $request->session()->put(
                'exam_geo_last_latitude',
                $latitude
            );

            $request->session()->put(
                'exam_geo_last_longitude',
                $longitude
            );

            $request->session()->put(
                'exam_geo_last_accuracy',
                $accuracy
            );

            $request->session()->put(
                'exam_geo_last_at',
                now()->timestamp
            );

            return [
                $latitude,
                $longitude,
                $accuracy,
            ];
        }

        /*
         * Untuk request AJAX/JSON, jangan menggunakan
         * cache lokasi lama.
         */
        if ($request->expectsJson()) {
            return [null, null, null];
        }

       
        $cachedAt = $request->session()->get(
            'exam_geo_last_at'
        );

        $ttlSeconds = (int) config(
            'exam.geo_cache_ttl_seconds',
            90
        );

        
        if (
            ! is_numeric($cachedAt) ||
            now()->timestamp - (int) $cachedAt > $ttlSeconds
        ) {
            return [null, null, null];
        }

        
        return [
            $this->toNullableFloat(
                $request->session()->get(
                    'exam_geo_last_latitude'
                )
            ),
            $this->toNullableFloat(
                $request->session()->get(
                    'exam_geo_last_longitude'
                )
            ),
            $this->toNullableFloat(
                $request->session()->get(
                    'exam_geo_last_accuracy'
                )
            ),
        ];
    }

    private function toNullableFloat(mixed $value): ?float
    {
        return is_numeric($value)
            ? (float) $value
            : null;
    }
}
