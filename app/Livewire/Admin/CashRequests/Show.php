<?php

namespace App\Livewire\Admin\CashRequests;

use App\Actions\CashRequest\DecideCashRequestAction;
use App\Actions\CashRequest\ReleaseCashRequestAction;
use App\Data\CashRequest\ApprovalDecisionData;
use App\Data\CashRequest\ReleaseCashRequestData;
use App\Enums\ApprovalDecision;
use App\Enums\CashApprovalStage;
use App\Enums\CashRequestStatus;
use App\Enums\PaymentMethod;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Models\CashRequestApproval;
use App\Models\CashRequestRejection;
use App\Models\RejectionReason;
use App\Support\AdminPanel;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class Show extends Component
{
    use WithFileUploads;

    public CashRequest $cashRequest;

    public string $managerComment = '';

    public string $financialComment = '';

    public string $releasePaymentMethod = 'pix';

    public float $releaseAmount = 0;

    public ?string $rejectionReasonPublicId = null;

    public $releaseReceipt = null;

    public ?string $feedbackMessage = null;

    public string $feedbackTone = 'success';

    public function mount(CashRequest $cashRequest): void
    {
        abort_unless(AdminPanel::canViewCashRequest(auth()->user(), $cashRequest), 403);

        $this->cashRequest = $cashRequest->load([
            'user',
            'department',
            'costCenter',
            'manager',
            'attachments',
            'approvals.actor',
            'rejections.reason',
            'rejections.rejectedBy',
            'deposits.attachments',
            'deposits.releasedBy',
            'expenses.attachments',
            'expenses.ocrRead',
            'expenses.fraudAlerts',
            'statements',
        ]);

        $this->releaseAmount = (float) ($this->cashRequest->approved_amount ?: $this->cashRequest->requested_amount);
    }

    public function approveManager(): void
    {
        $this->handleDecision(
            action: app(DecideCashRequestAction::class),
            stage: CashApprovalStage::MANAGER,
            decision: ApprovalDecision::APPROVED,
            comment: $this->managerComment,
            successMessage: 'Aprovação gerencial registrada.',
        );
    }

    public function rejectManager(): void
    {
        $this->handleDecision(
            action: app(DecideCashRequestAction::class),
            stage: CashApprovalStage::MANAGER,
            decision: ApprovalDecision::REJECTED,
            comment: $this->managerComment,
            successMessage: 'Reprovação gerencial registrada.',
        );
    }

    public function approveFinancial(): void
    {
        $this->handleDecision(
            action: app(DecideCashRequestAction::class),
            stage: CashApprovalStage::FINANCIAL,
            decision: ApprovalDecision::APPROVED,
            comment: $this->financialComment,
            successMessage: 'Aprovação financeira registrada.',
        );
    }

    public function rejectFinancial(): void
    {
        $this->handleDecision(
            action: app(DecideCashRequestAction::class),
            stage: CashApprovalStage::FINANCIAL,
            decision: ApprovalDecision::REJECTED,
            comment: $this->financialComment,
            successMessage: 'Reprovação financeira registrada.',
        );
    }

    public function release(): void
    {
        $actor = auth()->user();
        abort_unless($actor, 403);
        abort_unless(AdminPanel::canRegisterRelease($actor), 403);

        $this->resetFeedback();

        $validated = $this->validate($this->releaseRules(), [], [
            'releaseAmount' => 'valor da liberacao',
            'releasePaymentMethod' => 'meio de pagamento',
            'releaseReceipt' => 'comprovante de pagamento',
        ]);

        try {
            app(ReleaseCashRequestAction::class)->execute(
                actor: $actor,
                cashRequest: $this->cashRequest,
                data: new ReleaseCashRequestData(
                    amount: (float) $validated['releaseAmount'],
                    paymentMethod: PaymentMethod::from($validated['releasePaymentMethod']),
                    accountReference: null,
                    referenceNumber: null,
                    releasedAt: Carbon::now(),
                    receipt: $validated['releaseReceipt'],
                ),
            );

            $this->refreshRequest();
            $this->releaseReceipt = null;
            $this->feedbackTone = 'success';
            $this->feedbackMessage = 'Liberação registrada com sucesso.';
        } catch (BusinessRuleViolation $exception) {
            $this->feedbackTone = 'error';
            $this->feedbackMessage = $exception->getMessage();
        } catch (Throwable $exception) {
            report($exception);

            $this->feedbackTone = 'error';
            $this->feedbackMessage = 'Não foi possível registrar a liberação agora. Tente novamente.';
        }
    }

    public function render()
    {
        return view('livewire.admin.cash-requests.show', [
            'flowTimeline' => $this->buildFlowTimeline(),
            'requiresManagerApproval' => $this->requiresManagerApproval(),
            'hasManagerApproval' => $this->hasManagerApproval(),
            'canTakeManagerDecision' => $this->canTakeManagerDecision(),
            'canHandleFinancialStage' => $this->canHandleFinancialStage(),
            'canTakeFinancialDecision' => $this->canTakeFinancialDecision(),
            'canRegisterRelease' => $this->canRegisterRelease(),
            'rejectionReasons' => RejectionReason::query()->orderBy('name')->get(),
        ]);
    }

    private function refreshRequest(): void
    {
        $this->cashRequest = $this->cashRequest->fresh([
            'user',
            'department',
            'costCenter',
            'manager',
            'attachments',
            'approvals.actor',
            'rejections.reason',
            'rejections.rejectedBy',
            'deposits.attachments',
            'deposits.releasedBy',
            'expenses.attachments',
            'expenses.ocrRead',
            'expenses.fraudAlerts',
            'statements',
        ]);
    }

    private function handleDecision(
        DecideCashRequestAction $action,
        CashApprovalStage $stage,
        ApprovalDecision $decision,
        string $comment,
        string $successMessage,
    ): void {
        $actor = auth()->user();
        abort_unless($actor, 403);
        abort_unless(
            $stage === CashApprovalStage::MANAGER
                ? AdminPanel::canManageManagerDecision($actor, $this->cashRequest)
                : AdminPanel::canManageFinancialDecision($actor),
            403
        );

        $this->resetFeedback();

        try {
            $reasonId = RejectionReason::query()
                ->where('public_id', $this->rejectionReasonPublicId)
                ->value('id');

            $action->execute(
                actor: $actor,
                cashRequest: $this->cashRequest,
                data: new ApprovalDecisionData(
                    stage: $stage,
                    decision: $decision,
                    comment: $comment,
                    rejectionReasonId: $reasonId,
                ),
            );

            $this->refreshRequest();
            $this->resetDecisionInputs();
            $this->feedbackTone = 'success';
            $this->feedbackMessage = $successMessage;
        } catch (BusinessRuleViolation $exception) {
            $this->feedbackTone = 'error';
            $this->feedbackMessage = $exception->getMessage();
        } catch (Throwable $exception) {
            report($exception);

            $this->feedbackTone = 'error';
            $this->feedbackMessage = 'Não foi possível concluir a decisão agora. Atualize a página e tente novamente.';
        }
    }

    private function resetDecisionInputs(): void
    {
        $this->managerComment = '';
        $this->financialComment = '';
        $this->rejectionReasonPublicId = null;
    }

    private function resetFeedback(): void
    {
        $this->feedbackMessage = null;
        $this->feedbackTone = 'success';
    }

    /**
     * @return array<string, mixed>
     */
    private function releaseRules(): array
    {
        return [
            'releaseAmount' => ['required', 'numeric', 'min:0.01'],
            'releasePaymentMethod' => ['required', new Enum(PaymentMethod::class)],
            'releaseReceipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function buildFlowTimeline(): array
    {
        $submittedAt = $this->cashRequest->submitted_at ?? $this->cashRequest->created_at;
        $managerApproval = $this->latestApprovalForStage(CashApprovalStage::MANAGER);
        $managerRejection = $this->latestRejectionForStage(CashApprovalStage::MANAGER);
        $financialApproval = $this->latestApprovalForStage(CashApprovalStage::FINANCIAL);
        $financialRejection = $this->latestRejectionForStage(CashApprovalStage::FINANCIAL);
        $latestDeposit = $this->cashRequest->deposits->sortByDesc('released_at')->first();

        return [
            [
                'title' => 'Solicitação enviada',
                'description' => 'Abertura do caixa pelo colaborador com valor, justificativa e centro de custo.',
                'badge' => 'Registrada',
                'state' => 'completed',
                'date_label' => 'Data da solicitação',
                'date' => $this->formatDateTime($submittedAt),
                'actor' => $this->cashRequest->user?->name,
            ],
            $this->managerTimelineStep($managerApproval, $managerRejection),
            $this->financialTimelineStep($financialApproval, $financialRejection),
            $this->releaseTimelineStep($financialApproval, $financialRejection, $latestDeposit?->releasedBy?->name),
            $this->closingTimelineStep(),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function managerTimelineStep(?CashRequestApproval $approval, ?CashRequestRejection $rejection): array
    {
        if (! $this->requiresManagerApproval()) {
            return [
                'title' => 'Validação do gestor',
                'description' => 'Este fluxo seguiu direto para o financeiro porque o colaborador não possui gestor vinculado.',
                'badge' => 'Dispensada',
                'state' => 'skipped',
                'date_label' => 'Sem etapa',
                'date' => null,
                'actor' => null,
            ];
        }

        if ($approval) {
            return [
                'title' => 'Aprovação do gestor',
                'description' => $approval->comment ?: 'Gestor aprovou a solicitação para seguir ao financeiro.',
                'badge' => 'Aprovada',
                'state' => 'completed',
                'date_label' => 'Data da aprovação',
                'date' => $this->formatDateTime($approval->acted_at),
                'actor' => $approval->actor?->name,
            ];
        }

        if ($rejection) {
            return [
                'title' => 'Decisão do gestor',
                'description' => $rejection->comment ?: 'Solicitação reprovada pelo gestor responsável.',
                'badge' => 'Reprovada',
                'state' => 'rejected',
                'date_label' => 'Data da reprovação',
                'date' => $this->formatDateTime($rejection->created_at),
                'actor' => $rejection->rejectedBy?->name,
            ];
        }

        return [
            'title' => 'Aprovação do gestor',
            'description' => 'Aguardando decisão do gestor responsável pelo colaborador.',
            'badge' => 'Pendente',
            'state' => 'pending',
            'date_label' => 'Gestor responsável',
            'date' => null,
            'actor' => $this->cashRequest->manager?->name,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function financialTimelineStep(?CashRequestApproval $approval, ?CashRequestRejection $rejection): array
    {
        if (! $this->canHandleFinancialStage()) {
            return [
                'title' => 'Aprovação do financeiro',
                'description' => 'A etapa financeira será liberada somente após a aprovação do gestor.',
                'badge' => 'Bloqueada',
                'state' => 'locked',
                'date_label' => 'Dependência',
                'date' => null,
                'actor' => 'Aguardando gestor',
            ];
        }

        if ($approval) {
            return [
                'title' => 'Aprovação do financeiro',
                'description' => $approval->comment ?: 'Análise financeira concluída e apta para liberação.',
                'badge' => 'Aprovada',
                'state' => 'completed',
                'date_label' => 'Data da aprovação',
                'date' => $this->formatDateTime($approval->acted_at),
                'actor' => $approval->actor?->name,
            ];
        }

        if ($rejection) {
            return [
                'title' => 'Decisão do financeiro',
                'description' => $rejection->comment ?: 'Solicitação reprovada na análise financeira.',
                'badge' => 'Reprovada',
                'state' => 'rejected',
                'date_label' => 'Data da reprovação',
                'date' => $this->formatDateTime($rejection->created_at),
                'actor' => $rejection->rejectedBy?->name,
            ];
        }

        return [
            'title' => 'Aprovação do financeiro',
            'description' => 'Aguardando análise do financeiro para seguir para pagamento.',
            'badge' => 'Pendente',
            'state' => 'pending',
            'date_label' => 'Situação atual',
            'date' => null,
            'actor' => 'Fila financeira',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function releaseTimelineStep(
        ?CashRequestApproval $financialApproval,
        ?CashRequestRejection $financialRejection,
        ?string $releasedByName,
    ): array {
        if ($this->cashRequest->released_at) {
            return [
                'title' => 'Liberação e pagamento',
                'description' => 'Valor efetivamente liberado ao colaborador pelo financeiro.',
                'badge' => 'Pago',
                'state' => 'completed',
                'date_label' => 'Data do pagamento',
                'date' => $this->formatDateTime($this->cashRequest->released_at),
                'actor' => $releasedByName,
            ];
        }

        if ($financialRejection) {
            return [
                'title' => 'Liberação e pagamento',
                'description' => 'Não houve liberação porque a solicitação foi reprovada pelo financeiro.',
                'badge' => 'Bloqueada',
                'state' => 'rejected',
                'date_label' => 'Situação',
                'date' => null,
                'actor' => 'Sem pagamento',
            ];
        }

        if ($financialApproval) {
            return [
                'title' => 'Liberação e pagamento',
                'description' => 'O financeiro já aprovou. Falta apenas registrar a liberação do valor.',
                'badge' => 'Pronta',
                'state' => 'pending',
                'date_label' => 'Aguardando',
                'date' => null,
                'actor' => 'Registro de pagamento pendente',
            ];
        }

        return [
            'title' => 'Liberação e pagamento',
            'description' => 'O pagamento será habilitado somente após a aprovação financeira.',
            'badge' => 'Aguardando',
            'state' => 'locked',
            'date_label' => 'Dependência',
            'date' => null,
            'actor' => 'Análise financeira',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private function closingTimelineStep(): array
    {
        if ($this->cashRequest->closed_at) {
            return [
                'title' => 'Fechamento do caixa',
                'description' => 'Prestação concluída e caixa encerrado no fluxo administrativo.',
                'badge' => 'Fechado',
                'state' => 'completed',
                'date_label' => 'Data do fechamento',
                'date' => $this->formatDateTime($this->cashRequest->closed_at),
                'actor' => 'Encerramento registrado',
            ];
        }

        if ($this->cashRequest->released_at) {
            return [
                'title' => 'Fechamento do caixa',
                'description' => 'Caixa ainda em aberto. O fechamento acontecerá após a prestação de contas.',
                'badge' => 'Em aberto',
                'state' => 'pending',
                'date_label' => 'Prazo da prestação',
                'date' => $this->formatDateTime($this->cashRequest->due_accountability_at),
                'actor' => 'Acompanhando prestação',
            ];
        }

        return [
            'title' => 'Fechamento do caixa',
            'description' => 'O caixa ainda não foi liberado. O fechamento será exibido após a prestação.',
            'badge' => 'Sem liberação',
            'state' => 'locked',
            'date_label' => 'Situação',
            'date' => null,
            'actor' => 'Fluxo inicial',
        ];
    }

    private function requiresManagerApproval(): bool
    {
        return (bool) $this->cashRequest->manager_id;
    }

    private function hasManagerApproval(): bool
    {
        return $this->latestApprovalForStage(CashApprovalStage::MANAGER) !== null;
    }

    private function canTakeManagerDecision(): bool
    {
        return $this->cashRequest->status === CashRequestStatus::AWAITING_MANAGER_APPROVAL
            && AdminPanel::canManageManagerDecision(auth()->user(), $this->cashRequest);
    }

    private function canHandleFinancialStage(): bool
    {
        return ! $this->requiresManagerApproval() || $this->hasManagerApproval();
    }

    private function canTakeFinancialDecision(): bool
    {
        return $this->canHandleFinancialStage()
            && AdminPanel::canManageFinancialDecision(auth()->user())
            && $this->cashRequest->status === CashRequestStatus::AWAITING_FINANCIAL_APPROVAL;
    }

    private function canRegisterRelease(): bool
    {
        return $this->canHandleFinancialStage()
            && AdminPanel::canRegisterRelease(auth()->user())
            && $this->cashRequest->status === CashRequestStatus::FINANCIAL_APPROVED;
    }

    private function latestApprovalForStage(CashApprovalStage $stage): ?CashRequestApproval
    {
        /** @var CashRequestApproval|null $approval */
        $approval = $this->cashRequest->approvals
            ->filter(fn (CashRequestApproval $approval): bool => $approval->stage === $stage && $approval->decision === ApprovalDecision::APPROVED)
            ->sortByDesc('acted_at')
            ->first();

        return $approval;
    }

    private function latestRejectionForStage(CashApprovalStage $stage): ?CashRequestRejection
    {
        /** @var CashRequestRejection|null $rejection */
        $rejection = $this->cashRequest->rejections
            ->filter(fn (CashRequestRejection $rejection): bool => $rejection->stage === $stage)
            ->sortByDesc('created_at')
            ->first();

        return $rejection;
    }

    private function formatDateTime($date): ?string
    {
        return $date?->format('d/m/Y H:i');
    }
}
