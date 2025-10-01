<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\AmenityType;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            // Kitchen Appliances
            ['name' => 'Refrigerator', 'type' => 'Kitchen Appliances'],
            ['name' => 'Stove/Oven', 'type' => 'Kitchen Appliances'],
            ['name' => 'Microwave', 'type' => 'Kitchen Appliances'],
            ['name' => 'Dishwasher', 'type' => 'Kitchen Appliances'],
            ['name' => 'Coffee Maker', 'type' => 'Kitchen Appliances'],

            // Bathroom Fixtures
            ['name' => 'Toilet', 'type' => 'Bathroom Fixtures'],
            ['name' => 'Shower/Bathtub', 'type' => 'Bathroom Fixtures'],
            ['name' => 'Sink/Faucet', 'type' => 'Bathroom Fixtures'],
            ['name' => 'Mirror', 'type' => 'Bathroom Fixtures'],
            ['name' => 'Towel Rack', 'type' => 'Bathroom Fixtures'],

            // Furniture
            ['name' => 'Bed Frame', 'type' => 'Furniture'],
            ['name' => 'Mattress', 'type' => 'Furniture'],
            ['name' => 'Sofa', 'type' => 'Furniture'],
            ['name' => 'Dining Table', 'type' => 'Furniture'],
            ['name' => 'Chairs', 'type' => 'Furniture'],

            // Electrical Systems
            ['name' => 'Light Switches', 'type' => 'Electrical Systems'],
            ['name' => 'Power Outlets', 'type' => 'Electrical Systems'],
            ['name' => 'Ceiling Lights', 'type' => 'Electrical Systems'],
            ['name' => 'Smoke Detectors', 'type' => 'Electrical Systems'],
            ['name' => 'Circuit Breaker', 'type' => 'Electrical Systems'],

            // Plumbing Systems
            ['name' => 'Water Heater', 'type' => 'Plumbing Systems'],
            ['name' => 'Pipes', 'type' => 'Plumbing Systems'],
            ['name' => 'Water Pressure', 'type' => 'Plumbing Systems'],
            ['name' => 'Drainage', 'type' => 'Plumbing Systems'],

            // HVAC Systems
            ['name' => 'Air Conditioning', 'type' => 'HVAC Systems'],
            ['name' => 'Heating System', 'type' => 'HVAC Systems'],
            ['name' => 'Thermostat', 'type' => 'HVAC Systems'],
            ['name' => 'Air Vents', 'type' => 'HVAC Systems'],

            // Safety Equipment
            ['name' => 'Fire Extinguisher', 'type' => 'Safety Equipment'],
            ['name' => 'First Aid Kit', 'type' => 'Safety Equipment'],
            ['name' => 'Emergency Exit', 'type' => 'Safety Equipment'],
            ['name' => 'Carbon Monoxide Detector', 'type' => 'Safety Equipment'],

            // Exterior Elements
            ['name' => 'Front Door', 'type' => 'Exterior Elements'],
            ['name' => 'Windows', 'type' => 'Exterior Elements'],
            ['name' => 'Roof', 'type' => 'Exterior Elements'],
            ['name' => 'Gutters', 'type' => 'Exterior Elements'],

            // Interior Finishes
            ['name' => 'Paint/Walls', 'type' => 'Interior Finishes'],
            ['name' => 'Flooring', 'type' => 'Interior Finishes'],
            ['name' => 'Ceiling', 'type' => 'Interior Finishes'],
            ['name' => 'Baseboards', 'type' => 'Interior Finishes'],

            // Security Systems
            ['name' => 'Door Locks', 'type' => 'Security Systems'],
            ['name' => 'Security Camera', 'type' => 'Security Systems'],
            ['name' => 'Alarm System', 'type' => 'Security Systems'],
            ['name' => 'Window Locks', 'type' => 'Security Systems'],
        ];

        foreach ($amenities as $amenity) {
            $amenityType = AmenityType::where('name', $amenity['type'])->first();

            Amenity::create([
                'name' => $amenity['name'],
                'amenity_type_id' => $amenityType->id,
                'property_id' => 1, // Default property ID
            ]);
        }
    }
}