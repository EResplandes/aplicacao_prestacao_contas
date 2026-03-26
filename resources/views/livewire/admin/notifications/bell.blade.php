<div class="contents" wire:poll.5s.visible.keep-alive>
    <button
        class="icon-button {{ $open ? 'is-highlight' : '' }}"
        type="button"
        wire:click="toggle"
        aria-label="Notificações"
        aria-expanded="{{ $open ? 'true' : 'false' }}"
    >
        @if ($unreadCount > 0)
            <span class="icon-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
            <path d="M7.5 9.5a4.5 4.5 0 1 1 9 0v3.21c0 .52.2 1.02.56 1.39l.89.9H6.05l.89-.9c.36-.37.56-.87.56-1.39V9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
            <path d="M10 18a2 2 0 0 0 4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
    </button>

    @if ($open)
        <div class="fixed inset-0 z-[70]">
            <button
                class="absolute inset-0 bg-slate-950/35 backdrop-blur-[2px]"
                type="button"
                wire:click="close"
                aria-label="Fechar notificações"
            ></button>

            <aside class="absolute right-0 top-0 flex h-full w-full max-w-[440px] flex-col border-l border-slate-200 bg-white shadow-[0_20px_70px_rgba(15,23,42,0.18)]">
                <div class="border-b border-slate-200 px-5 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="hero-label">Notificações</span>
                            <h3 class="section-title mt-3.5">Atualizações operacionais</h3>
                            <p class="section-copy">Aprovações, liberações, gastos lançados e alertas recentes.</p>
                        </div>

                        <button
                            class="icon-button h-10 w-10 rounded-2xl"
                            type="button"
                            wire:click="close"
                            aria-label="Fechar drawer"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M6 6 18 18M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <span class="status-pill {{ $unreadCount > 0 ? 'is-warning' : 'is-neutral' }}">
                            {{ $unreadCount > 0 ? $unreadCount.' não lidas' : 'Tudo em dia' }}
                        </span>
                        <button class="button ghost !px-4 !py-2.5" type="button" wire:click="markAllAsRead">
                            Marcar tudo como lido
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-5">
                    @if ($latestNotifications->isEmpty())
                        <div class="empty-state">
                            Nenhuma notificação operacional registrada até o momento.
                        </div>
                    @else
                        <div class="flex flex-col gap-3">
                            @foreach ($latestNotifications as $notification)
                                @php
                                    $cashRequestPublicId = data_get($notification->data, 'context.cash_request_public_id');
                                    $isUnread = $notification->read_at === null;
                                @endphp

                                <article class="rounded-[24px] border {{ $isUnread ? 'border-brand-200 bg-brand-50/70' : 'border-slate-200 bg-slate-50' }} p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <strong class="text-sm font-extrabold text-slate-950">
                                            {{ data_get($notification->data, 'title', 'Notificação') }}
                                        </strong>
                                        <span class="status-pill {{ $isUnread ? 'is-warning' : 'is-neutral' }}">
                                            {{ $isUnread ? 'Nova' : 'Lida' }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        {{ data_get($notification->data, 'message') }}
                                    </p>

                                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-400">
                                            {{ $notification->created_at?->format('d/m/Y H:i') }}
                                        </span>

                                        @if ($cashRequestPublicId)
                                            <a
                                                class="button ghost !px-4 !py-2.5"
                                                href="{{ route('admin.cash-requests.show', $cashRequestPublicId) }}"
                                                wire:click="close"
                                            >
                                                Abrir caixa
                                            </a>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</div>
