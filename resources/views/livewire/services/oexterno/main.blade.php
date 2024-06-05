@php
    use Carbon\Carbon;
    use App\Helpers\DaysLeft;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    {{-- START SearchBar and Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-1">
                    <select name="" id="" class="form-select border border-secondary" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-2">
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar"
                        wire:model.debounce.2s="search">
                </div>

                <div class="col-3">

                </div>

                <div class="col-6 d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'oexterno', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'oexterno', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'oexterno', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('removeAll'))
                </div>
            </div>
        </div>
    </div>
    {{-- END SearchBar and Filters --}}{{-- START SearchBar and Filters --}}



    <div class="row">

        @if (!$lists->count())
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
                <h4 class="text-center">{{ $service->service }} LISTA PARA PROTOCOLAR</h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-secondary">LISTA PARA {{ mb_strtoupper($service->service) }}

            </h4>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark        ">
                        <tr>

                            <th scope="col" class="fw-bold">Note</th>


                            <th scope="col" class="fw-bold">Files</th>
                            <th scope="col" class="fw-bold">numPedido</th>
                            <th scope="col" class="fw-bold">Rubrica</th>
                            <th scope="col" class="fw-bold">Municipio</th>
                            <th scope="col" class="fw-bold">Grp1</th>
                            <th scope="col" class="fw-bold">Grp2</th>

                            <th scope="col" class="fw-bold">Descrição</th>


                            <th scope="col" class="fw-bold">Status</th>
                            {{-- <th scope="col" class="fw-bold">Pze</th> --}}
                            <th scope="col" class="fw-bold">Data</th>
                            <th scope="col" class="fw-bold">Prazo Real</th>
                            <th scope="col" class="fw-bold">Situação</th>
                            <th scope="col" class="fw-bold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $block = false;

                                // if ($list->Productions->count()) {
                                //     $block = $list->Productions
                                //         ->where('status_note', $list->nstats)
                                //         ->Where('dt_note', $list->dt_status)
                                //         // ->where(function ($q) use ($list) {
                                //         //     return $q->where('completed', false)
                                //         //         ->orWhere('dt_note', $list->dt_status);
                                //         // })
                                //         ->first();
                                // }

                                $count = $list->Productions
                                    ->where('service_id', $service->uuid)
                                    ->where('noinconsistency', false);

                                if ($count->count()) {
                                    if ($count->last()->dt_note == $list->dt_status || !$count->last()->confirmed) {
                                        $block = true;
                                    }
                                    if (isset($count->last()->User->name)) {
                                        $production = $count->last();

                                        $lastUser = $count->last()->User->name;

                                        $lastUser = explode(' ', $lastUser);
                                        $lastUser = $lastUser[0] . ' ' . end($lastUser);
                                    } else {
                                        $lastUser = 'DESCONHECIDO';
                                    }

                                    // $chave = array_search($list->id, $selected);

                                    // if ($chave !== false) {
                                    //     unset($selected[$chave]);
                                    //     $selected = $selected;
                                    // }
                                }

                                $daysleft = (new DaysLeft($list))->getDaysLeft();

                            @endphp
                            {{-- @dump($list->Productions) --}}
                            <tr
                                class="align-middle
                        @if ($block) @if ($production->status == 1)
                            table-warning
                            @elseif ($production->status == 2)
                            table-primary
                            @elseif ($production->status == 5 && !$production->confirmed)
                            table-success
                            @elseif ($production->status == 5 && $production->confirmed)
                            table-danger
                            @else
                            table-primary @endif @endif">

                                <td class="fw-bold copy-text" data-value="{{ $list->note }}">
                                    {{ $list->note }}
                                </td>


                                <td class="fw-light">{{ date('d/m/Y', strToTime($list->dt_created)) }}</td>
                                <td class="fw-light">{{ mb_strtoupper($list->numPedido) }}</td>
                                <td class="fw-light">{{ $list->rubrica }}</td>
                                <td class="fw-light">{{ $list->lexp }}</td>
                                <td class="fw-light">{{ $list->group1 }}</td>
                                <td class="fw-light">{{ $list->group2 }}</td>

                                <td class="fw-light">{{ $list->material }}</td>


                                <td class="fw-light">{{ $list->nstats }}</td>
                                {{-- <td class="fw-light">{{ $list->pze }}</td> --}}
                                <td class="fw-light">{{ date('d/m/Y H:i:s', strToTime($list->dt_status)) }}
                                </td>
                                <td scope="col"
                                    class="text-center
                                    @if ($daysleft < 0) text-bg-secondary
                                    @elseif($daysleft >= 0 && $daysleft < 6)
                                    table-danger
                                    @elseif($daysleft >= 6 && $daysleft < 10)
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
                                    {{ 30 - $daysleft }}
                                </td>
                                <td class="fw-light">
                                    @if ($list->pze_parecer === 'Vencido')
                                        <span class="badge text-bg-danger">VENCIDO</span>
                                    @elseif ($list->pze_parecer === 'Não vencido')
                                        <span class="badge text-bg-success">EM PRAZO</span>
                                    @else
                                        <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                    @endif
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
                                        {{-- @php
                                            if (isset($block->User->name)) {
                                                $name = explode(' ', $block->User->name);
                                                $name = $name[0] . ' ' . substr(end($name), 0, 1);
                                            } else {
                                                $name = 'DESCONHECIDO';
                                            }
                                        @endphp --}}
                                        <span style="font-size: 11px">{{ $lastUser }}</span>
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
