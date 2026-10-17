<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignMaintenanceRequestRequest;
use App\Http\Requests\StoreMaintenanceRequestRequest;
use App\Http\Requests\UpdateMaintenanceRequestStatusRequest;
use App\Http\Resources\MaintenanceRequestHistoryResource;
use App\Http\Resources\MaintenanceRequestResource;
use App\Services\MaintenanceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        private MaintenanceRequestService $maintenanceRequestService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $maintenanceRequests = $this->maintenanceRequestService->listForUser(
            $request->user()
        );

        return MaintenanceRequestResource::collection($maintenanceRequests);
    }

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

    public function show(int $id, Request $request): MaintenanceRequestResource
    {
        $maintenanceRequest = $this->maintenanceRequestService->findById(
            $id,
            $request->user()
        );

        return new MaintenanceRequestResource($maintenanceRequest);
    }

    public function history(int $id, Request $request): AnonymousResourceCollection
    {
        $history = $this->maintenanceRequestService->getHistory(
            $id,
            $request->user()
        );

        return MaintenanceRequestHistoryResource::collection($history);
    }

    public function assign(int $id, AssignMaintenanceRequestRequest $request): MaintenanceRequestResource
    {
        $maintenanceRequest = $this->maintenanceRequestService->assign(
            $id,
            $request->validated(),
            $request->user()
        );

        return new MaintenanceRequestResource($maintenanceRequest);
    }

    public function updateStatus(
        int $id,
        UpdateMaintenanceRequestStatusRequest $request
    ): MaintenanceRequestResource {
        $maintenanceRequest = $this->maintenanceRequestService->updateStatus(
            $id,
            $request->validated(),
            $request->user()
        );

        return new MaintenanceRequestResource($maintenanceRequest);
    }
}
