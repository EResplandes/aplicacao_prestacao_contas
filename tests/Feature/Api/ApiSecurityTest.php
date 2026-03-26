<?php

namespace Tests\Feature\Api;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_is_rate_limited_after_five_attempts(): void
    {
        $this->seed();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'requester@example.com',
                'password' => 'senha-invalida',
                'device_name' => 'Chrome local',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'requester@example.com',
            'password' => 'senha-invalida',
            'device_name' => 'Chrome local',
        ])
            ->assertStatus(429)
            ->assertHeader('Retry-After');

        $this->assertTrue(
            SecurityEvent::query()->where('event_type', 'rate_limited')->exists()
        );
    }

    public function test_api_login_sets_refresh_cookie_and_short_lived_access_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'requester@example.com',
            'password' => 'password',
            'device_name' => 'Chrome local',
        ]);

        $response
            ->assertOk()
            ->assertCookie(config('security.auth.refresh_cookie_name'))
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'expires_at',
                    'user',
                ],
            ]);
    }

    public function test_api_login_accepts_trusted_localhost_origin_with_dynamic_port(): void
    {
        $this->seed();

        $this->withHeaders([
            'Origin' => 'http://localhost:57231',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/auth/login', [
            'email' => 'requester@example.com',
            'password' => 'password',
            'device_name' => 'Chrome local',
        ])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:57231')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_invalid_api_login_creates_security_event(): void
    {
        $this->seed();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'requester@example.com',
            'password' => 'senha-invalida',
            'device_name' => 'Chrome local',
        ])->assertStatus(422);

        $event = SecurityEvent::query()->latest('detected_at')->first();

        $this->assertNotNull($event);
        $this->assertSame('login_failed', $event->event_type);
        $this->assertSame('api', $event->channel);
    }

    public function test_untrusted_origin_is_registered_as_security_event(): void
    {
        $this->seed();

        $this->withHeaders([
            'Origin' => 'https://attacker.example',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/auth/login', [
            'email' => 'requester@example.com',
            'password' => 'password',
            'device_name' => 'Chrome local',
        ])->assertStatus(403);

        $this->assertTrue(
            SecurityEvent::query()->where('event_type', 'untrusted_origin_blocked')->exists()
        );
    }

    public function test_suspicious_probe_is_registered(): void
    {
        $this->get('/.env')->assertNotFound();

        $this->assertTrue(
            SecurityEvent::query()->where('event_type', 'suspicious_probe')->exists()
        );
    }

    public function test_authenticated_api_responses_include_security_headers(): void
    {
        $this->seed();

        $requester = User::query()->where('email', 'requester@example.com')->firstOrFail();

        Sanctum::actingAs($requester);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-Request-Id');
    }
}
