<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model_number' => $this->model_number,
            'manufacturer' => [
                'id' => $this->manufacturer_id,
                'name' => $this->whenLoaded('manufacturer', fn() => $this->manufacturer?->name),
            ],
            'category' => [
                'id' => $this->category_id,
                'name' => $this->whenLoaded('category', fn() => $this->category?->name),
            ],
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}