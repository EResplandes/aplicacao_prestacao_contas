<?php

namespace Tests\Feature\Api;

use App\Enums\CashRequestStatus;
use App\Models\ApprovalRule;
use App\Models\CashRequest;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashRequestChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_requester_and_finance_can_exchange_messages_on_cash_request(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-CHAT-001',
            'user_id' => $requester->id,
            'manager_id' => $requester->manager_id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::RELEASED,
            'requested_amount' => 500,
            'approved_amount' => 500,
            'released_amount' => 500,
            'spent_amount' => 0,
            'available_amount' => 500,
            'purpose' => 'Visita ao cliente',
            'justification' => 'Caixa para deslocamento.',
            'planned_use_date' => now()->toDateString(),
            'submitted_at' => now()->subDay(),
            'released_at' => now()->subHours(12),
        ]);

        Sanctum::actingAs($requester);

        $this->postJson("/api/v1/cash-requests/{$cashRequest->public_id}/messages", [
            'message' => 'Posso enviar um comprovante complementar depois?',
        ])
            ->assertCreated()
            ->assertJsonPath('data.sender_role', 'requester');

        Sanctum::actingAs($finance);

        $this->postJson("/api/v1/cash-requests/{$cashRequest->public_id}/messages", [
            'message' => 'Pode sim. Anexe no gasto e me avise por aqui.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.sender_role', 'finance');

        Sanctum::actingAs($requester);

        $this->getJson("/api/v1/cash-requests/{$cashRequest->public_id}")
            ->assertOk()
            ->assertJsonPath('data.messages.0.sender_role', 'requester')
            ->assertJsonPath('data.messages.1.sender_role', 'finance')
            ->assertJsonPath('data.can_view_chat', true)
            ->assertJsonPath('data.can_send_chat_message', true);

        $this->assertDatabaseHas('cash_request_messages', [
            'cash_request_id' => $cashRequest->id,
            'sender_id' => $requester->id,
            'sender_role' => 'requester',
        ]);

        $this->assertDatabaseHas('cash_request_messages', [
            'cash_request_id' => $cashRequest->id,
            'sender_id' => $finance->id,
            'sender_role' => 'finance',
        ]);
    }

    public function test_manager_cannot_send_message_to_financial_chat(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-CHAT-002',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            'requested_amount' => 300,
            'approved_amount' => 0,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Despesa operacional',
            'justification' => 'Caixa aguardando fluxo.',
            'planned_use_date' => now()->toDateString(),
            'submitted_at' => now()->subHours(3),
        ]);

        Sanctum::actingAs($manager);

        $this->postJson("/api/v1/cash-requests/{$cashRequest->public_id}/messages", [
            'message' => 'Mensagem indevida do gestor.',
        ])->assertForbidden();
    }
}
