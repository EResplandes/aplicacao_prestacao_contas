<?php

namespace App\Enums;

enum ApprovalDecision: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ADJUSTMENT_REQUESTED = 'adjustment_requested';
}
