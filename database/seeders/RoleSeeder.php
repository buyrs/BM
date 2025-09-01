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
        $ops = Role::firstOrCreate(['name' => 'ops', 'guard_name' => 'web']);

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
            'share_whatsapp',
            // New permissions for Ops role
            'create_bail_mobilite',
            'edit_bail_mobilite',
            'view_bail_mobilite',
            'delete_bail_mobilite',
            'assign_missions_to_checkers',
            'validate_checklists',
            'view_ops_dashboard',
            'manage_incidents',
            'view_contract_templates',
            'create_contract_templates',
            'edit_contract_templates',
            'delete_contract_templates',
            'sign_contract_templates'
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

        // Assign Ops-specific permissions
        $ops->givePermissionTo([
            'view_missions',
            'create_missions',
            'edit_missions',
            'assign_missions',
            'view_checklists',
            'validate_checklists',
            'export_pdf',
            'share_whatsapp',
            'create_bail_mobilite',
            'edit_bail_mobilite',
            'view_bail_mobilite',
            'assign_missions_to_checkers',
            'view_ops_dashboard',
            'manage_incidents'
        ]);
    }
} 