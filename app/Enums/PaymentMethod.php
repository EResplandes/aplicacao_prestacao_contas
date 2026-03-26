<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PIX = 'pix';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';
    case CORPORATE_CARD = 'corporate_card';
    case OTHER = 'other';
}
