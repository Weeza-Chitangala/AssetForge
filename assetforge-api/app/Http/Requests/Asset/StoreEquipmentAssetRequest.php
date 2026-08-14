<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_tag' => ['required', 'string', 'max:100', 'unique:equipment_assets,asset_tag'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:equipment_assets,serial_number'],
            'name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'manufacturer_id' => ['nullable', 'uuid', 'exists:manufacturers,id'],
            'asset_model_id' => ['nullable', 'uuid', 'exists:asset_models,id'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
            'status_id' => ['nullable', 'uuid', 'exists:asset_statuses,id'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
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
}