@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
@endphp
<div class="historic-page">
    {{-- Carrega o Loading da página --}}
    <x-show-loading />
    <style>
        .historic-page {
            --hist-bg: #f4f7fb;
            --hist-surface: #ffffff;
            --hist-border: #dde5ef;
            background: radial-gradient(circle at 10% 0%, #dcfce7, transparent 35%),
                radial-gradient(circle at 90% 10%, #dbeafe, transparent 30%),
                var(--hist-bg);
            padding: 1rem 0 1.5rem;
        }

        .historic-header {
            background: linear-gradient(120deg, #0f172a, #0f766e);
            color: #f8fafc;
            border-radius: 0.6rem;
            padding: 1rem 1.2rem;
            margin-bottom: 1rem;
        }

        .historic-filters {
            background: var(--hist-surface);
            border: 1px solid var(--hist-border);
            border-radius: 0.55rem;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
        }

        .historic-table-card {
            background: var(--hist-surface);
            border: 1px solid var(--hist-border);
            border-radius: 0.6rem;
            overflow: hidden;
        }
    </style>

    <div class="historic-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold">HISTÓRICO DE ARQUIVOS - {{ mb_strtoupper($service->service) }}</h5>
        <small>Filtros rápidos e revisão versionada por produção</small>
    </div>

    <div class="historic-filters">
        <div class="row justify-content-between g-2">
        <div class="mb-1 col-12 col-md-4 col-lg-3">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="text" class="form-control border border-secondary"
                    id="search" placeholder="Buscar (aceita múltiplos por espaço, vírgula ou quebra de linha)">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#multi_search_modal"
                    type="button" title="Busca múltipla">
                    <i class="ri-file-copy-line"></i>
                </button>
            </div>
            <small class="text-muted">Use o ícone para colar/copiar múltiplos registros.</small>
        </div>
        <div class="mb-1 col-12 col-md-4 col-lg-3">
            <label for="file_search" class="form-label">Arquivo</label>
            <input wire:model.bounce.2s="file_search" type="text" class="form-control border border-secondary"
                id="file_search" placeholder="Nome do arquivo">
        </div>
        <div class="mb-1 col-12 col-md-4 col-lg-3">
            <label for="search" class="form-label">Período:</label>
            <select class="form-control border border-secondary" aria-label="Seleção período"
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
        <div class="mb-1 col-12 col-md-4 col-lg-3">
            <label for="date_field" class="form-label">Data de referência</label>
            <select id="date_field" class="form-control border border-secondary" wire:model="date_field">
                <option value="completed_at">Conclusão</option>
                <option value="att_at">Início</option>
                <option value="dispatch_at">Despacho</option>
            </select>
        </div>
        <div class="mb-1 col-12 col-md-3 col-lg-2">
            <label for="date_from" class="form-label">Data inicial</label>
            <input id="date_from" type="date" class="form-control border border-secondary" wire:model="date_from">
        </div>
        <div class="mb-1 col-12 col-md-3 col-lg-2">
            <label for="date_to" class="form-label">Data final</label>
            <input id="date_to" type="date" class="form-control border border-secondary" wire:model="date_to">
        </div>
        <div class="mb-1 col-12 col-md-6 col-lg-4 d-flex align-items-end gap-2">
            @if (count($multi_search_terms ?? []))
                <span class="badge text-bg-primary">Busca múltipla: {{ count($multi_search_terms ?? []) }}</span>
            @endif
            <button class="btn btn-outline-secondary" type="button" wire:click="clearDateFilters">Limpar datas</button>
            @if (count($multi_search_terms ?? []))
                <button class="btn btn-outline-danger" type="button" wire:click="clearMultiSearch">Limpar múltipla</button>
            @endif
        </div>
        </div>
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
    <div class="historic-table-card card border-0">

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
                                    <td class="fw-light">
                                        @if ((int) $list->higher_confirmed_count > 0)
                                            <span data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="Existe Status Superior Confirmado">
                                                <i class="ri-file-list-3-line text-danger fs-4"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        {{-- Componente para gerar a lista de arquivos, precisa do array de Arquivos --}}
                                        <div class="d-flex align-items-center gap-2">
                                            <x-files.select-download-list :files='$list->Note->Files' :latest-only="true" />
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                onclick="Livewire.emit('openFileRevisionModal', {{ $list->id }}, '{{ $service->uuid }}')">
                                                <i class="ri-upload-cloud-2-line"></i> Revisar
                                            </button>
                                        </div>
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
                                        @if ($list->Analise?->conclusion)
                                            <a href="#" class="link-secondary fw-bold"
                                                onclick="event.preventDefault(); Livewire.emit('openHistoricAnalise', {{ $list->id }})">
                                                {{ $list->Analise->conclusion }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif


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

    <div wire:ignore.self class="modal fade" id="multi_search_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-bg-primary">
                    <h5 class="modal-title">Busca múltipla de registros</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="multi_search_input" class="form-label">
                        Informe notas separadas por espaço, vírgula, ponto e vírgula ou quebra de linha
                    </label>
                    <textarea id="multi_search_input" rows="6" class="form-control"
                        wire:model.defer="multi_search_input"
                        placeholder="Ex: 30001234&#10;30001235&#10;30001236"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearMultiSearch">Limpar</button>
                    <button type="button" class="btn btn-primary" wire:click="applyMultiSearch" data-bs-dismiss="modal">
                        Aplicar busca
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- <div wire:init="checkOpen"></div> --}}

    {{-- Singletons: um único componente por página para evitar N+1 de Livewire no loop --}}
    @livewire('services.historic.file-revision-modal', ['isSingleton' => true])
    @livewire('components.historic.analises', ['isSingleton' => true])

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
