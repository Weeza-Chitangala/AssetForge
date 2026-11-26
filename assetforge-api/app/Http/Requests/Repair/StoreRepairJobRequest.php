<?php

namespace App\Http\Requests\Repair;

use App\Models\EquipmentAsset;
use App\Models\FaultType;
use App\Models\User;
use App\Rules\TenantRecordExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreRepairJobRequest extends FormRequest
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
            'fault_type_id' => ['nullable', 'uuid', new TenantRecordExists(FaultType::class, $tenantId)],
            'fault_description' => ['required', 'string'],
            'technician_id' => ['nullable', 'integer', new TenantRecordExists(User::class, $tenantId)],
            'repair_center' => ['nullable', 'string', 'in:internal,external_vendor,warranty_oem'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'diagnostic_notes' => ['nullable', 'string'],
        ];
    }
}