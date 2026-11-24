<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RepairUpdateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status_from' => $this->status_from,
            'status_to' => $this->status_to,
            'comments' => $this->comments,
            'author' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('author', fn() => $this->author?->name),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}