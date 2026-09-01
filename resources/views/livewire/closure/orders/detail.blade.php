<div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">Ordem {{ $order->ordem }}</h5>
                    <div class="text-muted small">Nota: {{ $order->Note->note ?? '—' }}</div>
                </div>
                <span class="badge {{ $isClosed ? 'bg-success' : ($situation === 'PASSIVO' ? 'bg-danger' : 'bg-warning text-dark') }} fs-6">
                    {{ $situation }}
                </span>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">statusSist (SAP)</div>
                    <div>{{ $status ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Competência original (meta)</div>
                    <div>{{ $target?->Cycle?->label ?? 'Ainda não entrou em meta' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Aging desde a entrada na meta</div>
                    <div>{{ $target?->frozen_at ? $target->frozen_at->diffInDays(now()) . ' dias' : '—' }}</div>
                </div>
            </div>

            @if ($target?->is_exception)
                <div class="alert alert-warning mt-3 mb-0 small">
                    <strong><i class="bi bi-exclamation-triangle-fill"></i> Caso atípico — entrada por exceção.</strong>
                    Esta Ordem entrou na competência {{ $target->Cycle?->label }} fora do fluxo automático,
                    por solicitação superior sob justificativa.
                    <div class="mt-1">
                        <strong>Justificativa:</strong> {{ $target->exception_reason }}<br>
                        <strong>Autorizado por:</strong> {{ $target->AuthorizedBy?->name ?? '—' }}
                        @if ($target->RequestedBy)
                            &middot; <strong>Solicitado por:</strong> {{ $target->RequestedBy->name }}
                        @endif
                        &middot; {{ $target->authorized_at?->format('d/m/Y H:i') }}
                    </div>
                </div>
            @endif

            <div class="alert alert-secondary mt-3 mb-0 small">
                Responsável operacional, localização e pendências ainda não estão disponíveis nesta fase do módulo
                (Fases 2, 3 e 4). Esta é a versão inicial (Fase 1) do Detalhe da Ordem.
            </div>
        </div>
    </div>
</div>
