<div class="stack-lg">
    <section class="toolbar-card">
        <div class="toolbar-head">
            <div>
                <span class="hero-label">Fila operacional</span>
                <h3 class="section-title mt-3.5">Solicitações de caixa</h3>
                <p class="section-copy">Filtre a operação por status, departamento e texto livre para acelerar a tomada de decisão.</p>
            </div>
            <span class="status-pill">{{ $cashRequests->total() }} registros</span>
        </div>

        <div class="toolbar-grid">
            <div class="field">
                <label for="search">Busca</label>
                <input id="search" type="text" wire:model.live="search" placeholder="Numero, finalidade ou justificativa">
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" wire:model.live="status">
                    <option value="">Todos</option>
                    @foreach (\App\Enums\CashRequestStatus::cases() as $statusOption)
                        <option value="{{ $statusOption->value }}">{{ \App\Support\AdminLabel::cashRequestStatus($statusOption) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="department">Departamento</label>
                <select id="department" wire:model.live="departmentPublicId">
                    <option value="">Todos</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->public_id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="table-card">
        <div class="table-header">
            <div>
                <h3 class="section-title">Visão consolidada do fluxo</h3>
                <p class="section-copy">Cada linha resume solicitante, saldo, status atual e contexto operacional da solicitação.</p>
            </div>
            <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar ao dashboard</a>
        </div>

        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Numero</th>
                        <th>Solicitante</th>
                        <th>Status</th>
                        <th>Valor solicitado</th>
                        <th>Saldo atual</th>
                        <th>Departamento</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cashRequests as $cashRequest)
                        <tr>
                            <td>
                                <strong>{{ $cashRequest->request_number }}</strong>
                                <div class="secondary-text">{{ $cashRequest->created_at?->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->user->name }}</strong>
                                <div class="secondary-text">{{ $cashRequest->purpose }}</div>
                            </td>
                            <td>
                                <span class="status-pill">{{ \App\Support\AdminLabel::cashRequestStatus($cashRequest->status) }}</span>
                            </td>
                            <td>
                                <strong>R$ {{ number_format($cashRequest->requested_amount, 2, ',', '.') }}</strong>
                                <div class="secondary-text">Previsto: {{ $cashRequest->planned_use_date?->format('d/m/Y') ?? '-' }}</div>
                            </td>
                            <td>
                                <strong>R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</strong>
                                <div class="secondary-text">Liberado: R$ {{ number_format($cashRequest->released_amount, 2, ',', '.') }}</div>
                            </td>
                            <td>
                                <strong>{{ $cashRequest->department?->name ?? '-' }}</strong>
                                <div class="secondary-text">{{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }}</div>
                            </td>
                            <td>
                                <a class="button ghost" href="{{ route('admin.cash-requests.show', $cashRequest) }}">Abrir detalhe</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">Nenhuma solicitação encontrada para os filtros aplicados.</div>
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
                        <a class="button ghost" href="{{ $cashRequests->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="button ghost is-disabled">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>
