<?php

namespace App\Support;

use App\Models\CashRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminPanel
{
    public static function canAccess(?User $user): bool
    {
        return self::isAdmin($user)
            || self::isFinance($user)
            || self::isManager($user);
    }

    public static function isAdmin(?User $user): bool
    {
        return $user?->hasRole('admin') ?? false;
    }

    public static function isFinance(?User $user): bool
    {
        return $user?->hasRole('finance') ?? false;
    }

    public static function isManager(?User $user): bool
    {
        return $user?->hasRole('manager') ?? false;
    }

    public static function homeRouteFor(?User $user): string
    {
        return self::isManager($user)
            ? 'admin.cash-requests.index'
            : 'admin.dashboard';
    }

    public static function canAccessSection(?User $user, string $section): bool
    {
        if (self::isAdmin($user)) {
            return true;
        }

        if (self::isFinance($user)) {
            return in_array($section, [
                'dashboard',
                'reports',
                'financial_calendar',
                'approvals',
                'cash_monitoring',
                'cash_requests',
            ], true);
        }

        if (self::isManager($user)) {
            return in_array($section, [
                'cash_requests',
                'cash_monitoring',
            ], true);
        }

        return false;
    }

    public static function scopeCashRequests(Builder $query, ?User $user): Builder
    {
        if (self::isAdmin($user) || self::isFinance($user)) {
            return $query;
        }

        if (self::isManager($user)) {
            return $query->where(function (Builder $nested) use ($user): void {
                $nested
                    ->where('manager_id', $user->id)
                    ->orWhereHas('user', fn (Builder $relation) => $relation->where('manager_id', $user->id));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    public static function scopeCashExpenses(Builder $query, ?User $user): Builder
    {
        if (self::isAdmin($user) || self::isFinance($user)) {
            return $query;
        }

        if (self::isManager($user)) {
            return $query->whereHas('cashRequest', fn (Builder $cashRequests) => self::scopeCashRequests($cashRequests, $user));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function canViewCashRequest(?User $user, CashRequest $cashRequest): bool
    {
        if (self::isAdmin($user) || self::isFinance($user)) {
            return true;
        }

        return self::isManagerResponsibleFor($user, $cashRequest);
    }

    public static function canManageManagerDecision(?User $user, CashRequest $cashRequest): bool
    {
        if (self::isAdmin($user) || self::isFinance($user)) {
            return true;
        }

        return self::isManagerResponsibleFor($user, $cashRequest);
    }

    public static function canManageFinancialDecision(?User $user): bool
    {
        return self::isAdmin($user) || self::isFinance($user);
    }

    public static function canAccessSecurity(?User $user): bool
    {
        return self::isAdmin($user);
    }

    public static function canRegisterRelease(?User $user): bool
    {
        return self::canManageFinancialDecision($user);
    }

    public static function canReviewExpense(?User $user): bool
    {
        return self::isAdmin($user) || self::isFinance($user);
    }

    private static function isManagerResponsibleFor(?User $user, CashRequest $cashRequest): bool
    {
        if (! self::isManager($user) || ! $user) {
            return false;
        }

        return (int) $cashRequest->manager_id === (int) $user->id
            || (int) $cashRequest->user?->manager_id === (int) $user->id;
    }
}
