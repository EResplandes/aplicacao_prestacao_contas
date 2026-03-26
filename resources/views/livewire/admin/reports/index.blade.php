<div class="stack-lg">
    <section class="hero-panel">
        <div>
            <span class="hero-label">Relatórios operacionais</span>
            <h2>Visão consolidada de todos os caixas, colaboradores, categorias e gastos acima do padrão.</h2>
            <p>
                O financeiro pode cruzar volume liberado, prestação, categoria, centro de custo e valores elevados em uma única leitura.
            </p>
            <div class="hero-actions">
                <a class="button" href="{{ route('admin.cash-monitoring.index') }}">Abrir caixas e gastos</a>
                <a class="button secondary" href="{{ route('admin.financial-calendar.index') }}">Ver calendário financeiro</a>
            </div>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Todos os caixas</span>
                <strong>{{ $report['summary']['total_requests'] }}</strong>
                <span>Base consolidada do recorte aplicado.</span>
            </div>
            <div class="summary-block">
                <span class="label">Caixas em aberto</span>
                <strong>{{ $report['summary']['open_requests'] }}</strong>
                <span>Fluxos ainda sob responsabilidade do solicitante ou do financeiro.</span>
            </div>
            <div class="summary-block">
                <span class="label">Caixas fechados</span>
                <strong>{{ $report['summary']['closed_requests'] }}</strong>
                <span>Caixas já conciliados e encerrados.</span>
            </div>
            <div class="summary-block">
                <span class="label">Média por caixa</span>
                <strong>R$ {{ number_format($report['summary']['average_request'], 2, ',', '.') }}</strong>
                <span>Média do valor solicitado no recorte.</span>
            </div>
        </div>
    </section>

    <section class="kpi-grid">
        <article class="metric-card">
            <span class="metric-label">Valor liberado</span>
            <strong class="metric-value">R$ {{ number_format($report['summary']['total_released'], 2, ',', '.') }}</strong>
            <p class="metric-footer">Total liberado para os caixas filtrados.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Valor gasto</span>
            <strong class="metric-value">R$ {{ number_format($report['summary']['total_spent'], 2, ',', '.') }}</strong>
            <p class="metric-footer">Total já lançado em despesas e prestação.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Centros de custo</span>
            <strong class="metric-value">{{ $report['cost_center_totals']->count() }}</strong>
            <p class="metric-footer">Centros de custo com movimentação no período.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Categorias ativas</span>
            <strong class="metric-value">{{ $report['category_totals']->count() }}</strong>
            <p class="metric-footer">Categorias de despesa presentes no relatório.</p>
        </article>
    </section>

    <section class="toolbar-card">
        <div class="toolbar-head">
            <div>
                <h3 class="section-title">Filtros do relatório</h3>
                <p class="section-copy">Recorte por período, colaborador, centro de custo, categoria e faixa para detectar gasto estranho com valor elevado.</p>
            </div>
            <div class="row">
                <button class="button ghost" type="button" wire:click="resetFilters">Limpar filtros</button>
            </div>
        </div>

        <div class="report-filters-grid">
            <div class="field">
                <label for="report-start-date">Data inicial</label>
                <input id="report-start-date" type="date" wire:model.live="startDate">
            </div>
            <div class="field">
                <label for="report-end-date">Data final</label>
                <input id="report-end-date" type="date" wire:model.live="endDate">
            </div>
            <div class="field">
                <label for="report-user">Colaborador</label>
                <select id="report-user" wire:model.live="userId">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="report-cost-center">Centro de custo</label>
                <select id="report-cost-center" wire:model.live="costCenterId">
                    <option value="">Todos</option>
                    @foreach ($costCenters as $costCenter)
                        <option value="{{ $costCenter->id }}">{{ $costCenter->code }} - {{ $costCenter->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="report-category">Categoria</label>
                <select id="report-category" wire:model.live="expenseCategoryId">
                    <option value="">Todas</option>
                    @foreach ($expenseCategories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="report-scope">Escopo dos caixas</label>
                <select id="report-scope" wire:model.live="scope">
                    <option value="all">Todos</option>
                    <option value="open">Somente abertos</option>
                    <option value="closed">Somente fechados</option>
                </select>
            </div>
            <div class="field">
                <label for="report-high-value-threshold">Valor elevado a partir de</label>
                <input id="report-high-value-threshold" type="number" min="0" step="0.01" wire:model.live="highValueThreshold">
            </div>
        </div>
    </section>

    <section class="table-card">
        <div class="table-header">
            <div>
                <h3 class="section-title">Todos os caixas</h3>
                <p class="section-copy">Relatório geral com colaborador, centro de custo, status, prazo de prestação e saldos do caixa.</p>
            </div>
        </div>

        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Caixa</th>
                        <th>Colaborador</th>
                        <th>Centro de custo</th>
                        <th>Status</th>
                        <th>Prazo</th>
                        <th>Liberado</th>
                        <th>Gasto</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['cash_requests'] as $cashRequest)
                        @php
                            $isClosed = $cashRequest->status === \App\Enums\CashRequestStatus::CLOSED;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $cashRequest->request_number }}</strong>
                                <div class="secondary-text">{{ $cashRequest->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->user?->name ?? 'Sem colaborador' }}</strong>
                                <div class="secondary-text">{{ $cashRequest->department?->name ?? 'Sem departamento' }}</div>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }}</strong>
                                <div class="secondary-text">{{ $cashRequest->costCenter?->code ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="status-pill {{ $isClosed ? 'is-success' : 'is-warning' }}">{{ \App\Support\AdminLabel::cashRequestStatus($cashRequest->status) }}</span>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->due_accountability_at?->format('d/m/Y H:i') ?? '-' }}</strong>
                                <div class="secondary-text">Uso: {{ $cashRequest->planned_use_date?->format('d/m/Y') ?? '-' }}</div>
                            </td>
                            <td>R$ {{ number_format($cashRequest->released_amount, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($cashRequest->spent_amount, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"><div class="empty-state">Nenhum caixa encontrado para os filtros atuais.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($report['cash_requests']->hasPages())
            <div class="pagination-shell">
                <div class="secondary-text">
                    Exibindo {{ $report['cash_requests']->firstItem() }} a {{ $report['cash_requests']->lastItem() }} de {{ $report['cash_requests']->total() }} caixas
                </div>
                <div class="pagination-actions">
                    @if ($report['cash_requests']->onFirstPage())
                        <span class="button ghost is-disabled">Anterior</span>
                    @else
                        <a class="button ghost" href="{{ $report['cash_requests']->previousPageUrl() }}">Anterior</a>
                    @endif

                    @if ($report['cash_requests']->hasMorePages())
                        <a class="button ghost" href="{{ $report['cash_requests']->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="button ghost is-disabled">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </section>

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Caixas em aberto</h3>
                    <p class="section-copy">Prestação ou fechamento ainda pendente.</p>
                </div>
            </div>

            <div class="dashboard-side-list">
                @forelse ($report['open_requests'] as $cashRequest)
                    <div class="side-item">
                        <div>
                            <strong>{{ $cashRequest->request_number }}</strong>
                            <small>{{ $cashRequest->user?->name ?? 'Sem colaborador' }} | {{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }}</small>
                        </div>
                        <span class="status-pill is-warning">R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="empty-state">Nenhum caixa em aberto foi encontrado no recorte atual.</div>
                @endforelse
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Caixas fechados</h3>
                    <p class="section-copy">Histórico dos caixas já encerrados e conciliados.</p>
                </div>
            </div>

            <div class="dashboard-side-list">
                @forelse ($report['closed_requests'] as $cashRequest)
                    <div class="side-item">
                        <div>
                            <strong>{{ $cashRequest->request_number }}</strong>
                            <small>{{ $cashRequest->user?->name ?? 'Sem colaborador' }} | encerrado em {{ $cashRequest->closed_at?->format('d/m/Y H:i') ?? '-' }}</small>
                        </div>
                        <span class="status-pill is-success">R$ {{ number_format($cashRequest->spent_amount, 2, ',', '.') }}</span>
                    </div>
                @empty
                    <div class="empty-state">Ainda não existem caixas fechados para esse recorte.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Caixas por colaborador / usuário</h3>
                    <p class="section-copy">Quantidade de caixas, total liberado, total gasto e quantos seguem em aberto por colaborador.</p>
                </div>
            </div>

            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Caixas</th>
                            <th>Liberado</th>
                            <th>Gasto</th>
                            <th>Abertos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['user_totals'] as $row)
                            <tr>
                                <td>{{ $row->user?->name ?? 'Sem usuário' }}</td>
                                <td>{{ $row->total_requests }}</td>
                                <td>R$ {{ number_format((float) $row->total_released, 2, ',', '.') }}</td>
                                <td>R$ {{ number_format((float) $row->total_spent, 2, ',', '.') }}</td>
                                <td>{{ $row->open_requests }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"><div class="empty-state">Sem dados de colaboradores para os filtros aplicados.</div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Relatório por centro de custo</h3>
                    <p class="section-copy">Volume de caixas, liberado e gasto por centro de custo.</p>
                </div>
            </div>

            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Centro de custo</th>
                            <th>Caixas</th>
                            <th>Liberado</th>
                            <th>Gasto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['cost_center_totals'] as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row->costCenter?->name ?? 'Sem centro de custo' }}</strong>
                                    <div class="secondary-text">{{ $row->costCenter?->code ?? '-' }}</div>
                                </td>
                                <td>{{ $row->total_requests }}</td>
                                <td>R$ {{ number_format((float) $row->total_released, 2, ',', '.') }}</td>
                                <td>R$ {{ number_format((float) $row->total_spent, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"><div class="empty-state">Nenhum centro de custo apareceu no relatório filtrado.</div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Relatório por categoria</h3>
                    <p class="section-copy">Leitura por categoria, como alimentação, transporte, materiais e demais despesas.</p>
                </div>
            </div>

            <div class="table-shell">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Lançamentos</th>
                            <th>Total</th>
                            <th>Média</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['category_totals'] as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row->category?->name ?? 'Sem categoria' }}</strong>
                                    <div class="secondary-text">{{ $row->category?->code ?? '-' }}</div>
                                </td>
                                <td>{{ $row->total_expenses }}</td>
                                <td>R$ {{ number_format((float) $row->total_amount, 2, ',', '.') }}</td>
                                <td>R$ {{ number_format((float) $row->average_amount, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"><div class="empty-state">Nenhuma categoria de despesa foi encontrada no recorte atual.</div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Gastos estranhos com valor elevado</h3>
                    <p class="section-copy">Itens acima de R$ {{ number_format((float) $highValueThreshold, 2, ',', '.') }} ou já sinalizados como suspeitos.</p>
                </div>
            </div>

            <div class="agenda-list">
                @forelse ($report['high_value_expenses'] as $expense)
                    @php
                        $isFlagged = $expense->status === \App\Enums\CashExpenseStatus::FLAGGED;
                    @endphp
                    <div class="agenda-item">
                        <strong>{{ $expense->description }}</strong>
                        <small>
                            {{ $expense->user?->name ?? 'Sem colaborador' }} |
                            {{ $expense->category?->name ?? 'Sem categoria' }} |
                            {{ $expense->cashRequest?->request_number ?? 'Sem caixa' }}
                        </small>
                        <div class="row mt-3">
                            <span class="status-pill {{ $isFlagged ? 'is-danger' : 'is-warning' }}">
                                {{ $isFlagged ? 'Sinalizado' : 'Valor elevado' }}
                            </span>
                            <span class="status-pill">R$ {{ number_format($expense->amount, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Nenhum gasto acima da faixa definida ou sinalizado por alerta apareceu neste recorte.</div>
                @endforelse
            </div>
        </article>
    </section>
</div>
