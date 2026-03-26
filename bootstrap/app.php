<?php

use App\Exceptions\BusinessRuleViolation;
use App\Exceptions\SecurityViolation;
use App\Http\Middleware\DetectSuspiciousProbe;
use App\Http\Middleware\EnsureAdminPanelAccess;
use App\Http\Middleware\EnsureAdminPanelSectionAccess;
use App\Http\Middleware\EnsureRecentMfaChallenge;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ValidateTrustedOrigin;
use App\Support\Api\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->append(DetectSuspiciousProbe::class);
        $middleware->web(prepend: [ValidateTrustedOrigin::class]);
        $middleware->api(prepend: [ValidateTrustedOrigin::class]);
        $middleware->alias([
            'admin.panel' => EnsureAdminPanelAccess::class,
            'admin.section' => EnsureAdminPanelSectionAccess::class,
            'mfa.payment' => EnsureRecentMfaChallenge::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (BusinessRuleViolation $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error($exception->getMessage(), 422);
            }
        });

        $exceptions->render(function (SecurityViolation $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error($exception->getMessage(), 403);
            }
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->expectsJson()) {
                $firstError = collect($exception->errors())
                    ->flatten()
                    ->first();

                $resolvedError = is_string($firstError) ? trim($firstError) : '';

                if ($resolvedError !== '' && Str::startsWith($resolvedError, 'validation.')) {
                    $resolvedError = match ($resolvedError) {
                        'validation.uploaded' => 'Não foi possível enviar o arquivo. Tente novamente com uma imagem ou PDF menor.',
                        default => __($resolvedError),
                    };
                }

                return ApiResponse::error(
                    $resolvedError !== '' ? $resolvedError : 'Erro de validação.',
                    422,
                    $exception->errors()
                );
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Nao autenticado.', 401);
            }
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Acesso negado.', 403);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error('Recurso nao encontrado.', 404);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    'Limite de requisicoes excedido. Aguarde antes de tentar novamente.',
                    429,
                    [],
                    $exception->getHeaders()
                );
            }
        });
    })->create();
