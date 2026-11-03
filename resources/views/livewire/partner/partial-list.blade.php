@php
    $statusFilter = $this->filterValues('status');
@endphp

{{-- OBRAS PARCIAIS INFORMADAS (lista de controle da parceira). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. Escopo por empresa: AuthorizesPartnerAccess. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Informes parciais" title="Obras parciais informadas"
        subtitle="Acompanhe cada informe parcial da sua empresa da avaliação até o pagamento.">
    </x-ops.hero>

    <section class="ops-stats" aria-label="Resumo das parciais">
        <x-ops.stat tone="amber" label="Avaliação" :value="number_format($counts['evaluation'], 0, ',', '.')" icon="ri-hourglass-line" :active="$statusFilter === ['evaluation']"
            wire:click.prevent="quickStatus('evaluation')" title="Filtrar parciais aguardando aprovação" />
        <x-ops.stat tone="blue" label="Em fiscalização" :value="number_format($counts['supervising'], 0, ',', '.')" icon="ri-search-eye-line" :active="$statusFilter === ['supervising']"
            wire:click.prevent="quickStatus('supervising')" title="Filtrar parciais aprovadas aguardando fiscalização" />
        <x-ops.stat tone="teal" label="Em medição" :value="number_format($counts['measuring'], 0, ',', '.')" icon="ri-ruler-line" :active="$statusFilter === ['measuring']"
            wire:click.prevent="quickStatus('measuring')" title="Filtrar parciais fiscalizadas aguardando pagamento" />
        <x-ops.stat tone="green" label="Pagas" :value="number_format($counts['paid'], 0, ',', '.')" icon="ri-money-dollar-circle-line" :active="$statusFilter === ['paid']"
            wire:click.prevent="quickStatus('paid')" title="Filtrar parciais pagas" />
        <x-ops.stat tone="red" label="Rejeitadas" :value="number_format($counts['rejected'], 0, ',', '.')" icon="ri-close-circle-line" :active="$statusFilter === ['rejected']"
            wire:click.prevent="quickStatus('rejected')" title="Filtrar parciais rejeitadas" />
    </section>

    <section class="ops-toolbar" aria-label="Filtros das parciais">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Nota, OV ou ordem…" label="Buscar parcial" />

            <label class="ops-toolbar-label" for="partialDtIn">De</label>
            <input type="date" id="partialDtIn" class="form-control form-control-sm w-auto" wire:model.lazy="dt_in">
            <label class="ops-toolbar-label" for="partialDtOut">Até</label>
            <input type="date" id="partialDtOut" class="form-control form-control-sm w-auto" wire:model.lazy="dt_out" min="{{ $dt_in ?: '' }}">

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

    <section class="mp-panel" aria-label="Parciais">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Lista de obras parciais informadas</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'parcial' : 'parciais' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

        <div wire:loading.delay.class="d-none">
            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-file-list-2-line"
                    :title="$hasCriteria ? 'Nenhuma parcial com esses filtros' : 'Sem informes parciais'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais parciais.' : 'Quando a sua empresa informar uma obra parcial, ela aparece aqui.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 120px">
                            <col style="width: 90px">
                            <col style="width: 110px">
                            <col style="width: 70px">
                            <col style="width: 130px">
                            <col style="width: 130px">
                            <col style="width: 130px">
                            <col style="width: 130px">
                            <col style="width: 120px">
                            <col style="width: 140px">
                            <col style="width: 90px">
                            <col style="width: 110px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nota/OV</th>
                                <th>Escopo</th>
                                <th>Ordem</th>
                                <th>Arquivos</th>
                                <th>Envio</th>
                                <th>Aprovação</th>
                                <th>Fiscalização</th>
                                <th>Pagamento</th>
                                <th>Valor ADS</th>
                                <th>Situação</th>
                                <th>Finalizado</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php
                                    $status = $this->partialStatus($list);
                                @endphp
                                <tr wire:key="partial-hist{{ $list->id }}">
                                    <td><x-ops.note :note="$list->Note->note" /></td>
                                    <td><x-ops.badge sm tone="muted" :label="$list->scopeLabel()" hint="Escopo da parcial" /></td>
                                    <td>
                                        @forelse ($list->orders as $order)
                                            <div>{{ $order->ordem }}</div>
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td><x-files.select-download-list :files="$list->Note->Files" :tacit-file-ids="$tacitFiles" /></td>
                                    <td><x-ops.date :date="$list->created_at" :time="true" hint="Quando a parcial foi enviada" /></td>
                                    <td>
                                        @if (!$list->allow && !$list->deny)
                                            <x-ops.badge tone="blue" icon="ri-hourglass-line" :label="$list->created_at->diffInDays() . ' d'" :hint="'Aguardando aprovação há ' . $list->created_at->diffInDays() . ' dias'" />
                                        @else
                                            <x-ops.date :date="$list->decision_at" :time="true" hint="Quando a parcial foi aprovada ou rejeitada" />
                                        @endif
                                    </td>
                                    <td>
                                        @if ($list->allow && !$list->supervision)
                                            <x-ops.badge tone="blue" icon="ri-hourglass-line" :label="($list->decision_at?->diffInDays() ?? 0) . ' d'" :hint="'Aguardando fiscalização há ' . ($list->decision_at?->diffInDays() ?? 0) . ' dias'" />
                                        @else
                                            <x-ops.date :date="$list->supervision_at" :time="true" hint="Quando a parcial foi fiscalizada" />
                                        @endif
                                    </td>
                                    <td>
                                        @if ($list->supervision && !$list->payment)
                                            <x-ops.badge tone="blue" icon="ri-hourglass-line" :label="($list->supervision_at?->diffInDays() ?? 0) . ' d'" :hint="'Aguardando pagamento há ' . ($list->supervision_at?->diffInDays() ?? 0) . ' dias'" />
                                        @else
                                            <x-ops.date :date="$list->payment_at" :time="true" hint="Quando a parcial foi paga" />
                                        @endif
                                    </td>
                                    <td><x-ops.money :value="$list->value" /></td>
                                    <td><x-ops.badge :tone="$status['tone']" :label="$status['status']" hint="Onde a parcial está no fluxo" /></td>
                                    <td>
                                        @if ($list->complete)
                                            <x-ops.badge sm tone="green" label="Sim" hint="Parcial finalizada" />
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click.prevent="$emitTo('partner.show.show-partial-info', 'show_form', {{ $list->id }})"><i class="ri-eye-line" aria-hidden="true"></i> Detalhes</button>
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

    @livewire('partner.show.show-partial-info', key('show_partial_info'))
</div>
