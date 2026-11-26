<?php

namespace Tests\Feature;

use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\EquipmentAsset;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Organization;
use App\Models\RepairJob;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warranty;
use App\Services\RoleHierarchyService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StageESearchAndAuditTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;

    private Tenant $tenantB;

    private Organization $orgA;

    private Organization $orgB;

    private Company $companyA;

    private Company $companyB;

    private Department $departmentA;

    private Location $locationA;

    private Category $categoryA;

    private Manufacturer $manufacturerA;

    private AssetModel $assetModelA;

    private Supplier $supplierA;

    private User $superUserA;

    private User $adminA;

    private User $assetManagerA;

    private User $technicianA;

    private User $auditorA;

    private User $adminB;

    private User $auditorB;

    private EquipmentAsset $assetA;

    private EquipmentAsset $assetB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

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

        $this->orgA = Organization::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Alpha Org',
            'code' => 'AO',
            'status' => 'active',
        ]);
        $this->orgB = Organization::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Beta Org',
            'code' => 'BO',
            'status' => 'active',
        ]);

        $this->companyA = Company::create([
            'organization_id' => $this->orgA->id,
            'name' => 'Alpha Co',
            'code' => 'AC',
            'status' => 'active',
        ]);
        $this->companyB = Company::create([
            'organization_id' => $this->orgB->id,
            'name' => 'Beta Co',
            'code' => 'BC',
            'status' => 'active',
        ]);

        $this->departmentA = Department::create([
            'company_id' => $this->companyA->id,
            'name' => 'Alpha IT',
            'code' => 'AIT',
            'status' => 'active',
        ]);

        $this->locationA = Location::create([
            'tenant_id' => $this->tenantA->id,
            'company_id' => $this->companyA->id,
            'name' => 'Alpha HQ',
            'code' => 'AHQ',
            'status' => 'active',
        ]);

        $this->categoryA = Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'SharedKeyword Laptops',
            'code' => 'SKL',
            'status' => 'active',
        ]);

        $this->manufacturerA = Manufacturer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'ThinkBrand',
            'code' => 'TB',
            'status' => 'active',
        ]);

        $this->assetModelA = AssetModel::create([
            'tenant_id' => $this->tenantA->id,
            'manufacturer_id' => $this->manufacturerA->id,
            'category_id' => $this->categoryA->id,
            'name' => 'ProBook X',
            'model_number' => 'PBX-100',
            'status' => 'active',
        ]);

        $this->supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Alpha Supply',
            'code' => 'ASUP',
            'status' => 'active',
        ]);

        $this->superUserA = $this->createUser($this->tenantA, 'super.a@alpha.test', RoleHierarchyService::ROLE_SUPER_USER);
        $this->adminA = $this->createUser($this->tenantA, 'admin.a@alpha.test', RoleHierarchyService::ROLE_ADMINISTRATOR);
        $this->assetManagerA = $this->createUser($this->tenantA, 'manager.a@alpha.test', RoleHierarchyService::ROLE_ASSET_MANAGER);
        $this->technicianA = $this->createUser($this->tenantA, 'tech.a@alpha.test', RoleHierarchyService::ROLE_TECHNICIAN);
        $this->auditorA = $this->createUser($this->tenantA, 'auditor.a@alpha.test', RoleHierarchyService::ROLE_AUDITOR);

        $this->adminB = $this->createUser($this->tenantB, 'admin.b@beta.test', RoleHierarchyService::ROLE_ADMINISTRATOR);
        $this->auditorB = $this->createUser($this->tenantB, 'auditor.b@beta.test', RoleHierarchyService::ROLE_AUDITOR);

        $assigneeA = User::create([
            'name' => 'SharedKeyword Staffer',
            'email' => 'secret.assignee@alpha.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);

        $this->assetA = EquipmentAsset::create([
            'tenant_id' => $this->tenantA->id,
            'asset_tag' => 'AF-ALPHA-001',
            'serial_number' => 'SN-ALPHA-001',
            'name' => 'SharedKeyword Workstation',
            'category_id' => $this->categoryA->id,
            'manufacturer_id' => $this->manufacturerA->id,
            'asset_model_id' => $this->assetModelA->id,
            'location_id' => $this->locationA->id,
            'assigned_to_user_id' => $assigneeA->id,
        ]);

        RepairJob::create([
            'tenant_id' => $this->tenantA->id,
            'equipment_asset_id' => $this->assetA->id,
            'job_number' => 'REP-ALPHA-001',
            'fault_description' => 'SharedKeyword screen flicker',
            'repair_center' => 'internal',
            'repair_status' => 'pending',
            'start_date' => now()->toDateString(),
        ]);

        Warranty::create([
            'tenant_id' => $this->tenantA->id,
            'equipment_asset_id' => $this->assetA->id,
            'provider_name' => 'SharedKeyword Care',
            'policy_number' => 'POL-ALPHA-001',
            'warranty_type' => 'onsite',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->addDays(180)->toDateString(),
        ]);

        $this->assetB = EquipmentAsset::create([
            'tenant_id' => $this->tenantB->id,
            'asset_tag' => 'AF-BETA-999',
            'serial_number' => 'SN-BETA-999',
            'name' => 'Beta Confidential Server',
        ]);
    }

    private function createUser(Tenant $tenant, string $email, string $role): User
    {
        $user = User::create([
            'name' => $role . ' ' . $tenant->slug,
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(User $user): array
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');

        return [
            'Authorization' => 'Bearer ' . $user->createToken('stage-e-test')->plainTextToken,
        ];
    }

    public function test_debug_test_permission_route_is_disabled(): void
    {
        $this->withHeaders($this->authHeaders($this->superUserA))
            ->getJson('/api/v1/test-permission')
            ->assertNotFound();

        $this->withHeaders($this->authHeaders($this->adminA))
            ->getJson('/api/v1/test-permission')
            ->assertNotFound();
    }

    public function test_audit_view_permission_separates_audit_access_from_reports_view(): void
    {
        foreach ([$this->superUserA, $this->adminA, $this->auditorA] as $authorizedActor) {
            $headers = $this->authHeaders($authorizedActor);

            $this->withHeaders($headers)
                ->getJson('/api/v1/audit-logs')
                ->assertOk();

            $this->withHeaders($headers)
                ->getJson("/api/v1/audit-logs/EquipmentAsset/{$this->assetA->id}")
                ->assertOk();
        }

        // Asset Manager holds reports.view but must NOT hold audit.view; Technician holds neither.
        foreach ([$this->assetManagerA, $this->technicianA] as $unauthorizedActor) {
            $headers = $this->authHeaders($unauthorizedActor);

            $this->withHeaders($headers)
                ->getJson('/api/v1/audit-logs')
                ->assertForbidden();

            $this->withHeaders($headers)
                ->getJson("/api/v1/audit-logs/EquipmentAsset/{$this->assetA->id}")
                ->assertForbidden();
        }
    }

    public function test_global_search_enforces_domain_permissions_tenant_context_and_assigned_email_guard(): void
    {
        // Technician (assets.view + repairs.view, NO users.view) cannot enumerate assignee email via asset search.
        $techEmailSearch = $this->withHeaders($this->authHeaders($this->technicianA))
            ->getJson('/api/v1/search?q=secret.assignee@alpha.test')
            ->assertOk();
        $this->assertCount(0, $techEmailSearch->json('data.assets'));
        $this->assertCount(0, $techEmailSearch->json('data.users'));

        // Asset Manager (assets.view + users.view) CAN search by assignee email.
        $mgrEmailSearch = $this->withHeaders($this->authHeaders($this->assetManagerA))
            ->getJson('/api/v1/search?q=secret.assignee@alpha.test')
            ->assertOk();
        $this->assertCount(1, $mgrEmailSearch->json('data.assets'));
        $this->assertCount(1, $mgrEmailSearch->json('data.users'));

        // Asset-only user receives assets and warranties only.
        $assetsOnlyRole = Role::create(['name' => 'Assets Only', 'guard_name' => 'api']);
        $assetsOnlyRole->syncPermissions(['assets.view']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $assetsOnlyUser = $this->createUser($this->tenantA, 'assets.only@alpha.test', 'Assets Only');
        $assetsOnlyResp = $this->withHeaders($this->authHeaders($assetsOnlyUser))
            ->getJson('/api/v1/search?q=SharedKeyword')
            ->assertOk();
        $this->assertCount(1, $assetsOnlyResp->json('data.assets'));
        $this->assertCount(1, $assetsOnlyResp->json('data.warranties'));
        $this->assertCount(0, $assetsOnlyResp->json('data.repairs'));
        $this->assertCount(0, $assetsOnlyResp->json('data.users'));

        // Repairs-only user receives repairs only.
        $repairsOnlyRole = Role::create(['name' => 'Repairs Only', 'guard_name' => 'api']);
        $repairsOnlyRole->syncPermissions(['repairs.view']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $repairsOnlyUser = $this->createUser($this->tenantA, 'repairs.only@alpha.test', 'Repairs Only');
        $repairsOnlyResp = $this->withHeaders($this->authHeaders($repairsOnlyUser))
            ->getJson('/api/v1/search?q=SharedKeyword')
            ->assertOk();
        $this->assertCount(0, $repairsOnlyResp->json('data.assets'));
        $this->assertCount(0, $repairsOnlyResp->json('data.warranties'));
        $this->assertCount(1, $repairsOnlyResp->json('data.repairs'));
        $this->assertCount(0, $repairsOnlyResp->json('data.users'));

        // Users-only user receives users only.
        $usersOnlyRole = Role::create(['name' => 'Users Only', 'guard_name' => 'api']);
        $usersOnlyRole->syncPermissions(['users.view']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $usersOnlyUser = $this->createUser($this->tenantA, 'users.only@alpha.test', 'Users Only');
        $usersOnlyResp = $this->withHeaders($this->authHeaders($usersOnlyUser))
            ->getJson('/api/v1/search?q=SharedKeyword')
            ->assertOk();
        $this->assertCount(0, $usersOnlyResp->json('data.assets'));
        $this->assertCount(0, $usersOnlyResp->json('data.warranties'));
        $this->assertCount(0, $usersOnlyResp->json('data.repairs'));
        $this->assertCount(1, $usersOnlyResp->json('data.users'));

        // Cross-tenant search returns zero records even for Super User.
        $crossTenantResp = $this->withHeaders($this->authHeaders($this->superUserA))
            ->getJson('/api/v1/search?q=AF-BETA-999')
            ->assertOk();
        $this->assertSame(0, $crossTenantResp->json('data.total'));

        // Tenantless non-Super-User is blocked by EnsureActiveSession (401), while a tenantless Super User hits GlobalSearchController and fails safely with 403.
        $orphanUser = User::create([
            'name' => 'Orphan User',
            'email' => 'orphan@platform.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);
        $orphanUser->assignRole(RoleHierarchyService::ROLE_ADMINISTRATOR);
        $orphanUser->tenant_id = null;

        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');
        $this->actingAs($orphanUser, 'sanctum')
            ->getJson('/api/v1/search?q=SharedKeyword')
            ->assertUnauthorized();

        $orphanSuper = User::create([
            'name' => 'Orphan Super',
            'email' => 'orphan.super@platform.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);
        $orphanSuper->assignRole(RoleHierarchyService::ROLE_SUPER_USER);
        $orphanSuper->tenant_id = null;

        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');
        $this->actingAs($orphanSuper, 'sanctum')
            ->getJson('/api/v1/search?q=SharedKeyword')
            ->assertForbidden();
    }

    public function test_activity_logging_covers_models_excludes_secrets_and_records_role_sync(): void
    {
        $expectedModelTypes = [
            EquipmentAsset::class,
            RepairJob::class,
            Warranty::class,
            Supplier::class,
            Category::class,
            Manufacturer::class,
            AssetModel::class,
            Location::class,
            Department::class,
            Company::class,
            Organization::class,
            User::class,
        ];

        foreach ($expectedModelTypes as $modelClass) {
            $activity = Activity::where('subject_type', $modelClass)->first();
            $this->assertNotNull($activity, "Expected activity log entry for {$modelClass}");
            if ($modelClass !== User::class || $activity->subject_id == $this->adminA->id) {
                $this->assertNotNull($activity->properties->get('tenant_id'));
            }
        }

        // Verify Department and Company carry Tenant A's tenant_id via hierarchy lookup.
        $companyLog = Activity::where('subject_type', Company::class)
            ->where('subject_id', (string) $this->companyA->id)
            ->firstOrFail();
        $this->assertSame((string) $this->tenantA->id, $companyLog->properties->get('tenant_id'));

        $departmentLog = Activity::where('subject_type', Department::class)
            ->where('subject_id', (string) $this->departmentA->id)
            ->firstOrFail();
        $this->assertSame((string) $this->tenantA->id, $departmentLog->properties->get('tenant_id'));

        // Verify User activity logs never include password or remember_token.
        $createResp = $this->withHeaders($this->authHeaders($this->adminA))
            ->postJson('/api/v1/users', [
                'name' => 'Logged Staffer',
                'email' => 'logged.staffer@alpha.test',
                'password' => 'StrongPass123!',
                'roles' => [RoleHierarchyService::ROLE_TECHNICIAN],
            ])
            ->assertCreated();
        $createdUserId = $createResp->json('data.id');

        $this->withHeaders($this->authHeaders($this->adminA))
            ->putJson("/api/v1/users/{$createdUserId}", [
                'name' => 'Logged Staffer Updated',
                'password' => 'UpdatedPass123!',
                'roles' => [RoleHierarchyService::ROLE_AUDITOR],
            ])
            ->assertOk();

        $userLogs = Activity::where('subject_type', User::class)
            ->where('subject_id', (string) $createdUserId)
            ->where('log_name', 'user')
            ->get();
        $this->assertGreaterThanOrEqual(2, $userLogs->count());

        foreach ($userLogs as $log) {
            $attrs = $log->properties->get('attributes', []);
            $old = $log->properties->get('old', []);
            $this->assertArrayNotHasKey('password', $attrs);
            $this->assertArrayNotHasKey('remember_token', $attrs);
            $this->assertArrayNotHasKey('password', $old);
            $this->assertArrayNotHasKey('remember_token', $old);
            $this->assertSame((string) $this->tenantA->id, $log->properties->get('tenant_id'));
        }

        // Verify role synchronization events are recorded cleanly.
        $roleSyncLogs = Activity::where('subject_type', User::class)
            ->where('subject_id', (string) $createdUserId)
            ->where('log_name', 'user_roles')
            ->where('event', 'roles_synced')
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $roleSyncLogs);
        $this->assertSame([], $roleSyncLogs[0]->properties->get('previous_roles'));
        $this->assertSame([RoleHierarchyService::ROLE_TECHNICIAN], $roleSyncLogs[0]->properties->get('new_roles'));
        $this->assertSame([RoleHierarchyService::ROLE_TECHNICIAN], $roleSyncLogs[1]->properties->get('previous_roles'));
        $this->assertSame([RoleHierarchyService::ROLE_AUDITOR], $roleSyncLogs[1]->properties->get('new_roles'));
    }

    public function test_audit_log_allowlist_tenant_isolation_and_cross_tenant_super_user_visibility(): void
    {
        // Disallowed or unknown entity types are rejected.
        $this->withHeaders($this->authHeaders($this->auditorA))
            ->getJson("/api/v1/audit-logs/Tenant/{$this->tenantA->id}")
            ->assertNotFound();

        $this->withHeaders($this->authHeaders($this->auditorA))
            ->getJson('/api/v1/audit-logs/SecretEntity/123')
            ->assertNotFound();

        // Cross-tenant entityAudit returns 404 even with valid asset UUID from Tenant B.
        $this->withHeaders($this->authHeaders($this->auditorA))
            ->getJson("/api/v1/audit-logs/EquipmentAsset/{$this->assetB->id}")
            ->assertNotFound();

        // Cross-tenant Super User update is stamped with platform_cross_tenant metadata.
        $this->actingAs($this->superUserA);
        $this->assetB->update(['name' => 'Platform Support Adjustment']);
        $this->app['auth']->forgetGuards();

        $crossLog = Activity::where('subject_type', EquipmentAsset::class)
            ->where('subject_id', (string) $this->assetB->id)
            ->where('event', 'updated')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame((string) $this->tenantB->id, $crossLog->properties->get('tenant_id'));
        $this->assertSame((string) $this->tenantA->id, $crossLog->properties->get('causer_tenant_id'));
        $this->assertTrue((bool) $crossLog->properties->get('causer_is_super_user'));
        $this->assertSame('platform_cross_tenant', $crossLog->properties->get('scope'));

        // Tenant A non-Super-User cannot see Tenant B asset updates caused by Tenant A Super User.
        $tenantAAudit = $this->withHeaders($this->authHeaders($this->adminA))
            ->getJson('/api/v1/audit-logs?per_page=100')
            ->assertOk();
        $this->assertNotContains($crossLog->id, collect($tenantAAudit->json('data'))->pluck('id')->all());

        // Tenant B non-Super-User (Administrator / Auditor) cannot see platform_cross_tenant entries.
        $tenantBAudit = $this->withHeaders($this->authHeaders($this->auditorB))
            ->getJson('/api/v1/audit-logs?per_page=100')
            ->assertOk();
        $this->assertNotContains($crossLog->id, collect($tenantBAudit->json('data'))->pluck('id')->all());

        $tenantBEntityAudit = $this->withHeaders($this->authHeaders($this->adminB))
            ->getJson("/api/v1/audit-logs/EquipmentAsset/{$this->assetB->id}")
            ->assertOk();
        $this->assertNotContains($crossLog->id, collect($tenantBEntityAudit->json('data'))->pluck('id')->all());

        // Tenant A Super User retains full traceability of their own cross-tenant action.
        $superUserAudit = $this->withHeaders($this->authHeaders($this->superUserA))
            ->getJson('/api/v1/audit-logs?per_page=100')
            ->assertOk();
        $this->assertContains($crossLog->id, collect($superUserAudit->json('data'))->pluck('id')->all());

        // Cross-tenant Super User token revocation via TenantObserver attributes target tenant and hides from tenant actors.
        $this->adminB->createToken('tenant-b-active-session');
        $this->actingAs($this->superUserA);
        $this->tenantB->update(['status' => 'suspended']);
        $this->app['auth']->forgetGuards();

        $revokedCrossLog = Activity::where('log_name', 'auth')
            ->where('event', 'tokens_revoked')
            ->where('subject_type', User::class)
            ->where('subject_id', (string) $this->adminB->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame((string) $this->tenantB->id, $revokedCrossLog->properties->get('tenant_id'));
        $this->assertSame((string) $this->tenantA->id, $revokedCrossLog->properties->get('causer_tenant_id'));
        $this->assertTrue((bool) $revokedCrossLog->properties->get('causer_is_super_user'));
        $this->assertSame('platform_cross_tenant', $revokedCrossLog->properties->get('scope'));

        // Reactivate Tenant B so its Auditor can query audit logs; cross-tenant revocation remains hidden from Tenant B.
        $this->tenantB->update(['status' => 'active']);
        $tenantBAfterReactivation = $this->withHeaders($this->authHeaders($this->auditorB))
            ->getJson('/api/v1/audit-logs?per_page=100')
            ->assertOk();
        $this->assertNotContains($revokedCrossLog->id, collect($tenantBAfterReactivation->json('data'))->pluck('id')->all());

        // Tenant A Super User retains full visibility into cross-tenant revocation telemetry.
        $superUserRevokedAudit = $this->withHeaders($this->authHeaders($this->superUserA))
            ->getJson('/api/v1/audit-logs?per_page=100')
            ->assertOk();
        $this->assertContains($revokedCrossLog->id, collect($superUserRevokedAudit->json('data'))->pluck('id')->all());
    }
}
