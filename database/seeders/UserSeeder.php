<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User with administrators role
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@bailmobilite.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('administrators');

        // Create Ops User with ops-staff role
        $opsUser = User::create([
            'name' => 'Ops Manager',
            'email' => 'ops@bailmobilite.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $opsUser->assignRole('ops-staff');

        // Create Checker User with controllers role
        $checkerUser = User::create([
            'name' => 'Property Checker',
            'email' => 'checker@bailmobilite.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $checkerUser->assignRole('controllers');

        // Create additional demo users
        $seniorAdminUser = User::create([
            'name' => 'Senior Admin',
            'email' => 'senior.admin@bailmobilite.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $seniorAdminUser->assignRole('administrators');

        $juniorOpsUser = User::create([
            'name' => 'Junior Ops',
            'email' => 'junior.ops@bailmobilite.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $juniorOpsUser->assignRole('ops-staff');

        $leadCheckerUser = User::create([
            'name' => 'Lead Checker',
            'email' => 'lead.checker@bailmobilite.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $leadCheckerUser->assignRole('controllers');

        // Create Super Admin User
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@bailmobilite.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');
    }
}