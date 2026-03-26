<?php

namespace App\Livewire\Admin\Approvals;

use App\Enums\CashRequestStatus;
use App\Models\CashRequest;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Queue extends Component
{
    use WithPagination;

    public string $stage = 'all';

    public string $search = '';

    public int $perPage = 10;

    public function updatingStage(): void
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
            $this->pendingQueueBaseQuery()
                ->with(['user', 'department', 'costCenter', 'manager'])
                ->latest('submitted_at')
        )
            ->paginate($this->perPage)
            ->withQueryString();

        return view('livewire.admin.approvals.queue', [
            'cashRequests' => $cashRequests,
            'totalPending' => $this->pendingQueueBaseQuery()->count(),
            'managerPending' => $this->pendingQueueBaseQuery()
                ->where('status', CashRequestStatus::AWAITING_MANAGER_APPROVAL)
                ->count(),
            'financialPending' => $this->pendingQueueBaseQuery()
                ->where('status', CashRequestStatus::AWAITING_FINANCIAL_APPROVAL)
                ->count(),
            'stalePending' => $this->pendingQueueBaseQuery()
                ->where('submitted_at', '<=', now()->subDay())
                ->count(),
        ]);
    }

    private function pendingQueueBaseQuery(): Builder
    {
        return CashRequest::query()->whereIn('status', [
            CashRequestStatus::AWAITING_MANAGER_APPROVAL,
            CashRequestStatus::AWAITING_FINANCIAL_APPROVAL,
        ]);
    }

    private function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->stage === 'manager', function (Builder $query): void {
                $query->where('status', CashRequestStatus::AWAITING_MANAGER_APPROVAL);
            })
            ->when($this->stage === 'financial', function (Builder $query): void {
                $query->where('status', CashRequestStatus::AWAITING_FINANCIAL_APPROVAL);
            })
            ->when($this->search !== '', function (Builder $query): void {
                $search = $this->search;

                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('request_number', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('justification', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('department', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('costCenter', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"));
                });
            });
    }
}
