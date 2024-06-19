@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Notestatus;
    use App\Models\Production;
    use App\Custom\Viabilitiesstatus;
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
                <h4 class="text-center">NENHUM PROJETO SEU EM VIABILIDADE
                </h4>
            </div>
        @else
            <h4 class="card-header fw-bold text-bg-success">MEUS PROJETOS EM VIABILIDAE
            </h4>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark        ">
                            <tr>
                                <th scope="col" class="fw-bold">Note</th>
                                <th scope="col" class="fw-bold">Files</th>
                                <th scope="col" class="fw-bold">Rubrica</th>
                                <th scope="col" class="fw-bold">Municipio</th>
                                <th scope="col" class="fw-bold">Levantado em</th>
                                <th scope="col" class="fw-bold">Eviado Viabilidade</th>
                                <th scope="col" class="fw-bold">Contratante</th>
                                <th scope="col" class="fw-bold">Empreiteira</th>
                                <th scope="col" class="fw-bold">Status</th>
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
                                        @if ($list->d5)
                                            <span class="badge text-bg-primary">RI</span>
                                        @endif
                                        <span class="copy-text" data-value="{{ $list->Note->note }}"
                                            style="cursor: pointer;"> <i class="ri-file-copy-line"></i></span>
                                    </td>

                                    <td class="align-middle">
                                        {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                        <x-files.select-download-list :files='$list->Note->Files' />
                                    <td class="fw-light">{{ $list->Note->rubrica }}</td>
                                    <td class="fw-light">{{ $list->Note->lexp }}</td>
                                    <td class="fw-light">
                                        {{ Carbon::parse($list->completed_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="fw-light">
                                        {{ $list->Note->Viabilities->count() ? Carbon::parse($list->Note->Viabilities->last()->sended_at)->format('d/m/Y H:i') : '--' }}
                                    </td>
                                    <td class="fw-light">
                                        @if ($list->Note->Viabilities->count())
                                            {{ $list->Note->Viabilities->last()->User ? $list->Note->Viabilities->last()->User->name : 'Desconhecido' }}
                                        @endif

                                    </td>
                                    <td class="fw-light">
                                        @if ($list->Note->Viabilities->count())
                                            {{ $list->Note->Viabilities->last()->Company ? $list->Note->Viabilities->last()->Company->name : 'Desconhecido' }}
                                        @endif

                                    </td>
                                    <td class="fw-light">
                                        @if ($list->Note->Viabilities->count())
                                            <span
                                                class="badge {{ Viabilitiesstatus::status($list->Note->Viabilities->last()->status)->colorbg }}">{{ Viabilitiesstatus::status($list->Note->Viabilities->last()->status)->status }}</span>
                                        @endif

                                    </td>
                                    {{-- <td class="fw-light">
                                        {{ CarbonInterval::seconds($list->stopped)->cascade()->forHumans(['short' => true]) }}
                                    </td>
                                    <td class="fs-6">
                                        @livewire('components.historic.analises', ['production_id' => $list->id], key('hist-' . $list->id))
                                    </td> --}}
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
