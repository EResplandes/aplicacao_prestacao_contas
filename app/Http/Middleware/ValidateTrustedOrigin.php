<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateTrustedOrigin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldValidate($request)) {
            return $next($request);
        }

        $origin = $this->resolveOrigin($request);

        if (! $origin || ! $this->isTrustedOrigin($origin, $request)) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    'Origem da requisicao nao confiavel.',
                    403
                );
            }

            abort(403, 'Origem da requisicao nao confiavel.');
        }

        return $next($request);
    }

    private function shouldValidate(Request $request): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($request->routeIs('api.*')) {
            return $request->cookies->has(config('session.cookie'))
                || $request->cookies->has('XSRF-TOKEN')
                || $request->headers->has('Origin')
                || $request->headers->has('Referer');
        }

        return true;
    }

    private function resolveOrigin(Request $request): ?string
    {
        $origin = $request->headers->get('Origin');

        if (filled($origin)) {
            return $this->normalizeOrigin($origin);
        }

        $referer = $request->headers->get('Referer');

        if (blank($referer)) {
            return null;
        }

        return $this->normalizeOrigin($referer);
    }

    private function isTrustedOrigin(string $origin, Request $request): bool
    {
        if (in_array($origin, $this->trustedOrigins(), true)) {
            return true;
        }

        $originParts = parse_url($origin);

        if (! is_array($originParts) || ! isset($originParts['host'])) {
            return false;
        }

        $originHost = strtolower((string) $originParts['host']);
        $originPort = isset($originParts['port']) ? (int) $originParts['port'] : null;

        if ($this->matchesStatefulDomain($originHost, $originPort)) {
            return true;
        }

        if (app()->environment('local')
            && $this->isLoopbackHost($originHost)
            && $this->isLoopbackHost((string) $request->getHost())) {
            return true;
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function trustedOrigins(): array
    {
        $origins = array_merge(
            config('security.trusted_origins', []),
            [$this->normalizeOrigin(config('app.url'))],
        );

        return array_values(array_unique(array_filter($origins)));
    }

    private function matchesStatefulDomain(string $originHost, ?int $originPort): bool
    {
        foreach (config('sanctum.stateful', []) as $domain) {
            $normalized = $this->normalizeOrigin(str_contains($domain, '://') ? $domain : 'http://'.$domain);

            if ($normalized === null) {
                continue;
            }

            $parts = parse_url($normalized);

            if (! is_array($parts) || ! isset($parts['host'])) {
                continue;
            }

            $trustedHost = strtolower((string) $parts['host']);
            $trustedPort = isset($parts['port']) ? (int) $parts['port'] : null;

            if ($trustedHost !== $originHost) {
                continue;
            }

            if ($trustedPort === null || $trustedPort === $originPort) {
                return true;
            }
        }

        return false;
    }

    private function isLoopbackHost(string $host): bool
    {
        return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    }

    private function normalizeOrigin(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $parts = parse_url((string) $value);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return strtolower(sprintf('%s://%s%s', $parts['scheme'], $parts['host'], $port));
    }
}
