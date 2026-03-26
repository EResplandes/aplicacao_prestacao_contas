<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_avoids_duplicate_sync_submission(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();

        Sanctum::actingAs($requester);

        $payload = [
            'device_id' => 'device-sync-001',
            'operations' => [[
                'operation_uuid' => '23b63f49-5c38-4708-8799-b7bf50f9a001',
                'type' => 'cash_request.create',
                'payload' => [
                    'requested_amount' => 500,
                    'purpose' => 'Compra de materiais',
                    'justification' => 'Compra de itens emergenciais.',
                    'department_public_id' => $department->public_id,
                    'cost_center_public_id' => $requester->costCenter->public_id,
                    'planned_use_date' => now()->toDateString(),
                ],
            ]],
        ];

        $this->postJson('/api/v1/sync/pending', $payload)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'processed');

        $this->postJson('/api/v1/sync/pending', $payload)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'duplicated');

        $this->assertDatabaseCount('cash_requests', 1);
        $this->assertDatabaseCount('sync_logs', 1);
    }
}
