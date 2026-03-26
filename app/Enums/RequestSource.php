<?php

namespace App\Enums;

enum RequestSource: string
{
    case ADMIN_PANEL = 'admin_panel';
    case API = 'api';
    case MOBILE_APP = 'mobile_app';
    case OFFLINE_SYNC = 'offline_sync';
}
