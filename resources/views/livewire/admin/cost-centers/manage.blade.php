<div class="stack-lg">
    <section class="hero-panel">
        <div>
            <span class="hero-label">Cadastro financeiro</span>
            <h2>Cadastre e mantenha centros de custo com vínculo organizacional claro.</h2>
            <p>
                Esta tela dedicada simplifica o cadastro operacional dos centros de custo usados na solicitação, aprovação, prestação de contas e relatórios.
            </p>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Total cadastrado</span>
                <strong>{{ $totalCostCenters }}</strong>
                <span>Base financeira disponivel para o fluxo de caixa.</span>
            </div>
            <div class="summary-block">
                <span class="label">Ativos</span>
                <strong>{{ $activeCostCenters }}</strong>
                <span>Centros de custo liberados para uso no sistema.</span>
            </div>
            <div class="summary-block">
                <span class="label">Departamentos vinculados</span>
                <strong>{{ $linkedDepartments }}</strong>
                <span>Areas com pelo menos um centro de custo associado.</span>
            </div>
        </div>
    </section>

    <section class="grid-2">
        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Cadastro de centro de custo</h3>
                    <p class="section-copy">Informe empresa, departamento e identificadores financeiros para manter a operação organizada.</p>
                </div>
                <button class="button ghost" type="button" wire:click="resetCostCenterForm">Novo centro de custo</button>
            </div>

            <div class="info-grid">
                <div class="field">
                    <label>Empresa</label>
                    <select wire:model.live="costCenterForm.company_id">
                        <option value="">Selecione</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
                        @endforeach
                    </select>
                    @error('costCenterForm.company_id') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Departamento</label>
                    <select wire:model="costCenterForm.department_id">
                        <option value="">Selecione</option>
                        @foreach ($formDepartments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                    @error('costCenterForm.department_id') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Código</label>
                    <input type="text" wire:model="costCenterForm.code">
                    @error('costCenterForm.code') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Nome</label>
                    <input type="text" wire:model="costCenterForm.name">
                    @error('costCenterForm.name') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Status</label>
                    <select wire:model="costCenterForm.is_active">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <button class="button" type="button" wire:click="saveCostCenter">
                    {{ $editingCostCenterPublicId ? 'Atualizar centro de custo' : 'Salvar centro de custo' }}
                </button>
            </div>
        </article>

        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Consulta rápida</h3>
                    <p class="section-copy">Filtre a base por empresa, departamento ou código para localizar rapidamente um cadastro financeiro.</p>
                </div>
            </div>

            <div class="info-grid">
                <div class="field">
                    <label>Busca</label>
                    <input type="text" wire:model.live="search" placeholder="Código ou nome">
                </div>
                <div class="field">
                    <label>Empresa</label>
                    <select wire:model.live="companyFilter">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Departamento</label>
                    <select wire:model.live="departmentFilter">
                        <option value="">Todos</option>
                        @foreach ($filterDepartments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="empty-state">
                A tela de usuários continua disponivel em <a href="{{ route('admin.users.index') }}"><strong>/admin/users</strong></a>, para manter o cadastro de contas, gestores, perfis e vínculos com os centros de custo criados aqui.
            </div>
        </article>
    </section>

    <section class="table-card">
        <div class="table-header">
            <div>
                <h3 class="section-title">Centros de custo cadastrados</h3>
                <p class="section-copy">Lista operacional com empresa, departamento, status e atalho rápido para edicao.</p>
            </div>
            <a class="button secondary" href="{{ route('admin.organization.index') }}">Ver organização completa</a>
        </div>

        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Centro de custo</th>
                        <th>Empresa</th>
                        <th>Departamento</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($costCenters as $costCenter)
                        <tr>
                            <td><strong>{{ $costCenter->code }}</strong></td>
                            <td>{{ $costCenter->name }}</td>
                            <td>{{ $costCenter->company?->trade_name ?? '-' }}</td>
                            <td>{{ $costCenter->department?->name ?? '-' }}</td>
                            <td>
                                <span class="status-pill {{ $costCenter->is_active ? 'is-success' : 'is-neutral' }}">
                                    {{ $costCenter->is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td>
                                <button class="button ghost" type="button" wire:click="editCostCenter('{{ $costCenter->public_id }}')">Editar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">Nenhum centro de custo foi encontrado para os filtros atuais.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($costCenters->hasPages())
            <div class="pagination-shell">
                <div class="secondary-text">
                    Exibindo {{ $costCenters->firstItem() }} a {{ $costCenters->lastItem() }} de {{ $costCenters->total() }} registros
                </div>
                <div class="pagination-actions">
                    @if ($costCenters->onFirstPage())
                        <span class="button ghost is-disabled">Anterior</span>
                    @else
                        <a class="button ghost" href="{{ $costCenters->previousPageUrl() }}">Anterior</a>
                    @endif

                    @if ($costCenters->hasMorePages())
                        <a class="button ghost" href="{{ $costCenters->nextPageUrl() }}">Próxima</a>
                    @else
                        <span class="button ghost is-disabled">Próxima</span>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>
