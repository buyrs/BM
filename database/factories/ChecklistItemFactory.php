<?php

namespace Database\Factories;

use App\Models\ChecklistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistItemFactory extends Factory
{
    protected $model = ChecklistItem::class;

    public function definition(): array
    {
        return [
            'checklist_id' => null, // to be set in seeder
            'category' => $this->faker->randomElement(['entrance', 'living_room', 'kitchen']),
            'item_name' => $this->faker->word(),
            'condition' => $this->faker->randomElement(['perfect', 'good', 'damaged', 'broken', null]),
            'comment' => $this->faker->optional()->sentence(),
        ];
    }
} 