<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'api';

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'assets.view',
            'assets.create',
            'assets.update',
            'assets.delete',
            'repairs.view',
            'repairs.create',
            'repairs.update',
            'repairs.delete',
            'reports.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $rolePermissions = [
            'Super Administrator' => $permissions,
            'IT Administrator' => $permissions,
            'Asset Manager' => [
                'dashboard.view',
                'users.view',
                'assets.view',
                'assets.create',
                'assets.update',
                'assets.delete',
                'repairs.view',
                'repairs.create',
                'repairs.update',
                'reports.view',
            ],
            'Technician' => [
                'dashboard.view',
                'assets.view',
                'assets.update',
                'repairs.view',
                'repairs.create',
                'repairs.update',
            ],
            'Auditor' => [
                'dashboard.view',
                'users.view',
                'assets.view',
                'repairs.view',
                'reports.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $assignedPermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);
            $role->syncPermissions($assignedPermissions);
        }

        $admin = User::where('email', 'admin@assetforge.com')->first();
        if ($admin && !$admin->hasRole('Super Administrator')) {
            $admin->assignRole('Super Administrator');
        }
    }
}
