<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarrantyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $serial = $this->asset?->serial_number;

        return [
            'id' => $this->id,
            'asset' => [
                'id' => $this->equipment_asset_id,
                'asset_tag' => $this->whenLoaded('asset', fn() => $this->asset?->asset_tag),
                'serial_number' => $serial,
                'name' => $this->whenLoaded('asset', fn() => $this->asset?->name),
            ],
            'provider_name' => $this->provider_name,
            'policy_number' => $this->policy_number,
            'warranty_type' => $this->warranty_type,
            'status' => $this->computed_status,
            'days_remaining' => $this->days_remaining,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'service_level' => $this->service_level,
            'support_contact' => [
                'phone' => $this->support_phone,
                'email' => $this->support_email,
                'portal_url' => $this->support_url,
            ],
            'oem_lookup_url' => $serial 
                ? "https://support.hp.com/za-en/check-warranty" 
                : null,
            'terms_and_conditions' => $this->terms_and_conditions,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}