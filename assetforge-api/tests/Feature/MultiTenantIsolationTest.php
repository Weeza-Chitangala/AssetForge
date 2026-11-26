<?php

namespace Tests\Feature;

use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\EquipmentAsset;
use App\Models\Location;
use App\Models\Organization;
use App\Models\RepairJob;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warranty;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $adminA;
    protected User $adminB;
    protected EquipmentAsset $assetA;
    protected EquipmentAsset $assetB;
    protected Warranty $warrantyB;
    protected RepairJob $repairB;
    protected Department $deptB;
    protected Supplier $supplierB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tenantA = Tenant::create(['name' => 'Tenant Alpha', 'slug' => 'tenant-alpha', 'email' => 'alpha@tenant.test', 'status' => 'active']);
        $this->tenantB = Tenant::create(['name' => 'Tenant Beta', 'slug' => 'tenant-beta', 'email' => 'beta@tenant.test', 'status' => 'active']);

        $orgA = Organization::create(['tenant_id' => $this->tenantA->id, 'name' => 'Org A', 'code' => 'OA', 'status' => 'active']);
        $orgB = Organization::create(['tenant_id' => $this->tenantB->id, 'name' => 'Org B', 'code' => 'OB', 'status' => 'active']);

        $compA = Company::create(['organization_id' => $orgA->id, 'name' => 'Comp A', 'code' => 'CA', 'status' => 'active']);
        $compB = Company::create(['organization_id' => $orgB->id, 'name' => 'Comp B', 'code' => 'CB', 'status' => 'active']);

        $this->deptB = Department::create(['company_id' => $compB->id, 'name' => 'Beta Dept', 'code' => 'BD', 'status' => 'active']);

        $this->adminA = User::create([
            'name' => 'Admin A',
            'email' => 'admin.a@alpha.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantA->id,
            'company_id' => $compA->id,
            'status' => 'active',
        ]);
        $this->adminA->assignRole('Administrator');

        $this->adminB = User::create([
            'name' => 'Admin B',
            'email' => 'admin.b@beta.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenantB->id,
            'company_id' => $compB->id,
            'status' => 'active',
        ]);
        $this->adminB->assignRole('Administrator');

        $catA = Category::create(['tenant_id' => $this->tenantA->id, 'name' => 'Laptops A', 'code' => 'LAPA', 'status' => 'active']);
        $catB = Category::create(['tenant_id' => $this->tenantB->id, 'name' => 'Laptops B', 'code' => 'LAPB', 'status' => 'active']);

        $locA = Location::create(['tenant_id' => $this->tenantA->id, 'company_id' => $compA->id, 'name' => 'HQ A', 'code' => 'HQA', 'status' => 'active']);
        $locB = Location::create(['tenant_id' => $this->tenantB->id, 'company_id' => $compB->id, 'name' => 'HQ B', 'code' => 'HQB', 'status' => 'active']);

        $statusA = AssetStatus::create(['tenant_id' => $this->tenantA->id, 'name' => 'Available A', 'code' => 'AVAILABLE', 'color' => '#16A34A']);
        $statusB = AssetStatus::create(['tenant_id' => $this->tenantB->id, 'name' => 'Available B', 'code' => 'AVAILABLE', 'color' => '#16A34A']);

        $this->supplierB = Supplier::create(['tenant_id' => $this->tenantB->id, 'name' => 'Beta Vendor', 'code' => 'BV', 'status' => 'active']);

        $this->assetA = EquipmentAsset::create([
            'tenant_id' => $this->tenantA->id,
            'asset_tag' => 'AF-A-0001',
            'serial_number' => 'SN-ALPHA-001',
            'name' => 'Alpha Laptop',
            'category_id' => $catA->id,
            'location_id' => $locA->id,
            'status_id' => $statusA->id,
            'purchase_cost' => 1200,
        ]);

        $this->assetB = EquipmentAsset::create([
            'tenant_id' => $this->tenantB->id,
            'asset_tag' => 'AF-B-0001',
            'serial_number' => 'SN-BETA-001',
            'name' => 'Beta Secret Server',
            'category_id' => $catB->id,
            'location_id' => $locB->id,
            'status_id' => $statusB->id,
            'purchase_cost' => 5000,
        ]);

        $this->warrantyB = Warranty::create([
            'tenant_id' => $this->tenantB->id,
            'equipment_asset_id' => $this->assetB->id,
            'provider_name' => 'Beta Care',
            'policy_number' => 'POL-BETA-1',
            'warranty_type' => 'extended',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);

        $this->repairB = RepairJob::create([
            'tenant_id' => $this->tenantB->id,
            'equipment_asset_id' => $this->assetB->id,
            'job_number' => 'REP-2026-9999',
            'fault_description' => 'Beta PSU failure',
            'repair_center' => 'internal',
            'repair_status' => 'in_repair',
            'cost' => 450,
            'start_date' => now()->toDateString(),
        ]);

        activity('auth')
            ->causedBy($this->adminB)
            ->event('login')
            ->withProperties(['tenant_id' => $this->tenantB->id])
            ->log('Beta admin login');
    }

    public function test_tenant_a_cannot_see_or_mutate_tenant_b_resources(): void
    {
        $token = $this->adminA->createToken('test')->plainTextToken;
        $headers = ['Authorization' => "Bearer {$token}"];

        // Index endpoints must exclude Tenant B records
        $assetsResp = $this->withHeaders($headers)->getJson('/api/v1/assets')->assertOk();
        $assetTags = collect($assetsResp->json('data.data'))->pluck('asset_tag')->all();
        $this->assertEquals(['AF-A-0001'], $assetTags);

        $usersResp = $this->withHeaders($headers)->getJson('/api/v1/users')->assertOk();
        $userEmails = collect($usersResp->json('data.data'))->pluck('email')->all();
        $this->assertEquals(['admin.a@alpha.test'], $userEmails);

        $deptsResp = $this->withHeaders($headers)->getJson('/api/v1/departments')->assertOk();
        $this->assertEmpty($deptsResp->json('data'));

        $suppliersResp = $this->withHeaders($headers)->getJson('/api/v1/suppliers')->assertOk();
        $this->assertEmpty($suppliersResp->json('data'));

        // Direct ID access to Tenant B entities must return 404
        $this->withHeaders($headers)->getJson("/api/v1/assets/{$this->assetB->id}")->assertNotFound();
        $this->withHeaders($headers)->putJson("/api/v1/assets/{$this->assetB->id}", ['name' => 'Hijacked'])->assertNotFound();
        $this->withHeaders($headers)->deleteJson("/api/v1/assets/{$this->assetB->id}")->assertNotFound();

        $this->withHeaders($headers)->getJson("/api/v1/warranties/{$this->warrantyB->id}")->assertNotFound();
        $this->withHeaders($headers)->getJson("/api/v1/repairs/{$this->repairB->id}")->assertNotFound();
        $this->withHeaders($headers)->getJson("/api/v1/users/{$this->adminB->id}")->assertNotFound();
        $this->withHeaders($headers)->getJson("/api/v1/departments/{$this->deptB->id}")->assertNotFound();
        $this->withHeaders($headers)->getJson("/api/v1/suppliers/{$this->supplierB->id}")->assertNotFound();

        // Dashboard, Reports, and Audit logs must only reflect Tenant A
        $dash = $this->withHeaders($headers)->getJson('/api/v1/dashboard/summary')->assertOk();
        $this->assertEquals(1, $dash->json('data.kpis.total_assets'));
        $this->assertEquals('1200.00', $dash->json('data.kpis.total_valuation'));

        $audit = $this->withHeaders($headers)->getJson('/api/v1/audit-logs?log_name=auth')->assertOk();
        $this->assertCount(0, $audit->json('data'));
    }

    public function test_x_tenant_id_header_spoofing_cannot_bypass_authenticated_tenant_isolation(): void
    {
        $token = $this->adminA->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Tenant-ID' => $this->tenantB->id,
        ])->getJson("/api/v1/assets/{$this->assetB->id}");

        $response->assertNotFound();
    }
}
