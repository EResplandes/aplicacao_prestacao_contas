<?php

namespace Tests\Feature\Web;

use App\Enums\CashRequestStatus;
use App\Livewire\Admin\CashRequests\ChatPanel;
use App\Models\ApprovalRule;
use App\Models\CashRequest;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashRequestChatPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_send_message_from_admin_cash_request_detail(): void
    {
        $this->seed();

        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-CHAT-WEB-001',
            'user_id' => $requester->id,
            'manager_id' => $requester->manager_id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::RELEASED,
            'requested_amount' => 700,
            'approved_amount' => 700,
            'released_amount' => 700,
            'spent_amount' => 0,
            'available_amount' => 700,
            'purpose' => 'Visita comercial',
            'justification' => 'Fluxo ativo para conversa.',
            'planned_use_date' => now()->toDateString(),
            'submitted_at' => now()->subDay(),
            'released_at' => now()->subHours(10),
        ]);

        Livewire::actingAs($finance)
            ->test(ChatPanel::class, ['cashRequest' => $cashRequest])
            ->set('message', 'Pode seguir e me avisar se o comprovante ficar ilegível.')
            ->call('sendMessage')
            ->assertSee('Mensagem enviada para o colaborador.');

        $this->assertDatabaseHas('cash_request_messages', [
            'cash_request_id' => $cashRequest->id,
            'sender_id' => $finance->id,
            'sender_role' => 'finance',
            'message' => 'Pode seguir e me avisar se o comprovante ficar ilegível.',
        ]);
    }

    public function test_manager_does_not_see_financial_chat_panel_on_cash_request_detail(): void
    {
        $this->seed();

        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-CHAT-WEB-002',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            'requested_amount' => 350,
            'approved_amount' => 0,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Solicitacao inicial',
            'justification' => 'Gestor acompanha sem chat financeiro.',
            'planned_use_date' => now()->toDateString(),
            'submitted_at' => now()->subHours(5),
        ]);

        $this->actingAs($manager)
            ->get(route('admin.cash-requests.show', $cashRequest))
            ->assertOk()
            ->assertDontSee('Conversa com o colaborador');
    }
}
