<?php

namespace App\Http\Requests\Asset;

use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Rules\TenantRecordExists;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'user_id' => ['required', 'integer', new TenantRecordExists(User::class, $tenantId)],
            'location_id' => ['nullable', 'uuid', new TenantRecordExists(Location::class, $tenantId)],
            'department_id' => ['nullable', 'uuid', new TenantRecordExists(Department::class, $tenantId)],
            'checkout_date' => ['nullable', 'date'],
            'condition' => ['nullable', 'string', 'in:new,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string'],
        ];
    }
}