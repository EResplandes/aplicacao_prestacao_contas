<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\CashDeposit;
use App\Models\CashExpense;
use App\Models\CashRequest;
use App\Models\User;
use App\Models\UserPayoutAccount;
use App\Services\SecurityEventService;
use App\Support\Api\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureLocale();

        if (config('livewire.csp_safe')) {
            Livewire::setScriptRoute(function ($handle) {
                $file = config('app.debug') ? 'livewire.csp.js' : 'livewire.csp.min.js';

                return Route::get(EndpointResolver::prefix().'/'.$file, $handle);
            });
        }

        RateLimiter::for('api', function (Request $request): Limit {
            return $this->rateLimit(
                'api',
                Limit::perMinute(100)->by('api:ip:'.$request->ip()),
            );
        });

        RateLimiter::for('authenticated-api', function (Request $request): array {
            return [
                $this->rateLimit('authenticated-api', Limit::perMinute(100)->by('authenticated:user:'.$request->user()?->getAuthIdentifier())),
                $this->rateLimit('authenticated-api', Limit::perMinute(100)->by('authenticated:ip:'.$request->ip())),
            ];
        });

        RateLimiter::for('auth-login', function (Request $request): array {
            $login = mb_strtolower(trim((string) $request->input('email')));

            return [
                $this->rateLimit('auth-login', Limit::perMinutes(15, 5)->by('auth-login:ip:'.$request->ip())),
                $this->rateLimit('auth-login', Limit::perMinutes(15, 5)->by('auth-login:login:'.$login.'|'.$request->ip())),
            ];
        });

        RateLimiter::for('auth-refresh', function (Request $request): array {
            return [
                $this->rateLimit('auth-refresh', Limit::perMinute(20)->by('auth-refresh:ip:'.$request->ip())),
            ];
        });

        RateLimiter::for('cash-approval', function (Request $request): array {
            return [
                $this->rateLimit('cash-approval', Limit::perMinute(10)->by('cash-approval:user:'.$request->user()?->getAuthIdentifier())),
                $this->rateLimit('cash-approval', Limit::perMinute(10)->by('cash-approval:ip:'.$request->ip())),
            ];
        });

        RateLimiter::for('bb-payment', function (Request $request): array {
            return [
                $this->rateLimit('bb-payment', Limit::perMinute(5)->by('bb-payment:user:'.$request->user()?->getAuthIdentifier())),
                $this->rateLimit('bb-payment', Limit::perMinute(5)->by('bb-payment:ip:'.$request->ip())),
            ];
        });

        Relation::enforceMorphMap([
            'attachment' => Attachment::class,
            'cash_deposit' => CashDeposit::class,
            'cash_expense' => CashExpense::class,
            'cash_request' => CashRequest::class,
            'user' => User::class,
            'user_payout_account' => UserPayoutAccount::class,
        ]);
    }

    private function rateLimit(string $limiter, Limit $limit): Limit
    {
        return $limit->response(function (Request $request, array $headers) use ($limiter) {
            app(SecurityEventService::class)->recordRateLimit($limiter, $headers);

            return ApiResponse::error(
                'Limite de requisições excedido. Aguarde antes de tentar novamente.',
                429,
                [],
                $headers
            );
        });
    }

    private function configureLocale(): void
    {
        $locale = config('app.locale', 'pt_BR');

        app()->setLocale($locale);
        CarbonImmutable::setLocale($locale);
        Date::setLocale($locale);

        setlocale(
            LC_TIME,
            'pt_BR.UTF-8',
            'pt_BR.utf8',
            'pt_BR',
            'Portuguese_Brazil.1252'
        );

        if (class_exists(\Locale::class)) {
            \Locale::setDefault(str_replace('_', '-', $locale));
        }
    }
}
