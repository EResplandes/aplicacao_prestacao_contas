<?php

namespace App\Livewire\Admin\CashMonitoring;

use App\Enums\CashExpenseStatus;
use App\Enums\CashRequestStatus;
use App\Models\CashExpense;
use App\Models\CashRequest;
use App\Support\AdminPanel;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $status = 'open';

    public string $search = '';

    public int $perPage = 8;

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $cashRequests = $this->applyFilters(
            $this->monitoringBaseQuery()
                ->with([
                    'user',
                    'department',
                    'costCenter',
                    'manager',
                    'expenses' => fn ($query) => $query->with('category')->latest('spent_at'),
                ])
                ->withCount([
                    'expenses',
                    'expenses as pending_expenses_count' => fn ($query) => $query->whereIn('status', [CashExpenseStatus::PENDING, CashExpenseStatus::SUBMITTED]),
                    'expenses as approved_expenses_count' => fn ($query) => $query->where('status', CashExpenseStatus::APPROVED),
                    'expenses as flagged_expenses_count' => fn ($query) => $query->where('status', CashExpenseStatus::FLAGGED),
                ])
                ->latest('updated_at')
        )
            ->paginate($this->perPage)
            ->withQueryString();

        return view('livewire.admin.cash-monitoring.index', [
            'cashRequests' => $cashRequests,
            'openCashCount' => $this->monitoringBaseQuery()
                ->whereIn('status', [
                    CashRequestStatus::RELEASED,
                    CashRequestStatus::PARTIALLY_ACCOUNTED,
                    CashRequestStatus::FULLY_ACCOUNTED,
                ])
                ->count(),
            'closedCashCount' => $this->monitoringBaseQuery()
                ->where('status', CashRequestStatus::CLOSED)
                ->count(),
            'totalSpent' => (float) $this->monitoringBaseQuery()->sum('spent_amount'),
            'totalAvailable' => (float) $this->monitoringBaseQuery()->sum('available_amount'),
            'flaggedExpenseCount' => AdminPanel::scopeCashExpenses(CashExpense::query(), auth()->user())
                ->where('status', CashExpenseStatus::FLAGGED)
                ->whereHas('cashRequest', function (Builder $query): void {
                    $query->whereIn('status', [
                        CashRequestStatus::RELEASED,
                        CashRequestStatus::PARTIALLY_ACCOUNTED,
                        CashRequestStatus::FULLY_ACCOUNTED,
                        CashRequestStatus::CLOSED,
                    ]);
                })
                ->count(),
        ]);
    }

    private function monitoringBaseQuery(): Builder
    {
        return AdminPanel::scopeCashRequests(CashRequest::query(), auth()->user())->whereIn('status', [
            CashRequestStatus::RELEASED,
            CashRequestStatus::PARTIALLY_ACCOUNTED,
            CashRequestStatus::FULLY_ACCOUNTED,
            CashRequestStatus::CLOSED,
        ]);
    }

    private function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->status === 'open', function (Builder $query): void {
                $query->whereIn('status', [
                    CashRequestStatus::RELEASED,
                    CashRequestStatus::PARTIALLY_ACCOUNTED,
                    CashRequestStatus::FULLY_ACCOUNTED,
                ]);
            })
            ->when($this->status === 'released', function (Builder $query): void {
                $query->where('status', CashRequestStatus::RELEASED);
            })
            ->when($this->status === 'accountability', function (Builder $query): void {
                $query->whereIn('status', [
                    CashRequestStatus::PARTIALLY_ACCOUNTED,
                    CashRequestStatus::FULLY_ACCOUNTED,
                ]);
            })
            ->when($this->status === 'closed', function (Builder $query): void {
                $query->where('status', CashRequestStatus::CLOSED);
            })
            ->when($this->search !== '', function (Builder $query): void {
                $search = $this->search;

                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('request_number', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('costCenter', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('expenses', function (Builder $relation) use ($search): void {
                            $relation
                                ->where('description', 'like', "%{$search}%")
                                ->orWhere('vendor_name', 'like', "%{$search}%");
                        });
                });
            });
    }
}
