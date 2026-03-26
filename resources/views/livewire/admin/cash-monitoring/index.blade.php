<div class="stack-lg" wire:poll.5s.visible.keep-alive>
    <section class="hero-panel">
        <div>
            <span class="hero-label">Prestacao</span>
            <h2>Monitor de caixas e gastos</h2>
            <p>Veja rapidamente o que esta liberado, gasto, pendente e sinalizado.</p>
            <div class="hero-actions">
                <a class="button" href="{{ route('admin.cash-requests.index') }}">Solicitacoes</a>
                <a class="button secondary" href="{{ route('admin.approvals.index') }}">Aprovacoes</a>
            </div>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Caixas em aberto</span>
                <strong>{{ $openCashCount }}</strong>
                <span>Em uso ou em prestacao.</span>
            </div>
            <div class="summary-block">
                <span class="label">Caixas encerrados</span>
                <strong>{{ $closedCashCount }}</strong>
                <span>Ja conciliados.</span>
            </div>
            <div class="summary-block">
                <span class="label">Total gasto</span>
                <strong>R$ {{ number_format($totalSpent, 2, ',', '.') }}</strong>
                <span>Despesa registrada.</span>
            </div>
            <div class="summary-block">
                <span class="label">Alertas em gastos</span>
                <strong>{{ $flaggedExpenseCount }}</strong>
                <span>Itens para revisao.</span>
            </div>
        </div>
    </section>

    <section class="toolbar-card">
        <div class="toolbar-head">
            <div>
                <h3 class="section-title">Filtro operacional</h3>
                <p class="section-copy">Busque por status, solicitante, centro de custo ou gasto.</p>
            </div>
            <span class="status-pill">Saldo em aberto R$ {{ number_format($totalAvailable, 2, ',', '.') }}</span>
        </div>

        <div class="toolbar-grid">
            <div class="field">
                <label for="monitor-search">Busca</label>
                <input id="monitor-search" type="text" wire:model.live="search" placeholder="Solicitante, numero, centro de custo ou gasto">
            </div>
            <div class="field">
                <label for="monitor-status">Recorte</label>
                <select id="monitor-status" wire:model.live="status">
                    <option value="open">Caixas em aberto</option>
                    <option value="released">Apenas liberados</option>
                    <option value="accountability">Em prestacao</option>
                    <option value="closed">Encerrados</option>
                    <option value="all">Todos</option>
                </select>
            </div>
            <div class="field">
                <label>Atalhos</label>
                <div class="row">
                    <button class="button ghost" type="button" wire:click="$set('status', 'open')">Em aberto</button>
                    <button class="button ghost" type="button" wire:click="$set('status', 'accountability')">Prestacao</button>
                </div>
            </div>
        </div>
    </section>

    <section class="stack">
        @forelse ($cashRequests as $cashRequest)
            @php
                $requestTone = match ($cashRequest->status) {
                    \App\Enums\CashRequestStatus::RELEASED => 'is-warning',
                    \App\Enums\CashRequestStatus::PARTIALLY_ACCOUNTED => 'is-neutral',
                    \App\Enums\CashRequestStatus::FULLY_ACCOUNTED => 'is-success',
                    \App\Enums\CashRequestStatus::CLOSED => 'is-success',
                    default => '',
                };
            @endphp
            <article class="section-card stack">
                <div class="section-header">
                    <div>
                        <span class="hero-label">Caixa {{ $cashRequest->request_number }}</span>
                        <h3 class="section-title mt-3.5">{{ $cashRequest->user?->name ?? 'Sem solicitante' }}</h3>
                        <p class="section-copy">
                            {{ $cashRequest->department?->name ?? 'Sem departamento' }} |
                            {{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }} |
                            {{ $cashRequest->purpose }}
                        </p>
                    </div>
                    <div class="list-meta">
                        <span class="status-pill {{ $requestTone }}">{{ \App\Support\AdminLabel::cashRequestStatus($cashRequest->status) }}</span>
                        <a class="button ghost" href="{{ route('admin.cash-requests.show', $cashRequest) }}">Abrir detalhe</a>
                    </div>
                </div>

                <div class="inline-list">
                    <div class="inline-stat">
                        <span class="label">Liberado</span>
                        <strong>R$ {{ number_format($cashRequest->released_amount, 2, ',', '.') }}</strong>
                    </div>
                    <div class="inline-stat">
                        <span class="label">Gasto</span>
                        <strong>R$ {{ number_format($cashRequest->spent_amount, 2, ',', '.') }}</strong>
                    </div>
                    <div class="inline-stat">
                        <span class="label">Saldo</span>
                        <strong>R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="label">Responsavel</span>
                        <p>Gestor: {{ $cashRequest->manager?->name ?? 'Nao definido' }}</p>
                    </div>
                    <div class="detail-item">
                        <span class="label">Prestacao</span>
                        <p>{{ $cashRequest->due_accountability_at?->format('d/m/Y H:i') ?? 'Sem prazo definido' }} | {{ $cashRequest->expenses_count }} gastos</p>
                    </div>
                    <div class="detail-item">
                        <span class="label">Pendencias</span>
                        <p>{{ $cashRequest->pending_expenses_count }} pendentes | {{ $cashRequest->approved_expenses_count }} aprovados | {{ $cashRequest->flagged_expenses_count }} sinalizados</p>
                    </div>
                    <div class="detail-item">
                        <span class="label">Data de uso</span>
                        <p>{{ $cashRequest->planned_use_date?->format('d/m/Y') ?? '-' }}</p>
                    </div>
                </div>

                <div class="stack">
                    <div class="card-head">
                        <div>
                            <h4 class="section-title text-lg">Gastos recentes</h4>
                            <p class="section-copy">Ultimos itens enviados neste caixa.</p>
                        </div>
                    </div>

                    @forelse ($cashRequest->expenses->take(3) as $expense)
                        @php
                            $expenseTone = match ($expense->status) {
                                \App\Enums\CashExpenseStatus::APPROVED => 'is-success',
                                \App\Enums\CashExpenseStatus::REJECTED => 'is-danger',
                                \App\Enums\CashExpenseStatus::FLAGGED => 'is-warning',
                                default => 'is-neutral',
                            };
                        @endphp
                        <div class="list-card">
                            <div>
                                <strong>{{ $expense->description }}</strong>
                                <div class="secondary-text">
                                    {{ $expense->category?->name ?? 'Sem categoria' }} |
                                    {{ $expense->vendor_name ?? 'Fornecedor nao informado' }} |
                                    {{ $expense->spent_at?->format('d/m/Y H:i') ?? '-' }}
                                </div>
                            </div>
                            <div class="list-meta">
                                <span class="status-pill {{ $expenseTone }}">{{ \App\Support\AdminLabel::cashExpenseStatus($expense->status) }}</span>
                                <strong>R$ {{ number_format($expense->amount, 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">Este caixa ainda nao possui gastos enviados.</div>
                    @endforelse
                </div>
            </article>
        @empty
            <div class="empty-state">Nenhum caixa com gastos foi encontrado para o recorte selecionado.</div>
        @endforelse
    </section>

    @if ($cashRequests->hasPages())
        <div class="pagination-shell">
            <div class="secondary-text">
                Exibindo {{ $cashRequests->firstItem() }} a {{ $cashRequests->lastItem() }} de {{ $cashRequests->total() }} caixas
            </div>
            <div class="pagination-actions">
                @if ($cashRequests->onFirstPage())
                    <span class="button ghost is-disabled">Anterior</span>
                @else
                    <a class="button ghost" href="{{ $cashRequests->previousPageUrl() }}">Anterior</a>
                @endif

                @if ($cashRequests->hasMorePages())
                    <a class="button ghost" href="{{ $cashRequests->nextPageUrl() }}">Proxima</a>
                @else
                    <span class="button ghost is-disabled">Proxima</span>
                @endif
            </div>
        </div>
    @endif
</div>
