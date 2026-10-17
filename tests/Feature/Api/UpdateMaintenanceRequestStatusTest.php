<?php

use App\Enums\MaintenanceRequestStatusEnum;
use App\Enums\UserRoleEnum;
use App\Models\User;
use App\Repositories\MaintenanceRequestHistoryRepository;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('permite que um administrador inicie uma solicitação de manutenção', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::InProgress->value]
    );

    $response->assertOk()
        ->assertJsonPath('data.id', $maintenanceRequest->id)
        ->assertJsonPath('data.status', MaintenanceRequestStatusEnum::InProgress->value);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::InProgress->value,
    ]);

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $admin->id,
        'old_status' => MaintenanceRequestStatusEnum::Open->value,
        'new_status' => MaintenanceRequestStatusEnum::InProgress->value,
        'description' => 'Status alterado de open para in_progress.',
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 1);
});

it('não permite que um solicitante altere o status de uma manutenção', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($this->user);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::InProgress->value]
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite concluir uma solicitação que ainda está aberta', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::Completed->value]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite atualizar uma solicitação para o mesmo status', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::Open->value]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('permite concluir uma solicitação em andamento', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user, [
        'status' => MaintenanceRequestStatusEnum::InProgress,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::Completed->value]
    );

    $response->assertOk()
        ->assertJsonPath('data.status', MaintenanceRequestStatusEnum::Completed->value);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Completed->value,
    ]);

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $admin->id,
        'old_status' => MaintenanceRequestStatusEnum::InProgress->value,
        'new_status' => MaintenanceRequestStatusEnum::Completed->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 1);
});

it('permite cancelar uma solicitação aberta', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::Cancelled->value]
    );

    $response->assertOk()
        ->assertJsonPath('data.status', MaintenanceRequestStatusEnum::Cancelled->value);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Cancelled->value,
    ]);

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $admin->id,
        'old_status' => MaintenanceRequestStatusEnum::Open->value,
        'new_status' => MaintenanceRequestStatusEnum::Cancelled->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 1);
});

it('não permite alterar o status de uma solicitação concluída', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user, [
        'status' => MaintenanceRequestStatusEnum::Completed,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::InProgress->value]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Completed->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite alterar o status de uma solicitação cancelada', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user, [
        'status' => MaintenanceRequestStatusEnum::Cancelled,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::Open->value]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Cancelled->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite alterar o status sem autenticação', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::InProgress->value]
    );

    $response->assertUnauthorized();

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite atualizar uma solicitação com status inválido', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => 'pending']
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('retorna erro ao atualizar o status de uma solicitação inexistente', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        '/api/maintenance-requests/999999/status',
        ['status' => MaintenanceRequestStatusEnum::InProgress->value]
    );

    $response->assertNotFound();

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('permite cancelar uma solicitação em andamento', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user, [
        'status' => MaintenanceRequestStatusEnum::InProgress,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson(
        "/api/maintenance-requests/{$maintenanceRequest->id}/status",
        ['status' => MaintenanceRequestStatusEnum::Cancelled->value]
    );

    $response->assertOk()
        ->assertJsonPath('data.status', MaintenanceRequestStatusEnum::Cancelled->value);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Cancelled->value,
    ]);

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequest->id,
        'actor_id' => $admin->id,
        'old_status' => MaintenanceRequestStatusEnum::InProgress->value,
        'new_status' => MaintenanceRequestStatusEnum::Cancelled->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 1);
});

it('desfaz a atualização do status quando o registro do histórico falha', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($admin);

    $this->mock(
        MaintenanceRequestHistoryRepository::class,
        function ($mock) {
            $mock->shouldReceive('create')
                ->once()
                ->andThrow(new RuntimeException('Falha ao registrar histórico.'));
        }
    );

    $this->withoutExceptionHandling();

    try {
        $this->patchJson(
            "/api/maintenance-requests/{$maintenanceRequest->id}/status",
            ['status' => MaintenanceRequestStatusEnum::InProgress->value]
        );

        $this->fail('Era esperado que uma exceção fosse lançada.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())
            ->toBe('Falha ao registrar histórico.');
    }

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequest->id,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 0);
});
