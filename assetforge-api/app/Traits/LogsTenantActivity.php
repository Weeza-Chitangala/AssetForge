<?php

namespace App\Traits;

use App\Models\Company;
use App\Models\User;
use App\Services\RoleHierarchyService;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Contracts\Activity;

/**
 * Stamps tenant ownership and platform cross-tenant metadata onto Spatie activity logs.
 *
 * Ensures every model activity record carries explicit tenant context even when triggered
 * outside HTTP requests or by a Super User operating across tenant boundaries.
 */
trait LogsTenantActivity
{
    /**
     * Enrich each recorded activity entry with tenant_id and cross-tenant causer metadata.
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $properties = $activity->properties ? $activity->properties->toArray() : [];
        $subjectTenantId = $this->resolveActivityTenantId();

        if ($subjectTenantId !== null) {
            $properties['tenant_id'] = $subjectTenantId;
        }

        $causer = $activity->causer ?? Auth::user();
        $isSuperUser = $causer instanceof User && $causer->hasRole(RoleHierarchyService::ROLE_SUPER_USER);

        if ($isSuperUser) {
            $properties['causer_is_super_user'] = true;

            if (
                $subjectTenantId !== null
                && $causer->tenant_id !== null
                && (string) $causer->tenant_id !== (string) $subjectTenantId
            ) {
                $properties['scope'] = 'platform_cross_tenant';
                $properties['causer_tenant_id'] = (string) $causer->tenant_id;
            }
        }

        $activity->properties = collect($properties);
    }

    /**
     * Resolve the owning tenant ID for the subject model without triggering tenant-scope blindspots.
     */
    protected function resolveActivityTenantId(): ?string
    {
        $directTenantId = $this->getAttribute('tenant_id');
        if ($directTenantId !== null && $directTenantId !== '') {
            return (string) $directTenantId;
        }

        if (method_exists($this, 'organization') && $this->getAttribute('organization_id')) {
            $tenantId = $this->organization()->withoutGlobalScopes()->value('tenant_id');
            if ($tenantId !== null && $tenantId !== '') {
                return (string) $tenantId;
            }
        }

        if (method_exists($this, 'company') && $this->getAttribute('company_id')) {
            $tenantId = Company::withoutGlobalScopes()
                ->whereKey($this->getAttribute('company_id'))
                ->join('organizations', 'companies.organization_id', '=', 'organizations.id')
                ->value('organizations.tenant_id');
            if ($tenantId !== null && $tenantId !== '') {
                return (string) $tenantId;
            }
        }

        if (app()->bound('current_tenant_id') && app('current_tenant_id')) {
            return (string) app('current_tenant_id');
        }

        $authTenantId = Auth::user()?->tenant_id;

        return $authTenantId !== null && $authTenantId !== '' ? (string) $authTenantId : null;
    }
}
