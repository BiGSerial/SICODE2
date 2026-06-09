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
            --hist-ink: #1f2933;
            --hist-muted: #6b7280;
            background: radial-gradient(circle at 10% 0%, #dcfce7, transparent 35%),
                radial-gradient(circle at 90% 10%, #dbeafe, transparent 30%),
                var(--hist-bg);
            padding: 1.5rem 0;
        }

        .historic-header {
            background: linear-gradient(120deg, #0f172a, #198754 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .historic-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .historic-header .meta {
            color: rgba(248, 250, 252, 0.78);
            font-size: 0.95rem;
        }

        .historic-header .hero-count {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
        }

        .historic-filters .filter-card {
            background: var(--hist-surface);
            border: 1px solid var(--hist-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .historic-filters .filter-card h6 {
            color: var(--hist-muted);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .historic-summary {
            background: var(--hist-surface);
            border: 1px solid var(--hist-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .historic-summary .summary-item {
            color: var(--hist-muted);
            font-size: 0.92rem;
        }

        .historic-summary .summary-item strong {
            color: var(--hist-ink);
        }

        .historic-table-card {
            background: var(--hist-surface);
            border: 1px solid var(--hist-border);
            border-radius: 0.9rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .historic-table-header {
            background: var(--bs-success);
            border-bottom: 1px solid var(--bs-success);
            color: #ffffff;
            padding: 1rem 1.25rem;
        }

        .historic-table-title {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.2;
            margin: 0;
        }

        .historic-table-subtitle {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.82rem;
            margin-top: 0.2rem;
        }

        .historic-table-card .table {
            margin-bottom: 0;
        }

        .historic-table-card .table thead th {
            font-size: 0.75rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .historic-table-card .table tbody td {
            font-size: 0.92rem;
        }

        @media (max-width: 991px) {
            .historic-header {
                padding: 1.25rem;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="historic-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="meta text-uppercase">Histórico de atividades</div>
                <h2>{{ mb_strtoupper($service->service) }}</h2>
                <div class="meta mt-1">Consulta de arquivos e produções concluídas</div>
            </div>
            <div class="d-flex align-items-center gap-4 text-lg-end">
                @if ($service->Status->count())
                    <div>
                        <div class="meta">Status do serviço</div>
                        <strong>
                            @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                ({{ $sts->value }})
                            @endforeach
                        </strong>
                    </div>
                @endif
                <div>
                    <div class="meta">Registros</div>
                    <div class="hero-count">{{ $lists->total() }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 historic-filters mb-3">
            <div class="col-12 col-xl-5">
                <div class="filter-card">
                    <h6>Pesquisa</h6>
                    <div class="row g-2">
                        <div class="col-12 col-sm-3">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="perPage"
                                    id="historicPerPage">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="500">500</option>
                                </select>
                                <label for="historicPerPage">Por página</label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-9">
                            <div class="input-group">
                                <div class="form-floating">
                                    <input wire:model.bounce.2s="search" type="text"
                                        class="form-control border border-secondary" id="historicSearch"
                                        placeholder="Buscar nota ou descrição">
                                    <label for="historicSearch">Buscar nota ou descrição</label>
                                </div>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal"
                                    data-bs-target="#multi_search_modal" type="button" title="Busca múltipla">
                                    <i class="ri-file-copy-line"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <input wire:model.bounce.2s="file_search" type="text"
                                    class="form-control border border-secondary" id="file_search"
                                    placeholder="Nome do arquivo">
                                <label for="file_search">Nome do arquivo</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="filter-card">
                    <h6>Período</h6>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="date_prod_s"
                                    id="historicPeriod">
                                    <option value="">Selecione um período</option>
                                    @foreach ($date_prod_l ?? [] as $date_prod)
                                        <option value="{{ $date_prod->mes_ano }}">
                                            {{ $meses[date('n', strtotime($date_prod->mes_ano))] }}
                                            {{ date('Y', strtotime($date_prod->mes_ano)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="historicPeriod">Mês de conclusão</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <select id="date_field" class="form-select border border-secondary"
                                    wire:model="date_field">
                                    <option value="completed_at">Conclusão</option>
                                    <option value="att_at">Início</option>
                                    <option value="dispatch_at">Despacho</option>
                                </select>
                                <label for="date_field">Data de referência</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input id="date_from" type="date" class="form-control border border-secondary"
                                    wire:model="date_from">
                                <label for="date_from">Data inicial</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-floating">
                                <input id="date_to" type="date" class="form-control border border-secondary"
                                    wire:model="date_to">
                                <label for="date_to">Data final</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-2">
                <div class="filter-card d-flex flex-column">
                    <h6>Ações</h6>
                    <div class="d-grid gap-2 mt-auto">
                        @if (count($multi_search_terms ?? []))
                            <span class="badge text-bg-primary">
                                Busca múltipla: {{ count($multi_search_terms ?? []) }}
                            </span>
                        @endif
                        <button class="btn btn-outline-secondary" type="button" wire:click="clearDateFilters">
                            <i class="ri-calendar-close-line me-1"></i>Limpar datas
                        </button>
                        @if (count($multi_search_terms ?? []))
                            <button class="btn btn-outline-danger" type="button" wire:click="clearMultiSearch">
                                <i class="ri-close-circle-line me-1"></i>Limpar múltipla
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @can('superadm')
            <div class="row g-3 historic-filters mb-3">
                <div class="col-12">
                    <div class="filter-card">
                        <h6>Visão administrativa</h6>
                        <div class="row g-2">
                            <div class="col-12 col-lg-5">
                                <input wire:model.bounce.2s="user_search" type="text"
                                    class="form-control border border-secondary" id="historicUserSearch"
                                    placeholder="Buscar usuário">
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="input-group">
                                    <select class="form-select border border-secondary" wire:model.defer="user_s">
                                        <option value="">Selecione o usuário</option>
                                        @foreach ($user_l as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-success" wire:click.prevent="visualizar" type="button">
                                        Visualizar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcan

    @if ($lists->count())
        <div class="historic-summary mb-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    {{ $lists->links() }}
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="historic-table-card">
        @if (!$lists->count())
            <div class="card-body py-5 text-center">
                <i class="ri-history-line display-5 text-secondary"></i>
                <h4 class="mt-3 mb-1">Nenhum registro no histórico</h4>
                <p class="text-muted mb-0">
                    Não existem tarefas concluídas de {{ mb_strtoupper($service->service) }} para os filtros informados.
                </p>
            </div>
        @else
            <div class="historic-table-header d-flex align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="historic-table-title">
                        <i class="ri-history-line me-2"></i>Meu histórico
                    </h5>
                    <div class="historic-table-subtitle">
                        {{ mb_strtoupper($service->service) }}
                        @if ($service->Status->count())
                            @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                · Status {{ $sts->value }}
                            @endforeach
                        @endif
                    </div>
                </div>
                <span class="badge text-bg-light">{{ $lists->total() }} registros</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped table-condensed">
                    <thead class="table-dark">
                        <tr class="sticky-top bg-dark" style="z-index: 1; top: 0;">
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
                                    <div class="d-flex align-items-center gap-1">
                                        <span>{{ $list->Note->note }}</span>
                                        @if ($list->d5)
                                            <span class="badge text-bg-primary">RI</span>
                                        @endif
                                        <button type="button" class="copy-text btn btn-link btn-sm p-0 text-secondary"
                                            data-value="{{ $list->Note->note }}" title="Copiar número da nota">
                                            <i class="ri-file-copy-line"></i>
                                        </button>
                                    </div>
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
                                    <div class="d-flex align-items-center gap-2">
                                        <x-files.select-download-list :files='$list->Note->Files' :latest-only="true" />
                                        <button type="button" class="btn btn-sm btn-outline-success"
                                            onclick="Livewire.emit('openFileRevisionModal', {{ $list->id }}, '{{ $service->uuid }}')">
                                            <i class="ri-upload-cloud-2-line"></i> Revisar
                                        </button>
                                    </div>
                                </td>
                                <td class="fw-light">{{ $list->Note->rubrica }}</td>
                                <td class="fw-light">{{ $list->Note->lexp }}</td>
                                <td class="fw-light">{{ $list->Note->group1 }}</td>
                                <td class="fw-light">{{ $list->Note->material }}</td>
                                <td class="fw-light">{{ date('d/m/Y H:i', strtotime($list->att_at)) }}</td>
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
                                        <a href="#" class="link-success fw-semibold"
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
        @endif
    </div>

    @if ($lists->count())
        <div class="historic-summary mt-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-6">
                    {{ $lists->links() }}
                </div>
                <div class="col-12 col-lg-6 text-lg-end">
                    <div class="summary-item">
                        Exibindo <strong>{{ $lists->firstItem() }}</strong> até
                        <strong>{{ $lists->lastItem() }}</strong> de
                        <strong>{{ $lists->total() }}</strong> registros.
                    </div>
                </div>
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
