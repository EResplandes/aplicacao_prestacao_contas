<?php

namespace App\Actions\CashRequest;

use App\Data\CashRequest\ApprovalDecisionData;
use App\Enums\ApprovalDecision;
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

            if ($data->decision === ApprovalDecision::APPROVED) {
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
                oldValues: ['status' => $previousStatus?->value],
                newValues: ['status' => $cashRequest->fresh()->status?->value],
                metadata: ['stage' => $data->stage->value, 'comment' => $data->comment],
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
