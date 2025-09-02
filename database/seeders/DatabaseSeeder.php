<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run role seeder first
        $this->call(RoleSeeder::class);

        // Run fresh credentials seeder
        $this->call(FreshCredentialsSeeder::class);

        // Create super admin user
        $superAdmin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        // Create test agent
        $agent = User::firstOrCreate([
            'email' => 'agent@example.com',
        ], [
            'name' => 'Test Agent',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $agent->assignRole('checker'); // Ensure 'checker' role is assigned

        // Call the MissionSeeder to create dummy missions
        $this->call(MissionSeeder::class);
    }
}
