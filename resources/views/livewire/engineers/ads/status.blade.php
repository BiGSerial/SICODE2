@php
    $hasCriteria = $this->hasCriteria();
    $isCurrent   = $statusFilter === 'atual';
    $stats       = [
        'a_informar'          => ['tone' => 'blue',  'label' => 'A informar',          'icon' => 'ri-edit-box-line'],
        'no_prazo'            => ['tone' => 'green', 'label' => 'No prazo',            'icon' => 'ri-time-line'],
        'vencendo_3_dias'     => ['tone' => 'amber', 'label' => 'Vencendo (3 dias)',   'icon' => 'ri-alarm-warning-line'],
        'vencida_sem_entrega' => ['tone' => 'red',   'label' => 'Vencida sem entrega', 'icon' => 'ri-error-warning-line'],
        'com_entrega'         => ['tone' => 'teal',  'label' => 'Com entrega',         'icon' => 'ri-check-double-line'],
        'entregue_atraso'     => ['tone' => 'muted', 'label' => 'Entregue em atraso',  'icon' => 'ri-history-line'],
    ];
    $topOverdue  = collect($summary['top_companies_overdue'])->map(fn ($company) => [
        'label' => $company['name'],
        'text'  => $company['count'],
        'pct'   => $company['count'] / max(1, $summary['top_companies_overdue'][0]['count']) * 100,
        'tone'  => 'red',
    ])->all();
@endphp

{{-- Lista de controle (acompanha, não despacha). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. --}}
<div class="closure-dash my-panel ops-list ops-control" x-data="opsSelection()">

    <x-ops.hero
        class="ops-hero--control"
        eyebrow="Engenharia · Informes de conclusão"
        title="Situação de ADS"
        subtitle="Prazo tácito dos informes: o que está a informar, no prazo, vencendo ou vencido, e a multa prevista por atraso."
        :updated-at="null">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <span class="ops-mode-pill" title="Lista de controle: aqui se acompanha o prazo e se abona dia não útil."><i class="ri-radar-line" aria-hidden="true"></i> Lista de controle</span>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportReport" wire:loading.attr="disabled" wire:target="exportReport">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-stats" aria-label="Situação dos informes">
        <x-ops.stat tone="teal" label="Total de informes" :value="number_format($summary['total'], 0, ',', '.')" icon="ri-file-list-3-line" :active="false" disabled />
        @if ($isCurrent)
            @foreach ($stats as $code => $stat)
                <x-ops.stat :tone="$stat['tone']" :label="$stat['label']" :value="number_format($summary[$code] ?? 0, 0, ',', '.')" :icon="$stat['icon']"
                    :active="$detailStatusFilter === $code" wire:click.prevent="setDetailStatusFilter('{{ $code }}')" wire:key="ads-stat-{{ $code }}"
                    :title="'Filtrar por ' . mb_strtolower($stat['label'])" />
            @endforeach
        @endif
    </section>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota ou empreiteira" label="Buscar nota ou empreiteira" />

            <x-ops.segmented name="adsScope" label="Escopo" wire:model="statusFilter" :options="['atual' => 'Atual', 'passivo' => 'Passivo']" />

            <label class="ops-toolbar-label" for="ads-date-in">Informe de</label>
            <input id="ads-date-in" type="date" class="form-control form-control-sm ops-date-input" wire:model.lazy="date_in" max="{{ now()->toDateString() }}" aria-label="Data inicial do informe">
            <label class="ops-toolbar-label" for="ads-date-out">até</label>
            <input id="ads-date-out" type="date" class="form-control form-control-sm ops-date-input" wire:model.lazy="date_out" max="{{ now()->toDateString() }}" aria-label="Data final do informe">

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Filtros</span>
            <x-ops.filters :payload="$this->listFilterPayload()" />
        </div>

        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    @if ($topOverdue)
        <section class="mp-panel" aria-label="Empreiteiras com mais ADS vencidas">
            <div class="mp-panel-head">
                <div>
                    <h2 class="mp-panel-title">Mais ADS vencidas sem entrega</h2>
                    <div class="mp-panel-sub">Top 5 empreiteiras nos filtros atuais</div>
                </div>
            </div>
            <div class="mp-panel-body">
                <x-home.bar-list :rows="$topOverdue" caption="Empreiteiras com mais ADS vencidas sem entrega" :headers="['Empreiteira', 'Qtd', 'Detalhe']" />
            </div>
        </section>
    @endif

    <section class="mp-panel" aria-label="Informes e prazo de ADS">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Informes · {{ $isCurrent ? 'ADS atual' : 'Passivo' }}</h2>
                <div class="mp-panel-sub">
                    {{ number_format($rows->total(), 0, ',', '.') }} {{ $rows->total() === 1 ? 'registro' : 'registros' }}
                </div>
            </div>
            <span class="mp-pill" wire:loading.delay>Atualizando…</span>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum resultado para os filtros escolhidos' : 'Nenhum informe no período'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais informes.' : 'Amplie o período do informe para ver mais registros.'"
                    icon="ri-inbox-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        <col style="width: 38px">
                        <col style="width: 110px">
                        <col style="width: 160px">
                        <col style="width: 48px">
                        <col style="width: 104px">
                        <col style="width: 112px">
                        <col style="width: 112px">
                        <col style="width: 140px">
                        <col style="width: 120px">
                        <col style="width: 100px">
                        <col style="width: 100px">
                        <col style="width: 100px">
                        <col style="width: 112px">
                        <col style="width: 104px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th><span class="visually-hidden">Seleção</span></th>
                            <th>Nota</th>
                            <th class="is-left">Empreiteira</th>
                            <th>UF</th>
                            <th>Informe</th>
                            <th>Limite ADS</th>
                            <th>Entrega ADS</th>
                            <th>Situação</th>
                            <th>Prazo</th>
                            <th>Faixa</th>
                            <th>Base</th>
                            <th>Multa diária</th>
                            <th>Multa prevista</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php $fine = $rowFineData[$row['work_report_id']] ?? null; @endphp
                            <tr wire:key="ads-status-{{ $row['work_report_id'] }}">
                                <td>
                                    <input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['work_report_id'] }}" wire:model.defer="selected" aria-label="Selecionar {{ $row['note_number'] }}" @change="recount()">
                                </td>
                                <td class="copy-text" data-value="{{ $row['note_number'] }}"><x-ops.note :note="$row['note_number']" /></td>
                                <td class="is-left">{{ $row['company_name'] }}</td>
                                <td>{{ $row['state'] ?: '—' }}</td>
                                <td><x-ops.date :date="$row['informed_at']" time hint="Data e hora do informe" /></td>
                                <td><x-ops.date :date="$row['due_at']" :tone="$row['due_tone']" time hint="Limite para entregar a ADS. Aviso em 3 dias; vermelho se venceu ou foi entregue depois" /></td>
                                <td><x-ops.date :date="$row['delivered_at']" time hint="Quando a ADS foi entregue" /></td>
                                <td><x-ops.badge :tone="$row['status_tone']" :label="$row['status_label']" :hint="'Situação do prazo tácito: ' . $row['status_label']" /></td>
                                <td>
                                    @if ($row['time_label'])
                                        <x-ops.badge :tone="$row['time_tone']" :label="$row['time_label']" hint="Dias para vencer ou em atraso, pela regra de dias úteis da ADS" />
                                    @else
                                        <span class="ops-empty" aria-label="Sem prazo em aberto">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['penalty_percentage'] > 0)
                                        <x-ops.badge tone="amber" :label="number_format($row['penalty_percentage'], 1, ',', '.') . '%'" :hint="'Faixa de multa: ' . str_replace('_', ' ', $row['penalty_band'])" />
                                    @else
                                        <span class="ops-empty" aria-label="Sem multa">—</span>
                                    @endif
                                </td>
                                <td><x-ops.money :value="$fine['base_amount'] ?? null" /></td>
                                <td><x-ops.money :value="$fine['daily_fine_amount'] ?? null" /></td>
                                <td><x-ops.money :value="$fine['total_fine_amount'] ?? null" /></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm ops-action" wire:click="refreshFine({{ $row['work_report_id'] }})" wire:loading.attr="disabled" wire:target="refreshFine({{ $row['work_report_id'] }})">
                                        <i class="ri-calculator-line" aria-hidden="true"></i> Calcular multa
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-ops.pager :paginator="$rows" :per-page="$perPage" />
        @endif
    </section>

    <div wire:ignore>
        <x-ops.selection-bar>
            <button type="button" class="btn btn-light btn-sm btn-dash" data-bs-toggle="modal" data-bs-target="#ads_abono">
                <i class="ri-calendar-close-line" aria-hidden="true"></i> Abonar dia não útil
            </button>
        </x-ops.selection-bar>
    </div>

    {{-- MODAL: abono de dia não útil para os informes marcados. --}}
    <div wire:ignore.self class="modal fade ops-modal" id="ads_abono" tabindex="-1" aria-labelledby="adsAbonoLabel" aria-hidden="true"
        x-data @ads-abono-done.window="window.bootstrap?.Modal.getInstance($el)?.hide()">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Prazo da ADS</span>
                        <h5 class="modal-title" id="adsAbonoLabel">Abonar dia não útil</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">O dia escolhido deixa de contar no prazo dos informes marcados e o limite da ADS é recalculado.</p>

                    <label class="form-label text-uppercase small fw-bold" for="ads-abono-date">Data abonada</label>
                    <input id="ads-abono-date" type="date" class="form-control mb-1" wire:model.defer="adjustmentDate">
                    @error('adjustmentDate') <small class="text-danger d-block mb-2">{{ $message }}</small> @enderror

                    <label class="form-label text-uppercase small fw-bold mt-3" for="ads-abono-reason">Justificativa</label>
                    <textarea id="ads-abono-reason" class="form-control" rows="3" wire:model.defer="adjustmentReason" placeholder="Motivo para considerar a data como não útil (mínimo de 10 caracteres)"></textarea>
                    @error('adjustmentReason') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="addNonWorkingDayAdjustment" wire:loading.attr="disabled" wire:target="addNonWorkingDayAdjustment">
                        <i class="ri-calendar-close-line" aria-hidden="true"></i> Abonar selecionados
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
