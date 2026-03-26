<?php

namespace App\Livewire\Admin\Security;

use App\Models\SecurityEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $channel = '';

    public string $severity = '';

    public string $eventType = '';

    public string $timeframe = '24h';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingChannel(): void
    {
        $this->resetPage();
    }

    public function updatingSeverity(): void
    {
        $this->resetPage();
    }

    public function updatingEventType(): void
    {
        $this->resetPage();
    }

    public function updatingTimeframe(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = $this->filteredQuery();

        $events = (clone $query)
            ->with('user')
            ->latest('detected_at')
            ->paginate(12);

        $metricsSource = $this->baseQuery();

        $metrics = [
            'total' => (clone $metricsSource)->count(),
            'failed_logins' => (clone $metricsSource)->where('event_type', 'login_failed')->count(),
            'lockouts' => (clone $metricsSource)->where('event_type', 'login_lockout')->count(),
            'rate_limits' => (clone $metricsSource)->where('event_type', 'rate_limited')->count(),
            'blocked_origins' => (clone $metricsSource)->where('event_type', 'untrusted_origin_blocked')->count(),
            'suspicious_probes' => (clone $metricsSource)->where('event_type', 'suspicious_probe')->count(),
            'critical' => (clone $metricsSource)->whereIn('severity', ['high', 'critical'])->count(),
        ];

        $topIps = (clone $metricsSource)
            ->selectRaw('ip_address, COUNT(*) as aggregate')
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->orderByDesc('aggregate')
            ->limit(5)
            ->get();

        return view('livewire.admin.security.index', [
            'events' => $events,
            'metrics' => $metrics,
            'topIps' => $topIps,
            'eventTypeOptions' => [
                'login_failed',
                'login_lockout',
                'rate_limited',
                'untrusted_origin_blocked',
                'suspicious_probe',
            ],
        ]);
    }

    private function filteredQuery(): Builder
    {
        return $this->baseQuery()
            ->when($this->channel !== '', fn (Builder $query) => $query->where('channel', $this->channel))
            ->when($this->severity !== '', fn (Builder $query) => $query->where('severity', $this->severity))
            ->when($this->eventType !== '', fn (Builder $query) => $query->where('event_type', $this->eventType))
            ->when($this->search !== '', function (Builder $query): void {
                $search = trim($this->search);

                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->where('identifier', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('path', 'like', "%{$search}%")
                        ->orWhere('event_type', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            });
    }

    private function baseQuery(): Builder
    {
        $since = match ($this->timeframe) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        return SecurityEvent::query()->where('detected_at', '>=', $since);
    }
}
