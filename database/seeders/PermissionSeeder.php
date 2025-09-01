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
        $admin = Role::where('name', 'admin')->first();
        $checker = Role::findByName('checker');
        $ops = Role::findByName('ops');

        // Define all permissions with categories
        $permissions = [
            // Basic mission permissions
            'view_missions',
            'create_missions',
            'edit_missions',
            'delete_missions', // Admin only
            'assign_missions',
            
            // Checklist permissions
            'view_checklists',
            'create_checklists',
            'edit_checklists',
            'delete_checklists', // Admin only
            'validate_checklists', // Ops only
            
            // Export and sharing
            'export_pdf',
            'share_whatsapp',
            
            // Bail Mobilité permissions (Ops specific)
            'create_bail_mobilite',
            'edit_bail_mobilite',
            'view_bail_mobilite',
            'delete_bail_mobilite', // Admin only
            'assign_missions_to_checkers',
            'view_ops_dashboard',
            'manage_incidents',
            
            // Contract template permissions (Admin only)
            'view_contract_templates',
            'create_contract_templates', // Admin only
            'edit_contract_templates', // Admin only
            'delete_contract_templates', // Admin only
            'sign_contract_templates', // Admin only
            
            // Signature permissions
            'create_tenant_signatures', // Checker only
            'view_signatures', // Ops and Admin
            'validate_signatures', // Ops and Admin
            'archive_signatures', // Ops and Admin
            
            // Administrative permissions
            'manage_users', // Admin only
            'manage_roles', // Admin only
            'view_system_logs', // Admin only
            'access_admin_panel', // Admin only
            
            // Notification permissions
            'view_notifications',
            'manage_notifications',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Super Admin gets all permissions
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Admin permissions (if admin role exists)
        if ($admin) {
            $admin->givePermissionTo([
                'view_missions',
                'create_missions',
                'edit_missions',
                'delete_missions',
                'assign_missions',
                'view_checklists',
                'create_checklists',
                'edit_checklists',
                'delete_checklists',
                'validate_checklists',
                'export_pdf',
                'share_whatsapp',
                'create_bail_mobilite',
                'edit_bail_mobilite',
                'view_bail_mobilite',
                'delete_bail_mobilite',
                'assign_missions_to_checkers',
                'view_ops_dashboard',
                'manage_incidents',
                'view_contract_templates',
                'create_contract_templates',
                'edit_contract_templates',
                'delete_contract_templates',
                'sign_contract_templates',
                'view_signatures',
                'validate_signatures',
                'archive_signatures',
                'manage_users',
                'manage_roles',
                'view_system_logs',
                'access_admin_panel',
                'view_notifications',
                'manage_notifications',
            ]);
        }

        // Checker permissions (limited to their tasks)
        if ($checker) {
            $checker->givePermissionTo([
                'view_missions',
                'view_checklists',
                'create_checklists',
                'edit_checklists',
                'export_pdf',
                'share_whatsapp',
                'create_tenant_signatures',
                'view_notifications',
            ]);
        }

        // Ops permissions (operational management, no admin functions)
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
                'manage_incidents',
                'view_contract_templates', // Can view but not modify
                'view_signatures',
                'validate_signatures',
                'archive_signatures',
                'view_notifications',
                'manage_notifications',
            ]);
        }
    }
} 