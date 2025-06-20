<?php

namespace Database\Factories;

use App\Models\ChecklistPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistPhotoFactory extends Factory
{
    protected $model = ChecklistPhoto::class;

    public function definition(): array
    {
        return [
            'checklist_item_id' => null, // to be set in seeder
            'photo_path' => $this->faker->imageUrl(640, 480, 'house', true),
        ];
    }
} 