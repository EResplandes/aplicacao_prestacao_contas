<div class="stack-lg">
    <section class="hero-panel">
        <div>
            <span class="hero-label">Calendário financeiro</span>
            <h2>Agenda do financeiro para prestação de contas, fechamento e caixas com prazo próximo.</h2>
            <p>
                Use este calendário para saber quando cada caixa precisa prestar contas, fechar o ciclo financeiro ou entrar em ação por atraso.
            </p>
            <div class="hero-actions">
                <a class="button" href="{{ route('admin.reports.index') }}">Abrir relatórios</a>
                <a class="button secondary" href="{{ route('admin.cash-monitoring.index') }}">Acompanhar caixas e gastos</a>
            </div>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Vencem no mês</span>
                <strong>{{ $calendar['summary']['due_this_month'] }}</strong>
                <span>Caixas com prestação vencendo no mês selecionado.</span>
            </div>
            <div class="summary-block">
                <span class="label">Próximos 7 dias</span>
                <strong>{{ $calendar['summary']['due_this_week'] }}</strong>
                <span>Prazos que merecem acompanhamento imediato do financeiro.</span>
            </div>
            <div class="summary-block">
                <span class="label">Em atraso</span>
                <strong>{{ $calendar['summary']['overdue'] }}</strong>
                <span>Caixas com prestação de contas vencida.</span>
            </div>
            <div class="summary-block">
                <span class="label">Fechados no mês</span>
                <strong>{{ $calendar['summary']['closed_this_month'] }}</strong>
                <span>Caixas encerrados e conciliados no calendário atual.</span>
            </div>
        </div>
    </section>

    <section class="toolbar-card">
        <div class="calendar-toolbar">
            <div>
                <h3 class="calendar-month-label">{{ \Illuminate\Support\Str::title($calendar['month_label']) }}</h3>
                <p class="section-copy">Navegue mês a mês para ver os caixas que precisam ser fechados ou prestar contas.</p>
            </div>
            <div class="row">
                <button class="button ghost" type="button" wire:click="previousMonth">Mês anterior</button>
                <input class="max-w-[180px]" type="month" wire:model.live="month">
                <button class="button ghost" type="button" wire:click="nextMonth">Próximo mês</button>
            </div>
        </div>

        <div class="report-filters-grid mt-[18px]">
            <div class="field">
                <label for="calendar-user">Colaborador</label>
                <select id="calendar-user" wire:model.live="userId">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="calendar-cost-center">Centro de custo</label>
                <select id="calendar-cost-center" wire:model.live="costCenterId">
                    <option value="">Todos</option>
                    @foreach ($costCenters as $costCenter)
                        <option value="{{ $costCenter->id }}">{{ $costCenter->code }} - {{ $costCenter->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Calendário de prazos</h3>
                    <p class="section-copy">Cada célula mostra o volume de eventos do dia e destaca datas com prestação vencida.</p>
                </div>
            </div>

            <div class="calendar-shell">
                <div class="calendar-grid">
                    @foreach ($weekdays as $weekday)
                        <div class="calendar-weekday">{{ $weekday }}</div>
                    @endforeach
                </div>

                @foreach ($calendar['calendar_days'] as $week)
                    <div class="calendar-grid">
                        @foreach ($week as $day)
                            <button
                                type="button"
                                wire:click="selectDate('{{ $day['date'] }}')"
                                class="calendar-day {{ $day['is_current_month'] ? '' : 'is-outside' }} {{ $day['is_selected'] ? 'is-selected' : '' }} {{ $day['is_today'] ? 'is-today' : '' }} {{ $day['has_overdue'] ? 'has-overdue' : '' }}"
                            >
                                <div class="calendar-day-head">
                                    <span class="calendar-day-number">{{ $day['day_number'] }}</span>
                                    @if ($day['events_count'] > 0)
                                        <span class="calendar-count">{{ $day['events_count'] }}</span>
                                    @endif
                                </div>

                                <div class="calendar-event-list">
                                    @foreach ($day['sample_events'] as $event)
                                        <div class="calendar-event {{ $event['tone'] === 'danger' ? 'is-danger' : ($event['tone'] === 'success' ? 'is-success' : ($event['tone'] === 'warning' ? 'is-warning' : '')) }}">
                                            <span>{{ $event['title'] }} • {{ $event['type_label'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Agenda do dia selecionado</h3>
                    <p class="section-copy">{{ \Illuminate\Support\Str::title($calendar['selected_date_label']) }}</p>
                </div>
            </div>

            <div class="agenda-list">
                @forelse ($calendar['selected_day_events'] as $event)
                    <div class="agenda-item">
                        <strong>{{ $event['title'] }} • {{ $event['type_label'] }}</strong>
                        <small>{{ $event['subtitle'] }}</small>
                        <div class="row mt-3">
                            <span class="status-pill {{ $event['tone'] === 'danger' ? 'is-danger' : ($event['tone'] === 'success' ? 'is-success' : ($event['tone'] === 'warning' ? 'is-warning' : '')) }}">
                                {{ $event['time'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Não existe evento financeiro para a data selecionada.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Próximos vencimentos</h3>
                    <p class="section-copy">Caixas que precisam prestar contas nos próximos dias.</p>
                </div>
            </div>

            <div class="agenda-list">
                @forelse ($calendar['upcoming_due_requests'] as $cashRequest)
                    <div class="agenda-item">
                        <strong>{{ $cashRequest->request_number }}</strong>
                        <small>{{ $cashRequest->user?->name ?? 'Sem colaborador' }} | {{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }}</small>
                        <div class="row mt-3">
                            <span class="status-pill is-warning">{{ $cashRequest->due_accountability_at?->format('d/m/Y H:i') ?? '-' }}</span>
                            <span class="status-pill">R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Não há vencimentos futuros no recorte atual.</div>
                @endforelse
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Caixas com prestação em atraso</h3>
                    <p class="section-copy">Lista prioritária para cobrança, fechamento ou revisão imediata do financeiro.</p>
                </div>
            </div>

            <div class="agenda-list">
                @forelse ($calendar['overdue_requests'] as $cashRequest)
                    <div class="agenda-item">
                        <strong>{{ $cashRequest->request_number }}</strong>
                        <small>{{ $cashRequest->user?->name ?? 'Sem colaborador' }} | {{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }}</small>
                        <div class="row mt-3">
                            <span class="status-pill is-danger">{{ $cashRequest->due_accountability_at?->format('d/m/Y H:i') ?? '-' }}</span>
                            <span class="status-pill">R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Nenhum caixa atrasado foi encontrado.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
