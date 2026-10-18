<?php

use App\Models\User;

beforeEach(function () {
    $this->data = [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'SenhaSegura123',
        'password_confirmation' => 'SenhaSegura123',
    ];
});

it('permite que um usuário se cadastre com sucesso', function () {
    $response = $this->postJson('/api/register', $this->data);

    $response
        ->assertCreated()
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

    $this->assertDatabaseHas('users', [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'role' => 'requester',
    ]);

    $user = User::where('email', 'joao@example.com')->firstOrFail();

    expect($user->password)->not->toBe('SenhaSegura123');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
    ]);
});

it('não permite cadastrar usuários com e-mail duplicado', function () {
    User::factory()->create([
        'email' => $this->data['email'],
    ]);

    $response = $this->postJson('/api/register', [
        ...$this->data,
        'name' => 'Maria Silva',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('users', 1);
});

it('não permite cadastro sem os campos obrigatórios', function () {
    $response = $this->postJson('/api/register', []);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'email',
            'password',
        ]);

    $this->assertDatabaseCount('users', 0);
});

it('não permite cadastro com e-mail inválido', function () {
    $response = $this->postJson('/api/register', [
        ...$this->data,
        'email' => 'email-invalido',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('users', 0);
});

it('não permite cadastro com senhas diferentes', function () {
    $response = $this->postJson('/api/register', [
        ...$this->data,
        'password_confirmation' => 'OutraSenha456',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $this->assertDatabaseCount('users', 0);
});

it('não permite cadastro com senha menor que oito caracteres', function () {
    $response = $this->postJson('/api/register', [
        ...$this->data,
        'password' => '1234567',
        'password_confirmation' => '1234567',
    ]);

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');

    $this->assertDatabaseCount('users', 0);
});

it('retorna somente os campos públicos do usuário no cadastro', function () {
    $response = $this->postJson('/api/register', $this->data);

    $response
        ->assertCreated()
        ->assertJsonCount(4, 'data.user')
        ->assertJsonPath('data.user.name', 'João Silva')
        ->assertJsonPath('data.user.email', 'joao@example.com')
        ->assertJsonPath('data.user.role', 'requester')
        ->assertJsonMissingPath('data.user.created_at')
        ->assertJsonMissingPath('data.user.updated_at')
        ->assertJsonMissingPath('data.user.email_verified_at');
});
