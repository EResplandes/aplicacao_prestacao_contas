<?php

namespace App\Actions\CashRequest;

use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OperationalNotificationService;
use Illuminate\Support\Facades\DB;

class CloseCashRequestAction
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly OperationalNotificationService $notificationService,
    ) {}

    public function execute(User $actor, CashRequest $cashRequest): CashRequest
    {
        if (! in_array($cashRequest->status, [
            CashRequestStatus::RELEASED,
            CashRequestStatus::PARTIALLY_ACCOUNTED,
            CashRequestStatus::FULLY_ACCOUNTED,
        ], true)) {
            throw new BusinessRuleViolation('Este caixa ainda não pode ser encerrado.');
        }

        if ($cashRequest->closed_at) {
            throw new BusinessRuleViolation('Este caixa já foi encerrado.');
        }

        $pendingReviews = $cashRequest->expenses()
            ->whereIn('status', [
                CashExpenseStatus::PENDING,
                CashExpenseStatus::SUBMITTED,
                CashExpenseStatus::FLAGGED,
            ])
            ->count();

        if ($pendingReviews > 0) {
            throw new BusinessRuleViolation('Ainda existem gastos pendentes ou sinalizados para revisão neste caixa.');
        }

        return DB::transaction(function () use ($actor, $cashRequest): CashRequest {
            $previousStatus = $cashRequest->status;
            $previousClosedAt = $cashRequest->closed_at;

            $cashRequest->forceFill([
                'status' => CashRequestStatus::CLOSED,
                'closed_at' => now(),
            ])->save();

            $this->auditService->log(
                user: $actor,
                event: 'cash_request.closed',
                action: 'close',
                auditable: $cashRequest,
                oldValues: [
                    'status' => $previousStatus?->value,
                    'closed_at' => optional($previousClosedAt)->toIso8601String(),
                ],
                newValues: [
                    'status' => CashRequestStatus::CLOSED->value,
                    'closed_at' => optional($cashRequest->closed_at)->toIso8601String(),
                ],
            );

            $cashRequest = $cashRequest->fresh([
                'department',
                'costCenter',
                'manager',
                'attachments',
                'deposits.attachments',
                'deposits.releasedBy',
                'approvals.actor',
                'rejections.reason',
                'expenses.attachments',
                'expenses.category',
                'expenses.ocrRead',
                'expenses.fraudAlerts',
                'expenses.reviewedBy',
            ]);

            $this->notificationService->notifyCashClosed($cashRequest);

            return $cashRequest;
        });
    }
}
