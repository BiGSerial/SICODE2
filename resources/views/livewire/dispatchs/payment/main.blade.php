@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
@endphp
<div>

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

            @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'payments', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'payments', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'payments', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'payments', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'payments', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'payments'], key('removeAll'))
        </div>

        <div class="btn-group">
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

            <div class="mb-3 mx-1">
                <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                    data-bs-trigger="hover focus" data-bs-placement="right" data-bs-title="Exibir Apenas Notas D5"
                    data-bs-content="<p>Ao clicar, apenas as notas que possuem D5 estarão visíveis. </p> <p>A palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
                    <button type="button" class="btn btn-warning" wire:click.prevent="filterD5()">
                        Apenas D5
                        @if ($filter_d5)
                            <span class="badge text-bg-success">ON</span>
                        @else
                            <span class="badge text-bg-danger">OFF</span>
                        @endif
                    </button>
                </div>
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
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectall"
                                    wire:click="setSelectAll" @checked($this->checkAllSelect($lists))>
                            </th>
                            <th class="align-middle text-center">Nota</th>
                            <th class="align-middle text-center">Tipo</th>
                            <th class="align-middle text-center">Ordem</th>
                            <th class="align-middle text-center">MOA</th>
                            {{-- <th class="align-middle text-center">Status</th> --}}
                            <th class="align-middle text-center">OP30</th>
                            <th class="align-middle text-center">OP40</th>
                            <th class="align-middle text-center">OP50</th>
                            <th class="align-middle text-center">CentroTrab</th>
                            <th class="align-middle text-center">Empresa</th>
                            <th class="align-middle text-center">Município</th>
                            <th class="align-middle text-center">Final OP20</th>
                            <th class="align-middle text-center">Data Informe</th>

                            <th class="align-middle text-center">Ads</th>
                            <th class="align-middle text-center">Fiscalizado</th>
                            <th class="align-middle text-center">Data Vencimento</th>
                            <th class="align-middle text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $soma = 0;
                        @endphp
                        @foreach ($lists as $list)
                            @php

                                $eval = $this->needBlock($list);
                                $block = $eval['block'];
                                $rowClass = $eval['color'];
                                $production = $eval['production'] ?? null;
                                $command = $eval['command'];
                                $reason = $eval['reason'] ?? null;

                                if ($partial = $list->Partials && !$list->WorkForm ? $list->Partials->last() : null) {
                                    if (!($partial->allow && $partial->supervision && !$partial->payment)) {
                                        $partial = null;
                                    }
                                } else {
                                    $partial = null;
                                }

                                $five = $list->FiveNote;
                                $hasD5 = (bool) $five;
                                $d5BadgeClass = '';
                                $d5Msg = '';
                                if ($hasD5) {
                                    if ($five->is_supervisioned ?? false) {
                                        $d5BadgeClass = 'text-bg-success';
                                        $d5Msg = 'D5 Fiscalizada – liberar carta';
                                    } else {
                                        $d5BadgeClass = 'text-bg-primary';
                                        $d5Msg = 'Gerar D5 e reter carta';
                                    }
                                }

                                if ($partial) {
                                    $date = $partial?->supervision_at?->addDays(5);
                                } else {
                                    $date = $list->fimLancado;
                                }

                                $date = $list->fimLancado;

                                $daysLeft = Carbon::now()
                                    ->startOfDay()
                                    ->diffInDays(Carbon::parse($date)->startOfDay(), true);

                                if (Carbon::parse($date)->startOfDay() < Carbon::now()->startOfDay()) {
                                    $daysLeft = -$daysLeft;
                                }

                            @endphp
                            {{-- @dump($list->Productions) --}}

                            <tr class="align-middle text-center" wire:key="note-{{ $list->id }}">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary " type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled($block)>
                                </td>

                                <td class="fw-light fw-bold text-center {{ $rowClass }}">
                                    @if ($hasD5)
                                        <span class="badge {{ $d5BadgeClass }} fs-6" tabindex="0"
                                            data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="Nota com D5"
                                            data-bs-content="{{ $d5Msg }}">
                                            <span class="fw-bold">D5</span> {{ $list->note }}
                                        </span>
                                    @else
                                        {{ $list->note }}
                                    @endif
                                </td>

                                <td
                                    class="fw-light fw-bold text-center  @if ($partial) text-bg-warning @else text-bg-success @endif">
                                    {{ $partial ? 'PARCIAL' : 'TOTAL' }} </td>

                                <td class="text-center align-middle {{ $rowClass }}">
                                    @if ($list->WorkForm)
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @elseif ($partial)
                                        @foreach ($partial->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle fw-bold {{ $rowClass }}">
                                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty() && !$partial)
                                        {{-- @foreach ($list->WorkForm->Orders as $order)
                                            @php
                                                $soma += $order->moaberto;
                                            @endphp
                                            <p class="my-0 py-0">
                                                R$ {{ number_format($order->moaberto, 2, ',', '.') }}
                                            </p>
                                        @endforeach --}}
                                        @php
                                            $soma += $list->total_moaberto;
                                        @endphp
                                        <p class="my-0 py-0">
                                            R$ {{ number_format($list->total_moaberto, 2, ',', '.') }}
                                        </p>
                                    @elseif ($partial)
                                        @php
                                            $soma += $partial->value;
                                        @endphp
                                        <p class="my-0 py-0">
                                            R$ {{ number_format($partial->value, 2, ',', '.') }}
                                        </p>
                                    @endif

                                </td>
                                {{-- <td class="text-center align-middle">
                                    @if ($list->WorkForm->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->statusSist }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td> --}}

                                <td class="text-center align-middle {{ $rowClass }}">
                                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0030')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0030')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @elseif ($partial && $partial->Orders->isNotEmpty())
                                        @foreach ($partial->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0030')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0040')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0040')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0040')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @elseif ($partial && $partial->Orders->isNotEmpty())
                                        @foreach ($partial->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0040')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0040')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0050')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0050')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @elseif ($partial && $partial->Orders->isNotEmpty())
                                        @foreach ($partial->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0050')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0050')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle {{ $rowClass }}">
                                    @if ($list->WorkForm && $list->WorkForm->Orders->isNotEmpty())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->isNotEmpty() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @elseif ($partial && $partial->Orders->isNotEmpty())
                                        @foreach ($partial->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->isNotEmpty() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if ($list->WorkForm)
                                        {{ $list->WorkForm->Company ? $list->WorkForm->Company->name : '---' }}
                                    @elseif ($partial)
                                        {{ $list->Partials->last()->Company ? $list->Partials->last()->Company->name : '---' }}
                                    @endif
                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->lexp }}</td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->WorkForm ? $list->WorkForm->earliest_fim_real?->format('d/m/Y') : '---' }}
                                </td>
                                <td class="fw-light {{ $rowClass }}">
                                    @if ($list->WorkForm)
                                        {{ $list->WorkForm && $list->WorkForm->informed_at ? $list->WorkForm->informed_at->format('d/m/Y H:i:s') : $list->WorkForm->created_at->format('d/m/Y H:i:s') }}
                                    @elseif ($partial)
                                        {{ $partial->created_at?->format('d/m/Y H:i:s') }}
                                    @endif
                                </td>



                                @php
                                    if ($list->WorkForm?->Adsform) {
                                        $daysLeft = $list->WorkForm?->Adsform
                                            ? $list->WorkForm?->Adsform->created_at->diffInDays(Carbon::now(), true)
                                            : null;
                                    } elseif ($partial) {
                                        $daysLeft = $partial
                                            ? $partial->created_at->diffInDays(Carbon::now(), true)
                                            : null;
                                    } else {
                                        $daysLeft = null;
                                    }

                                    $prazoClass = '';

                                    if ($daysLeft) {
                                        if ($daysLeft && $daysLeft > 20) {
                                            $prazoClass = 'text-bg-danger';
                                        } elseif ($daysLeft && $daysLeft < 15) {
                                            $prazoClass = 'text-bg-success';
                                        } else {
                                            $prazoClass = 'text-bg-warning';
                                        }
                                    }
                                @endphp
                                <td scope="col"
                                    class="text-center text-center
                                    {{ $prazoClass ?? 'text-bg-info' }}"
                                    style="background-color: inherit;" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Prazo Pagamento"
                                    data-bs-content="
                            <p>A Data Corresponde a entrega da ADS <br>:</p>
                            <span class='fs-4 text-success'>&#9632;</span> > 15 DIAS PARA VENCER <br>
                            <span class='fs-4 text-warning'>&#9632;</span> <= 5 DIAS PARA VENCER <br>
                            <span class='fs-4 text-danger'>&#9632;</span> VENCIDO <br>
                            {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                            ">
                                    @if ($list->WorkForm?->Adsform)
                                        {{ $list->WorkForm->Adsform->created_at?->format('d/m/Y H:i:s') }}
                                    @else
                                        ----
                                    @endif
                                </td>


                                <td scope="col"
                                    class="text-center text-center
                                   text-bg-secondary
                                "
                                    style="background-color: inherit;" tabindex="0" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Prazo Pagamento"
                                    data-bs-content="
                            <p>A Data Corresponde 40 Parcial <br> para Parcial, corresponde a partir da data da fiscalização:</p>
                            <span class='fs-4 text-success'>&#9632;</span> >= 5 DIAS PARA VENCER <br>
                            <span class='fs-4 text-warning'>&#9632;</span> < 5 DIAS PARA VENCER <br>
                            <span class='fs-4 text-danger'>&#9632;</span> VENCIDO <br>
                            {{-- <span class='fs-4 text-secondary'>&#9632;</span> VENCIDO <br> --}}
                            ">
                                    {{ $date ? Carbon::parse($date)->format('d/m/Y') : '---' }}
                                </td>

                                @php
                                    $daysLeft = new DaysLeft($list);
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
                                <td scope="col" class="text-center {{ $rowClass }}">
                                    {{ $daysLeft->getLastDate() }}
                                </td>

                                @php
                                    if (isset($production->User->name)) {
                                        $name = explode(' ', $production->User->name);
                                        $name = $name[0] . ' ' . end($name);
                                    } else {
                                        $name = 'DESCONHECIDO';
                                    }
                                @endphp
                                <td class="fw-bold text-center {{ $rowClass }}" data-bs-toggle="tooltip"
                                    data-bs-placement="top" title="{{ $reason }}">
                                    @if (!$block || $command)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="get_single_note({{ $list->id }})"></i>
                                        @if ($command)
                                            <p style="font-size: 11px">{{ $name }}</p>
                                        @endif
                                    @else
                                        <span style="font-size: 11px">{{ $name }}</span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-dark align-middle">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-end">Total:</td>
                            <td class="fw-bold"> R$ {{ number_format($soma, 2, ',', '.') }}</td>
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
                            <td></td>
                        </tr>
                    </tfoot>
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
                <div class="px-3 pt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="multiSearchAnySituation"
                            wire:model="multi_search_any_situation">
                        <label class="form-check-label text-danger" for="multiSearchAnySituation">
                            Buscar notas em qualquer situação (modo de risco)
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Use apenas quando necessário. Este modo pode exibir notas fora do fluxo padrão e exige conferência manual antes do despacho.
                    </small>
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
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Tipo de Despacho</label>
                                <select class="form-select form-select-sm" aria-label="Small select example"
                                    wire:model="type">
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
                                        <input wire:model.bounce.500ms="search_user"
                                            class="form-control form-control-sm" type="text"
                                            placeholder="Digite um nome" aria-label="">
                                    </div>
                                    <div class="col">
                                        <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                        <select class="form-select form-select-sm" aria-label=""
                                            wire:model="user_s">

                                            @if ($user_l && $user_l->count())

                                                <option value="" selected>Selecione um Usuário</option>
                                                @foreach ($user_l->sortBy('name', SORT_LOCALE_STRING) as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option selected>Escolha uma Empresa Primeiro</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>

                                {{-- <div class="mb-3 ">
                                    <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                    <select class="form-select form-select-sm" aria-label="" wire:model="user_s">

                                        @if ($user_l && $user_l->count())
                                            <option value="" selected>Selecione um Usuário</option>
                                            @foreach ($user_l as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        @else
                                            <option selected>Escolha uma Empresa Primeiro</option>
                                        @endif
                                    </select>
                                </div> --}}


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
                                                @if ($this->type === '2')
                                                    <input wire:model.defer="additionalData.{{ $index }}"
                                                        class="form-control form-control-sm" type="text"
                                                        placeholder="Informe a DD" aria-label="">
                                                @endif

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




    {{-- END MODALS --}}



</div>
