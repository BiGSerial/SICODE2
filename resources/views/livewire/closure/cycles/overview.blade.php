<div class="closure-dash">
    @include('livewire.closure._styles')

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Controle de Encerramento — Visão Geral</h2>
                <div class="dash-subtitle">Meta mensal, passivo acumulado e produtividade do encerramento de Ordens no SAP.</div>
            </div>
            <div class="text-end small opacity-75">
                @if ($currentCycle)
                    Competência atual: <strong>{{ $currentCycle->label }}</strong>
                @else
                    Nenhuma competência congelada ainda
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-teal);">
                <div class="metric-label">Meta do mês</div>
                <div class="metric-value">{{ number_format($metaTotal, 0, ',', '.') }}</div>
                <div class="metric-note">Ordens elegíveis na competência {{ $currentCycle->label ?? '—' }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-green);">
                <div class="metric-label">Encerradas</div>
                <div class="metric-value">{{ number_format($metaClosed, 0, ',', '.') }}</div>
                <div class="metric-note">{{ $metaPercent }}% da meta concluída</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-amber);">
                <div class="metric-label">Em aberto (meta atual)</div>
                <div class="metric-value">{{ number_format($metaOpen, 0, ',', '.') }}</div>
                <div class="metric-note">Ainda dentro do prazo da competência</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card h-100" style="--accent: var(--dash-red);">
                <div class="metric-label">Passivo acumulado</div>
                <div class="metric-value">{{ number_format($passiveTotal, 0, ',', '.') }}</div>
                <div class="metric-note">
                    {{ $passiveByCycle->count() }} {{ $passiveByCycle->count() === 1 ? 'competência' : 'competências' }} em atraso
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-4">
            <div class="chart-card h-100">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Meta do Mês</h3>
                        <div class="chart-subtitle">Encerradas x em aberto</div>
                    </div>
                    <i class="bi bi-pie-chart text-secondary fs-4"></i>
                </div>
                <div class="chart-body" wire:ignore>
                    <x-grafico.apex :chart="$statusDonutChart" chartId="closure-status-donut" :showDataLabels="true" class="w-100 h-100" />
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="chart-card h-100">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Passivo por Competência</h3>
                        <div class="chart-subtitle">Ordens em aberto de competências anteriores, por mês de origem</div>
                    </div>
                    <i class="bi bi-bar-chart text-secondary fs-4"></i>
                </div>
                <div class="chart-body" wire:ignore>
                    <x-grafico.apex :chart="$passiveBarChart" chartId="closure-passive-bar" :showDataLabels="true" class="w-100 h-100" />
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="table-card">
                <div class="chart-head">
                    <div>
                        <h3 class="chart-title">Passivo mais antigo</h3>
                        <div class="chart-subtitle">Ordens paradas há mais tempo — prioridade de atuação</div>
                    </div>
                    <a href="{{ route('closure.passive') }}" class="btn btn-outline-secondary btn-sm btn-dash">Ver passivo completo</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <colgroup>
                            <col style="width: 16%;">
                            <col style="width: 13%;">
                            <col style="width: 14%;">
                            <col style="width: 12%;">
                            <col style="width: 27%;">
                            <col style="width: 18%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="ps-3">Ordem</th>
                                <th>Nota</th>
                                <th>Competência original</th>
                                <th>Aging</th>
                                <th>statusSist</th>
                                <th class="pe-3 text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($criticalPassive as $target)
                                @php $days = (int) ($target->Cycle?->startDate()->diffInDays(now()) ?? 0); @endphp
                                <tr>
                                    <td class="ps-3">
                                        {{ $target->Order->ordem ?? '—' }}
                                        @if ($target->is_exception)
                                            <span class="badge-stack badge-exception ms-1" title="{{ $target->exception_reason }}">EXC</span>
                                        @endif
                                    </td>
                                    <td>{{ $target->Note->note ?? '—' }}</td>
                                    <td>{{ $target->Cycle->label ?? '—' }}</td>
                                    <td>
                                        <span class="badge-stack {{ $days > 180 ? 'badge-aging-high' : ($days > 60 ? 'badge-aging-mid' : 'badge-aging-low') }}">
                                            {{ $days }} dias
                                        </span>
                                    </td>
                                    <td class="text-muted small" title="{{ $target->Order->statusSist }}">{{ $target->Order->statusSist ?? '—' }}</td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('closure.order.detail', $target->order_id) }}" class="row-action">
                                            <i class="bi bi-eye"></i> Detalhe
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nenhuma Ordem em passivo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <a href="{{ route('closure.meta') }}" class="btn btn-sm btn-dash text-white" style="background: var(--dash-teal);">Ver Meta completa</a>
        <a href="{{ route('closure.passive') }}" class="btn btn-sm btn-outline-secondary btn-dash">Ver Passivo completo</a>
    </div>
</div>
