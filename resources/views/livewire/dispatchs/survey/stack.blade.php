@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Custom\WpaStatus;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <x-showselected :count="$selected" />


    <div class="d-flex align-items-center justify-content-between mb-3">
        <!-- Campo de busca com botão e tooltip -->
        <div class="input-group me-3">
            <input type="text" class="form-control" placeholder="Buscar..." aria-label="Buscar"
                wire:model.debounce.1s="search">
            <span data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" data-bs-content="Multinotas">
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                    data-bs-target="#buscar_multi" title="Multinotas">
                    <i class="ri-checkbox-multiple-blank-fill"></i>
                </button>
            </span>
        </div>

        <!-- Botões do tipo radio para seleção individual -->
        <div class="btn-group me-3" role="group" aria-label="Seleção de Opções">
            <input type="radio" class="btn-check" name="selecao" id="nota" autocomplete="off"
                wire:model="note_type" value="1">
            <label class="btn btn-outline-primary" for="nota">Nota</label>

            <input type="radio" class="btn-check" name="selecao" id="ov" autocomplete="off"
                wire:model="note_type" value="2">
            <label class="btn btn-outline-primary" for="ov">Ov</label>

            <input type="radio" class="btn-check" name="selecao" id="ambas" autocomplete="off"
                wire:model="note_type" value="">
            <label class="btn btn-outline-primary" for="ambas">Ambas</label>
        </div>

        <!-- Quatro botões alinhados -->
        <div class="btn-group" role="group" aria-label="Ações">
            @livewire('components.filter.filter', ['myKey' => 'user', 'sendFilter' => '', 'model' => 'App\Models\User', 'column' => 'id', 'filter' => 'Usuarios', 'group_filter' => 'control_survey', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('users'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'control_survey', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'control_survey', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'control_survey', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'control_survey', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'control_survey'], key('removeAll'))
        </div>
    </div>

    <div class="btn-group my-3" role="group" aria-label="Status">
        @if ($status->count())

            @foreach ($status as $index => $sts)
                <button class="btn btn-{{ Notestatus::status($sts->status)->color }}"> <input class="form-check-input"
                        type="checkbox" wire:model.defer="status_s" value="{{ $sts->status }}"
                        id="statusCheck{{ $sts->status }}"> {{ Notestatus::status($sts->status)->status }}
                    @if ($sts->total > 0)
                        <span class="badge text-bg-dark">{{ $sts->total }}</span>
                    @endif
                </button>
                {{-- <label class="btn btn-{{ Notestatus::status($sts->status)->color }}">
                    <input type="checkbox" name="selecao[]" wire:model="status_s" value="{{ $sts->status }}"
                        autocomplete="off" class="visually-hidden">
                    {{ Notestatus::status($sts->status)->status }}
                    @if ($sts->total > 0)
                        <span class="badge text-bg-dark">{{ $sts->total }}</span>
                    @endif
                </label> --}}
            @endforeach
            <button class="btn btn-danger" wire:click.prevent="aplicar"> Aplicar
            </button>
        @endif
        {{-- <input type="radio" class="btn-check" name="selecao" id="nota" autocomplete="off"
            wire:model="note_type" value="1">
        <label class="btn btn-outline-primary" for="nota">Nota</label>

        <input type="radio" class="btn-check" name="selecao" id="ov" autocomplete="off"
            wire:model="note_type" value="2">
        <label class="btn btn-outline-primary" for="ov">Ov</label>

        <input type="radio" class="btn-check" name="selecao" id="ambas" autocomplete="off"
            wire:model="note_type" value="">
        <label class="btn btn-outline-primary" for="ambas">Ambas</label> --}}
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
                    <div class="col-4 d-flex justify-content-end">
                        <button class="btn btn-sm btn-success me-2" data-bs-toggle="modal"
                            data-bs-target="#add_mass_dds"><i class="ri-checkbox-multiple-fill"></i> Att DD</button>
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
                            </ul>
                        </div>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click="$refresh"><i
                                class="ri-refresh-line"></i></button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model.defer="selectall"
                                    wire:click.prevent="setSelectall" @checked($this->checkSelectAll($lists))>
                            </th>
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">DD</th>
                            <th scope="col" class="fw-bold text-center">stsDD</th>
                            <th scope="col" class="fw-bold text-center">MMGD</th>
                            <th scope="col" class="fw-bold text-center">Despachante</th>
                            <th scope="col" class="fw-bold text-center">Grp2</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Zona</th>
                            <th scope="col" class="fw-bold text-center">Descrição</th>
                            <th scope="col" class="fw-bold text-center">Empresa</th>
                            <th scope="col" class="fw-bold text-center">Usuário</th>
                            <th scope="col" class="fw-bold text-center">Dias Despachado</th>
                            <th scope="col" class="fw-bold text-center">Dias Atribuido</th>
                            <th scope="col" class="fw-bold text-center">Prazo Real</th>
                            <th scope="col" class="fw-bold text-center">Mensalização</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            <tr class="align-middle
                                    @if ($list->block) table-primary @endif

                                    "
                                wire:key="item-{{ $list->id }}">
                                <td>
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected">
                                </td>
                                <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif">
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
                                </td>

                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    @if ($list->Wpas->count())
                                        <a class="link-primary fw-bold"
                                            href="https://edp-wpa-po.azurewebsites.net/Search?q={{ $list->Wpas()->get()->last()->dd }}"><span
                                                class="text-primary">{{ $list->Wpas()->get()->last()->dd }}</span></a>
                                    @else
                                        -----
                                    @endif

                                </td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    @if ($list->wpas->count())
                                        @php
                                            $wpas = $list->wpas->last();

                                            $wpa = WpaStatus::status(
                                                $wpas->stats,
                                                $wpas->execstats,
                                                $wpas->completed_at,
                                            );
                                        @endphp
                                        <i
                                            class="{{ $wpa->icon }} {{ $wpa->color }} fs-3 align-middle my-0"></i><br>
                                        <span class="badge {{ $wpa->bg_color }} my-0">{{ $wpa->info }}</span>
                                        <br>
                                    @else
                                        -----
                                    @endif

                                </td>
                                <td class="fw-bold text-danger text-center">
                                    {{ $list->Note->mmgd ? 'MMGD' : '' }}
                                </td>
                                @php
                                    $name = isset($list->Dispatcher->name)
                                        ? explode(' ', $list->Dispatcher->name)
                                        : null;

                                    if ($name) {
                                        $name = $name[0] . ' ' . end($name);
                                    } else {
                                        $name = 'DESCONHECIDO';
                                    }

                                @endphp
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    {{ $name }}</td>


                                <td <td class="fw-bold @if ($list->priority) text-danger fw-bold @endif"
                                    text-center">
                                    {{ $list->Note->group2 ? $list->Note->group2 : '____' }}
                                </td>

                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    {{ $list->Note->rubrica }}</td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    {{ $list->Note->lexp }}</td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    {{ $list->Note->group1 }}</td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    {{ $list->Note->material }}</td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">

                                    {{ $list->Company ? explode(' ', $list->Company->name)[0] : '-' }}</td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    @php
                                        $nome = $list->User ? explode(' ', $list->User->name) : '----';
                                        if (is_array($nome)) {
                                            $nome = $nome[0] . ' ' . substr(end($nome), 0, 1);
                                        }
                                    @endphp
                                    {{ $nome }}</td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
                                    {{ Carbon::now()->diffInDays(Carbon::parse($list->dispatch_at)->format('Y-m-d')) }}
                                </td>
                                <td
                                    class="fw-light text-center @if ($list->priority) text-danger fw-bold @endif">
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
                                ">
                                    {{ 30 - $list->Note->days_left }}
                                </td>
                                {{-- <td class="fw-light text-center">
                                    <span
                                        class="badge {{ Notestatus::status($list->status)->colorbg }}">{{ Notestatus::status($list->status)->status }}</span>
                                </td> --}}
                                <td class="fw-light text-center">
                                    {{ $list->note->mesalization }}
                                </td>
                                <td class="fw-light text-center">
                                    @if ($list->transferred && $list->block_wpa)
                                        <span class="badge bg-warning">Aguardando Despacho</span>
                                    @else
                                        <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                            wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                            style="cursor: pointer;">{{ Notestatus::status($list->status)->status }}</span>
                                    @endif
                                </td>
                                <td>
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
                                        @foreach ($user_l as $user)
                                            <option wire:key='{{ $user->id }}' value="{{ $user->id }}">
                                                {{ $user->name }}</option>
                                        @endforeach
                                    @else
                                        <option selected>Escolha uma Empresa Primeiro</option>
                                    @endif
                                </select>
                            </div>


                            <div class="mb-2 ">
                                <label for="exampleFormControlInput1" class="form-label">Relacionar DD em
                                    MASSA:</label>
                                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"
                                    placeholder="<número OV/NOTA> <número DD> Ex: 4001123232 14034330" wire:model.defer="enter_dd"></textarea>
                            </div>
                            <div class="mb-3">
                                <button class="btn-sm btn btn-primary" wire:click.prevent="add_dd">DD em
                                    MASSA</button>
                            </div>


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
                                                @php
                                                    $this->additionalData[$index] = $note->load('Wpas')
                                                        ? $note->load('Wpas')->Wpas->last()->dd
                                                        : '';
                                                @endphp

                                                <input wire:model.defer="additionalData.{{ $index }}"
                                                    class="form-control form-control-sm" type="text"
                                                    placeholder="Informe a DD" aria-label="">


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


    <div wire:ignore.self class="modal fade" id="add_mass_dds" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Atribuir DD em {{ $service->service }}</h1>
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
