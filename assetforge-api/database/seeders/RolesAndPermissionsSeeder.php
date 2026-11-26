<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = RoleHierarchyService::GUARD;

        $this->renameLegacyRolesInPlace($guard);

        $permissions = [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'assets.view',
            'assets.create',
            'assets.update',
            'assets.transfer',
            'assets.delete',
            'repairs.view',
            'repairs.create',
            'repairs.update',
            'repairs.delete',
            'reports.view',
            'audit.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]);
        }

        $rolePermissions = [
            RoleHierarchyService::ROLE_SUPER_USER => $permissions,
            RoleHierarchyService::ROLE_ADMINISTRATOR => $permissions,
            RoleHierarchyService::ROLE_ASSET_MANAGER => [
                'dashboard.view',
                'users.view',
                'assets.view',
                'assets.create',
                'assets.update',
                'assets.transfer',
                'assets.delete',
                'repairs.view',
                'repairs.create',
                'repairs.update',
                'reports.view',
            ],
            RoleHierarchyService::ROLE_TECHNICIAN => [
                'dashboard.view',
                'assets.view',
                'assets.update',
                'repairs.view',
                'repairs.create',
                'repairs.update',
            ],
            RoleHierarchyService::ROLE_AUDITOR => [
                'dashboard.view',
                'users.view',
                'assets.view',
                'repairs.view',
                'reports.view',
                'audit.view',
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
        if ($admin && ! $admin->hasRole(RoleHierarchyService::ROLE_SUPER_USER, $guard)) {
            $admin->assignRole(RoleHierarchyService::ROLE_SUPER_USER);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Rename legacy Phase 0 roles to canonical Phase 1B names in place so role IDs
     * and existing user-role assignments remain intact across existing installations.
     */
    protected function renameLegacyRolesInPlace(string $guard): void
    {
        foreach (RoleHierarchyService::LEGACY_ROLE_MAP as $legacyName => $canonicalName) {
            $legacyRole = Role::where('name', $legacyName)->where('guard_name', $guard)->first();

            if (! $legacyRole) {
                continue;
            }

            $canonicalRole = Role::where('name', $canonicalName)->where('guard_name', $guard)->first();

            if (! $canonicalRole) {
                $legacyRole->name = $canonicalName;
                $legacyRole->save();
                continue;
            }

            // Reassign any remaining users if both role rows existed, then drop the duplicate legacy role.
            User::role($legacyName, $guard)->get()->each(function (User $user) use ($legacyName, $canonicalName): void {
                $user->assignRole($canonicalName);
                $user->removeRole($legacyName);
            });

            $legacyRole->delete();
        }
    }
}
