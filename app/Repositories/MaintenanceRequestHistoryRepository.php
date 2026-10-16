<?php

namespace App\Repositories;

use App\Models\MaintenanceRequestHistory;
use Illuminate\Database\Eloquent\Collection;

class MaintenanceRequestHistoryRepository
{
    public function findByMaintenanceRequestId(int $maintenanceRequestId): Collection
    {
        return MaintenanceRequestHistory::query()
            ->where('maintenance_request_id', $maintenanceRequestId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): MaintenanceRequestHistory
    {
        return MaintenanceRequestHistory::create($data);
    }
}
