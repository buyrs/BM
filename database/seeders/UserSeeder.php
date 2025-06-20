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
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        // Create test checker
        $checker = User::create([
            'name' => 'Test Checker',
            'email' => 'checker@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $checker->assignRole('checker');
    }
} 