<section class="section-card stack" wire:poll.6s.visible="refreshMessages">
    <div class="section-header">
        <div>
            <h3 class="section-title">Conversa com o colaborador</h3>
            <p class="section-copy">
                Canal rápido para tirar dúvidas sobre o caixa sem sair do detalhe da solicitação.
            </p>
        </div>
    </div>

    @if ($feedbackMessage)
        <div class="{{ $feedbackTone === 'error' ? 'rounded-[20px] border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700' : 'flash' }}">
            {{ $feedbackMessage }}
        </div>
    @endif

    <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-4">
        @forelse ($cashRequest->messages as $chatMessage)
            @php($fromFinance = $chatMessage->sender_role?->value === 'finance')
            <div class="mb-3 flex {{ $fromFinance ? 'justify-start' : 'justify-end' }}">
                <div class="{{ $fromFinance ? 'bg-slate-900 text-white' : 'bg-blue-600 text-white' }} max-w-[92%] rounded-[22px] px-4 py-3 shadow-sm lg:max-w-[72%]">
                    <div class="flex items-center justify-between gap-3 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $fromFinance ? 'text-white/70' : 'text-blue-100' }}">
                        <span>{{ $chatMessage->sender?->name ?? ($fromFinance ? 'Financeiro' : 'Colaborador') }}</span>
                        <span>{{ $chatMessage->created_at?->format('d/m H:i') }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6">
                        {{ $chatMessage->message }}
                    </p>
                </div>
            </div>
        @empty
            <div class="empty-state">
                Ainda não há mensagens para este caixa. Use o campo abaixo para orientar o colaborador ou responder uma dúvida.
            </div>
        @endforelse
    </div>

    @if ($canSend)
        <div class="field">
            <label for="cash-request-chat-message-{{ $cashRequest->public_id }}">Nova mensagem</label>
            <textarea
                id="cash-request-chat-message-{{ $cashRequest->public_id }}"
                rows="4"
                wire:model.defer="message"
                placeholder="Escreva uma orientação ou responda a dúvida do colaborador..."
            ></textarea>
            <span class="secondary-text">
                O histórico da conversa fica registrado junto ao caixa para auditoria.
            </span>
            @error('message')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="row">
            <button class="button" type="button" wire:click.prevent="sendMessage" wire:loading.attr="disabled" wire:target="sendMessage">
                <span wire:loading.remove wire:target="sendMessage">Enviar mensagem</span>
                <span wire:loading wire:target="sendMessage">Enviando...</span>
            </button>
        </div>
    @else
        <div class="empty-state">
            A conversa fica habilitada somente enquanto o caixa estiver em andamento.
        </div>
    @endif
</section>
