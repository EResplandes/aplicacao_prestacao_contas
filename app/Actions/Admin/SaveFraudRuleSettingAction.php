<?php

namespace App\Actions\Admin;

use App\Models\FraudRuleSetting;
use App\Models\User;
use App\Services\AuditService;

class SaveFraudRuleSettingAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?FraudRuleSetting $rule, array $attributes): FraudRuleSetting
    {
        $rule ??= new FraudRuleSetting();
        $isNew = ! $rule->exists;
        $oldValues = $rule->exists ? $rule->only(['code', 'name', 'severity', 'is_active']) : [];

        $rule->fill([
            'code' => $attributes['code'],
            'name' => $attributes['name'],
            'severity' => $attributes['severity'],
            'is_active' => (bool) $attributes['is_active'],
            'settings' => $attributes['settings'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'fraud_rule_setting',
            action: $isNew ? 'created' : 'updated',
            auditable: $rule,
            oldValues: $oldValues,
            newValues: $rule->only(['code', 'name', 'severity', 'is_active']),
        );

        return $rule->fresh();
    }
}
