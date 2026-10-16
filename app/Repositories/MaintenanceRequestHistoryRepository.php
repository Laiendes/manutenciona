<?php

namespace App\Repositories;

use App\Models\MaintenanceRequestHistory;

class MaintenanceRequestHistoryRepository
{
    public function create(array $data): MaintenanceRequestHistory
    {
        return MaintenanceRequestHistory::create($data);
    }
}
