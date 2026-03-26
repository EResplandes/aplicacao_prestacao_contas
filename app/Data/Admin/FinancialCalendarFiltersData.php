<?php

namespace App\Data\Admin;

use Carbon\Carbon;

readonly class FinancialCalendarFiltersData
{
    public function __construct(
        public Carbon $month,
        public Carbon $selectedDate,
        public ?int $userId = null,
        public ?int $costCenterId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $month = filled($data['month'] ?? null)
            ? Carbon::createFromFormat('Y-m', (string) $data['month'])->startOfMonth()
            : now()->startOfMonth();

        $selectedDate = filled($data['selected_date'] ?? null)
            ? Carbon::parse((string) $data['selected_date'])
            : now();

        if (! $selectedDate->isSameMonth($month)) {
            $selectedDate = $month->copy();
        }

        return new self(
            month: $month,
            selectedDate: $selectedDate->startOfDay(),
            userId: filled($data['user_id'] ?? null) ? (int) $data['user_id'] : null,
            costCenterId: filled($data['cost_center_id'] ?? null) ? (int) $data['cost_center_id'] : null,
        );
    }
}
