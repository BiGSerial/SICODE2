@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="row mb-3 justify-content-end">
        <div class="col-1">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm  border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>

        <div class="col-2">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="text"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                        class="ri-checkbox-multiple-blank-line"></i></button>
            </div>
        </div>

        <div class="col-md-9 d-flex mb-3 justify-content-end py-4">
            <label for="search" class="form-label"> </label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="1">
                <label class="form-check-label" for="inlineRadio1">Nota</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="2">
                <label class="form-check-label" for="inlineRadio1">OV</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="typeNote" wire:model="typeNote" value="">
                <label class="form-check-label" for="inlineRadio1">Ambos</label>
            </div>

            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'publication_acc', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'publication_acc', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'publication_acc', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'publication_acc', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'publication_acc'], key('removeAll'))
        </div>



    </div>


    <nav>
        <div class="nav nav-tabs" id="nav-tab" role="tablist">
            <button class="nav-link active" id="nav-production-tab" data-bs-toggle="tab" data-bs-target="#my_production"
                type="button" role="tab" aria-controls="nav-home" aria-selected="true"
                wire:click.prevent="$emit('refresh_accomany')">Produção</button>
            <button class="nav-link" id="nav-transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer"
                type="button" role="tab" aria-controls="nav-profile" aria-selected="false"
                wire:click.prevent="$emit('refresh_translist')">Transferências @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))</button>

        </div>
    </nav>

    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="my_production" role="tabpanel" aria-labelledby="nav-home-tab"
            tabindex="0">
            @if ($lists->count())
                <div class="row">
                    <div class="col-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-6 d-flex justify-content-end align-middle">
                        <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                            {{ $lists->lastItem() }}
                            de {{ $lists->total() }}
                            registros.</span>
                    </div>
                </div>
            @endif
            <dic class="card">

                @if (!$lists->count())
                    <div class="card-body">
                        <h4 class="text-center">VOCÊ NAO TEM TAREFA ATRIBUÍDA
                            <strong>{{ mb_strtoupper($service->service) }}</strong>
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                @else
                    <h4 class="card-header fw-bold text-bg-danger">ACOMPANHAMENTO -
                        {{ mb_strtoupper($service->service) }}
                    </h4>

                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-condensed table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col" class="fw-bold text-center">Note</th>
                                    <th scope="col" class="fw-bold text-center">Files</th>
                                    <th scope="col" class="fw-bold text-center">Ordens</th>
                                    <th scope="col" class="fw-bold text-center">Qtd Equipamentos</th>
                                    <th scope="col" class="fw-bold text-center">Empreiteira</th>
                                    <th scope="col" class="fw-bold text-center">Municipio</th>
                                    <th scope="col" class="fw-bold text-center">Descrição</th>
                                    <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
                                    <th scope="col" class="fw-bold text-center">Na Pilha</th>
                                    <th class="align-middle text-center">Dt Vencimento</th>
                                    <th scope="col" class="fw-bold text-center">Status</th>
                                    <th scope="col" class="fw-bold text-center"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lists->sortBy([['priority', 'desc'], ['Note.days_left', 'asc']]) as $list)
                                    @php
                                        $daysLeft = new DaysLeft($list->Note);
                                        $formBlock =
                                            $list->Note->WorkForm && $list->Note->WorkForm->rejected
                                                ? $list->Note->WorkForm->rejected
                                                : false;
                                    @endphp
                                    <tr wire:key="work-{{ $list->id }}"
                                        wire:dblclick="$emitTo('btzero.view.compare-form', 'showCompareForm', {{ $list->Note }})"
                                        class="align-middle text-center align-middle @if ($list->block) table-primary @elseif ($list->priority) table-danger @elseif ($formBlock) table-warning text-danger  @elseif (!$list->Note->WorkForm && $list->Note->RamalForm) table-warning @elseif ($list->Note->WorkForm && $list->Note->RamalForm) table-success @endif">
                                        <td
                                            class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
                                            {{ $list->Note->note }}
                                            <span class="copy-text" data-value="{{ $list->Note->note }}"
                                                style="cursor: pointer;" tabindex="0" data-bs-toggle="popover"
                                                data-bs-trigger="hover focus" data-bs-placement="top"
                                                data-bs-content="Copiar Número da Nota"> <i
                                                    class="ri-file-copy-line"></i></span>

                                            @if ($list->priority)
                                                <i class="ri-alert-fill align-middle"
                                                    wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                    style="cursor: pointer;" tabindex="0" data-bs-toggle="popover"
                                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                                    data-bs-title="Exibir Prioridade"
                                                    data-bs-content="Clique para visualizar a informação da prioridade desta nota/ov."></i>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                            <x-files.select-download-list :files='$list->Note->Files' />

                                        </td>
                                        <td class="fw-light text-center align-middle">
                                            @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count())
                                                @foreach ($list->Note->WorkForm->Orders as $order)
                                                    <p class="my-0 py-0">{{ $order->ordem }}</p>
                                                @endforeach
                                            @elseif (isset($list->Note->RamalForm) && $list->Note->RamalForm->Orders->count())
                                                @foreach ($list->Note->RamalForm->Orders as $order)
                                                    <p class="my-0 py-0">{{ $order->ordem }}</p>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td class="fw-light text-center align-middle">

                                            @if (isset($list->Note->WorkForm))
                                                <span
                                                    class="badge text-bg-dark">{{ $list->Note->WorkForm->Equipment->count() }}</span>
                                            @elseif(isset($list->Note->RamalForm))
                                                <span
                                                    class="badge text-bg-dark">{{ $list->Note->RamalForm->BtzeroEquipment->isNotEmpty() ? $list->Note->RamalForm->BtzeroEquipment->count() : '' }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-light text-center align-middle">

                                            @if (isset($list->Note->WorkForm))
                                                {{ isset($list->Note->WorkForm) && $list->Note->WorkForm->Company ? $list->Note->WorkForm->Company->name : '---' }}
                                            @elseif(isset($list->Note->RamalForm))
                                                {{ isset($list->Note->RamalForm) && $list->Note->RamalForm->Company ? $list->Note->RamalForm->Company->name : '---' }}
                                            @endif
                                        </td>
                                        <td class="fw-light text-center align-middle">{{ $list->Note->lexp }}</td>
                                        <td class="fw-light text-center align-middle">{{ $list->Note->material }}</td>
                                        <td class="fw-light text-center align-middle">
                                            {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                                        </td>
                                        <td class="fw-light text-center align-middle">
                                            {{ isset($list->Note->WorkForm) ? Carbon::now()->diffInDays($list->Note->WorkForm->informed_at) : '---' }}
                                        </td>
                                        @php
                                            $daysLeft = new DaysLeft($list->Note);
                                            $prazoClass = '';

                                            if ($daysLeft->getDaysLeft() < 0) {
                                                $prazoClass = 'text-bg-danger';
                                            } elseif ($daysLeft->getDaysLeft() > 15) {
                                                $prazoClass = 'text-bg-success';
                                            } else {
                                                $prazoClass = 'text-bg-warning';
                                            }
                                        @endphp

                                        <!-- Prioridade de estilo da célula 'Prazo Restante' -->
                                        <td scope="col" class="text-center {{ $prazoClass }}"
                                            style="background-color: inherit;">
                                            {{ $daysLeft->getLastDate() }}
                                        </td>

                                        <td class="fw-light text-center">

                                            @if ($formBlock)
                                                <span class="badge text-bg-warning text-wrap p-1">INFORME EM
                                                    REVISÃO</span>
                                            @else
                                                <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                                    wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                                    style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold fs-5">

                                            @if (
                                                !$list->block &&
                                                    !$this->blockWaiting($list->status) &&
                                                    !$formBlock &&
                                                    !($list->status == 28 && !$list->Note->WorkForm))
                                                @if (!$list->completed)
                                                    <span class="d-inline-block" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                        data-bs-title="Iniciar.">
                                                        {{-- <i class="ri-play-circle-line m-0 align-middle text-success"
                                                            style="cursor: pointer;"
                                                            wire:click.prevent="getAnalise({{ $list->id }}, {{ $list->Note->id }})"></i> --}}
                                                        <i class="ri-play-circle-line m-0 align-middle text-success"
                                                            style="cursor: pointer;"
                                                            wire:click.prevent="$emitTo('services.publication.forms.jobform', 'showProduction', {{ $list }})"></i>
                                                    </span>
                                                    <span class="d-inline-block" data-bs-toggle="tooltip"
                                                        data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                        data-bs-title="Transferir.">
                                                        <i class="ri-exchange-fill m-0 align-middle text-primary"
                                                            style="cursor: pointer;" {{-- data-bs-toggle="modal" data-bs-target="#analise_form" --}}
                                                            wire:click.prevent="goTransferProd({{ $list->id }})"></i>
                                                    </span>
                                                @endif
                                            @endif

                                            @if (!$formBlock && $list->Note->WorkForm)
                                                <span class="d-inline-block" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                    data-bs-title="Devolver Informe">
                                                    <i class="ri-delete-back-2-fill m-0 align-middle text-primary text-danger"
                                                        style="cursor: pointer;" {{-- data-bs-toggle="modal" data-bs-target="#analise_form" --}}
                                                        wire:click.prevent="$emitTo('production.return.return-work', 'toReturn', {{ $list }})"></i>
                                                </span>
                                            @endif
                                        </td>


                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @endif


            </dic>
            @if ($lists->count())
                <div class="row">
                    <div class="col-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-6 d-flex justify-content-end align-middle">
                        <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                            {{ $lists->lastItem() }}
                            de {{ $lists->total() }}
                            registros.</span>
                    </div>
                </div>
            @endif
        </div>


        <div class="tab-pane fade" id="transfer" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
            @livewire('components.transprod.translist', ['service' => $service->id])
        </div>
    </div>


    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="analise_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header text-bg-success">
                    <h1 class="modal-title fs-5 text-center" id="staticBackdropLabel">
                        {{ mb_strtoupper($service->service) }}
                    </h1>
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
                </div>
                <div class="modal-body">
                    @livewire('services.publication.forms.analise', key('analise-form'))
                </div>
                {{-- <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click.prevent="$emit('analise_clean')">Close</button>
                    <button type="button" class="btn btn-primary">Understood</button>
                </div> --}}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="pause_note" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content h-100">
                <div class="modal-header text-bg-warning">
                    <h1 class="modal-title fs-5 text-center" id="staticBackdropLabel">
                        PARAR {{ mb_strtoupper($service->service) }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('components.pausenote.pausenote')
                </div>
                {{-- <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click.prevent="$emit('analise_clean')">Close</button>
                    <button type="button" class="btn btn-primary">Understood</button>
                </div> --}}
            </div>
        </div>
    </div>

    {{-- MODAL COMPLEMENTS TRANSFER NOTE --}}
    @livewire('components.transprod.transprod', key('Transfer_production'))
    @livewire('partner.show.show-work-form', key('WorkFormCompany'))
    @livewire('services.publication.forms.jobform', key('production'))
    @livewire('production.return.return-work', key('returnWorkfomr'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('btzero.view.compare-form', key('compare_form'))
    {{-- <div wire:init="checkOpen"></div> --}}

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        Livewire.emitTo('services.publication.accompany.main', 'checkOpen');

    });
</script>


@push('script')
    <script>
        const copyTextCells = document.querySelectorAll('.copy-text');

        copyTextCells.forEach(cell => {
            cell.addEventListener('click', () => {
                const value = cell.getAttribute('data-value');
                copyToClipboard(value);
                livewire.emit('getCopy',
                    `Valor "${value}" copiado para a área de transferência.`);
                // alert(`Valor "${value}" copiado para a área de transferência.`);
            });
        });

        function copyToClipboard(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }

        window.addEventListener("showModal2", function(e) {
            alert('Funciona')
            const myModal = new bootstrap.Modal(document.getElementById(e.detail.id))
            myModal.show();
        })
    </script>
@endpush
