<?php

namespace Database\Seeders;

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

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Administrator',
            'guard_name' => 'web',
        ]);

        $ictManager = Role::firstOrCreate([
            'name' => 'ICT Manager',
            'guard_name' => 'web',
        ]);

        $ictOfficer = Role::firstOrCreate([
            'name' => 'ICT Officer',
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

        $superAdmin->syncPermissions(Permission::all());
    }
}