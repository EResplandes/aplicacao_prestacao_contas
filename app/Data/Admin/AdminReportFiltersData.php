<?php

namespace App\Data\Admin;

use Carbon\Carbon;

readonly class AdminReportFiltersData
{
    public function __construct(
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
        public ?int $userId = null,
        public ?int $costCenterId = null,
        public ?int $expenseCategoryId = null,
        public string $scope = 'all',
        public float $highValueThreshold = 500.0,
    ) {}

    public static function fromArray(array $data): self
    {
        $scope = in_array($data['scope'] ?? 'all', ['all', 'open', 'closed'], true)
            ? (string) $data['scope']
            : 'all';

        return new self(
            startDate: filled($data['start_date'] ?? null)
                ? Carbon::parse((string) $data['start_date'])->startOfDay()
                : null,
            endDate: filled($data['end_date'] ?? null)
                ? Carbon::parse((string) $data['end_date'])->endOfDay()
                : null,
            userId: filled($data['user_id'] ?? null) ? (int) $data['user_id'] : null,
            costCenterId: filled($data['cost_center_id'] ?? null) ? (int) $data['cost_center_id'] : null,
            expenseCategoryId: filled($data['expense_category_id'] ?? null) ? (int) $data['expense_category_id'] : null,
            scope: $scope,
            highValueThreshold: max((float) ($data['high_value_threshold'] ?? 500), 0),
        );
    }
}
