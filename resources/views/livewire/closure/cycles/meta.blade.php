<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Meta da Competência</h2>
                <div class="dash-subtitle">
                    Ordens elegíveis, agrupadas por Nota/OV, com o status de encerramento no SAP.
                    @if ($selectedCycle)
                        &middot; Competência aberta há {{ $cycleAgeDays }} dias
                    @endif
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="text-white small mb-0">Competência</label>
                <select wire:model="cycleId" class="form-select form-select-sm w-auto">
                    @forelse ($cycles as $cycle)
                        <option value="{{ $cycle->id }}">{{ $cycle->label }}</option>
                    @empty
                        <option value="">Nenhuma competência com Ordens em aberto</option>
                    @endforelse
                </select>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-teal);">
                <div class="metric-label">Total na meta</div>
                <div class="metric-value">{{ number_format($summaryTotal, 0, ',', '.') }}</div>
                <div class="metric-note">Ordens elegíveis nesta competência</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-green);">
                <div class="metric-label">Encerradas</div>
                <div class="metric-value">{{ number_format($summaryClosed, 0, ',', '.') }}</div>
                <div class="metric-note">{{ $summaryPercent }}% concluído</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-amber);">
                <div class="metric-label">Em aberto</div>
                <div class="metric-value">{{ number_format($summaryTotal - $summaryClosed, 0, ',', '.') }}</div>
                <div class="metric-note">Ainda não encerradas no SAP</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-blue);">
                <div class="metric-label">Notas / OVs</div>
                <div class="metric-value">{{ number_format($targets->count(), 0, ',', '.') }}</div>
                <div class="metric-note">Grupos nesta competência</div>
            </div>
        </div>
    </div>

    <div class="progress mb-3">
        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $summaryPercent }}%;"
            aria-valuenow="{{ $summaryPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
    </div>

    <div class="table-card">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Ordens da Meta</h3>
                <div class="chart-subtitle">Agrupadas por Nota/OV &middot; ordenadas pela competência selecionada</div>
            </div>
            <i class="bi bi-list-check text-secondary fs-4"></i>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <colgroup>
                    <col style="width: 22%;">
                    <col style="width: 16%;">
                    <col style="width: 44%;">
                    <col style="width: 18%;">
                </colgroup>
                <thead>
                    <tr>
                        <th class="ps-3">Ordem</th>
                        <th>Status</th>
                        <th>statusSist</th>
                        <th class="pe-3 text-end">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($targets as $noteLabel => $noteTargets)
                        @php
                            $closedCount = $noteTargets->filter(fn ($t) => $t->Order && (str_starts_with((string) $t->Order->statusSist, 'ENTE') || str_starts_with((string) $t->Order->statusSist, 'ENCE')))->count();
                            $notePercent = $noteTargets->count() > 0 ? round(($closedCount / $noteTargets->count()) * 100, 1) : 0;
                            $noteType    = optional($noteTargets->first()->Note)->type_note;
                            $noteTypeLabel = $noteType === 2 ? 'OV' : ($noteType === 1 ? 'EP' : '—');
                        @endphp
                        <tr class="group-row">
                            <td colspan="4" class="ps-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <strong class="chart-title">{{ $noteLabel }}</strong>
                                    <span class="badge-stack badge-type">{{ $noteTypeLabel }}</span>
                                    <span class="badge-stack {{ $closedCount === $noteTargets->count() ? 'badge-closed' : 'badge-open' }}">
                                        {{ $closedCount }}/{{ $noteTargets->count() }} encerradas
                                    </span>
                                    <div class="progress flex-grow-1" style="max-width: 160px; min-width: 100px;">
                                        <div class="progress-bar bg-success" style="width: {{ $notePercent }}%;"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @foreach ($noteTargets as $target)
                            @php
                                $status = (string) ($target->Order->statusSist ?? '');
                                $closed = str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE');
                            @endphp
                            <tr>
                                <td class="ps-3">
                                    Ordem {{ $target->Order->ordem ?? '—' }}
                                    @if ($target->is_exception)
                                        <span class="badge-stack badge-exception ms-1" title="{{ $target->exception_reason }}">EXC</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-stack {{ $closed ? 'badge-closed' : 'badge-open' }}">
                                        {{ $closed ? 'ENCERRADA' : 'EM ABERTO' }}
                                    </span>
                                </td>
                                <td class="text-muted small" title="{{ $status }}">{{ $status ?: '—' }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('closure.order.detail', $target->order_id) }}" class="row-action">
                                        <i class="bi bi-eye"></i> Detalhe
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Nenhuma Ordem nesta competência.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
