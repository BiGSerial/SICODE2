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

            @livewire('components.filter.filter', ['myKey' => 'company', 'sendFilter' => '', 'model' => 'App\Models\Company', 'column' => 'id', 'filter' => 'Empreiteira', 'group_filter' => 'payments', 'values' => 'name', 'direction' => 'ASC', 'query' => ''], key('company'))
            @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'payments', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
            @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'payments', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
            @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'payments', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
            @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'payments', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
            @livewire('components.filter.remove-all', ['group_filter' => 'payments'], key('removeAll'))
        </div>



    </div>


    <div class="btn-group">
        <div class="mb-3 mx-1">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right"
                data-bs-title="Exibir Apenas Notas Nao Atribuidas"
                data-bs-content="<p>Ao clicar, todas as notas que nao contenham atribuiçao estará visível. Ocultando qualquer outra nota atribu[ida. </p> <p> A palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
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

        {{-- <div class="mb-3 mx-1">
            <div class="btn-group" role="group" aria-label="Basic example" tabindex="0" data-bs-toggle="popover"
                data-bs-trigger="hover focus" data-bs-placement="right" data-bs-title="Exibir Apenas Notas MMGD"
                data-bs-content="<p>Ao clicar, Apenas as notas de MMGD estarão visíveis. </p> <p>A palavra ON significa que o filtro está ativo, e OFF inativo. Basta clicar novamente para desativar o filtro.</p>">
                <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                    wire:click.prevent="filterMMGD()">
                    Somente MMGD
                    @if ($assigned_mmgd)
                        <span class="badge text-bg-success">ON</span>
                    @else
                        <span class="badge text-bg-danger">OFF</span>
                    @endif
                </button>

            </div>
        </div> --}}
    </div>

    <div class="row">

        @if (!$lists->count())
            <div class="col-6">
                {{-- @livewire('components.manualnote.manualnote', ['service' => $service->uuid]) --}}
            </div>
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
                <h4 class="text-center">SEM NOTAS PARA EXIBIR EM {{ $service->service }}</h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-secondary">LISTA PARA {{ mb_strtoupper($service->service) }}
                @if ($service->Status->count())
                    @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                        ({{ $sts->value }})
                    @endforeach
                @endif
            </h4>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th class="align-middle text-center">Nota</th>
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

                            <th class="align-middle text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $soma = 0;
                        @endphp
                        @foreach ($lists as $list)
                            @php
                                $block = false;

                                if ($production = $this->hasProduction($list)) {
                                    $block = true;
                                }

                                $daysLeft = $this->deadline($list);

                            @endphp
                            {{-- @dump($list->Productions) --}}

                            <tr
                                class="align-middle text-center
                                @if ($block) @if ($production->status == 1)
                                    table-warning
                                    @elseif ($production->status == 2)
                                    table-primary
                                    @elseif ($production->status == 5)
                                    table-success
                                    @else
                                    table-primary @endif
                                @endif">

                                <td class="fw-light fw-bold text-center">{{ $list->note }} </td>

                                <td class="text-center align-middle">
                                    @if ($list->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->ordem }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle fw-bold">
                                    @if ($list->WorkForm->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            @php
                                                $soma += $order->moaberto;
                                            @endphp
                                            <p class="my-0 py-0">
                                                R$ {{ number_format($order->moaberto, 2, ',', '.') }}
                                            </p>
                                        @endforeach
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

                                <td class="text-center align-middle">
                                    @if ($list->WorkForm->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0030')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0030')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->WorkForm->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0040')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0040')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->WorkForm->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0050')->first()->status) ? explode(' ', $order->Operations->where('operacao', '0050')->first()->status)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>
                                <td class="text-center align-middle">
                                    @if ($list->WorkForm->Orders->count())
                                        @foreach ($list->WorkForm->Orders as $order)
                                            <p class="my-0 py-0">
                                                {{ $order->Operations->count() && isset($order->Operations->where('operacao', '0010')->first()->cenTrab) ? explode(' ', $order->Operations->where('operacao', '0010')->first()->cenTrab)[0] : '---' }}
                                            </p>
                                        @endforeach
                                    @endif

                                </td>

                                <td class="fw-light text-center">
                                    {{ $list->WorkForm ? $list->WorkForm->Company->name : '---' }}
                                </td>

                                <td class="fw-light text-center">{{ $list->lexp }}</td>

                                <td class="fw-light text-center">
                                    {{ $list->WorkForm ? date('d/m/Y', strToTime($list->WorkForm->date)) : '---' }}
                                </td>
                                <td class="fw-light">
                                    {{ $list->WorkForm ? date('d/m/Y H:i:s', strToTime($list->WorkForm->informed_at)) : '---' }}
                                </td>

                                <td scope="col"
                                    class="text-center text-center
                                    @if ($daysLeft < 0) table-dark
                                    @elseif($daysLeft >= 0 && $daysLeft < 3)
                                    table-danger
                                    @elseif($daysLeft >= 3 && $daysLeft < 6)
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
                                    {{ $daysLeft }}
                                </td>

                                <td class="fw-bold text-center">
                                    @if (!$block)
                                        <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                            style="cursor: pointer;"
                                            wire:click.prevent="to_accompany({{ $list->id }})"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-custom-class="custom-tooltip"
                                            data-bs-title="Enviar para Acompanhamento"></i>
                                    @else
                                        @php
                                            if (isset($production->User->name)) {
                                                $name = explode(' ', $production->User->name);
                                                $name = $name[0] . ' ' . end($name);
                                            } else {
                                                $name = 'DESCONHECIDO';
                                            }
                                        @endphp
                                        <span style="font-size: 11px">{{ $name }}</span>
                                    @endif

                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-dark align-middle">
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
