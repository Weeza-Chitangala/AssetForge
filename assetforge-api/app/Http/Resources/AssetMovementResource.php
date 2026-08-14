<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action_type' => $this->action_type,
            'movement_date' => $this->movement_date?->toIso8601String(),
            'condition' => $this->condition,
            'notes' => $this->notes,
            'from' => [
                'user' => $this->whenLoaded('fromUser', fn() => $this->fromUser ? [
                    'id' => $this->fromUser->id,
                    'name' => $this->fromUser->name,
                ] : null),
                'location' => $this->whenLoaded('fromLocation', fn() => $this->fromLocation ? [
                    'id' => $this->fromLocation->id,
                    'name' => $this->fromLocation->name,
                ] : null),
                'department' => $this->whenLoaded('fromDepartment', fn() => $this->fromDepartment ? [
                    'id' => $this->fromDepartment->id,
                    'name' => $this->fromDepartment->name,
                ] : null),
                'status' => $this->whenLoaded('fromStatus', fn() => $this->fromStatus ? [
                    'id' => $this->fromStatus->id,
                    'name' => $this->fromStatus->name,
                ] : null),
            ],
            'to' => [
                'user' => $this->whenLoaded('toUser', fn() => $this->toUser ? [
                    'id' => $this->toUser->id,
                    'name' => $this->toUser->name,
                ] : null),
                'location' => $this->whenLoaded('toLocation', fn() => $this->toLocation ? [
                    'id' => $this->toLocation->id,
                    'name' => $this->toLocation->name,
                ] : null),
                'department' => $this->whenLoaded('toDepartment', fn() => $this->toDepartment ? [
                    'id' => $this->toDepartment->id,
                    'name' => $this->toDepartment->name,
                ] : null),
                'status' => $this->whenLoaded('toStatus', fn() => $this->toStatus ? [
                    'id' => $this->toStatus->id,
                    'name' => $this->toStatus->name,
                ] : null),
            ],
            'performer' => [
                'id' => $this->performed_by,
                'name' => $this->whenLoaded('performer', fn() => $this->performer?->name),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}