<?php

namespace Database\Factories;

use App\Models\BailMobilite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BailMobiliteFactory extends Factory
{
    protected $model = BailMobilite::class;

    public function definition(): array
    {
        return [
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'address' => $this->faker->address,
            'tenant_name' => $this->faker->name,
            'tenant_phone' => $this->faker->phoneNumber,
            'tenant_email' => $this->faker->email,
            'notes' => $this->faker->optional()->text(200),
            'status' => 'assigned', // Default status
            'ops_user_id' => User::factory(),
        ];
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'assigned',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function incident(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'incident',
        ]);
    }
}