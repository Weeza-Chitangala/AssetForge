<?php

namespace App\Rules;

use App\Models\Company;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Scopes\TenantScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * TenantRecordExists
 *
 * Ensures a referenced foreign key belongs to the caller's tenant before persisting
 * or linking records, preventing cross-tenant foreign-key injection even on models
 * that do not use the global TenantScope (e.g., User, Company, Department, Team).
 */
class TenantRecordExists implements ValidationRule
{
    /**
     * @param  class-string  $modelClass  Target Eloquent model class
     * @param  string|null   $tenantId    Authenticated user's tenant UUID
     * @param  string        $column      Target lookup column (defaults to primary key 'id')
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly ?string $tenantId,
        private readonly string $column = 'id',
    ) {
    }

    /**
     * Validate that the supplied foreign key exists and belongs to the active tenant.
     *
     * @param  string  $attribute  Validated attribute name
     * @param  mixed   $value      Supplied identifier
     * @param  Closure(string): void  $fail  Validation failure callback
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->tenantId || ! is_scalar($value)) {
            $fail("The selected {$attribute} is invalid for the current tenant.");

            return;
        }

        // Guard key syntax before querying so PostgreSQL does not throw 22P02 on malformed UUID/int payloads.
        if ($this->column === 'id') {
            if ($this->modelClass === User::class) {
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $fail("The selected {$attribute} is invalid for the current tenant.");

                    return;
                }
            } elseif (! Str::isUuid((string) $value)) {
                $fail("The selected {$attribute} is invalid for the current tenant.");

                return;
            }
        }

        /** @var Builder $query */
        $query = ($this->modelClass)::withoutGlobalScope(TenantScope::class)
            ->where($this->column, $value);

        match ($this->modelClass) {
            Company::class => $query->whereHas('organization', function (Builder $q) {
                $q->withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenantId);
            }),
            Department::class => $query->whereHas('company.organization', function (Builder $q) {
                $q->withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenantId);
            }),
            Team::class => $query->whereHas('department.company.organization', function (Builder $q) {
                $q->withoutGlobalScope(TenantScope::class)->where('tenant_id', $this->tenantId);
            }),
            default => $query->where('tenant_id', $this->tenantId),
        };

        if (! $query->exists()) {
            $fail("The selected {$attribute} is invalid for the current tenant.");
        }
    }
}
