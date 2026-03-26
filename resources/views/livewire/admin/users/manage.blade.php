<div class="stack-lg">
    <section class="hero-panel">
        <div>
            <span class="hero-label">Gestão de usuários</span>
            <h2>Cadastre solicitantes, gestores, financeiro e administradores com papéis claros.</h2>
            <p>
                Esta área concentra perfis, vínculos organizacionais, gestor aprovador e ativação da conta para operação no painel e na API.
            </p>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Usuários cadastrados</span>
                <strong>{{ $users->count() }}</strong>
                <span>Total de contas corporativas carregadas na plataforma.</span>
            </div>
            <div class="summary-block">
                <span class="label">Gestores e financeiro</span>
                <strong>{{ $users->filter(fn ($user) => $user->getRoleNames()->first() !== 'requester')->count() }}</strong>
                <span>Perfis com responsabilidade de aprovação, análise ou administração.</span>
            </div>
        </div>
    </section>

    <section class="grid-2">
        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Cadastro de usuário</h3>
                    <p class="section-copy">Mantenha o vínculo de empresa, centro de custo, gestor e perfil de acesso.</p>
                </div>
                <button class="button ghost" type="button" wire:click="resetUserForm">Novo usuário</button>
            </div>

            <div class="info-grid">
                <div class="field">
                    <label>Nome</label>
                    <input type="text" wire:model="userForm.name">
                    @error('userForm.name') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>E-mail</label>
                    <input type="email" wire:model="userForm.email">
                    @error('userForm.email') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Matrícula</label>
                    <input type="text" wire:model="userForm.employee_code">
                    @error('userForm.employee_code') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Perfil</label>
                    <select wire:model="userForm.role">
                        <option value="">Selecione</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ \App\Support\AdminLabel::role($role->name) }}</option>
                        @endforeach
                    </select>
                    @error('userForm.role') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Empresa</label>
                    <select wire:model="userForm.company_id">
                        <option value="">Selecione</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Departamento</label>
                    <select wire:model="userForm.department_id">
                        <option value="">Selecione</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Centro de custo</label>
                    <select wire:model="userForm.cost_center_id">
                        <option value="">Selecione</option>
                        @foreach ($costCenters as $costCenter)
                            <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Gestor aprovador</label>
                    <select wire:model="userForm.manager_id">
                        <option value="">Sem gestor</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>{{ $editingUserPublicId ? 'Nova senha opcional' : 'Senha inicial' }}</label>
                    <input type="password" wire:model="userForm.password">
                    @error('userForm.password') <span class="muted">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label>Status</label>
                    <select wire:model="userForm.is_active">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <button class="button" type="button" wire:click="saveUser">{{ $editingUserPublicId ? 'Atualizar usuário' : 'Salvar usuário' }}</button>
            </div>
        </article>

        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Usuários cadastrados</h3>
                    <p class="section-copy">Busca por nome, e-mail ou matrícula para revisar rapidamente a base administrativa.</p>
                </div>
            </div>

            <div class="field">
                <label>Busca</label>
                <input type="text" wire:model.live="search" placeholder="Nome, e-mail ou matrícula">
            </div>

            <div class="stack">
                @foreach ($users as $user)
                    <div class="list-card">
                        <div>
                            <strong>{{ $user->name }}</strong>
                            <div class="secondary-text">
                                {{ $user->email }} |
                                {{ \App\Support\AdminLabel::role($user->getRoleNames()->first() ?? 'sem perfil') }} |
                                Gestor: {{ $user->manager?->name ?? 'Não definido' }}
                            </div>
                            <div class="secondary-text">
                                {{ $user->company?->trade_name ?? 'Sem empresa' }} |
                                {{ $user->department?->name ?? 'Sem departamento' }} |
                                {{ $user->costCenter?->name ?? 'Sem centro de custo' }}
                            </div>
                        </div>
                        <div class="list-meta">
                            <span class="status-pill">{{ $user->is_active ? 'Ativo' : 'Inativo' }}</span>
                            <button class="button ghost" type="button" wire:click="editUser('{{ $user->public_id }}')">Editar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>
</div>
