<?php

namespace App\Observers;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AuthService;
use App\Services\RoleHierarchyService;

/**
 * TenantObserver
 *
 * Immediately revokes active Sanctum tokens for all tenant-bound users when a tenant
 * transitions to an inactive/suspended state or is deleted, while preserving platform
 * Super User sessions so platform operators cannot be locked out.
 */
class TenantObserver
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RoleHierarchyService $roleHierarchy,
    ) {
    }

    public function updated(Tenant $tenant): void
    {
        if (! $tenant->wasChanged('status') || $tenant->status === 'active') {
            return;
        }

        $this->revokeNonSuperUserTokens($tenant, 'tenant_' . ($tenant->status ?: 'inactive'));
    }

    public function deleting(Tenant $tenant): void
    {
        $this->revokeNonSuperUserTokens($tenant, 'tenant_deleted');
    }

    protected function revokeNonSuperUserTokens(Tenant $tenant, string $reason): void
    {
        $causer = request()?->user() ?? auth('sanctum')->user() ?? auth()->user();

        User::query()
            ->where('tenant_id', $tenant->id)
            ->get()
            ->each(function (User $member) use ($reason, $causer): void {
                if ($this->roleHierarchy->isSuperUser($member)) {
                    return;
                }

                $this->authService->revokeUserTokens(
                    $member,
                    $reason,
                    $causer instanceof User ? $causer : null
                );
            });
    }
}
