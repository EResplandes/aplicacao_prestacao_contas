<?php

namespace App\Actions\Admin;

use App\Models\Company;
use App\Models\User;
use App\Services\AuditService;

class SaveCompanyAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?Company $company, array $attributes): Company
    {
        $company ??= new Company();
        $isNew = ! $company->exists;
        $oldValues = $company->exists ? $company->only(['code', 'legal_name', 'trade_name', 'tax_id', 'is_active']) : [];

        $company->fill([
            'code' => $attributes['code'],
            'legal_name' => $attributes['legal_name'],
            'trade_name' => $attributes['trade_name'],
            'tax_id' => $attributes['tax_id'] ?: null,
            'is_active' => (bool) $attributes['is_active'],
            'metadata' => $attributes['metadata'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'company',
            action: $isNew ? 'created' : 'updated',
            auditable: $company,
            oldValues: $oldValues,
            newValues: $company->only(['code', 'legal_name', 'trade_name', 'tax_id', 'is_active']),
        );

        return $company->fresh();
    }
}
