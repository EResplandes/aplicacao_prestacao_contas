<?php

namespace Tests\Unit\Security;

use App\Exceptions\SecurityViolation;
use App\Services\Security\ReplayProtectionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReplayProtectionServiceTest extends TestCase
{
    public function test_it_accepts_a_fresh_nonce_once(): void
    {
        Cache::flush();

        $service = app(ReplayProtectionService::class);

        $service->assertTimestampAndNonce(now()->toIso8601String(), 'nonce-seguro-1');

        $this->assertTrue(true);
    }

    public function test_it_rejects_reused_nonces(): void
    {
        Cache::flush();

        $service = app(ReplayProtectionService::class);

        $service->assertTimestampAndNonce(now()->toIso8601String(), 'nonce-seguro-2');

        $this->expectException(SecurityViolation::class);

        $service->assertTimestampAndNonce(now()->toIso8601String(), 'nonce-seguro-2');
    }

    public function test_it_rejects_timestamps_outside_the_allowed_window(): void
    {
        Cache::flush();

        $service = app(ReplayProtectionService::class);

        $this->expectException(SecurityViolation::class);

        $service->assertTimestampAndNonce(now()->subMinutes(10)->toIso8601String(), 'nonce-seguro-3');
    }
}
