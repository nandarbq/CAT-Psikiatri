<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExamAttempt>
 */
class ExamAttemptFactory extends Factory
{
    protected $model = ExamAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'participant_id' => Participant::factory(),
            'session_id' => ExamSession::factory(),
            'started_at' => now()->subMinutes(10),
            'finished_at' => null,
            'current_question' => 1,
            'is_submitted' => false,
            'session_token' => Str::random(80),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_submitted' => true,
            'finished_at' => now(),
        ]);
    }

    public function forParticipant(Participant $participant): static
    {
        return $this->state(fn (array $attributes): array => [
            'participant_id' => $participant->id,
            'session_id' => $participant->session_id,
        ]);
    }

    public function forSession(ExamSession $session): static
    {
        return $this->state(fn (array $attributes): array => [
            'session_id' => $session->id,
        ]);
    }
}
