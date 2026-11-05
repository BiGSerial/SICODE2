@php
    use App\Services\Dispatch\Dashboard\ActivityDashboardAnalytics as Data;

    $s        = $snapshot['summary'];
    $f        = $snapshot['filters'];
    $users    = $snapshot['users'];
    $critical = $snapshot['critical'];
    $bands    = $snapshot['bands'];
    $hasStack = $s['total'] > 0;
    $profile  = $snapshot['profile'];
    $unit     = $profile['unit'];
    $pct      = fn (int $n) => $s['total'] > 0 ? number_format($n / $s['total'] * 100, 0, ',', '.') . '%' : '0%';
    $days     = fn (?int $d) => $d === null ? '—' : $d . ' ' . ($d === 1 ? 'dia' : 'dias');
    $typeText = ['2' => 'Somente OVs', '1' => 'Somente Notas', '' => 'Notas e OVs'][$f['type']];
    $period   = $f['from']->format('d/m/Y') . ' a ' . $f['to']->format('d/m/Y');
    $custom   = $noteType !== '2' || $includeTrackingRubric || $adherenceDays !== '8' || $this->hasListFilters();
    $ageSpark = ['values' => array_map(fn ($a, $b) => $a + $b, $snapshot['status_age']['assigned'], $snapshot['status_age']['unassigned']), 'labels' => $snapshot['status_age']['labels']];
@endphp

{{-- Dashboard de atividade. Padrão: docs/PADRAO_DASHBOARDS.md e docs/dashboards/DASHBOARD_ATIVIDADE.md. --}}
<div class="closure-dash my-panel ops-list">

    <header class="mp-hero">
        <div class="mp-hero-text">
            <div class="mp-eyebrow">Dashboard · {{ $service->service }}</div>
            <h1 class="mp-hero-title">Dashboard de {{ mb_strtolower($service->service) }}</h1>
            <p class="mp-hero-sub">Pilha operacional por {{ mb_strtolower($profile['time']['title']) }}, {{ mb_strtolower($profile['deadline']['title']) }} e atribuição atual. {{ $typeText }}.</p>
        </div>
        <div class="mp-hero-actions">
            <span class="mp-hero-sub me-2" title="Os números da pilha são calculados na hora e guardados por até 1 minuto">Atualizado em {{ $snapshot['updated_at']->format('d/m/Y H:i') }}</span>
            <a class="btn btn-outline-light btn-sm btn-dash" href="{{ route('dispatch.main', ['service' => $service->uuid]) }}"><i class="ri-list-check-2" aria-hidden="true"></i> Lista para {{ $service->service }}</a>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel" title="Excel completo do dashboard, com uma aba por bloco, nos filtros atuais">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </header>

    <section class="mp-filters filter-bar" aria-label="Filtros do dashboard">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-auto">
                <span class="form-label small d-block mb-1">Tipo</span>
                <x-ops.segmented name="dashType" label="Tipo de nota" wire:model="noteType" :options="['2' => 'OV', '1' => 'Nota', '' => 'Ambos']" />
            </div>
            <div class="col-12 col-md-auto">
                <span class="form-label small d-block mb-1">Rubrica</span>
                <x-ops.toggle label="Incluir Acompanhamento" icon="ri-eye-line" :on="$includeTrackingRubric" wire:click.prevent="$toggle('includeTrackingRubric')" title="A rubrica Acompanhamento vem desligada por padrão" />
            </div>
            <div class="col-12 col-xl">
                <span class="form-label small d-block mb-1">Período da aderência (conclusões)</span>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="mp-presets" role="group" aria-label="Períodos rápidos">
                        @foreach (\App\Http\Livewire\Dispatchs\Dashboard::PRESETS as $key => $label)
                            <button type="button" class="mp-preset" wire:click="setPreset('{{ $key }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                    <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="rangeFrom" aria-label="De">
                    <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="rangeTo" aria-label="Até">
                </div>
            </div>
            <div class="col-12 col-md-auto">
                <span class="form-label small d-block mb-1">Limite da 1ª faixa</span>
                <x-ops.segmented name="dashLimit" label="Limite da primeira faixa de aderência" wire:model="adherenceDays" :options="['6' => '6 dias', '8' => '8 dias']" />
            </div>
            @if ($custom || $rangeFrom !== now()->startOfMonth()->toDateString() || $rangeTo !== now()->endOfMonth()->toDateString())
                <div class="col-12 col-md-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="clearFilters"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
                </div>
            @endif
        </div>
        <div class="ops-toolbar-row mt-3">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>
        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
        <div class="mp-period-note" wire:loading.class="is-loading-soft">
            A pilha mostra sempre a situação de agora: {{ $profile['pile'] }} ({{ $typeText }}{{ $f['tracking'] ? '' : ', sem a rubrica Acompanhamento' }}). A aderência usa as conclusões de <strong>{{ $period }}</strong>.@if ($this->hasListFilters()) Grupos, rubrica e localização recortam a pilha e a aderência.@endif
        </div>
    </section>

    @if ($snapshot['too_large'])
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-database-2-line" title="Esta atividade não tem uma pilha própria para o dashboard" text="A pilha passa de {{ number_format(Data::PILE_MAX, 0, ',', '.') }} {{ $unit }}, então os blocos da pilha não se aplicam. A aderência abaixo continua valendo." />
        </section>
    @elseif (!$hasStack)
        <section class="mp-panel mb-3">
            <x-closure.empty-state icon="ri-inbox-line" title="Não há notas na pilha com estes filtros" text="Troque o tipo (Nota/OV) ou inclua a rubrica Acompanhamento para ver mais." />
        </section>
    @endif

    <div wire:loading.class="is-loading-soft" wire:target="noteType,includeTrackingRubric,rangeFrom,rangeTo,adherenceDays,setPreset,clearFilters,applyListFilters,removeListFilter,clearListFilters">
        <h2 class="mp-section-title">Painel executivo</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="blue" icon="ri-stack-line" label="Total na pilha" :value="$s['total']" :note="$typeText . ' · mais antiga há ' . $days($s['oldest_status_days']) . ($profile['key'] === 'status' ? '' : ' desde o informe')" :values="$ageSpark['values']" :labels="$ageSpark['labels']" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="green" icon="ri-user-follow-line" label="Em atribuição" :value="$s['assigned']" :note="$pct($s['assigned']) . ' da pilha · atividades abertas no serviço'" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="amber" icon="ri-user-unfollow-line" label="Sem atribuição" :value="$s['unassigned']" :note="$pct($s['unassigned']) . ' da pilha · disponível para despacho'" />
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <x-home.hero-card tone="red" icon="ri-alarm-warning-line" label="Prazo vencido" :value="$s['overdue']" :note="$profile['time']['avg'] . ': ' . number_format($s['avg_status_days'], 1, ',', '.') . ' dias'" />
            </div>
        </div>

        <h2 class="mp-section-title">A pilha no tempo</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">{{ $profile['time']['title'] }}</h3><div class="mp-panel-sub">{{ $profile['time']['sub'] }}</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="status" data-config="{{ json_encode($charts['status']) }}" role="img" aria-label="Barras empilhadas: {{ $unit }} por dias, em atribuição e na pilha"></canvas>
                        </div>
                        @if (!$hasStack)<div class="mp-chart-empty">Nenhuma nota na pilha</div>@endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">{{ $profile['deadline']['title'] }}</h3><div class="mp-panel-sub">{{ $profile['deadline']['sub'] }}</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart" wire:ignore>
                            <canvas data-my-panel-chart="deadline" data-config="{{ json_encode($charts['deadline']) }}" role="img" aria-label="Barras empilhadas: {{ $unit }} por prazo e por empresa"></canvas>
                        </div>
                        @if (!$hasStack)<div class="mp-chart-empty">Nenhuma nota na pilha</div>@endif
                    </div>
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Equipe e pilha crítica</h2>
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-5">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head"><div><h3 class="mp-panel-title">Quantidade por colaborador</h3><div class="mp-panel-sub">Atribuições abertas, por tempo com o colaborador (top {{ Data::USER_LIMIT }})</div></div></div>
                    <div class="mp-chart-wrap">
                        <div class="mp-chart mp-chart-tall" wire:ignore>
                            <canvas data-my-panel-chart="users" data-config="{{ json_encode($charts['users']) }}" role="img" aria-label="Barras horizontais empilhadas: atribuições abertas por colaborador e por tempo"></canvas>
                        </div>
                        @if ($users['labels'] === [])<div class="mp-chart-empty">Ninguém com atribuições abertas</div>@endif
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-7">
                <section class="mp-panel h-100">
                    <div class="mp-panel-head">
                        <div><h3 class="mp-panel-title">Pilha crítica</h3><div class="mp-panel-sub">Ordenada pelo prazo mais consumido; a lista completa está no Excel</div></div>
                        <span class="mp-pill">{{ count($critical) }} de {{ $s['total'] }}</span>
                    </div>
                    @if ($critical === [])
                        <x-closure.empty-state icon="ri-checkbox-circle-line" title="Nada crítico na pilha" text="Não há notas na pilha com estes filtros." />
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0 mp-table">
                                <colgroup><col class="col-pct-17"><col class="col-pct-17"><col class="col-pct-17"><col class="col-pct-14"><col class="col-pct-14"><col></colgroup>
                                <thead>
                                    <tr>
                                        <th scope="col">Nota/OV</th>
                                        <th scope="col">Rubrica</th>
                                        <th scope="col">Município</th>
                                        <th scope="col" class="text-center">{{ $profile['time']['column'] }}</th>
                                        <th scope="col" class="text-center">Prazo</th>
                                        <th scope="col">Situação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($critical as $row)
                                        <tr wire:key="critical-{{ $row['id'] }}">
                                            <td><strong>{{ $row['note'] }}</strong> <x-ops.badge sm tone="muted" :label="$row['kind']" /></td>
                                            <td>{{ $row['rubrica'] ?: '—' }}</td>
                                            <td>{{ $row['city'] ?: '—' }}</td>
                                            <td class="text-center"><x-ops.badge tone="muted" :label="$days($row['status_days'])" hint="{{ $profile['time']['basis'] }}" /></td>
                                            <td class="text-center">
                                                <x-ops.badge :tone="$row['deadline_tone']" :label="$row['deadline_label']" :hint="$profile['deadline']['rule']" />
                                            </td>
                                            <td>
                                                @if ($row['assigned'])
                                                    <x-ops.badge tone="green" icon="ri-user-line" :label="$row['assigned_user'] ?: 'Em atribuição'" />
                                                @else
                                                    <x-ops.badge tone="blue" label="Na pilha" />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>
        </div>

        <h2 class="mp-section-title">Aderência ao prazo</h2>
        <p class="mp-panel-sub mb-2">OVs concluídas em {{ $period }}: dias entre a data do status e a conclusão, por mês. Primeira faixa: até {{ $bands['limit'] }} dias.</p>
        <div class="row g-3 mb-3">
            @foreach (['satel' => 'SATEL', 'own' => 'Própria (EDP)'] as $key => $label)
                @php $has = collect($bands['groups'][$key]['months'])->sum('total') > 0; @endphp
                <div class="col-12 col-xl-6">
                    <section class="mp-panel h-100">
                        <div class="mp-panel-head"><div><h3 class="mp-panel-title">{{ $label }}</h3><div class="mp-panel-sub">Conclusões por faixa de dias, por mês de conclusão</div></div></div>
                        <div class="mp-chart-wrap">
                            <div class="mp-chart" wire:ignore>
                                <canvas data-my-panel-chart="band_{{ $key }}" data-config="{{ json_encode($charts['band_' . $key]) }}" role="img" aria-label="Barras empilhadas: conclusões da {{ $label }} por faixa de dias e por mês"></canvas>
                            </div>
                            @if (!$has)<div class="mp-chart-empty">Nenhuma conclusão no período</div>@endif
                        </div>
                    </section>
                </div>
            @endforeach
        </div>
    </div>
</div>

@once
    @push('script')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endpush
@endonce
