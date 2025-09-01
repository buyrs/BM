<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\BailMobilite;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['EXIT_REMINDER', 'CHECKLIST_VALIDATION', 'INCIDENT_ALERT', 'MISSION_ASSIGNED']),
            'recipient_id' => User::factory(),
            'bail_mobilite_id' => $this->faker->optional()->randomElement([null, BailMobilite::factory()]),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
            'status' => $this->faker->randomElement(['pending', 'sent', 'cancelled']),
            'data' => [
                'message' => $this->faker->sentence,
                'additional_info' => $this->faker->optional()->words(5, true),
            ],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'sent_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function exitReminder(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'EXIT_REMINDER',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'data' => [
                'message' => 'Bail Mobilité ending in 10 days',
                'days_remaining' => 10,
            ],
        ]);
    }

    public function checklistValidation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'CHECKLIST_VALIDATION',
            'data' => [
                'message' => 'Checklist requires validation',
                'mission_type' => $this->faker->randomElement(['entry', 'exit']),
            ],
        ]);
    }

    public function incidentAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'INCIDENT_ALERT',
            'data' => [
                'message' => 'Incident detected',
                'incident_type' => $this->faker->randomElement(['keys_not_returned', 'missing_signature', 'checklist_not_validated']),
            ],
        ]);
    }

    public function missionAssigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'MISSION_ASSIGNED',
            'data' => [
                'message' => 'New mission assigned',
                'mission_type' => $this->faker->randomElement(['entry', 'exit']),
                'scheduled_time' => $this->faker->time(),
            ],
        ]);
    }
}