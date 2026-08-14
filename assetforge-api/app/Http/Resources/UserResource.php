<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'employee_number' => $this->employee_number,
            'job_title' => $this->job_title,
            'status' => $this->status,
            'tenant' => [
                'id' => $this->tenant_id,
                'name' => $this->whenLoaded('tenant', fn() => $this->tenant?->name),
            ],
            'organization' => [
                'id' => $this->organization_id,
                'name' => $this->whenLoaded('organization', fn() => $this->organization?->name),
            ],
            'company' => [
                'id' => $this->company_id,
                'name' => $this->whenLoaded('company', fn() => $this->company?->name),
            ],
            'department' => [
                'id' => $this->department_id,
                'name' => $this->whenLoaded('department', fn() => $this->department?->name),
            ],
            'team' => [
                'id' => $this->team_id,
                'name' => $this->whenLoaded('team', fn() => $this->team?->name),
            ],
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}