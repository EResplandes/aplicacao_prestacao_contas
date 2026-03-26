<?php

namespace App\Data\CashRequest;

use App\Enums\ApprovalDecision;
use App\Enums\CashApprovalStage;
use Carbon\CarbonInterface;

readonly class ApprovalDecisionData
{
    public function __construct(
        public CashApprovalStage $stage,
        public ApprovalDecision $decision,
        public ?string $comment = null,
        public ?int $rejectionReasonId = null,
        public bool $canResubmit = true,
        public ?CarbonInterface $dueAccountabilityAt = null,
    ) {}
}
