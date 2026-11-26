<?php

namespace App\Http\Requests\Repair;

use App\Models\FaultType;
use App\Models\User;
use App\Rules\TenantRecordExists;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRepairJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'fault_type_id' => ['nullable', 'uuid', new TenantRecordExists(FaultType::class, $tenantId)],
            'fault_description' => ['sometimes', 'required', 'string'],
            'technician_id' => ['nullable', 'integer', new TenantRecordExists(User::class, $tenantId)],
            'repair_center' => ['nullable', 'string', 'in:internal,external_vendor,warranty_oem'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'repair_status' => ['nullable', 'string', 'in:pending,diagnosing,in_repair,parts_on_order,completed,unrepairable'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'completion_date' => ['nullable', 'date'],
            'diagnostic_notes' => ['nullable', 'string'],
            'resolution_summary' => ['nullable', 'string'],
        ];
    }
}