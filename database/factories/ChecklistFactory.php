<?php

namespace Database\Factories;

use App\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistFactory extends Factory
{
    protected $model = Checklist::class;

    public function definition(): array
    {
        return [
            'mission_id' => null, // to be set in seeder
            'general_info' => [
                'heating' => [
                    'type' => $this->faker->randomElement(['gas', 'electric', 'none']),
                    'condition' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'comment' => $this->faker->sentence(),
                ],
                'hot_water' => [
                    'type' => $this->faker->randomElement(['boiler', 'central', 'none']),
                    'condition' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'comment' => $this->faker->sentence(),
                ],
                'keys' => [
                    'count' => $this->faker->numberBetween(1, 5),
                    'condition' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'comment' => $this->faker->sentence(),
                ],
            ],
            'rooms' => [
                'entrance' => [
                    'walls' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'floor' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'ceiling' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'door' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'windows' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'electrical' => $this->faker->randomElement(['good', 'fair', 'poor']),
                ],
                'living_room' => [
                    'walls' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'floor' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'ceiling' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'windows' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'electrical' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'heating' => $this->faker->randomElement(['good', 'fair', 'poor']),
                ],
                'kitchen' => [
                    'walls' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'floor' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'ceiling' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'windows' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'electrical' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'plumbing' => $this->faker->randomElement(['good', 'fair', 'poor']),
                    'appliances' => $this->faker->randomElement(['good', 'fair', 'poor']),
                ],
            ],
            'utilities' => [
                'electricity_meter' => [
                    'number' => $this->faker->numerify('EM####'),
                    'reading' => $this->faker->randomFloat(2, 0, 9999),
                ],
                'gas_meter' => [
                    'number' => $this->faker->numerify('GM####'),
                    'reading' => $this->faker->randomFloat(2, 0, 9999),
                ],
                'water_meter' => [
                    'number' => $this->faker->numerify('WM####'),
                    'reading' => $this->faker->randomFloat(2, 0, 9999),
                ],
            ],
            'tenant_signature' => null,
            'agent_signature' => null,
            'status' => $this->faker->randomElement(['draft', 'completed']),
        ];
    }
} 