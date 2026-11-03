@php
    use App\Services\Partner\ViabilityListRowPresenter as Row;

    $canExport = $this->can('viability.export');
@endphp

{{-- HISTÓRICO DE VIABILIDADE (lista de controle da parceira). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Viabilidade" title="Histórico de viabilidade"
        subtitle="Todas as viabilidades da sua empresa, de qualquer situação: filtre por período, tipo e localidade e consulte a justificativa de cada uma.">
        @if ($canExport)
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
            </button>
        @endif
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros do histórico">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar por nota ou ordem…" label="Buscar">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#partnerViabMultiNotes" title="Buscar várias notas ou ordens de uma vez">
                    <i class="ri-checkbox-multiple-blank-line" aria-hidden="true"></i> Várias
                </button>
            </x-ops.search>

            <x-ops.segmented name="partnerHistType" label="Tipo de nota" wire:model="typeNote" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar filtros</button>
            @endif
        </div>

        <div class="ops-toolbar-row">
            <span class="ops-toolbar-label">Período</span>
            <select class="form-select form-select-sm w-auto" wire:model="dateBy" aria-label="Data usada no período">
                <option value="sended_at">Recebido</option>
                <option value="returned_at">Viabilizado</option>
                <option value="completed_at">Completado</option>
            </select>
            <input type="month" class="form-control form-control-sm w-auto" wire:model="month" min="2023-06" max="{{ date('Y-m') }}" aria-label="Mês e ano">
            <input type="date" class="form-control form-control-sm w-auto" wire:model="date_in" max="{{ $date_out ?: '' }}" aria-label="Data inicial">
            <input type="date" class="form-control form-control-sm w-auto" wire:model="date_out" min="{{ $date_in ?: '' }}" aria-label="Data final">
            @if ($multinotas)
                <button type="button" class="ops-chip" wire:click="$set('multinotas', [])" title="Remover a busca por várias notas">
                    {{ count($multinotas) }} notas/ordens na busca <i class="ri-close-line" aria-hidden="true"></i>
                </button>
            @endif
        </div>

        @if ($this->listFilterPayload()['defs'])
            <div class="ops-toolbar-row">
                <span class="ops-toolbar-label">Filtros</span>
                <x-ops.filters :payload="$this->listFilterPayload()" />
            </div>
        @endif

        @if ($this->hasListFilters())
            <div class="ops-toolbar-row">
                <x-ops.chips :chips="$this->listFilterChips()" />
            </div>
        @endif
    </section>

    <section class="mp-panel" aria-label="Histórico de viabilidade">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Histórico de viabilidade</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'viabilidade' : 'viabilidades' }}@if ($hasCriteria) com os filtros atuais @endif · duplo clique na linha abre os detalhes</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="10" />

        <div wire:loading.delay.class="d-none">
            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-history-line"
                    :title="$hasCriteria ? 'Nenhuma viabilidade com esses filtros' : 'Nenhuma atividade encontrada'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais viabilidades.' : 'As viabilidades da sua empresa aparecem aqui conforme forem recebidas.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 110px">
                            <col style="width: 70px">
                            <col style="width: 110px">
                            <col style="width: 100px">
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col style="width: 110px">
                            <col>
                            <col style="width: 140px">
                            <col style="width: 150px">
                            <col style="width: 16%">
                            <col style="width: 70px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nota/OV</th>
                                <th class="text-center">Arquivos</th>
                                <th>Ordem</th>
                                <th>Contratado</th>
                                <th>Recebido</th>
                                <th>Viabilizado</th>
                                <th>Completado em</th>
                                <th>Rubrica</th>
                                <th>Município</th>
                                <th>Justificativa tácita</th>
                                <th>Status</th>
                                <th>Empreiteira</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $viability)
                                @php
                                    $status = Row::status($viability);
                                    $orders = Row::orders($viability);
                                    $tacit  = Row::tacit($viability);
                                @endphp
                                <tr class="ops-clickable" wire:key="viability-{{ $viability->id }}"
                                    wire:dblclick="$emitTo('partner.actions.responserviab', 'getInfoResponse', {{ $viability->id }})"
                                    title="Duplo clique para mais opções">
                                    <td><x-ops.note :note="$viability->Note?->note" /></td>
                                    <td class="text-center">
                                        @if ($viability->Note?->Files?->isNotEmpty())
                                            <x-files.select-download-list :files="$viability->Note->Files" :tacit-file-ids="$tacitFileIds" />
                                        @endif
                                    </td>
                                    <td>
                                        @forelse ($orders as $order)
                                            <div>{{ $order }}</div>
                                        @empty
                                            <span class="ops-empty">—</span>
                                        @endforelse
                                    </td>
                                    <td><x-ops.badge sm :tone="$viability->hired ? 'green' : 'muted'" :label="$viability->hired ? 'Sim' : 'Não'" hint="Se a obra desta viabilidade já foi contratada" /></td>
                                    <td><x-ops.date :date="$viability->sended_at" hint="Data em que a viabilidade chegou à parceira" /></td>
                                    <td><x-ops.date :date="$viability->returned_at" hint="Data em que a parceira devolveu a viabilidade" /></td>
                                    <td><x-ops.date :date="$viability->completed_at" hint="Data em que a viabilidade foi concluída" /></td>
                                    <td>{{ $viability->Note?->rubrica ?: '—' }}</td>
                                    <td>{{ $viability->Note?->lexp ?: '—' }}</td>
                                    <td>
                                        @if ($tacit && $tacit['open'])
                                            <x-ops.badge class="ops-clickable" :tone="$tacit['tone']" :label="$tacit['label']" :hint="$tacit['hint']"
                                                wire:click="$emitTo('partner.show.tacitjusfy-show', 'getTacitInfo', '{{ $viability->id }}')" />
                                        @elseif ($tacit)
                                            <x-ops.badge :tone="$tacit['tone']" :label="$tacit['label']" :hint="$tacit['hint']" />
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td><x-ops.badge :tone="$status['tone']" :label="$status['label']" /></td>
                                    <td>{{ $viability->Company?->name ?? '—' }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-outline-primary btn-sm ops-action" href="{{ route('pdf.checklist', ['id' => $viability->id]) }}" target="_blank" rel="noopener"
                                            title="Imprimir a Ficha Técnica de Viabilidade e Execução de Obras"><i class="ri-printer-line" aria-hidden="true"></i> Checklist</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-ops.pager :paginator="$lists" :per-page="$perPage" :sizes="[25, 50, 100, 250, 500]" />
            @endif
        </div>
    </section>

    {{-- Busca por várias notas ou ordens (cola direto do Excel). --}}
    <div wire:ignore.self class="modal fade ops-modal" id="partnerViabMultiNotes" tabindex="-1" aria-labelledby="partnerViabMultiNotesTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="partnerViabMultiNotesTitle">Várias notas ou ordens</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Informe um valor por linha, ou separe por vírgula ou ponto e vírgula. Colar direto do Excel também funciona.</p>
                    <label class="form-label fw-semibold text-uppercase small" for="partnerViabMultiNotesText">Notas ou ordens</label>
                    <textarea class="form-control" id="partnerViabMultiNotesText" rows="10" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMultinotas"><i class="ri-search-line" aria-hidden="true"></i> Buscar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Livewire Components --}}
    @livewire('partner.actions.responserviab', key('reesponser_modal_viab'))
    @livewire('partner.show.tacitjusfy-show', key('tacitjusfy-show'))
</div>
