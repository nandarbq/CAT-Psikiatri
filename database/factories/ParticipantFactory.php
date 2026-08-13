<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'session_id' => ExamSession::factory(),
            'nik' => fake()->unique()->numerify('################'),
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'code' => fake()->unique()->bothify('CODE-####??'),
            'name' => fake()->name(),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->date('Y-m-d', '-20 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'purpose' => 'Tes Psikiatri',
            'login_token' => null,
            'is_finished' => false,
        ];
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_finished' => true,
        ]);
    }

    public function forSession(ExamSession $session): static
    {
        return $this->state(fn (array $attributes): array => [
            'session_id' => $session->id,
        ]);
    }
}
