<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'company' => [
                'id' => $this->company_id,
                'name' => $this->whenLoaded('company', fn() => $this->company?->name),
            ],
            'building' => $this->building,
            'floor' => $this->floor,
            'room' => $this->room,
            'address' => $this->address,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}