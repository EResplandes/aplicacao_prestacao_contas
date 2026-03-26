<?php

namespace App\Services\Auth;

use App\Models\AuthRefreshToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

class RefreshTokenService
{
    public function issue(User $user, string $deviceName, Request $request, ?AuthRefreshToken $rotatedFrom = null): array
    {
        $plainTextToken = Str::random(96);

        $refreshToken = AuthRefreshToken::query()->create([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $plainTextToken),
            'device_name' => Str::limit(trim($deviceName), 120, ''),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'rotated_from_id' => $rotatedFrom?->getKey(),
            'expires_at' => now()->addMinutes((int) config('security.auth.refresh_token_ttl_minutes')),
        ]);

        if ($rotatedFrom) {
            $rotatedFrom->forceFill([
                'last_used_at' => now(),
                'revoked_at' => now(),
            ])->save();
        }

        return [
            'record' => $refreshToken,
            'plain_text' => $plainTextToken,
        ];
    }

    public function rotateFromRequest(Request $request): ?array
    {
        $refreshToken = $this->resolveFromRequest($request);

        if (! $refreshToken) {
            return null;
        }

        return $this->issue($refreshToken->user, $refreshToken->device_name, $request, $refreshToken);
    }

    public function resolveFromRequest(Request $request): ?AuthRefreshToken
    {
        $plainTextToken = $request->cookie($this->cookieName());

        if (blank($plainTextToken)) {
            return null;
        }

        /** @var AuthRefreshToken|null $refreshToken */
        $refreshToken = AuthRefreshToken::query()
            ->with([
                'user.department',
                'user.costCenter',
                'user.manager',
                'user.roles',
                'user.payoutAccount',
            ])
            ->where('token_hash', hash('sha256', (string) $plainTextToken))
            ->first();

        if (! $refreshToken || ! $refreshToken->isActive()) {
            return null;
        }

        return $refreshToken;
    }

    public function revokeFromRequest(Request $request): void
    {
        $refreshToken = $this->resolveFromRequest($request);

        if (! $refreshToken) {
            return;
        }

        $refreshToken->forceFill([
            'last_used_at' => now(),
            'revoked_at' => now(),
        ])->save();
    }

    public function makeCookie(string $plainTextToken): SymfonyCookie
    {
        return Cookie::make(
            name: $this->cookieName(),
            value: $plainTextToken,
            minutes: (int) config('security.auth.refresh_token_ttl_minutes'),
            path: '/',
            domain: null,
            secure: (bool) config('security.auth.refresh_cookie_secure'),
            httpOnly: true,
            raw: false,
            sameSite: (string) config('security.auth.refresh_cookie_same_site', 'lax'),
        );
    }

    public function forgetCookie(): SymfonyCookie
    {
        return Cookie::make(
            name: $this->cookieName(),
            value: '',
            minutes: -2628000,
            path: '/',
            domain: null,
            secure: (bool) config('security.auth.refresh_cookie_secure'),
            httpOnly: true,
            raw: false,
            sameSite: (string) config('security.auth.refresh_cookie_same_site', 'lax'),
        );
    }

    private function cookieName(): string
    {
        return (string) config('security.auth.refresh_cookie_name');
    }
}
