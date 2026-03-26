<?php

namespace App\Actions\CashExpense;

use App\Enums\CashExpenseStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashExpense;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CashRequestWorkflowService;
use App\Services\CashStatementService;
use App\Services\OperationalNotificationService;
use Illuminate\Support\Facades\DB;

class ReviewCashExpenseAction
{
    public function __construct(
        private readonly CashStatementService $statementService,
        private readonly CashRequestWorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly OperationalNotificationService $notificationService,
    ) {}

    public function execute(
        User $actor,
        CashExpense $expense,
        CashExpenseStatus $status,
        ?string $reviewNotes = null,
    ): CashExpense {
        if (! in_array($status, [
            CashExpenseStatus::APPROVED,
            CashExpenseStatus::REJECTED,
            CashExpenseStatus::FLAGGED,
        ], true)) {
            throw new BusinessRuleViolation('Status de revisão inválido para este gasto.');
        }

        if (in_array($expense->cashRequest?->status, [null], true) || ! $expense->cashRequest) {
            throw new BusinessRuleViolation('Não foi possível localizar o caixa vinculado a este gasto.');
        }

        return DB::transaction(function () use ($actor, $expense, $status, $reviewNotes): CashExpense {
            $cashRequest = $expense->cashRequest()->lockForUpdate()->firstOrFail();
            $previousStatus = $expense->status;
            $previousReviewNotes = $expense->review_notes;
            $previousRequestStatus = $cashRequest->status;

            $expense->forceFill([
                'status' => $status,
                'reviewed_at' => now(),
                'reviewed_by_id' => $actor->id,
                'review_notes' => blank($reviewNotes) ? null : trim((string) $reviewNotes),
            ])->save();

            $cashRequest = $this->statementService->syncRequestBalances($cashRequest);

            if (! $cashRequest->closed_at) {
                $cashRequest->update([
                    'status' => $this->workflowService->closeStatusForBalances($cashRequest),
                ]);
            }

            $this->auditService->log(
                user: $actor,
                event: 'cash_expense.reviewed',
                action: $status->value,
                auditable: $expense,
                oldValues: [
                    'status' => $previousStatus?->value,
                    'review_notes' => $previousReviewNotes,
                    'cash_request_status' => $previousRequestStatus?->value,
                ],
                newValues: [
                    'status' => $status->value,
                    'review_notes' => $expense->review_notes,
                    'cash_request_status' => $cashRequest->status?->value,
                ],
            );

            $expense = $expense->fresh([
                'attachments',
                'ocrRead',
                'fraudAlerts',
                'category',
                'cashRequest.user',
                'cashRequest.manager',
                'reviewedBy',
            ]);

            $this->notificationService->notifyExpenseReviewed($expense);

            return $expense;
        });
    }
}
