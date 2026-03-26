<?php

namespace App\Livewire\Admin\CashRequests;

use App\Models\CashRequest;
use App\Models\Department;
use App\Repositories\CashRequestRepository;
use App\Support\AdminPanel;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $status = '';

    public string $search = '';

    public string $departmentPublicId = '';

    public int $perPage = 15;

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(CashRequestRepository $repository)
    {
        $viewer = auth()->user();

        $cashRequests = $repository->paginateForAdmin([
            'status' => $this->status ?: null,
            'search' => $this->search ?: null,
            'department_public_id' => $this->departmentPublicId ?: null,
        ], $this->perPage, $viewer);

        return view('livewire.admin.cash-requests.index', [
            'cashRequests' => $cashRequests,
            'departments' => Department::query()
                ->whereIn(
                    'id',
                    AdminPanel::scopeCashRequests(CashRequest::query(), $viewer)->select('department_id')
                )
                ->orderBy('name')
                ->get(),
        ]);
    }
}
