@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());

    $prazoRealFromDtCreated = function ($note) {
        if (!$note?->dt_created) {
            return null;
        }

        return Carbon::parse($note->dt_created)->startOfDay()->diffInDays(Carbon::now()->startOfDay());
    };
@endphp
<div class="survey-main-page">

    @include('livewire.dispatchs.partials.list-shell-style')

    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="container-fluid px-3 px-lg-4">
        <div class="survey-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>LISTA PARA {{ mb_strtoupper($service->service) }}
                    @if ($contractCompanyName)
                        - {{ mb_strtoupper($contractCompanyName) }}
                    @endif
                </h2>
                <div class="survey-meta">
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="text-lg-end">
                @if ($update)
                    <div class="survey-meta">Última Atualização</div>
                    <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </div>
        </div>

    <div class="filter-shell mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-3 align-items-end">
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


            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'analises_pre', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('analises-pre-rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'analises_pre', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('analises-pre-region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'baseConstrucao', 'filter' => 'Regional', 'group_filter' => 'analises_pre', 'values' => 'baseConstrucao', 'direction' => 'ASC', 'query' => ''], key('analises-pre-regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'rdMunicipio', 'filter' => 'Municipio', 'group_filter' => 'analises_pre', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('analises-pre-city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'analises_pre'], key('analises-pre-removeAll'))
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
        </div>

    </div>

    <div class="filter-shell mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-3 align-items-end">

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
        </div>
    </div>

    <div class="table-card">

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
            <div class="card-header fw-bold text-bg-secondary">
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
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-condensed table-hover mb-0 main-table">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectall">
                            </th>

                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">Dt Status</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Material</th>
                            <th scope="col" class="fw-bold text-center">Grp1</th>
                            <th scope="col" class="fw-bold text-center">Grp2</th>
                            <th scope="col" class="fw-bold text-center">Retorno</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            <th scope="col" class="fw-bold text-center">Prazo Real</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $block = 0;
                                $exception = false;
                                $production = '';
$user = [];

                                $production = $list->Productions->where('service_id', $this->service->uuid);

                                if ($production->where('completed', false)->where('confirmed', false)->count()) {
                                    $block = 1;

                                    $lastProduction = $production
                                        ->where('completed', false)
                                        ->where('confirmed', false)
                                        ->last();

                                    $lastName = $lastProduction->User->name ?? 'Desconhecido';
                                    $company = $lastProduction->Company->name ?? 'Desconhecido';
                                    $status = $lastProduction->status ?? 'Desconhecido';

                                    $count = $production->count();

                                    $lastName = explode(' ', $lastName);
                                    $lastName =
                                        count($lastName) > 1 ? $lastName[0] . ' ' . end($lastName) : $lastName[0];

                                    $company = explode(' ', $company)[0];

                                    $user = [
                                        'lastUser' => $lastName,
                                        'countProd' => $count,
                                        'status' => $status,
                                        'company' => $company,
                                    ];
                                } elseif ($production->where('completed', true)->where('confirmed', false)->count()) {
                                    $block = 2;

                                    $lastProduction = $production
                                        ->where('completed', true)
                                        ->where('confirmed', false)
                                        ->last();

                                    $lastName = $lastProduction->User->name ?? 'Desconhecido';
                                    $company = $lastProduction->Company->name ?? 'Desconhecido';
                                    $status = $lastProduction->status ?? 'Desconhecido';

                                    $count = $production->count();

                                    $lastName = explode(' ', $lastName);
                                    $lastName = $lastName[0] . ' ' . end($lastName);

                                    $company = explode(' ', $company)[0];

                                    $user = [
                                        'lastUser' => $lastName,
                                        'countProd' => $count,
                                        'status' => $status,
                                        'company' => $company,
                                    ];
                                } elseif ($production->where('completed', true)->where('confirmed', true)->count()) {
                                    if (
                                        $production
                                            ->where('completed', true)
                                            ->where('confirmed', true)
                                            ->where('dt_note', $list->dt_status)
                                            ->where('noinconsistency', false)
                                            ->where('type_note', 2)
                                            ->count()
                                    ) {
                                        $block = 3;

                                        $lastProduction = $production
                                            ->where('completed', true)
                                            ->where('confirmed', true)
                                            ->where('dt_note', $list->dt_status)
                                            ->where('noinconsistency', false)
                                            ->where('type_note', 2)
                                            ->last();

                                        $lastName = $lastProduction->User->name ?? 'Desconhecido';
                                        $company = $lastProduction->Company->name ?? 'Desconhecido';
                                        $status = $lastProduction->status ?? 'Desconhecido';

                                        $count = $production->count();

                                        // Get First and Last name from User Name,
                                        $lastName = explode(' ', $lastName);
                                        $lastName = $lastName[0] . ' ' . end($lastName);

                                        // Get just first Company name.
                                        $company = explode(' ', $company)[0];

                                        $user = [
                                            'lastUser' => $lastName,
                                            'countProd' => $count,
                                            'status' => $status,
                                            'company' => $company,
                                        ];
                                    } else {
                                        $lastProduction = $production
                                            ->where('completed', true)
                                            ->where('confirmed', true)
                                            ->last();

                                        $lastName = $lastProduction->User->name ?? 'Desconhecido';
                                        $company = $lastProduction->Company->name ?? 'Desconhecido';
                                        $status = $lastProduction->status ?? 'Desconhecido';

                                        $count = $production->count();

                                        $company = explode(' ', $company)[0];

                                        $lastName = explode(' ', $lastName);
                                        $lastName = $lastName[0] . ' ' . end($lastName);

                                        $user = [
                                            'lastUser' => $lastName,
                                            'countProd' => $count,
                                            'status' => $status,
                                            'company' => $company,
                                        ];
                                    }
                                }
                            @endphp

                            @php
                                $e = $this->needBlock($list);
                                $rowClass = $e['color'];
                                $block = $e['block'];
                                $command = (bool) ($e['command'] ?? false);
                                $production = $e['production'] ?? null;
                                $reason = $e['reason'] ?? null;
                                $stackProductionAvailable = \App\Support\SicodeRules::openCompanyStackProductionFor($list, Auth()->User(), $service->uuid);
                                $canDispatch = !$block || $command || $stackProductionAvailable;

                                if ($stackProductionAvailable) {
                                    $rowClass = '';
$production = $stackProductionAvailable;
                                    $reason = 'Disponivel na pilha da empresa para atribuicao individual.';
                                }

                                $assignee = $production?->User?->name ?: ($production?->Company?->name ?: 'Desconhecido');
                                $nameParts = explode(' ', $assignee);
                                $assigneeShort = count($nameParts) > 1 ? $nameParts[0] . ' ' . end($nameParts) : $nameParts[0];
                                $user = [
                                    'lastUser' => $assigneeShort,
                                    'countProd' => $e['count'] ?? 0,
                                    'company' => $assigneeShort,
                                ];
                            @endphp

                            <tr class="align-middle {{ $rowClass }}">
                                <td>
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled(!$canDispatch)>
                                </td>
                                {{-- @can('management')
                                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                                        </td>
                                    @endcan --}}
                                <td class="fw-bold copy-text" data-value="{{ $list->note }}">
                                    {{ $list->note }}
                                    <x-legal.note-demand-tags :note-id="$list->note_id ?? $list->id" :row-key="'dispatchs-analises-pre-main-'.$list->id" />
                                </td>

                                <td class="fw-light text-center">{{ $list->dt_status->format('d/m/Y') }}
                                </td>
                                <td class="fw-light text-center">{{ mb_strtoupper($list->numPedido) }}</td>
                                <td class="fw-light text-center">{{ $list->rubrica }}</td>
                                <td class="fw-light text-center">{{ $list->lexp }}</td>
                                <td class="fw-light text-center">{{ $list->material }}</td>
                                <td class="fw-light text-center">{{ $list->group1 ? $list->group1 : '_____' }}
                                </td>
                                <td class="fw-light text-center">{{ $list->group2 ? $list->group2 : '_____' }}
                                </td>

                                <td class="fw-light text-center" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Desenhos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($user)
                                        <span class="badge text-bg-dark">{{ $user['countProd'] }}</span><br>
                                        {{ $user['lastUser'] }}
                                    @else
                                        --
                                    @endif

                                </td>

                                @if ($list->type_note != 1)
                                    <td class="fw-light text-center">{{ $list->nstats }} </td>
                                @else
                                    <td class="fw-light text-center">{{ $list->centerjob }} <span class="text-danger"
                                            style="font-size: 8px;">{{ $list->nstats }}</span></td>
                                @endif
                                @php
                                    $prazoReal = $prazoRealFromDtCreated($list);
                                    $prazoRestante = $prazoReal === null ? null : 30 - $prazoReal;
                                @endphp
                                <td scope="col"
                                    class="text-center
                                    @if ($prazoRestante === null || $prazoRestante < 0) text-bg-secondary
                                    @elseif($prazoRestante >= 0 && $prazoRestante < 6)
                                    table-danger
                                    @elseif($prazoRestante >= 6 && $prazoRestante < 10)
                                        table-warning
                                    @else
                                        table-success @endif
                                "
                                    tabindex="0" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                    data-bs-placement="top" data-bs-title="Prazo Real"
                                    data-bs-content="
                                    <p>Prazo real contado em dias corridos desde a criação da Nota/OV.</p>
                                    <span class='fs-4 text-success'>&#9632;</span> 10> DIAS PARA VENCER <br>
                                    <span class='fs-4 text-warning'>&#9632;</span> 10< DIAS PARA VENCER <br>
                                    <span class='fs-4 text-danger'>&#9632;</span> 5< DIAS PARA VENCER <br>
                                    <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br>
                                    ">
                                    {{ $prazoReal ?? '--' }}
                                </td>


                                <td class="fw-light text-center">
                                    @if ($list->pze_parecer === 'Vencido')
                                        <span class="badge text-bg-danger">VENCIDO</span>
                                    @elseif ($list->pze_parecer === 'Não vencido')
                                        <span class="badge text-bg-success">EM PRAZO</span>
                                    @else
                                        <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                    @endif
                                </td>


                                <td class="fw-bold text-center">
                                    @if ($canDispatch)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', [{{ $list->id }}])"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-title="{{ $stackProductionAvailable ? 'Assumir/atribuir Nota/OV da pilha da empresa' : 'Despachar nota' }}"></i>
                                    @else
                                        <span style="font-size: 11px" data-bs-toggle="tooltip"
                                            data-bs-placement="top" data-bs-title="{{ $reason }}">{{ $assigneeShort }}</span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
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


    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-'.$service->uuid))


    {{-- END MODALS --}}

</div>
