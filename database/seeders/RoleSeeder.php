<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create initial permissions
        $permissions = [
            'manage games',
            'manage users',
            'manage privileges',
            'manage llm',
            'play games',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign created permissions

        // Super Admin gets everything
        $superAdmin = Role::findOrCreate('Super Admin');

        $admin = Role::findOrCreate('Administrator');
        $admin->syncPermissions(['manage games', 'manage users', 'manage llm', 'play games']);

        $userRole = Role::findOrCreate('User');
        $userRole->syncPermissions(['play games']);

        // Assign Super Admin to first user if exists
        $user = User::first();
        if ($user) {
            $user->assignRole($superAdmin);
        }
    }
}
