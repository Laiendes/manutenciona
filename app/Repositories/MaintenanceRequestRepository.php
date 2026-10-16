<?php

namespace App\Repositories;

use App\Models\MaintenanceRequest;

class MaintenanceRequestRepository
{
    public function create(array $data): MaintenanceRequest
    {
        return MaintenanceRequest::create($data);
    }
}
