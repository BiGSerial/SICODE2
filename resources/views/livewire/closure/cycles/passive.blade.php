<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Passivo</h2>
                <div class="dash-subtitle">Ordens de competências anteriores que ainda não encerraram no SAP.</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-4">
            <div class="metric-card h-100" style="--accent: var(--dash-red);">
                <div class="metric-label">Total em passivo</div>
                <div class="metric-value">{{ number_format($totalCount, 0, ',', '.') }}</div>
                <div class="metric-note">Ordens de competências já vencidas</div>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="metric-card h-100" style="--accent: var(--dash-teal);">
                <div class="metric-label">Competências em atraso</div>
                <div class="metric-value">{{ number_format($groups->count(), 0, ',', '.') }}</div>
                <div class="metric-note">Meses com Ordens ainda em aberto</div>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="metric-card h-100" style="--accent: var(--dash-amber);">
                <div class="metric-label">Ordem mais antiga</div>
                <div class="metric-value">{{ number_format($oldestDays, 0, ',', '.') }} dias</div>
                <div class="metric-note">Desde o início da competência original</div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Ordens em Passivo</h3>
                <div class="chart-subtitle">Agrupadas por competência de origem &middot; mais antigas primeiro</div>
            </div>
            <i class="bi bi-hourglass-split text-secondary fs-4"></i>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <colgroup>
                    <col style="width: 20%;">
                    <col style="width: 15%;">
                    <col style="width: 47%;">
                    <col style="width: 18%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-3">Ordem</th>
                        <th>Nota</th>
                        <th>statusSist</th>
                        <th class="pe-3 text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $row)
                        @php $groupDays = (int) $row['cycle']->startDate()->diffInDays(now()); @endphp
                        <tr class="group-row">
                            <td colspan="4" class="ps-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <strong class="chart-title">{{ $row['cycle']->label }}</strong>
                                    <span class="badge-stack {{ $groupDays > 180 ? 'badge-aging-high' : ($groupDays > 60 ? 'badge-aging-mid' : 'badge-aging-low') }}">
                                        {{ $groupDays }} dias em aberto
                                    </span>
                                    <span class="badge-stack badge-type">{{ $row['targets']->count() }} Ordens</span>
                                </div>
                            </td>
                        </tr>
                        @foreach ($row['targets'] as $target)
                            <tr>
                                <td class="ps-3">
                                    {{ $target->Order->ordem ?? '—' }}
                                    @if ($target->is_exception)
                                        <span class="badge-stack badge-exception ms-1" title="{{ $target->exception_reason }}">EXC</span>
                                    @endif
                                </td>
                                <td>{{ $target->Note->note ?? '—' }}</td>
                                <td class="text-muted small" title="{{ $target->Order->statusSist }}">{{ $target->Order->statusSist ?? '—' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('closure.order.detail', $target->order_id) }}" class="row-action">
                                        <i class="bi bi-eye"></i> Detalhe
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nenhuma Ordem em passivo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
