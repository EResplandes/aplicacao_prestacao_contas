<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\CashExpenseController;
use App\Http\Controllers\Api\V1\CashRequestController;
use App\Http\Controllers\Api\V1\ExpenseCategoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('/auth/refresh', [AuthController::class, 'refresh'])->middleware('throttle:auth-refresh');

    Route::middleware(['auth:sanctum', 'throttle:authenticated-api'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::post('/profile/onboarding', [ProfileController::class, 'completeFirstAccess']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('/balance/current', [BalanceController::class, 'show']);
        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index']);

        Route::get('/cash-requests', [CashRequestController::class, 'index']);
        Route::post('/cash-requests', [CashRequestController::class, 'store']);
        Route::get('/cash-requests/{cashRequest}', [CashRequestController::class, 'show']);
        Route::post('/cash-requests/{cashRequest}/manager-decision', [CashRequestController::class, 'managerDecision'])->middleware('throttle:cash-approval');
        Route::post('/cash-requests/{cashRequest}/financial-decision', [CashRequestController::class, 'financialDecision'])->middleware('throttle:cash-approval');
        Route::post('/cash-requests/{cashRequest}/release', [CashRequestController::class, 'release'])->middleware('throttle:cash-approval');
        Route::post('/cash-requests/{cashRequest}/rejections/respond', [CashRequestController::class, 'respondRejection']);
        Route::get('/cash-requests/{cashRequest}/statement', [CashRequestController::class, 'statement']);

        Route::get('/expenses', [CashExpenseController::class, 'index']);
        Route::post('/cash-requests/{cashRequest}/expenses', [CashExpenseController::class, 'store']);
        Route::post('/sync/pending', [SyncController::class, 'store']);
    });
});
