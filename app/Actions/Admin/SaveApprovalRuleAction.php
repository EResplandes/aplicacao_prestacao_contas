<?php

namespace App\Actions\Admin;

use App\Models\ApprovalRule;
use App\Models\User;
use App\Services\AuditService;

class SaveApprovalRuleAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?ApprovalRule $rule, array $attributes): ApprovalRule
    {
        $rule ??= new ApprovalRule();
        $isNew = ! $rule->exists;
        $oldValues = $rule->exists ? $rule->only([
            'name',
            'stage',
            'company_id',
            'department_id',
            'cost_center_id',
            'min_amount',
            'max_amount',
            'required_approvals',
            'is_active',
        ]) : [];

        $rule->fill([
            'name' => $attributes['name'],
            'stage' => $attributes['stage'],
            'company_id' => $attributes['company_id'] ?: null,
            'department_id' => $attributes['department_id'] ?: null,
            'cost_center_id' => $attributes['cost_center_id'] ?: null,
            'min_amount' => $attributes['min_amount'] ?: null,
            'max_amount' => $attributes['max_amount'] ?: null,
            'required_approvals' => (int) $attributes['required_approvals'],
            'is_active' => (bool) $attributes['is_active'],
            'settings' => $attributes['settings'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'approval_rule',
            action: $isNew ? 'created' : 'updated',
            auditable: $rule,
            oldValues: $oldValues,
            newValues: $rule->only([
                'name',
                'stage',
                'company_id',
                'department_id',
                'cost_center_id',
                'min_amount',
                'max_amount',
                'required_approvals',
                'is_active',
            ]),
        );

        return $rule->fresh();
    }
}
