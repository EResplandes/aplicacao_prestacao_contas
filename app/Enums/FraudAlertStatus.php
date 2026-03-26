<?php

namespace App\Enums;

enum FraudAlertStatus: string
{
    case OPEN = 'open';
    case UNDER_REVIEW = 'under_review';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';
}
