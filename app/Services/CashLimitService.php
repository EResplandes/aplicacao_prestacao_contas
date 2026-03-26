<?php

namespace App\Services;

use App\Enums\CashRequestStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashLimitRule;
use App\Models\CashRequest;
use App\Models\User;

class CashLimitService
{
    public function ensureCanCreate(
        User $user,
        float $requestedAmount,
        ?int $departmentId = null,
        ?int $costCenterId = null,
    ): void {
        $rules = CashLimitRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user, $departmentId, $costCenterId): void {
                $query
                    ->orWhere(fn ($sub) => $sub->where('scope_type', 'user')->where('scope_id', $user->id))
                    ->orWhere(fn ($sub) => $sub->where('scope_type', 'department')->where('scope_id', $departmentId))
                    ->orWhere(fn ($sub) => $sub->where('scope_type', 'cost_center')->where('scope_id', $costCenterId));

                foreach ($user->roles as $role) {
                    $query->orWhere(fn ($sub) => $sub->where('scope_type', 'role')->where('scope_id', $role->id));
                }
            })
            ->get();

        $openRequestCount = CashRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', CashRequestStatus::openValues())
            ->count();

        $maxOpen = $rules->pluck('max_open_requests')->filter()->min()
            ?? (bool) config('cash_management.default_block_new_request_when_open') ? 1 : null;

        if ($maxOpen !== null && $openRequestCount >= $maxOpen) {
            throw new BusinessRuleViolation('Existe uma prestacao de contas em aberto para este usuario.');
        }

        $maxAmount = $rules->pluck('max_amount')->filter()->min();

        if ($maxAmount !== null && $requestedAmount > (float) $maxAmount) {
            throw new BusinessRuleViolation('O valor solicitado ultrapassa o limite permitido.');
        }
    }
}
