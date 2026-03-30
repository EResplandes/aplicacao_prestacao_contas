@php
    $text = static fn (string $value): string => html_entity_decode($value, ENT_QUOTES, 'UTF-8');
@endphp

<div class="stack-lg" wire:key="cash-request-detail-{{ $cashRequest->public_id }}">
    @if ($feedbackMessage)
        <div class="{{ $feedbackTone === 'error' ? 'rounded-[20px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700' : 'flash' }}">
            {{ $feedbackMessage }}
        </div>
    @endif

    <section class="grid-2">
        <article class="section-card stack">
            <div class="card-head">
                <div>
                    <span class="hero-label">Solicitação em acompanhamento</span>
                    <h3 class="section-title mt-3.5">{{ $cashRequest->request_number }}</h3>
                    <p class="section-copy">
                        {{ $cashRequest->user->name }} |
                        {{ $cashRequest->department?->name ?? 'Sem departamento' }} |
                        {{ $cashRequest->costCenter?->name ?? 'Sem centro de custo' }}
                    </p>
                </div>
                <span class="status-pill">{{ \App\Support\AdminLabel::cashRequestStatus($cashRequest->status) }}</span>
            </div>

            <div class="summary-grid">
                <div class="summary-tile">
                    <span class="label">Valor solicitado</span>
                    <strong>R$ {{ number_format($cashRequest->requested_amount, 2, ',', '.') }}</strong>
                    <span>Valor informado pelo colaborador na abertura do fluxo.</span>
                </div>
                <div class="summary-tile">
                    <span class="label">Valor aprovado</span>
                    <strong>R$ {{ number_format($cashRequest->approved_amount ?? 0, 2, ',', '.') }}</strong>
                    <span>Montante aprovado após as etapas gerencial e financeira.</span>
                </div>
                <div class="summary-tile">
                    <span class="label">Saldo atual</span>
                    <strong>R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</strong>
                    <span>Saldo restante disponível para uso neste caixa.</span>
                </div>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <span class="label">Finalidade</span>
                    <p>{{ $cashRequest->purpose }}</p>
                </div>
                <div class="detail-item">
                    <span class="label">Data prevista de uso</span>
                    <p>{{ $cashRequest->planned_use_date?->format('d/m/Y') ?? 'Não informada' }}</p>
                </div>
                <div class="detail-item">
                    <span class="label">Justificativa</span>
                    <p>{{ $cashRequest->justification }}</p>
                </div>
                <div class="detail-item">
                    <span class="label">Prazo de prestação</span>
                    <p>{{ $cashRequest->due_accountability_at?->format('d/m/Y H:i') ?? 'Não definido' }}</p>
                </div>
            </div>
        </article>

        <article class="section-card stack">
            <div class="card-head">
                <div>
                    <span class="hero-label">Ações administrativas</span>
                    <h3 class="section-title mt-3.5">Decisão e liberação</h3>
                    <p class="section-copy">
                        Os comandos abaixo continuam usando as actions reutilizáveis do domínio para manter a regra centralizada.
                    </p>
                </div>
            </div>

            <div class="action-group" wire:key="manager-stage-{{ $cashRequest->public_id }}-{{ $cashRequest->status->value }}">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <h4>Etapa gerencial</h4>
                    @if ($cashRequest->status === \App\Enums\CashRequestStatus::AWAITING_MANAGER_APPROVAL)
                        <span class="status-pill is-warning">Aguardando gestor</span>
                    @elseif ($hasManagerApproval)
                        <span class="status-pill is-success">Gestor aprovou</span>
                    @elseif ($requiresManagerApproval)
                        <span class="status-pill is-danger">Gestor reprovou</span>
                    @else
                        <span class="status-pill is-neutral">Sem etapa gerencial</span>
                    @endif
                </div>

                @if ($canTakeManagerDecision)
                    <div class="field">
                        <label for="managerComment">Observação gerencial</label>
                        <textarea id="managerComment" rows="3" wire:model.defer="managerComment"></textarea>
                    </div>
                    <div class="field">
                        <label for="rejectionReasonManager">Motivo de reprovação</label>
                        <select id="rejectionReasonManager" wire:model.defer="rejectionReasonPublicId">
                            <option value="">Selecione</option>
                            @foreach ($rejectionReasons as $reason)
                                <option value="{{ $reason->public_id }}">{{ $reason->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <button class="button" type="button" wire:click.prevent="approveManager" wire:loading.attr="disabled" wire:target="approveManager">
                            <span wire:loading.remove wire:target="approveManager">Aprovar gestor</span>
                            <span wire:loading wire:target="approveManager">Registrando...</span>
                        </button>
                        <button class="button danger" type="button" wire:click.prevent="rejectManager" wire:loading.attr="disabled" wire:target="rejectManager">
                            <span wire:loading.remove wire:target="rejectManager">Reprovar gestor</span>
                            <span wire:loading wire:target="rejectManager">Registrando...</span>
                        </button>
                    </div>
                @elseif ($cashRequest->status === \App\Enums\CashRequestStatus::AWAITING_MANAGER_APPROVAL)
                    <div class="empty-state">
                        Esta etapa está aguardando decisão do gestor responsável ou de um perfil com acesso administrativo.
                    </div>
                @elseif ($hasManagerApproval)
                    <div class="alert-inline">
                        A etapa do gestor já foi concluída. A timeline abaixo mostra a data exata da aprovação.
                    </div>
                @elseif ($requiresManagerApproval)
                    <div class="empty-state">
                        Esta solicitação não está mais aguardando decisão gerencial. Consulte a timeline para ver a data da reprovação.
                    </div>
                @else
                    <div class="empty-state">
                        Este colaborador não possui gestor vinculado, então o fluxo segue direto para o financeiro.
                    </div>
                @endif
            </div>

            <div class="action-group" wire:key="financial-stage-{{ $cashRequest->public_id }}-{{ $cashRequest->status->value }}">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <h4>Etapa financeira</h4>
                    @if ($canTakeFinancialDecision)
                        <span class="status-pill is-warning">Pronta para decisão</span>
                    @elseif (! $canHandleFinancialStage)
                        <span class="status-pill is-neutral">Aguardando gestor</span>
                    @elseif ($cashRequest->status === \App\Enums\CashRequestStatus::FINANCIAL_REJECTED)
                        <span class="status-pill is-danger">Reprovada</span>
                    @elseif ($cashRequest->status === \App\Enums\CashRequestStatus::FINANCIAL_APPROVED || $cashRequest->released_at)
                        <span class="status-pill is-success">Aprovada</span>
                    @else
                        <span class="status-pill is-neutral">Em acompanhamento</span>
                    @endif
                </div>

                @if ($canTakeFinancialDecision)
                    <div class="field">
                        <label for="financialComment">Observação financeira</label>
                        <textarea id="financialComment" rows="3" wire:model.defer="financialComment"></textarea>
                    </div>
                    <div class="field">
                        <label for="financialDueAccountabilityAt">Prazo de fechamento do caixa</label>
                        <input id="financialDueAccountabilityAt" type="datetime-local" wire:model.defer="financialDueAccountabilityAt">
                        <span class="secondary-text">
                            Defina a data limite para o colaborador prestar contas e encerrar o caixa.
                        </span>
                        @error('financialDueAccountabilityAt')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="field">
                        <label for="rejectionReasonFinancial">Motivo de reprovação</label>
                        <select id="rejectionReasonFinancial" wire:model.defer="rejectionReasonPublicId">
                            <option value="">Selecione</option>
                            @foreach ($rejectionReasons as $reason)
                                <option value="{{ $reason->public_id }}">{{ $reason->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <button class="button" type="button" wire:click.prevent="approveFinancial" wire:loading.attr="disabled" wire:target="approveFinancial">
                            <span wire:loading.remove wire:target="approveFinancial">Aprovar financeiro</span>
                            <span wire:loading wire:target="approveFinancial">Registrando...</span>
                        </button>
                        <button class="button danger" type="button" wire:click.prevent="rejectFinancial" wire:loading.attr="disabled" wire:target="rejectFinancial">
                            <span wire:loading.remove wire:target="rejectFinancial">Reprovar financeiro</span>
                            <span wire:loading wire:target="rejectFinancial">Registrando...</span>
                        </button>
                    </div>
                @elseif (! $canHandleFinancialStage)
                    <div class="empty-state">
                        A etapa financeira só fica disponível depois que o gestor do colaborador aprovar a solicitação.
                    </div>
                @elseif ($cashRequest->status === \App\Enums\CashRequestStatus::AWAITING_FINANCIAL_APPROVAL)
                    <div class="empty-state">
                        Somente administrador e financeiro podem concluir a decisão desta etapa.
                    </div>
                @else
                    <div class="empty-state">
                        A decisão financeira já foi registrada ou esta solicitação ainda não está em uma fase financeira editável.
                    </div>
                @endif
            </div>

            <div class="action-group" wire:key="release-stage-{{ $cashRequest->public_id }}-{{ $cashRequest->status->value }}">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <h4>Liberação de valor</h4>
                    @if ($canRegisterRelease)
                        <span class="status-pill is-warning">Disponível para liberar</span>
                    @elseif ($cashRequest->released_at)
                        <span class="status-pill is-success">Liberação registrada</span>
                    @elseif (! $canHandleFinancialStage)
                        <span class="status-pill is-neutral">Aguardando gestor</span>
                    @else
                        <span class="status-pill is-neutral">Aguardando financeiro</span>
                    @endif
                </div>

                @php($latestDeposit = $cashRequest->deposits->sortByDesc('released_at')->first())

                @if ($canRegisterRelease)
                    <div class="info-grid">
                        <div class="field">
                            <label for="releaseAmount">Valor da liberação</label>
                            <input id="releaseAmount" type="number" step="0.01" wire:model.defer="releaseAmount">
                            @error('releaseAmount')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="field">
                            <label for="releasePaymentMethod">Meio de pagamento</label>
                            <select id="releasePaymentMethod" wire:model.defer="releasePaymentMethod">
                                @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                    <option value="{{ $method->value }}">{{ \App\Support\AdminLabel::paymentMethod($method) }}</option>
                                @endforeach
                            </select>
                            @error('releasePaymentMethod')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="releaseReceipt">Comprovante de pagamento</label>
                        <input id="releaseReceipt" type="file" wire:model="releaseReceipt" accept=".pdf,image/png,image/jpeg,image/webp">
                        <span class="secondary-text">
                            Anexe o comprovante do pagamento antes de registrar a liberação do valor.
                        </span>
                        <div class="secondary-text" wire:loading wire:target="releaseReceipt">Enviando comprovante...</div>
                        @if ($releaseReceipt)
                            <div class="secondary-text">Arquivo selecionado: {{ $releaseReceipt->getClientOriginalName() }}</div>
                        @endif
                        @error('releaseReceipt')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="alert-inline">
                        O fluxo financeiro só pode ser concluído com o comprovante de pagamento anexado.
                    </div>
                    <button class="button" type="button" wire:click.prevent="release" wire:loading.attr="disabled" wire:target="release,releaseReceipt">
                        <span wire:loading.remove wire:target="release">Registrar liberação</span>
                        <span wire:loading wire:target="release">Registrando...</span>
                        <span wire:loading wire:target="releaseReceipt">Aguarde o upload...</span>
                    </button>
                @elseif ($cashRequest->released_at)
                    <details class="expense-expand" open>
                        <summary class="expense-summary">
                            <div>
                                <strong>Pagamento registrado</strong>
                                <div class="secondary-text">{{ $text('Libera&ccedil;&atilde;o conclu&iacute;da em') }} {{ $cashRequest->released_at->format('d/m/Y H:i') }}.</div>
                            </div>
                            <div class="list-meta">
                                <span class="status-pill is-success">Comprovado</span>
                                <strong>Ver anexos</strong>
                            </div>
                        </summary>

                        <div class="expense-detail">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="label">Valor liberado</span>
                                    <p>R$ {{ number_format($latestDeposit?->amount ?? $cashRequest->released_amount, 2, ',', '.') }}</p>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Meio de pagamento</span>
                                    <p>{{ $latestDeposit?->payment_method ? \App\Support\AdminLabel::paymentMethod($latestDeposit->payment_method) : $text('N&atilde;o informado') }}</p>
                                </div>
                                <div class="detail-item">
                                    <span class="label">{{ $text('Respons&aacute;vel pela libera&ccedil;&atilde;o') }}</span>
                                    <p>{{ $latestDeposit?->releasedBy?->name ?? $text('N&atilde;o informado') }}</p>
                                </div>
                                <div class="detail-item">
                                    <span class="label">{{ $text('Refer&ecirc;ncia') }}</span>
                                    <p>{{ $latestDeposit?->reference_number ?? $text('Sem n&uacute;mero de refer&ecirc;ncia') }}</p>
                                </div>
                            </div>

                            @if ($latestDeposit && $latestDeposit->attachments->isNotEmpty())
                                <div class="attachment-links">
                                    @foreach ($latestDeposit->attachments as $attachment)
                                        @php($attachmentUrl = \Illuminate\Support\Facades\Storage::disk($attachment->disk)->url($attachment->path))
                                        <details class="attachment-preview">
                                            <summary class="attachment-link">
                                                <div class="attachment-link-copy">
                                                    <strong>{{ $attachment->original_name }}</strong>
                                                    <span>
                                                        Comprovante do pagamento
                                                        @if ($attachment->mime_type)
                                                            | {{ strtoupper($attachment->mime_type) }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <span>Preview</span>
                                            </summary>

                                            <div class="attachment-preview-panel">
                                                @if (str_starts_with((string) $attachment->mime_type, 'image/'))
                                                    <img
                                                        class="attachment-preview-image"
                                                        src="{{ $attachmentUrl }}"
                                                        alt="{{ $attachment->original_name }}"
                                                        loading="lazy"
                                                    >
                                                @elseif ($attachment->mime_type === 'application/pdf' || str_ends_with(strtolower($attachment->original_name), '.pdf'))
                                                    <iframe
                                                        class="attachment-preview-frame"
                                                        src="{{ $attachmentUrl }}"
                                                        title="{{ $attachment->original_name }}"
                                                    ></iframe>
                                                @else
                                                    <div class="empty-state">
                                                        Preview inline indisponível para este formato.
                                                    </div>
                                                @endif

                                                <a class="button secondary" href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer">
                                                    Abrir arquivo completo
                                                </a>
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">Nenhum comprovante de pagamento foi localizado para esta liberação.</div>
                            @endif
                        </div>
                    </details>
                    <div class="alert-inline">
                        A liberação já foi registrada em {{ $cashRequest->released_at->format('d/m/Y H:i') }}.
                    </div>
                @elseif (! $canHandleFinancialStage)
                    <div class="empty-state">
                        A liberação só aparece para o financeiro depois que o gestor do funcionário aprovar a solicitação.
                    </div>
                @elseif ($cashRequest->status === \App\Enums\CashRequestStatus::FINANCIAL_APPROVED)
                    <div class="empty-state">
                        Somente administrador e financeiro podem registrar a liberação deste caixa.
                    </div>
                @else
                    <div class="empty-state">
                        A liberação será habilitada somente após a aprovação financeira.
                    </div>
                @endif
            </div>
        </article>
    </section>

    <section class="section-card stack">
        <div class="section-header">
            <div>
                <h3 class="section-title">Linha do tempo do caixa</h3>
                <p class="section-copy">
                    Acompanhe a data da solicitação, a aprovação do gestor, a aprovação do financeiro, o pagamento e o fechamento do caixa.
                </p>
            </div>
        </div>

        <div class="flow-timeline">
            @foreach ($flowTimeline as $step)
                <article class="flow-step flow-step--{{ $step['state'] }}">
                    <div class="flow-step-marker" aria-hidden="true">
                        <span class="flow-step-order">
                            {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        @unless ($loop->last)
                            <span class="flow-step-line"></span>
                        @endunless
                    </div>

                    <div class="flow-step-body">
                        <div class="flow-step-head">
                            <div>
                                <div class="flow-step-eyebrow">
                                    Etapa {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </div>
                                <h4 class="flow-step-title">{{ $step['title'] }}</h4>
                                <p class="flow-step-copy">{{ $step['description'] }}</p>
                            </div>

                            <span class="flow-step-badge">
                                {{ $step['badge'] }}
                            </span>
                        </div>
                    </div>

                    <div class="flow-step-side">
                        <div class="flow-step-date-label">
                            {{ $step['date_label'] }}
                        </div>
                        <strong class="flow-step-date-value">
                            {{ $step['date'] ?? 'Ainda sem data registrada' }}
                        </strong>
                        <span class="flow-step-date-actor">
                            {{ $step['actor'] ?? 'Sem responsável registrado' }}
                        </span>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @if (\App\Support\AdminPanel::canViewFinanceChat(auth()->user(), $cashRequest))
        <livewire:admin.cash-requests.chat-panel
            :cash-request="$cashRequest"
            :wire:key="'cash-request-chat-'.$cashRequest->public_id"
        />
    @endif

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Histórico de aprovações</h3>
                    <p class="section-copy">Cada decisão do fluxo gerencial e financeiro fica disponível para auditoria.</p>
                </div>
            </div>

            <div class="timeline">
                @forelse ($cashRequest->approvals as $approval)
                    <div class="timeline-item">
                        <strong>{{ \App\Support\AdminLabel::approvalStage($approval->stage) }} | {{ \App\Support\AdminLabel::approvalDecision($approval->decision) }}</strong>
                        <div class="secondary-text">{{ $approval->actor?->name }} | {{ $approval->acted_at?->format('d/m/Y H:i') }}</div>
                        <div class="secondary-text mt-2">{{ $approval->comment ?: 'Sem observação complementar.' }}</div>
                    </div>
                @empty
                    <div class="empty-state">Nenhuma aprovação registrada até o momento.</div>
                @endforelse
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Reprovações e respostas</h3>
                    <p class="section-copy">Acompanhe recusas, ajustes e o retorno do colaborador.</p>
                </div>
            </div>

            <div class="timeline">
                @forelse ($cashRequest->rejections as $rejection)
                    <div class="timeline-item">
                        <strong>{{ \App\Support\AdminLabel::approvalStage($rejection->stage) }} | {{ $rejection->reason?->name ?? 'Sem motivo padrão' }}</strong>
                        <div class="secondary-text mt-2">{{ $rejection->comment ?: 'Sem comentário adicional.' }}</div>
                        @if ($rejection->response_comment)
                            <div class="alert-inline">Resposta do usuário: {{ $rejection->response_comment }}</div>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">Nenhuma reprovação registrada para esta solicitação.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid-2">
        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Gastos vinculados</h3>
                    <p class="section-copy">Leitura rápida dos lançamentos enviados pelo colaborador com status e sinais de risco.</p>
                </div>
            </div>

            <div class="stack">
                @forelse ($cashRequest->expenses as $expense)
                    <details class="expense-expand">
                        <summary class="expense-summary">
                        <div>
                            <strong>{{ $expense->description }}</strong>
                            <div class="secondary-text">
                                {{ $expense->vendor_name ?? 'Fornecedor não informado' }} |
                                {{ $expense->spent_at?->format('d/m/Y H:i') ?? 'Sem data' }}
                            </div>
                            <div class="secondary-text">
                                {{ $expense->attachments->count() }} anexos |
                                {{ $expense->fraudAlerts->count() }} alertas
                            </div>
                            @if ($expense->location_latitude !== null && $expense->location_longitude !== null)
                                <div class="secondary-text">
                                    Localização:
                                    {{ number_format((float) $expense->location_latitude, 5, ',', '.') }},
                                    {{ number_format((float) $expense->location_longitude, 5, ',', '.') }}
                                    @if ($expense->location_accuracy_meters)
                                        | Precisão {{ number_format((float) $expense->location_accuracy_meters, 1, ',', '.') }} m
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="list-meta">
                            <span class="status-pill">{{ \App\Support\AdminLabel::cashExpenseStatus($expense->status) }}</span>
                            <strong>R$ {{ number_format($expense->amount, 2, ',', '.') }}</strong>
                        </div>
                        </summary>

                        <div class="expense-detail">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span class="label">Fornecedor</span>
                                    <p>{{ $expense->vendor_name ?? 'Não informado' }}</p>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Data do gasto</span>
                                    <p>{{ $expense->spent_at?->format('d/m/Y H:i') ?? 'Sem data' }}</p>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Documento</span>
                                    <p>{{ $expense->document_number ?? 'Não informado' }}</p>
                                </div>
                                <div class="detail-item">
                                    <span class="label">Forma de pagamento</span>
                                    <p>{{ $expense->payment_method ? \App\Support\AdminLabel::paymentMethod($expense->payment_method) : 'Não informada' }}</p>
                                </div>
                            </div>

                            @if ($expense->notes)
                                <div class="detail-item">
                                    <span class="label">Observações</span>
                                    <p>{{ $expense->notes }}</p>
                                </div>
                            @endif

                            <div class="detail-item">
                                <span class="label">Revisão administrativa</span>
                                <p>
                                    @if ($expense->reviewed_at)
                                        Revisado por {{ $expense->reviewedBy?->name ?? 'Usuário não identificado' }}
                                        em {{ $expense->reviewed_at->format('d/m/Y H:i') }}.
                                    @else
                                        Este gasto ainda não recebeu validação administrativa.
                                    @endif
                                </p>
                            </div>

                            @if ($expense->review_notes)
                                <div class="detail-item">
                                    <span class="label">Parecer da revisão</span>
                                    <p>{{ $expense->review_notes }}</p>
                                </div>
                            @endif

                            @if ($canReviewExpenses)
                                <div class="field">
                                    <label for="expense-review-{{ $expense->public_id }}">Observação da revisão</label>
                                    <textarea
                                        id="expense-review-{{ $expense->public_id }}"
                                        rows="3"
                                        wire:model.defer="expenseReviewNotes.{{ $expense->public_id }}"
                                    ></textarea>
                                    <span class="secondary-text">
                                        Registre o parecer antes de aprovar, reprovar ou sinalizar o gasto.
                                    </span>
                                </div>

                                <div class="row">
                                    <button
                                        class="button secondary"
                                        type="button"
                                        wire:click.prevent="approveExpense('{{ $expense->public_id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="approveExpense('{{ $expense->public_id }}')"
                                    >
                                        <span wire:loading.remove wire:target="approveExpense('{{ $expense->public_id }}')">Dar ok no gasto</span>
                                        <span wire:loading wire:target="approveExpense('{{ $expense->public_id }}')">Salvando...</span>
                                    </button>
                                    <button
                                        class="button danger"
                                        type="button"
                                        wire:click.prevent="rejectExpense('{{ $expense->public_id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="rejectExpense('{{ $expense->public_id }}')"
                                    >
                                        <span wire:loading.remove wire:target="rejectExpense('{{ $expense->public_id }}')">Reprovar gasto</span>
                                        <span wire:loading wire:target="rejectExpense('{{ $expense->public_id }}')">Salvando...</span>
                                    </button>
                                    <button
                                        class="button ghost"
                                        type="button"
                                        wire:click.prevent="flagExpense('{{ $expense->public_id }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="flagExpense('{{ $expense->public_id }}')"
                                    >
                                        <span wire:loading.remove wire:target="flagExpense('{{ $expense->public_id }}')">Sinalizar gasto</span>
                                        <span wire:loading wire:target="flagExpense('{{ $expense->public_id }}')">Salvando...</span>
                                    </button>
                                </div>
                            @endif

                            @if ($expense->attachments->isNotEmpty())
                                <div class="attachment-links">
                                    @foreach ($expense->attachments as $attachment)
                                        @php($attachmentUrl = \Illuminate\Support\Facades\Storage::disk($attachment->disk)->url($attachment->path))
                                        <details class="attachment-preview">
                                            <summary class="attachment-link">
                                                <div class="attachment-link-copy">
                                                    <strong>{{ $attachment->original_name }}</strong>
                                                    <span>
                                                        Anexo do gasto
                                                        @if ($attachment->mime_type)
                                                            | {{ strtoupper($attachment->mime_type) }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <span>Preview</span>
                                            </summary>

                                            <div class="attachment-preview-panel">
                                                @if (str_starts_with((string) $attachment->mime_type, 'image/'))
                                                    <img
                                                        class="attachment-preview-image"
                                                        src="{{ $attachmentUrl }}"
                                                        alt="{{ $attachment->original_name }}"
                                                        loading="lazy"
                                                    >
                                                @elseif ($attachment->mime_type === 'application/pdf' || str_ends_with(strtolower($attachment->original_name), '.pdf'))
                                                    <iframe
                                                        class="attachment-preview-frame"
                                                        src="{{ $attachmentUrl }}"
                                                        title="{{ $attachment->original_name }}"
                                                    ></iframe>
                                                @else
                                                    <div class="empty-state">
                                                        Preview inline indisponível para este formato.
                                                    </div>
                                                @endif

                                                <a class="button secondary" href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer">
                                                    Abrir arquivo completo
                                                </a>
                                            </div>
                                        </details>
                                    @endforeach
                                </div>
                            @else
                                <div class="empty-state">Este gasto ainda não possui anexos.</div>
                            @endif
                        </div>
                    </details>
                @empty
                    <div class="empty-state">Nenhum gasto foi lançado para este caixa até o momento.</div>
                @endforelse
            </div>
        </article>

        <article class="section-card">
            <div class="section-header">
                <div>
                    <h3 class="section-title">Resumo financeiro</h3>
                    <p class="section-copy">Consolidado rápido para leitura administrativa e conferência de saldo.</p>
                </div>
            </div>

            <div class="stack">
                <div class="summary-tile">
                    <span class="label">Valor liberado</span>
                    <strong>R$ {{ number_format($cashRequest->released_amount, 2, ',', '.') }}</strong>
                    <span>Montante efetivamente depositado ou liberado ao solicitante.</span>
                </div>
                <div class="summary-tile">
                    <span class="label">Valor gasto</span>
                    <strong>R$ {{ number_format($cashRequest->spent_amount, 2, ',', '.') }}</strong>
                    <span>Somatório dos lançamentos submetidos no fluxo de prestação.</span>
                </div>
                <div class="summary-tile">
                    <span class="label">Saldo restante</span>
                    <strong>R$ {{ number_format($cashRequest->available_amount, 2, ',', '.') }}</strong>
                    <span>Diferença entre o valor liberado e os gastos já registrados.</span>
                </div>
            </div>
        </article>
    </section>

    <section class="table-card">
        <div class="table-header">
            <div>
                <h3 class="section-title">Extrato do caixa</h3>
                <p class="section-copy">Histórico financeiro da solicitação com saldo após cada movimentação.</p>
            </div>
        </div>

        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Saldo após evento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cashRequest->statements as $statement)
                        <tr>
                            <td>{{ $statement->occurred_at?->format('d/m/Y H:i') }}</td>
                            <td><span class="status-pill">{{ \App\Support\AdminLabel::statementEntryType($statement->entry_type) }}</span></td>
                            <td>{{ $statement->description }}</td>
                            <td>R$ {{ number_format($statement->amount, 2, ',', '.') }}</td>
                            <td>R$ {{ number_format($statement->balance_after, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">Sem movimentações registradas para compor o extrato deste caixa.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
