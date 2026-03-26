<?php

namespace App\Services;

use App\Enums\ApprovalDecision;
use App\Enums\CashApprovalStage;
use App\Models\CashExpense;
use App\Models\CashRequest;
use App\Models\User;
use App\Notifications\OperationalAlertNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Notification;

class OperationalNotificationService
{
    public function notifyCashRequestDecision(
        CashRequest $cashRequest,
        CashApprovalStage $stage,
        ApprovalDecision $decision,
    ): void {
        $cashRequest->loadMissing(['user', 'manager']);

        if ($stage === CashApprovalStage::MANAGER && $decision === ApprovalDecision::APPROVED) {
            $this->notifyRequester(
                $cashRequest,
                'Solicitação aprovada pelo gestor',
                "O caixa {$cashRequest->request_number} foi aprovado pelo gestor e seguiu para análise do financeiro.",
                'cash_request.manager_approved',
            );

            $this->notifyOperations(
                $this->financeAndAdminUsers(),
                'Caixa pronto para análise financeira',
                "{$cashRequest->request_number} do colaborador {$cashRequest->user?->name} está pronto para decisão do financeiro.",
                'cash_request.awaiting_financial_approval',
                $cashRequest,
            );

            return;
        }

        if ($stage === CashApprovalStage::MANAGER && $decision === ApprovalDecision::REJECTED) {
            $this->notifyRequester(
                $cashRequest,
                'Solicitação reprovada pelo gestor',
                "O caixa {$cashRequest->request_number} foi reprovado pelo gestor. Verifique a justificativa para corrigir ou responder.",
                'cash_request.manager_rejected',
            );

            $this->notifyOperations(
                $this->adminUsers(),
                'Solicitação reprovada pelo gestor',
                "{$cashRequest->request_number} do colaborador {$cashRequest->user?->name} foi reprovado na etapa gerencial.",
                'cash_request.manager_rejected',
                $cashRequest,
            );

            return;
        }

        if ($stage === CashApprovalStage::FINANCIAL && $decision === ApprovalDecision::APPROVED) {
            $this->notifyRequester(
                $cashRequest,
                'Caixa aprovado pelo financeiro',
                "O caixa {$cashRequest->request_number} foi aprovado pelo financeiro e aguarda a liberação do valor.",
                'cash_request.financial_approved',
            );

            $this->notifyOperations(
                $this->financeAndAdminUsers(),
                'Caixa aprovado para liberação',
                "{$cashRequest->request_number} do colaborador {$cashRequest->user?->name} está aprovado e pronto para pagamento.",
                'cash_request.financial_approved',
                $cashRequest,
            );

            return;
        }

        if ($stage === CashApprovalStage::FINANCIAL && $decision === ApprovalDecision::REJECTED) {
            $this->notifyRequester(
                $cashRequest,
                'Caixa reprovado pelo financeiro',
                "O caixa {$cashRequest->request_number} foi reprovado pelo financeiro. Consulte o motivo e responda a pendência.",
                'cash_request.financial_rejected',
            );

            $this->notifyOperations(
                $this->financeAndAdminUsers(),
                'Solicitação reprovada pelo financeiro',
                "{$cashRequest->request_number} do colaborador {$cashRequest->user?->name} foi reprovado na análise financeira.",
                'cash_request.financial_rejected',
                $cashRequest,
            );
        }
    }

    public function notifyCashReleased(CashRequest $cashRequest): void
    {
        $cashRequest->loadMissing(['user']);

        $formattedAmount = 'R$ '.number_format((float) $cashRequest->released_amount, 2, ',', '.');

        $this->notifyRequester(
            $cashRequest,
            'Caixa liberado para uso',
            "O caixa {$cashRequest->request_number} foi liberado. O valor de {$formattedAmount} já está disponível para utilização.",
            'cash_request.released',
        );

        $this->notifyOperations(
            $this->financeAndAdminUsers(),
            'Liberação registrada',
            "{$cashRequest->request_number} foi pago para {$cashRequest->user?->name}. Valor liberado: {$formattedAmount}.",
            'cash_request.released',
            $cashRequest,
        );
    }

    public function notifyExpenseSubmitted(CashExpense $expense): void
    {
        $expense->loadMissing(['cashRequest.user', 'cashRequest.manager', 'category']);

        $cashRequest = $expense->cashRequest;
        if (! $cashRequest) {
            return;
        }

        $baseRecipients = $this->financeAndAdminUsers();
        if ($cashRequest->manager) {
            $baseRecipients->push($cashRequest->manager);
        }

        $formattedAmount = 'R$ '.number_format((float) $expense->amount, 2, ',', '.');

        $this->notifyOperations(
            $baseRecipients,
            'Novo gasto lançado',
            "{$cashRequest->user?->name} lançou {$formattedAmount} no caixa {$cashRequest->request_number} com a despesa {$expense->description}.",
            'cash_expense.submitted',
            $cashRequest,
            [
                'expense_public_id' => $expense->public_id,
                'expense_status' => $expense->status?->value,
                'expense_category_name' => $expense->category?->name,
            ],
        );

        if ($expense->status?->value === 'flagged') {
            $this->notifyOperations(
                $this->financeAndAdminUsers(),
                'Gasto sinalizado para revisão',
                "{$cashRequest->request_number} recebeu um gasto sinalizado de {$formattedAmount}. Verifique os alertas de conformidade.",
                'cash_expense.flagged',
                $cashRequest,
                [
                    'expense_public_id' => $expense->public_id,
                    'expense_status' => $expense->status?->value,
                    'expense_category_name' => $expense->category?->name,
                ],
            );
        }
    }

    private function notifyRequester(
        CashRequest $cashRequest,
        string $title,
        string $message,
        string $type,
    ): void {
        if (! $cashRequest->user) {
            return;
        }

        $cashRequest->user->notify(new OperationalAlertNotification(
            title: $title,
            message: $message,
            type: $type,
            context: [
                'cash_request_public_id' => $cashRequest->public_id,
                'cash_request_number' => $cashRequest->request_number,
            ],
            occurredAt: now(),
        ));
    }

    private function notifyOperations(
        EloquentCollection $users,
        string $title,
        string $message,
        string $type,
        CashRequest $cashRequest,
        array $extraContext = [],
    ): void {
        $recipients = $users
            ->filter(fn (?User $user): bool => $user instanceof User && $user->is_active)
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new OperationalAlertNotification(
            title: $title,
            message: $message,
            type: $type,
            context: array_merge([
                'cash_request_public_id' => $cashRequest->public_id,
                'cash_request_number' => $cashRequest->request_number,
                'requester_name' => $cashRequest->user?->name,
            ], $extraContext),
            occurredAt: now(),
        ));
    }

    private function adminUsers(): EloquentCollection
    {
        return User::query()
            ->role('admin')
            ->where('is_active', true)
            ->get();
    }

    private function financeAndAdminUsers(): EloquentCollection
    {
        return User::query()
            ->role(['admin', 'finance'])
            ->where('is_active', true)
            ->get();
    }
}
