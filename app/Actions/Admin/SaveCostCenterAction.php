<?php

namespace App\Actions\Admin;

use App\Models\CostCenter;
use App\Models\User;
use App\Services\AuditService;

class SaveCostCenterAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?CostCenter $costCenter, array $attributes): CostCenter
    {
        $costCenter ??= new CostCenter();
        $isNew = ! $costCenter->exists;
        $oldValues = $costCenter->exists ? $costCenter->only(['company_id', 'department_id', 'code', 'name', 'is_active']) : [];

        $costCenter->fill([
            'company_id' => $attributes['company_id'] ?: null,
            'department_id' => $attributes['department_id'] ?: null,
            'code' => $attributes['code'],
            'name' => $attributes['name'],
            'is_active' => (bool) $attributes['is_active'],
            'metadata' => $attributes['metadata'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'cost_center',
            action: $isNew ? 'created' : 'updated',
            auditable: $costCenter,
            oldValues: $oldValues,
            newValues: $costCenter->only(['company_id', 'department_id', 'code', 'name', 'is_active']),
        );

        return $costCenter->fresh();
    }
}
