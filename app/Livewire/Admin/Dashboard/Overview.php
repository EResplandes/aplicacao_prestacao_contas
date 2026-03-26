<?php

namespace App\Livewire\Admin\Dashboard;

use App\Actions\Dashboard\GetDashboardMetricsAction;
use Livewire\Component;

class Overview extends Component
{
    public array $metrics = [];

    public function mount(GetDashboardMetricsAction $action): void
    {
        $this->metrics = $action->execute();
    }

    public function render()
    {
        return view('livewire.admin.dashboard.overview');
    }
}
