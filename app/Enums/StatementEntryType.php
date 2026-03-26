<?php

namespace App\Enums;

enum StatementEntryType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case ADJUSTMENT = 'adjustment';
    case REVERSAL = 'reversal';
}
