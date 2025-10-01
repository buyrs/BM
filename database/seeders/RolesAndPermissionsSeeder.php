<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $opsStaffRole = Role::firstOrCreate(['name' => 'ops-staff', 'guard_name' => 'web']);
        $controllersRole = Role::firstOrCreate(['name' => 'controllers', 'guard_name' => 'web']);
        $administratorsRole = Role::firstOrCreate(['name' => 'administrators', 'guard_name' => 'web']);

        // Define permissions for different parts of the system
        $permissions = [
            // User management permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Mission management permissions
            'view missions',
            'create missions',
            'edit missions',
            'delete missions',
            'assign missions',
            'approve missions',
            
            // Property management permissions
            'view properties',
            'create properties',
            'edit properties',
            'delete properties',
            
            // Checklist management permissions
            'view checklists',
            'create checklists',
            'edit checklists',
            'delete checklists',
            'submit checklists',
            'send checklists to guest',
            
            // Amenity management permissions
            'view amenities',
            'create amenities',
            'edit amenities',
            'delete amenities',
            
            // Amenity type management permissions
            'view amenity types',
            'create amenity types',
            'edit amenity types',
            'delete amenity types',
            
            // Audit log permissions
            'view audit logs',
            'export audit logs',
            'cleanup audit logs',
            
            // Notification permissions
            'view notifications',
            'mark notifications as read',
            
            // Performance monitoring permissions
            'view performance',
            'view metrics',
            'export performance reports',
            
            // Backup management permissions
            'view backups',
            'create backups',
            'delete backups',
            'download backups',
            'verify backups',
            
            // Report permissions
            'view reports',
            'generate reports',
            'download reports',
            
            // Analytics permissions
            'view analytics',
            'export analytics',
            
            // Maintenance request permissions
            'view maintenance requests',
            'approve maintenance requests',
            'reject maintenance requests',
            'start maintenance work',
            'complete maintenance work',
            'update maintenance assignment',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        $superAdminRole->givePermissionTo(Permission::all());
        
        $opsStaffRole->givePermissionTo([
            'view missions',
            'create missions',
            'edit missions',
            'view properties',
            'create properties',
            'edit properties',
            'view checklists',
            'create checklists',
            'edit checklists',
            'view amenities',
            'create amenities',
            'edit amenities',
            'view amenity types',
            'create amenity types',
            'edit amenity types',
            'view maintenance requests',
            'approve maintenance requests',
            'reject maintenance requests',
            'start maintenance work',
            'complete maintenance work',
            'update maintenance assignment',
        ]);
        
        $controllersRole->givePermissionTo([
            'view missions',
            'view checklists',
            'create checklists',
            'edit checklists',
            'submit checklists',
        ]);
        
        $administratorsRole->givePermissionTo([
            'view users',
            'view missions',
            'create missions',
            'edit missions',
            'delete missions',
            'assign missions',
            'approve missions',
            'view properties',
            'create properties',
            'edit properties',
            'delete properties',
            'view checklists',
            'create checklists',
            'edit checklists',
            'delete checklists',
            'send checklists to guest',
            'view amenities',
            'create amenities',
            'edit amenities',
            'delete amenities',
            'view amenity types',
            'create amenity types',
            'edit amenity types',
            'delete amenity types',
            'view audit logs',
            'export audit logs',
            'view notifications',
            'mark notifications as read',
            'view performance',
            'view metrics',
            'view reports',
            'generate reports',
            'download reports',
            'view analytics',
            'export analytics',
        ]);
    }
}