<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Middlewares\RoleMiddleware;


class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'manage documents']); // Create, update, delete
        Permission::create(['name' => 'view documents']);   // View only
        Permission::create(['name' => 'upload documents']); // Upload new documents

        // Permissions related to user management
        Permission::create(['name' => 'manage users']);     // Add, update, deactivate users
        Permission::create(['name' => 'read only']);
        // Permissions related to tenant management
        Permission::create(['name' => 'view tenants']);     // View tenant information
        Permission::create(['name' => 'manage tenants']);   // Add, update, delete tenant information

        // Permissions related to contract management
        Permission::create(['name' => 'manage contracts']); // Create, renew, update, delete contracts

        // Permissions related to payment management
        Permission::create(['name' => 'manage payments']);  // Add, update, delete payments
        Permission::create(['name' => 'view payments']);    // View payment details

        // Additional permissions for building and floor management
        Permission::create(['name' => 'manage categories']);   // Add, update, delete categories
        Permission::create(['name' => 'manage buildings']);    // Add, update, delete buildings
        Permission::create(['name' => 'manage floors']);       // Add, update, delete floors

        // Define roles and assign appropriate permissions
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());
        
        $admin = Role::create(['name' => 'manager']);
        $admin->givePermissionTo(Permission::all());
    

        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'view tenants',
            'upload documents',
            'view documents',
            'view payments', 
        ]);

        
        // Assign this role to employees who need limited access
        // Add other roles if needed and assign relevant permissions
    
        // Waiter role - manages orders and tables
      
    
    }
}
