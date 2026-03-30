<?php

namespace App\Actions\CashRequest;

use App\Enums\CashRequestMessageSenderRole;
use App\Enums\CashRequestStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Models\CashRequestMessage;
use App\Models\User;
use App\Services\OperationalNotificationService;
use App\Support\AdminPanel;

class SendCashRequestMessageAction
{
    public function __construct(
        private readonly OperationalNotificationService $notificationService,
    ) {}

    public function execute(User $actor, CashRequest $cashRequest, string $message): CashRequestMessage
    {
        $content = trim($message);

        if ($content === '') {
            throw new BusinessRuleViolation('Informe a mensagem antes de enviar.');
        }

        if (in_array($cashRequest->status, [CashRequestStatus::CLOSED, CashRequestStatus::CANCELLED], true)) {
            throw new BusinessRuleViolation('Este caixa ja foi encerrado e nao aceita novas mensagens.');
        }

        $senderRole = $this->resolveSenderRole($actor, $cashRequest);

        $chatMessage = $cashRequest->messages()->create([
            'sender_id' => $actor->id,
            'sender_role' => $senderRole,
            'message' => $content,
        ]);

        $chatMessage->load('sender');

        $this->notificationService->notifyCashRequestChatMessage($cashRequest, $chatMessage);

        return $chatMessage;
    }

    private function resolveSenderRole(User $actor, CashRequest $cashRequest): CashRequestMessageSenderRole
    {
        if ((int) $cashRequest->user_id === (int) $actor->id) {
            return CashRequestMessageSenderRole::REQUESTER;
        }

        if (AdminPanel::canManageFinancialDecision($actor)) {
            return CashRequestMessageSenderRole::FINANCE;
        }

        throw new BusinessRuleViolation('Este perfil nao pode participar da conversa financeira deste caixa.');
    }
}
