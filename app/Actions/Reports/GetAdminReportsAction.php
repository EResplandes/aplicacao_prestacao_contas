<?php

namespace App\Actions\Reports;

use App\Data\Admin\AdminReportFiltersData;
use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Models\CashExpense;
use App\Models\CashRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GetAdminReportsAction
{
    public function execute(AdminReportFiltersData $filters, int $perPage = 12, int $page = 1): array
    {
        $cashRequestQuery = $this->cashRequestQuery($filters);
        $expenseQuery = $this->expenseQuery($filters);

        return [
            'summary' => [
                'total_requests' => (clone $cashRequestQuery)->count(),
                'open_requests' => (clone $cashRequestQuery)
                    ->whereIn('status', CashRequestStatus::openValues())
                    ->count(),
                'closed_requests' => (clone $cashRequestQuery)
                    ->where('status', CashRequestStatus::CLOSED)
                    ->count(),
                'total_released' => (float) (clone $cashRequestQuery)->sum('released_amount'),
                'total_spent' => (float) (clone $cashRequestQuery)->sum('spent_amount'),
                'average_request' => round((float) (clone $cashRequestQuery)->avg('requested_amount'), 2),
            ],
            'cash_requests' => $this->cashRequestsPaginator($cashRequestQuery, $perPage, $page),
            'open_requests' => (clone $cashRequestQuery)
                ->with(['user', 'costCenter', 'department'])
                ->whereIn('status', CashRequestStatus::openValues())
                ->orderBy('due_accountability_at')
                ->limit(6)
                ->get(),
            'closed_requests' => (clone $cashRequestQuery)
                ->with(['user', 'costCenter', 'department'])
                ->where('status', CashRequestStatus::CLOSED)
                ->latest('closed_at')
                ->limit(6)
                ->get(),
            'user_totals' => $this->userTotals($filters),
            'cost_center_totals' => $this->costCenterTotals($filters),
            'category_totals' => $this->categoryTotals($filters),
            'high_value_expenses' => (clone $expenseQuery)
                ->with(['user', 'category', 'cashRequest.costCenter'])
                ->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('amount', '>=', $filters->highValueThreshold)
                        ->orWhere('status', CashExpenseStatus::FLAGGED);
                })
                ->orderByDesc('amount')
                ->limit(10)
                ->get(),
        ];
    }

    private function cashRequestsPaginator(Builder $query, int $perPage, int $page): LengthAwarePaginator
    {
        return (clone $query)
            ->with(['user', 'costCenter', 'department'])
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    private function userTotals(AdminReportFiltersData $filters): Collection
    {
        $openStatuses = collect(CashRequestStatus::openValues())
            ->map(fn (string $value) => "'{$value}'")
            ->implode(', ');

        return $this->cashRequestQuery($filters)
            ->select('user_id')
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw('COALESCE(SUM(released_amount), 0) as total_released')
            ->selectRaw('COALESCE(SUM(spent_amount), 0) as total_spent')
            ->selectRaw("SUM(CASE WHEN status IN ({$openStatuses}) THEN 1 ELSE 0 END) as open_requests")
            ->with('user:id,name')
            ->groupBy('user_id')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get();
    }

    private function costCenterTotals(AdminReportFiltersData $filters): Collection
    {
        return $this->cashRequestQuery($filters)
            ->select('cost_center_id')
            ->selectRaw('COUNT(*) as total_requests')
            ->selectRaw('COALESCE(SUM(released_amount), 0) as total_released')
            ->selectRaw('COALESCE(SUM(spent_amount), 0) as total_spent')
            ->with('costCenter:id,name,code')
            ->groupBy('cost_center_id')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get();
    }

    private function categoryTotals(AdminReportFiltersData $filters): Collection
    {
        return $this->expenseQuery($filters)
            ->select('expense_category_id')
            ->selectRaw('COUNT(*) as total_expenses')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
            ->selectRaw('COALESCE(AVG(amount), 0) as average_amount')
            ->with('category:id,name,code')
            ->groupBy('expense_category_id')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();
    }

    private function cashRequestQuery(AdminReportFiltersData $filters): Builder
    {
        return CashRequest::query()
            ->when($filters->startDate, fn (Builder $query) => $query->where('created_at', '>=', $filters->startDate))
            ->when($filters->endDate, fn (Builder $query) => $query->where('created_at', '<=', $filters->endDate))
            ->when($filters->userId, fn (Builder $query) => $query->where('user_id', $filters->userId))
            ->when($filters->costCenterId, fn (Builder $query) => $query->where('cost_center_id', $filters->costCenterId))
            ->when($filters->expenseCategoryId, function (Builder $query) use ($filters): void {
                $query->whereHas('expenses', fn (Builder $relation) => $relation->where('expense_category_id', $filters->expenseCategoryId));
            })
            ->when($filters->scope === 'open', fn (Builder $query) => $query->whereIn('status', CashRequestStatus::openValues()))
            ->when($filters->scope === 'closed', fn (Builder $query) => $query->where('status', CashRequestStatus::CLOSED));
    }

    private function expenseQuery(AdminReportFiltersData $filters): Builder
    {
        return CashExpense::query()
            ->whereHas('cashRequest', function (Builder $query) use ($filters): void {
                $query
                    ->when($filters->startDate, fn (Builder $nested) => $nested->where('created_at', '>=', $filters->startDate))
                    ->when($filters->endDate, fn (Builder $nested) => $nested->where('created_at', '<=', $filters->endDate))
                    ->when($filters->userId, fn (Builder $nested) => $nested->where('user_id', $filters->userId))
                    ->when($filters->costCenterId, fn (Builder $nested) => $nested->where('cost_center_id', $filters->costCenterId))
                    ->when($filters->scope === 'open', fn (Builder $nested) => $nested->whereIn('status', CashRequestStatus::openValues()))
                    ->when($filters->scope === 'closed', fn (Builder $nested) => $nested->where('status', CashRequestStatus::CLOSED));
            })
            ->when($filters->expenseCategoryId, fn (Builder $query) => $query->where('expense_category_id', $filters->expenseCategoryId))
            ->when($filters->startDate, fn (Builder $query) => $query->where('spent_at', '>=', $filters->startDate))
            ->when($filters->endDate, fn (Builder $query) => $query->where('spent_at', '<=', $filters->endDate));
    }
}
