@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
    use App\Models\Production;
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
        <div class="mb-3 col-3">
            <label for="search" class="form-label">Período:</label>
            <select class="form-control border border-2 border-secondary" aria-label="Seleção período"
                wire:model="date_prod_s">
                <option value="" selected>Selecione um Período</option>
                @if ($date_prod_l)
                    @foreach ($date_prod_l as $date_prod)
                        <option value="{{ $date_prod->mes_ano }}">
                            {{ $meses[date('n', strtotime($date_prod->mes_ano))] }}
                            {{ date('Y', strtotime($date_prod->mes_ano)) }}</option>
                    @endforeach
                @endif

            </select>
        </div>
        {{-- <div class="btn-group mb-3">
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
        </div> --}}
    </div>

    @can('superadm')
        <div class="row justify-content-start">
            <div class="col-2">
                <input wire:model.bounce.2s="user_search" type="email"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar usuario">
            </div>

            <div class="col-3 mb-3">
                <div class="input-group">
                    <select class="form-select border border-2 border-secondary" aria-label="Default select example"
                        wire:model.defer="user_s">
                        @if ($user_l->count())
                            <option value="">Selecione Usuario</option>
                            @foreach ($user_l as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        @endif
                    </select>


                    <button class="btn btn-primary " wire:click.prevent="visualizar" type="button">
                        Visualizar</button>
                </div>
            </div>
        </div>
    @endcan

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
                <h4 class="text-center">VOCÊ NAO TEM REGISTRO DE TAREFAS PARA
                    <strong>{{ mb_strtoupper($service->service) }}</strong>
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-success">MEU HISTÓRICO - {{ mb_strtoupper($service->service) }}
                @if ($service->Status->count())
                    @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                        ({{ $sts->value }})
                    @endforeach
                @endif
            </h4>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark        ">
                            <tr>
                                <th scope="col" class="fw-bold">Note</th>
                                <th scope="col" class="fw-bold"></th>
                                <th scope="col" class="fw-bold"></th>
                                <th scope="col" class="fw-bold">Files</th>
                                <th scope="col" class="fw-bold">Rubrica</th>
                                <th scope="col" class="fw-bold">Municipio</th>
                                <th scope="col" class="fw-bold">Grupo</th>
                                <th scope="col" class="fw-bold">Descrição</th>
                                <th scope="col" class="fw-bold">Iniciado</th>
                                <th scope="col" class="fw-bold">Concluído</th>
                                <th scope="col" class="fw-bold">Tempo</th>
                                <th scope="col" class="fw-bold">Parado</th>
                                <th scope="col" class="fw-bold">Resultado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $progresso = Production::whereIn('note_id', $lists->pluck('note_id'))
                                    ->where('completed', true)
                                    ->get();
                            @endphp
                            @foreach ($lists as $list)
                                <tr
                                    class="align-middle
                            @if (Carbon::parse($list->completed_at)->diffInDays(Carbon::now()) > 1 &&
                                    $list->completed &&
                                    $list->status_note == $list->Note->nstats) table-warning @endif
                        ">
                                    <td class="fw-bold">
                                        {{ $list->Note->note }}
                                        <span class="copy-text" data-value="{{ $list->Note->note }}"
                                            style="cursor: pointer;"> <i class="ri-file-copy-line"></i></span>
                                    </td>
                                    <td>
                                        @if (!$list->confirmed)
                                            <i class="ri-rest-time-line text-primary fs-4"></i>
                                        @else
                                            <i class="ri-checkbox-circle-line text-success fs-4"></i>
                                        @endif

                                        @if ($list->transferred)
                                            <i class="ri-exchange-fill text-warning fs-4"></i>
                                        @endif

                                    </td>
                                    @php
                                        $count = $progresso
                                            ->where('note_id', $list->note_id)
                                            ->where('status_note', '>', $list->status_note)
                                            ->count();
                                    @endphp
                                    <td class="fw-light">
                                        @if ($count)
                                            <span data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Existe Status Superior Confirmado">
                                                <i class="ri-file-list-3-line text-danger fs-4"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                        <x-files.select-download-list :files='$list->Note->Files' />
                                    <td class="fw-light">{{ $list->Note->rubrica }}</td>
                                    <td class="fw-light">{{ $list->Note->lexp }}</td>
                                    <td class="fw-light">{{ $list->Note->group1 }}</td>
                                    <td class="fw-light">{{ $list->Note->material }}</td>
                                    <td class="fw-light">{{ date('d/m/Y H:i', strToTime($list->att_at)) }}</td>
                                    <td class="fw-light">
                                        {{ Carbon::parse($list->completed_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="fw-light">
                                        {{ Carbon::parse($list->completed_at)->diffForHumans(Carbon::parse($list->att_at)->format('Y-m-d H:i')) }}
                                    </td>
                                    <td class="fw-light">
                                        {{ CarbonInterval::seconds($list->stopped)->cascade()->forHumans(['short' => true]) }}
                                    </td>
                                    <td class="fs-6">
                                        @livewire('components.historic.analises', ['production_id' => $list->id], key('hist-' . $list->id))
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
                    @livewire('services.analises.forms.analise')
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

    {{-- <div wire:init="checkOpen"></div> --}}

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
