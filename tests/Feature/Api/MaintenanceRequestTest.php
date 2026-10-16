<?php

use App\Enums\MaintenanceRequestPriorityEnum;
use App\Enums\MaintenanceRequestStatusEnum;
use App\Models\User;
use App\Repositories\MaintenanceRequestHistoryRepository;
use Laravel\Sanctum\Sanctum;

it('permite que um usuário autenticado crie uma solicitação de manutenção', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $data = [
        'title' => 'Ar-condicionado com defeito',
        'description' => 'O equipamento não está refrigerando.',
        'location' => 'Sala 203',
        'priority' => MaintenanceRequestPriorityEnum::High->value,
    ];

    $response = $this->postJson('/api/maintenance-requests', $data);

    $response->assertCreated()
        ->assertJsonPath('data.title', $data['title'])
        ->assertJsonPath('data.description', $data['description'])
        ->assertJsonPath('data.location', $data['location'])
        ->assertJsonPath('data.priority', MaintenanceRequestPriorityEnum::High->value)
        ->assertJsonPath('data.status', MaintenanceRequestStatusEnum::Open->value)
        ->assertJsonPath('data.requester_id', $user->id)
        ->assertJsonPath('data.assignee_id', null);

    $this->assertDatabaseHas('maintenance_requests', [
        'title' => $data['title'],
        'requester_id' => $user->id,
        'priority' => MaintenanceRequestPriorityEnum::High->value,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $maintenanceRequestId = $response->json('data.id');

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequestId,
        'actor_id' => $user->id,
        'old_status' => null,
        'new_status' => MaintenanceRequestStatusEnum::Open->value,
        'description' => 'Solicitação de manutenção criada.',
    ]);
});

it('não permite criar solicitações sem autenticação', function () {
    $response = $this->postJson('/api/maintenance-requests', [
        'title' => 'Ar-condicionado com defeito',
        'description' => 'O equipamento não está refrigerando.',
        'location' => 'Sala 203',
    ]);

    $response->assertUnauthorized();

    $this->assertDatabaseCount('maintenance_requests', 0);
    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite criar solicitações sem os campos obrigatórios', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/maintenance-requests', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title',
            'description',
            'location',
        ]);

    $this->assertDatabaseCount('maintenance_requests', 0);
    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('não permite criar solicitações com prioridade inválida', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/maintenance-requests', [
        'title' => 'Ar-condicionado com defeito',
        'description' => 'O equipamento não está refrigerando.',
        'location' => 'Sala 203',
        'priority' => 'urgent',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['priority']);

    $this->assertDatabaseCount('maintenance_requests', 0);
    $this->assertDatabaseCount('maintenance_request_histories', 0);
});

it('define prioridade média quando nenhuma prioridade é informada', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/maintenance-requests', [
        'title' => 'Lâmpada queimada',
        'description' => 'A lâmpada do corredor precisa ser substituída.',
        'location' => 'Corredor principal',
    ]);

    $response->assertCreated()
        ->assertJsonPath(
            'data.priority',
            MaintenanceRequestPriorityEnum::Medium->value
        );

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $response->json('data.id'),
        'priority' => MaintenanceRequestPriorityEnum::Medium->value,
        'status' => MaintenanceRequestStatusEnum::Open->value,
        'requester_id' => $user->id,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 1);
});

it('não permite criar solicitações em nome de outro usuário', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->postJson('/api/maintenance-requests', [
        'title' => 'Vazamento de água',
        'description' => 'Existe um vazamento no banheiro.',
        'location' => 'Banheiro principal',
        'requester_id' => $otherUser->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.requester_id', $user->id);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $response->json('data.id'),
        'requester_id' => $user->id,
    ]);

    $this->assertDatabaseMissing('maintenance_requests', [
        'requester_id' => $otherUser->id,
    ]);
});

it('desfaz a criação da solicitação quando o registro do histórico falha', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

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
        $this->postJson('/api/maintenance-requests', [
            'title' => 'Problema elétrico',
            'description' => 'Tomada apresentando falhas.',
            'location' => 'Sala 101',
        ]);

        $this->fail('Era esperado que uma exceção fosse lançada.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())
            ->toBe('Falha ao registrar histórico.');
    }

    $this->assertDatabaseCount('maintenance_requests', 0);
    $this->assertDatabaseCount('maintenance_request_histories', 0);
});
