<?php

namespace App\Livewire\Admin\FinancialCalendar;

use App\Actions\Reports\GetFinancialCalendarAction;
use App\Data\Admin\FinancialCalendarFiltersData;
use App\Models\CostCenter;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class Index extends Component
{
    public string $month;

    public string $selectedDate;

    public string $userId = '';

    public string $costCenterId = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->selectedDate = now()->toDateString();
    }

    public function previousMonth(): void
    {
        $month = Carbon::createFromFormat('Y-m', $this->month)->subMonth();
        $this->month = $month->format('Y-m');
        $this->selectedDate = $month->startOfMonth()->toDateString();
    }

    public function nextMonth(): void
    {
        $month = Carbon::createFromFormat('Y-m', $this->month)->addMonth();
        $this->month = $month->format('Y-m');
        $this->selectedDate = $month->startOfMonth()->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = Carbon::parse($date)->toDateString();
    }

    public function updatedMonth(): void
    {
        $month = Carbon::createFromFormat('Y-m', $this->month);
        $this->selectedDate = $month->startOfMonth()->toDateString();
    }

    public function render(GetFinancialCalendarAction $action)
    {
        $calendar = $action->execute(
            FinancialCalendarFiltersData::fromArray([
                'month' => $this->month,
                'selected_date' => $this->selectedDate,
                'user_id' => $this->userId,
                'cost_center_id' => $this->costCenterId,
            ]),
        );

        return view('livewire.admin.financial-calendar.index', [
            'calendar' => $calendar,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name', 'code']),
            'weekdays' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        ]);
    }
}
