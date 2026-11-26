<?php

namespace App\Http\Requests\Warranty;

use App\Models\EquipmentAsset;
use App\Rules\TenantRecordExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarrantyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'equipment_asset_id' => ['required', 'uuid', new TenantRecordExists(EquipmentAsset::class, $tenantId)],
            'provider_name' => ['required', 'string', 'max:255'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'warranty_type' => ['nullable', 'string', 'in:standard,extended,accidental_damage,onsite'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'service_level' => ['nullable', 'string', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'support_email' => ['nullable', 'email', 'max:100'],
            'support_url' => ['nullable', 'url', 'max:255'],
            'terms_and_conditions' => ['nullable', 'string'],
        ];
    }
}