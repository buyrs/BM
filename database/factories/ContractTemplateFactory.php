<?php

namespace Database\Factories;

use App\Models\ContractTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractTemplateFactory extends Factory
{
    protected $model = ContractTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . ' Contract Template',
            'type' => $this->faker->randomElement(['entry', 'exit']),
            'content' => $this->faker->paragraphs(5, true),
            'admin_signature' => $this->faker->optional()->sha256,
            'admin_signed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'created_by' => User::factory(),
        ];
    }

    public function entry(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'entry',
            'name' => 'Entry Contract Template',
        ]);
    }

    public function exit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'exit',
            'name' => 'Exit Contract Template',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'admin_signature' => $this->faker->sha256,
            'admin_signed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function unsigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'admin_signature' => null,
            'admin_signed_at' => null,
        ]);
    }
}