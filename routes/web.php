<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\CashRequest;
use App\Support\AdminPanel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return auth()->check()
        ? redirect()->route(AdminPanel::homeRouteFor(auth()->user()))
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware('admin.panel')->group(function (): void {
        Route::view('/dashboard', 'admin.dashboard.index')->middleware('admin.section:dashboard')->name('dashboard');
        Route::view('/reports', 'admin.reports.index')->middleware('admin.section:reports')->name('reports.index');
        Route::view('/financial-calendar', 'admin.financial-calendar.index')->middleware('admin.section:financial_calendar')->name('financial-calendar.index');
        Route::view('/security', 'admin.security.index')->middleware('admin.section:security')->name('security.index');
        Route::view('/approvals', 'admin.approvals.index')->middleware('admin.section:approvals')->name('approvals.index');
        Route::view('/cash-monitoring', 'admin.cash-monitoring.index')->middleware('admin.section:cash_monitoring')->name('cash-monitoring.index');
        Route::view('/organization', 'admin.organization.index')->middleware('admin.section:organization')->name('organization.index');
        Route::view('/cost-centers', 'admin.cost-centers.index')->middleware('admin.section:cost_centers')->name('cost-centers.index');
        Route::view('/users', 'admin.users.index')->middleware('admin.section:users')->name('users.index');
        Route::view('/policies', 'admin.policies.index')->middleware('admin.section:policies')->name('policies.index');
        Route::view('/audit', 'admin.audit.index')->middleware('admin.section:audit')->name('audit.index');
        Route::view('/cash-requests', 'admin.cash-requests.index')->middleware('admin.section:cash_requests')->name('cash-requests.index');
        Route::get('/cash-requests/{cashRequest}', function (CashRequest $cashRequest) {
            abort_unless(AdminPanel::canViewCashRequest(auth()->user(), $cashRequest), 403);

            return view('admin.cash-requests.show', compact('cashRequest'));
        })->middleware('admin.section:cash_requests')->name('cash-requests.show');
    });
});
