@php
    $movementFilter = $this->filterValues('movement');
    $overLimit      = $equipments->total() > $excelExportLimit;
@endphp

{{-- EQUIPAMENTOS DECLARADOS (lista de controle da parceira). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. Consulta e escopo: DeclaredEquipmentQueryService. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Informes de conclusão" title="Equipamentos declarados"
        subtitle="Equipamentos instalados e desinstalados informados nas obras concluídas da sua empresa.">
        @if ($canExport)
            <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
                @if ($overLimit)
                    <button type="button" class="btn btn-light btn-sm btn-dash" disabled title="Esta consulta tem mais de {{ number_format($excelExportLimit, 0, ',', '.') }} registros: use CSV."><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel (até {{ number_format($excelExportLimit, 0, ',', '.') }})</button>
                @else
                    <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="exportFile('xlsx')" wire:loading.attr="disabled" wire:target="exportFile"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
                @endif
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="exportFile('csv')" wire:loading.attr="disabled" wire:target="exportFile"><i class="ri-file-list-3-line" aria-hidden="true"></i> Exportar CSV</button>
            </div>
        @endif
    </x-ops.hero>

    <section class="ops-stats" aria-label="Resumo dos equipamentos">
        <x-ops.stat tone="green" label="Instalações" :value="number_format($counts['installed'], 0, ',', '.')" icon="ri-install-line" :active="$movementFilter === ['1']"
            wire:click.prevent="quickMovement('1')" title="Filtrar equipamentos instalados" />
        <x-ops.stat tone="amber" label="Desinstalações" :value="number_format($counts['removed'], 0, ',', '.')" icon="ri-uninstall-line" :active="$movementFilter === ['0']"
            wire:click.prevent="quickMovement('0')" title="Filtrar equipamentos desinstalados" />
    </section>

    <section class="ops-toolbar" aria-label="Filtros dos equipamentos">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Patrimônio, nota/OV, município ou ordem…" label="Buscar equipamento" />

            <label class="ops-toolbar-label" for="equipMonth">Mês</label>
            <input type="month" id="equipMonth" class="form-control form-control-sm w-auto" wire:model="month" max="{{ now()->format('Y-m') }}" min="2023-01">
            <label class="ops-toolbar-label" for="equipDateIn">De</label>
            <input type="date" id="equipDateIn" class="form-control form-control-sm w-auto" wire:model.lazy="date_in">
            <label class="ops-toolbar-label" for="equipDateOut">Até</label>
            <input type="date" id="equipDateOut" class="form-control form-control-sm w-auto" wire:model.lazy="date_out" min="{{ $date_in ?: '' }}">

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

    <section class="mp-panel" aria-label="Equipamentos">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Equipamentos informados</h2>
                <div class="mp-panel-sub">{{ number_format($equipments->total(), 0, ',', '.') }} {{ $equipments->total() === 1 ? 'equipamento' : 'equipamentos' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

        <div wire:loading.delay.class="d-none">
            @if ($equipments->isEmpty())
                <x-closure.empty-state icon="ri-tools-line"
                    :title="$hasCriteria ? 'Nenhum equipamento com esses filtros' : 'Nenhum equipamento encontrado'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais equipamentos.' : 'Os equipamentos declarados nos informes de conclusão aparecem aqui.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 150px">
                            <col style="width: 90px">
                            <col style="width: 140px">
                            <col style="width: 120px">
                            <col style="width: 100px">
                            <col style="width: 140px">
                            <col>
                            <col style="width: 160px">
                            <col style="width: 130px">
                            <col style="width: 110px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Patrimônio</th>
                                <th>Tipo</th>
                                <th>Movimento</th>
                                <th>Nota/OV</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th>Empreiteira</th>
                                <th>Responsável</th>
                                <th>Informado em</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($equipments as $item)
                                @php $report = $item->WorkReport; @endphp
                                <tr wire:key="equipment-{{ $item->id }}">
                                    <td class="fw-bold text-uppercase">{{ $item->patrimony ?: '—' }}</td>
                                    <td>{{ $item->type ?: '—' }}</td>
                                    <td>
                                        @if ($item->installed)
                                            <x-ops.badge tone="green" icon="ri-install-line" label="Instalação" hint="Equipamento instalado na obra" />
                                        @else
                                            <x-ops.badge tone="amber" icon="ri-uninstall-line" label="Desinstalação" hint="Equipamento retirado da obra" />
                                        @endif
                                    </td>
                                    <td><x-ops.note :note="$report?->Note?->note ?? '—'" /></td>
                                    <td>{{ $report?->Note?->rubrica ?: '—' }}</td>
                                    <td>{{ $report?->Note?->lexp ?: '—' }}</td>
                                    <td>{{ $report?->Company?->name ?: '—' }}</td>
                                    <td>{{ $report?->informer ?: '—' }}</td>
                                    <td><x-ops.date :date="$item->created_at" :time="true" hint="Quando o equipamento foi informado" /></td>
                                    <td class="text-end">
                                        @if ($report)
                                            <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click="$emitTo('partner.show.show-work-form', 'show_form', {{ $report->id }})"><i class="ri-eye-line" aria-hidden="true"></i> Informe</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$equipments" :per-page="$perPage" />
            @endif
        </div>
    </section>

    @livewire('partner.show.show-work-form', key('FormModdalShow'))
</div>
