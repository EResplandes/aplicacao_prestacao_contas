<div class="stack-lg">
    <section class="hero-panel">
        <div>
            <span class="hero-label">Aprovacoes</span>
            <h2>Fila de novos caixas</h2>
            <p>Use esta tela para decidir rapido o que esta com gestor ou financeiro.</p>
            <div class="hero-actions">
                <a class="button" href="{{ route('admin.cash-requests.index') }}">Fila completa</a>
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar ao dashboard</a>
            </div>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Pendentes</span>
                <strong>{{ $totalPending }}</strong>
                <span>Total aguardando decisao.</span>
            </div>
            <div class="summary-block">
                <span class="label">Gestor</span>
                <strong>{{ $managerPending }}</strong>
                <span>Primeira alcada.</span>
            </div>
            <div class="summary-block">
                <span class="label">Financeiro</span>
                <strong>{{ $financialPending }}</strong>
                <span>Analise financeira.</span>
            </div>
            <div class="summary-block">
                <span class="label">Acima de 24h</span>
                <strong>{{ $stalePending }}</strong>
                <span>Prioridade da fila.</span>
            </div>
        </div>
    </section>

    <section class="toolbar-card">
        <div class="toolbar-head">
            <div>
                <h3 class="section-title">Filtros da fila</h3>
                <p class="section-copy">Busque por etapa, solicitante, centro de custo ou motivo.</p>
            </div>
            <span class="status-pill">{{ $cashRequests->total() }} registros</span>
        </div>

        <div class="toolbar-grid">
            <div class="field">
                <label for="approval-search">Busca</label>
                <input id="approval-search" type="text" wire:model.live="search" placeholder="Numero, solicitante, centro de custo ou motivo">
            </div>
            <div class="field">
                <label for="approval-stage">Etapa</label>
                <select id="approval-stage" wire:model.live="stage">
                    <option value="all">Todas</option>
                    <option value="manager">Aguardando gestor</option>
                    <option value="financial">Aguardando financeiro</option>
                </select>
            </div>
            <div class="field">
                <label>Atalhos</label>
                <div class="row">
                    <button class="button ghost" type="button" wire:click="$set('stage', 'manager')">Gestor</button>
                    <button class="button ghost" type="button" wire:click="$set('stage', 'financial')">Financeiro</button>
                </div>
            </div>
        </div>
    </section>

    <section class="table-card">
        <div class="table-header">
            <div>
                <h3 class="section-title">Caixas prontos para decisao</h3>
                <p class="section-copy">Cada linha resume solicitante, etapa atual, contexto e valor.</p>
            </div>
        </div>

        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Solicitacao</th>
                        <th>Solicitante</th>
                        <th>Etapa</th>
                        <th>Centro de custo</th>
                        <th>Valor</th>
                        <th>Data de uso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cashRequests as $cashRequest)
                        @php
                            $isManagerStage = $cashRequest->status === \App\Enums\CashRequestStatus::AWAITING_MANAGER_APPROVAL;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $cashRequest->request_number }}</strong>
                                <div class="secondary-text">{{ $cashRequest->submitted_at?->format('d/m/Y H:i') ?? $cashRequest->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->user?->name ?? 'Sem solicitante' }}</strong>
                                <div class="secondary-text">{{ $cashRequest->department?->name ?? 'Sem departamento' }}</div>
                            </td>
                            <td>
                                <span class="status-pill {{ $isManagerStage ? 'is-warning' : 'is-success' }}">
                                    {{ $isManagerStage ? 'Aguardando gestor' : 'Aguardando financeiro' }}
                                </span>
                                <div class="secondary-text">Gestor: {{ $cashRequest->manager?->name ?? 'Nao definido' }}</div>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }}</strong>
                                <div class="secondary-text">{{ $cashRequest->purpose }}</div>
                            </td>
                            <td>
                                <strong>R$ {{ number_format($cashRequest->requested_amount, 2, ',', '.') }}</strong>
                                <div class="secondary-text">{{ \Illuminate\Support\Str::limit($cashRequest->justification, 60) }}</div>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->planned_use_date?->format('d/m/Y') ?? '-' }}</strong>
                                <div class="secondary-text">Criada em {{ $cashRequest->created_at?->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <a class="button ghost" href="{{ route('admin.cash-requests.show', $cashRequest) }}">Decidir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">Nenhum caixa pendente foi encontrado para os filtros aplicados.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cashRequests->hasPages())
            <div class="pagination-shell">
                <div class="secondary-text">
                    Exibindo {{ $cashRequests->firstItem() }} a {{ $cashRequests->lastItem() }} de {{ $cashRequests->total() }} registros
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
    </section>
</div>
