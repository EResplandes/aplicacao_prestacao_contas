<?php

namespace App\Enums;

enum CashExpenseStatus: string
{
    case PENDING = 'pending';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case FLAGGED = 'flagged';
}
