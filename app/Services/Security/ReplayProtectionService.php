<?php

namespace App\Services\Security;

use App\Exceptions\SecurityViolation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class ReplayProtectionService
{
    public function assertTimestampAndNonce(string $timestamp, string $nonce): void
    {
        $parsedTimestamp = CarbonImmutable::parse($timestamp);
        $windowSeconds = (int) config('security.replay.window_seconds', 300);

        if (abs(now()->diffInSeconds($parsedTimestamp, false)) > $windowSeconds) {
            throw new SecurityViolation('Timestamp fora da janela de seguranca permitida.');
        }

        $cacheKey = sprintf(
            '%s%s',
            config('security.replay.nonce_cache_prefix', 'security:nonce:'),
            hash('sha256', $nonce)
        );

        if (! Cache::add($cacheKey, $parsedTimestamp->toIso8601String(), $windowSeconds)) {
            throw new SecurityViolation('Nonce ja utilizado anteriormente.');
        }
    }
}
