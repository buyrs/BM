<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FreshCredentialsSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@bm.com',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        // Create test checkers
        $checkers = [
            [
                'name' => 'John Checker',
                'email' => 'john@bm.com',
                'password' => 'checker123'
            ],
            [
                'name' => 'Sarah Checker',
                'email' => 'sarah@bm.com',
                'password' => 'checker123'
            ],
            [
                'name' => 'Mike Checker',
                'email' => 'mike@bm.com',
                'password' => 'checker123'
            ]
        ];

        foreach ($checkers as $checker) {
            $user = User::create([
                'name' => $checker['name'],
                'email' => $checker['email'],
                'password' => Hash::make($checker['password']),
                'email_verified_at' => now(),
            ]);
            $user->assignRole('checker');
        }
    }
} 