<div class="stack-lg">
    <section class="hero-panel">
        <div>
            <span class="hero-label">Políticas e conformidade</span>
            <h2>Regras de aprovação, limites, categorias, reprovações e parâmetros de fraude.</h2>
            <p>
                Esta camada concentra a parametrização que sustenta as alçadas, os bloqueios, os alertas e a operação do financeiro.
            </p>
        </div>

        <div class="hero-summary">
            <div class="summary-block">
                <span class="label">Regras de aprovação</span>
                <strong>{{ $approvalRules->count() }}</strong>
                <span>Alçadas ativas no fluxo gerencial e financeiro.</span>
            </div>
            <div class="summary-block">
                <span class="label">Categorias</span>
                <strong>{{ $expenseCategories->count() }}</strong>
                <span>Classificações de despesa com politica de anexo.</span>
            </div>
            <div class="summary-block">
                <span class="label">Alertas de fraude</span>
                <strong>{{ $fraudRules->count() }}</strong>
                <span>Parâmetros de criticidade e rastreio de inconsistências.</span>
            </div>
        </div>
    </section>

    <section class="grid-2">
        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Regras de aprovação</h3>
                    <p class="section-copy">Defina etapa, escopo organizacional e faixa de valor para a decisão.</p>
                </div>
                <button class="button ghost" type="button" wire:click="resetApprovalRuleForm">Nova regra</button>
            </div>

            <div class="toolbar-grid">
                <div class="field">
                    <label>Nome</label>
                    <input type="text" wire:model="approvalRuleForm.name">
                </div>
                <div class="field">
                    <label>Etapa</label>
                    <select wire:model="approvalRuleForm.stage">
                        @foreach ($stageOptions as $stage)
                            <option value="{{ $stage->value }}">{{ \App\Support\AdminLabel::approvalStage($stage) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Empresa</label>
                    <select wire:model="approvalRuleForm.company_id">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->trade_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Departamento</label>
                    <select wire:model="approvalRuleForm.department_id">
                        <option value="">Todos</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Centro de custo</label>
                    <select wire:model="approvalRuleForm.cost_center_id">
                        <option value="">Todos</option>
                        @foreach ($costCenters as $costCenter)
                            <option value="{{ $costCenter->id }}">{{ $costCenter->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Aprovações requeridas</label>
                    <input type="number" wire:model="approvalRuleForm.required_approvals" min="1" max="5">
                </div>
                <div class="field">
                    <label>Valor minimo</label>
                    <input type="number" step="0.01" wire:model="approvalRuleForm.min_amount">
                </div>
                <div class="field">
                    <label>Valor máximo</label>
                    <input type="number" step="0.01" wire:model="approvalRuleForm.max_amount">
                </div>
                <div class="field">
                    <label>Status</label>
                    <select wire:model="approvalRuleForm.is_active">
                        <option value="1">Ativa</option>
                        <option value="0">Inativa</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <button class="button" type="button" wire:click="saveApprovalRule">{{ $editingApprovalRulePublicId ? 'Atualizar regra' : 'Salvar regra' }}</button>
            </div>

            <div class="stack">
                @foreach ($approvalRules as $rule)
                    <div class="list-card">
                        <div>
                            <strong>{{ $rule->name }}</strong>
                            <div class="secondary-text">
                                {{ \App\Support\AdminLabel::approvalStage($rule->stage) }} |
                                {{ $rule->department?->name ?? 'Todos os departamentos' }} |
                                {{ $rule->costCenter?->name ?? 'Todos os centros' }}
                            </div>
                        </div>
                        <div class="list-meta">
                            <span class="status-pill">{{ $rule->is_active ? 'Ativa' : 'Inativa' }}</span>
                            <button class="button ghost" type="button" wire:click="editApprovalRule('{{ $rule->public_id }}')">Editar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Categorias e reprovações</h3>
                    <p class="section-copy">Configure politica de anexo, limites por categoria e motivos padrão de reprovar.</p>
                </div>
            </div>

            <div class="action-group">
                <h4>Categoria de despesa</h4>
                <div class="info-grid">
                    <div class="field"><label>Código</label><input type="text" wire:model="expenseCategoryForm.code"></div>
                    <div class="field"><label>Nome</label><input type="text" wire:model="expenseCategoryForm.name"></div>
                    <div class="field"><label>Valor máximo</label><input type="number" step="0.01" wire:model="expenseCategoryForm.max_amount"></div>
                    <div class="field">
                        <label>Anexo obrigatorio</label>
                        <select wire:model="expenseCategoryForm.requires_attachment">
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select wire:model="expenseCategoryForm.is_active">
                            <option value="1">Ativa</option>
                            <option value="0">Inativa</option>
                        </select>
                    </div>
                </div>
                <button class="button" type="button" wire:click="saveExpenseCategory">{{ $editingExpenseCategoryPublicId ? 'Atualizar categoria' : 'Salvar categoria' }}</button>

                <div class="stack">
                    @foreach ($expenseCategories as $category)
                        <div class="list-card">
                            <div>
                                <strong>{{ $category->name }}</strong>
                                <div class="secondary-text">{{ $category->code }} | Limite: R$ {{ number_format($category->max_amount ?? 0, 2, ',', '.') }}</div>
                            </div>
                            <div class="list-meta">
                                <span class="status-pill">{{ $category->requires_attachment ? 'Anexo exigido' : 'Sem anexo' }}</span>
                                <button class="button ghost" type="button" wire:click="editExpenseCategory('{{ $category->public_id }}')">Editar</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="action-group">
                <h4>Motivo de reprovação</h4>
                <div class="info-grid">
                    <div class="field"><label>Código</label><input type="text" wire:model="rejectionReasonForm.code"></div>
                    <div class="field"><label>Nome</label><input type="text" wire:model="rejectionReasonForm.name"></div>
                    <div class="field"><label>Aplica em</label><input type="text" wire:model="rejectionReasonForm.applies_to"></div>
                    <div class="field">
                        <label>Status</label>
                        <select wire:model="rejectionReasonForm.is_active">
                            <option value="1">Ativo</option>
                            <option value="0">Inativo</option>
                        </select>
                    </div>
                </div>
                <button class="button" type="button" wire:click="saveRejectionReason">{{ $editingRejectionReasonPublicId ? 'Atualizar motivo' : 'Salvar motivo' }}</button>

                <div class="stack">
                    @foreach ($rejectionReasons as $reason)
                        <div class="list-card">
                            <div>
                                <strong>{{ $reason->name }}</strong>
                                <div class="secondary-text">{{ $reason->code }} | {{ $reason->applies_to }}</div>
                            </div>
                            <div class="list-meta">
                                <span class="status-pill">{{ $reason->is_active ? 'Ativo' : 'Inativo' }}</span>
                                <button class="button ghost" type="button" wire:click="editRejectionReason('{{ $reason->public_id }}')">Editar</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>
    </section>

    <section class="grid-2">
        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Regras de fraude</h3>
                    <p class="section-copy">Criticidade e ativação dos detectores que abastecem a fila de conformidade.</p>
                </div>
                <button class="button ghost" type="button" wire:click="resetFraudRuleForm">Nova regra</button>
            </div>

            <div class="info-grid">
                <div class="field"><label>Código</label><input type="text" wire:model="fraudRuleForm.code"></div>
                <div class="field"><label>Nome</label><input type="text" wire:model="fraudRuleForm.name"></div>
                <div class="field">
                    <label>Criticidade</label>
                    <select wire:model="fraudRuleForm.severity">
                        @foreach ($severityOptions as $severity)
                            <option value="{{ $severity->value }}">{{ \App\Support\AdminLabel::fraudSeverity($severity) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select wire:model="fraudRuleForm.is_active">
                        <option value="1">Ativa</option>
                        <option value="0">Inativa</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <button class="button" type="button" wire:click="saveFraudRule">{{ $editingFraudRulePublicId ? 'Atualizar regra de fraude' : 'Salvar regra de fraude' }}</button>
            </div>

            <div class="stack">
                @foreach ($fraudRules as $rule)
                    <div class="list-card">
                        <div>
                            <strong>{{ $rule->name }}</strong>
                            <div class="secondary-text">{{ $rule->code }}</div>
                        </div>
                        <div class="list-meta">
                            <span class="status-pill">{{ \App\Support\AdminLabel::fraudSeverity($rule->severity) }}</span>
                            <button class="button ghost" type="button" wire:click="editFraudRule('{{ $rule->public_id }}')">Editar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="section-card stack">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Limites operacionais</h3>
                    <p class="section-copy">Bloqueio de novo caixa, valor máximo e quantidade de caixas em aberto por escopo.</p>
                </div>
                <button class="button ghost" type="button" wire:click="resetCashLimitRuleForm">Novo limite</button>
            </div>

            <div class="toolbar-grid">
                <div class="field"><label>Nome</label><input type="text" wire:model="cashLimitRuleForm.name"></div>
                <div class="field">
                    <label>Escopo</label>
                    <select wire:model.live="cashLimitRuleForm.scope_type">
                        <option value="user">Usuário</option>
                        <option value="department">Departamento</option>
                        <option value="cost_center">Centro de custo</option>
                        <option value="company">Empresa</option>
                    </select>
                </div>
                <div class="field">
                    <label>Aplicar em</label>
                    <select wire:model="cashLimitRuleForm.scope_id">
                        <option value="">Selecione</option>
                        @foreach ($scopeOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Valor máximo</label><input type="number" step="0.01" wire:model="cashLimitRuleForm.max_amount"></div>
                <div class="field"><label>Máximo de caixas abertos</label><input type="number" min="1" wire:model="cashLimitRuleForm.max_open_requests"></div>
                <div class="field">
                    <label>Bloquear se houver pendência</label>
                    <select wire:model="cashLimitRuleForm.block_new_if_pending">
                        <option value="1">Sim</option>
                        <option value="0">Não</option>
                    </select>
                </div>
                <div class="field">
                    <label>Status</label>
                    <select wire:model="cashLimitRuleForm.is_active">
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <button class="button" type="button" wire:click="saveCashLimitRule">{{ $editingCashLimitRulePublicId ? 'Atualizar limite' : 'Salvar limite' }}</button>
            </div>

            <div class="stack">
                @foreach ($cashLimitRules as $rule)
                    <div class="list-card">
                        <div>
                            <strong>{{ $rule->name }}</strong>
                            <div class="secondary-text">
                                {{ \App\Support\AdminLabel::scopeType($rule->scope_type) }} |
                                max. R$ {{ number_format($rule->max_amount ?? 0, 2, ',', '.') }} |
                                {{ $rule->max_open_requests }} caixas
                            </div>
                        </div>
                        <div class="list-meta">
                            <span class="status-pill">{{ $rule->block_new_if_pending ? 'Bloqueia pendente' : 'Não bloqueia' }}</span>
                            <button class="button ghost" type="button" wire:click="editCashLimitRule('{{ $rule->public_id }}')">Editar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>
</div>
