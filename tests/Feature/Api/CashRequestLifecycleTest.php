<?php

namespace Tests\Feature\Api;

use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashRequestLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_runs_the_main_cash_request_lifecycle(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        Sanctum::actingAs($requester);

        $createResponse = $this->postJson('/api/v1/cash-requests', [
            'requested_amount' => 1000,
            'purpose' => 'Viagem comercial',
            'justification' => 'Despesas da visita ao cliente.',
            'department_public_id' => $department->public_id,
            'cost_center_public_id' => $requester->costCenter->public_id,
            'planned_use_date' => now()->toDateString(),
            'client_reference_id' => 'mobile-create-001',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'awaiting_manager_approval');

        $cashRequestPublicId = $createResponse->json('data.public_id');

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/manager-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado pelo gestor.',
        ])->assertOk()->assertJsonPath('data.status', 'awaiting_financial_approval');

        Sanctum::actingAs($finance);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/financial-decision", [
            'decision' => 'approved',
            'comment' => 'Aprovado pelo financeiro.',
        ])->assertOk()->assertJsonPath('data.status', 'financial_approved');

        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/release", [
            'payment_method' => 'pix',
            'amount' => 1000,
            'receipt' => UploadedFile::fake()->image('payment-receipt.png'),
        ])->assertOk()->assertJsonPath('data.status', 'released');

        Sanctum::actingAs($requester);
        $this->postJson("/api/v1/cash-requests/{$cashRequestPublicId}/expenses", [
            'expense_category_public_id' => $expenseCategory->public_id,
            'client_reference_id' => 'mobile-expense-001',
            'spent_at' => now()->toIso8601String(),
            'amount' => 250,
            'description' => 'Taxi para visita',
            'vendor_name' => 'Cooperativa Local',
            'location' => [
                'latitude' => -23.55052,
                'longitude' => -46.63330,
                'accuracy_meters' => 12.4,
                'captured_at' => now()->toIso8601String(),
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.location.latitude', -23.55052)
            ->assertJsonPath('data.location.longitude', -46.6333);

        $this->getJson("/api/v1/cash-requests/{$cashRequestPublicId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'partially_accounted')
            ->assertJsonPath('data.available_amount', 750);

        $this->assertGreaterThanOrEqual(
            3,
            $requester->fresh()->unreadNotifications()->count(),
        );

        $this->assertGreaterThanOrEqual(
            2,
            $finance->fresh()->unreadNotifications()->count(),
        );

        $this->assertDatabaseHas('cash_expenses', [
            'client_reference_id' => 'mobile-expense-001',
            'location_latitude' => -23.5505200,
            'location_longitude' => -46.6333000,
        ]);
    }
}
