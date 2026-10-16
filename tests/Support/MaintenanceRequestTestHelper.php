<?php

use App\Models\MaintenanceRequest;
use App\Models\User;

function createMaintenanceRequest(User $user, array $overrides = []): MaintenanceRequest
{
    return MaintenanceRequest::create(array_merge([
        'title' => 'Ar-condicionado com defeito',
        'description' => 'O equipamento não está refrigerando.',
        'location' => 'Sala 203',
        'requester_id' => $user->id,
    ], $overrides));
}
