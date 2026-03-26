<?php

namespace Tests\Unit\Security;

use App\Exceptions\SecurityViolation;
use App\Services\Security\OutboundEndpointGuard;
use Tests\TestCase;

class OutboundEndpointGuardTest extends TestCase
{
    public function test_it_allows_an_explicitly_whitelisted_public_endpoint(): void
    {
        config()->set('bb_api.allowed_hosts', ['93.184.216.34']);

        $guard = app(OutboundEndpointGuard::class);

        $guard->assertAllowed('https://93.184.216.34/pagamentos');

        $this->assertTrue(true);
    }

    public function test_it_rejects_non_whitelisted_hosts(): void
    {
        config()->set('bb_api.allowed_hosts', ['api.bb.com.br']);

        $guard = app(OutboundEndpointGuard::class);

        $this->expectException(SecurityViolation::class);

        $guard->assertAllowed('https://example.com/pagamentos');
    }

    public function test_it_rejects_private_ips_even_when_whitelisted(): void
    {
        config()->set('bb_api.allowed_hosts', ['127.0.0.1']);

        $guard = app(OutboundEndpointGuard::class);

        $this->expectException(SecurityViolation::class);

        $guard->assertAllowed('https://127.0.0.1/pagamentos');
    }
}
