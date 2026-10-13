<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Ordem {{ $order->ordem }}</h2>
                <div class="dash-subtitle">Nota/OV {{ $order->Note?->note ?? '—' }} · consulta operacional</div>
            </div>
            <span class="badge {{ $isClosed ? 'bg-success' : ($situation === 'PASSIVO' ? 'bg-danger' : 'bg-warning text-dark') }} align-self-start px-3 py-2">{{ $situation }}</span>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-4">
            <div class="metric-card h-100" style="--accent: {{ $isClosed ? 'var(--dash-green)' : 'var(--dash-amber)' }};">
                <div class="metric-label">SAP</div>
                <div class="metric-value fs-4">{{ $isClosed ? 'Encerrada' : 'Em aberto' }}</div>
                <div class="metric-note">{{ $status ?: 'Sem informação de status' }}</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="metric-card h-100" style="--accent: var(--dash-teal);">
                <div class="metric-label">Tratativa</div>
                <div class="metric-value fs-4">{{ $state && $canViewOperational ? str_replace('_', ' ', $state->operational_status) : '—' }}</div>
                <div class="metric-note">Status operacional separado do SAP e somente para consulta.</div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="metric-card h-100" style="--accent: var(--dash-blue);">
                <div class="metric-label">Meta original</div>
                <div class="metric-value fs-4">{{ $target?->Cycle?->label ?? '—' }}</div>
                <div class="metric-note">{{ $target?->Cycle ? $target->Cycle->startDate()->diffInDays(now()) . ' dias desde a entrada' : 'Ainda não entrou em meta' }}</div>
            </div>
        </div>
    </div>

    @if ($state && $canViewOperational)
        <div class="table-card p-3 mb-3">
            <h3 class="chart-title mb-3">Posse atual</h3>
            <div class="row g-3">
                <div class="col-12 col-md-4"><div class="text-muted small">Detentor</div><strong>{{ $state->CurrentHolderUser?->name ?? $state->CurrentHolderCompany?->name ?? 'Não informado' }}</strong></div>
                <div class="col-12 col-md-4"><div class="text-muted small">Desde quando</div><strong>{{ $state->entered_current_status_at?->format('d/m/Y H:i') ?? 'Não informado' }}</strong></div>
                <div class="col-12 col-md-4"><div class="text-muted small">Tempo em posse</div><strong>{{ $state->entered_current_status_at?->diffForHumans(null, true) ?? 'Não informado' }}</strong></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="table-card h-100">
                    <div class="chart-head"><div><h3 class="chart-title">Linha do tempo</h3><div class="chart-subtitle">Eventos registrados para esta Ordem.</div></div></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th class="ps-3">Quando</th><th>Evento</th><th class="pe-3">Responsável</th></tr></thead>
                            <tbody>
                                @forelse ($state->Events as $event)
                                    <tr><td class="ps-3 small">{{ $event->created_at?->format('d/m/Y H:i') }}</td><td>{{ str_replace('_', ' ', $event->type) }}</td><td class="pe-3">{{ $event->Actor?->name ?? 'Sistema' }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Ainda não há eventos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-6">
                <div class="table-card h-100">
                    <div class="chart-head"><div><h3 class="chart-title">Períodos de posse</h3><div class="chart-subtitle">Onde a Ordem ficou e por quanto tempo.</div></div></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th class="ps-3">Posse</th><th>Início</th><th class="pe-3">Fim</th></tr></thead>
                            <tbody>
                                @forelse ($state->Periods as $period)
                                    <tr>
                                        <td class="ps-3">{{ $period->HolderUser?->name ?? $period->HolderCompany?->name ?? 'Não informado' }}</td>
                                        <td class="small">{{ $period->started_at?->format('d/m/Y H:i') }}</td>
                                        <td class="pe-3 small">{{ $period->ended_at?->format('d/m/Y H:i') ?? 'Em curso' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Ainda não há períodos registrados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($state)
        <div class="alert alert-secondary">A situação operacional desta Ordem está disponível apenas para o detentor atual ou para a empresa responsável.</div>
    @else
        <div class="alert alert-secondary">Esta Ordem ainda não possui uma tratativa operacional iniciada.</div>
    @endif

    @if ($target?->is_exception)
        <div class="alert alert-warning mt-3 mb-0 small">
            <strong>Caso atípico — entrada por exceção.</strong> Justificativa: {{ $target->exception_reason }}.
        </div>
    @endif
</div>
