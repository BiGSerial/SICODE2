@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="row justify-content-between">
        <div class="mb-3 col-3">
            <label for="search" class="form-label">Buscar</label>
            <input wire:model.bounce.2s="search" type="email" class="form-control border border-2 border-secondary"
                id="search" placeholder="Buscar">
        </div>
        <div class="btn-group mb-3">
            <div class="dropdown mx-1">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    Rubrica
                    @if (count($rubrica_s))
                        <span class="badge text-bg-light">{{ count($rubrica_s) }}</span>
                    @endif

                </button>

                <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                    <form wire:submit.prevent="filter_save">
                        @if (isset($rubrica_l) && $rubrica_l->count() > 0)
                            @foreach ($rubrica_l as $rubrica)
                                @if ($rubrica->rubrica)
                                    <div class="dropdown-item">
                                        <input type="checkbox" wire:model.defer="rubrica_s"
                                            wire:key="{{ $rubrica->rubrica }}" value="{{ $rubrica->rubrica }}">
                                        <label for="opcao1">{{ $rubrica->rubrica }}</label>
                                    </div>
                                @endif
                            @endforeach

                        @endif


                    </form>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary mx-1" wire:click.prevent="filter_save"><i class="ri-filter-fill"></i>
                        Aplicar Filtro</button>
                    <button class="btn btn-primary mx-1" wire:click.prevent="filter_clean"><i
                            class="ri-filter-off-fill"></i> Limpar Filtro</button>

                </div>
            </div>


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
                @if ($last_update)
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
            {{-- <div class="card-body"> --}}
            <div class="table-responsive">
                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark        ">
                        <tr class="text-center">

                            <th scope="col" class="fw-bold">Note</th>

                            <th scope="col" class="fw-bold">Criado Em</th>
                            <th scope="col" class="fw-bold">numPedido</th>
                            <th scope="col" class="fw-bold">Rubrica</th>
                            <th scope="col" class="fw-bold">Municipio</th>
                            @can('management')
                                <th scope="col" class="fw-bold">Grp1</th>
                            @endcan
                            <th scope="col" class="fw-bold">Grp2</th>
                            @can('management')
                                <th scope="col" class="fw-bold">Descrição</th>
                            @endcan



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
                                // 1. Inicializa
                                $block = 0;
                                // Coleta todas as produções deste serviço
                                $productions = $list->Productions->where('service_id', $this->service->uuid);
                                // Pega a última produção (ou null)
                                $lastProduction = $productions->last();
                                $user = [];

                                // Helper para formatar dados do usuário
                                $getUserInfo = function ($production) use ($productions) {
                                    $fullName = $production->User->name ?? 'Desconhecido';
                                    $company = $production->Company->name ?? 'Desconhecido';

                                    $nameParts = explode(' ', $fullName);
                                    $shortName =
                                        count($nameParts) > 1 ? $nameParts[0] . ' ' . end($nameParts) : $nameParts[0];

                                    return [
                                        'lastUser' => $shortName,
                                        'countProd' => $productions->count(),
                                        'status' => $production->status ?? 'Desconhecido',
                                        'company' => explode(' ', $company)[0],
                                    ];
                                };

                                // 2. Se o dt_status mudou desde a última produção, libera (block = 0)
                                if ($lastProduction && $lastProduction->dt_note != $list->dt_status) {
                                    $user = $getUserInfo($lastProduction);
                                }
                                // 3. Caso contrário, avalia os estados de completed/confirmed
                                elseif ($lastProduction && !$lastProduction->completed && !$lastProduction->confirmed) {
                                    $block = 1;
                                    $user = $getUserInfo($lastProduction);
                                } elseif (
                                    $lastProduction &&
                                    $lastProduction->completed &&
                                    !$lastProduction->confirmed
                                ) {
                                    $block = 2;
                                    $user = $getUserInfo($lastProduction);
                                } elseif ($lastProduction && $lastProduction->completed && $lastProduction->confirmed) {
                                    $block = 3;
                                    $user = $getUserInfo($lastProduction);
                                } elseif ($lastProduction && $lastProduction->dt_note === $list->dt_status) {
                                    $block = 3;
                                    $user = $getUserInfo($lastProduction);
                                }
                            @endphp


                            {{-- @dump($list->Productions) --}}
                            <tr
                                class="align-middle
                                    @if ($block == 1 && $user['lastUser'] != 'Desconhecido') table-primary
                                    @elseif($block == 1 && $user['lastUser'] == 'Desconhecido')
                                        table-warning
                                    @elseif($block == 2)
                                        table-success
                                    @elseif($block == 3)
                                        table-danger @endif
                                    ">

                                @if (Auth()->User()->management ||
                                        Auth()->User()->superadm ||
                                        ($lastProduction && $lastProduction->user_id === Auth()->User()->id))
                                    <td class="fw-bold copy-text" data-value="{{ $list->note }}">
                                        {{ $list->note }}
                                    </td>
                                @else
                                    <td></td>
                                @endif


                                <td class="fw-light">{{ date('d/m/Y', strToTime($list->dt_created)) }}</td>
                                <td class="fw-light">{{ mb_strtoupper($list->numPedido) }}</td>
                                <td class="fw-light">{{ $list->rubrica }}</td>
                                <td class="fw-light">{{ $list->lexp }}</td>
                                @can('management')
                                    <td class="fw-light">{{ $list->group1 }}</td>
                                @endcan

                                <td class="fw-light">{{ $list->group2 }}</td>


                                @can('management')
                                    <td class="fw-light">{{ $list->material }}</td>
                                @endcan



                                <td class="fw-light">{{ $list->nstats }}</td>
                                {{-- <td class="fw-light">{{ $list->pze }}</td> --}}
                                <td class="fw-light">{{ date('d/m/Y H:i:s', strToTime($list->dt_status)) }}
                                </td>
                                <td scope="col"
                                    class="text-center
                                        @if ($list->days_left < 0) text-bg-secondary
                                        @elseif($list->days_left >= 0 && $list->days_left < 6)
                                        table-danger
                                        @elseif($list->days_left >= 6 && $list->days_left < 10)
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
                                    {{ 30 - $list->days_left }}
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
                                        <span style="font-size: 11px">{{ $user['lastUser'] }}</span>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- </div> --}}
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
