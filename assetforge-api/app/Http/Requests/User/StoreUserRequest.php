<?php

namespace App\Http\Requests\User;

use App\Models\Company;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Team;
use App\Rules\TenantRecordExists;
use App\Services\RoleHierarchyService;
use App\Services\TenantHierarchyValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,inactive,suspended'],
            'organization_id' => ['nullable', 'uuid', new TenantRecordExists(Organization::class, $tenantId)],
            'company_id' => ['nullable', 'uuid', new TenantRecordExists(Company::class, $tenantId)],
            'department_id' => ['nullable', 'uuid', new TenantRecordExists(Department::class, $tenantId)],
            'team_id' => ['nullable', 'uuid', new TenantRecordExists(Team::class, $tenantId)],
            'roles' => ['nullable', 'array'],
            'roles.*' => [
                'string',
                Rule::in(RoleHierarchyService::ALL_ROLES),
                Rule::exists('roles', 'name')->where('guard_name', RoleHierarchyService::GUARD),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            app(TenantHierarchyValidator::class)->validateOrganizationHierarchy(
                $validator,
                $this->input('organization_id'),
                $this->input('company_id'),
                $this->input('department_id'),
                $this->input('team_id'),
            );
        });
    }
}