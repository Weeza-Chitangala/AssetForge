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

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;
        $tenantId = $this->user()?->tenant_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['nullable', 'string', 'min:8'],
            'employee_number' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive,suspended'],
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
            if (! $this->hasAny(['organization_id', 'company_id', 'department_id', 'team_id'])) {
                return;
            }

            $targetUser = $this->route('user');
            $resolve = fn (string $key): ?string => $this->has($key) ? $this->input($key) : $targetUser?->{$key};

            app(TenantHierarchyValidator::class)->validateOrganizationHierarchy(
                $validator,
                $resolve('organization_id'),
                $resolve('company_id'),
                $resolve('department_id'),
                $resolve('team_id'),
            );
        });
    }
}