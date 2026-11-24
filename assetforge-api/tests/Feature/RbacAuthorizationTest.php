<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tenant = Tenant::create([
            'name' => 'RBAC Corp',
            'slug' => 'rbac-corp',
            'email' => 'rbac@tenant.test',
            'status' => 'active',
        ]);
    }

    protected function userWithRole(string $role): User
    {
        $user = User::create([
            'name' => $role . ' User',
            'email' => strtolower(str_replace(' ', '.', $role)) . '@rbac.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->tenant->id,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_administrator_and_it_administrator_have_full_access(): void
    {
        foreach (['Super Administrator', 'IT Administrator'] as $role) {
            $user = $this->userWithRole($role);
            $headers = ['Authorization' => 'Bearer ' . $user->createToken('test')->plainTextToken];

            $this->withHeaders($headers)->getJson('/api/v1/dashboard/summary')->assertOk();
            $this->withHeaders($headers)->getJson('/api/v1/users')->assertOk();
            $this->withHeaders($headers)->getJson('/api/v1/reports/inventory')->assertOk();
            $this->withHeaders($headers)->getJson('/api/v1/audit-logs')->assertOk();
            $this->withHeaders($headers)->getJson('/api/v1/test-permission')->assertOk();
            $this->withHeaders($headers)->postJson('/api/v1/categories', [
                'name' => 'Category by ' . $role,
                'code' => substr(md5($role), 0, 5),
            ])->assertCreated();
        }
    }

    public function test_asset_manager_can_manage_assets_and_reports_but_not_settings_or_create_users(): void
    {
        $manager = $this->userWithRole('Asset Manager');
        $headers = ['Authorization' => 'Bearer ' . $manager->createToken('test')->plainTextToken];

        $this->withHeaders($headers)->getJson('/api/v1/dashboard/summary')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/assets')->assertOk();
        $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-MGR-001',
            'serial_number' => 'SN-MGR-001',
            'name' => 'Manager Laptop',
        ])->assertCreated();
        $this->withHeaders($headers)->getJson('/api/v1/reports/inventory')->assertOk();

        $this->withHeaders($headers)->postJson('/api/v1/users', [
            'name' => 'Blocked',
            'email' => 'blocked@rbac.test',
            'password' => 'Password123!',
        ])->assertForbidden();
        $this->withHeaders($headers)->postJson('/api/v1/categories', ['name' => 'Blocked Cat'])->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/test-permission')->assertForbidden();
    }

    public function test_technician_can_handle_repairs_and_update_assets_but_not_reports_or_delete_assets(): void
    {
        $tech = $this->userWithRole('Technician');
        $headers = ['Authorization' => 'Bearer ' . $tech->createToken('test')->plainTextToken];

        $this->withHeaders($headers)->getJson('/api/v1/dashboard/summary')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/assets')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/repairs')->assertOk();

        $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-TECH-001',
            'serial_number' => 'SN-TECH-001',
        ])->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/reports/inventory')->assertForbidden();
        $this->withHeaders($headers)->getJson('/api/v1/audit-logs')->assertForbidden();
        $this->withHeaders($headers)->postJson('/api/v1/suppliers', ['name' => 'Blocked'])->assertForbidden();
    }

    public function test_auditor_has_read_only_visibility_across_inventory_repairs_reports_and_audit_logs(): void
    {
        $auditor = $this->userWithRole('Auditor');
        $headers = ['Authorization' => 'Bearer ' . $auditor->createToken('test')->plainTextToken];

        $this->withHeaders($headers)->getJson('/api/v1/dashboard/summary')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/assets')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/repairs')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/reports/inventory')->assertOk();
        $this->withHeaders($headers)->getJson('/api/v1/audit-logs')->assertOk();

        $this->withHeaders($headers)->postJson('/api/v1/assets', [
            'asset_tag' => 'AF-AUD-001',
            'serial_number' => 'SN-AUD-001',
        ])->assertForbidden();
        $this->withHeaders($headers)->postJson('/api/v1/repairs', [
            'equipment_asset_id' => '00000000-0000-0000-0000-000000000000',
            'fault_description' => 'Read only',
        ])->assertForbidden();
        $this->withHeaders($headers)->postJson('/api/v1/categories', ['name' => 'Blocked'])->assertForbidden();
    }
}
