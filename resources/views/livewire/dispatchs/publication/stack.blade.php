@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div>

    @push('css')
        <style>
            @keyframes flame {
                0% {
                    transform: scaleX(1) scaleY(1);
                }

                25% {
                    transform: scaleX(1) scaleY(0.8);
                }

                50% {
                    transform: scaleX(-1) scaleY(0.8);
                }

                75% {
                    transform: scaleX(-1) scaleY(1);
                }
            }
        </style>
    @endpush
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="row">
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
        <div class="mb-3 col-md-2">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="email"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                        class="ri-checkbox-multiple-blank-line"></i></button>
            </div>
        </div>


        <div class="col-md-9 d-flex mb-3 justify-content-end py-4">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="1">
                <label class="form-check-label" for="inlineRadio1">Nota</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="2">
                <label class="form-check-label" for="inlineRadio1">OV</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="">
                <label class="form-check-label" for="inlineRadio1">Ambos</label>
            </div>
            <div class="dropdown mx-1">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Status
                    @if (count($status_s))
                        <span class="badge text-bg-light">{{ count($status_s) }}</span>
                    @endif
                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($status_l) && count($status_l) > 0)
                            @foreach ($status_l as $value)
                                @if ($value)
                                    <div class="dropdown-item {{ Notestatus::status($value)->colorbg }}">
                                        <input type="checkbox" class="form-check-input" wire:model.defer="status_s"
                                            wire:key="status-{{ $value }}" value="{{ $value }}">
                                        <label for="opcao1">{{ Notestatus::status($value)->status }}</label>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                        <div class="dropdown-divider"></div>
                        <div class="d-flex justify-content-center mt-2">
                            <button type="submit" class="btn btn-sm btn-primary mx-1">Aplicar Filtro</button>
                            <button type="button" class="btn btn-sm btn-danger mx-1"
                                wire:click.prevent="filter_clean">Limpar</button>

                        </div>
                    </form>
                </div>
            </div>

            @livewire('components.filter.filter', ['myKey' => 'user', 'sendFilter' => '', 'model' => 'App\Models\user', 'column' => 'id', 'filter' => 'Usuario', 'group_filter' => 'publishing', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('usuario'))
            @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'publishing', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'publishing', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'publishing', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'publishing', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'publishing', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'publishing'], key('removeAll'))


        </div>


        <div class="mb-3">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="1">
                <label class="form-check-label" for="inlineRadio1">Nota</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="2">
                <label class="form-check-label" for="inlineRadio1">OV</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="note_type" wire:model="note_type" value="">
                <label class="form-check-label" for="inlineRadio1">Ambos</label>
            </div>
        </div>

        <div class="mb-3">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right" data-bs-title="Filtragem Direta por Status"
                data-bs-content="
        <p>Ao apertar o botão, o sistema filtrará a lista pelo status escolhido. Para remover o filtro, basta limpar os filtros.</p>

       ">
                <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                    wire:click.prevent="filterStatus(1)">
                    {{ Notestatus::status(1)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 1)->count() }}</span></button>
                <button type="button" class="btn btn-{{ Notestatus::status(2)->color }}"
                    wire:click.prevent="filterStatus(2)">
                    {{ Notestatus::status(2)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 2)->count() }}</span></button>
                <button type="button" class="btn btn-{{ Notestatus::status(4)->color }}"
                    wire:click.prevent="filterStatus(4)">
                    {{ Notestatus::status(4)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 4)->count() }}</span></button>

                <button type="button" class="btn btn-{{ Notestatus::status(5)->color }}"
                    wire:click.prevent="filterStatus(5)">
                    {{ Notestatus::status(5)->status }} <span
                        class="badge text-bg-light">{{ $allList->where('status', 5)->count() }}</span></button>
            </div>
        </div>

    </div>

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
                <h4 class="text-center">SEM NOTAS SELECIONADAS PARA CONTROLE EM
                    <strong>{{ mb_strtoupper($service->service) }}</strong>
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            {{-- <h4 class="card-header fw-bold text-bg-danger">ACOMPANHAMENTO -
                {{ mb_strtoupper($service->service) }} - @if ($service->Status->count())
                    @foreach ($service->Status as $sts)
                        ({{ $sts->status }})
                    @endforeach
                @endif
            </h4> --}}
            <div class="card-header text-bg-danger">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">CONTROLE DE {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">
                        {{-- <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button> --}}

                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary me-2 dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Ações em Massa
                            </button>
                            <ul class="dropdown-menu">
                                <li tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="left" data-bs-title="Atribuir em Massa"
                                    data-bs-content="
                                    <p>A Atribuição em Massa possibilita a modificação dos responsáveis por uma tarefa,
                                        mesmo que ela já tenha sido atribuída a outra pessoa.
                                        No entanto, essa ação só é possível se a atividade não estiver FINALIZADA ou em PAUSA.</p>
                                   ">
                                    <a class="dropdown-item" href="#" wire:click.prevent='go_att_mass'><i
                                            class="ri-user-add-line text-primary"></i> Atribuir
                                        em Massa</a>
                                </li>
                                <li tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="left" data-bs-title="Desatribuir em Massa"
                                    data-bs-content="
                                <p>A Desatribuição em Massa possibilita a remoção total responsável pela atividade liberando-a na LISTA PARA DESPACHO.
                                    No entanto, essa ação só é possível se a atividade <span class='fw-bold'>NÃO</span> estiver FINALIZADA ou em PAUSA.</p>
                                    <span class='fs-4 text-white fw-bold'>&#9632;</span> <span class='text-white fw-bold text-uppercase'>Marque a caixa no final do botão para forçar e ignorar o PAUSE.</span>
                               ">

                                    <a class="dropdown-item" href="#">
                                        <i class="ri-user-shared-line text-danger"></i>
                                        <span wire:click.prevent='go_des_att_mass'>Desatribuir em Massa</span>
                                        <input class="form-check-input border border-1 border-secondary"
                                            type="checkbox" wire:model.defer="forcar">
                                    </a>
                                </li>
                                <li tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="left" data-bs-title="Priorizar em Massa"
                                    data-bs-content="
                            <p>A priorização em massa permite priorizar um grande números de registros, porém, o texto de motivo será unico para todos eles.</p>

                           ">
                                    <a class="dropdown-item" href="#">
                                        <i class="ri-alert-fill text-danger align-middle"></i>
                                        <span wire:click.prevent='go_priority_mass'>Priorizar em Massa</span>

                                    </a>


                                </li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <i class="ri-alert-fill align-middle text-success"></i>
                                        <span wire:click.prevent='go_des_priority_mass'>Despriorizar em Massa</span>

                                    </a>
                                </li>
                            </ul>
                        </div>



                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                    wire:click="setSelectAll()" @checked($this->checkAllSelect($lists))>
                            </th>
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">inf Digitacao</th>
                            <th scope="col" class="fw-bold text-center">Ordem</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Material</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Empresa</th>
                            <th scope="col" class="fw-bold text-center">Usuário</th>
                            <th scope="col" class="fw-bold text-center">Dias Despachado</th>
                            <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
                            <th class="align-middle text-center">Dt Vencimento</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $formBlock =
                                    $list->Note->WorkForm && $list->Note->WorkForm->rejected
                                        ? $list->Note->WorkForm->rejected
                                        : false;

                                $color = '';

                                if ($formBlock) {
                                    $color = 'table-dark text-danger';
                                } elseif ($list->priority) {
                                    $color = 'text-danger fw-bold';
                                } elseif ($list->block) {
                                    $color = 'table-primary';
                                } elseif (!$list->Note->WorkForm && $list->Note->RamalForm) {
                                    $color = 'table-warning';
                                } elseif (!$list->Note->WorkForm && $list->Note->RamalForm && $list->status == 28) {
                                    $color = 'table-info';
                                } elseif ($list->Note->WorkForm && $list->Note->RamalForm && $list->status == 28) {
                                    $color = 'table-success';
                                }

                            @endphp
                            <tr wire:key="line-{{ $list->id }}" class="align-middle text-center">
                                <td class="{{ $color }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected">
                                </td>
                                <td
                                    class="@if ($list->Note->is45) text-bg-warning
                                @else
                                {{ $color }} @endif">

                                    @if ($list->d5)
                                        <span class="badge text-bg-primary fs-6">{{ $list->Note->note }}
                                            (RI)
                                        </span>
                                    @else
                                        {{ $list->Note->note }}
                                        <span class="copy-text" data-value="{{ $list->Note->note }}"
                                            style="cursor: pointer;"> <i class="ri-file-copy-line"></i></span>
                                    @endif

                                    @if ($list->priority)
                                        <i class="ri-alert-fill text-danger align-middle"
                                            wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                            style="cursor: pointer;"></i>
                                    @endif

                                    @if ($list->Note->is45)
                                        <span tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="NOTA EXPRESSA"
                                            data-bs-content="Nota com prazo de execução de 45 dias"
                                            style="z-index: 9999;" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="ri-fire-line text-danger fw-bold"
                                                style="display: inline-block; animation: flame 1s steps(1) infinite;"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="fw-light {{ $color }}">
                                    @if (!$list->Note->WorkForm && $list->Note->RamalForm)
                                        <i class="ri-alert-line text-danger align-middle fs-4"></i>
                                    @endif

                                </td>
                                <td class="fw-bold {{ $color }} text-center">
                                    @if ($list->Note->WorkForm && $list->Note->WorkForm->Orders->count())
                                        @foreach ($list->Note->WorkForm->Orders as $order)
                                            <p class="py-0 my-0">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @elseif ($list->Note->RamalForm && $list->Note->RamalForm->Orders->count())
                                        @foreach ($list->Note->RamalForm->Orders as $order)
                                            <p class="py-0 my-0">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @endif
                                </td>



                                <td class="fw-light {{ $color }}">
                                    {{ $list->Note->rubrica }}</td>
                                <td class="fw-light {{ $color }}">
                                    {{ $list->Note->material }}</td>
                                <td class="fw-light {{ $color }}">
                                    {{ $list->Note->lexp }}</td>



                                <td class="fw-light {{ $color }}">

                                    {{ $list->Company ? $list->Company->name : '-' }}</td>
                                <td class="fw-light {{ $color }}">
                                    @php
                                        $nome = $list->User ? explode(' ', $list->User->name) : '----';
                                        if (is_array($nome)) {
                                            $nome = $nome[0] . ' ' . end($nome);
                                        }
                                    @endphp
                                    {{ $nome }}</td>
                                <td class="fw-light {{ $color }}">
                                    {{ Carbon::now()->diffInDays(Carbon::parse($list->dispatch_at)->format('Y-m-d')) }}
                                </td>
                                <td class="fw-light {{ $color }}">
                                    {{ Carbon::now()->diffInDays(Carbon::parse($list->att_at)->format('Y-m-d')) }}
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
                                <td class="fw-light text-center {{ $color }}">
                                    @if ($formBlock)
                                        <span class="badge text-bg-info text-wrap p-1">INFORME EM REVISAO</span>
                                    @else
                                        <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                            wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                            style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                    @endif

                                </td>
                                <td class="fw-bold fs-5 {{ $color }}">



                                    <x-production.action-production :production="$list" />



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


    {{-- MODALS --}}
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

    <div wire:ignore.self class="modal fade" id="add_mass_notes" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Despachar {{ $service->service }}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="row">
                            {{-- <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Tipo de Despacho</label>
                                <select class="form-select form-select-sm" aria-label="Small select example"
                                    wire:model="type">
                                    <option selected>Selecione</option>
                                    <option value="1">Pilha</option>
                                    <option value="2">Individual</option>
                                </select>
                            </div> --}}
                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Empresa:</label>
                                <select class="form-select form-select-sm" aria-label="" wire:model="company_s">
                                    <option selected>Selecione</option>
                                    @if ($company_l && $company_l->count())
                                        @foreach ($company_l as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>



                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                <select class="form-select form-select-sm" aria-label="" wire:model="user_s">

                                    @if ($user_l && $user_l->count())
                                        <option value="" selected>Selecione um Usuário</option>
                                        @foreach ($user_l->sortBy('name', SORT_LOCALE_STRING) as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    @else
                                        <option selected>Escolha uma Empresa Primeiro</option>
                                    @endif
                                </select>
                            </div>


                            {{-- <div class="mb-2 ">
                                <label for="exampleFormControlInput1" class="form-label">Relacionar DD em
                                    MASSA:</label>
                                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"
                                    placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                            </div>
                            <div class="mb-3">
                                <button class="btn-sm btn btn-primary" wire:click.prevent="add_dd">DD em
                                    MASSA</button>
                            </div> --}}


                            <div class="col-12 fw-bold">
                                DESPACHANDO {{ $notes->count() }} OV/NOTA(S)
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Note</th>
                                        <th scope="col">Desc</th>
                                        {{-- <th scope="col">DD</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr>
                                            <td scope="col" class="fw-bold">{{ $index + 1 }}</td>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->material }}</td>
                                            {{-- <td>

                                                <input wire:model.defer="additionalData.{{ $index }}"
                                                    class="form-control form-control-sm" type="text"
                                                    placeholder="Informe a DD" aria-label="">


                                            </td> --}}

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="confirm_att"
                        wire:loading.attr="disabled" wire:target="confirm_att">
                        Despachar
                    </button>
                </div>
            </div>
        </div>
    </div>



    @stack('modals')
    {{-- END MODALS --}}
    @livewire('audits.info')
    @livewire('components.status.show-status', key('show_status_note'))

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
    </script>
@endpush
