<?php

namespace App\Services;

use App\Enums\CashApprovalStage;
use App\Enums\CashRequestStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Models\CashRequestRejection;
use App\Models\User;

class CashRequestWorkflowService
{
    public function initialStatusFor(User $user): CashRequestStatus
    {
        return $user->manager_id
            ? CashRequestStatus::AWAITING_MANAGER_APPROVAL
            : CashRequestStatus::AWAITING_FINANCIAL_APPROVAL;
    }

    public function approve(CashRequest $cashRequest, CashApprovalStage $stage): CashRequestStatus
    {
        return match ($stage) {
            CashApprovalStage::MANAGER => $this->approveManager($cashRequest),
            CashApprovalStage::FINANCIAL => $this->approveFinancial($cashRequest),
        };
    }

    public function reject(CashRequest $cashRequest, CashApprovalStage $stage): CashRequestStatus
    {
        return match ($stage) {
            CashApprovalStage::MANAGER => CashRequestStatus::MANAGER_REJECTED,
            CashApprovalStage::FINANCIAL => CashRequestStatus::FINANCIAL_REJECTED,
        };
    }

    public function resubmitStatus(CashRequestRejection $rejection): CashRequestStatus
    {
        return $rejection->stage === CashApprovalStage::FINANCIAL
            ? CashRequestStatus::AWAITING_FINANCIAL_APPROVAL
            : CashRequestStatus::AWAITING_MANAGER_APPROVAL;
    }

    public function closeStatusForBalances(CashRequest $cashRequest): CashRequestStatus
    {
        if ((float) $cashRequest->released_amount === 0.0) {
            return $cashRequest->status;
        }

        if ((float) $cashRequest->available_amount <= 0) {
            return CashRequestStatus::FULLY_ACCOUNTED;
        }

        if ((float) $cashRequest->spent_amount > 0) {
            return CashRequestStatus::PARTIALLY_ACCOUNTED;
        }

        return CashRequestStatus::RELEASED;
    }

    private function approveManager(CashRequest $cashRequest): CashRequestStatus
    {
        if ($cashRequest->status !== CashRequestStatus::AWAITING_MANAGER_APPROVAL) {
            throw new BusinessRuleViolation('A solicitacao nao esta aguardando aprovacao gerencial.');
        }

        return CashRequestStatus::AWAITING_FINANCIAL_APPROVAL;
    }

    private function approveFinancial(CashRequest $cashRequest): CashRequestStatus
    {
        if (! in_array($cashRequest->status, [
            CashRequestStatus::AWAITING_FINANCIAL_APPROVAL,
            CashRequestStatus::MANAGER_APPROVED,
        ], true)) {
            throw new BusinessRuleViolation('A solicitacao nao esta aguardando aprovacao financeira.');
        }

        return CashRequestStatus::FINANCIAL_APPROVED;
    }
}
