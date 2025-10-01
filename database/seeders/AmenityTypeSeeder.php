<?php

namespace Database\Seeders;

use App\Models\AmenityType;
use Illuminate\Database\Seeder;

class AmenityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenityTypes = [
            'Kitchen Appliances',
            'Bathroom Fixtures',
            'Furniture',
            'Electrical Systems',
            'Plumbing Systems',
            'HVAC Systems',
            'Safety Equipment',
            'Exterior Elements',
            'Interior Finishes',
            'Security Systems',
        ];

        foreach ($amenityTypes as $type) {
            AmenityType::create([
                'name' => $type,
            ]);
        }
    }
}