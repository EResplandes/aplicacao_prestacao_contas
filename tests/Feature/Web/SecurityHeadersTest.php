<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_includes_security_headers(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Request-Id');
    }

    public function test_web_mutations_reject_untrusted_origin(): void
    {
        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertForbidden();
    }
}
