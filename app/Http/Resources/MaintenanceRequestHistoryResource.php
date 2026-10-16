<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'maintenance_request_id' => $this->maintenance_request_id,
            'actor_id' => $this->actor_id,
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
