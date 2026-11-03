@php
    $canReinform   = \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'conclusion_reports.reinform');
    $detailOpen    = filled($selectedRejectedWorkReportId);
    $detailTotal   = count($selectedReturnworks);
    $detailCurrent = $selectedReturnworks[$selectedReturnworkIndex] ?? null;
    $detailOfficialIndex = max(0, $detailTotal - 1);
    $detailIsOfficial    = $detailCurrent && $selectedReturnworkIndex === $detailOfficialIndex;
@endphp

{{-- INFORMES REJEITADOS (lista de controle da parceira). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. Escopo por empresa: AuthorizesPartnerAccess. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Informes de conclusão" title="Informes rejeitados"
        subtitle="Informes devolvidos para correção. Veja o motivo e reinforme a obra.">
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros dos informes rejeitados">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Nota, OV, ordem, motivo ou usuário…" label="Buscar informe rejeitado">
                <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#workedRejectedMultiSearch" title="Buscar vários registros de uma vez" aria-label="Buscar vários registros">
                    <i class="ri-file-copy-line" aria-hidden="true"></i>
                </button>
            </x-ops.search>

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

    <section class="mp-panel" aria-label="Informes rejeitados">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Aguardando reinforme</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'informe rejeitado' : 'informes rejeitados' }}@if ($hasCriteria) com os filtros atuais @endif</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="8" />

        <div wire:loading.delay.class="d-none">
            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-arrow-go-back-line"
                    :title="$hasCriteria ? 'Nenhum informe rejeitado com esses filtros' : 'Nenhum informe rejeitado por aqui'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais informes.' : 'Quando um informe for devolvido para correção, ele aparece aqui.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 110px">
                            <col style="width: 120px">
                            <col style="width: 110px">
                            <col style="width: 100px">
                            <col style="width: 130px">
                            <col>
                            <col style="width: 160px">
                            <col style="width: 130px">
                            <col style="width: 110px">
                            <col style="width: 160px">
                            <col style="width: 130px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Situação</th>
                                <th>Nota/OV</th>
                                <th>Ordens</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th>Motivo</th>
                                <th>Devolvido por</th>
                                <th>Data devolução</th>
                                <th>Tempo</th>
                                <th>Empreiteira</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                @php $latestReturn = $list->LatestReturnwork; @endphp
                                <tr wire:key="ret-{{ $list->id }}">
                                    <td><x-ops.badge tone="amber" icon="ri-arrow-go-back-line" label="Rejeitado" hint="Devolvido para correção" /></td>
                                    <td><x-ops.note :note="$list->Note->note" /></td>
                                    <td>
                                        @forelse ($list->Orders as $order)
                                            <div>{{ $order->ordem }}</div>
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td>{{ $list->Note->rubrica ?: '—' }}</td>
                                    <td>{{ $list->Note->lexp ?: '—' }}</td>
                                    <td>
                                        @if ($latestReturn?->category)
                                            <x-ops.badge tone="red" :label="$latestReturn->category" hint="Motivo da última devolução" />
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $latestReturn?->User?->name ?: '—' }}</td>
                                    <td><x-ops.date :date="$latestReturn?->created_at" :time="true" hint="Quando o informe foi devolvido" /></td>
                                    <td>
                                        @if ($latestReturn?->created_at)
                                            <x-ops.badge tone="blue" :label="$latestReturn->created_at->diffForHumans(null, true)" hint="Tempo desde a devolução" />
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $list->Company?->name ?: '—' }}</td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-outline-primary btn-sm ops-action" wire:click="openRejectDetails({{ $list->id }})"><i class="ri-information-line" aria-hidden="true"></i> Detalhes</button>
                                            @if ($canReinform)
                                                <button type="button" class="btn btn-primary btn-sm ops-action" wire:click="reinform({{ $list->id }})"><i class="ri-play-circle-line" aria-hidden="true"></i> Reinformar</button>
                                            @endif
                                        </div>
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

    {{-- Detalhes da rejeição: histórico das devoluções do informe. --}}
    @if ($detailOpen)
        <div class="modal fade show d-block ops-modal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="rejectDetailsTitle">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content closure-dash ops-list">
                    <div class="modal-header">
                        <div>
                            <span class="modal-eyebrow">Detalhes da rejeição</span>
                            <h5 class="modal-title" id="rejectDetailsTitle">Nota/OV {{ $selectedRejectedNote ?: '—' }}</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-auto" wire:click="closeRejectDetails" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        @if ($selectedRejectedCompany)
                            <p class="ops-modal-note mb-3"><strong>{{ $selectedRejectedCompany }}</strong></p>
                        @endif

                        @if ($detailCurrent)
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <x-ops.badge tone="muted" :label="'Item ' . ($selectedReturnworkIndex + 1) . ' de ' . $detailTotal" />
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="previousRejectDetail" @disabled($selectedReturnworkIndex === 0)><i class="ri-arrow-left-s-line" aria-hidden="true"></i> Anterior</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="nextRejectDetail" @disabled($selectedReturnworkIndex === $detailTotal - 1)>Próxima <i class="ri-arrow-right-s-line" aria-hidden="true"></i></button>
                                </div>
                            </div>

                            @if ($detailTotal > 1)
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    @foreach ($selectedReturnworks as $index => $returnItem)
                                        <button type="button" class="btn btn-sm {{ $selectedReturnworkIndex === $index ? 'btn-primary' : 'btn-outline-secondary' }}" wire:click="goToRejectDetail({{ $index }})" title="Ir para a rejeição {{ $index + 1 }}{{ $detailOfficialIndex === $index ? ' (vigente)' : '' }}">{{ $index + 1 }}{{ $detailOfficialIndex === $index ? ' *' : '' }}</button>
                                    @endforeach
                                </div>
                            @endif

                            <div class="ops-modal-card">
                                <div class="ops-modal-meta">
                                    <x-ops.badge :tone="$detailIsOfficial ? 'green' : 'muted'" :label="$detailIsOfficial ? 'Vigente' : 'Histórico'" />
                                    <span>{{ $detailCurrent['user_name'] ?: 'Usuário não informado' }}</span>
                                    <span>{{ $detailCurrent['created_at'] ?: '—' }}@if (!empty($detailCurrent['created_human'])) · {{ $detailCurrent['created_human'] }}@endif</span>
                                </div>
                                <div class="fw-semibold small mb-1">Motivo</div>
                                <div class="mb-3"><x-ops.badge tone="red" :label="$detailCurrent['category'] ?: 'Não informado'" /></div>
                                <div class="fw-semibold small mb-1">Descrição</div>
                                <div class="ops-modal-note mb-0">{{ $detailCurrent['text_obs'] ?: 'Sem descrição.' }}</div>
                            </div>
                        @else
                            <p class="ops-modal-note mb-0">Este informe não possui histórico de rejeições detalhado.</p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-dash" wire:click="closeRejectDetails">Fechar</button>
                        @if ($canReinform)
                            <button type="button" class="btn btn-primary btn-dash" wire:click="reinform({{ (int) $selectedRejectedWorkReportId }})"><i class="ri-play-circle-line" aria-hidden="true"></i> Reinformar</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    <div class="modal fade ops-modal" id="workedRejectedMultiSearch" tabindex="-1" aria-labelledby="workedRejectedMultiSearchTitle" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="workedRejectedMultiSearchTitle">Buscar vários registros</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole um registro por linha, ou separe por vírgula, ponto e vírgula ou espaço.</p>
                    <label for="workedRejectedMultiSearchText" class="form-label text-uppercase">Notas, OVs, ordens, motivos ou usuários</label>
                    <textarea id="workedRejectedMultiSearchText" class="form-control" rows="8" wire:model.defer="multiSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="applyMultiSearch" data-bs-dismiss="modal"><i class="ri-search-line" aria-hidden="true"></i> Buscar</button>
                </div>
            </div>
        </div>
    </div>
</div>
