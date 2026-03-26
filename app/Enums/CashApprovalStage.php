<?php

namespace App\Enums;

enum CashApprovalStage: string
{
    case MANAGER = 'manager';
    case FINANCIAL = 'financial';
}
