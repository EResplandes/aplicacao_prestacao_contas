<?php

namespace App\Enums;

enum AttachmentType: string
{
    case REQUEST_SUPPORT = 'request_support';
    case DEPOSIT_RECEIPT = 'deposit_receipt';
    case EXPENSE_RECEIPT = 'expense_receipt';
}
