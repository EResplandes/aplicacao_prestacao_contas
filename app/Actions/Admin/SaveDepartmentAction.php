<?php

namespace App\Actions\Admin;

use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;

class SaveDepartmentAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?Department $department, array $attributes): Department
    {
        $department ??= new Department();
        $isNew = ! $department->exists;
        $oldValues = $department->exists ? $department->only(['company_id', 'code', 'name', 'manager_user_id', 'is_active']) : [];

        $department->fill([
            'company_id' => $attributes['company_id'] ?: null,
            'code' => $attributes['code'],
            'name' => $attributes['name'],
            'manager_user_id' => $attributes['manager_user_id'] ?: null,
            'is_active' => (bool) $attributes['is_active'],
            'metadata' => $attributes['metadata'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'department',
            action: $isNew ? 'created' : 'updated',
            auditable: $department,
            oldValues: $oldValues,
            newValues: $department->only(['company_id', 'code', 'name', 'manager_user_id', 'is_active']),
        );

        return $department->fresh();
    }
}
