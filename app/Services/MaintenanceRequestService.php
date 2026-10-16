<?php

namespace App\Services;

use App\Enums\MaintenanceRequestPriorityEnum;
use App\Enums\MaintenanceRequestStatusEnum;
use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Repositories\MaintenanceRequestHistoryRepository;
use App\Repositories\MaintenanceRequestRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestService
{
    public function __construct(
        private MaintenanceRequestRepository $maintenanceRequestRepository,
        private MaintenanceRequestHistoryRepository $maintenanceRequestHistoryRepository,
    ) {}

    public function listForUser(User $user): Collection
    {
        return $this->maintenanceRequestRepository->listForUser($user);
    }

    public function create(array $data, User $user): MaintenanceRequest
    {
        return DB::transaction(function () use ($data, $user): MaintenanceRequest {
            $maintenanceRequest = $this->maintenanceRequestRepository->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'location' => $data['location'],
                'priority' => MaintenanceRequestPriorityEnum::from(
                    $data['priority'] ?? MaintenanceRequestPriorityEnum::Medium->value
                ),
                'status' => MaintenanceRequestStatusEnum::Open,
                'requester_id' => $user->id,
            ]);

            $this->maintenanceRequestHistoryRepository->create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'actor_id' => $user->id,
                'old_status' => null,
                'new_status' => MaintenanceRequestStatusEnum::Open->value,
                'description' => 'Solicitação de manutenção criada.',
            ]);

            return $maintenanceRequest;
        });
    }
}
