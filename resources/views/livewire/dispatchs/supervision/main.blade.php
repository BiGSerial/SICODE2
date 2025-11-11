@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="row mb-3 justify-content-end align-middle">
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

            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'supervision', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            {{-- @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'supervision', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region')) --}}
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'baseConstrucao', 'filter' => 'Regiao', 'group_filter' => 'supervision', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'supervision', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'supervision'], key('removeAll'))
        </div>

        <div class="mb-3">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right"
                data-bs-title="Exibir Apenas Notas Nao Atribuidas"
                data-bs-content="<p>Ao clicar, todas as notas que nao contenham atribuiçao estará visível. Ocultando qualquer outra nota atribu[ida. </p> <pA palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
                <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                    wire:click.prevent="filterStatus()">
                    {{ Notestatus::status(1)->status }}
                    @if ($not_assigned)
                        <span class="badge text-bg-success">ON</span>
                    @else
                        <span class="badge text-bg-danger">OFF</span>
                    @endif
                </button>

            </div>
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right"
                data-bs-title="Exibir Apenas Notas Nao Atribuidas"
                data-bs-content="<p>Ao clicar, todas as notas que nao contenham atribuiçao estará visível. Ocultando qualquer outra nota atribu[ida. </p> <pA palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
                <button type="button" class="btn btn-secondary" wire:click.prevent="filterD5()">
                    Somente D5
                    @if ($filter_d5)
                        <span class="badge text-bg-success">ON</span>
                    @else
                        <span class="badge text-bg-danger">OFF</span>
                    @endif
                </button>

            </div>
        </div>

    </div>


    <div class="row">

        @if (!$lists->count())
            {{-- <div class="col-6">
                @livewire('components.manualnote.manualnote', ['service' => $service->uuid])
            </div> --}}
        @elseif ($lists->count())
            <div class="col-6">
                {{ $lists->links() }}
            </div>
        @endif
        <div class="col-6 d-flex justify-content-end align-middle">
            <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                {{ $lists->lastItem() }}
                de {{ $lists->total() }}
                registros.
                @if ($update)
                    Ultima Atualização: <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </span>
        </div>

    </div>

    <div class="card">

        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM NOTAS PARA EXIBIR EM {{ $service->service }} - @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">LISTA PARA {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">
                        <span data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                            data-bs-title="Adicionar numeração DD em Massa"> <button class="btn btn-sm btn-success me-2"
                                data-bs-toggle="modal" data-bs-target="#add_mass_dds"><i
                                    class="ri-checkbox-multiple-fill"></i> Att
                                DD</button></span>

                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                            data-bs-title="Atribuir Notas em Massa"><i class="ri-checkbox-multiple-fill"></i>
                            Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'
                            data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                            data-bs-title="Exportar as Notas SELECIONADAS para o EXCEL"><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-condensed table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model.defer="selectAll"
                                    wire:click="setSelectAll()" @disabled($this->checkAllSelect($lists))>
                            </th>
                            <th scope="col" class="fw-bold text-center">Tipo</th>
                            <th scope="col" class="fw-bold text-center">ADS</th>
                            <th scope="col" class="fw-bold text-center">Dt ADS</th>
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">Ordem</th>
                            <th scope="col" class="fw-bold text-center">DD</th>
                            <th scope="col" class="fw-bold text-center">MMGD</th>
                            <th scope="col" class="fw-bold text-center">Postes</th>
                            <th scope="col" class="fw-bold text-center">Informado Em</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">MOA</th>
                            <th scope="col" class="fw-bold text-center">Fiscalizações</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th scope="col" class="fw-bold text-center">Dias Informe</th>
                            <th scope="col" class="fw-bold text-center">Dias D5</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $e = $this->needBlock($list); // ['block'=>.., 'command'=>.., 'color'=>.., 'reason'=>..]
                                $rowClass = $e['color'];
                                $block = $e['block'];
                                $command = $e['command'];
                                $production = $e['production'];
                                $reason = $e['reason'];

                                // mantém tua lógica de “parcial” apenas pra exibir a tag:
                                $partial = $e['isPartial'];

                                if ($list->FiveNote) {
                                    $dateFive = Carbon::parse($list->FiveNote->completed_at);
                                } else {
                                    $dateFive = null;
                                }
                            @endphp


                            <tr class="align-middle">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled($block)>
                                </td>
                                {{-- @can('management')
                                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                                        </td>
                                    @endcan --}}
                                <td
                                    class="fw-bold text-center @if ($partial) table-warning @else table-success @endif">
                                    {{ $partial ? 'PARCIAL' : 'FINAL' }}
                                </td>
                                <td class="fw-bold text-primary text-center {{ $rowClass }}">
                                    {{ $list->Adsform || $list->OldAds->isNotEmpty() ? 'SIM' : '' }}
                                </td>
                                <td class="fw-bold text-primary text-center {{ $rowClass }}">
                                    @if ($list->OldAds->isNotEmpty())
                                        {{ $list->OldAds->last()->date->format('d/m/Y H:i:s') }}
                                    @elseif($list->Adsform)
                                        {{ $list->Adsform->created_at->format('d/m/Y H:i:s') }}
                                    @endif

                                </td>
                                <td class="fw-bold copy-text text-center {{ $rowClass }}"
                                    data-value="{{ $list->note }}">
                                    @if ($list->FiveNote?->is_completed && !$list->FiveNote?->is_supervisioned)
                                        <span class="badge text-bg-success fs-6">D5 {{ $list->note }}</span>
                                    @else
                                        {{ $list->note }}
                                    @endif
                                    @if ($list->pze == '25')
                                        <span tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="NOTA EXPRESSA"
                                            data-bs-content="Nota com prazo de execução de {{ $list->pze }} dias"
                                            style="z-index: 9999;" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <i class="ri-fire-line text-danger fw-bold"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center {{ $rowClass }}">
                                    @if ($list->WorkForm)
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @elseif ($list->Partials->count())
                                        @foreach ($list->Partials->last()->Orders as $order)
                                            <p class="my-0 py-0">{{ $order->ordem }}</p>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="fw-bold text-danger {{ $rowClass }} text-center">
                                    {{ $list->Wpas->count() ? (!$list->Wpas->last()->production_id ? $list->Wpas->last()->dd : '') : '' }}
                                </td>
                                <td class="fw-bold text-danger {{ $rowClass }} text-center">
                                    {{ $list->mmgd ? 'MMGD' : '' }}
                                </td>
                                <td class="fw-bold text-primary {{ $rowClass }} text-center">
                                    {{ isset($list->postes) ? $list->postes : '---' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if ($list->WorkForm)
                                        {{ Carbon::parse($list->WorkForm->informed_at)?->format('d/m/Y') }}
                                    @elseif ($list->Partials)
                                        {{ $list->Partials?->last()?->created_at?->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ mb_strtoupper($list->numPedido) }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->rubrica }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if (!empty($list->lexp))
                                        {{ $list->lexp }}
                                    @else
                                        <span tabindex="1" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="Editar Município"
                                            data-bs-content="Clique para editar o município faltante para esta nota.">
                                            <button class="btn btn-sm btn-secondary"
                                                wire:click.prevent="$emit('editMunicipio', '{{ $list->id }}')">Edit</button>
                                        </span>
                                    @endif

                                </td>
                                <td class="fw-bold text-center {{ $rowClass }}">
                                    R$ {{ number_format($list->orders?->sum('moaberto'), 2, ',', '.') }}
                                </td>




                                <td class="fw-light text-center {{ $rowClass }}" tabindex="2"
                                    data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Levantamentos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($count = $this->hasPublicationCount($list))
                                        @php
                                            if ($production && $production->User) {
                                                $name = explode(' ', $production->User->name);
                                                $name = $name[0] . ' ' . end($name);
                                            } else {
                                                $name = 'Desconhecido';
                                            }
                                        @endphp
                                        <span class="badge text-bg-dark">{{ $count }}</span><br>
                                        {{ $name }}
                                    @else
                                        --
                                    @endif
                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->nstats }}<br><span>{{ $list->centerjob }}</span></td>
                                {{-- <td class="fw-light text-center">{{ $list->pze }}</td> --}}
                                @php
                                    $days_left = 0;
                                    if ($list->WorkForm) {
                                        $days_left = Carbon::parse($list->WorkForm->informed_at)->diffInDays(
                                            Carbon::now(),
                                            false,
                                        );
                                    } elseif ($list->Partials) {
                                        $days_left = $list->Partials
                                            ?->last()
                                            ?->created_at?->diffInDays(Carbon::now(), false);
                                    }
                                @endphp
                                <td scope="col"
                                    class="text-center
                                        @if ($days_left <= 20) text-bg-success
                                        @elseif ($days_left >= 28)
                                        text-bg-danger
                                        @else
                                        text-bg-warning @endif
                                    ">
                                    {{ $days_left }}
                                </td>
                                <td scope="col" class="text-center text-bg-info">
                                    @if ($dateFive)
                                        {{ $dateFive?->startOfDay()->diffInDays() }}
                                    @else
                                        ---
                                    @endif
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if ($list->pze_parecer === 'Vencido')
                                        <span class="badge text-bg-danger">VENCIDO</span>
                                    @elseif ($list->pze_parecer === 'Não vencido')
                                        <span class="badge text-bg-success">EM PRAZO</span>
                                    @else
                                        <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                    @endif
                                </td>


                                <td class="fw-bold text-center {{ $rowClass }}" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="{{ $reason }}">


                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="get_single_note({{ $list->id }})"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="Despachar esta Nota/OV"></i>
                                    @else
                                        @php
                                            if ($production && $production->Company) {
                                                $name = explode(' ', $production->Company->name)[0];
                                            } else {
                                                $name = 'Desconhecido';
                                            }
                                        @endphp
                                        <span style="font-size: 11px">{{ $name }}</span>
                                        @if ($command)
                                            <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                                style="cursor: pointer;"
                                                wire:click.prevent="get_single_note({{ $list->id }})"></i>
                                        @endif
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
        </dic>
    </div>
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


    {{-- MODALS --}}

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


    <div wire:ignore.self class="modal fade" id="add_mass_dds" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Atribuir DD em {{ $service->service }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="exampleFormControlInput1" class="form-label">Relacionar DD em
                            MASSA:</label>
                        <textarea class="form-control" id="exampleFormControlTextarea1" rows="10" style="resize: none;"
                            placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                    </div>
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="mass_modal">Atribuir</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="row">
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Tipo de Despacho</label>
                                <select class="form-select form-select-sm" aria-label="Small select example"
                                    wire:model="type" disabled>
                                    <option selected>Selecione</option>
                                    <option value="1">Pilha</option>
                                    <option value="2">Individual</option>
                                </select>
                            </div>

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

                            @if ($type === '2')

                                <div class="row mb-3">
                                    <div class="col">
                                        <label for="exampleFormControlInput1" class="form-label">Buscar
                                            Usuario:</label>
                                        <input wire:model="search_user" class="form-control form-control-sm"
                                            type="text" placeholder="Digite um nome" aria-label="">
                                    </div>
                                    <div class="col">
                                        <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                        <select class="form-select form-select-sm" aria-label=""
                                            wire:model="user_s">

                                            @if ($user_l && $user_l->count())
                                                <option value="" selected>Selecione um Usuário</option>
                                                @foreach ($user_l as $user)
                                                    <option wire:key='{{ $user->id }}'
                                                        value="{{ $user->id }}">{{ $user->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option selected>Escolha uma Empresa Primeiro</option>
                                            @endif
                                        </select>
                                    </div>
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
                            @endif

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
                                        <th scope="col">DD</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr>
                                            <td scope="col" class="fw-bold">{{ $index + 1 }}</td>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->material }}</td>
                                            <td>
                                                @if ($this->type === '2' && isset($list))
                                                    @php
                                                        $dd = $list->Wpas->count()
                                                            ? (!$list->Wpas->last()->production_id
                                                                ? $list->Wpas->last()->dd
                                                                : '')
                                                            : '';

                                                        $additionalData[$index] = $dd;
                                                    @endphp


                                                    <input wire:model.defer="additionalData.{{ $index }}"
                                                        class="form-control form-control-sm" type="text"
                                                        placeholder="Informe a DD" aria-label="" value="">
                                                @endif

                                            </td>

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




    {{-- END MODALS --}}



</div>
