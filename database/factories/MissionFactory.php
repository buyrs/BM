<?php

namespace Database\Factories;

use App\Models\Mission;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MissionFactory extends Factory
{
    protected $model = Mission::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'property_address' => $this->faker->address(),
            'checkin_date' => $this->faker->dateTimeBetween('now', '+1 week'),
            'checkout_date' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'cancelled']),
            'admin_id' => User::factory(),
            'ops_id' => User::factory(),
            'checker_id' => User::factory(),
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'completed_at' => null,
            ];
        });
    }

    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_progress',
                'completed_at' => null,
            ];
        });
    }
}