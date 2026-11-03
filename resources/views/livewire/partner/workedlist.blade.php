@php
    $situationFilter = $this->filterValues('situation');
@endphp

{{-- OBRAS INFORMADAS (lista de controle da parceira). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. Escopo por empresa: AuthorizesPartnerAccess. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Informes de conclusão" title="Obras informadas"
        subtitle="Todos os informes da sua empresa, incluindo rejeitados e cancelados, com o andamento de cada um.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-stats" aria-label="Resumo dos informes">
        <x-ops.stat tone="teal" label="Informados" :value="number_format($counts['informed'], 0, ',', '.')" icon="ri-file-list-3-line" :active="$situationFilter === ['informed']"
            wire:click.prevent="quickSituation('informed')" title="Filtrar informes em andamento (não rejeitados nem cancelados)" />
        <x-ops.stat tone="amber" label="Rejeitados" :value="number_format($counts['rejected'], 0, ',', '.')" icon="ri-arrow-go-back-line" :active="$situationFilter === ['rejected']"
            wire:click.prevent="quickSituation('rejected')" title="Filtrar informes devolvidos para correção" />
        <x-ops.stat tone="muted" label="Cancelados" :value="number_format($counts['canceled'], 0, ',', '.')" icon="ri-close-circle-line" :active="$situationFilter === ['canceled']"
            wire:click.prevent="quickSituation('canceled')" title="Filtrar informes cancelados" />
    </section>

    <section class="ops-toolbar" aria-label="Filtros dos informes">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Nota, OV ou ordem…" label="Buscar informe">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#workedListMultiSearch" title="Buscar vários registros de uma vez" aria-label="Buscar vários registros">
                    <i class="ri-file-copy-line" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <label class="ops-toolbar-label" for="workedMonth">Mês</label>
            <input type="month" id="workedMonth" class="form-control form-control-sm w-auto" wire:model="month" max="{{ now()->format('Y-m') }}" min="2023-05">
            <label class="ops-toolbar-label" for="workedDateIn">De</label>
            <input type="date" id="workedDateIn" class="form-control form-control-sm w-auto" wire:model.lazy="date_in" min="2023-05-01">
            <label class="ops-toolbar-label" for="workedDateOut">Até</label>
            <input type="date" id="workedDateOut" class="form-control form-control-sm w-auto" wire:model.lazy="date_out" min="{{ $date_in ?: '2023-05-01' }}">

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

    <section class="mp-panel" aria-label="Informes">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Informes de conclusão</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'informe' : 'informes' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

        <div wire:loading.delay.class="d-none">
            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-file-list-3-line"
                    :title="$hasCriteria ? 'Nenhum informe com esses filtros' : 'Nenhum informe encontrado'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais informes.' : 'Quando a sua empresa informar uma obra concluída, ela aparece aqui.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 110px">
                            <col style="width: 150px">
                            <col style="width: 120px">
                            <col style="width: 110px">
                            <col style="width: 90px">
                            <col style="width: 100px">
                            <col style="width: 70px">
                            <col style="width: 60px">
                            <col style="width: 80px">
                            <col style="width: 110px">
                            <col>
                            <col style="width: 130px">
                            <col style="width: 130px">
                            <col style="width: 110px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Situação</th>
                                <th>Status atual</th>
                                <th>Nota/OV</th>
                                <th>Ordens</th>
                                <th>Escopo</th>
                                <th>Rubrica</th>
                                <th>Arquivos</th>
                                <th>Equip.</th>
                                <th>Alteração</th>
                                <th>Equipe WPA</th>
                                <th>Responsável / Empreiteira</th>
                                <th>Conclusão informada</th>
                                <th>ADS entregue</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $adsAt = $list->Adsform
                                        ? ($list->Adsform->tacit ? $list->Adsform->tacit_delivered_at : $list->Adsform->created_at)
                                        : ($list->Note->OldAds->isNotEmpty() ? $list->Note->OldAds->last()->date : null);
                                @endphp
                                <tr wire:key="work-report-{{ $list->id }}">
                                    <td>
                                        @if ($list->canceled)
                                            <x-ops.badge tone="muted" icon="ri-close-circle-line" label="Cancelado" hint="Informe cancelado" />
                                        @elseif ($list->rejected)
                                            <x-ops.badge tone="amber" icon="ri-arrow-go-back-line" label="Rejeitado" hint="Devolvido para correção" />
                                        @else
                                            <x-ops.badge tone="green" icon="ri-checkbox-circle-line" label="Informado" hint="Informe enviado" />
                                        @endif
                                    </td>
                                    <td>
                                        @if ($list->current_status_label)
                                            <x-ops.badge :tone="$this->classTone($list->current_status_class)" :label="$list->current_status_label" hint="Onde o informe está agora no fluxo" />
                                        @else
                                            <span class="ops-sub">Atualizando</span>
                                        @endif
                                    </td>
                                    <td><x-ops.note :note="$list->Note->note" /></td>
                                    <td>
                                        @forelse ($list->Orders as $order)
                                            <div>{{ $order->ordem }}</div>
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach ($list->finalScopeBadges() as $scopeBadge)
                                                <x-ops.badge sm :tone="$this->classTone($scopeBadge['class'])" :label="$scopeBadge['label']" hint="Escopo informado" />
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>{{ $list->Note->rubrica ?: '—' }}</td>
                                    <td><x-files.select-download-list :files="$list->Note->Files" :tacit-file-ids="$tacitFiles" /></td>
                                    <td>
                                        @if ($list->Equipment->count())
                                            <x-ops.badge sm tone="teal" :label="$list->Equipment->count()" hint="Equipamentos declarados no informe" />
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($list->changes)
                                            <x-ops.badge sm tone="amber" label="Sim" hint="O informe teve alteração" />
                                        @else
                                            <span class="ops-sub">Não</span>
                                        @endif
                                    </td>
                                    <td>{{ $list->team ?: 'Desconhecido' }}</td>
                                    <td>
                                        <div class="ops-person-name">{{ $list->responsible ?: 'Desconhecido' }}</div>
                                        <div class="ops-person-sub">{{ $list->Company?->name ?: 'Empreiteira não informada' }}</div>
                                    </td>
                                    <td><x-ops.date :date="$list->informed_at" :time="true" hint="Quando o informe foi enviado" /></td>
                                    <td><x-ops.date :date="$adsAt" :time="true" hint="Quando a ADS foi entregue" /></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click="$emitTo('partner.show.show-work-form', 'show_form', {{ $list->id }})"><i class="ri-eye-line" aria-hidden="true"></i> Detalhes</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$lists" :per-page="$perPage" />
            @endif
        </div>
    </section>

    @livewire('partner.show.show-work-form', key('FormModdalShow'))

    <div class="modal fade ops-modal" id="workedListMultiSearch" tabindex="-1" aria-labelledby="workedListMultiSearchTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="workedListMultiSearchTitle">Buscar vários registros</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota, OV ou ordem por linha, ou separe por vírgula, ponto e vírgula ou espaço.</p>
                    <label for="workedListMultiSearchText" class="form-label text-uppercase">Notas, OVs ou ordens</label>
                    <textarea id="workedListMultiSearchText" class="form-control" rows="8" wire:model.defer="multiSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="applyMultiSearch" data-bs-dismiss="modal"><i class="ri-search-line" aria-hidden="true"></i> Buscar</button>
                </div>
            </div>
        </div>
    </div>
</div>
