<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\ProfileResource;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Auth\RefreshTokenService;
use App\Services\SecurityEventService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly AuditService $auditService,
        private readonly SecurityEventService $securityEventService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = User::query()
            ->with(['department', 'costCenter', 'manager', 'roles', 'payoutAccount'])
            ->where('email', $request->validated('email'))
            ->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            $this->securityEventService->recordFailedLogin(
                channel: 'api',
                identifier: $request->validated('email'),
                user: $user,
            );

            return ApiResponse::error('Credenciais inválidas.', 422);
        }

        if (! $user->is_active) {
            $this->securityEventService->recordFailedLogin(
                channel: 'api',
                identifier: $request->validated('email'),
                user: $user,
                reason: 'inactive_account',
            );

            return ApiResponse::error('Usuário inativo.', 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $tokenExpiresAt = now()->addMinutes((int) config('security.auth.access_token_ttl_minutes'));
        $token = $user->createToken(
            $request->validated('device_name'),
            ['*'],
            $tokenExpiresAt,
        )->plainTextToken;

        $refreshToken = $this->refreshTokenService->issue(
            $user,
            $request->validated('device_name'),
            $request,
        );

        $this->auditService->log(
            user: $user,
            event: 'auth.login',
            action: 'login',
            auditable: $user,
            metadata: [
                'device_name' => $request->validated('device_name'),
            ],
        );

        $response = ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $tokenExpiresAt->toIso8601String(),
            'user' => new ProfileResource($user),
        ], 'Login realizado com sucesso.');

        $response->headers->set('Cache-Control', 'no-store, private');

        return $response->withCookie($this->refreshTokenService->makeCookie($refreshToken['plain_text']));
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $this->refreshTokenService->resolveFromRequest($request);

        if (! $refreshToken || ! $refreshToken->user->is_active) {
            return ApiResponse::error('Sessao expirada ou invalida.', 401)
                ->withCookie($this->refreshTokenService->forgetCookie());
        }

        $rotatedRefreshToken = $this->refreshTokenService->rotateFromRequest($request);
        $user = $refreshToken->user->load(['department', 'costCenter', 'manager', 'roles', 'payoutAccount']);
        $tokenExpiresAt = now()->addMinutes((int) config('security.auth.access_token_ttl_minutes'));
        $token = $user->createToken($refreshToken->device_name, ['*'], $tokenExpiresAt)->plainTextToken;

        $this->auditService->log(
            user: $user,
            event: 'auth.refresh',
            action: 'refresh',
            auditable: $user,
            metadata: [
                'device_name' => $refreshToken->device_name,
            ],
        );

        $response = ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $tokenExpiresAt->toIso8601String(),
            'user' => new ProfileResource($user),
        ], 'Token renovado com sucesso.');

        $response->headers->set('Cache-Control', 'no-store, private');

        return $response->withCookie($this->refreshTokenService->makeCookie($rotatedRefreshToken['plain_text']));
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->user()?->currentAccessToken()?->delete();
        $this->refreshTokenService->revokeFromRequest($request);

        if ($user) {
            $this->auditService->log(
                user: $user,
                event: 'auth.logout',
                action: 'logout',
                auditable: $user,
            );
        }

        return ApiResponse::success(message: 'Logout realizado com sucesso.')
            ->withCookie($this->refreshTokenService->forgetCookie());
    }
}
