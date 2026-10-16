<?php

use App\Enums\MaintenanceRequestPriorityEnum;
use App\Enums\MaintenanceRequestStatusEnum;
use App\Models\User;
use App\Repositories\MaintenanceRequestHistoryRepository;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function maintenanceRequestData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Ar-condicionado com defeito',
        'description' => 'O equipamento não está refrigerando.',
        'location' => 'Sala 203',
    ], $overrides);
}

function assertNoMaintenanceRequests(): void
{
    expect(DB::table('maintenance_requests')->count())->toBe(0)
        ->and(DB::table('maintenance_request_histories')->count())->toBe(0);
}

it('permite que um usuário autenticado crie uma solicitação de manutenção', function () {
    Sanctum::actingAs($this->user);

    $data = maintenanceRequestData([
        'priority' => MaintenanceRequestPriorityEnum::High->value,
    ]);

    $response = $this->postJson('/api/maintenance-requests', $data);

    $response->assertCreated()
        ->assertJsonPath('data.title', $data['title'])
        ->assertJsonPath('data.description', $data['description'])
        ->assertJsonPath('data.location', $data['location'])
        ->assertJsonPath('data.priority', MaintenanceRequestPriorityEnum::High->value)
        ->assertJsonPath('data.status', MaintenanceRequestStatusEnum::Open->value)
        ->assertJsonPath('data.requester_id', $this->user->id)
        ->assertJsonPath('data.assignee_id', null);

    $maintenanceRequestId = $response->json('data.id');

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $maintenanceRequestId,
        'requester_id' => $this->user->id,
        'priority' => MaintenanceRequestPriorityEnum::High->value,
        'status' => MaintenanceRequestStatusEnum::Open->value,
    ]);

    $this->assertDatabaseHas('maintenance_request_histories', [
        'maintenance_request_id' => $maintenanceRequestId,
        'actor_id' => $this->user->id,
        'old_status' => null,
        'new_status' => MaintenanceRequestStatusEnum::Open->value,
        'description' => 'Solicitação de manutenção criada.',
    ]);
});

it('não permite criar solicitações sem autenticação', function () {
    $response = $this->postJson(
        '/api/maintenance-requests',
        maintenanceRequestData()
    );

    $response->assertUnauthorized();

    assertNoMaintenanceRequests();
});

it('não permite criar solicitações sem os campos obrigatórios', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson('/api/maintenance-requests', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title',
            'description',
            'location',
        ]);

    assertNoMaintenanceRequests();
});

it('não permite criar solicitações com prioridade inválida', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson(
        '/api/maintenance-requests',
        maintenanceRequestData([
            'priority' => 'urgent',
        ])
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['priority']);

    assertNoMaintenanceRequests();
});

it('define prioridade média quando nenhuma prioridade é informada', function () {
    Sanctum::actingAs($this->user);

    $response = $this->postJson(
        '/api/maintenance-requests',
        maintenanceRequestData()
    );

    $response->assertCreated()
        ->assertJsonPath(
            'data.priority',
            MaintenanceRequestPriorityEnum::Medium->value
        );

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $response->json('data.id'),
        'priority' => MaintenanceRequestPriorityEnum::Medium->value,
        'status' => MaintenanceRequestStatusEnum::Open->value,
        'requester_id' => $this->user->id,
    ]);

    $this->assertDatabaseCount('maintenance_request_histories', 1);
});

it('não permite criar solicitações em nome de outro usuário', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();

    $response = $this->postJson(
        '/api/maintenance-requests',
        maintenanceRequestData([
            'requester_id' => $otherUser->id,
        ])
    );

    $response->assertCreated()
        ->assertJsonPath('data.requester_id', $this->user->id);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $response->json('data.id'),
        'requester_id' => $this->user->id,
    ]);

    $this->assertDatabaseMissing('maintenance_requests', [
        'requester_id' => $otherUser->id,
    ]);
});

it('desfaz a criação da solicitação quando o registro do histórico falha', function () {
    Sanctum::actingAs($this->user);

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
        $this->postJson(
            '/api/maintenance-requests',
            maintenanceRequestData()
        );

        $this->fail('Era esperado que uma exceção fosse lançada.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())
            ->toBe('Falha ao registrar histórico.');
    }

    assertNoMaintenanceRequests();
});
