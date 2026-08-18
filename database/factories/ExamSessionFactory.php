<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExamSession>
 */
class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Sesi '.fake()->unique()->words(3, true),
            'module_id' => Module::factory(),
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 60,
            'status' => true,
            'access_mode' => ExamSession::ACCESS_MODE_ONLINE,
            'instructions' => null,
        ];
    }

    public function onsiteRadius(): static
    {
        return $this->state(fn (array $attributes): array => [
            'access_mode' => ExamSession::ACCESS_MODE_ONSITE_RADIUS,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => false,
        ]);
    }

    /**
     * Sesi yang sudah berakhir (start_time & end_time di masa lalu) —
     * berguna untuk menguji ensureSessionActive() menolak sesi kedaluwarsa.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_time' => now()->subHours(3),
            'end_time' => now()->subHour(),
        ]);
    }

    public function notStartedYet(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_time' => now()->addHour(),
            'end_time' => now()->addHours(3),
        ]);
    }
}
