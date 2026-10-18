<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('retorna somente os campos públicos do usuário autenticado', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/user');

    $response
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', $user->name)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', $user->role)
        ->assertJsonMissingPath('data.created_at')
        ->assertJsonMissingPath('data.updated_at')
        ->assertJsonMissingPath('data.email_verified_at');
});

it('não permite consultar os dados do usuário sem autenticação', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});
