<?php

namespace App\Http\Middleware;

use App\Support\Api\ApiResponse;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRecentMfaChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = config('security.mfa.session_key', 'mfa_passed_at');
        $maxAgeSeconds = (int) config('security.mfa.max_age_seconds', 300);
        $mfaPassedAt = $request->session()->get($sessionKey);

        if ($mfaPassedAt && now()->diffInSeconds(CarbonImmutable::parse($mfaPassedAt)) <= $maxAgeSeconds) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return ApiResponse::error(
                'Confirmacao MFA obrigatoria para aprovar ou agendar pagamentos.',
                403,
                ['code' => ['mfa_required']]
            );
        }

        abort(403, 'Confirmacao MFA obrigatoria para aprovar ou agendar pagamentos.');
    }
}
