<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $properties = [
            [
                'property_address' => '123 Main Street, Downtown, NY 10001',
                'owner_name' => 'John Smith',
                'owner_address' => '456 Oak Avenue, Uptown, NY 10002',
                'description' => 'Modern 3-bedroom apartment complex with excellent downtown location. Features include in-unit laundry, parking garage, and rooftop garden.',
            ],
            [
                'property_address' => '789 Broadway Avenue, Midtown, NY 10003',
                'owner_name' => 'Sarah Johnson',
                'owner_address' => '321 Pine Street, Brooklyn, NY 11201',
                'description' => 'Luxury high-rise condominium with panoramic city views. Amenities include fitness center, concierge service, and business center.',
            ],
            [
                'property_address' => '456 River Drive, Westside, NY 10004',
                'owner_name' => 'Michael Brown',
                'owner_address' => '789 Elm Street, Queens, NY 11101',
                'description' => 'Charming riverside townhouse complex with private patios and waterfront access. Perfect for families seeking tranquil living.',
            ],
            [
                'property_address' => '321 Park Lane, Upper East, NY 10005',
                'owner_name' => 'Emily Davis',
                'owner_address' => '147 Maple Street, Manhattan, NY 10006',
                'description' => 'Historic brownstone converted into luxury apartments. Original architecture preserved with modern amenities added.',
            ],
            [
                'property_address' => '987 Tech Boulevard, Innovation District, NY 10007',
                'owner_name' => 'Robert Wilson',
                'owner_address' => '258 Cedar Avenue, Bronx, NY 10456',
                'description' => 'State-of-the-art smart building with high-speed internet, co-working spaces, and electric vehicle charging stations.',
            ],
            [
                'property_address' => '654 Garden Street, Green Valley, NY 10008',
                'owner_name' => 'Lisa Anderson',
                'owner_address' => '369 Birch Road, Staten Island, NY 10301',
                'description' => 'Eco-friendly residential complex with solar panels, green roofs, and organic community gardens.',
            ],
            [
                'property_address' => '147 Commerce Plaza, Business District, NY 10009',
                'owner_name' => 'David Taylor',
                'owner_address' => '741 Spruce Street, Long Island City, NY 11106',
                'description' => 'Mixed-use development with retail spaces on ground floor and residential units above. Great for urban professionals.',
            ],
            [
                'property_address' => '852 Sunset Boulevard, Arts Quarter, NY 10010',
                'owner_name' => 'Jennifer Martinez',
                'owner_address' => '963 Willow Lane, Greenwich Village, NY 10011',
                'description' => 'Artist loft-style apartments with high ceilings, large windows, and creative community spaces. Near galleries and theaters.',
            ],
            [
                'property_address' => '258 Harbor View Drive, Waterfront, NY 10012',
                'owner_name' => 'Christopher Garcia',
                'owner_address' => '159 Chestnut Street, Chelsea, NY 10013',
                'description' => 'Luxury waterfront condominiums with marina access, infinity pool, and 24/7 security. Premium harbor views from every unit.',
            ],
            [
                'property_address' => '741 University Heights, College Town, NY 10014',
                'owner_name' => 'Amanda Rodriguez',
                'owner_address' => '357 Poplar Street, SoHo, NY 10012',
                'description' => 'Student-friendly housing complex near major universities. Furnished units with study lounges, gym, and shuttle service.',
            ],
            [
                'property_address' => '369 Medical Center Drive, Hospital District, NY 10015',
                'owner_name' => 'James Thompson',
                'owner_address' => '486 Sycamore Avenue, TriBeCa, NY 10013',
                'description' => 'Healthcare professionals housing with flexible lease terms. Walking distance to major hospitals and medical facilities.',
            ],
            [
                'property_address' => '159 Shopping Center Way, Retail Hub, NY 10016',
                'owner_name' => 'Michelle White',
                'owner_address' => '627 Hickory Street, East Village, NY 10003',
                'description' => 'Convenient location above shopping complex with retail discounts for residents. Great public transportation access.',
            ],
            [
                'property_address' => '963 Sports Complex Boulevard, Stadium District, NY 10017',
                'owner_name' => 'Kevin Lee',
                'owner_address' => '741 Magnolia Street, West Village, NY 10014',
                'description' => 'Sports-themed residential complex near major stadiums. Includes sports bar, fitness facilities, and game rooms.',
            ],
            [
                'property_address' => '456 Cultural Center Street, Museum Quarter, NY 10018',
                'owner_name' => 'Rachel Clark',
                'owner_address' => '852 Dogwood Lane, NoLita, NY 10012',
                'description' => 'Culturally rich neighborhood surrounded by museums, libraries, and performance venues. Intellectual community atmosphere.',
            ],
            [
                'property_address' => '789 Transportation Hub, Transit Center, NY 10019',
                'owner_name' => 'Brian Walker',
                'owner_address' => '123 Redwood Street, Financial District, NY 10004',
                'description' => 'Perfect for commuters with direct access to subway, bus, and train connections. Modern amenities and professional clientele.',
            ]
        ];

        foreach ($properties as $propertyData) {
            Property::create($propertyData);
        }
    }
}
