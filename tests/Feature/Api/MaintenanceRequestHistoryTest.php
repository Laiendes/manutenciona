<?php

use App\Enums\UserRoleEnum;
use App\Models\MaintenanceRequestHistory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('permite que um solicitante consulte o histórico da própria solicitação', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    $history = MaintenanceRequestHistory::create([
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $this->user->id,
        'old_status' => null,
        'new_status' => 'open',
        'description' => 'Solicitação de manutenção criada.',
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}/history");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $history->id)
        ->assertJsonPath('data.0.maintenance_request_id', $maintenanceRequest->id)
        ->assertJsonPath('data.0.actor_id', $this->user->id)
        ->assertJsonPath('data.0.new_status', 'open');
});

it('permite que um administrador consulte o histórico de solicitações de outros usuários', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    $history = MaintenanceRequestHistory::create([
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $this->user->id,
        'old_status' => null,
        'new_status' => 'open',
        'description' => 'Solicitação de manutenção criada.',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}/history");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $history->id)
        ->assertJsonPath('data.0.maintenance_request_id', $maintenanceRequest->id);
});

it('não permite que um solicitante consulte o histórico de outros usuários', function () {
    $otherUser = User::factory()->create();

    $maintenanceRequest = createMaintenanceRequest($otherUser);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}/history");

    $response->assertForbidden();
});

it('retorna erro ao consultar o histórico de uma solicitação inexistente', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/maintenance-requests/999999/history');

    $response->assertNotFound();
});

it('não permite consultar o histórico sem autenticação', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}/history");

    $response->assertUnauthorized();
});

it('retorna o histórico em ordem cronológica', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    $newerHistory = MaintenanceRequestHistory::create([
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $this->user->id,
        'old_status' => 'open',
        'new_status' => 'in_progress',
        'description' => 'Solicitação em andamento.',
        'created_at' => now(),
    ]);

    $olderHistory = MaintenanceRequestHistory::create([
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $this->user->id,
        'old_status' => null,
        'new_status' => 'open',
        'description' => 'Solicitação criada.',
        'created_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}/history");

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $olderHistory->id)
        ->assertJsonPath('data.1.id', $newerHistory->id);
});
