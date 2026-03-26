<?php

namespace App\Livewire\Admin\Audit;

use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($this->search, function ($query): void {
                $query->where(function ($nested): void {
                    $nested
                        ->where('event', 'like', "%{$this->search}%")
                        ->orWhere('action', 'like', "%{$this->search}%");
                });
            })
            ->latest('performed_at')
            ->paginate(12);

        return view('livewire.admin.audit.index', [
            'logs' => $logs,
        ]);
    }
}
