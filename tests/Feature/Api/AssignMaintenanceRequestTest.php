<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('permite que um administrador atribua um responsável a uma solicitação', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $assignee = User::factory()->create();

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/assign",
        ['assignee_id' => $assignee->id]
    );

    $response->assertOk()
        ->assertJsonPath('data.id', $maintenanceRequest->id)
        ->assertJsonPath('data.assignee_id', $assignee->id);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'assignee_id' => $assignee->id,
    ]);

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $admin->id,
        'old_status' => 'open',
        'new_status' => 'open',
        'description' => "Responsável alterado de  para {$assignee->id}.",
    ]);
});

it('não permite que um solicitante atribua responsáveis', function () {
    $assignee = User::factory()->create();

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($this->user);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/assign",
        ['assignee_id' => $assignee->id]
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'assignee_id' => null,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite atribuir um responsável inexistente', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/assign",
        ['assignee_id' => 999999]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['assignee_id']);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'assignee_id' => null,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('retorna erro ao atribuir responsável a uma solicitação inexistente', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $assignee = User::factory()->create();

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        '/api/maintenance-requests/999999/assign',
        ['assignee_id' => $assignee->id]
    );

    $response->assertNotFound();

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite atribuir responsáveis sem autenticação', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    $assignee = User::factory()->create();

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/assign",
        ['assignee_id' => $assignee->id]
    );

    $response->assertUnauthorized();

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'assignee_id' => null,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('permite reatribuir uma solicitação e registra a alteração no histórico', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $previousAssignee = User::factory()->create();
    $newAssignee = User::factory()->create();

    $maintenanceRequest = createMaintenanceRequest($this->user, [
        'assignee_id' => $previousAssignee->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/assign",
        ['assignee_id' => $newAssignee->id]
    );

    $response->assertOk()
        ->assertJsonPath('data.assignee_id', $newAssignee->id);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'assignee_id' => $newAssignee->id,
    ]);

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $admin->id,
        'description' => "Responsável alterado de {$previousAssignee->id} para {$newAssignee->id}.",
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 1);
});
