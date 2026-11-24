<?php

namespace App\Http\Requests\Repair;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepairJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_asset_id' => ['required', 'uuid', 'exists:equipment_assets,id'],
            'fault_type_id' => ['nullable', 'uuid', 'exists:fault_types,id'],
            'fault_description' => ['required', 'string'],
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'repair_center' => ['nullable', 'string', 'in:internal,external_vendor,warranty_oem'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'diagnostic_notes' => ['nullable', 'string'],
        ];
    }
}