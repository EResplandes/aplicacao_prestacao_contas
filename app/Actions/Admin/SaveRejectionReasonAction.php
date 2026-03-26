<?php

namespace App\Actions\Admin;

use App\Models\RejectionReason;
use App\Models\User;
use App\Services\AuditService;

class SaveRejectionReasonAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?RejectionReason $reason, array $attributes): RejectionReason
    {
        $reason ??= new RejectionReason();
        $isNew = ! $reason->exists;
        $oldValues = $reason->exists ? $reason->only(['code', 'name', 'applies_to', 'is_active']) : [];

        $reason->fill([
            'code' => $attributes['code'],
            'name' => $attributes['name'],
            'applies_to' => $attributes['applies_to'],
            'is_active' => (bool) $attributes['is_active'],
            'metadata' => $attributes['metadata'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'rejection_reason',
            action: $isNew ? 'created' : 'updated',
            auditable: $reason,
            oldValues: $oldValues,
            newValues: $reason->only(['code', 'name', 'applies_to', 'is_active']),
        );

        return $reason->fresh();
    }
}
