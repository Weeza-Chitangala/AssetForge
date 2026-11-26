<?php

namespace Tests\Feature;

use App\Models\AssetMovement;
use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\EquipmentAsset;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StageCCustodyPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;

    protected Organization $orgA;
    protected Company $compA1;
    protected Company $compA2;
    protected Company $compB;

    protected Department $deptA1;
    protected Department $deptA2;
    protected Department $deptB;

    protected Location $locA1;
    protected Location $locA2;
    protected Location $locB;

    protected AssetStatus $availableStatusA;
    protected AssetStatus $assignedStatusA;

    protected User $superUserA;
    protected User $adminA;
    protected User $assetManagerA;
    protected User $technicianA;
    protected User $auditorA;
    protected User $custodianA;
    protected User $custodianB;

    protected EquipmentAsset $assetA;
    protected EquipmentAsset $assetB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tenantA = Tenant::create(['name' => 'Alpha Custody', 'slug' => 'alpha-custody', 'email' => 'alpha@custody.test', 'status' => 'active']);
        $this->tenantB = Tenant::create(['name' => 'Beta Custody', 'slug' => 'beta-custody', 'email' => 'beta@custody.test', 'status' => 'active']);

        $this->orgA = Organization::create(['tenant_id' => $this->tenantA->id, 'name' => 'Alpha Org', 'code' => 'AO', 'status' => 'active']);
        $orgB = Organization::create(['tenant_id' => $this->tenantB->id, 'name' => 'Beta Org', 'code' => 'BO', 'status' => 'active']);

        $this->compA1 = Company::create(['organization_id' => $this->orgA->id, 'name' => 'Alpha Co 1', 'code' => 'AC1', 'status' => 'active']);
        $this->compA2 = Company::create(['organization_id' => $this->orgA->id, 'name' => 'Alpha Co 2', 'code' => 'AC2', 'status' => 'active']);
        $this->compB = Company::create(['organization_id' => $orgB->id, 'name' => 'Beta Co', 'code' => 'BC', 'status' => 'active']);

        $this->deptA1 = Department::create(['company_id' => $this->compA1->id, 'name' => 'Alpha IT', 'code' => 'AIT', 'status' => 'active']);
        $this->deptA2 = Department::create(['company_id' => $this->compA2->id, 'name' => 'Alpha Finance', 'code' => 'AFIN', 'status' => 'active']);
        $this->deptB = Department::create(['company_id' => $this->compB->id, 'name' => 'Beta Ops', 'code' => 'BOPS', 'status' => 'active']);

        $this->locA1 = Location::create(['tenant_id' => $this->tenantA->id, 'company_id' => $this->compA1->id, 'name' => 'Alpha HQ', 'code' => 'AHQ', 'status' => 'active']);
        $this->locA2 = Location::create(['tenant_id' => $this->tenantA->id, 'company_id' => $this->compA2->id, 'name' => 'Alpha Branch', 'code' => 'ABR', 'status' => 'active']);
        $this->locB = Location::create(['tenant_id' => $this->tenantB->id, 'company_id' => $this->compB->id, 'name' => 'Beta Vault', 'code' => 'BVT', 'status' => 'active']);

        $catA = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Laptops', 'code' => 'LAP', 'status' => 'active']);
        $catB = Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'Laptops B', 'code' => 'LAPB', 'status' => 'active']);

        $this->availableStatusA = AssetStatus::create(['tenant_id' => $this->tenantA->id, 'name' => 'Available', 'code' => 'AVAILABLE', 'color' => '#16A34A']);
        $this->assignedStatusA = AssetStatus::create(['tenant_id' => $this->tenantA->id, 'name' => 'Assigned', 'code' => 'ASSIGNED', 'color' => '#2563EB']);
        $statusB = AssetStatus::create(['tenant_id' => $this->tenantB->id, 'name' => 'Available B', 'code' => 'AVAILABLE', 'color' => '#16A34A']);

        $this->superUserA = $this->makeUser('Super User', $this->tenantA, 'su');
        $this->adminA = $this->makeUser('Administrator', $this->tenantA, 'adm');
        $this->assetManagerA = $this->makeUser('Asset Manager', $this->tenantA, 'mgr');
        $this->technicianA = $this->makeUser('Technician', $this->tenantA, 'tech');
        $this->auditorA = $this->makeUser('Auditor', $this->tenantA, 'aud');
        $this->custodianA = $this->makeUser('Auditor', $this->tenantA, 'cust_a');
        $this->custodianB = $this->makeUser('Auditor', $this->tenantB, 'cust_b');

        $this->assetA = EquipmentAsset::create([
            'tenant_id' => $this->tenantA->id,
            'organization_id' => $this->orgA->id,
            'company_id' => $this->compA1->id,
            'department_id' => $this->deptA1->id,
            'location_id' => $this->locA1->id,
            'category_id' => $catA->id,
            'status_id' => $this->availableStatusA->id,
            'asset_tag' => 'AF-CUST-A1',
            'serial_number' => 'SN-CUST-A1',
            'name' => 'Primary Laptop',
            'condition' => 'good',
            'notes' => 'Initial benchmark note',
        ]);

        $this->assetB = EquipmentAsset::create([
            'tenant_id' => $this->tenantB->id,
            'organization_id' => $orgB->id,
            'company_id' => $this->compB->id,
            'department_id' => $this->deptB->id,
            'location_id' => $this->locB->id,
            'category_id' => $catB->id,
            'status_id' => $statusB->id,
            'asset_tag' => 'AF-CUST-B1',
            'serial_number' => 'SN-CUST-B1',
            'name' => 'Beta Laptop',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $tag): User
    {
        $user = User::create([
            'name' => "{$role} ({$tag})",
            'email' => "{$tag}@custody.test",
            'password' => Hash::make('Password123!'),
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function authHeaders(User $user): array
    {
        // Reset resolved Sanctum guard user & tenant binding between requests in multi-actor tests.
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $user->createToken('stage-c-test')->plainTextToken,
        ];
    }

    public function test_technician_without_assets_transfer_cannot_perform_custody_operations_or_bypass_via_update(): void
    {
        $headers = $this->authHeaders($this->technicianA);
        $initialMovements = AssetMovement::withoutGlobalScope(\App\Scopes\TenantScope::class)->count();

        // 1. Checkout, checkin, transfer endpoints return 403 and leave DB untouched
        $this->withHeaders($headers)->postJson("/api/v1/assets/{$this->assetA->id}/checkout", [
            'user_id' => $this->custodianA->id,
        ])->assertForbidden();

        $this->withHeaders($headers)->postJson("/api/v1/assets/{$this->assetA->id}/checkin", [
            'location_id' => $this->locA1->id,
        ])->assertForbidden();

        $this->withHeaders($headers)->postJson("/api/v1/assets/{$this->assetA->id}/transfer", [
            'company_id' => $this->compA2->id,
            'department_id' => $this->deptA2->id,
            'location_id' => $this->locA2->id,
        ])->assertForbidden();

        // 2. Direct PUT update of assigned_to_user_id, assigned_date, or placement fields returns 403
        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'assigned_to_user_id' => $this->custodianA->id,
        ])->assertForbidden();

        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'assigned_date' => '2026-11-26 10:00:00',
        ])->assertForbidden();

        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'location_id' => $this->locA2->id,
        ])->assertForbidden();

        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'department_id' => $this->deptA2->id,
            'company_id' => $this->compA2->id,
        ])->assertForbidden();

        // 3. Mixed update containing notes + unauthorized custody field rejects atomically (notes stay untouched)
        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'notes' => 'Should NOT persist alongside custody tampering',
            'assigned_to_user_id' => $this->custodianA->id,
            'assigned_date' => '2026-11-26 10:00:00',
        ])->assertForbidden();

        $fresh = $this->assetA->fresh();
        $this->assertNull($fresh->assigned_to_user_id);
        $this->assertNull($fresh->assigned_date);
        $this->assertSame((string) $this->locA1->id, (string) $fresh->location_id);
        $this->assertSame((string) $this->deptA1->id, (string) $fresh->department_id);
        $this->assertSame((string) $this->compA1->id, (string) $fresh->company_id);
        $this->assertSame('Initial benchmark note', $fresh->notes);
        $this->assertSame($initialMovements, AssetMovement::withoutGlobalScope(\App\Scopes\TenantScope::class)->count());
    }

    public function test_technician_retains_ordinary_non_custody_asset_editing(): void
    {
        $headers = $this->authHeaders($this->technicianA);

        // Sending non-custody updates (and optionally unchanged location_id) succeeds cleanly
        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'name' => 'Primary Laptop - Repaired Fan',
            'notes' => 'Thermal paste replaced during bench check',
            'condition' => 'fair',
            'ram' => '32GB DDR5',
            'location_id' => $this->locA1->id,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Primary Laptop - Repaired Fan')
            ->assertJsonPath('data.notes', 'Thermal paste replaced during bench check');

        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetA->id,
            'name' => 'Primary Laptop - Repaired Fan',
            'notes' => 'Thermal paste replaced during bench check',
            'condition' => 'fair',
            'ram' => '32GB DDR5',
            'location_id' => $this->locA1->id,
            'assigned_to_user_id' => null,
        ]);
    }

    public function test_authorised_roles_can_perform_custody_operations_with_tenant_isolation_enforced(): void
    {
        // Asset Manager performs full checkout -> transfer -> checkin + direct custody update
        $mgrHeaders = $this->authHeaders($this->assetManagerA);

        $this->withHeaders($mgrHeaders)->postJson("/api/v1/assets/{$this->assetA->id}/checkout", [
            'user_id' => $this->custodianA->id,
            'location_id' => $this->locA1->id,
            'department_id' => $this->deptA1->id,
            'notes' => 'Issued to custodian A',
        ])->assertOk()
            ->assertJsonPath('data.assigned_to.id', $this->custodianA->id);

        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetA->id,
            'assigned_to_user_id' => $this->custodianA->id,
            'status_id' => $this->assignedStatusA->id,
        ]);
        $this->assertDatabaseHas('asset_movements', [
            'tenant_id' => $this->tenantA->id,
            'equipment_asset_id' => $this->assetA->id,
            'action_type' => 'checkout',
            'to_user_id' => $this->custodianA->id,
        ]);

        // Transfer across companies/departments/locations within Tenant A
        $this->withHeaders($mgrHeaders)->postJson("/api/v1/assets/{$this->assetA->id}/transfer", [
            'company_id' => $this->compA2->id,
            'department_id' => $this->deptA2->id,
            'location_id' => $this->locA2->id,
            'notes' => 'Transferred to Alpha Co 2',
        ])->assertOk();

        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetA->id,
            'company_id' => $this->compA2->id,
            'department_id' => $this->deptA2->id,
            'location_id' => $this->locA2->id,
        ]);

        // Checkin returns asset to available storage
        $this->withHeaders($mgrHeaders)->postJson("/api/v1/assets/{$this->assetA->id}/checkin", [
            'location_id' => $this->locA1->id,
            'notes' => 'Returned to HQ stock',
        ])->assertOk();

        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetA->id,
            'assigned_to_user_id' => null,
            'assigned_date' => null,
            'location_id' => $this->locA1->id,
            'status_id' => $this->availableStatusA->id,
        ]);

        // Administrator and Super User can also perform custody operations within Tenant A
        $this->withHeaders($this->authHeaders($this->adminA))
            ->putJson("/api/v1/assets/{$this->assetA->id}", [
                'assigned_to_user_id' => $this->custodianA->id,
                'assigned_date' => '2026-11-26 11:00:00',
            ])->assertOk();
        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetA->id,
            'assigned_to_user_id' => $this->custodianA->id,
        ]);

        $this->withHeaders($this->authHeaders($this->superUserA))
            ->postJson("/api/v1/assets/{$this->assetA->id}/checkin", [
                'location_id' => $this->locA1->id,
            ])->assertOk();

        // Cross-tenant custody attempts remain blocked even for Asset Manager / Administrator
        $this->withHeaders($mgrHeaders)->postJson("/api/v1/assets/{$this->assetA->id}/checkout", [
            'user_id' => $this->custodianB->id,
            'location_id' => $this->locB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'location_id']);

        $this->withHeaders($mgrHeaders)->postJson("/api/v1/assets/{$this->assetB->id}/checkout", [
            'user_id' => $this->custodianA->id,
        ])->assertNotFound();

        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetB->id,
            'assigned_to_user_id' => null,
        ]);
    }

    public function test_role_permission_matrix_and_super_user_authority(): void
    {
        $expectedPermissions = [
            RoleHierarchyService::ROLE_SUPER_USER => [
                'dashboard.view', 'users.view', 'users.create', 'users.update', 'users.delete',
                'assets.view', 'assets.create', 'assets.update', 'assets.transfer', 'assets.delete',
                'repairs.view', 'repairs.create', 'repairs.update', 'repairs.delete',
                'reports.view', 'audit.view', 'settings.manage',
            ],
            RoleHierarchyService::ROLE_ADMINISTRATOR => [
                'dashboard.view', 'users.view', 'users.create', 'users.update', 'users.delete',
                'assets.view', 'assets.create', 'assets.update', 'assets.transfer', 'assets.delete',
                'repairs.view', 'repairs.create', 'repairs.update', 'repairs.delete',
                'reports.view', 'audit.view', 'settings.manage',
            ],
            RoleHierarchyService::ROLE_ASSET_MANAGER => [
                'dashboard.view', 'users.view',
                'assets.view', 'assets.create', 'assets.update', 'assets.transfer', 'assets.delete',
                'repairs.view', 'repairs.create', 'repairs.update', 'reports.view',
            ],
            RoleHierarchyService::ROLE_TECHNICIAN => [
                'dashboard.view',
                'assets.view', 'assets.update',
                'repairs.view', 'repairs.create', 'repairs.update',
            ],
            RoleHierarchyService::ROLE_AUDITOR => [
                'dashboard.view', 'users.view',
                'assets.view', 'repairs.view', 'reports.view', 'audit.view',
            ],
        ];

        foreach ($expectedPermissions as $roleName => $expectedList) {
            $role = Role::where('name', $roleName)->where('guard_name', RoleHierarchyService::GUARD)->firstOrFail();
            $actual = $role->permissions->pluck('name')->sort()->values()->all();
            $expectedSorted = collect($expectedList)->sort()->values()->all();
            $this->assertSame($expectedSorted, $actual, "Permission mismatch for role {$roleName}");
        }

        // Auditor remains read-only and Super User retains Gate::before wildcard authority
        $this->assertFalse($this->technicianA->can('assets.transfer'));
        $this->assertFalse($this->auditorA->can('assets.transfer'));
        $this->assertTrue($this->assetManagerA->can('assets.transfer'));
        $this->assertTrue($this->adminA->can('assets.transfer'));
        $this->assertTrue($this->superUserA->can('assets.transfer'));
        $this->assertTrue($this->superUserA->can('settings.manage'));
    }

    public function test_technician_repair_completion_preserves_asset_assignment_and_placement(): void
    {
        $inRepairStatus = AssetStatus::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'In Repair',
            'code' => 'IN_REPAIR',
            'color' => '#F59E0B',
        ]);

        // Assign assetA via authorised Asset Manager checkout first
        $this->withHeaders($this->authHeaders($this->assetManagerA))
            ->postJson("/api/v1/assets/{$this->assetA->id}/checkout", [
                'user_id' => $this->custodianA->id,
                'location_id' => $this->locA1->id,
                'department_id' => $this->deptA1->id,
                'checkout_date' => '2026-11-26 09:00:00',
            ])->assertOk();

        $assignedAssetBeforeRepair = $this->assetA->fresh();
        $techHeaders = $this->authHeaders($this->technicianA);

        // Technician creates repair with crafted extra custody fields; they must not alter asset custody/placement
        $createRepairResp = $this->withHeaders($techHeaders)->postJson('/api/v1/repairs', [
            'equipment_asset_id' => $this->assetA->id,
            'fault_description' => 'Display flickering',
            'repair_center' => 'internal',
            'assigned_to_user_id' => null,
            'location_id' => $this->locA2->id,
            'department_id' => $this->deptA2->id,
            'company_id' => $this->compA2->id,
        ])->assertCreated();

        $repairId = $createRepairResp->json('data.id');
        $afterOpen = $this->assetA->fresh();
        $this->assertSame((string) $inRepairStatus->id, (string) $afterOpen->status_id);
        $this->assertSame($this->custodianA->id, $afterOpen->assigned_to_user_id);
        $this->assertSame((string) $this->locA1->id, (string) $afterOpen->location_id);
        $this->assertSame((string) $this->deptA1->id, (string) $afterOpen->department_id);
        $this->assertSame((string) $this->compA1->id, (string) $afterOpen->company_id);

        // Updating repair with crafted custody fields also cannot alter asset custody
        $this->withHeaders($techHeaders)->putJson("/api/v1/repairs/{$repairId}", [
            'diagnostic_notes' => 'Replaced display cable',
            'assigned_to_user_id' => null,
            'location_id' => $this->locA2->id,
            'department_id' => $this->deptA2->id,
            'company_id' => $this->compA2->id,
        ])->assertOk();

        // Completing repair restores status to ASSIGNED without clearing assigned_to_user_id or placement
        $this->withHeaders($techHeaders)->postJson("/api/v1/repairs/{$repairId}/progress", [
            'status' => 'completed',
            'comments' => 'Verified fixed under load',
            'resolution_summary' => 'Display harness replaced',
            'cost' => 75.00,
            'assigned_to_user_id' => null,
            'location_id' => $this->locA2->id,
            'department_id' => $this->deptA2->id,
            'company_id' => $this->compA2->id,
        ])->assertOk()
            ->assertJsonPath('data.repair_status', 'completed');

        $afterComplete = $this->assetA->fresh();
        $this->assertSame($this->custodianA->id, $afterComplete->assigned_to_user_id);
        $this->assertEquals($assignedAssetBeforeRepair->assigned_date, $afterComplete->assigned_date);
        $this->assertSame((string) $this->locA1->id, (string) $afterComplete->location_id);
        $this->assertSame((string) $this->deptA1->id, (string) $afterComplete->department_id);
        $this->assertSame((string) $this->compA1->id, (string) $afterComplete->company_id);
        $this->assertSame((string) $this->assignedStatusA->id, (string) $afterComplete->status_id);

        $this->assertDatabaseHas('repair_jobs', [
            'id' => $repairId,
            'repair_status' => 'completed',
            'resolution_summary' => 'Display harness replaced',
        ]);
        $this->assertDatabaseHas('asset_movements', [
            'equipment_asset_id' => $this->assetA->id,
            'action_type' => 'repair_completed',
            'from_user_id' => $this->custodianA->id,
            'to_user_id' => $this->custodianA->id,
            'from_location_id' => $this->locA1->id,
            'to_location_id' => $this->locA1->id,
            'to_status_id' => $this->assignedStatusA->id,
        ]);

        // Unassigned asset repair completion still restores status to AVAILABLE cleanly
        $unassignedAsset = EquipmentAsset::create([
            'tenant_id' => $this->tenantA->id,
            'organization_id' => $this->orgA->id,
            'company_id' => $this->compA2->id,
            'department_id' => $this->deptA2->id,
            'location_id' => $this->locA2->id,
            'status_id' => $this->availableStatusA->id,
            'asset_tag' => 'AF-UNASSIGNED-REP-1',
            'serial_number' => 'SN-UNASSIGNED-REP-1',
            'name' => 'Spare Asset For Repair',
        ]);

        $unassignedRepairId = $this->withHeaders($techHeaders)->postJson('/api/v1/repairs', [
            'equipment_asset_id' => $unassignedAsset->id,
            'fault_description' => 'Keyboard key stuck',
        ])->assertCreated()->json('data.id');

        $this->withHeaders($techHeaders)->postJson("/api/v1/repairs/{$unassignedRepairId}/progress", [
            'status' => 'completed',
            'comments' => 'Keycap replaced',
        ])->assertOk();

        $freshUnassigned = $unassignedAsset->fresh();
        $this->assertNull($freshUnassigned->assigned_to_user_id);
        $this->assertSame((string) $this->availableStatusA->id, (string) $freshUnassigned->status_id);
        $this->assertSame((string) $this->locA2->id, (string) $freshUnassigned->location_id);
    }

    protected function makeCsvUpload(string $csvContent, string $filename = 'assets.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($filename, $csvContent);
    }

    public function test_import_custody_and_placement_enforces_assets_transfer_and_tenant_isolation(): void
    {
        $csvWithLocation = implode("\n", [
            'serial_number,category,manufacturer,model,location,asset_tag,name',
            'SN-IMP-LOC-1,Laptops,HP,EliteBook 840 G11,Alpha HQ,AF-IMP-LOC-1,Imported Placed Asset',
        ]);

        // 1. Technician and Auditor cannot import assets with location (403, zero DB writes)
        $this->withHeaders($this->authHeaders($this->technicianA))
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($csvWithLocation)])
            ->assertForbidden();

        $this->withHeaders($this->authHeaders($this->auditorA))
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($csvWithLocation)])
            ->assertForbidden();

        $this->assertDatabaseMissing('equipment_assets', ['serial_number' => 'SN-IMP-LOC-1']);

        // 2. User with assets.create + assets.update (but WITHOUT assets.transfer) can create/import
        // ordinary assets without placement/custody, but is blocked from setting or changing placement.
        $creatorNoTransfer = User::create([
            'name' => 'Catalog Registrar',
            'email' => 'registrar@custody.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);
        $creatorNoTransfer->givePermissionTo(['assets.view', 'assets.create', 'assets.update']);
        $creatorHeaders = $this->authHeaders($creatorNoTransfer);

        // Ordinary POST /assets without placement/custody succeeds
        $this->withHeaders($creatorHeaders)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-REG-ORD-1',
            'serial_number' => 'SN-REG-ORD-1',
            'name' => 'Unplaced Ordinary Asset',
        ])->assertCreated();
        $this->assertDatabaseHas('equipment_assets', [
            'serial_number' => 'SN-REG-ORD-1',
            'location_id' => null,
        ]);

        // POST /assets with initial placement or custodian requires assets.transfer
        $this->withHeaders($creatorHeaders)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-REG-PLACED-1',
            'serial_number' => 'SN-REG-PLACED-1',
            'location_id' => $this->locA1->id,
        ])->assertForbidden();
        $this->assertDatabaseMissing('equipment_assets', ['serial_number' => 'SN-REG-PLACED-1']);

        // CSV import without location succeeds for assets.create without assets.transfer
        $csvNoLocation = implode("\n", [
            'serial_number,category,manufacturer,model,asset_tag,name',
            'SN-IMP-ORD-1,Laptops,HP,ProBook 450,AF-IMP-ORD-1,Ordinary Imported Asset',
        ]);
        $this->withHeaders($creatorHeaders)
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($csvNoLocation)])
            ->assertOk();
        $this->assertDatabaseHas('equipment_assets', [
            'serial_number' => 'SN-IMP-ORD-1',
            'tenant_id' => $this->tenantA->id,
            'location_id' => null,
        ]);

        // Multi-row CSV with valid row 1 + placed row 2 must fail atomically (zero partial inserts)
        $mixedCreateCsv = implode("\n", [
            'serial_number,category,manufacturer,model,location,asset_tag,name',
            'SN-IMP-ATOM-1,Laptops,HP,ProBook 450,,AF-IMP-ATOM-1,Atomic Row 1',
            'SN-IMP-ATOM-2,Laptops,HP,EliteBook 840,Alpha HQ,AF-IMP-ATOM-2,Atomic Row 2',
        ]);
        $this->withHeaders($creatorHeaders)
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($mixedCreateCsv)])
            ->assertForbidden();
        $this->assertDatabaseMissing('equipment_assets', ['serial_number' => 'SN-IMP-ATOM-1']);
        $this->assertDatabaseMissing('equipment_assets', ['serial_number' => 'SN-IMP-ATOM-2']);

        // Upsert attempting to change an existing asset's location rejects with 403 and preserves DB state
        $upsertChangeLocCsv = implode("\n", [
            'serial_number,category,manufacturer,model,location,notes',
            'SN-CUST-A1,Laptops,HP,ProBook 450,Alpha Branch,Attempted bypass note',
        ]);
        $this->withHeaders($creatorHeaders)
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($upsertChangeLocCsv)])
            ->assertForbidden();
        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetA->id,
            'location_id' => $this->locA1->id,
            'notes' => 'Initial benchmark note',
        ]);

        // 3. Authorised Asset Manager CAN import with Tenant A location and relocate via upsert
        $mgrHeaders = $this->authHeaders($this->assetManagerA);

        $this->withHeaders($mgrHeaders)
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($csvWithLocation)])
            ->assertOk();
        $this->assertDatabaseHas('equipment_assets', [
            'serial_number' => 'SN-IMP-LOC-1',
            'tenant_id' => $this->tenantA->id,
            'location_id' => $this->locA1->id,
        ]);

        // Put assetA into ASSIGNED status, then upsert via Asset Manager to verify relocation preserves status & tag
        $this->assetA->update(['status_id' => $this->assignedStatusA->id]);
        $this->withHeaders($mgrHeaders)
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($upsertChangeLocCsv)])
            ->assertOk();
        $this->assertDatabaseHas('equipment_assets', [
            'id' => $this->assetA->id,
            'asset_tag' => 'AF-CUST-A1',
            'status_id' => $this->assignedStatusA->id,
            'location_id' => $this->locA2->id,
            'notes' => 'Attempted bypass note',
        ]);

        // 4. Tenant isolation: referencing Tenant B location name during import fails 422 cleanly
        $crossTenantLocCsv = implode("\n", [
            'serial_number,category,manufacturer,model,location,asset_tag',
            'SN-IMP-XTEN-1,Laptops,HP,ProBook 450,Beta Vault,AF-IMP-XTEN-1',
        ]);
        $this->withHeaders($mgrHeaders)
            ->post('/api/v1/assets/import', ['file' => $this->makeCsvUpload($crossTenantLocCsv)])
            ->assertStatus(422);
        $this->assertDatabaseMissing('equipment_assets', ['serial_number' => 'SN-IMP-XTEN-1']);
    }

    public function test_repair_completion_on_assigned_asset_never_falls_back_to_available_when_assigned_status_missing(): void
    {
        $inRepairStatus = AssetStatus::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'In Repair',
            'code' => 'IN_REPAIR',
            'color' => '#F59E0B',
        ]);

        // Checkout assetA cleanly while ASSIGNED status exists, then remove Tenant A's ASSIGNED status row
        $this->withHeaders($this->authHeaders($this->assetManagerA))
            ->postJson("/api/v1/assets/{$this->assetA->id}/checkout", [
                'user_id' => $this->custodianA->id,
            ])->assertOk();

        $this->assignedStatusA->delete();

        $techHeaders = $this->authHeaders($this->technicianA);
        $repairId = $this->withHeaders($techHeaders)->postJson('/api/v1/repairs', [
            'equipment_asset_id' => $this->assetA->id,
            'fault_description' => 'Power jack repair',
        ])->assertCreated()->json('data.id');

        $this->withHeaders($techHeaders)->postJson("/api/v1/repairs/{$repairId}/progress", [
            'status' => 'completed',
            'comments' => 'Power jack soldered',
        ])->assertOk();

        $fresh = $this->assetA->fresh();
        $this->assertSame($this->custodianA->id, $fresh->assigned_to_user_id);
        $this->assertNotSame((string) $this->availableStatusA->id, (string) $fresh->status_id);
        $this->assertSame((string) $inRepairStatus->id, (string) $fresh->status_id);

        $this->assertDatabaseHas('asset_movements', [
            'equipment_asset_id' => $this->assetA->id,
            'action_type' => 'repair_completed',
            'to_user_id' => $this->custodianA->id,
            'to_status_id' => $inRepairStatus->id,
        ]);
    }
}
