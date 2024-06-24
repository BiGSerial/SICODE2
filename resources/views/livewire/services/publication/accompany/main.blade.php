@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
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
                        {{ mb_strtoupper($service->service) }} - @if ($service->Status->count())
                            @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                ({{ $sts->value }})
                            @endforeach
                        @endif
                    </h4>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-condensed">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col" class="fw-bold">Note</th>
                                        <th scope="col" class="fw-bold">Ordens</th>
                                        <th scope="col" class="fw-bold">NumEquipamentos</th>
                                        <th scope="col" class="fw-bold">Empreiteira</th>
                                        <th scope="col" class="fw-bold">Municipio</th>
                                        <th scope="col" class="fw-bold">Descrição</th>
                                        <th scope="col" class="fw-bold">Dias Atribuido</th>
                                        <th scope="col" class="fw-bold">Na Pilha</th>
                                        <th scope="col" class="fw-bold">Status</th>
                                        <th scope="col" class="fw-bold"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lists->sortBy([['priority', 'desc'], ['Note.days_left', 'asc']]) as $list)
                                        <tr
                                            class="align-middle @if ($list->block) table-primary @endif">
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
                                                        style="cursor: pointer;" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="top" data-bs-title="Exibir Prioridade"
                                                        data-bs-content="Clique para visualizar a informação da prioridade desta nota/ov."></i>
                                                @endif
                                            </td>
                                            <td class="fw-light">
                                                {{ date('d/m/Y', strToTime($list->Note->dt_created)) }}</td>
                                            <td class="fw-light">{{ $list->Note->numPedido }}</td>
                                            <td class="fw-light">{{ $list->Note->rubrica }}</td>
                                            <td class="fw-light">{{ $list->Note->lexp }}</td>
                                            <td class="fw-light">{{ $list->Note->group1 }}</td>
                                            <td class="fw-light">{{ $list->Note->group2 }}</td>
                                            <td class="fw-light">{{ $list->Note->material }}</td>
                                            <td class="fw-light">
                                                {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
                                            </td>
                                            <td scope="col"
                                                class="text-center
                                        @if ($list->Note->days_left < 0) text-bg-secondary
                                        @elseif($list->Note->days_left >= 0 && $list->Note->days_left < 6)
                                        table-danger
                                        @elseif($list->Note->days_left >= 6 && $list->Note->days_left < 10)
                                            table-warning
                                        @else
                                            table-success @endif
                                    "
                                                tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                data-bs-placement="top" data-bs-title="Prazo Real"
                                                data-bs-content="
                                <p>Os prazos contados já foram expurgado os tempos em status não contabilizáveis.</p>
                                <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                                <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                                <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                                <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                                ">
                                                {{ 30 - $list->Note->days_left }}
                                            </td>
                                            <td class="fw-light">
                                                <span
                                                    class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
                                            </td>
                                            <td class="fw-bold fs-5">
                                                @if (!$list->block)
                                                    @if (!$list->completed)
                                                        <span class="d-inline-block" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="Iniciar.">
                                                            <i class="ri-play-circle-line m-0 align-middle text-success"
                                                                style="cursor: pointer;" {{-- data-bs-toggle="modal" data-bs-target="#analise_form" --}}
                                                                wire:click.prevent="getAnalise({{ $list->id }}, {{ $list->Note->id }})"></i>
                                                        </span>
                                                        <span class="d-inline-block" data-bs-toggle="tooltip"
                                                            data-bs-placement="top"
                                                            data-bs-custom-class="custom-tooltip"
                                                            data-bs-title="Transferir.">
                                                            <i class="ri-exchange-fill m-0 align-middle text-primary"
                                                                style="cursor: pointer;" {{-- data-bs-toggle="modal" data-bs-target="#analise_form" --}}
                                                                wire:click.prevent="goTransferProd({{ $list->id }})"></i>
                                                        </span>
                                                    @endif
                                                @endif
                                            </td>


                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
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
                    @livewire('services.analises.forms.analise', key('analise-form'))
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

    <div wire:init="checkOpen"></div>

</div>


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
