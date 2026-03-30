<?php

namespace App\Livewire\Admin\CashRequests;

use App\Actions\CashRequest\SendCashRequestMessageAction;
use App\Enums\CashRequestStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Models\CashRequest;
use App\Support\AdminPanel;
use Livewire\Component;
use Throwable;

class ChatPanel extends Component
{
    public CashRequest $cashRequest;

    public string $message = '';

    public ?string $feedbackMessage = null;

    public string $feedbackTone = 'success';

    public function mount(CashRequest $cashRequest): void
    {
        abort_unless(AdminPanel::canViewFinanceChat(auth()->user(), $cashRequest), 403);

        $this->cashRequest = $cashRequest->load([
            'user',
            'messages.sender',
        ]);
    }

    public function refreshMessages(): void
    {
        $this->cashRequest = $this->cashRequest->fresh([
            'user',
            'messages.sender',
        ]);
    }

    public function sendMessage(): void
    {
        $actor = auth()->user();
        abort_unless($actor, 403);
        abort_unless(AdminPanel::canSendFinanceChatMessage($actor, $this->cashRequest), 403);

        $this->validate([
            'message' => ['required', 'string', 'max:2000'],
        ], [], [
            'message' => 'mensagem',
        ]);

        try {
            app(SendCashRequestMessageAction::class)->execute(
                actor: $actor,
                cashRequest: $this->cashRequest,
                message: $this->message,
            );

            $this->message = '';
            $this->feedbackTone = 'success';
            $this->feedbackMessage = 'Mensagem enviada para o colaborador.';
            $this->refreshMessages();
        } catch (BusinessRuleViolation $exception) {
            $this->feedbackTone = 'error';
            $this->feedbackMessage = $exception->getMessage();
        } catch (Throwable $exception) {
            report($exception);

            $this->feedbackTone = 'error';
            $this->feedbackMessage = 'Nao foi possivel enviar a mensagem agora. Tente novamente.';
        }
    }

    public function render()
    {
        return view('livewire.admin.cash-requests.chat-panel', [
            'canSend' => $this->canSend(),
        ]);
    }

    private function canSend(): bool
    {
        return AdminPanel::canSendFinanceChatMessage(auth()->user(), $this->cashRequest)
            && ! in_array($this->cashRequest->status, [CashRequestStatus::CLOSED, CashRequestStatus::CANCELLED], true);
    }
}
