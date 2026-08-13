<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'order_no' => fake()->unique()->numberBetween(1, 1_000_000),
            'question' => fake()->sentence(10).'?',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function forModule(Module $module): static
    {
        return $this->state(fn (array $attributes): array => [
            'module_id' => $module->id,
        ]);
    }
}
