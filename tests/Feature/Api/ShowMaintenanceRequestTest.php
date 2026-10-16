<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('permite que um solicitante visualize sua própria solicitação', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $maintenanceRequest->id)
        ->assertJsonPath('data.title', $maintenanceRequest->title)
        ->assertJsonPath('data.requester_id', $this->user->id);
});

it('permite que um administrador visualize solicitações de outros usuários', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $maintenanceRequest = createMaintenanceRequest($this->user, [
        'title' => 'Vazamento de água',
        'description' => 'Vazamento no banheiro.',
        'location' => 'Banheiro principal',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $maintenanceRequest->id)
        ->assertJsonPath('data.requester_id', $this->user->id);
});

it('não permite que um solicitante visualize solicitações de outros usuários', function () {
    $otherUser = User::factory()->create();

    $maintenanceRequest = createMaintenanceRequest($otherUser, [
        'title' => 'Problema elétrico',
        'description' => 'Tomada apresentando falhas.',
        'location' => 'Sala 101',
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}");

    $response->assertForbidden();
});

it('retorna erro quando a solicitação não existe', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/maintenance-requests/999999');

    $response->assertNotFound();
});

it('não permite visualizar uma solicitação sem autenticação', function () {
    $maintenanceRequest = createMaintenanceRequest($this->user);

    $response = $this->getJson("/api/maintenance-requests/{$maintenanceRequest->id}");

    $response->assertUnauthorized();
});
