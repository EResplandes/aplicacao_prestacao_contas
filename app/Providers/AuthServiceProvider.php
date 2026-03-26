<?php

namespace App\Providers;

use App\Models\CashRequest;
use App\Policies\CashRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        CashRequest::class => CashRequestPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('dashboard.view', fn ($user) => $user->can('dashboard.view'));
    }
}
