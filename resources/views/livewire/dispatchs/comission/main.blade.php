@php
    $partner = $partner ?? false;
    $statusCodes = $service->Status->count()
        ? $service->Status->where('exclusion', false)->unique('value')->pluck('value')->implode(' · ')
        : null;
    $hasCriteria = $this->hasListFilters() || filled($search) || filled($note_type) || $not_assigned || !empty($multiSearch);
    $dueHint = 'Prazo real: já desconta o tempo em status que não conta. Verde com 10 dias ou mais para vencer, âmbar de 6 a 9, vermelho com menos de 6 ou vencido.';
@endphp

{{-- Lista operacional: padrão em docs/PADRAO_LISTAS_OPERACIONAIS.md. Hero → ferramentas (busca, tipo, atalhos, filtros) → painel com a tabela. --}}
<div class="closure-dash my-panel ops-list" x-data="opsSelection()">

    <x-ops.hero
        :eyebrow="($partner ? 'Parceira' : 'Despacho') . ' · ' . $service->service"
        :title="'Lista para ' . mb_strtolower($service->service)"
        subtitle="Notas e OVs prontas para atribuição. Marque as linhas, escolha o responsável e acompanhe os prazos pela cor."
        :updated-at="$update ? $last_update : null">
        <div class="d-flex flex-wrap gap-2 justify-content-end">
            <x-ops.legend />
            <button type="button" class="btn btn-light btn-sm btn-dash" data-bs-toggle="modal" data-bs-target="#add_mass_dds" title="Relacionar números de DD às notas em massa">
                <i class="ri-checkbox-multiple-fill" aria-hidden="true"></i> Atribuir DD em massa
            </button>
        </div>
    </x-ops.hero>

    <section class="ops-toolbar" aria-label="Filtros da lista">
        <div class="ops-toolbar-row">
            <x-ops.search wire:model.debounce.500ms="search" placeholder="Buscar nota, material, pedido ou grupo" label="Buscar nota, material, pedido ou grupo">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#buscar_multi" title="Buscar várias notas de uma vez" aria-label="Buscar várias notas de uma vez">
                    <i class="ri-list-check-2" aria-hidden="true"></i>
                </button>
            </x-ops.search>

            <x-ops.segmented name="comissionNoteType" label="Tipo de nota" wire:model="note_type" :options="['1' => 'Nota', '2' => 'OV', '' => 'Ambos']" />

            <x-ops.toggle label="Só livres" icon="ri-user-unfollow-line" :on="$not_assigned" wire:click.prevent="filterStatus" title="Mostra só o que ainda não foi atribuído a ninguém" />

            <span class="ops-toolbar-spacer"></span>

            @if ($hasCriteria)
                <button type="button" class="ops-chips-clear" wire:click="clearAllCriteria"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar tudo</button>
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

    <section class="mp-panel" aria-label="Lista para {{ $service->service }}">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Lista para {{ mb_strtolower($service->service) }}</h2>
                <div class="mp-panel-sub">
                    {{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'registro' : 'registros' }}@if ($statusCodes) · status {{ $statusCodes }}@endif
                </div>
            </div>
            <span class="mp-pill" wire:loading.delay>Atualizando…</span>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="min(10, max(4, $rows->count()))" :cols="8" />

        @if ($rows->isEmpty())
            <div class="mp-panel-body">
                <x-closure.empty-state
                    :title="$hasCriteria ? 'Nenhum resultado para os filtros escolhidos' : 'Sem notas em ' . $service->service . ' no momento'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais notas.' : 'Quando houver notas liberadas, elas aparecem aqui.'"
                    icon="ri-inbox-line" />
                @if ($hasCriteria)
                    <div class="text-center mt-2"><button type="button" class="btn btn-outline-primary btn-sm btn-dash" wire:click="clearAllCriteria">Limpar filtros</button></div>
                @endif
            </div>
        @else
            <div class="ops-table-wrap" wire:loading.delay.class="d-none">
                <table class="table ops-table">
                    <colgroup>
                        @foreach ([36, 112, 84, 64, 92, 100, 100, 120, 64, 64, 64, 64, 100, 100, 100, 92, 112] as $w)
                            <col style="width: {{ $w }}px">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th><input class="form-check-input" type="checkbox" wire:click="setSelectAll" @checked($selectall) aria-label="Selecionar todas as linhas da página"></th>
                            <th>Nota</th>
                            <th>DD</th>
                            <th>MMGD</th>
                            <th>Criado em</th>
                            <th>Pedido</th>
                            <th>Rubrica</th>
                            <th>Município</th>
                            <th>Grp1</th>
                            <th>Grp2</th>
                            <th>Grp4</th>
                            <th>Grp5</th>
                            <th>Levantamentos</th>
                            <th>Status</th>
                            <th>Prazo real</th>
                            <th>Situação</th>
                            <th><span class="visually-hidden">Ação</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="note-{{ $row['key'] }}" @class(['ops-row-' . $row['state'] => $row['state'] !== 'free'])>
                                <td>
                                    <input class="form-check-input" type="checkbox" data-ops-row value="{{ $row['key'] }}" wire:model.defer="selected" @disabled(!$row['canDispatch']) aria-label="Selecionar {{ $row['note'] }}" @change="recount()">
                                </td>

                                <td>
                                    <x-ops.note :note="$row['note']">
                                        @if (isset($legalNoteIds[$row['noteId']]))
                                            <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-comission-main-' . $row['key']" />
                                        @endif
                                    </x-ops.note>
                                </td>

                                <td>
                                    @if ($row['dd'])
                                        <x-ops.badge mono tone="blue" :label="$row['dd']" hint="DD relacionada a esta nota, ainda sem atividade" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($row['mmgd'])
                                        <x-ops.badge sm tone="teal" label="MMGD" hint="Micro e minigeração distribuída" />
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td><x-ops.date :date="$row['createdAt']" hint="Data de criação da nota" /></td>

                                <td>{{ $row['order'] ?: '—' }}</td>
                                <td>{{ $row['rubrica'] ?: '—' }}</td>

                                <td>
                                    @if ($row['city'])
                                        {{ $row['city'] }}
                                    @else
                                        <button type="button" class="btn btn-outline-secondary btn-sm ops-action" wire:click.prevent="$emit('editMunicipio', '{{ $row['noteId'] }}')" title="Informar o município que falta nesta nota">
                                            <i class="ri-edit-2-line" aria-hidden="true"></i> Editar
                                        </button>
                                    @endif
                                </td>

                                <td>{{ $row['group1'] ?: '—' }}</td>
                                <td>{{ $row['group2'] ?: '—' }}</td>
                                <td>{{ $row['group4'] ?: '—' }}</td>
                                <td>{{ $row['group5'] ?: '—' }}</td>

                                <td>
                                    @if ($row['surveyCount'])
                                        <div class="ops-cell-stack is-center">
                                            <x-ops.badge sm tone="muted" :label="$row['surveyCount']" hint="Quantas vezes esta nota/OV já passou por este status e quem a encerrou por último neste serviço" />
                                            @if ($row['surveyUser'])
                                                <span class="ops-sub">{{ $row['surveyUser'] }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="ops-empty">—</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="ops-cell-stack is-center">
                                        <span>{{ $row['status'] ?: '—' }}</span>
                                        @if ($row['center'])
                                            <span class="ops-sub">{{ $row['center'] }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td><x-ops.date :date="$row['dueAt']" :tone="$row['dueTone']" :sub="$row['dueSub']" :hint="$dueHint . ' Já passaram ' . $row['elapsedDays'] . ' de 30 dias.'" /></td>

                                <td><x-ops.badge sm :tone="$row['sap']['tone']" :label="$row['sap']['label']" :hint="$row['sap']['hint']" /></td>

                                <td>
                                    @can('operator')
                                        @if ($row['canDispatch'])
                                            <button type="button" class="btn btn-primary btn-sm ops-action" wire:click.prevent="get_single_note({{ $row['noteId'] }})" title="Despachar esta Nota/OV">
                                                <i class="ri-user-add-line" aria-hidden="true"></i> Despachar
                                            </button>
                                        @else
                                            <x-ops.badge :tone="\App\Support\Ops\RowState::tone($row['state'])" :label="$row['company']" :hint="\App\Support\Ops\RowState::all()[$row['state']]['hint']" icon="ri-user-line" />
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-ops.pager :paginator="$lists" :per-page="$perPage" />
    </section>

    <div wire:ignore>
        <x-ops.selection-bar>
            <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="go_att_mass"><i class="ri-user-add-line" aria-hidden="true"></i> Atribuir selecionadas</button>
            <button type="button" class="btn btn-outline-light btn-sm btn-dash" wire:click.prevent="export_excel"><i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar selecionadas</button>
        </x-ops.selection-bar>
    </div>

    {{-- MODAIS --}}
    <div wire:ignore.self class="modal fade ops-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Busca em massa</span>
                        <h5 class="modal-title" id="buscarMultiLabel">{{ $service->service }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula.</p>
                    <label class="form-label fw-semibold text-uppercase" for="advanceSearch">Notas</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" rows="8" wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click="buscarMulti"><i class="ri-search-line" aria-hidden="true"></i> Aplicar busca</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="add_mass_dds" tabindex="-1" aria-labelledby="addMassDdsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Atribuir DD em massa</span>
                        <h5 class="modal-title" id="addMassDdsLabel">{{ $service->service }}</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar" wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <p class="ops-modal-note">Uma linha por nota, com o número da OV/Nota e o da DD separados por espaço, tabulação, vírgula ou ponto e vírgula. Exemplo: 4001123232 14034330</p>
                    <label class="form-label fw-semibold text-uppercase" for="enterDd">Relacionar DD</label>
                    <textarea class="form-control" id="enterDd" rows="10" placeholder="<número OV/NOTA> <número DD>" wire:model.defer="enter_dd"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-dash" data-bs-dismiss="modal" wire:click.prevent="closeall">Fechar</button>
                    <button type="button" class="btn btn-primary btn-dash" wire:click.prevent="mass_modal"><i class="ri-links-line" aria-hidden="true"></i> Atribuir</button>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-' . $service->uuid))
</div>
