<?php

namespace App\Actions\Admin;

use App\Models\CashLimitRule;
use App\Models\User;
use App\Services\AuditService;

class SaveCashLimitRuleAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?CashLimitRule $rule, array $attributes): CashLimitRule
    {
        $rule ??= new CashLimitRule();
        $isNew = ! $rule->exists;
        $oldValues = $rule->exists ? $rule->only([
            'name',
            'scope_type',
            'scope_id',
            'max_amount',
            'max_open_requests',
            'block_new_if_pending',
            'is_active',
        ]) : [];

        $rule->fill([
            'name' => $attributes['name'],
            'scope_type' => $attributes['scope_type'],
            'scope_id' => $attributes['scope_id'] ?: null,
            'max_amount' => $attributes['max_amount'] ?: null,
            'max_open_requests' => (int) $attributes['max_open_requests'],
            'block_new_if_pending' => (bool) $attributes['block_new_if_pending'],
            'is_active' => (bool) $attributes['is_active'],
            'settings' => $attributes['settings'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'cash_limit_rule',
            action: $isNew ? 'created' : 'updated',
            auditable: $rule,
            oldValues: $oldValues,
            newValues: $rule->only([
                'name',
                'scope_type',
                'scope_id',
                'max_amount',
                'max_open_requests',
                'block_new_if_pending',
                'is_active',
            ]),
        );

        return $rule->fresh();
    }
}
