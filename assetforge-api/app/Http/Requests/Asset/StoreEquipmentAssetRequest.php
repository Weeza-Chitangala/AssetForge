<?php

namespace App\Http\Requests\Asset;

use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Organization;
use App\Models\User;
use App\Rules\TenantRecordExists;
use App\Services\AssetCustodyService;
use App\Services\TenantHierarchyValidator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEquipmentAssetRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    protected array $blockedCustodyFields = [];

    public function authorize(): bool
    {
        $actor = $this->user();
        if (! $actor) {
            return false;
        }

        $this->blockedCustodyFields = app(AssetCustodyService::class)
            ->unauthorizedCustodyFields($actor, $this->all());

        return $this->blockedCustodyFields === [];
    }

    protected function failedAuthorization(): void
    {
        if ($this->blockedCustodyFields !== []) {
            throw new AuthorizationException(
                'You do not have permission (' . AssetCustodyService::PERMISSION_TRANSFER . ') to assign asset custody during registration: '
                . implode(', ', $this->blockedCustodyFields) . '.'
            );
        }

        parent::failedAuthorization();
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'asset_tag' => ['required', 'string', 'max:100', 'unique:equipment_assets,asset_tag'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:equipment_assets,serial_number'],
            'name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'uuid', new TenantRecordExists(Category::class, $tenantId)],
            'manufacturer_id' => ['nullable', 'uuid', new TenantRecordExists(Manufacturer::class, $tenantId)],
            'asset_model_id' => ['nullable', 'uuid', new TenantRecordExists(AssetModel::class, $tenantId)],
            'location_id' => ['nullable', 'uuid', new TenantRecordExists(Location::class, $tenantId)],
            'status_id' => ['nullable', 'uuid', new TenantRecordExists(AssetStatus::class, $tenantId)],
            'organization_id' => ['nullable', 'uuid', new TenantRecordExists(Organization::class, $tenantId)],
            'company_id' => ['nullable', 'uuid', new TenantRecordExists(Company::class, $tenantId)],
            'department_id' => ['nullable', 'uuid', new TenantRecordExists(Department::class, $tenantId)],
            'assigned_to_user_id' => ['nullable', 'integer', new TenantRecordExists(User::class, $tenantId)],
            'assigned_date' => ['nullable', 'date'],
            'operating_system' => ['nullable', 'string', 'max:100'],
            'processor' => ['nullable', 'string', 'max:100'],
            'ram' => ['nullable', 'string', 'max:50'],
            'storage' => ['nullable', 'string', 'max:50'],
            'mac_address' => ['nullable', 'string', 'max:50'],
            'ip_address' => ['nullable', 'ip'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'warranty_expiry_date' => ['nullable', 'date'],
            'condition' => ['nullable', 'string', 'in:new,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hierarchy = app(TenantHierarchyValidator::class);
            $ruleErrors = $validator->errors()->keys();

            $hierarchy->validateOrganizationHierarchy(
                $validator,
                $this->input('organization_id'),
                $this->input('company_id'),
                $this->input('department_id'),
                null,
                $ruleErrors,
            );

            $hierarchy->validateAssetCatalogHierarchy(
                $validator,
                $this->input('asset_model_id'),
                $this->input('manufacturer_id'),
                $this->input('category_id'),
                $ruleErrors,
            );

            $hierarchy->validateLocationCompany(
                $validator,
                $this->input('location_id'),
                $this->input('company_id'),
                $ruleErrors,
            );
        });
    }
}