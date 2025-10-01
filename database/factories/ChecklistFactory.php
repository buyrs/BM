<?php

namespace Database\Factories;

use App\Models\Checklist;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistFactory extends Factory
{
    protected $model = Checklist::class;

    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'type' => $this->faker->randomElement(['checkin', 'checkout', 'maintenance']),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed']),
            'submitted_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'signature_path' => $this->faker->optional()->filePath(),
            'guest_token' => $this->faker->uuid(),
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'submitted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'submitted_at' => null,
            ];
        });
    }
}