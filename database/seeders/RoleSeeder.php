<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $checker = Role::firstOrCreate(['name' => 'checker', 'guard_name' => 'web']);

        // Create permissions
        $permissions = [
            'view_missions',
            'create_missions',
            'edit_missions',
            'delete_missions',
            'assign_missions',
            'view_checklists',
            'create_checklists',
            'edit_checklists',
            'delete_checklists',
            'export_pdf',
            'share_whatsapp'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign all permissions to super-admin
        $superAdmin->givePermissionTo($permissions);

        // Assign limited permissions to checker
        $checker->givePermissionTo([
            'view_missions',
            'view_checklists',
            'create_checklists',
            'edit_checklists',
            'export_pdf',
            'share_whatsapp'
        ]);
    }
} 