<?php

namespace App\Enums;

enum BankAccountType: string
{
    case CHECKING = 'checking';
    case SAVINGS = 'savings';
}
