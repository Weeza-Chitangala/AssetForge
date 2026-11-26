<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Company;
use App\Models\EquipmentAsset;
use App\Models\Location;
use App\Models\Organization;
use App\Models\RepairJob;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warranty;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportingAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Company $company;
    protected User $admin;
    protected Category $category;
    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tenant = Tenant::create(['name' => 'Report Corp', 'slug' => 'report-corp', 'email' => 'reports@tenant.test', 'status' => 'active']);
        $org = Organization::create(['tenant_id' => $this->tenant->id, 'name' => 'Report Org', 'code' => 'RO', 'status' => 'active']);
        $this->company = Company::create(['organization_id' => $org->id, 'name' => 'Report Co', 'code' => 'RC', 'status' => 'active']);

        $this->admin = User::create([
            'name' => 'Report Admin',
            'email' => 'report.admin@test.local',
            'password' => Hash::make('Admin@12345'),
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        $this->admin->assignRole('Super User');

        $this->category = Category::create(['tenant_id' => $this->tenant->id, 'name' => 'Laptops', 'code' => 'LAP', 'status' => 'active']);
        $this->location = Location::create(['tenant_id' => $this->tenant->id, 'company_id' => $this->company->id, 'name' => 'HQ', 'code' => 'HQ', 'status' => 'active']);

        $asset = EquipmentAsset::create([
            'tenant_id' => $this->tenant->id,
            'asset_tag' => 'AF-RPT-001',
            'serial_number' => 'SN-RPT-001',
            'name' => 'Report Laptop',
            'category_id' => $this->category->id,
            'location_id' => $this->location->id,
            'purchase_cost' => 1500,
            'purchase_date' => now()->subDays(10)->toDateString(),
        ]);

        Warranty::create([
            'tenant_id' => $this->tenant->id,
            'equipment_asset_id' => $asset->id,
            'provider_name' => 'HP Care',
            'policy_number' => 'HP-101',
            'warranty_type' => 'onsite',
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
        ]);

        RepairJob::create([
            'tenant_id' => $this->tenant->id,
            'equipment_asset_id' => $asset->id,
            'job_number' => 'REP-2026-0001',
            'fault_description' => 'Keyboard replacement',
            'repair_center' => 'internal',
            'repair_status' => 'completed',
            'cost' => 125.50,
            'start_date' => now()->subDays(3)->toDateString(),
            'completion_date' => now()->toDateString(),
        ]);
    }

    public function test_health_and_auth_login_audit_trail_and_me_payload(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $loginResp = $this->postJson('/api/v1/auth/login', [
            'email' => 'report.admin@test.local',
            'password' => 'Admin@12345',
        ])->assertOk();

        $token = $loginResp->json('data.token');
        $this->assertNotEmpty($token);
        $this->assertContains('Super User', $loginResp->json('data.user.roles'));
        $this->assertContains('reports.view', $loginResp->json('data.user.permissions'));

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'report.admin@test.local');

        $auditResp = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v1/audit-logs?log_name=auth')
            ->assertOk();
        $this->assertGreaterThanOrEqual(1, count($auditResp->json('data')));
    }

    public function test_reports_json_and_csv_xlsx_pdf_exports(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken];

        // Inventory JSON + exports
        $this->withHeaders($headers)
            ->getJson("/api/v1/reports/inventory?category_id={$this->category->id}&location_id={$this->location->id}")
            ->assertOk()
            ->assertJsonPath('data.summary.total_assets', 1)
            ->assertJsonPath('data.summary.total_valuation', '1500.00');

        $this->withHeaders($headers)->get('/api/v1/reports/inventory/export?format=csv')->assertOk();
        $this->withHeaders($headers)->get('/api/v1/reports/inventory/export?format=xlsx')->assertOk();
        $pdfInv = $this->withHeaders($headers)->get('/api/v1/reports/inventory/export?format=pdf')->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdfInv->headers->get('Content-Type'));

        // Warranties JSON + PDF
        $this->withHeaders($headers)
            ->getJson('/api/v1/reports/warranties?status=expiring_soon')
            ->assertOk()
            ->assertJsonPath('data.summary.expiring_soon', 1);
        $this->withHeaders($headers)->get('/api/v1/reports/warranties/export?format=pdf')->assertOk();

        // Repairs JSON + CSV
        $this->withHeaders($headers)
            ->getJson('/api/v1/reports/repairs?repair_status=completed')
            ->assertOk()
            ->assertJsonPath('data.summary.total_cost', '125.50');
        $this->withHeaders($headers)->get('/api/v1/reports/repairs/export?format=csv')->assertOk();
    }

    public function test_departments_and_suppliers_crud_endpoints(): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->admin->createToken('test')->plainTextToken];

        $deptCreate = $this->withHeaders($headers)->postJson('/api/v1/departments', [
            'company_id' => $this->company->id,
            'name' => 'Engineering',
            'code' => 'ENG',
            'status' => 'active',
        ])->assertCreated();

        $deptId = $deptCreate->json('data.id');
        $this->withHeaders($headers)->getJson('/api/v1/departments')->assertOk()->assertJsonCount(1, 'data');
        $this->withHeaders($headers)->putJson("/api/v1/departments/{$deptId}", [
            'company_id' => $this->company->id,
            'name' => 'Platform Engineering',
            'code' => 'PENG',
            'status' => 'active',
        ])->assertOk();
        $this->withHeaders($headers)->deleteJson("/api/v1/departments/{$deptId}")->assertOk();

        $supCreate = $this->withHeaders($headers)->postJson('/api/v1/suppliers', [
            'name' => 'TechDirect Ltd',
            'code' => 'TDL',
            'contact_person' => 'Alice Banda',
            'email' => 'sales@techdirect.test',
            'status' => 'active',
        ])->assertCreated();

        $supId = $supCreate->json('data.id');
        $this->withHeaders($headers)->getJson('/api/v1/suppliers')->assertOk()->assertJsonCount(1, 'data');
        $this->withHeaders($headers)->putJson("/api/v1/suppliers/{$supId}", [
            'name' => 'TechDirect Global',
            'code' => 'TDG',
            'status' => 'active',
        ])->assertOk();
        $this->withHeaders($headers)->deleteJson("/api/v1/suppliers/{$supId}")->assertOk();
    }
}
