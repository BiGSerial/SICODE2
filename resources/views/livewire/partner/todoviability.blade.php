@php
    use App\Services\Partner\ViabilityListRowPresenter as Row;

    $canExport  = $this->can('viability.export');
    $canRespond = $this->can('viability.respond');
    $canReturn  = $this->can('viability.return');
@endphp

{{-- VIABILIDADES A EXECUTAR (lista de controle da parceira). Padrão: docs/PADRAO_LISTAS_OPERACIONAIS.md §10. --}}
<div class="closure-dash my-panel ops-list ops-control">

    <x-ops.hero class="ops-hero--control" eyebrow="Portal da parceira · Viabilidade" title="Viabilidades a executar"
        subtitle="Viabilidades que chegaram à sua parceira: execute a vistoria, imprima o checklist e devolva os informes da obra.">
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
            <a href="{{ route('pdf.checklist', ['id' => 0]) }}" target="_blank" rel="noopener" class="btn btn-light btn-sm btn-dash"
                title="Abre para impressão a Ficha Técnica de Viabilidade e Execução de Obras (obrigatório anexar no Sicode ao retornar a viabilidade)">
                <i class="ri-printer-line" aria-hidden="true"></i> Checklist FTVEO (genérica)
            </a>
            @if ($canExport)
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click.prevent="export_excel" wire:loading.attr="disabled" wire:target="export_excel">
                    <i class="ri-file-excel-2-line" aria-hidden="true"></i> Exportar Excel
                </button>
            @endif
        </div>
    </x-ops.hero>

    @include('livewire.partner.partials.viability-toolbar', ['placeholder' => 'Buscar por nota ou ordem…'])

    <section class="mp-panel" aria-label="Viabilidades a executar">
        <div class="mp-panel-head">
            <div>
                <h2 class="mp-panel-title">Viabilidades a executar</h2>
                <div class="mp-panel-sub">{{ number_format($lists->total(), 0, ',', '.') }} {{ $lists->total() === 1 ? 'viabilidade' : 'viabilidades' }}@if ($hasCriteria) com os filtros atuais @endif · duplo clique na linha abre os detalhes</div>
            </div>
        </div>

        <x-ops.skeleton wire:loading.delay.class.remove="d-none" class="d-none" :rows="8" :cols="10" />

        <div wire:loading.delay.class="d-none">
            @if ($lists->isEmpty())
                <x-closure.empty-state icon="ri-checkbox-circle-line"
                    :title="$hasCriteria ? 'Nenhuma viabilidade com esses filtros' : 'Nenhuma atividade encontrada'"
                    :text="$hasCriteria ? 'Ajuste ou limpe os filtros para ver mais viabilidades.' : 'Quando uma viabilidade chegar à sua parceira, ela aparece aqui.'" />
            @else
                <div class="table-responsive">
                    <table class="table mp-table align-middle mb-0">
                        <colgroup>
                            <col style="width: 130px">
                            <col style="width: 110px">
                            <col style="width: 70px">
                            <col style="width: 110px">
                            <col style="width: 100px">
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col style="width: 120px">
                            <col style="width: 110px">
                            <col>
                            <col style="width: 110px">
                            <col style="width: 130px">
                            <col style="width: 150px">
                            <col style="width: 15%">
                            <col style="width: 90px">
                            <col style="width: 110px">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Nota/OV</th>
                                <th>Criticidade</th>
                                <th class="text-center">Arquivos</th>
                                <th>Ordem</th>
                                <th>Contratado</th>
                                <th>Recebido</th>
                                <th>Prazo viab.</th>
                                <th>Prazo obra</th>
                                <th>Rubrica</th>
                                <th>Material</th>
                                <th>Região</th>
                                <th>Município</th>
                                <th>Status</th>
                                <th>Empreiteira</th>
                                <th class="text-center">Em atividade</th>
                                <th><span class="visually-hidden">Ações</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $viability)
                                @php
                                    $note     = $viability->Note;
                                    $orders   = Row::orders($viability, false);
                                    $due      = Row::due($viability);
                                    $workDate = Row::workDate($viability);
                                    $status   = Row::status($viability);
                                    $blocked  = $viability->approved || $viability->rejected;
                                @endphp
                                <tr class="ops-clickable" wire:key="viability-{{ $viability->id }}"
                                    wire:dblclick="$emitTo('partner.actions.responserviab', 'getInfoResponse', {{ $viability->id }})">
                                    <td>
                                        <x-ops.note :note="$note->note">
                                            @if ($note->is45)
                                                <x-ops.badge sm tone="amber" icon="ri-fire-line" label="Expressa" hint="Nota expressa: prazo de execução de 45 dias" />
                                            @endif
                                        </x-ops.note>
                                    </td>
                                    <td class="fw-semibold">{{ $note->txpriority ?: 'Normal' }}</td>
                                    <td class="text-center">
                                        @if ($note->Files->isNotEmpty())
                                            <x-files.select-download-list :files="$note->Files" :tacit-file-ids="$tacitFileIds" />
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
                                    <td><x-ops.date :date="$due['date']" :tone="$due['tone']" :hint="$due['hint']" /></td>
                                    <td><x-ops.date :date="$workDate" hint="Data limite da obra, pela mensalização da nota ou vencimento da OV" /></td>
                                    <td>{{ $note->rubrica ?: '—' }}</td>
                                    <td>{{ $note->material ?: '—' }}</td>
                                    <td>{{ $note->City?->regiao ?: '—' }}</td>
                                    <td>{{ $note->lexp ?: '—' }}</td>
                                    <td><x-ops.badge :tone="$status['tone']" :label="$status['label']" /></td>
                                    <td>{{ $viability->Company?->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if ($canRespond)
                                            <input class="form-check-input" type="checkbox" wire:click.prevent="putInActivity({{ $viability->id }})"
                                                @checked($this->checkInActivity($viability)) aria-label="Marcar a nota {{ $note->note }} como em atividade">
                                        @else
                                            <span class="ops-empty">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown ops-row-actions">
                                            <button class="btn btn-outline-secondary btn-sm ops-row-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mais ações para a nota {{ $note->note }}"><i class="ri-more-2-fill" aria-hidden="true"></i></button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><button type="button" class="dropdown-item" wire:click="$emitTo('partner.actions.responserviab', 'getInfoResponse', {{ $viability->id }})"><i class="ri-eye-line text-primary align-middle"></i> Ver detalhes</button></li>
                                                <li><a class="dropdown-item" href="{{ route('pdf.checklist', ['id' => $viability->id]) }}" target="_blank" rel="noopener" title="Abre a Ficha Técnica de Viabilidade e Execução de Obras para impressão"><i class="ri-printer-line text-primary align-middle"></i> Imprimir checklist FTVEO</a></li>
                                                @if (!$blocked && $canReturn)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><button type="button" class="dropdown-item" wire:click="$emitTo('partner.forms.return-viability', 'openViability', '{{ $viability->id }}')" title="Entrega os informes da obra"><i class="ri-checkbox-circle-line text-success align-middle"></i> Encerrar atividade</button></li>
                                                @endif
                                            </ul>
                                        </div>
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

    {{-- Modais Livewire --}}
    @livewire('partner.actions.responserviab', key('responser_modal_viab'))
    @livewire('partner.forms.return-viability', key('responser_viab_form'))
</div>
