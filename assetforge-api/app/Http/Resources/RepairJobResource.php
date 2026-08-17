<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_number' => $this->job_number,
            'repair_status' => $this->repair_status,
            'repair_center' => $this->repair_center,
            'vendor_name' => $this->vendor_name,
            'cost' => $this->cost,
            'start_date' => $this->start_date?->toDateString(),
            'completion_date' => $this->completion_date?->toDateString(),
            'fault_description' => $this->fault_description,
            'diagnostic_notes' => $this->diagnostic_notes,
            'resolution_summary' => $this->resolution_summary,
            'asset' => [
                'id' => $this->equipment_asset_id,
                'asset_tag' => $this->whenLoaded('asset', fn() => $this->asset?->asset_tag),
                'serial_number' => $this->whenLoaded('asset', fn() => $this->asset?->serial_number),
                'name' => $this->whenLoaded('asset', fn() => $this->asset?->name),
            ],
            'fault_type' => [
                'id' => $this->fault_type_id,
                'name' => $this->whenLoaded('faultType', fn() => $this->faultType?->name),
                'severity' => $this->whenLoaded('faultType', fn() => $this->faultType?->severity),
            ],
            'technician' => [
                'id' => $this->technician_id,
                'name' => $this->whenLoaded('technician', fn() => $this->technician?->name),
                'email' => $this->whenLoaded('technician', fn() => $this->technician?->email),
            ],
            'updates' => RepairUpdateResource::collection($this->whenLoaded('updates')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}