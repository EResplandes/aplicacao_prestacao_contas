<?php

namespace App\Services;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SecurityEventService
{
    public function __construct(private readonly Request $request) {}

    public function record(
        string $eventType,
        string $severity,
        ?User $user = null,
        ?string $identifier = null,
        ?int $statusCode = null,
        ?string $channel = null,
        array $metadata = [],
    ): SecurityEvent {
        return SecurityEvent::query()->create([
            'user_id' => $user?->id,
            'channel' => $channel ?? $this->resolveChannel(),
            'event_type' => $eventType,
            'severity' => $severity,
            'identifier' => $identifier,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'request_method' => $this->request->getMethod(),
            'route_name' => $this->request->route()?->getName(),
            'path' => $this->request->path(),
            'status_code' => $statusCode,
            'metadata' => $metadata,
            'detected_at' => now(),
        ]);
    }

    public function recordFailedLogin(
        string $channel,
        string $identifier,
        ?User $user = null,
        string $reason = 'invalid_credentials',
    ): SecurityEvent {
        return $this->record(
            eventType: 'login_failed',
            severity: 'high',
            user: $user,
            identifier: $identifier,
            statusCode: 422,
            channel: $channel,
            metadata: [
                'reason' => $reason,
            ],
        );
    }

    public function recordLockout(
        string $channel,
        string $identifier,
        int $retryAfterSeconds,
    ): SecurityEvent {
        return $this->record(
            eventType: 'login_lockout',
            severity: 'critical',
            identifier: $identifier,
            statusCode: 429,
            channel: $channel,
            metadata: [
                'retry_after_seconds' => $retryAfterSeconds,
            ],
        );
    }

    public function recordRateLimit(string $limiter, array $headers = []): SecurityEvent
    {
        return $this->record(
            eventType: 'rate_limited',
            severity: str_contains($limiter, 'auth') ? 'critical' : 'medium',
            identifier: $this->request->user()?->email,
            statusCode: 429,
            metadata: [
                'limiter' => $limiter,
                'retry_after' => $headers['Retry-After'] ?? $headers['retry-after'] ?? null,
            ],
        );
    }

    public function recordUntrustedOrigin(?string $origin): SecurityEvent
    {
        return $this->record(
            eventType: 'untrusted_origin_blocked',
            severity: 'high',
            identifier: $this->request->input('email'),
            statusCode: 403,
            metadata: [
                'origin' => $origin,
                'referer' => $this->request->headers->get('Referer'),
            ],
        );
    }

    /**
     * @param  array<int, string>  $matchedPatterns
     */
    public function recordSuspiciousProbe(string $path, array $matchedPatterns = []): SecurityEvent
    {
        return $this->record(
            eventType: 'suspicious_probe',
            severity: 'high',
            identifier: $this->request->ip(),
            statusCode: 404,
            metadata: [
                'path' => $path,
                'matched_patterns' => array_values(array_unique($matchedPatterns)),
                'query' => Arr::query($this->request->query()),
            ],
        );
    }

    private function resolveChannel(): string
    {
        return $this->request->routeIs('api.*') ? 'api' : 'web';
    }
}
