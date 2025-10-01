<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@bailmobilite.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Ops User
        User::create([
            'name' => 'Ops Manager',
            'email' => 'ops@bailmobilite.com',
            'password' => Hash::make('password'),
            'role' => 'ops',
            'email_verified_at' => now(),
        ]);

        // Create Checker User
        User::create([
            'name' => 'Property Checker',
            'email' => 'checker@bailmobilite.com',
            'password' => Hash::make('password'),
            'role' => 'checker',
            'email_verified_at' => now(),
        ]);

        // Create additional demo users
        User::create([
            'name' => 'Senior Admin',
            'email' => 'senior.admin@bailmobilite.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Junior Ops',
            'email' => 'junior.ops@bailmobilite.com',
            'password' => Hash::make('password'),
            'role' => 'ops',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Lead Checker',
            'email' => 'lead.checker@bailmobilite.com',
            'password' => Hash::make('password'),
            'role' => 'checker',
            'email_verified_at' => now(),
        ]);
    }
}