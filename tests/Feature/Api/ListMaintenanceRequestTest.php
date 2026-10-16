<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('permite que um solicitante visualize somente suas solicitações', function () {
    $otherUser = User::factory()->create();

    $ownRequest = createMaintenanceRequest($this->user);

    $otherRequest = createMaintenanceRequest($otherUser, [
        'title' => 'Lâmpada queimada',
        'description' => 'A lâmpada precisa ser substituída.',
        'location' => 'Corredor principal',
    ]);

    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/maintenance-requests');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownRequest->id)
        ->assertJsonMissing(['id' => $otherRequest->id]);
});

it('permite que um administrador visualize todas as solicitações', function () {
    $admin = User::factory()->create([
        'role' => UserRoleEnum::Admin,
    ]);

    $firstRequest = createMaintenanceRequest($this->user, [
        'title' => 'Problema elétrico',
        'description' => 'Tomada apresentando falhas.',
        'location' => 'Sala 101',
    ]);

    $secondRequest = createMaintenanceRequest($this->user, [
        'title' => 'Vazamento de água',
        'description' => 'Vazamento no banheiro.',
        'location' => 'Banheiro principal',
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson('/api/maintenance-requests');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $firstRequest->id])
        ->assertJsonFragment(['id' => $secondRequest->id]);
});

it('não permite listar solicitações sem autenticação', function () {
    $response = $this->getJson('/api/maintenance-requests');

    $response->assertUnauthorized();
});

it('retorna uma lista vazia quando o solicitante não possui solicitações', function () {
    Sanctum::actingAs($this->user);

    $response = $this->getJson('/api/maintenance-requests');

    $response->assertOk()
        ->assertExactJson([
            'data' => [],
        ]);
});
