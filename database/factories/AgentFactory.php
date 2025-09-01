<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'agent_code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{4}'),
            'phone_number' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'refusals_count' => $this->faker->numberBetween(0, 5),
            'refusals_month' => $this->faker->numberBetween(1, 12),
            'is_downgraded' => $this->faker->boolean(20), // 20% chance of being downgraded
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function downgraded(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_downgraded' => true,
            'refusals_count' => $this->faker->numberBetween(3, 5),
        ]);
    }
}