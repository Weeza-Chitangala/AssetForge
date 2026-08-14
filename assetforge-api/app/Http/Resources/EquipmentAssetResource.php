<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_tag' => $this->asset_tag,
            'serial_number' => $this->serial_number,
            'name' => $this->name,
            'category' => [
                'id' => $this->category_id,
                'name' => $this->whenLoaded('category', fn() => $this->category?->name),
                'code' => $this->whenLoaded('category', fn() => $this->category?->code),
            ],
            'manufacturer' => [
                'id' => $this->manufacturer_id,
                'name' => $this->whenLoaded('manufacturer', fn() => $this->manufacturer?->name),
                'code' => $this->whenLoaded('manufacturer', fn() => $this->manufacturer?->code),
            ],
            'model' => [
                'id' => $this->asset_model_id,
                'name' => $this->whenLoaded('assetModel', fn() => $this->assetModel?->name),
                'model_number' => $this->whenLoaded('assetModel', fn() => $this->assetModel?->model_number),
            ],
            'location' => [
                'id' => $this->location_id,
                'name' => $this->whenLoaded('location', fn() => $this->location?->name),
                'building' => $this->whenLoaded('location', fn() => $this->location?->building),
                'floor' => $this->whenLoaded('location', fn() => $this->location?->floor),
            ],
            'status' => [
                'id' => $this->status_id,
                'name' => $this->whenLoaded('status', fn() => $this->status?->name),
                'color' => $this->whenLoaded('status', fn() => $this->status?->color),
                'is_deployable' => $this->whenLoaded('status', fn() => $this->status?->is_deployable),
            ],
            'assigned_to' => [
                'id' => $this->assigned_to_user_id,
                'name' => $this->whenLoaded('assignedTo', fn() => $this->assignedTo?->name),
                'email' => $this->whenLoaded('assignedTo', fn() => $this->assignedTo?->email),
                'employee_number' => $this->whenLoaded('assignedTo', fn() => $this->assignedTo?->employee_number),
            ],
            'assigned_date' => $this->assigned_date?->toIso8601String(),
            'specs' => [
                'operating_system' => $this->operating_system,
                'processor' => $this->processor,
                'ram' => $this->ram,
                'storage' => $this->storage,
                'mac_address' => $this->mac_address,
                'ip_address' => $this->ip_address,
            ],
            'procurement' => [
                'supplier' => $this->supplier,
                'order_number' => $this->order_number,
                'purchase_date' => $this->purchase_date?->toDateString(),
                'purchase_cost' => $this->purchase_cost,
                'warranty_expiry_date' => $this->warranty_expiry_date?->toDateString(),
            ],
            'condition' => $this->condition,
            'notes' => $this->notes,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'qr_code_url' => $this->qr_code_path ? asset('storage/' . $this->qr_code_path) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}