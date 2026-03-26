<div class="stack-lg">
    <section class="section-card stack">
        <div class="section-header">
            <div>
                <span class="hero-label">Resumo</span>
                <h3 class="section-title mt-3.5">Atalhos da operacao</h3>
                <p class="section-copy">Acesse filas, monitoramento, cadastros e alertas a partir do painel inicial.</p>
            </div>
            <div class="row">
                <a class="button" href="{{ route('admin.cash-requests.index') }}">Fila geral</a>
                <a class="button secondary" href="{{ route('admin.users.index') }}">Usuarios</a>
                <a class="button secondary" href="{{ route('admin.cash-monitoring.index') }}">Caixas e gastos</a>
            </div>
        </div>

        <div class="quick-grid">
            <a class="quick-card is-primary" href="{{ route('admin.dashboard') }}">
                <span class="card-kicker">Visao geral</span>
                <h4>Resumo executivo</h4>
                <p>Solicitado, aprovado, liberado e pendente de prestacao em uma unica leitura.</p>
                <div class="card-footer">
                    <span>{{ $metrics['open_requests'] ?? 0 }} caixas ativos</span>
                    <span>Dashboard</span>
                </div>
            </a>

            <a class="quick-card" href="{{ route('admin.approvals.index') }}">
                <span class="card-kicker">Aprovacoes</span>
                <h4>Fila de decisao</h4>
                <p>Solicitacoes aguardando gestor ou financeiro.</p>
                <div class="card-footer">
                    <span>{{ $metrics['under_analysis_total'] ?? 0 }} em analise</span>
                    <span>Abrir fila</span>
                </div>
            </a>

            <a class="quick-card" href="{{ route('admin.cash-monitoring.index') }}">
                <span class="card-kicker">Prestacao</span>
                <h4>Caixas e gastos</h4>
                <p>Saldos, gastos recentes e prestacoes ainda abertas.</p>
                <div class="card-footer">
                    <span>R$ {{ number_format($metrics['open_balance'] ?? 0, 2, ',', '.') }}</span>
                    <span>Monitorar</span>
                </div>
            </a>

            <a class="quick-card" href="{{ route('admin.cost-centers.index') }}">
                <span class="card-kicker">Cadastro</span>
                <h4>Centros de custo</h4>
                <p>Base financeira pronta para operacao, aprovacao e relatorio.</p>
                <div class="card-footer">
                    <span>{{ count($metrics['top_departments'] ?? []) }} frentes</span>
                    <span>Organizar</span>
                </div>
            </a>
        </div>
    </section>

    <section class="kpi-grid">
        <article class="metric-card">
            <span class="metric-label">Total solicitado</span>
            <strong class="metric-value">R$ {{ number_format($metrics['requested_total'] ?? 0, 2, ',', '.') }}</strong>
            <p class="metric-footer">Volume solicitado no fluxo.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Total aprovado</span>
            <strong class="metric-value">R$ {{ number_format($metrics['approved_total'] ?? 0, 2, ',', '.') }}</strong>
            <p class="metric-footer">Valor que ja passou pelas alcadas.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Total gasto</span>
            <strong class="metric-value">R$ {{ number_format($metrics['spent_total'] ?? 0, 2, ',', '.') }}</strong>
            <p class="metric-footer">Despesa ja registrada nos caixas ativos.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Saldo em aberto</span>
            <strong class="metric-value">R$ {{ number_format($metrics['open_balance'] ?? 0, 2, ',', '.') }}</strong>
            <p class="metric-footer">Valor ainda em responsabilidade operacional.</p>
        </article>
    </section>

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Pendencias</h3>
                    <p class="section-copy">Itens que pedem acao imediata do gestor, financeiro ou conformidade.</p>
                </div>
            </div>

            <div class="dashboard-side-list">
                @foreach (($metrics['status_panels'] ?? []) as $panel)
                    <div class="side-item">
                        <div>
                            <strong>{{ $panel['label'] }}</strong>
                            <small>Status atual da fila.</small>
                        </div>
                        <span class="status-pill">{{ $panel['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Alertas recentes</h3>
                    <p class="section-copy">Fraude, OCR ou revisao financeira para tratar agora.</p>
                </div>
            </div>

            <div class="dashboard-side-list">
                @forelse(($metrics['latest_flagged_expenses'] ?? []) as $expense)
                    <div class="side-item">
                        <div>
                            <strong>{{ $expense->description }}</strong>
                            <small>{{ $expense->vendor_name ?? 'Fornecedor nao informado' }} | {{ $expense->created_at?->format('d/m/Y H:i') }}</small>
                        </div>
                        <span class="status-pill">R$ {{ number_format($expense->amount, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="empty-state">Nenhum alerta critico foi registrado nas ultimas movimentacoes.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid-2">
        <article class="table-card">
            <div class="table-header">
                <div>
                    <h3 class="section-title">Solicitacoes recentes</h3>
                    <p class="section-copy">Leitura rapida da operacao para abrir detalhes ou agir na fila.</p>
                </div>
            </div>

            <div class="table-shell">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Solicitacao</th>
                            <th>Solicitante</th>
                            <th>Status</th>
                            <th>Departamento</th>
                            <th>Valor</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($metrics['recent_requests'] ?? []) as $cashRequest)
                            <tr>
                                <td>
                                    <strong>{{ $cashRequest->request_number }}</strong>
                                    <div class="secondary-text">{{ $cashRequest->created_at?->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    <span class="dashboard-dot">{{ $cashRequest->user?->name ?? 'Sem usuario' }}</span>
                                </td>
                                <td>
                                    <span class="status-pill">{{ \App\Support\AdminLabel::cashRequestStatus($cashRequest->status) }}</span>
                                </td>
                                <td>{{ $cashRequest->department?->name ?? '-' }}</td>
                                <td>R$ {{ number_format($cashRequest->requested_amount, 2, ',', '.') }}</td>
                                <td><a class="button ghost" href="{{ route('admin.cash-requests.show', $cashRequest) }}">Abrir</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"><div class="empty-state">Nao existem solicitacoes para compor a lista principal.</div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Departamentos com maior uso</h3>
                    <p class="section-copy">Areas com mais solicitacoes no periodo atual.</p>
                </div>
            </div>

            <div class="dashboard-side-list">
                @forelse (($metrics['top_departments'] ?? []) as $departmentMetric)
                    <div class="side-item">
                        <div>
                            <strong>{{ $departmentMetric->department?->name ?? 'Sem departamento' }}</strong>
                            <small>{{ $departmentMetric->total }} solicitacoes registradas</small>
                        </div>
                        <span class="status-pill">{{ $loop->iteration }}o</span>
                    </div>
                @empty
                    <div class="empty-state">Ainda nao ha dados suficientes para formar o ranking.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
