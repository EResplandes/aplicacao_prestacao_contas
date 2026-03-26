@php
    $severityClass = static fn (string $severity): string => match ($severity) {
        'critical' => 'status-pill is-danger',
        'high' => 'status-pill is-warning',
        'medium' => 'status-pill',
        default => 'status-pill is-neutral',
    };
@endphp

<div class="stack-lg" wire:poll.15s>
    <section class="hero-panel">
        <div>
            <span class="hero-label">Painel de segurança</span>
            <h2>Monitoramento de brute force e tentativas de ataque</h2>
            <p>
                Acompanhe falhas de login, bloqueios por força bruta, limites excedidos, origens não confiáveis e
                sondagens suspeitas como acessos a <code>.env</code>, <code>wp-admin</code> e outros endpoints comuns de varredura.
            </p>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="card-kicker">Eventos críticos</span>
                <strong>{{ $metrics['critical'] }}</strong>
                <span>Ocorrências de alta severidade no período selecionado.</span>
            </div>
            <div class="summary-block">
                <span class="card-kicker">Sondagens suspeitas</span>
                <strong>{{ $metrics['suspicious_probes'] }}</strong>
                <span>Varreduras e probes bloqueados ou identificados pelo sistema.</span>
            </div>
        </div>
    </section>

    <section class="section-card stack">
        <div class="section-header">
            <div>
                <h3 class="section-title">Visão rápida</h3>
                <p class="section-copy">Indicadores operacionais da superfície de ataque recente.</p>
            </div>
        </div>

        <div class="kpi-grid">
            <article class="metric-card">
                <span class="metric-label">Falhas de login</span>
                <strong class="metric-value">{{ $metrics['failed_logins'] }}</strong>
                <span class="metric-footer">Credenciais inválidas registradas.</span>
            </article>
            <article class="metric-card">
                <span class="metric-label">Bloqueios</span>
                <strong class="metric-value">{{ $metrics['lockouts'] }}</strong>
                <span class="metric-footer">IPs ou credenciais temporariamente travados.</span>
            </article>
            <article class="metric-card">
                <span class="metric-label">Rate limit</span>
                <strong class="metric-value">{{ $metrics['rate_limits'] }}</strong>
                <span class="metric-footer">Requisições que ultrapassaram o limite configurado.</span>
            </article>
            <article class="metric-card">
                <span class="metric-label">Origens bloqueadas</span>
                <strong class="metric-value">{{ $metrics['blocked_origins'] }}</strong>
                <span class="metric-footer">Origem ou referer rejeitados pela política de confiança.</span>
            </article>
        </div>
    </section>

    <div class="grid-2">
        <section class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Filtros</h3>
                    <p class="section-copy">Refine a investigação por canal, severidade e tipo de evento.</p>
                </div>
            </div>

            <div class="report-filters-grid">
                <div class="field">
                    <label>Busca</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="IP, e-mail, usuário ou rota">
                </div>

                <div class="field">
                    <label>Canal</label>
                    <select wire:model.live="channel">
                        <option value="">Todos</option>
                        <option value="web">Painel web</option>
                        <option value="api">API</option>
                    </select>
                </div>

                <div class="field">
                    <label>Severidade</label>
                    <select wire:model.live="severity">
                        <option value="">Todas</option>
                        <option value="critical">Crítica</option>
                        <option value="high">Alta</option>
                        <option value="medium">Média</option>
                        <option value="low">Baixa</option>
                    </select>
                </div>

                <div class="field">
                    <label>Período</label>
                    <select wire:model.live="timeframe">
                        <option value="24h">Últimas 24 horas</option>
                        <option value="7d">Últimos 7 dias</option>
                        <option value="30d">Últimos 30 dias</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label>Tipo de evento</label>
                <select wire:model.live="eventType">
                    <option value="">Todos</option>
                    @foreach ($eventTypeOptions as $eventTypeOption)
                        <option value="{{ $eventTypeOption }}">{{ \App\Support\AdminLabel::securityEventType($eventTypeOption) }}</option>
                    @endforeach
                </select>
            </div>
        </section>

        <section class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">IPs com maior recorrência</h3>
                    <p class="section-copy">Endereços que mais concentraram eventos no período filtrado.</p>
                </div>
            </div>

            <div class="dashboard-side-list">
                @forelse ($topIps as $ip)
                    <article class="side-item">
                        <div>
                            <strong>{{ $ip->ip_address }}</strong>
                            <p class="section-copy">Eventos identificados nesse IP.</p>
                        </div>
                        <span class="status-pill {{ $ip->aggregate >= 5 ? 'is-danger' : 'is-warning' }}">{{ $ip->aggregate }}</span>
                    </article>
                @empty
                    <div class="empty-state">Nenhum IP recorrente foi identificado para o período selecionado.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="section-card stack">
        <div class="section-header">
            <div>
                <h3 class="section-title">Eventos recentes</h3>
                <p class="section-copy">Detalhamento dos sinais de ataque capturados pelo backend.</p>
            </div>
        </div>

        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Evento</th>
                        <th>Canal</th>
                        <th>Severidade</th>
                        <th>Usuário / identificador</th>
                        <th>IP / rota</th>
                        <th>Detalhes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ $event->detected_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td>{{ \App\Support\AdminLabel::securityEventType($event->event_type) }}</td>
                            <td>{{ \App\Support\AdminLabel::securityChannel($event->channel) }}</td>
                            <td>
                                <span class="{{ $severityClass($event->severity) }}">
                                    {{ \App\Support\AdminLabel::securitySeverity($event->severity) }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ $event->user?->name ?? $event->identifier ?? '-' }}</strong>
                                <div class="secondary-text">{{ $event->user?->email ?? $event->identifier ?? 'Sem identificador' }}</div>
                            </td>
                            <td>
                                <strong>{{ $event->ip_address ?? '-' }}</strong>
                                <div class="secondary-text">{{ $event->path ?? ($event->route_name ?? '-') }}</div>
                            </td>
                            <td>
                                <div class="secondary-text">
                                    @if (($event->metadata['reason'] ?? null) !== null)
                                        Motivo: {{ $event->metadata['reason'] }}
                                    @elseif (($event->metadata['origin'] ?? null) !== null)
                                        Origem: {{ $event->metadata['origin'] }}
                                    @elseif (($event->metadata['limiter'] ?? null) !== null)
                                        Limiter: {{ $event->metadata['limiter'] }}
                                    @elseif (($event->metadata['matched_patterns'] ?? null) !== null)
                                        Padrões: {{ implode(', ', $event->metadata['matched_patterns']) }}
                                    @else
                                        Sem detalhe adicional.
                                    @endif
                                </div>

                                @if (($event->metadata['retry_after'] ?? null) !== null)
                                    <div class="secondary-text">Retry-After: {{ $event->metadata['retry_after'] }}s</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">Nenhum evento de segurança foi encontrado com os filtros atuais.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $events->links() }}
        </div>
    </section>
</div>
