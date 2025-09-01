<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Get existing roles
        $superAdmin = Role::findByName('super-admin');
        $checker = Role::findByName('checker');
        $ops = Role::findByName('ops');

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
            Permission::firstOrCreate(['name' => $permission]);
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
        if ($ops) {
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
} 