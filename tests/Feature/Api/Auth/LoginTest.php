<?php

use App\Models\User;

it('permite que um usuário faça login com credenciais válidas', function () {
    $user = User::factory()->create([
        'email' => 'joao@example.com',
        'password' => 'SenhaSegura123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'joao@example.com',
        'password' => 'SenhaSegura123',
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
                'token',
            ],
        ]);

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
    ]);

    expect($response->json('data.token'))
        ->toBeString()
        ->not->toBeEmpty();
});

it('não permite login com senha incorreta', function () {
    User::factory()->create([
        'email' => 'joao@example.com',
        'password' => 'SenhaSegura123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'joao@example.com',
        'password' => 'SenhaErrada456',
    ]);

    $response->assertUnauthorized();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('não permite login com e-mail inexistente', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'inexistente@example.com',
        'password' => 'SenhaSegura123',
    ]);

    $response->assertUnauthorized();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('não permite login sem os campos obrigatórios', function () {
    $response = $this->postJson('/api/login', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'email',
            'password',
        ]);

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('retorna somente os campos públicos do usuário no login', function () {
    $user = User::factory()->create([
        'email' => 'joao@example.com',
        'password' => 'SenhaSegura123',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'joao@example.com',
        'password' => 'SenhaSegura123',
    ]);

    $response
        ->assertOk()
        ->assertJsonCount(4, 'data.user')
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonMissingPath('data.user.created_at')
        ->assertJsonMissingPath('data.user.updated_at')
        ->assertJsonMissingPath('data.user.email_verified_at');
});
