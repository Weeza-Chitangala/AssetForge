<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class StageDAuthSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $activeTenant;
    protected Tenant $suspendedTenant;
    protected User $superUser;
    protected User $adminUser;
    protected User $activeOpUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->activeTenant = Tenant::create([
            'name' => 'Active Org',
            'slug' => 'active-org',
            'email' => 'active@tenant.test',
            'status' => 'active',
        ]);

        $this->suspendedTenant = Tenant::create([
            'name' => 'Suspended Org',
            'slug' => 'suspended-org',
            'email' => 'suspended@tenant.test',
            'status' => 'suspended',
        ]);

        $this->superUser = User::create([
            'name' => 'Platform Super',
            'email' => 'super@assetforge.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->suspendedTenant->id,
            'status' => 'active',
        ]);
        $this->superUser->assignRole('Super User');

        $this->adminUser = User::create([
            'name' => 'Tenant Admin',
            'email' => 'admin@active.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->activeTenant->id,
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('Administrator');

        $this->activeOpUser = User::create([
            'name' => 'Active Technician',
            'email' => 'tech@active.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->activeTenant->id,
            'status' => 'active',
        ]);
        $this->activeOpUser->assignRole('Technician');
    }

    protected function authHeaders(string $plainTextToken): array
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');

        return [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$plainTextToken}",
        ];
    }

    public function test_inactive_and_suspended_users_and_suspended_tenants_cannot_login_or_retain_sessions(): void
    {
        $inactiveUser = User::create([
            'name' => 'Inactive Staff',
            'email' => 'inactive@active.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->activeTenant->id,
            'status' => 'inactive',
        ]);
        $inactiveUser->assignRole('Technician');

        $suspendedUser = User::create([
            'name' => 'Suspended Staff',
            'email' => 'suspended@active.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->activeTenant->id,
            'status' => 'suspended',
        ]);
        $suspendedUser->assignRole('Technician');

        $suspendedTenantUser = User::create([
            'name' => 'Frozen Tenant Staff',
            'email' => 'staff@suspended.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->suspendedTenant->id,
            'status' => 'active',
        ]);
        $suspendedTenantUser->assignRole('Asset Manager');

        // Login rejected (403) and zero Sanctum tokens created for all 3 cases
        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@active.test',
            'password' => 'Password123!',
        ])->assertForbidden();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@active.test',
            'password' => 'Password123!',
        ])->assertForbidden();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'staff@suspended.test',
            'password' => 'Password123!',
        ])->assertForbidden();

        $this->assertSame(0, $inactiveUser->tokens()->count());
        $this->assertSame(0, $suspendedUser->tokens()->count());
        $this->assertSame(0, $suspendedTenantUser->tokens()->count());

        // Existing token for a user whose tenant later becomes suspended is revoked immediately by TenantObserver
        $tokenBeforeSuspension = $this->activeOpUser->createToken('pre-suspend')->plainTextToken;
        $this->assertSame(1, $this->activeOpUser->tokens()->count());
        $this->activeTenant->update(['status' => 'suspended']);

        $this->assertSame(0, $this->activeOpUser->tokens()->count());
        $this->withHeaders($this->authHeaders($tokenBeforeSuspension))
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        // Ordinary user in an inactive tenant or soft-deleted tenant cannot log in, and tokens are revoked
        $inactiveTenant = Tenant::create([
            'name' => 'Inactive Org',
            'slug' => 'inactive-org',
            'email' => 'inactive-org@tenant.test',
            'status' => 'active',
        ]);
        $inactiveTenantUser = User::create([
            'name' => 'Inactive Tenant Staff',
            'email' => 'staff@inactive-tenant.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $inactiveTenant->id,
            'status' => 'active',
        ]);
        $inactiveTenantUser->assignRole('Technician');
        $inactiveTenantToken = $inactiveTenantUser->createToken('t-inactive')->plainTextToken;
        $inactiveTenant->update(['status' => 'inactive']);

        $this->assertSame(0, $inactiveTenantUser->tokens()->count());
        $this->withHeaders($this->authHeaders($inactiveTenantToken))->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', [
            'email' => 'staff@inactive-tenant.test',
            'password' => 'Password123!',
        ])->assertForbidden();

        // Soft-deleted tenant blocks login and revokes member tokens
        $deletedTenant = Tenant::create([
            'name' => 'Deleted Org',
            'slug' => 'deleted-org',
            'email' => 'deleted-org@tenant.test',
            'status' => 'active',
        ]);
        $deletedTenantUser = User::create([
            'name' => 'Deleted Tenant Staff',
            'email' => 'staff@deleted-tenant.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $deletedTenant->id,
            'status' => 'active',
        ]);
        $deletedTenantUser->assignRole('Technician');
        $deletedTenantToken = $deletedTenantUser->createToken('t-deleted')->plainTextToken;
        $deletedTenant->delete();

        $this->assertSame(0, $deletedTenantUser->tokens()->count());
        $this->withHeaders($this->authHeaders($deletedTenantToken))->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', [
            'email' => 'staff@deleted-tenant.test',
            'password' => 'Password123!',
        ])->assertForbidden();

        // Platform Super User remains able to authenticate even when assigned tenant is suspended,
        // and TenantObserver does not revoke the active Super User's token when tenant suspends.
        $superLogin = $this->postJson('/api/v1/auth/login', [
            'email' => 'super@assetforge.test',
            'password' => 'Password123!',
        ])->assertOk();

        $this->withHeaders($this->authHeaders($superLogin->json('data.token')))
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'super@assetforge.test');

        // Inactive Super User IS blocked from login and existing tokens are immediately revoked
        $this->superUser->update(['status' => 'inactive']);
        $this->assertSame(0, $this->superUser->tokens()->count());

        $this->postJson('/api/v1/auth/login', [
            'email' => 'super@assetforge.test',
            'password' => 'Password123!',
        ])->assertForbidden();

        $this->withHeaders($this->authHeaders($superLogin->json('data.token')))
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        // Reactivate Super User and confirm tenant-less Super User access still enforces controller tenant rules
        $this->superUser->update(['status' => 'active']);
        $this->superUser->tenant_id = null;
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('current_tenant_id');
        $this->actingAs($this->superUser, 'sanctum')
            ->postJson('/api/v1/assets', [
                'asset_tag' => 'AF-SU-NOTENANT-1',
                'serial_number' => 'SN-SU-NOTENANT-1',
            ])->assertForbidden();
    }

    public function test_tokens_are_revoked_when_user_is_suspended_password_changes_or_account_deleted(): void
    {
        $adminToken = $this->adminUser->createToken('admin-session')->plainTextToken;

        // 1. Suspension revokes all active tokens
        $techToken1 = $this->activeOpUser->createToken('device-1')->plainTextToken;
        $techToken2 = $this->activeOpUser->createToken('device-2')->plainTextToken;
        $this->assertSame(2, $this->activeOpUser->tokens()->count());

        $this->withHeaders($this->authHeaders($adminToken))
            ->putJson("/api/v1/users/{$this->activeOpUser->id}", ['status' => 'suspended'])
            ->assertOk();

        $this->assertSame(0, $this->activeOpUser->tokens()->count());
        $this->withHeaders($this->authHeaders($techToken1))->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withHeaders($this->authHeaders($techToken2))->getJson('/api/v1/auth/me')->assertUnauthorized();

        // Re-activate user and issue a fresh token, then test password reset token revocation via API
        $this->withHeaders($this->authHeaders($adminToken))
            ->putJson("/api/v1/users/{$this->activeOpUser->id}", ['status' => 'active'])
            ->assertOk();

        $freshToken = $this->activeOpUser->createToken('device-3')->plainTextToken;
        $this->withHeaders($this->authHeaders($freshToken))->getJson('/api/v1/auth/me')->assertOk();

        $this->withHeaders($this->authHeaders($adminToken))
            ->putJson("/api/v1/users/{$this->activeOpUser->id}", ['password' => 'NewSecretPass99!'])
            ->assertOk();

        $this->assertSame(0, $this->activeOpUser->tokens()->count());
        $this->withHeaders($this->authHeaders($freshToken))->getJson('/api/v1/auth/me')->assertUnauthorized();

        // 2. Direct Eloquent model password & status updates also trigger central UserObserver revocation
        $directToken = $this->activeOpUser->createToken('device-direct')->plainTextToken;
        $this->assertSame(1, $this->activeOpUser->tokens()->count());
        $this->activeOpUser->update(['password' => Hash::make('DirectObserverPass99!')]);
        $this->assertSame(0, $this->activeOpUser->tokens()->count());
        $this->withHeaders($this->authHeaders($directToken))->getJson('/api/v1/auth/me')->assertUnauthorized();

        $directToken2 = $this->activeOpUser->createToken('device-direct-2')->plainTextToken;
        $this->activeOpUser->update(['status' => 'inactive']);
        $this->assertSame(0, $this->activeOpUser->tokens()->count());
        $this->withHeaders($this->authHeaders($directToken2))->getJson('/api/v1/auth/me')->assertUnauthorized();

        $this->activeOpUser->update(['status' => 'active']);

        // 3. Account deletion revokes all tokens too
        $preDeleteToken = $this->activeOpUser->createToken('device-4')->plainTextToken;
        $this->assertSame(1, $this->activeOpUser->tokens()->count());

        $this->withHeaders($this->authHeaders($adminToken))
            ->deleteJson("/api/v1/users/{$this->activeOpUser->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $this->activeOpUser->id]);
        $this->assertSame(0, $this->activeOpUser->tokens()->count());
        $this->withHeaders($this->authHeaders($preDeleteToken))->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_login_rate_limiter_enforces_email_and_ip_dimensions_and_logs_failures_safely(): void
    {
        RateLimiter::clear('login');

        // 1. Legitimate user establishes a known trusted client IP
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.50'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@active.test',
                'password' => 'Password123!',
            ])->assertOk();

        // 2. Five failed attempts from same IP+email return 422, 6th returns 429
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
                ->postJson('/api/v1/auth/login', [
                    'email' => 'tech@active.test',
                    'password' => 'SecretWrongPass999!',
                ])->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'tech@active.test',
                'password' => 'SecretWrongPass999!',
            ])->assertStatus(429);

        // 3. Distributed credential stuffing across unknown IPs blocks the botnet on attempt 11 (429),
        // WITHOUT locking out the legitimate user on their known trusted IP (200).
        for ($ip = 1; $ip <= 10; $ip++) {
            $this->withServerVariables(['REMOTE_ADDR' => "203.0.113.{$ip}"])
                ->postJson('/api/v1/auth/login', [
                    'email' => 'admin@active.test',
                    'password' => 'SecretWrongPass999!',
                ])->assertStatus(422);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.99'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@active.test',
                'password' => 'SecretWrongPass999!',
            ])->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.50'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'admin@active.test',
                'password' => 'Password123!',
            ])->assertOk();

        // 4. Unknown email probe is audited without leaking secrets
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'unknown.probe@example.test',
                'password' => 'SecretWrongPass999!',
            ])->assertStatus(422);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'event' => 'login_failed',
            'causer_id' => null,
        ]);

        // Trigger inactive-user & suspended-tenant failure audits to confirm complete coverage
        $this->activeOpUser->update(['status' => 'inactive']);
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.88'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'tech@active.test',
                'password' => 'Password123!',
            ])->assertForbidden();

        $suspendedStaff = User::create([
            'name' => 'Suspended Audit Probe',
            'email' => 'audit.susp@suspended.test',
            'password' => Hash::make('Password123!'),
            'tenant_id' => $this->suspendedTenant->id,
            'status' => 'active',
        ]);
        $suspendedStaff->assignRole('Technician');
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.89'])
            ->postJson('/api/v1/auth/login', [
                'email' => 'audit.susp@suspended.test',
                'password' => 'Password123!',
            ])->assertForbidden();

        $failedLogs = Activity::where('log_name', 'auth')
            ->where('event', 'login_failed')
            ->get();

        $reasons = $failedLogs->map(fn ($l) => $l->properties['reason'] ?? null)->unique()->all();
        $this->assertContains('invalid_credentials', $reasons);
        $this->assertContains('distributed_rate_limited', $reasons);
        $this->assertContains('user_inactive', $reasons);
        $this->assertContains('tenant_suspended', $reasons);

        foreach ($failedLogs as $log) {
            $serialized = json_encode($log->toArray(), JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString('SecretWrongPass999!', $serialized);
            $this->assertStringNotContainsString('Password123!', $serialized);
            $this->assertStringNotContainsString('password', strtolower((string) $log->description));
        }
    }

    public function test_logout_all_revokes_all_active_sessions_and_cors_drops_x_tenant_id(): void
    {
        $t1 = $this->activeOpUser->createToken('web')->plainTextToken;
        $t2 = $this->activeOpUser->createToken('mobile')->plainTextToken;
        $t3 = $this->activeOpUser->createToken('cli')->plainTextToken;

        $this->assertSame(3, $this->activeOpUser->tokens()->count());

        $this->withHeaders($this->authHeaders($t1))
            ->postJson('/api/v1/auth/logout-all')
            ->assertOk()
            ->assertJsonPath('data.revoked_tokens', 3);

        $this->assertSame(0, $this->activeOpUser->tokens()->count());

        foreach ([$t1, $t2, $t3] as $token) {
            $this->withHeaders($this->authHeaders($token))
                ->getJson('/api/v1/auth/me')
                ->assertUnauthorized();
        }

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'event' => 'logout_all',
            'causer_id' => $this->activeOpUser->id,
        ]);

        // CORS no longer advertises unused X-Tenant-ID header
        $this->assertNotContains('X-Tenant-ID', config('cors.allowed_headers', []));
    }
}
