<?php

use App\Models\User;

it('permite que um usuário faça logout com sucesso', function () {
    $user = User::factory()->create();

    $token = $user->createToken('auth_token');

    $response = $this
        ->withToken($token->plainTextToken)
        ->postJson('/api/logout');

    $response
        ->assertOk()
        ->assertJson([
            'message' => 'Logout realizado com sucesso.',
        ]);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

it('não permite logout sem autenticação', function () {
    $response = $this->postJson('/api/logout');

    $response->assertUnauthorized();
});

it('não permite utilizar um token revogado', function () {
    $user = User::factory()->create();

    $token = $user->createToken('auth_token');

    $this->withToken($token->plainTextToken)
        ->postJson('/api/logout')
        ->assertOk();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);

    auth()->forgetGuards();

    $this->withToken($token->plainTextToken)
        ->getJson('/api/user')
        ->assertUnauthorized();
});
