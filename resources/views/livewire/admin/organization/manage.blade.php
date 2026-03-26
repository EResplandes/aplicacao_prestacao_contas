<div class="stack-lg">
    <section class="hero-panel">
        <div>
            <span class="hero-label">Estrutura organizacional</span>
            <h2>Cadastros base para empresas, departamentos, centros de custo e gestores.</h2>
            <p>
                Esta área organiza a malha administrativa que sustenta solicitações, aprovações, limites e trilha de auditoria.
            </p>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Empresas</span>
                <strong>{{ $companies->count() }}</strong>
                <span>Entidades corporativas habilitadas no painel.</span>
            </div>
            <div class="summary-block">
                <span class="label">Departamentos</span>
                <strong>{{ $departments->count() }}</strong>
                <span>Estruturas responsaveis por solicitações e aprovação.</span>
            </div>
            <div class="summary-block">
                <span class="label">Centros de custo</span>
                <strong>{{ $costCenters->count() }}</strong>
                <span>Classificação financeira para uso e controle do caixa.</span>
            </div>
        </div>
    </section>

    <section class="grid-2">
        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Empresas</h3>
                    <p class="section-copy">Cadastro de empresas ou unidades juridicas suportadas pelo sistema.</p>
                </div>
                <button class="button ghost" type="button" wire:click="resetCompanyForm">Nova empresa</button>
            </div>

            <div class="info-grid">
                <div class="field">
                    <label>Código</label>
                    <input type="text" wire:model="companyForm.code">
                    @error('companyForm.code') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Nome fantasia</label>
                    <input type="text" wire:model="companyForm.trade_name">
                    @error('companyForm.trade_name') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Razao social</label>
                    <input type="text" wire:model="companyForm.legal_name">
                    @error('companyForm.legal_name') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>CNPJ</label>
                    <input type="text" wire:model="companyForm.tax_id">
                    @error('companyForm.tax_id') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Status</label>
                    <select wire:model="companyForm.is_active">
                        <option value="1">Ativa</option>
                        <option value="0">Inativa</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <button class="button" type="button" wire:click="saveCompany">{{ $editingCompanyPublicId ? 'Atualizar empresa' : 'Salvar empresa' }}</button>
            </div>

            <div class="stack">
                @foreach ($companies as $company)
                    <div class="list-card">
                        <div>
                            <strong>{{ $company->trade_name }}</strong>
                            <div class="secondary-text">{{ $company->legal_name }} | {{ $company->code }}</div>
                        </div>
                        <div class="list-meta">
                            <span class="status-pill">{{ $company->is_active ? 'Ativa' : 'Inativa' }}</span>
                            <button class="button ghost" type="button" wire:click="editCompany('{{ $company->public_id }}')">Editar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Departamentos</h3>
                    <p class="section-copy">Vínculo entre empresa, gestor responsável e equipes que originam as solicitações.</p>
                </div>
                <button class="button ghost" type="button" wire:click="resetDepartmentForm">Novo departamento</button>
            </div>

            <div class="info-grid">
                <div class="field">
                    <label>Empresa</label>
                    <select wire:model="departmentForm.company_id">
                        <option value="">Selecione</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
                        @endforeach
                    </select>
                    @error('departmentForm.company_id') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Código</label>
                    <input type="text" wire:model="departmentForm.code">
                    @error('departmentForm.code') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Nome</label>
                    <input type="text" wire:model="departmentForm.name">
                    @error('departmentForm.name') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Gestor responsável</label>
                    <select wire:model="departmentForm.manager_user_id">
                        <option value="">Sem gestor</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select wire:model="departmentForm.is_active">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <button class="button" type="button" wire:click="saveDepartment">{{ $editingDepartmentPublicId ? 'Atualizar departamento' : 'Salvar departamento' }}</button>
            </div>

            <div class="stack">
                @foreach ($departments as $department)
                    <div class="list-card">
                        <div>
                            <strong>{{ $department->name }}</strong>
                            <div class="secondary-text">
                                {{ $department->company?->trade_name ?? 'Sem empresa' }} |
                                Gestor: {{ $department->manager?->name ?? 'Não definido' }} |
                                {{ $department->costCenters->count() }} centros de custo
                            </div>
                        </div>
                        <div class="list-meta">
                            <span class="status-pill">{{ $department->is_active ? 'Ativo' : 'Inativo' }}</span>
                            <button class="button ghost" type="button" wire:click="editDepartment('{{ $department->public_id }}')">Editar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="section-card stack">
        <div class="section-header">
            <div>
                <h3 class="section-title">Centros de custo</h3>
                <p class="section-copy">Estrutura financeira usada nos filtros, limites e trilhas de prestação de contas.</p>
            </div>
            <button class="button ghost" type="button" wire:click="resetCostCenterForm">Novo centro de custo</button>
        </div>

        <div class="toolbar-grid">
            <div class="field">
                <label>Empresa</label>
                <select wire:model="costCenterForm.company_id">
                    <option value="">Selecione</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Departamento</label>
                <select wire:model="costCenterForm.department_id">
                    <option value="">Selecione</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>Código</label>
                <input type="text" wire:model="costCenterForm.code">
            </div>
            <div class="field">
                <label>Nome</label>
                <input type="text" wire:model="costCenterForm.name">
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
            <button class="button" type="button" wire:click="saveCostCenter">{{ $editingCostCenterPublicId ? 'Atualizar centro de custo' : 'Salvar centro de custo' }}</button>
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
                    @foreach ($costCenters as $costCenter)
                        <tr>
                            <td>{{ $costCenter->code }}</td>
                            <td>{{ $costCenter->name }}</td>
                            <td>{{ $costCenter->company?->trade_name ?? '-' }}</td>
                            <td>{{ $costCenter->department?->name ?? '-' }}</td>
                            <td><span class="status-pill">{{ $costCenter->is_active ? 'Ativo' : 'Inativo' }}</span></td>
                            <td><button class="button ghost" type="button" wire:click="editCostCenter('{{ $costCenter->public_id }}')">Editar</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
