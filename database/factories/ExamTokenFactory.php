<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\ExamToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamToken>
 */
class ExamTokenFactory extends Factory
{
    protected $model = ExamToken::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => ExamSession::factory(),
            'token' => fake()->unique()->numerify('######'),
            'is_active' => true,
            'expires_at' => null,
            'last_used_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function forSession(ExamSession $session): static
    {
        return $this->state(fn (array $attributes): array => [
            'session_id' => $session->id,
        ]);
    }
}
