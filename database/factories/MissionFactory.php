<?php

namespace Database\Factories;

use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['checkin', 'checkout'];
        $statuses = ['unassigned', 'assigned', 'in_progress', 'completed', 'cancelled'];

        return [
            'type' => $this->faker->randomElement($types),
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'address' => $this->faker->address(),
            'tenant_name' => $this->faker->name(),
            'tenant_phone' => $this->faker->optional()->phoneNumber(),
            'tenant_email' => $this->faker->optional()->safeEmail(),
            'notes' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->randomElement($statuses),
            'agent_id' => null, // Will be assigned by the seeder if needed
        ];
    }
} 