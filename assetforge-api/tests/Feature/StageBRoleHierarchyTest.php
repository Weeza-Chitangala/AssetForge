<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StageBRoleHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create([
            'name' => 'Alpha Tenant',
            'slug' => 'alpha-tenant',
            'email' => 'alpha@tenant.test',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Beta Tenant',
            'slug' => 'beta-tenant',
            'email' => 'beta@tenant.test',
            'status' => 'active',
        ]);
    }

    protected function createUserWithRole(string $role, ?Tenant $tenant = null, string $prefix = 'u'): User
    {
        $tenant ??= $this->tenantA;
        $user = User::create([
            'name' => "{$role} User",
            'email' => strtolower(str_replace(' ', '.', $role)) . ".{$prefix}." . uniqid() . '@assetforge.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function authHeaders(User $user): array
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');

        return ['Authorization' => 'Bearer ' . $user->createToken('stage-b-test')->plainTextToken];
    }

    public function test_fresh_seeding_creates_canonical_roles_on_api_guard_and_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $roleNames = Role::where('guard_name', RoleHierarchyService::GUARD)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $expected = collect(RoleHierarchyService::ALL_ROLES)->sort()->values()->all();
        $this->assertSame($expected, $roleNames);
        $this->assertSame(0, Role::whereIn('name', ['Super Administrator', 'IT Administrator'])->count());
        $this->assertSame(0, Role::where('guard_name', '!=', RoleHierarchyService::GUARD)->count());
    }

    public function test_legacy_roles_are_renamed_in_place_preserving_ids_and_user_assignments(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'users.view', 'guard_name' => 'api']);
        $legacySuper = Role::create(['name' => 'Super Administrator', 'guard_name' => 'api']);
        $legacyAdmin = Role::create(['name' => 'IT Administrator', 'guard_name' => 'api']);
        $legacySuper->givePermissionTo($perm);
        $legacyAdmin->givePermissionTo($perm);

        $superUser = User::create([
            'name' => 'Legacy Super',
            'email' => 'legacy.super@alpha.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);
        $superUser->assignRole('Super Administrator');

        $tenantAdmin = User::create([
            'name' => 'Legacy Admin',
            'email' => 'legacy.admin@alpha.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);
        $tenantAdmin->assignRole('IT Administrator');

        $this->seed(RolesAndPermissionsSeeder::class);
        // Repeated seeding must not recreate legacy roles or duplicate canonical roles
        $this->seed(RolesAndPermissionsSeeder::class);

        $renamedSuper = Role::where('name', 'Super User')->where('guard_name', 'api')->firstOrFail();
        $renamedAdmin = Role::where('name', 'Administrator')->where('guard_name', 'api')->firstOrFail();

        $this->assertSame($legacySuper->id, $renamedSuper->id);
        $this->assertSame($legacyAdmin->id, $renamedAdmin->id);
        $this->assertSame(0, Role::whereIn('name', ['Super Administrator', 'IT Administrator'])->count());

        $superUser->refresh();
        $tenantAdmin->refresh();
        $this->assertTrue($superUser->hasRole('Super User', 'api'));
        $this->assertTrue($tenantAdmin->hasRole('Administrator', 'api'));
    }

    public function test_administrator_cannot_escalate_privileges_or_manage_administrators_or_super_users(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $superUser = $this->createUserWithRole('Super User');
        $adminA = $this->createUserWithRole('Administrator', $this->tenantA, 'a1');
        $otherAdminA = $this->createUserWithRole('Administrator', $this->tenantA, 'a2');
        $techA = $this->createUserWithRole('Technician', $this->tenantA, 't1');
        $headers = $this->authHeaders($adminA);

        // Cannot create a Super User or Administrator
        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Escalated Super',
            'email' => 'esc.super@alpha.test',
            'password' => 'Password123!',
            'roles' => ['Super User'],
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'esc.super@alpha.test']);

        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Peer Admin',
            'email' => 'peer.admin@alpha.test',
            'password' => 'Password123!',
            'roles' => ['Administrator'],
        ])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'peer.admin@alpha.test']);

        // Cannot promote self to Super User
        $this->withHeaders($headers)->putJson("/api/v1/users/{$adminA->id}", [
            'roles' => ['Super User'],
        ])->assertForbidden();
        $this->assertFalse($adminA->fresh()->hasRole('Super User', 'api'));

        // Cannot promote another operational user to Super User or Administrator
        $this->withHeaders($headers)->putJson("/api/v1/users/{$techA->id}", [
            'roles' => ['Super User'],
        ])->assertForbidden();
        $this->withHeaders($headers)->putJson("/api/v1/users/{$techA->id}", [
            'roles' => ['Administrator'],
        ])->assertForbidden();
        $this->assertTrue($techA->fresh()->hasRole('Technician', 'api'));

        // Cannot edit or delete another Administrator
        $this->withHeaders($headers)->putJson("/api/v1/users/{$otherAdminA->id}", [
            'name' => 'Tampered Admin',
        ])->assertForbidden();
        $this->withHeaders($headers)->deleteJson("/api/v1/users/{$otherAdminA->id}")
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $otherAdminA->id, 'name' => $otherAdminA->name]);

        // Cannot edit, demote, or delete a Super User
        $this->withHeaders($headers)->putJson("/api/v1/users/{$superUser->id}", [
            'name' => 'Tampered Super',
            'roles' => ['Auditor'],
        ])->assertForbidden();
        $this->withHeaders($headers)->deleteJson("/api/v1/users/{$superUser->id}")
            ->assertForbidden();
        $this->assertTrue($superUser->fresh()->hasRole('Super User', 'api'));

        // Cannot self-delete
        $this->withHeaders($headers)->deleteJson("/api/v1/users/{$adminA->id}")
            ->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $adminA->id]);

        // Rejects legacy or nonexistent role names with 422 rather than ignoring them
        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Legacy Role Attack',
            'email' => 'legacy.attack@alpha.test',
            'password' => 'Password123!',
            'roles' => ['Super Administrator'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['roles.0']);

        $this->withHeaders($headers)->putJson("/api/v1/users/{$techA->id}", [
            'roles' => ['UnknownRole'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['roles.0']);
    }

    public function test_administrator_can_manage_operational_users_in_own_tenant_but_not_other_tenants(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $adminA = $this->createUserWithRole('Administrator', $this->tenantA, 'a');
        $techB = $this->createUserWithRole('Technician', $this->tenantB, 'b');
        $headers = $this->authHeaders($adminA);

        // Cannot access or mutate Tenant B users
        $this->withHeaders($headers)->getJson("/api/v1/users/{$techB->id}")->assertNotFound();
        $this->withHeaders($headers)->putJson("/api/v1/users/{$techB->id}", ['name' => 'Hijack'])->assertNotFound();
        $this->withHeaders($headers)->deleteJson("/api/v1/users/{$techB->id}")->assertNotFound();

        // Can create, update, re-role among operational roles, and delete operational user in Tenant A
        $created = $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Ops Technician',
            'email' => 'ops.tech@alpha.test',
            'password' => 'Password123!',
            'roles' => ['Technician'],
        ])->assertCreated();

        $opUserId = $created->json('data.id');
        $this->assertSame(['Technician'], $created->json('data.roles'));

        $updated = $this->withHeaders($headers)->putJson("/api/v1/users/{$opUserId}", [
            'roles' => ['Asset Manager'],
        ])->assertOk();
        $this->assertSame(['Asset Manager'], $updated->json('data.roles'));

        $this->withHeaders($headers)->deleteJson("/api/v1/users/{$opUserId}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $opUserId]);
    }

    public function test_operational_users_cannot_access_user_or_settings_administration_endpoints(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $target = $this->createUserWithRole('Technician', $this->tenantA, 'target');

        foreach (RoleHierarchyService::OPERATIONAL_ROLES as $role) {
            $actor = $this->createUserWithRole($role, $this->tenantA);
            $headers = $this->authHeaders($actor);

            $this->withHeaders($headers)->postJson('/api/v1/users', [
                'name' => 'Blocked',
                'email' => 'blocked.' . uniqid() . '@alpha.test',
                'password' => 'Password123!',
                'roles' => ['Auditor'],
            ])->assertForbidden();

            $this->withHeaders($headers)->putJson("/api/v1/users/{$target->id}", [
                'name' => 'Blocked Update',
            ])->assertForbidden();

            $this->withHeaders($headers)->deleteJson("/api/v1/users/{$target->id}")
                ->assertForbidden();

            $this->withHeaders($headers)->postJson('/api/v1/categories', [
                'name' => 'Blocked Cat ' . uniqid(),
            ])->assertForbidden();
        }
    }

    public function test_super_user_retains_platform_authority_and_last_super_user_is_protected(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $super1 = $this->createUserWithRole('Super User', $this->tenantA, 'su1');
        $headers1 = $this->authHeaders($super1);

        // Last active Super User cannot remove own Super User role, deactivate self, or delete self
        $this->withHeaders($headers1)->putJson("/api/v1/users/{$super1->id}", [
            'roles' => ['Administrator'],
        ])->assertForbidden();

        $this->withHeaders($headers1)->putJson("/api/v1/users/{$super1->id}", [
            'status' => 'suspended',
        ])->assertForbidden();

        $this->withHeaders($headers1)->deleteJson("/api/v1/users/{$super1->id}")
            ->assertForbidden();

        $this->assertTrue($super1->fresh()->hasRole('Super User', 'api'));

        // Benign profile updates on own Super User account are still allowed
        $this->withHeaders($headers1)->putJson("/api/v1/users/{$super1->id}", [
            'name' => 'Primary Platform Super User',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Primary Platform Super User');

        // Super User can create an Administrator, modify it, and create/manage a second Super User
        $adminResp = $this->withHeaders($headers1)->postJson('/api/v1/users', [
            'name' => 'Tenant Alpha Admin',
            'email' => 'tenant.admin@alpha.test',
            'password' => 'Password123!',
            'roles' => ['Administrator'],
        ])->assertCreated();
        $adminId = $adminResp->json('data.id');
        $this->assertSame(['Administrator'], $adminResp->json('data.roles'));

        $this->withHeaders($headers1)->putJson("/api/v1/users/{$adminId}", [
            'roles' => ['Asset Manager'],
        ])->assertOk();
        $this->withHeaders($headers1)->deleteJson("/api/v1/users/{$adminId}")->assertOk();

        // Create second Super User, then verify super1 can manage super2 while super1 still cannot self-demote/delete
        $super2Resp = $this->withHeaders($headers1)->postJson('/api/v1/users', [
            'name' => 'Backup Super User',
            'email' => 'backup.super@alpha.test',
            'password' => 'Password123!',
            'roles' => ['Super User'],
        ])->assertCreated();
        $super2Id = $super2Resp->json('data.id');

        // Self-demotion & self-deletion remain blocked even with a second Super User present
        $this->withHeaders($headers1)->putJson("/api/v1/users/{$super1->id}", [
            'roles' => ['Administrator'],
        ])->assertForbidden();
        $this->withHeaders($headers1)->deleteJson("/api/v1/users/{$super1->id}")
            ->assertForbidden();

        // Removing the second Super User IS permitted because super1 remains active
        $this->withHeaders($headers1)->putJson("/api/v1/users/{$super2Id}", [
            'roles' => ['Administrator'],
        ])->assertOk();
        $this->withHeaders($headers1)->deleteJson("/api/v1/users/{$super2Id}")->assertOk();

        // An inactive/suspended peer Super User is blocked at session level (401) AND
        // does not satisfy the active Super User count in RoleHierarchyService.
        $suspendedSuper = $this->createUserWithRole('Super User', $this->tenantA, 'su_susp');
        $suspendedSuper->update(['status' => 'suspended']);
        $suspendedHeaders = $this->authHeaders($suspendedSuper);

        $this->withHeaders($suspendedHeaders)->putJson("/api/v1/users/{$super1->id}", [
            'roles' => ['Administrator'],
        ])->assertUnauthorized();

        $hierarchy = app(RoleHierarchyService::class);
        $this->assertNotNull($hierarchy->authorizeUserUpdate($suspendedSuper, $super1, ['roles' => ['Administrator']]));
        $this->assertNotNull($hierarchy->authorizeUserUpdate($suspendedSuper, $super1, ['status' => 'inactive']));
        $this->assertNotNull($hierarchy->authorizeUserDeletion($suspendedSuper, $super1));

        $this->assertDatabaseHas('users', ['id' => $super1->id, 'status' => 'active']);
        $this->assertTrue($super1->fresh()->hasRole('Super User', 'api'));
    }
}
