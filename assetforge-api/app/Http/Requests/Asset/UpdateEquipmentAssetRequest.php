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

class UpdateEquipmentAssetRequest extends FormRequest
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
            ->unauthorizedCustodyFields($actor, $this->all(), $this->route('asset'));

        return $this->blockedCustodyFields === [];
    }

    protected function failedAuthorization(): void
    {
        if ($this->blockedCustodyFields !== []) {
            throw new AuthorizationException(
                'You do not have permission (' . AssetCustodyService::PERMISSION_TRANSFER . ') to modify custody fields: '
                . implode(', ', $this->blockedCustodyFields) . '.'
            );
        }

        parent::failedAuthorization();
    }

    public function rules(): array
    {
        $assetId = $this->route('asset') ? $this->route('asset')->id : null;
        $tenantId = $this->user()?->tenant_id;

        return [
            'asset_tag' => ['sometimes', 'required', 'string', 'max:100', 'unique:equipment_assets,asset_tag,' . $assetId],
            'serial_number' => ['sometimes', 'required', 'string', 'max:100', 'unique:equipment_assets,serial_number,' . $assetId],
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
            $asset = $this->route('asset');
            $resolve = fn (string $key): ?string => $this->has($key) ? $this->input($key) : $asset?->{$key};
            $hierarchy = app(TenantHierarchyValidator::class);
            $ruleErrors = $validator->errors()->keys();

            if ($this->hasAny(['organization_id', 'company_id', 'department_id'])) {
                $hierarchy->validateOrganizationHierarchy(
                    $validator,
                    $resolve('organization_id'),
                    $resolve('company_id'),
                    $resolve('department_id'),
                    null,
                    $ruleErrors,
                );
            }

            if ($this->hasAny(['asset_model_id', 'manufacturer_id', 'category_id'])) {
                $hierarchy->validateAssetCatalogHierarchy(
                    $validator,
                    $resolve('asset_model_id'),
                    $resolve('manufacturer_id'),
                    $resolve('category_id'),
                    $ruleErrors,
                );
            }

            if ($this->hasAny(['location_id', 'company_id'])) {
                $hierarchy->validateLocationCompany(
                    $validator,
                    $resolve('location_id'),
                    $resolve('company_id'),
                    $ruleErrors,
                );
            }
        });
    }
}