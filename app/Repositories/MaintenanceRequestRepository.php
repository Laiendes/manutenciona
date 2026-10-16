<?php

namespace App\Repositories;

use App\Enums\UserRoleEnum;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MaintenanceRequestRepository
{
    public function listForUser(User $user): Collection
    {
        $query = MaintenanceRequest::query();

        if ($user->role !== UserRoleEnum::Admin) {
            $query->where('requester_id', $user->id);
        }

        return $query->latest()->get();
    }

    public function create(array $data): MaintenanceRequest
    {
        return MaintenanceRequest::create($data);
    }

    public function findById(int $id): ?MaintenanceRequest
    {
        return MaintenanceRequest::find($id);
    }
}
