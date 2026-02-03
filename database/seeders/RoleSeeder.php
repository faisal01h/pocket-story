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
        $permissions = collect([
            'game.view', // play games
            'game.edit',
            'game.create',
            'game.delete', // soft delete
            'game.llm', // privilege to toggle enable LLM mode
            'game.llm-mode', // play game with LLM mode
            'user.view', // view all user data
            'user.edit',
            'user.delete', // soft delete
            'user.analyze', // user ai summary analysis
            'privilege.manage',
            'llm.provider.manage',
            'llm.limit.manage',
            'llm.request.view',
        ]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create roles and assign created permissions

        // Super Admin gets everything
        $superAdmin = Role::findOrCreate('Super Admin');

        $admin = Role::findOrCreate('Administrator');
        $admin->syncPermissions($permissions);

        $userRole = Role::findOrCreate('User');
        $userRole->syncPermissions($permissions->only([
            'game.view',
            'game.edit',
            'game.create',
            'game.delete',
        ]));
        $plusUserRole = Role::findOrCreate('PlusUser');
        $plusUserRole->syncPermissions($permissions->only([
            'game.llm',
            'game.llm-mode',
        ]));

        // Assign Super Admin to first user if exists
        $user = User::first();
        if ($user) {
            $user->assignRole($superAdmin);
        }
    }
}
