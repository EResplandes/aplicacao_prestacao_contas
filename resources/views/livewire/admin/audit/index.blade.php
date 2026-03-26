<div class="stack-lg">
    <section class="section-card stack">
        <div class="section-header">
            <div>
                <h3 class="section-title">Trilha de auditoria</h3>
                <p class="section-copy">Registro de quem criou, alterou, aprovou, reprovou e parametrizou o sistema.</p>
            </div>
        </div>

        <div class="field">
            <label>Busca por evento ou ação</label>
            <input type="text" wire:model.live="search" placeholder="Ex.: user, approval_rule, updated">
        </div>

        <div class="table-shell">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Usuário</th>
                        <th>Evento</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->performed_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'Sistema' }}</td>
                            <td>{{ \App\Support\AdminLabel::auditEvent($log->event) }}</td>
                            <td>{{ \App\Support\AdminLabel::auditAction($log->action) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"><div class="empty-state">Nenhum log encontrado para o filtro informado.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $logs->links() }}
        </div>
    </section>
</div>
