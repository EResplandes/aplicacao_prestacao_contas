<?php

namespace App\Enums;

enum CashRequestMessageSenderRole: string
{
    case REQUESTER = 'requester';
    case FINANCE = 'finance';
}
