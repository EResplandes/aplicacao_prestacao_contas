<?php

namespace App\Enums;

enum SyncStatus: string
{
    case PENDING = 'pending';
    case PROCESSED = 'processed';
    case DUPLICATED = 'duplicated';
    case FAILED = 'failed';
}
