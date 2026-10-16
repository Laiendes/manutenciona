<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Http\Resources\MaintenanceRequestResource;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\JsonResponse;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private MaintenanceRequestService $maintenanceRequestService,
    ) {}

    public function store(StoreMaintenanceRequestRequest $request): JsonResponse
    {
        $maintenanceRequest = $this->maintenanceRequestService->create(
            $request->validated(),
            $request->user()
        );

        return (new MaintenanceRequestResource($maintenanceRequest))
            ->response()
            ->setStatusCode(201);
    }
}
