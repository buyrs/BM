<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create all roles
        $roles = [
            'super-admin' => 'Super Administrator with full system access',
            'admin' => 'Administrator with management privileges',
            'ops' => 'Operations manager for Bail Mobilité',
            'checker' => 'Field agent for inspections and checklists'
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web'
            ]);
        }
    }
} 