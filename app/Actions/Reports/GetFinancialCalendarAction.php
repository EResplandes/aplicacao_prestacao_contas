<?php

namespace App\Actions\Reports;

use App\Data\Admin\FinancialCalendarFiltersData;
use App\Enums\CashRequestStatus;
use App\Models\CashRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetFinancialCalendarAction
{
    public function execute(FinancialCalendarFiltersData $filters): array
    {
        $monthStart = $filters->month->copy()->startOfMonth();
        $monthEnd = $filters->month->copy()->endOfMonth();

        $monthlyDueRequests = $this->dueRequestsQuery($filters)
            ->whereBetween('due_accountability_at', [$monthStart, $monthEnd])
            ->orderBy('due_accountability_at')
            ->get();

        $monthlyClosedRequests = $this->closedRequestsQuery($filters)
            ->whereBetween('closed_at', [$monthStart, $monthEnd])
            ->orderBy('closed_at')
            ->get();

        $events = $this->buildEvents($monthlyDueRequests, $monthlyClosedRequests);
        $eventsByDate = $events->groupBy('date');

        return [
            'month_label' => $monthStart->translatedFormat('F \\d\\e Y'),
            'summary' => [
                'due_this_month' => $monthlyDueRequests->count(),
                'due_this_week' => $this->dueRequestsQuery($filters)
                    ->whereBetween('due_accountability_at', [now()->startOfDay(), now()->copy()->addDays(7)->endOfDay()])
                    ->count(),
                'overdue' => $this->dueRequestsQuery($filters)
                    ->where('due_accountability_at', '<', now())
                    ->count(),
                'closed_this_month' => $monthlyClosedRequests->count(),
            ],
            'calendar_days' => $this->buildCalendarDays($monthStart, $filters->selectedDate, $eventsByDate),
            'selected_date_label' => $filters->selectedDate->translatedFormat('d \\d\\e F'),
            'selected_day_events' => collect($eventsByDate->get($filters->selectedDate->toDateString(), []))->values(),
            'upcoming_due_requests' => $this->dueRequestsQuery($filters)
                ->where('due_accountability_at', '>=', now()->startOfDay())
                ->orderBy('due_accountability_at')
                ->limit(8)
                ->get(),
            'overdue_requests' => $this->dueRequestsQuery($filters)
                ->where('due_accountability_at', '<', now())
                ->orderBy('due_accountability_at')
                ->limit(8)
                ->get(),
        ];
    }

    private function dueRequestsQuery(FinancialCalendarFiltersData $filters): Builder
    {
        return CashRequest::query()
            ->with(['user', 'costCenter', 'department'])
            ->whereIn('status', [
                CashRequestStatus::RELEASED,
                CashRequestStatus::PARTIALLY_ACCOUNTED,
                CashRequestStatus::FULLY_ACCOUNTED,
            ])
            ->whereNotNull('due_accountability_at')
            ->when($filters->userId, fn (Builder $query) => $query->where('user_id', $filters->userId))
            ->when($filters->costCenterId, fn (Builder $query) => $query->where('cost_center_id', $filters->costCenterId));
    }

    private function closedRequestsQuery(FinancialCalendarFiltersData $filters): Builder
    {
        return CashRequest::query()
            ->with(['user', 'costCenter', 'department'])
            ->where('status', CashRequestStatus::CLOSED)
            ->whereNotNull('closed_at')
            ->when($filters->userId, fn (Builder $query) => $query->where('user_id', $filters->userId))
            ->when($filters->costCenterId, fn (Builder $query) => $query->where('cost_center_id', $filters->costCenterId));
    }

    private function buildEvents(Collection $dueRequests, Collection $closedRequests): Collection
    {
        $dueEvents = $dueRequests->map(function (CashRequest $cashRequest): array {
            $isOverdue = $cashRequest->due_accountability_at?->isPast() ?? false;
            $isToday = $cashRequest->due_accountability_at?->isToday() ?? false;

            return [
                'date' => $cashRequest->due_accountability_at?->toDateString(),
                'time' => $cashRequest->due_accountability_at?->format('H:i') ?? '--:--',
                'title' => $cashRequest->request_number,
                'subtitle' => trim(($cashRequest->user?->name ?? 'Sem solicitante') . ' | ' . ($cashRequest->costCenter?->name ?? 'Sem centro de custo')),
                'type' => 'due',
                'type_label' => 'Prestação vence',
                'tone' => $isOverdue ? 'danger' : ($isToday ? 'warning' : 'neutral'),
            ];
        });

        $closedEvents = $closedRequests->map(function (CashRequest $cashRequest): array {
            return [
                'date' => $cashRequest->closed_at?->toDateString(),
                'time' => $cashRequest->closed_at?->format('H:i') ?? '--:--',
                'title' => $cashRequest->request_number,
                'subtitle' => trim(($cashRequest->user?->name ?? 'Sem solicitante') . ' | ' . ($cashRequest->costCenter?->name ?? 'Sem centro de custo')),
                'type' => 'closed',
                'type_label' => 'Caixa fechado',
                'tone' => 'success',
            ];
        });

        return $dueEvents
            ->concat($closedEvents)
            ->sortBy(['date', 'time'])
            ->values();
    }

    private function buildCalendarDays(Carbon $monthStart, Carbon $selectedDate, Collection $eventsByDate): array
    {
        $firstCalendarDay = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $lastCalendarDay = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $currentDay = $firstCalendarDay->copy();
        $weeks = [];

        while ($currentDay <= $lastCalendarDay) {
            $week = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $currentDay->copy();
                $dateKey = $date->toDateString();
                $dayEvents = collect($eventsByDate->get($dateKey, []));

                $week[] = [
                    'date' => $dateKey,
                    'day_number' => $date->day,
                    'is_current_month' => $date->isSameMonth($monthStart),
                    'is_today' => $date->isToday(),
                    'is_selected' => $date->isSameDay($selectedDate),
                    'events_count' => $dayEvents->count(),
                    'has_overdue' => $dayEvents->contains(fn (array $event) => $event['tone'] === 'danger'),
                    'sample_events' => $dayEvents->take(2)->values(),
                ];

                $currentDay->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }
}
