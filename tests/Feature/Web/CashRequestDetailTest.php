<?php

namespace Tests\Feature\Web;

use App\Enums\CashApprovalStage;
use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Admin\CashRequests\Show;
use App\Models\ApprovalRule;
use App\Models\CashRequest;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CashRequestDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_request_detail_hides_financial_actions_until_manager_approval(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-001',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            'requested_amount' => 850,
            'approved_amount' => null,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Visita técnica',
            'justification' => 'Despesas operacionais para atendimento externo.',
            'planned_use_date' => '2026-03-25',
            'due_accountability_at' => '2026-03-31 18:00:00',
            'submitted_at' => '2026-03-24 09:10:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.cash-requests.show', $cashRequest))
            ->assertOk()
            ->assertSee('breadcrumb-trail', false)
            ->assertSee(route('admin.cash-requests.index'), false)
            ->assertDontSee('PÃ', false)
            ->assertSee('Linha do tempo do caixa')
            ->assertSee('Data da solicitação')
            ->assertSee('A etapa financeira só fica disponível depois que o gestor do colaborador aprovar a solicitação.')
            ->assertSee('A liberação só aparece para o financeiro depois que o gestor do funcionário aprovar a solicitação.')
            ->assertDontSee('Registrar liberação');
    }

    public function test_cash_request_detail_shows_timeline_dates_for_approval_payment_and_closing(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $finance = User::query()->where('email', 'finance@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-002',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::CLOSED,
            'requested_amount' => 1400,
            'approved_amount' => 1400,
            'released_amount' => 1400,
            'spent_amount' => 1400,
            'available_amount' => 0,
            'purpose' => 'Roteiro comercial',
            'justification' => 'Caixa para roteiro de visitas e despesas de campo.',
            'planned_use_date' => '2026-03-17',
            'due_accountability_at' => '2026-03-24 18:00:00',
            'submitted_at' => '2026-03-17 08:00:00',
            'released_at' => '2026-03-17 14:00:00',
            'closed_at' => '2026-03-24 17:00:00',
        ]);

        $cashRequest->approvals()->create([
            'stage' => CashApprovalStage::MANAGER,
            'decision' => 'approved',
            'acted_by_id' => $manager->id,
            'comment' => 'Aprovado pelo gestor.',
            'acted_at' => '2026-03-17 09:00:00',
        ]);

        $cashRequest->approvals()->create([
            'stage' => CashApprovalStage::FINANCIAL,
            'decision' => 'approved',
            'acted_by_id' => $finance->id,
            'comment' => 'Aprovado pelo financeiro.',
            'acted_at' => '2026-03-17 13:00:00',
        ]);

        $cashRequest->deposits()->create([
            'released_by_id' => $finance->id,
            'payment_method' => PaymentMethod::PIX,
            'amount' => 1400,
            'reference_number' => 'PIX-TEST-002',
            'released_at' => '2026-03-17 14:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.cash-requests.show', $cashRequest))
            ->assertOk()
            ->assertSee('Aprovação do gestor')
            ->assertSee('Aprovação do financeiro')
            ->assertSee('Liberação e pagamento')
            ->assertSee('Fechamento do caixa')
            ->assertSee('17/03/2026 08:00')
            ->assertSee('17/03/2026 09:00')
            ->assertSee('17/03/2026 13:00')
            ->assertSee('17/03/2026 14:00')
            ->assertSee('24/03/2026 17:00');
    }

    public function test_livewire_financial_approval_button_changes_request_state_and_enables_release(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-003',
            'user_id' => $requester->id,
            'manager_id' => null,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_FINANCIAL_APPROVAL,
            'requested_amount' => 1120,
            'approved_amount' => null,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Apoio operacional',
            'justification' => 'Solicitação enviada direto ao financeiro.',
            'planned_use_date' => '2026-03-26',
            'due_accountability_at' => '2026-04-02 18:00:00',
            'submitted_at' => '2026-03-24 10:00:00',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['cashRequest' => $cashRequest])
            ->assertSee('Aprovar financeiro')
            ->set('financialComment', 'Fluxo validado pelo financeiro.')
            ->set('financialDueAccountabilityAt', '2026-04-05T18:30')
            ->call('approveFinancial')
            ->assertSee('Aprovação financeira registrada.')
            ->assertDontSee('Aprovar financeiro')
            ->assertSee('Registrar liberação');

        $cashRequest->refresh();

        $this->assertSame(CashRequestStatus::FINANCIAL_APPROVED, $cashRequest->status);
        $this->assertSame(1120.0, (float) $cashRequest->approved_amount);
        $this->assertSame('2026-04-05 18:30:00', $cashRequest->due_accountability_at?->format('Y-m-d H:i:s'));
    }

    public function test_livewire_manager_approval_button_moves_request_to_financial_stage(): void
    {
        $this->seed();

        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-005',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            'requested_amount' => 640,
            'approved_amount' => null,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Visita gerencial',
            'justification' => 'Caixa para reunioes externas do colaborador.',
            'planned_use_date' => '2026-03-26',
            'due_accountability_at' => '2026-04-01 18:00:00',
            'submitted_at' => '2026-03-25 08:00:00',
        ]);

        Livewire::actingAs($manager)
            ->test(Show::class, ['cashRequest' => $cashRequest])
            ->assertSee('Aprovar gestor')
            ->set('managerComment', 'Fluxo validado pelo gestor responsavel.')
            ->call('approveManager')
            ->assertSee('Gestor aprovou')
            ->assertDontSee('Aprovar gestor')
            ->assertSee('Etapa financeira')
            ->assertSee('Em acompanhamento');

        $cashRequest->refresh();

        $this->assertSame(CashRequestStatus::AWAITING_FINANCIAL_APPROVAL, $cashRequest->status);
        $this->assertSame(640.0, (float) $cashRequest->approved_amount);
        $this->assertDatabaseHas('cash_request_approvals', [
            'cash_request_id' => $cashRequest->id,
            'stage' => CashApprovalStage::MANAGER->value,
            'comment' => 'Fluxo validado pelo gestor responsavel.',
        ]);
    }

    public function test_livewire_manager_rejection_button_changes_request_state(): void
    {
        $this->seed();

        $manager = User::query()->where('email', 'manager@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();
        $rejectionReason = \App\Models\RejectionReason::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-006',
            'user_id' => $requester->id,
            'manager_id' => $manager->id,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            'requested_amount' => 710,
            'approved_amount' => null,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Reuniao externa',
            'justification' => 'Solicitacao precisa de validacao adicional.',
            'planned_use_date' => '2026-03-27',
            'due_accountability_at' => '2026-04-02 18:00:00',
            'submitted_at' => '2026-03-25 09:15:00',
        ]);

        Livewire::actingAs($manager)
            ->test(Show::class, ['cashRequest' => $cashRequest])
            ->assertSee('Reprovar gestor')
            ->set('managerComment', 'Necessario complementar a justificativa antes de aprovar.')
            ->set('rejectionReasonPublicId', $rejectionReason->public_id)
            ->call('rejectManager')
            ->assertSee('Gestor reprovou')
            ->assertDontSee('Reprovar gestor');

        $cashRequest->refresh();

        $this->assertSame(CashRequestStatus::MANAGER_REJECTED, $cashRequest->status);
        $this->assertDatabaseHas('cash_request_rejections', [
            'cash_request_id' => $cashRequest->id,
            'stage' => CashApprovalStage::MANAGER->value,
            'comment' => 'Necessario complementar a justificativa antes de aprovar.',
        ]);
    }

    public function test_livewire_financial_rejection_button_changes_request_state(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();
        $rejectionReason = \App\Models\RejectionReason::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-004',
            'user_id' => $requester->id,
            'manager_id' => null,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::AWAITING_FINANCIAL_APPROVAL,
            'requested_amount' => 980,
            'approved_amount' => null,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Despesa emergencial',
            'justification' => 'Precisa de revisão documental.',
            'planned_use_date' => '2026-03-27',
            'due_accountability_at' => '2026-04-03 18:00:00',
            'submitted_at' => '2026-03-24 11:00:00',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['cashRequest' => $cashRequest])
            ->assertSee('Reprovar financeiro')
            ->set('financialComment', 'Documentação insuficiente para seguir.')
            ->set('rejectionReasonPublicId', $rejectionReason->public_id)
            ->call('rejectFinancial')
            ->assertSee('Reprovação financeira registrada.')
            ->assertDontSee('Reprovar financeiro');

        $cashRequest->refresh();

        $this->assertSame(CashRequestStatus::FINANCIAL_REJECTED, $cashRequest->status);
        $this->assertDatabaseHas('cash_request_rejections', [
            'cash_request_id' => $cashRequest->id,
            'stage' => CashApprovalStage::FINANCIAL->value,
            'comment' => 'Documentação insuficiente para seguir.',
        ]);
    }
    public function test_livewire_release_requires_payment_receipt(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-007',
            'user_id' => $requester->id,
            'manager_id' => null,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::FINANCIAL_APPROVED,
            'requested_amount' => 1200,
            'approved_amount' => 1200,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Liberacao operacional',
            'justification' => 'Fluxo aguardando comprovante de pagamento.',
            'planned_use_date' => '2026-03-28',
            'due_accountability_at' => '2026-04-04 18:00:00',
            'submitted_at' => '2026-03-25 10:30:00',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['cashRequest' => $cashRequest])
            ->set('releaseAmount', 1200)
            ->set('releasePaymentMethod', PaymentMethod::PIX->value)
            ->call('release')
            ->assertHasErrors(['releaseReceipt' => 'required']);

        $cashRequest->refresh();

        $this->assertNull($cashRequest->released_at);
        $this->assertSame(CashRequestStatus::FINANCIAL_APPROVED, $cashRequest->status);
    }

    public function test_livewire_release_registers_payment_receipt_attachment(): void
    {
        $this->seed();
        Storage::fake('public');

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-008',
            'user_id' => $requester->id,
            'manager_id' => null,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::FINANCIAL_APPROVED,
            'requested_amount' => 890,
            'approved_amount' => 890,
            'released_amount' => 0,
            'spent_amount' => 0,
            'available_amount' => 0,
            'purpose' => 'Pagamento aprovado',
            'justification' => 'Fluxo pronto para liberar com comprovante.',
            'planned_use_date' => '2026-03-29',
            'due_accountability_at' => '2026-04-05 18:00:00',
            'submitted_at' => '2026-03-25 11:10:00',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['cashRequest' => $cashRequest])
            ->set('releaseAmount', 890)
            ->set('releasePaymentMethod', PaymentMethod::PIX->value)
            ->set('releaseReceipt', UploadedFile::fake()->create('comprovante.pdf', 120, 'application/pdf'))
            ->call('release')
            ->assertHasNoErrors();

        $cashRequest->refresh();

        $this->assertSame(CashRequestStatus::RELEASED, $cashRequest->status);
        $this->assertNotNull($cashRequest->released_at);

        $deposit = $cashRequest->deposits()->with('attachments')->latest('released_at')->firstOrFail();
        $attachment = $deposit->attachments->first();

        $this->assertNotNull($attachment);
        Storage::disk('public')->assertExists($attachment->path);
    }

    public function test_livewire_can_approve_expense_from_cash_request_detail(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();
        $department = Department::query()->firstOrFail();
        $costCenter = CostCenter::query()->firstOrFail();
        $approvalRule = ApprovalRule::query()->firstOrFail();
        $expenseCategory = ExpenseCategory::query()->firstOrFail();

        $cashRequest = CashRequest::query()->create([
            'request_number' => 'CX-TEST-DET-009',
            'user_id' => $requester->id,
            'manager_id' => null,
            'department_id' => $department->id,
            'cost_center_id' => $costCenter->id,
            'approval_rule_id' => $approvalRule->id,
            'status' => CashRequestStatus::RELEASED,
            'requested_amount' => 300,
            'approved_amount' => 300,
            'released_amount' => 300,
            'spent_amount' => 0,
            'available_amount' => 300,
            'purpose' => 'Despesa de campo',
            'justification' => 'Fluxo aguardando validação do gasto.',
            'planned_use_date' => '2026-03-29',
            'due_accountability_at' => '2026-04-05 18:00:00',
            'submitted_at' => '2026-03-25 12:00:00',
            'released_at' => '2026-03-25 13:00:00',
        ]);

        $cashRequest->deposits()->create([
            'released_by_id' => $admin->id,
            'payment_method' => PaymentMethod::PIX,
            'amount' => 300,
            'reference_number' => 'PIX-TEST-009',
            'released_at' => '2026-03-25 13:00:00',
        ]);

        $expense = $cashRequest->expenses()->create([
            'user_id' => $requester->id,
            'expense_category_id' => $expenseCategory->id,
            'status' => CashExpenseStatus::SUBMITTED,
            'spent_at' => '2026-03-25 14:00:00',
            'submitted_at' => '2026-03-25 14:01:00',
            'amount' => 300,
            'description' => 'Hotel',
            'vendor_name' => 'Hotel Central',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['cashRequest' => $cashRequest])
            ->set("expenseReviewNotes.{$expense->public_id}", 'Comprovante validado e valor coerente.')
            ->call('approveExpense', $expense->public_id)
            ->assertSee('Gasto aprovado com sucesso.');

        $expense->refresh();
        $cashRequest->refresh();

        $this->assertSame(CashExpenseStatus::APPROVED, $expense->status);
        $this->assertSame('Comprovante validado e valor coerente.', $expense->review_notes);
        $this->assertNotNull($expense->reviewed_at);
        $this->assertSame($admin->id, $expense->reviewed_by_id);
        $this->assertSame(CashRequestStatus::FULLY_ACCOUNTED, $cashRequest->status);
        $this->assertSame(300.0, (float) $cashRequest->spent_amount);
        $this->assertSame(0.0, (float) $cashRequest->available_amount);
    }
}
