@php
    $hasCriteria = $this->hasCriteria();
@endphp

{{-- AUDITORIA DE NOTAS (lista de controle). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md; regras e índices: docs/admin/AUDITORIA.md. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Administração · Dados" title="Auditoria de notas"
        subtitle="Quem mexeu em cada nota e atividade, quando, e o que mudou. Busque por nota, registro, usuário ou campo alterado." />

    <section class="ops-toolbar" aria-label="Filtros da auditoria">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="note" placeholder="Nota ou OV…" label="Buscar por nota ou OV" />
            <x-ops.search wire:model.debounce.500ms="record" placeholder="ID da atividade…" label="Buscar pelo ID do registro" />

            <x-ops.segmented name="auditPeriod" label="Período" wire:model="period" :options="\App\Http\Livewire\Admin\Audits\NoteAudit::PERIODS" />

            @if ($period === 'custom')
                <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="dateFrom" aria-label="De">
                <input type="date" class="form-control form-control-sm w-auto" wire:model.lazy="dateTo" aria-label="Até">
            @endif

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

    <section class="mp-panel" aria-label="Registros de auditoria">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Registros de auditoria</h2>
                <div class="mp-panel-sub">
                    {{ number_format($audits->total(), 0, ',', '.') }} {{ $audits->total() === 1 ? 'registro' : 'registros' }}
                    @if ($periodOn && $period !== 'all') · {{ $this->periodLabel() }} @elseif (!$periodOn) · busca em todo o histórico @endif
                </div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="7" />

        <div wire:loading.delay.class="d-none">
            @if ($rows === [])
                <x-closure.empty-state icon="ri-file-search-line"
                    :title="$noteMiss ? 'Nota não encontrada' : 'Nenhum registro de auditoria'"
                    :text="$noteMiss ? 'Confira o número da nota ou da OV digitado.' : ($hasCriteria ? 'Ajuste ou limpe os filtros, ou amplie o período.' : 'Nada foi alterado no período. Escolha 30 dias ou Tudo para ver mais.')" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 130px">
                            <col style="width: 190px">
                            <col style="width: 130px">
                            <col style="width: 150px">
                            <col style="width: 120px">
                            <col style="width: 140px">
                            <col>
                            <col style="width: 90px">
                            <col style="width: 100px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Modelo</th>
                                <th>Nota/OV</th>
                                <th>Atividade</th>
                                <th>O que mudou</th>
                                <th>Registro</th>
                                <th><span class="visually-hidden">Detalhes</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="audit-{{ $row['id'] }}">
                                    <td><x-ops.date :date="$row['when']" time /></td>
                                    <td>{{ $row['user'] ?? '—' }}</td>
                                    <td><x-ops.badge :tone="$row['action']['tone']" :label="$row['action']['label']" /></td>
                                    <td>{{ $row['model'] }}</td>
                                    <td>@if ($row['note'])<x-ops.note :note="$row['note']" />@else<span class="ops-empty">—</span>@endif</td>
                                    <td>{{ $row['service'] ?? '—' }}</td>
                                    <td>
                                        @forelse ($row['summary'] as $change)
                                            <div class="ops-person-sub" title="{{ $change['field'] }}: {{ $change['before'] }} → {{ $change['after'] }}">
                                                <strong>{{ $change['field'] }}:</strong> {{ $change['before'] }} <i class="ri-arrow-right-line" aria-hidden="true"></i> {{ $change['after'] }}
                                            </div>
                                        @empty
                                            <span class="ops-person-sub">{{ $row['noChange'] ? 'Gravação sem alteração de dados' : 'Registro ' . strtolower($row['action']['label']) }}</span>
                                        @endforelse
                                        @if ($row['more'])
                                            <div class="ops-person-sub">+ {{ $row['more'] }} {{ $row['more'] === 1 ? 'campo' : 'campos' }}</div>
                                        @endif
                                    </td>
                                    <td>@if ($row['recordId'])<x-ops.badge sm mono tone="muted" :label="$row['recordId']" hint="ID do registro auditado" />@else<span class="ops-empty">—</span>@endif</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-primary btn-sm ops-action" wire:click="openDetails({{ $row['id'] }})">
                                            <i class="ri-eye-line" aria-hidden="true"></i> Ver
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$audits" :per-page="$perPage" :sizes="[25, 50, 100, 250]" />
            @endif
        </div>
    </section>

    @if ($detail)
        <x-ops.modal eyebrow="Detalhes da auditoria" :title="'Nota/OV ' . ($detail['row']['note'] ?? '—')" close="closeDetails" size="modal-lg">
            <div class="ops-modal-card">
                <div class="ops-modal-meta">
                    <x-ops.badge :tone="$detail['row']['action']['tone']" :label="$detail['row']['action']['label']" />
                    <span>{{ $detail['row']['when']->format('d/m/Y H:i:s') }}</span>
                    <span>{{ $detail['row']['user'] ?? '—' }}</span>
                    <span>{{ $detail['row']['model'] }}@if ($detail['row']['service']) · {{ $detail['row']['service'] }}@endif @if ($detail['row']['recordId']) · #{{ $detail['row']['recordId'] }}@endif</span>
                </div>
            </div>

            @if ($detail['rows'])
                <div class="table-responsive mt-3">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 28%">
                            <col>
                            <col>
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Antes</th>
                                <th>Depois</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($detail['rows'] as $change)
                                <tr wire:key="change-{{ $change['key'] }}">
                                    <td><strong>{{ $change['field'] }}</strong>@if ($change['field'] !== $change['key']) <div class="ops-person-sub">{{ $change['key'] }}</div>@endif</td>
                                    @foreach (['before', 'after'] as $i => $side)
                                        <td>
                                            @if ($change['tones'] && $change[$side] !== '—')
                                                <x-ops.badge :tone="$change['tones'][$i]" :label="$change[$side]" />
                                            @else
                                                {{ $change[$side] }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="ops-modal-note mt-3 mb-0">Esta gravação não alterou nenhum dado (o sistema apenas salvou o registro de novo).</p>
            @endif

            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" wire:click="closeDetails">Fechar</button>
            </x-slot>
        </x-ops.modal>
    @endif
</div>
