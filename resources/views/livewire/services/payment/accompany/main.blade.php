@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="row mb-3 justify-content-end">
        <div class="col-md-4 col-lg-4 col-xl-1">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm  border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>

        <div class="col-md-6 col-xl-2 ">
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

            @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'payments_acc', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'payments_acc', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'payments_acc', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'payments_acc', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'payments_acc', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'payments_acc'], key('removeAll'))
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
                    <div class="card-header  text-bg-danger">
                        <div class="row">
                            <div class="col">
                                <h4 class="fw-bold my-0">ACOMPANHAMENTO -
                                    {{ mb_strtoupper($service->service) }}
                                </h4>
                            </div>
                            <div class="col-3 d-flex justify-content-end">
                                <button class="btn btn-sm btn-warning me-2" wire:click.prevent='export_d5tolist'><i
                                        class="ri-file-text-line"></i> Exportar D5</button>
                                <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                        class="ri-file-excel-2-line"></i> Exportar</button>
                                <button class="btn btn-sm btn-secondary" onclick="cleanLocalStorage()"
                                    title="Limpar LocalStorage"><i class="ri-delete-bin-line"></i></button>
                            </div>
                        </div>
                    </div>


                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-condensed table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="align-middle text-center">Tipo</th>
                                    <th class="align-middle text-center">Nota</th>
                                    <th class="align-middle text-center">Files</th>
                                    <th class="align-middle text-center">Ordem</th>
                                    <th class="align-middle text-center">MOA</th>
                                    {{-- <th class="align-middle text-center">Status</th> --}}
                                    <th class="align-middle text-center">OP30</th>
                                    <th class="align-middle text-center">OP40</th>
                                    <th class="align-middle text-center">OP50</th>
                                    <th class="align-middle text-center">CentroTrab</th>
                                    <th class="align-middle text-center">Empresa</th>
                                    <th class="align-middle text-center">Município</th>
                                    <th class="align-middle text-center">Data Execução</th>
                                    <th class="align-middle text-center">Data Informe</th>
                                    <th class="align-middle text-center">Prazo Pagamento</th>
                                    <th class="align-middle text-center">Status</th>
                                    <th class="align-middle text-center"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $soma = 0;

                                    if (!function_exists('FiveStatus')) {
                                        function FiveStatus($list): object
                                        {
                                            $object = (object) [
                                                'exists' => false,
                                                'bgColor' => '',
                                                'message' => '',
                                            ];

                                            if ($five = $list->note->fiveNote) {
                                                if (!$five->is_supervisioned) {
                                                    $object->exists = true;
                                                    $object->bgColor = 'text-bg-primary';
                                                    $object->message = 'Gerar D5 e reter carta';
                                                } else {
                                                    $object->exists = true;
                                                    $object->bgColor = 'text-bg-success';
                                                    $object->message = 'D5 Fiscalizada Liberar carta';
                                                }
                                            }

                                            return (object) $object;
                                        }
                                    }
                                @endphp
                                @foreach ($lists as $list)
                                    @php
                                        $daysLeft = $this->deadline($list->Note);
                                        if ($list->partial) {
                                            $partial = $list->note->partials?->last();
                                        } else {
                                            $partial = null;
                                        }

                                        $five = FiveStatus($list);
                                        $adsForm = $list->Note->Adsform ?? $list->Note->WorkForm?->Adsform;
                                        $isTacitAds = (bool) ($adsForm?->tacit ?? false);
                                        $tacitDelivered = (bool) ($adsForm?->tacit_delivered_at ?? false);
                                    @endphp

                                    @if ($partial)
                                        <tr wire:key="work-{{ $list->id }}"
                                            wire:dblclick="$emitTo('partner.show.show-partial-info', 'show_form', {{ $partial }})"
                                            class="align-middle text-center align-middle @if ($list->block) table-primary @endif">
                                        @else
                                        <tr wire:key="work-{{ $list->id }}"
                                            wire:dblclick="$emitTo('partner.show.show-work-form', 'show_form', {{ $list->Note->WorkForm }})"
                                            class="align-middle text-center align-middle @if ($list->block) table-primary @endif">
                                    @endif
                                    <td
                                        class="align-middle @if ($list->partial) text-bg-warning
                                            @else
                                            text-bg-success @endif">
                                        {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                        @if ($list->partial)
                                            PARCIAL
                                        @else
                                            TOTAL
                                        @endif

                                    </td>
                                    <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">


                                        @if ($five->exists)
                                            <span class="badge {{ $five->bgColor }} fs-6" tabindex="0"
                                                data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                data-bs-placement="top" data-bs-title="Nota com D5"
                                                data-bs-content="{{ $five->message }}"
                                                wire:click.prevent="$emitTo('components.d5.d5details', 'openD5Details', {{ $list->Note->id }})"
                                                style="cursor: pointer;" z-index="0">
                                                <span class="fw-bold">D5</span>
                                                {{ $list->Note->note }}
                                            </span>
                                        @else
                                            {{ $list->Note->note }}
                                        @endif
                                        {{-- <span class="copy-text" data-value="{{ $list->Note->note }}"
                                            style="cursor: pointer;" tabindex="0" data-bs-toggle="popover"
                                            data-bs-trigger="hover focus" data-bs-placement="top"
                                            data-bs-content="Copiar Número da Nota"> <i
                                                class="ri-file-copy-line"></i></span> --}}

                                        @if ($list->priority)
                                            <i class="ri-alert-fill align-middle"
                                                wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                style="cursor: pointer;" tabindex="0" data-bs-toggle="popover"
                                                data-bs-trigger="hover focus" data-bs-placement="top"
                                                data-bs-title="Exibir Prioridade"
                                                data-bs-content="Clique para visualizar a informação da prioridade desta nota/ov."></i>
                                        @endif
                                        @if ($isTacitAds)
                                            <div class="mt-1">
                                                <span class="badge text-bg-dark">ADS TÁCITA</span>
                                                <span class="badge {{ $tacitDelivered ? 'text-bg-success' : 'text-bg-danger' }}">
                                                    {{ $tacitDelivered ? 'ENTREGUE' : 'NÃO ENTREGUE' }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                        <x-files.select-download-list :files='$list->Note->Files' />

                                    </td>
                                    <td class="fw-light text-center align-middle">
                                        @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count() && !$partial)
                                            @foreach ($list->Note->WorkForm->Orders as $order)
                                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @elseif ($partial)
                                            @foreach ($partial->Orders as $order)
                                                <p class="my-0 py-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-center align-middle fw-bold">
                                        @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count() && !$partial)
                                            @php
                                                $soma += $list->Note->WorkForm->Orders->sum('moaberto');
                                            @endphp
                                            <span class="my-0py-0">
                                                R$
                                                {{ number_format($list->Note->WorkForm->Orders->sum('moaberto'), 2, ',', '.') }}
                                            </span>
                                        @elseif ($partial && $partial?->Orders->isNotEmpty())
                                            @php
                                                $soma += $partial->value;
                                            @endphp
                                            R$ {{ number_format($partial->value, 2, ',', '.') }}
                                        @endif

                                    </td>
                                    {{--
                                        <td class="text-center align-middle">
                                            @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count())
                                                @foreach ($list->Note->WorkForm->Orders as $order)
                                                    <span class="my-0py-0">
                                                        {{ $order->statusSist }}
                                                    </span>
                                                @endforeach
                                            @endif

                                        </td> --}}

                                    <td class="text-center align-middle">
                                        @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count())
                                            @foreach ($list->Note->WorkForm->Orders as $order)
                                                <span class="my-0py-0">
                                                    {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0030')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0030')->first()->status)[0] : '---' }}
                                                </span>
                                            @endforeach
                                        @endif
                                    </td>

                                    <td class="text-center align-middle">
                                        @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count())
                                            @foreach ($list->Note->WorkForm->Orders as $order)
                                                <span class="my-0py-0">
                                                    {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0040')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0040')->first()->status)[0] : '---' }}
                                                </span>
                                            @endforeach
                                        @endif

                                    </td>
                                    <td class="text-center align-middle">
                                        @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count())
                                            @foreach ($list->Note->WorkForm->Orders as $order)
                                                <span class="my-0py-0">
                                                    {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0050')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0050')->first()->status)[0] : '---' }}
                                                </span>
                                            @endforeach
                                        @endif

                                    </td>
                                    <td class="text-center align-middle">
                                        @if (isset($list->Note->WorkForm) && $list->Note->WorkForm->Orders->count() && !$partial)
                                            @foreach ($list->Note->WorkForm->Orders as $order)
                                                <span class="my-0py-0">
                                                    {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                                                </span>
                                            @endforeach
                                        @elseif ($partial)
                                            @foreach ($partial->Orders as $order)
                                                <span class="my-0py-0">
                                                    {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                                                </span>
                                            @endforeach
                                        @endif

                                    </td>


                                    <td class="fw-light text-center">
                                        @if ($list->Note->WorkForm)
                                            {{ $list->Note->WorkForm ? $list->Note->WorkForm->Company->name : '---' }}
                                        @elseif ($partial)
                                            {{ $partial->Company->name }}
                                        @endif
                                    </td>

                                    <td class="fw-light text-center">{{ $list->Note->lexp }}</td>

                                    <td class="fw-light text-center">
                                        @if ($list->Note->WorkForm)
                                            {{ $list->Note->WorkForm ? date('d/m/Y', strToTime($list->Note->WorkForm->date)) : '---' }}
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="fw-light">
                                        @if ($list->Note->WorkForm)
                                            {{ $list->Note->WorkForm ? date('d/m/Y H:i:s', strToTime($list->Note->WorkForm->informed_at)) : '---' }}
                                        @elseif ($partial)
                                            {{ $partial->supervision_at->format('d/m/Y H:i:s') }}
                                        @endif

                                    </td>

                                    @php
                                        if ($partial) {
                                            $daysLeft = Carbon::parse($partial->supervision_at)
                                                ->addDays(5)
                                                ->startOfDay()
                                                ->diffInDays(Carbon::now()->startOfDay());
                                            $lastDate = $partial->supervision_at?->addDays(5)->format('d/m/Y');
                                        } else {
                                            $daysLeft = Carbon::parse($list->fimLancado)
                                                ->startOfDay()
                                                ->diffInDays(Carbon::now()->startOfDay());
                                            $lastDate = Carbon::parse($list->fimLancado)->format('d/m/Y');
                                        }
                                    @endphp
                                    <td scope="col"
                                        class="text-center text-center
                                    @if ($daysLeft <= 2) text-bg-success
                                 @elseif($daysLeft > 5)
                                     text-bg-danger
                                 @else
                                 text-bg-warning @endif
                                 "
                                        style="background-color: inherit;" tabindex="0" data-bs-toggle="popover"
                                        data-bs-trigger="hover focus" data-bs-placement="top"
                                        data-bs-title="Prazo Pagamento"
                                        data-bs-content="
                             <p>A Data Corresponde 40 Parcial</p>
                             <span class='fs-4 text-success'>&#9632;</span> <= 2 DIAS PARA VENCER <br>
                             <span class='fs-4 text-warning'>&#9632;</span> <= 5 DIAS PARA VENCER <br>
                             <span class='fs-4 text-danger'>&#9632;</span> > 5 DIAS VENCIDO <br>
                             {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                             ">
                                        {{ $lastDate }}
                                    </td>
                                    <td class="fw-light text-center">

                                        <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                            wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                            style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                    </td>
                                    <td class="fw-bold fs-5">
                                        @if (!$list->block && !$this->blockWaiting($list->status))
                                            @if (!$list->completed)
                                                <span class="d-inline-block" data-bs-toggle="tooltip"
                                                    data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                    data-bs-title="Iniciar.">
                                                    {{-- <i class="ri-play-circle-line m-0 align-middle text-success"
                                                            style="cursor: pointer;"
                                                            wire:click.prevent="getAnalise({{ $list->id }}, {{ $list->Note->id }})"></i> --}}
                                                    <i class="ri-play-circle-line m-0 align-middle text-success"
                                                        style="cursor: pointer;"
                                                        wire:click.prevent="$emitTo('services.payment.forms.jobform', 'showProduction', {{ $list }})"></i>
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

                                        @if ($list->partial && !$list->Note->WorkForm)
                                            <span class="d-inline-block" data-bs-toggle="tooltip"
                                                data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Devolver Informe">
                                                <i class="ri-delete-back-2-fill m-0 align-middle text-primary text-danger"
                                                    style="cursor: pointer;"
                                                    wire:click.prevent="$emitTo('production.return.reject-inform-partial', 'toReturn', {{ $list }})"></i>
                                            </span>
                                        @endif


                                    </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="fw-bold">R$ {{ number_format($soma, 2, ',', '.') }}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
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
    {{-- <div wire:ignore.self class="modal fade" id="analise_form" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content h-100">
                <div class="modal-header text-bg-success">
                    <h1 class="modal-title fs-5 text-center" id="staticBackdropLabel">
                        {{ mb_strtoupper($service->service) }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('services.publication.forms.analise', key('analise-form'))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click.prevent="$emit('analise_clean')">Close</button>
                    <button type="button" class="btn btn-primary">Understood</button>
                </div>
            </div>
        </div>
    </div> --}}

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


    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    Buscar Multi-Notas
                </div>
                <div>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                </div>
            </div>

        </div>

    </div>

    {{-- MODAL COMPLEMENTS TRANSFER NOTE --}}
    @livewire('components.transprod.transprod', key('Transfer_production'))
    @livewire('partner.show.show-work-form', key('WorkFormCompany'))
    @livewire('services.payment.forms.jobform', key('payment-form'))
    @livewire('components.status.show-status', key('show_status_note'))
    @livewire('partner.show.show-partial-info', key('show_partial_info'))
    @livewire('production.return.reject-inform-partial', key('reject_inform_partial'))
    @livewire('components.d5.d5details', key('d5_details'))


    {{-- <div wire:init="checkOpen"></div> --}}

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        Livewire.emitTo('services.payment.accompany.main', 'checkOpen');

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

        function cleanLocalStorage() {
            localStorage.clear();
            livewire.emit('refresh_accomany');
        }

        window.addEventListener("showModal2", function(e) {
            alert('Funciona')
            const myModal = new bootstrap.Modal(document.getElementById(e.detail.id))
            myModal.show();
        })
    </script>
@endpush
