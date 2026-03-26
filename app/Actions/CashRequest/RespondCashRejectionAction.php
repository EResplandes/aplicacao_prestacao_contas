<?php

namespace App\Actions\CashRequest;

use App\Enums\CashRequestStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CashRequestWorkflowService;
use Illuminate\Support\Facades\DB;

class RespondCashRejectionAction
{
    public function __construct(
        private readonly CashRequestWorkflowService $workflowService,
        private readonly AuditService $auditService,
    ) {}

    public function execute(User $actor, CashRequest $cashRequest, string $responseComment, bool $resubmit): CashRequest
    {
        return DB::transaction(function () use ($actor, $cashRequest, $responseComment, $resubmit): CashRequest {
            $rejection = $cashRequest->rejections()->latest('created_at')->first();

            if (! $rejection) {
                throw new BusinessRuleViolation('Nao existe reprovacao registrada para esta solicitacao.');
            }

            $rejection->update([
                'response_comment' => $responseComment,
                'responded_at' => now(),
                'responded_by_user_id' => $actor->id,
            ]);

            if ($resubmit) {
                if (! $rejection->can_resubmit) {
                    throw new BusinessRuleViolation('Esta reprovacao nao permite reenvio.');
                }

                $cashRequest->update([
                    'status' => $this->workflowService->resubmitStatus($rejection),
                    'submitted_at' => now(),
                ]);
            } else {
                $cashRequest->update([
                    'status' => CashRequestStatus::CANCELLED,
                    'cancelled_at' => now(),
                ]);
            }

            $this->auditService->log(
                user: $actor,
                event: 'cash_request.rejection_response',
                action: $resubmit ? 'resubmit' : 'cancel',
                auditable: $cashRequest,
                metadata: ['response_comment' => $responseComment],
            );

            return $cashRequest->load(['rejections.reason']);
        });
    }
}
