<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'requester_id' => $this->requester_id,
            'assignee_id' => $this->assignee_id,
        ];
    }
}
