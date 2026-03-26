<?php

namespace App\Actions\Admin;

use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\AuditService;

class SaveExpenseCategoryAction
{
    public function __construct(
        private readonly AuditService $auditService,
    ) {
    }

    public function execute(?User $actor, ?ExpenseCategory $category, array $attributes): ExpenseCategory
    {
        $category ??= new ExpenseCategory();
        $isNew = ! $category->exists;
        $oldValues = $category->exists ? $category->only(['code', 'name', 'requires_attachment', 'max_amount', 'is_active']) : [];

        $category->fill([
            'code' => $attributes['code'],
            'name' => $attributes['name'],
            'requires_attachment' => (bool) $attributes['requires_attachment'],
            'max_amount' => $attributes['max_amount'] ?: null,
            'is_active' => (bool) $attributes['is_active'],
            'metadata' => $attributes['metadata'] ?? [],
        ])->save();

        $this->auditService->log(
            user: $actor,
            event: 'expense_category',
            action: $isNew ? 'created' : 'updated',
            auditable: $category,
            oldValues: $oldValues,
            newValues: $category->only(['code', 'name', 'requires_attachment', 'max_amount', 'is_active']),
        );

        return $category->fresh();
    }
}
