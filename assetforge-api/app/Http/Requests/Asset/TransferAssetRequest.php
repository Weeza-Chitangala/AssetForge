<?php

namespace App\Http\Requests\Asset;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Rules\TenantRecordExists;
use App\Services\TenantHierarchyValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TransferAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'location_id' => ['nullable', 'uuid', new TenantRecordExists(Location::class, $tenantId)],
            'department_id' => ['nullable', 'uuid', new TenantRecordExists(Department::class, $tenantId)],
            'company_id' => ['nullable', 'uuid', new TenantRecordExists(Company::class, $tenantId)],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->hasAny(['company_id', 'department_id', 'location_id'])) {
                return;
            }

            $asset = $this->route('asset');
            $resolve = fn (string $key): ?string => $this->has($key) ? $this->input($key) : $asset?->{$key};
            $hierarchy = app(TenantHierarchyValidator::class);
            $ruleErrors = $validator->errors()->keys();

            $hierarchy->validateOrganizationHierarchy(
                $validator,
                $asset?->organization_id,
                $resolve('company_id'),
                $resolve('department_id'),
                null,
                $ruleErrors,
            );

            $hierarchy->validateLocationCompany(
                $validator,
                $resolve('location_id'),
                $resolve('company_id'),
                $ruleErrors,
            );
        });
    }
}