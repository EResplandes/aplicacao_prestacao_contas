<?php

namespace App\Policies;

use App\Models\CashRequest;
use App\Models\User;

class CashRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cash_requests.view_any') || $user->can('cash_requests.view_own');
    }

    public function view(User $user, CashRequest $cashRequest): bool
    {
        return $user->can('cash_requests.view_any')
            || $cashRequest->user_id === $user->id
            || $cashRequest->manager_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('cash_requests.create');
    }

    public function managerDecision(User $user, CashRequest $cashRequest): bool
    {
        return $user->can('cash_requests.manager_approve') && $cashRequest->manager_id === $user->id
            || $user->hasRole('admin');
    }

    public function financialDecision(User $user, CashRequest $cashRequest): bool
    {
        return $user->can('cash_requests.financial_approve') || $user->hasRole('admin');
    }

    public function release(User $user, CashRequest $cashRequest): bool
    {
        return $user->can('cash_requests.release') || $user->hasRole('admin');
    }

    public function respondRejection(User $user, CashRequest $cashRequest): bool
    {
        return $user->can('cash_requests.respond_rejection') && $cashRequest->user_id === $user->id;
    }

    public function closeAccountability(User $user, CashRequest $cashRequest): bool
    {
        return $cashRequest->user_id === $user->id || $user->hasRole('admin');
    }

    public function createExpense(User $user, CashRequest $cashRequest): bool
    {
        return $user->can('cash_expenses.create') && $cashRequest->user_id === $user->id;
    }

    public function viewStatement(User $user, CashRequest $cashRequest): bool
    {
        return $this->view($user, $cashRequest);
    }
}
