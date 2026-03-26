<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Services\AuditService;
use Spatie\Permission\Models\Role;

class SaveUserAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?User $user, array $attributes): User
    {
        $user ??= new User();
        $isNew = ! $user->exists;
        $oldValues = $user->exists ? $user->only([
            'name',
            'email',
            'employee_code',
            'company_id',
            'department_id',
            'cost_center_id',
            'manager_id',
            'is_active',
        ]) : [];
        $oldValues['role'] = $user->exists ? $user->getRoleNames()->first() : null;

        $payload = [
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'employee_code' => $attributes['employee_code'] ?: null,
            'company_id' => $attributes['company_id'] ?: null,
            'department_id' => $attributes['department_id'] ?: null,
            'cost_center_id' => $attributes['cost_center_id'] ?: null,
            'manager_id' => $attributes['manager_id'] ?: null,
            'is_active' => (bool) $attributes['is_active'],
        ];

        if (! empty($attributes['password'])) {
            $payload['password'] = $attributes['password'];
        }

        $user->fill($payload)->save();

        if (! empty($attributes['role'])) {
            $role = Role::findByName($attributes['role'], 'web');
            $user->syncRoles([$role]);
        }

        $newValues = $user->only([
            'name',
            'email',
            'employee_code',
            'company_id',
            'department_id',
            'cost_center_id',
            'manager_id',
            'is_active',
        ]);
        $newValues['role'] = $user->getRoleNames()->first();

        $this->auditService->log(
            user: $actor,
            event: 'user',
            action: $isNew ? 'created' : 'updated',
            auditable: $user,
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return $user->fresh(['company', 'department', 'costCenter', 'manager']);
    }
}
