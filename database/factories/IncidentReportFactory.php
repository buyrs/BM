<?php

namespace Database\Factories;

use App\Models\IncidentReport;
use App\Models\BailMobilite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = IncidentReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['open', 'in_progress', 'resolved', 'closed'];
        $types = ['missing_checklist', 'incomplete_checklist', 'missing_tenant_signature'];
        $severities = ['low', 'medium', 'high', 'critical'];

        return [
            'bail_mobilite_id' => BailMobilite::factory(),
            'mission_id' => null,
            'checklist_id' => null,
            'type' => $this->faker->randomElement($types),
            'severity' => $this->faker->randomElement($severities),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'metadata' => null,
            'status' => $this->faker->randomElement($statuses),
            'detected_at' => $this->faker->dateTimeThisMonth(),
            'resolved_at' => null,
            'created_by' => User::factory(),
            'resolved_by' => null,
            'resolution_notes' => null,
        ];
    }
}
