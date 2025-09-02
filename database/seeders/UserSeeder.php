<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin user
        $superAdmin = User::firstOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Super Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        // Create test checker
        $checker = User::firstOrCreate([
            'email' => 'checker@example.com',
        ], [
            'name' => 'Test Checker',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $checker->assignRole('checker');
    }
} 