<?php

namespace App\Http\Requests\Asset;

use App\Models\AssetStatus;
use App\Models\Location;
use App\Rules\TenantRecordExists;
use Illuminate\Foundation\Http\FormRequest;

class CheckinAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'location_id' => ['nullable', 'uuid', new TenantRecordExists(Location::class, $tenantId)],
            'status_id' => ['nullable', 'uuid', new TenantRecordExists(AssetStatus::class, $tenantId)],
            'checkin_date' => ['nullable', 'date'],
            'condition' => ['nullable', 'string', 'in:new,good,fair,poor,damaged'],
            'notes' => ['nullable', 'string'],
        ];
    }
}