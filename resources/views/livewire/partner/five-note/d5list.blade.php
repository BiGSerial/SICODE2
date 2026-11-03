@php
    $canFinish = \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'd5_notes.finish');
    $canExport = \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'd5_notes.export');
    $tabs      = [
        'current' => 'Metas atuais (' . number_format($counts['current'], 0, ',', '.') . ')',
        'passive' => 'Passivos (' . number_format($counts['passive'], 0, ',', '.') . ')',
        'all'     => 'Tudo (' . number_format($counts['all'], 0, ',', '.') . ')',
    ];
    $scopeLabels = ['connection' => 'Ligação', 'network' => 'Rede'];
@endphp

{{-- D5 EM ESPERA / REJEITADAS DA PARCEIRA (lista de controle). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. Escopo por empresa: AuthorizesPartnerAccess. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Notas D5"
        :title="$returned ? 'D5 rejeitadas' : 'D5 em espera de execução'"
        :subtitle="$returned ? 'D5 devolvidas para correção: ajuste e conclua novamente.' : 'Fila de execução e acompanhamento das D5 despachadas para a sua empresa.'">
        @if ($canExport)
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel</button>
        @endif
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros das D5">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar por nota, ordem, PEP ou local…" label="Buscar D5">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#multiSearchModal" title="Busca em massa" aria-label="Busca em massa"><i class="ri-checkbox-multiple-blank-line" aria-hidden="true"></i></button>
            </x-ops.search>

            <x-ops.segmented name="d5Show" label="Mostrar" wire:model="passiveFilter" :options="$tabs" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="toClean"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Período</span>
            <input type="month" class="form-control form-control-sm w-auto" wire:model="month" aria-label="Mês de despacho">
            <input type="date" class="form-control form-control-sm w-auto" wire:model="startDate" aria-label="Despachada a partir de">
            <input type="date" class="form-control form-control-sm w-auto" wire:model="endDate" aria-label="Despachada até">
            @if (count($this->listFilterPayload()['defs']))
                <span class="ops-toolbar-label">Filtros</span>
                <x-ops.filters :payload="$this->listFilterPayload()" />
            @endif
        </div>

        @if ($this->hasListFilters() || $multipleSearch)
            <div class="ops-toolbar-row">
                @if ($multipleSearch)
                    <span class="ops-chip"><span><strong>Busca em massa:</strong> {{ count($multipleSearch) }} termo(s)</span></span>
                @endif
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    <section class="mp-panel" aria-label="{{ $returned ? 'D5 rejeitadas' : 'D5 em espera' }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">{{ $returned ? 'D5 rejeitadas' : 'D5 em espera' }}</h2>
                <div class="mp-panel-sub">{{ number_format($fives->total(), 0, ',', '.') }} {{ $fives->total() === 1 ? 'registro' : 'registros' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

        <div wire:loading.delay.class="d-none">
            @if ($fives->isEmpty())
                <x-closure.empty-state icon="ri-folder-2-line"
                    :title="$hasCriteria ? 'Nenhuma D5 com esses filtros' : ($returned ? 'Nenhuma D5 rejeitada' : 'Sem D5 para execução')"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais D5.' : 'Quando uma D5 for despachada para a sua empresa, ela aparece aqui.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 150px">
                            <col style="width: 90px">
                            <col style="width: 110px">
                            <col style="width: 110px">
                            <col style="width: 120px">
                            <col style="width: 130px">
                            <col>
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col style="width: 16%">
                            <col style="width: 130px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nota D5</th>
                                <th>Informe</th>
                                <th>Escopo</th>
                                <th>Nota</th>
                                <th>Ordem</th>
                                <th>PEP</th>
                                <th>Motivo</th>
                                <th>Codificação</th>
                                <th>Despachada</th>
                                <th>Empresa</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fives as $five)
                                <tr wire:key="five-{{ $five->id }}">
                                    <td>
                                        <x-ops.note :note="$five->note_d5 ?: '—'" kind="d5" tone="blue" :hint="$returned ? 'D5 devolvida para correção' : 'D5 em espera de execução'" :sub="$five->loc_install">
                                            @if ($five->isPassive)
                                                <x-ops.badge sm tone="amber" label="Passivo" hint="Registro passivo: não conta como meta atual" />
                                            @endif
                                        </x-ops.note>
                                    </td>
                                    <td>
                                        @forelse ($five->list_reports as $association)
                                            <x-ops.badge sm tone="muted" mono :label="'#' . $association['report']" hint="Número do informe" />
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @forelse ($five->list_reports as $association)
                                            <x-ops.badge sm :tone="$association['scope'] === 'connection' ? 'amber' : 'green'" :label="$scopeLabels[$association['scope']] ?? 'Geral'" />
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td>{{ $five->note?->note ?? '—' }}</td>
                                    <td>{{ $five->list_order ?? '—' }}</td>
                                    <td>{{ $five->pep ?: '—' }}</td>
                                    <td>{{ $five->reason ?: '—' }}</td>
                                    <td>{{ $five->codify ?: '—' }}</td>
                                    <td><x-ops.age :date="$five->dispatch_at" :days="$five->list_days" rule="dispatch" :ok="4" :warn="9" what="do despacho da D5" /></td>
                                    <td>{{ $five->company?->name ?? '—' }}</td>
                                    <td class="text-end">
                                        @if ($canFinish)
                                            <button type="button" class="btn btn-primary btn-sm ops-action" wire:click="$emitTo('partner.five-note.actions.finish-d5', 'getInfoResponse', {{ $five->id }})"><i class="ri-check-double-line" aria-hidden="true"></i> Concluir</button>
                                        @else
                                            <button type="button" class="btn btn-outline-secondary btn-sm ops-action" disabled title="Seu perfil não tem permissão para concluir D5"><i class="ri-check-double-line" aria-hidden="true"></i> Concluir</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$fives" :per-page="$perPage" :sizes="[25, 50, 100]" />
            @endif
        </div>
    </section>

    {{-- Busca em massa --}}
    <div wire:ignore.self class="modal fade ops-modal" id="multiSearchModal" tabindex="-1" aria-labelledby="multiSearchTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="multiSearchTitle">Notas, ordens ou PEP</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota, ordem ou PEP por linha.</p>
                    <textarea class="form-control" rows="12" wire:model.defer="multiSearch" aria-label="Termos da busca em massa" placeholder="Cole aqui as notas/OV (uma por linha)"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="multiSearch"><i class="ri-search-line" aria-hidden="true"></i> Buscar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Conclusão da D5 (modal próprio, fora do escopo desta migração) --}}
    @livewire('partner.five-note.actions.finish-d5')
</div>
