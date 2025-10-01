<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement([
                'login_successful',
                'logout',
                'created',
                'updated',
                'deleted',
                'view',
                'export'
            ]),
            'resource_type' => $this->faker->randomElement([
                'App\Models\User',
                'App\Models\Property',
                'App\Models\Mission',
                'App\Models\Checklist'
            ]),
            'resource_id' => $this->faker->numberBetween(1, 1000),
            'changes' => [
                'field1' => $this->faker->word,
                'field2' => $this->faker->sentence,
                'timestamp' => now()->toISOString()
            ],
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now')
        ];
    }

    public function withoutUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    public function withAction(string $action): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => $action,
        ]);
    }

    public function withResourceType(string $resourceType): static
    {
        return $this->state(fn (array $attributes) => [
            'resource_type' => $resourceType,
        ]);
    }

    public function old(int $days = 400): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays($days),
        ]);
    }

    public function recent(int $days = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subDays($days),
        ]);
    }
}