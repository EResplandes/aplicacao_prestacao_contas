<?php

namespace App\Livewire\Admin\Reports;

use App\Actions\Reports\GetAdminReportsAction;
use App\Data\Admin\AdminReportFiltersData;
use App\Models\CostCenter;
use App\Models\ExpenseCategory;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $startDate = '';

    public string $endDate = '';

    public string $userId = '';

    public string $costCenterId = '';

    public string $expenseCategoryId = '';

    public string $scope = 'all';

    public string $highValueThreshold = '500';

    public int $perPage = 10;

    public function updated(string $property): void
    {
        if (in_array($property, [
            'startDate',
            'endDate',
            'userId',
            'costCenterId',
            'expenseCategoryId',
            'scope',
            'highValueThreshold',
        ], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset([
            'startDate',
            'endDate',
            'userId',
            'costCenterId',
            'expenseCategoryId',
        ]);

        $this->scope = 'all';
        $this->highValueThreshold = '500';
        $this->resetPage();
    }

    public function render(GetAdminReportsAction $action)
    {
        $reportData = $action->execute(
            AdminReportFiltersData::fromArray([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'user_id' => $this->userId,
                'cost_center_id' => $this->costCenterId,
                'expense_category_id' => $this->expenseCategoryId,
                'scope' => $this->scope,
                'high_value_threshold' => $this->highValueThreshold,
            ]),
            $this->perPage,
            $this->getPage(),
        );

        return view('livewire.admin.reports.index', [
            'report' => $reportData,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name', 'code']),
            'expenseCategories' => ExpenseCategory::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }
}
