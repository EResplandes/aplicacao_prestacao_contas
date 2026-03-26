<?php

namespace App\Actions\CashRequest;

use App\Data\CashRequest\ApprovalDecisionData;
use App\Enums\ApprovalDecision;
use App\Enums\CashApprovalStage;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CashRequestWorkflowService;
use App\Services\OperationalNotificationService;
use Illuminate\Support\Facades\DB;

class DecideCashRequestAction
{
    public function __construct(
        private readonly CashRequestWorkflowService $workflowService,
        private readonly AuditService $auditService,
        private readonly OperationalNotificationService $notificationService,
    ) {}

    public function execute(User $actor, CashRequest $cashRequest, ApprovalDecisionData $data): CashRequest
    {
        return DB::transaction(function () use ($actor, $cashRequest, $data): CashRequest {
            $previousStatus = $cashRequest->status;
            $previousDueAccountabilityAt = $cashRequest->due_accountability_at;

            if ($data->decision === ApprovalDecision::APPROVED) {
                if ($data->stage === CashApprovalStage::FINANCIAL && ! $data->dueAccountabilityAt) {
                    throw new BusinessRuleViolation('Informe a data limite para fechamento do caixa antes de aprovar no financeiro.');
                }

                $cashRequest->approvals()->create([
                    'stage' => $data->stage,
                    'decision' => $data->decision,
                    'acted_by_id' => $actor->id,
                    'comment' => $data->comment,
                    'acted_at' => now(),
                ]);

                $cashRequest->update([
                    'status' => $this->workflowService->approve($cashRequest, $data->stage),
                    'approved_amount' => $cashRequest->approved_amount ?: $cashRequest->requested_amount,
                    'due_accountability_at' => $data->stage === CashApprovalStage::FINANCIAL
                        ? $data->dueAccountabilityAt
                        : $cashRequest->due_accountability_at,
                ]);
            } else {
                $cashRequest->rejections()->create([
                    'stage' => $data->stage,
                    'rejection_reason_id' => $data->rejectionReasonId,
                    'rejected_by_id' => $actor->id,
                    'comment' => $data->comment,
                    'can_resubmit' => $data->canResubmit,
                ]);

                $cashRequest->update([
                    'status' => $this->workflowService->reject($cashRequest, $data->stage),
                ]);
            }

            $this->auditService->log(
                user: $actor,
                event: 'cash_request.decision',
                action: $data->decision->value,
                auditable: $cashRequest,
                oldValues: [
                    'status' => $previousStatus?->value,
                    'due_accountability_at' => optional($previousDueAccountabilityAt)->toIso8601String(),
                ],
                newValues: [
                    'status' => $cashRequest->fresh()->status?->value,
                    'due_accountability_at' => optional($cashRequest->fresh()->due_accountability_at)->toIso8601String(),
                ],
                metadata: [
                    'stage' => $data->stage->value,
                    'comment' => $data->comment,
                    'due_accountability_at' => optional($data->dueAccountabilityAt)->toIso8601String(),
                ],
            );

            $this->notificationService->notifyCashRequestDecision(
                cashRequest: $cashRequest->fresh(['user', 'manager']),
                stage: $data->stage,
                decision: $data->decision,
            );

            return $cashRequest->load(['approvals.actor', 'rejections.reason', 'rejections.rejectedBy']);
        });
    }
}
