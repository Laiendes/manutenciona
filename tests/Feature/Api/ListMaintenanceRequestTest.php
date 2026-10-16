<?php

use App\Enums\UserRoleEnum;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('permite que um solicitante visualize somente suas solicitações', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->role = UserRoleEnum::Requester;
    $user->save();

    $otherUser->role = UserRoleEnum::Requester;
    $otherUser->save();

    $ownRequest = MaintenanceRequest::create([
        'title' => 'Ar-condicionado com defeito',
        'description' => 'O equipamento não está refrigerando.',
        'location' => 'Sala 203',
        'requester_id' => $user->id,
    ]);

    $otherRequest = MaintenanceRequest::create([
        'title' => 'Lâmpada queimada',
        'description' => 'A lâmpada precisa ser substituída.',
        'location' => 'Corredor principal',
        'requester_id' => $otherUser->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/maintenance-requests');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownRequest->id)
        ->assertJsonMissing(['id' => $otherRequest->id]);
});

it('permite que um administrador visualize todas as solicitações', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $admin->role = UserRoleEnum::Admin;
    $admin->save();

    $firstRequest = MaintenanceRequest::create([
        'title' => 'Problema elétrico',
        'description' => 'Tomada apresentando falhas.',
        'location' => 'Sala 101',
        'requester_id' => $user->id,
    ]);

    $secondRequest = MaintenanceRequest::create([
        'title' => 'Vazamento de água',
        'description' => 'Vazamento no banheiro.',
        'location' => 'Banheiro principal',
        'requester_id' => $user->id,
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
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/maintenance-requests');

    $response->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertExactJson([
            'data' => [],
        ]);
});
