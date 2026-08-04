<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

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
                'guard_name' => 'web',
            ]);
        }

        $systemAdministrator = Role::firstOrCreate([
            'name' => 'System Administrator',
            'guard_name' => 'web',
        ]);

        $itAdministrator = Role::firstOrCreate([
            'name' => 'IT Administrator',
            'guard_name' => 'web',
        ]);

        $assetManager = Role::firstOrCreate([
            'name' => 'Asset Manager',
            'guard_name' => 'web',
        ]);

        $technician = Role::firstOrCreate([
            'name' => 'Technician',
            'guard_name' => 'web',
        ]);

        $auditor = Role::firstOrCreate([
            'name' => 'Auditor',
            'guard_name' => 'web',
        ]);

        $systemAdministrator->syncPermissions(Permission::all());

        $admin = User::where('email', 'admin@assetforge.com')->first();

        if ($admin) {
            $admin->assignRole($systemAdministrator);
        }
    }
}