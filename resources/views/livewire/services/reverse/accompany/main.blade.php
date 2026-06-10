@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp
<div class="reverse-page">
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <style>
        .reverse-page {
            --reverse-bg: #f6f7fb;
            --reverse-surface: #ffffff;
            --reverse-ink: #1f2933;
            --reverse-muted: #6b7280;
            --reverse-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--reverse-bg);
            padding: 1.5rem 0;
        }

        .reverse-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .reverse-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .reverse-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .reverse-header .hero-count {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
        }

        .filters-grid .filter-card {
            background-color: var(--reverse-surface);
            border: 1px solid var(--reverse-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .filters-grid .filter-card h6 {
            color: var(--reverse-muted);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .summary-bar {
            background: var(--reverse-surface);
            border: 1px solid var(--reverse-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-bar .summary-item {
            color: var(--reverse-muted);
            font-size: 0.92rem;
        }

        .summary-bar .summary-item strong {
            color: var(--reverse-ink);
        }

        .table-card {
            background: var(--reverse-surface);
            border: 1px solid var(--reverse-border);
            border-radius: 0.9rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .table-card .table tbody td {
            font-size: 0.92rem;
        }

        .table-card-header {
            background: var(--bs-danger);
            border-bottom: 1px solid var(--bs-danger);
            color: #ffffff;
            padding: 1rem 1.25rem;
        }

        .table-card-title {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.2;
            margin: 0;
        }

        .table-card-subtitle {
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.82rem;
            margin-top: 0.2rem;
        }

        .reverse-tabs {
            border-bottom: 0;
            gap: 0.5rem;
        }

        .reverse-tabs .nav-link {
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid var(--reverse-border);
            border-radius: 0.75rem 0.75rem 0 0;
            color: var(--reverse-muted);
            font-weight: 600;
        }

        .reverse-tabs .nav-link.active {
            background: var(--reverse-surface);
            color: #0f766e;
        }

        @media (max-width: 991px) {
            .reverse-header {
                padding: 1.25rem;
            }
        }
    </style>

    <div class="container-fluid">
        <div class="reverse-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <div class="meta text-uppercase">Acompanhamento de produção</div>
                <h2>{{ mb_strtoupper($service->service) }}</h2>
                <div class="meta mt-1">Gestão de atividades do fluxo reverso</div>
            </div>
            <div class="d-flex align-items-center gap-4 text-lg-end">
                @if ($service->Status->count())
                    <div>
                        <div class="meta">Status ativos</div>
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

        <div class="row g-3 filters-grid mb-3">
            <div class="col-12 col-xl-5">
                <div class="filter-card">
                    <h6>Pesquisa</h6>
                    <div class="row g-2">
                        <div class="col-12 col-sm-4">
                            <div class="form-floating">
                                <select class="form-select border border-secondary" wire:model="perPage"
                                    id="reversePerPage">
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="200">200</option>
                                    <option value="500">500</option>
                                </select>
                                <label for="reversePerPage">Registros por página</label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-8">
                            <div class="form-floating position-relative">
                                <input wire:model.debounce.500ms="search" type="text"
                                    class="form-control border border-secondary pe-5" id="reverseSearch"
                                    placeholder="Buscar por nota ou material">
                                <label for="reverseSearch">Buscar por nota ou material</label>
                                <button type="button"
                                    class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                    data-bs-toggle="modal" data-bs-target="#reverse_multi_search_modal"
                                    title="Buscar em massa" aria-label="Buscar em massa">
                                    <i class="ri-checkbox-multiple-blank-line"></i>
                                </button>
                            </div>
                            @if (count($multiSearch))
                                <small class="text-primary">
                                    Busca em massa ativa: {{ count($multiSearch) }} termo(s)
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="filter-card">
                    <h6>Filtro por rubrica</h6>
                    <div class="dropdown mb-2">
                        <button class="btn btn-outline-secondary dropdown-toggle w-100 d-flex align-items-center justify-content-between"
                            type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                            aria-expanded="false">
                            <span>
                                <i class="ri-price-tag-3-line me-1"></i>
                                {{ count($rubrica_s) ? 'Rubricas selecionadas' : 'Todas as rubricas' }}
                            </span>
                            @if (count($rubrica_s))
                                <span class="badge text-bg-secondary ms-1">{{ count($rubrica_s) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu w-100 p-0 shadow">
                            <div class="px-3 py-2 border-bottom">
                                <strong class="small">Selecione uma ou mais rubricas</strong>
                            </div>
                            <div class="p-2" style="max-height: 280px; overflow-y: auto;">
                                @if (isset($rubrica_l) && $rubrica_l->count() > 0)
                                    @foreach ($rubrica_l as $rubrica)
                                        @if ($rubrica->rubrica)
                                            <label class="dropdown-item d-flex align-items-center gap-2 rounded">
                                                <input class="form-check-input mt-0" type="checkbox"
                                                    wire:model.defer="rubrica_s" wire:key="{{ $rubrica->rubrica }}"
                                                    value="{{ $rubrica->rubrica }}">
                                                <span>{{ $rubrica->rubrica }}</span>
                                            </label>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="text-muted small p-2">Nenhuma rubrica disponível.</div>
                                @endif
                            </div>
                            <div class="d-flex gap-2 border-top p-2">
                                <button type="button" class="btn btn-sm btn-primary flex-fill"
                                    wire:click.prevent="filter_save">
                                    <i class="ri-filter-fill me-1"></i>Aplicar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    wire:click.prevent="filter_clean">
                                    Limpar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="btn-group w-100">
                        <button type="button" class="btn btn-primary" wire:click.prevent="filter_save">
                            <i class="ri-filter-fill me-1"></i>Aplicar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" wire:click.prevent="filter_clean"
                            @disabled(!count($rubrica_s))>
                            <i class="ri-filter-off-fill me-1"></i>Limpar
                        </button>
                    </div>
                    @if (count($rubrica_s))
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach ($rubrica_s as $selectedRubrica)
                                <span class="badge text-bg-light border text-dark">{{ $selectedRubrica }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @can('superadm')
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="filter-card">
                        <h6>Visão administrativa</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <input wire:model.debounce.500ms="user_search" type="text"
                                    class="form-control border border-secondary" id="reverseUserSearch"
                                    placeholder="Buscar usuário">
                            </div>
                            <div class="col-12">
                                <div class="input-group">
                                    <select class="form-select border border-secondary" wire:model.defer="user_s">
                                        <option value="">Selecione o usuário</option>
                                        @foreach ($user_l as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-primary" wire:click.prevent="visualizar" type="button">
                                        Visualizar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endcan
        </div>

        <nav>
            <div class="nav nav-tabs reverse-tabs" id="nav-tab" role="tablist">
                <button class="nav-link active" id="nav-production-tab" data-bs-toggle="tab"
                    data-bs-target="#my_production" type="button" role="tab" aria-controls="nav-home"
                    aria-selected="true" wire:click.prevent="$emit('refresh_accomany')">
                    Produção
                </button>
                <button class="nav-link" id="nav-transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer"
                    type="button" role="tab" aria-controls="nav-profile" aria-selected="false"
                    wire:click.prevent="$emit('refresh_translist')">
                    Transferências
                    @livewire('components.transprod.count', ['service_id' => $service->uuid], key('transfer_count'))
                </button>
            </div>
        </nav>

    <div class="tab-content" id="nav-tabContent">
        <div class="tab-pane fade show active" id="my_production" role="tabpanel" aria-labelledby="nav-home-tab"
            tabindex="0">
            @if ($lists->count())
                <div class="summary-bar my-3">
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

            <div class="table-card">
                @if (!$lists->count())
                    <div class="card-body py-5 text-center">
                        <i class="ri-inbox-2-line display-5 text-secondary"></i>
                        <h4 class="mt-3 mb-1">Nenhuma tarefa atribuída</h4>
                        <p class="text-muted mb-0">
                            Não existem registros de {{ mb_strtoupper($service->service) }} disponíveis nesta visão.
                        </p>
                    </div>
                @else
                    <div
                        class="table-card-header d-flex flex-column flex-lg-row gap-3 align-items-lg-center justify-content-between">
                        <div>
                            <h5 class="table-card-title">
                                <i class="ri-list-check-2 me-2"></i>Acompanhamento
                            </h5>
                            <div class="table-card-subtitle">
                                {{ mb_strtoupper($service->service) }}
                                @if ($service->Status->count())
                                    @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                        · Status {{ $sts->value }}
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        @if (count($selected))
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                data-bs-target="#bulk_finish_modal">
                                <i class="ri-checkbox-multiple-line me-1"></i>
                                Encerrar selecionados ({{ count($selected) }})
                            </button>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-condensed mb-0">
                            <thead class="table-dark">
                                <tr class="sticky-top bg-dark" style="z-index: 1; top: 0;">
                                        <th scope="col" class="fw-bold text-center">
                                            <input class="form-check-input" type="checkbox" wire:model="selectAll"
                                                wire:click="setSelectAll()" @checked($this->checkAllSelect($lists))>
                                        </th>
                                        <th scope="col" class="fw-bold">Note</th>
                                        <th scope="col" class="fw-bold">Criado Em</th>
                                        <th scope="col" class="fw-bold">numPedido</th>
                                        <th scope="col" class="fw-bold">Rubrica</th>
                                        <th scope="col" class="fw-bold">Municipio</th>
                                        <th scope="col" class="fw-bold">Zona</th>
                                        <th scope="col" class="fw-bold">Grp2</th>
                                        <th scope="col" class="fw-bold">Descrição</th>
                                        <th scope="col" class="fw-bold">Dias Atribuido</th>
                                        <th scope="col" class="fw-bold">Dias da Nota</th>
                                        <th scope="col" class="fw-bold">Status</th>
                                        <th scope="col" class="fw-bold text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lists->sortBy([['priority', 'desc'], ['Note.days_left', 'asc']]) as $list)
                                    <tr class="align-middle @if ($list->block) table-primary @endif"
                                        wire:key="{{ $list->id }}">
                                        <td class="text-center">
                                            <input class="form-check-input border border-1 border-primary"
                                                type="checkbox" value="{{ $list->id }}" wire:model.defer="selected">
                                        </td>
                                        <td class="fw-bold @if ($list->priority) text-danger @endif">
                                            <div class="d-flex align-items-center gap-1">
                                                <span>{{ $list->Note->note }}</span>
                                                <button type="button"
                                                    class="copy-text btn btn-link btn-sm p-0 text-secondary"
                                                    data-value="{{ $list->Note->note }}" tabindex="0"
                                                    data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                    data-bs-placement="top" data-bs-content="Copiar número da nota">
                                                    <i class="ri-file-copy-line"></i>
                                                </button>
                                                @if ($list->priority)
                                                    <i class="ri-alert-fill text-danger"
                                                        wire:click.prevent="$emit('infoPriority', '{{ $list->id }}')"
                                                        style="cursor: pointer;" tabindex="0"
                                                        data-bs-toggle="popover" data-bs-trigger="hover focus"
                                                        data-bs-placement="top" data-bs-title="Exibir prioridade"
                                                        data-bs-content="Clique para visualizar a informação da prioridade desta nota/OV."></i>
                                                @endif
                                            </div>
                                            <div class="mt-1">
                                                <x-legal.note-demand-tags
                                                    :demands="$legalTagsByNoteId[$list->note_id] ?? []"
                                                    :row-key="'services-reverse-accompany-'.$list->id"
                                                />
                                            </div>
                                        </td>
                                        <td class="fw-light">
                                            {{ date('d/m/Y', strtotime($list->Note->dt_created)) }}
                                        </td>
                                        <td class="fw-light">{{ $list->Note->numPedido }}</td>
                                        <td class="fw-light">{{ $list->Note->rubrica }}</td>
                                        <td class="fw-light">{{ $list->Note->lexp }}</td>
                                        <td class="fw-light">{{ $list->Note->group1 }}</td>
                                        <td class="fw-light">{{ $list->Note->group2 }}</td>
                                        <td class="fw-light">{{ $list->Note->material }}</td>
                                        <td class="fw-light text-center">
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
                                            {{ 30 - $list->Note->days_left }}
                                        </td>
                                        <td class="fw-light text-center">
                                            <span class="badge {{ Notestatus::status($list->status)->colorbg }}"
                                                wire:click="$emitTo('components.status.show-status', 'showStatus',  {{ $list }}, {{ $list->status }})"
                                                style="cursor: pointer;">
                                                {{ Notestatus::status($list->status)->status }}
                                            </span>
                                        </td>
                                        <td class="text-center text-nowrap">
                                            @if (!$list->block && !$list->completed)
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-title="Iniciar"
                                                    wire:click.prevent="getAnalise({{ $list->id }}, {{ $list->Note->id }})">
                                                    <i class="ri-play-circle-line"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="tooltip" data-bs-placement="top"
                                                    data-bs-title="Transferir"
                                                    wire:click.prevent="goTransferProd({{ $list->id }})">
                                                    <i class="ri-exchange-fill"></i>
                                                </button>
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
                <div class="summary-bar my-3">
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
        </div>


        <div class="tab-pane fade" id="transfer" role="tabpanel" aria-labelledby="nav-profile-tab" tabindex="0">
            @livewire('components.transprod.translist', ['service' => $service->id])
        </div>
    </div>


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
                    @livewire('services.reverse.forms.analise', key('analise-form'))
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

    {{-- MODAL COMPLEMENTS TRANSFER NOTE --}}
    @livewire('components.transprod.transprod', key('Transfer_production'))
    @livewire('components.status.show-status', key('show_status_note'))

    <div wire:ignore.self class="modal fade" id="reverse_multi_search_modal" tabindex="-1"
        aria-labelledby="reverseMultiSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-bg-primary">
                    <h5 class="modal-title" id="reverseMultiSearchModalLabel">Buscar em massa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label for="reverseAdvanceSearch" class="form-label">
                        Informe notas ou números de pedido separados por espaço, vírgula, ponto e vírgula ou quebra de linha
                    </label>
                    <textarea class="form-control" id="reverseAdvanceSearch" rows="8"
                        wire:model.defer="advanceSearch"
                        placeholder="Ex: 30001234&#10;30001235&#10;450009999"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearMultiSearch">
                        Limpar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">
                        Aplicar busca
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="bulk_finish_modal" tabindex="-1"
        aria-labelledby="bulkFinishModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-bg-danger">
                    <h5 class="modal-title" id="bulkFinishModalLabel">Encerramento em massa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">
                                Você selecionou <strong>{{ count($selected) }}</strong> registro(s) para encerrar.
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="bulkMmgd" wire:model.defer="bulkMmgd">
                                    <option value="" selected>Selecione</option>
                                    <option value="SIM">SIM</option>
                                    <option value="NAO">NÃO</option>
                                </select>
                                <label for="bulkMmgd">MMGD?</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="bulkIs45" wire:model.defer="bulkIs45">
                                    <option value="" selected>Selecione</option>
                                    <option value="1">SIM</option>
                                    <option value="0">NÃO</option>
                                </select>
                                <label for="bulkIs45">Art.90 (45 dias)?</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="form-floating">
                                <select class="form-select" id="bulkConclusion" wire:model.defer="bulkConclusion">
                                    <option value="" selected>Selecione</option>
                                    <option value="ISR - LIBERADO">ISR - LIBERADO</option>
                                    <option value="ENVIADO A CAMPO">ENVIADO A CAMPO</option>
                                    <option value="ENVIADO AO DESENHO">ENVIADO AO DESENHO</option>
                                    <option value="ENVIADO CARTA AO CLIENTE">ENVIADO CARTA AO CLIENTE</option>
                                    <option value="ENVIADO RESPOSTA EMPRESA">ENVIADO RESPOSTA EMPRESA</option>
                                    <option value="ENVIADO PARA O STATUS 21">ENVIADO PARA O STATUS 21</option>
                                </select>
                                <label for="bulkConclusion">Conclusão</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="bulkInfo" style="height: 140px"
                                    wire:model.defer="bulkInfo"></textarea>
                                <label for="bulkInfo">Informações</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" wire:click.prevent="confirmBulkClose">
                        Encerrar em massa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:init="checkOpen"></div>

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
