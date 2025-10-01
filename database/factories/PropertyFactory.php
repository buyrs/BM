<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'owner_name' => $this->faker->name,
            'owner_address' => $this->faker->address,
            'property_address' => $this->faker->address,
            'property_type' => $this->faker->randomElement(['classic', 'vip']),
            'description' => $this->faker->sentence,
        ];
    }
}