<?php

namespace Tests\Feature;

use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\EquipmentAsset;
use App\Models\FaultType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Organization;
use App\Models\RepairJob;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warranty;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StageATenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;

    protected Organization $orgA1;
    protected Organization $orgA2;
    protected Organization $orgB;

    protected Company $compA1;
    protected Company $compA2;
    protected Company $compB;

    protected Department $deptA1;
    protected Department $deptA2;
    protected Department $deptB;

    protected Team $teamA2;
    protected Team $teamB;

    protected User $adminA;
    protected User $adminB;

    protected Category $catA1;
    protected Category $catA2;
    protected Category $catB;

    protected Manufacturer $mfgA1;
    protected Manufacturer $mfgA2;
    protected Manufacturer $mfgB;

    protected AssetModel $modelA1;
    protected AssetModel $modelA2;
    protected AssetModel $modelB;

    protected Location $locA1;
    protected Location $locA2;
    protected Location $locB;

    protected AssetStatus $statusA;
    protected AssetStatus $statusB;

    protected FaultType $faultA;
    protected FaultType $faultB;

    protected EquipmentAsset $assetA;
    protected EquipmentAsset $assetB;

    protected RepairJob $repairA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tenantA = Tenant::create(['name' => 'Alpha Corp', 'slug' => 'alpha-corp', 'email' => 'alpha@tenant.test', 'status' => 'active']);
        $this->tenantB = Tenant::create(['name' => 'Beta Corp', 'slug' => 'beta-corp', 'email' => 'beta@tenant.test', 'status' => 'active']);

        $this->orgA1 = Organization::create(['tenant_id' => $this->tenantA->id, 'name' => 'Alpha Holdings 1', 'code' => 'AH1', 'status' => 'active']);
        $this->orgA2 = Organization::create(['tenant_id' => $this->tenantA->id, 'name' => 'Alpha Holdings 2', 'code' => 'AH2', 'status' => 'active']);
        $this->orgB = Organization::create(['tenant_id' => $this->tenantB->id, 'name' => 'Beta Holdings', 'code' => 'BH1', 'status' => 'active']);

        $this->compA1 = Company::create(['organization_id' => $this->orgA1->id, 'name' => 'Alpha Co 1', 'code' => 'AC1', 'status' => 'active']);
        $this->compA2 = Company::create(['organization_id' => $this->orgA2->id, 'name' => 'Alpha Co 2', 'code' => 'AC2', 'status' => 'active']);
        $this->compB = Company::create(['organization_id' => $this->orgB->id, 'name' => 'Beta Co', 'code' => 'BC1', 'status' => 'active']);

        $this->deptA1 = Department::create(['company_id' => $this->compA1->id, 'name' => 'Alpha IT', 'code' => 'AIT', 'status' => 'active']);
        $this->deptA2 = Department::create(['company_id' => $this->compA2->id, 'name' => 'Alpha Ops', 'code' => 'AOPS', 'status' => 'active']);
        $this->deptB = Department::create(['company_id' => $this->compB->id, 'name' => 'Beta Security', 'code' => 'BSEC', 'status' => 'active']);

        $this->teamA2 = Team::create(['department_id' => $this->deptA2->id, 'name' => 'Alpha Ops Team', 'code' => 'AOT', 'status' => 'active']);
        $this->teamB = Team::create(['department_id' => $this->deptB->id, 'name' => 'Beta Sec Team', 'code' => 'BST', 'status' => 'active']);

        $this->adminA = User::create([
            'name' => 'Alpha Admin',
            'email' => 'admin.a@alpha.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'organization_id' => $this->orgA1->id,
            'company_id' => $this->compA1->id,
            'department_id' => $this->deptA1->id,
            'status' => 'active',
        ]);
        $this->adminA->assignRole('Administrator');

        $this->adminB = User::create([
            'name' => 'Beta Admin',
            'email' => 'admin.b@beta.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantB->id,
            'organization_id' => $this->orgB->id,
            'company_id' => $this->compB->id,
            'department_id' => $this->deptB->id,
            'status' => 'active',
        ]);
        $this->adminB->assignRole('Administrator');

        $this->catA1 = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Alpha Laptops', 'code' => 'ALPHA-LAP-CODE', 'status' => 'active']);
        $this->catA2 = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Alpha Monitors', 'code' => 'ALPHA-MON-CODE', 'status' => 'active']);
        $this->catB = Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'Beta Classified Gear', 'code' => 'BETA-SECRET-CODE', 'status' => 'active']);

        $this->mfgA1 = Manufacturer::create(['tenant_id' => $this->tenantA->id, 'name' => 'Alpha OEM', 'code' => 'ALPHA-MFG-1', 'status' => 'active']);
        $this->mfgA2 = Manufacturer::create(['tenant_id' => $this->tenantA->id, 'name' => 'Alpha Alt OEM', 'code' => 'ALPHA-MFG-2', 'status' => 'active']);
        $this->mfgB = Manufacturer::create(['tenant_id' => $this->tenantB->id, 'name' => 'Beta OEM', 'code' => 'BETA-MFG-SECRET', 'status' => 'active']);

        $this->modelA1 = AssetModel::create([
            'tenant_id' => $this->tenantA->id,
            'manufacturer_id' => $this->mfgA1->id,
            'category_id' => $this->catA1->id,
            'name' => 'Alpha Book 14',
            'model_number' => 'MOD-ALPHA-14',
            'status' => 'active',
        ]);
        $this->modelA2 = AssetModel::create([
            'tenant_id' => $this->tenantA->id,
            'manufacturer_id' => $this->mfgA2->id,
            'category_id' => $this->catA2->id,
            'name' => 'Alpha Display 27',
            'model_number' => 'MOD-ALPHA-27',
            'status' => 'active',
        ]);
        $this->modelB = AssetModel::create([
            'tenant_id' => $this->tenantB->id,
            'manufacturer_id' => $this->mfgB->id,
            'category_id' => $this->catB->id,
            'name' => 'Beta Vault Unit',
            'model_number' => 'MOD-BETA-VAULT-99',
            'status' => 'active',
        ]);

        $this->locA1 = Location::create(['tenant_id' => $this->tenantA->id, 'company_id' => $this->compA1->id, 'name' => 'Alpha HQ', 'code' => 'LOC-ALPHA-1', 'status' => 'active']);
        $this->locA2 = Location::create(['tenant_id' => $this->tenantA->id, 'company_id' => $this->compA2->id, 'name' => 'Alpha Branch', 'code' => 'LOC-ALPHA-2', 'status' => 'active']);
        $this->locB = Location::create(['tenant_id' => $this->tenantB->id, 'company_id' => $this->compB->id, 'name' => 'Beta Bunker', 'code' => 'LOC-BETA-SECRET', 'status' => 'active']);

        $this->statusA = AssetStatus::create(['tenant_id' => $this->tenantA->id, 'name' => 'Available', 'code' => 'AVAILABLE', 'color' => '#16A34A']);
        $this->statusB = AssetStatus::create(['tenant_id' => $this->tenantB->id, 'name' => 'Available Beta', 'code' => 'AVAILABLE', 'color' => '#16A34A']);

        $this->faultA = FaultType::create(['tenant_id' => $this->tenantA->id, 'name' => 'Battery Alpha', 'code' => 'BAT-A', 'severity' => 'low', 'status' => 'active']);
        $this->faultB = FaultType::create(['tenant_id' => $this->tenantB->id, 'name' => 'Board Beta', 'code' => 'BRD-B', 'severity' => 'high', 'status' => 'active']);

        $this->assetA = EquipmentAsset::create([
            'tenant_id' => $this->tenantA->id,
            'organization_id' => $this->orgA1->id,
            'company_id' => $this->compA1->id,
            'department_id' => $this->deptA1->id,
            'asset_tag' => 'AF-A-STAGEA-1',
            'serial_number' => 'SN-A-STAGEA-1',
            'name' => 'Alpha Primary Asset',
            'category_id' => $this->catA1->id,
            'manufacturer_id' => $this->mfgA1->id,
            'asset_model_id' => $this->modelA1->id,
            'location_id' => $this->locA1->id,
            'status_id' => $this->statusA->id,
        ]);

        $this->assetB = EquipmentAsset::create([
            'tenant_id' => $this->tenantB->id,
            'organization_id' => $this->orgB->id,
            'company_id' => $this->compB->id,
            'department_id' => $this->deptB->id,
            'asset_tag' => 'AF-B-STAGEA-1',
            'serial_number' => 'SN-B-STAGEA-1',
            'name' => 'Beta Restricted Asset',
            'category_id' => $this->catB->id,
            'manufacturer_id' => $this->mfgB->id,
            'asset_model_id' => $this->modelB->id,
            'location_id' => $this->locB->id,
            'status_id' => $this->statusB->id,
        ]);

        $this->repairA = RepairJob::create([
            'tenant_id' => $this->tenantA->id,
            'equipment_asset_id' => $this->assetA->id,
            'job_number' => 'REP-2026-0001',
            'fault_description' => 'Fan noise',
            'repair_center' => 'internal',
            'repair_status' => 'pending',
            'start_date' => now()->toDateString(),
        ]);
    }

    protected function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer ' . $user->createToken('stage-a-test')->plainTextToken];
    }

    public function test_reference_controller_searches_cannot_leak_cross_tenant_records(): void
    {
        $headers = $this->authHeaders($this->adminA);

        // Searching by Tenant B code / model_number must return zero records.
        $this->withHeaders($headers)->getJson('/api/v1/categories?search=BETA-SECRET-CODE')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($headers)->getJson('/api/v1/manufacturers?search=BETA-MFG-SECRET')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($headers)->getJson('/api/v1/asset-models?search=MOD-BETA-VAULT-99')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($headers)->getJson('/api/v1/locations?search=LOC-BETA-SECRET')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Filter + search combinations must honor both filters and TenantScope.
        $this->withHeaders($headers)
            ->getJson("/api/v1/asset-models?manufacturer_id={$this->mfgA1->id}&search=MOD-ALPHA-27")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withHeaders($headers)
            ->getJson("/api/v1/locations?company_id={$this->compA1->id}&search=LOC-ALPHA-2")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Valid own-tenant code / model_number searches return the exact expected records.
        $this->withHeaders($headers)->getJson('/api/v1/categories?search=ALPHA-LAP-CODE')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->catA1->id)
            ->assertJsonPath('data.0.code', 'ALPHA-LAP-CODE');

        $this->withHeaders($headers)->getJson('/api/v1/manufacturers?search=ALPHA-MFG-1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->mfgA1->id)
            ->assertJsonPath('data.0.code', 'ALPHA-MFG-1');

        $this->withHeaders($headers)->getJson('/api/v1/asset-models?search=MOD-ALPHA-14')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->modelA1->id)
            ->assertJsonPath('data.0.model_number', 'MOD-ALPHA-14');

        $this->withHeaders($headers)->getJson('/api/v1/locations?search=LOC-ALPHA-1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->locA1->id)
            ->assertJsonPath('data.0.code', 'LOC-ALPHA-1');
    }

    public function test_cross_tenant_foreign_key_injection_is_rejected_across_all_endpoints(): void
    {
        $headers = $this->authHeaders($this->adminA);

        // EquipmentAsset create with every Tenant B FK
        $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-INJECT-1',
            'serial_number' => 'SN-INJECT-1',
            'category_id' => $this->catB->id,
            'manufacturer_id' => $this->mfgB->id,
            'asset_model_id' => $this->modelB->id,
            'location_id' => $this->locB->id,
            'status_id' => $this->statusB->id,
            'organization_id' => $this->orgB->id,
            'company_id' => $this->compB->id,
            'department_id' => $this->deptB->id,
            'assigned_to_user_id' => $this->adminB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'category_id',
                'manufacturer_id',
                'asset_model_id',
                'location_id',
                'status_id',
                'organization_id',
                'company_id',
                'department_id',
                'assigned_to_user_id',
            ]);

        $this->assertDatabaseMissing('equipment_assets', ['asset_tag' => 'AF-INJECT-1']);

        // EquipmentAsset update with Tenant B user & location
        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'assigned_to_user_id' => $this->adminB->id,
            'location_id' => $this->locB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to_user_id', 'location_id']);

        // Asset lifecycle actions with Tenant B references
        $this->withHeaders($headers)->postJson("/api/v1/assets/{$this->assetA->id}/checkout", [
            'user_id' => $this->adminB->id,
            'location_id' => $this->locB->id,
            'department_id' => $this->deptB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'location_id', 'department_id']);

        $this->withHeaders($headers)->postJson("/api/v1/assets/{$this->assetA->id}/checkin", [
            'location_id' => $this->locB->id,
            'status_id' => $this->statusB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['location_id', 'status_id']);

        $this->withHeaders($headers)->postJson("/api/v1/assets/{$this->assetA->id}/transfer", [
            'company_id' => $this->compB->id,
            'department_id' => $this->deptB->id,
            'location_id' => $this->locB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['company_id', 'department_id', 'location_id']);

        // Warranty attached to Tenant B asset
        $this->withHeaders($headers)->postJson('/api/v1/warranties', [
            'equipment_asset_id' => $this->assetB->id,
            'provider_name' => 'Cross Tenant Care',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_asset_id']);

        $this->assertSame(0, Warranty::withoutGlobalScope(\App\Scopes\TenantScope::class)->count());

        // Repair create & update with Tenant B references
        $this->withHeaders($headers)->postJson('/api/v1/repairs', [
            'equipment_asset_id' => $this->assetB->id,
            'fault_type_id' => $this->faultB->id,
            'fault_description' => 'Injected repair',
            'technician_id' => $this->adminB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_asset_id', 'fault_type_id', 'technician_id']);

        $this->withHeaders($headers)->putJson("/api/v1/repairs/{$this->repairA->id}", [
            'fault_type_id' => $this->faultB->id,
            'technician_id' => $this->adminB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['fault_type_id', 'technician_id']);

        // User create & update with Tenant B organizational hierarchy IDs
        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Injected Org User',
            'email' => 'injected.org@alpha.test',
            'password' => 'Password123!',
            'organization_id' => $this->orgB->id,
            'company_id' => $this->compB->id,
            'department_id' => $this->deptB->id,
            'team_id' => $this->teamB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id', 'company_id', 'department_id', 'team_id']);

        $this->withHeaders($headers)->putJson("/api/v1/users/{$this->adminA->id}", [
            'organization_id' => $this->orgB->id,
            'company_id' => $this->compB->id,
            'department_id' => $this->deptB->id,
            'team_id' => $this->teamB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id', 'company_id', 'department_id', 'team_id']);

        // Reference create with Tenant B references
        $this->withHeaders($headers)->postJson('/api/v1/asset-models', [
            'name' => 'Cross Tenant Model',
            'manufacturer_id' => $this->mfgB->id,
            'category_id' => $this->catB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['manufacturer_id', 'category_id']);

        $this->withHeaders($headers)->postJson('/api/v1/locations', [
            'name' => 'Cross Tenant Location',
            'company_id' => $this->compB->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['company_id']);

        $this->withHeaders($headers)->postJson('/api/v1/departments', [
            'company_id' => $this->compB->id,
            'name' => 'Cross Tenant Dept',
            'code' => 'CTD',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['company_id']);
    }

    public function test_intra_tenant_parent_child_hierarchy_mismatches_are_rejected(): void
    {
        $headers = $this->authHeaders($this->adminA);

        // User org hierarchy mismatch within Tenant A
        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Mismatched User',
            'email' => 'mismatch@alpha.test',
            'password' => 'Password123!',
            'organization_id' => $this->orgA1->id,
            'company_id' => $this->compA2->id,      // belongs to orgA2
            'department_id' => $this->deptA1->id,   // belongs to compA1
            'team_id' => $this->teamA2->id,         // belongs to deptA2
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['company_id', 'department_id', 'team_id']);

        // Asset org, catalog, and location-company mismatch within Tenant A
        $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-HIER-1',
            'serial_number' => 'SN-HIER-1',
            'organization_id' => $this->orgA1->id,
            'company_id' => $this->compA2->id,
            'department_id' => $this->deptA1->id,
            'manufacturer_id' => $this->mfgA1->id,
            'category_id' => $this->catA1->id,
            'asset_model_id' => $this->modelA2->id, // belongs to mfgA2 & catA2
            'location_id' => $this->locA1->id,      // belongs to compA1
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['company_id', 'department_id', 'asset_model_id', 'location_id']);

        // Transfer mismatch (new company_id without matching department_id/location_id)
        $this->withHeaders($headers)->postJson("/api/v1/assets/{$this->assetA->id}/transfer", [
            'company_id' => $this->compA2->id,
            'department_id' => $this->deptA1->id,
            'location_id' => $this->locA1->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['department_id', 'location_id']);

        // Partial asset update on non-hierarchy field still succeeds cleanly
        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetA->id}", [
            'notes' => 'Updated safely without breaking existing links',
        ])->assertOk();
    }

    public function test_organization_model_applies_global_tenant_scope(): void
    {
        app()->instance('current_tenant_id', $this->tenantA->id);

        try {
            $orgCodes = Organization::all()->pluck('code')->sort()->values()->all();
            $this->assertSame(['AH1', 'AH2'], $orgCodes);
        } finally {
            app()->forgetInstance('current_tenant_id');
        }
    }

    public function test_soft_deleted_and_malformed_references_and_transitive_mismatches_are_rejected(): void
    {
        $headers = $this->authHeaders($this->adminA);

        $deletedCat = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Deleted Cat', 'code' => 'DELC', 'status' => 'active']);
        $deletedComp = Company::create(['organization_id' => $this->orgA1->id, 'name' => 'Deleted Co', 'code' => 'DELCO', 'status' => 'active']);
        $deletedCat->delete();
        $deletedComp->delete();

        $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-DEL-REF-1',
            'serial_number' => 'SN-DEL-REF-1',
            'category_id' => $deletedCat->id,
            'company_id' => $deletedComp->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'company_id']);

        $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-BAD-ID-1',
            'serial_number' => 'SN-BAD-ID-1',
            'category_id' => 'not-a-uuid',
            'assigned_to_user_id' => 'abc',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'assigned_to_user_id']);

        // Transitive ancestry mismatches when intermediate parent IDs are omitted
        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Transitive User 1',
            'email' => 'transitive1@alpha.test',
            'password' => 'Password123!',
            'organization_id' => $this->orgA1->id,
            'department_id' => $this->deptA2->id, // belongs to compA2 -> orgA2
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['department_id']);

        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Transitive User 2',
            'email' => 'transitive2@alpha.test',
            'password' => 'Password123!',
            'company_id' => $this->compA1->id,
            'team_id' => $this->teamA2->id, // belongs to deptA2 -> compA2
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['team_id']);
    }

    public function test_authoritative_tenant_assignment_cannot_be_overridden_and_missing_tenant_context_fails_safely(): void
    {
        $headers = $this->authHeaders($this->adminA);

        // Payload attempting to inject tenant_id of Tenant B must still persist under Tenant A
        $assetResp = $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'tenant_id' => $this->tenantB->id,
            'asset_tag' => 'AF-AUTH-TENANT-1',
            'serial_number' => 'SN-AUTH-TENANT-1',
            'name' => 'Tenant Locked Asset',
        ])->assertCreated();

        $this->assertDatabaseHas('equipment_assets', [
            'id' => $assetResp->json('data.id'),
            'tenant_id' => $this->tenantA->id,
        ]);

        $userResp = $this->withHeaders($headers)->postJson('/api/v1/users', [
            'tenant_id' => $this->tenantB->id,
            'name' => 'Tenant Locked User',
            'email' => 'locked.user@alpha.test',
            'password' => 'Password123!',
            'roles' => ['Technician'],
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'id' => $userResp->json('data.id'),
            'tenant_id' => $this->tenantA->id,
        ]);

        // Tenant-less Administrator is blocked at session level (401 via EnsureActiveSession),
        // while a platform Super User without an active tenant context fails safely with 403 at controller boundary.
        $orphanAdmin = User::create([
            'name' => 'Orphan Admin',
            'email' => 'orphan@admin.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);
        $orphanAdmin->assignRole('Administrator');
        $orphanAdmin->tenant_id = null;

        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');
        $this->actingAs($orphanAdmin, 'sanctum')
            ->postJson('/api/v1/assets', [
                'asset_tag' => 'AF-ORPHAN-1',
                'serial_number' => 'SN-ORPHAN-1',
            ])->assertUnauthorized();

        $orphanSuper = User::create([
            'name' => 'Tenantless Super',
            'email' => 'orphan@super.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'status' => 'active',
        ]);
        $orphanSuper->assignRole('Super User');
        $orphanSuper->tenant_id = null;

        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');
        $this->actingAs($orphanSuper, 'sanctum')
            ->postJson('/api/v1/assets', [
                'asset_tag' => 'AF-ORPHAN-1',
                'serial_number' => 'SN-ORPHAN-1',
            ])->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');
        $this->actingAs($orphanSuper, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Orphan Created User',
                'email' => 'orphan.created@admin.test',
                'password' => 'Password123!',
            ])->assertForbidden();

        $this->assertDatabaseMissing('equipment_assets', ['asset_tag' => 'AF-ORPHAN-1']);
        $this->assertDatabaseMissing('users', ['email' => 'orphan.created@admin.test']);
    }
}
