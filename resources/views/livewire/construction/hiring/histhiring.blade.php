@php
    use App\Custom\Viabilitiesstatus;
    use Carbon\Carbon;
@endphp

@push('css')
    <style>
        .hiring-page {
            --h-bg: #f6f7fb;
            --h-surface: #ffffff;
            --h-ink: #1f2933;
            --h-muted: #6b7280;
            --h-accent: #0f766e;
            --h-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--h-bg);
            padding: 1.5rem 0;
        }

        .hiring-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1.5rem;
        }

        .hiring-header h2 {
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
        }

        .hiring-header .meta {
            color: rgba(248, 250, 252, 0.75);
            font-size: 0.95rem;
        }

        .filters-grid .filter-card {
            background-color: var(--h-surface);
            border: 1px solid var(--h-border);
            border-radius: 0.9rem;
            padding: 1rem 1.25rem;
            height: 100%;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .filters-grid .filter-card h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--h-muted);
        }

        .filters-grid .chip-filters {
            gap: 0.5rem;
        }

        .summary-bar {
            background: var(--h-surface);
            border: 1px solid var(--h-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1.25rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
        }

        .summary-bar .summary-item {
            font-size: 0.92rem;
            color: var(--h-muted);
        }

        .summary-bar .summary-item strong {
            color: var(--h-ink);
        }

        .table-card {
            background: var(--h-surface);
            border: 1px solid var(--h-border);
            border-radius: 1rem;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .table-card .table tbody td {
            font-size: 0.92rem;
        }

        .hiring-rowbar {
            border-left: 6px solid transparent;
        }

        .hiring-pill {
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-weight: 600;
            font-size: 0.8rem;
        }

        @media (max-width: 991px) {
            .hiring-header {
                padding: 1.25rem;
            }
        }
    </style>
@endpush

<div class="hiring-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="hiring-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>HISTORICO DE VIABILIDADE</h2>
                <div class="meta">Controle de contratacoes e viabilidades</div>
            </div>
            <div class="text-lg-end">
                <div class="meta">Selecionados</div>
                <div><strong>{{ count($selected) }}</strong></div>
            </div>
        </div>

        {{-- START SearchBar and Filters --}}
        <div class="card mb-3 border-0 bg-transparent">
            <div class="card-body px-0">
                <div class="row g-3 filters-grid">
                    <div class="col-12 col-lg-5 col-xl-4">
                        <div class="filter-card">
                            <h6>Pesquisa</h6>
                            <div class="row g-2">
                                <div class="col-12 col-sm-5">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="perPage"
                                            id="perPageSelect">
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                            <option value="250">250</option>
                                            <option value="500">500</option>
                                        </select>
                                        <label for="perPageSelect">Registros por pagina</label>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <div class="form-floating w-100 position-relative">
                                        <input type="text" class="form-control border border-secondary" id="searchInput"
                                            wire:model.debounce.2s="search" placeholder="Buscar">
                                        <label for="searchInput">Buscar</label>
                                        <button
                                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                            data-bs-toggle="modal" data-bs-target="#multiSearchModal"
                                            title="Busca multipla">
                                            <i class="ri-checkbox-multiple-blank-line"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-danger w-100" wire:click.prevent="cleanAll()"
                                        title="Limpar filtros">
                                        <i class="ri-find-replace-line me-1"></i> Limpar filtros
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4 col-xl-3">
                        <div class="filter-card">
                            <h6>Periodo</h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="form-floating w-100">
                                        <input type="date" id="date_in" class="form-control border border-secondary"
                                            wire:model="date_in" placeholder="Data inicial">
                                        <label for="date_in">Data inicial</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating w-100">
                                        <input type="date" id="date_out" class="form-control border border-secondary"
                                            wire:model="date_out" placeholder="Data final">
                                        <label for="date_out">Data final</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating w-100">
                                        <select class="form-select border border-secondary" wire:model="dateBy"
                                            id="dateBySelect">
                                            <option value="sended_at">Recebido</option>
                                            <option value="returned_at">Viabilizado</option>
                                            <option value="completed_at">Completado</option>
                                        </select>
                                        <label for="dateBySelect">Tipo de data</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-5">
                        <div class="filter-card h-100">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="mb-0">Filtros adicionais</h6>
                                @livewire('components.filter.remove-all', ['group_filter' => 'hiring_hist'], key('removeAll'))
                            </div>
                            <div class="d-flex flex-wrap chip-filters">
                                @livewire(
                                    'components.filter.filter',
                                    [
                                        'myKey' => 'rubrica',
                                        'sendFilter' => '',
                                        'model' => 'App\Models\Note',
                                        'column' => 'rubrica',
                                        'filter' => 'Rubrica',
                                        'group_filter' => 'hiring_hist',
                                        'values' => 'rubrica',
                                        'direction' => 'ASC',
                                        'query' => '',
                                    ],
                                    key('rubrica')
                                )

                                @livewire(
                                    'components.filter.filter',
                                    [
                                        'myKey' => 'region',
                                        'sendFilter' => 'city',
                                        'model' => 'App\Models\Edp_depc\City',
                                        'column' => 'regiao',
                                        'filter' => 'Regiao',
                                        'group_filter' => 'hiring_hist',
                                        'values' => 'regiao',
                                        'direction' => 'ASC',
                                        'query' => '',
                                    ],
                                    key('region')
                                )

                                @livewire(
                                    'components.filter.filter',
                                    [
                                        'myKey' => 'city',
                                        'sendFilter' => '',
                                        'model' => 'App\Models\Edp_depc\City',
                                        'column' => 'cidade',
                                        'filter' => 'Municipio',
                                        'group_filter' => 'hiring_hist',
                                        'values' => 'municipio',
                                        'direction' => 'ASC',
                                        'query' => '',
                                    ],
                                    key('city')
                                )
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button"
                                    class="btn {{ $hasNoHired ? 'btn-warning' : 'btn-outline-secondary' }}"
                                    wire:click="$toggle('hasNoHired')" title="Mostrar apenas nao contratadas">
                                    <i class="ri-checkbox-blank-circle-line me-1"></i>
                                    Nao contratadas
                                    @if ($hasNoHired)
                                        <i class="ri-toggle-fill text-warning ms-1"></i>
                                    @else
                                        <i class="ri-toggle-line ms-1"></i>
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- END SearchBar and Filters --}}

        @if ($lists->isEmpty())
            <div class="text-center my-5 py-3">
                <h3 class="text-muted">Nenhuma atividade encontrada</h3>
            </div>
        @endif

        @if ($lists->isNotEmpty())
            <div class="summary-bar mb-3">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <div class="summary-item">
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-card mb-3">
                <div class="card-header fw-bold text-bg-secondary d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">HISTORICO DE VIABILIDADE</h4>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-success btn-sm" wire:click="exportToExcel">
                            <i class="ri-file-excel-2-line me-1"></i> Exportar
                        </button>
                        <button class="btn btn-primary btn-sm" wire:click="editSelected" @disabled(!count($selected))>
                            <i class="ri-edit-2-fill me-1"></i> Editar selecionados
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr class="sticky-top bg-dark" style="z-index:1; top:0;">
                                <th class="text-center" style="width:34px;">
                                    <input type="checkbox" class="form-check-input" wire:model="selectPage">
                                </th>
                                <th class="text-center">Nota/OV</th>
                                <th class="text-center">Arquivos</th>
                                <th class="text-center">Ordem</th>
                                <th class="text-center">Enviado</th>
                                <th class="text-center">Contratado</th>
                                <th class="text-center">Empreiteira</th>
                                <th class="text-center">Responsavel</th>
                                <th class="text-center">Rubrica</th>
                                <th class="text-center">Regiao</th>
                                <th class="text-center">Municipio</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:46px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $index => $list)
                                @php
                                    $color = match (true) {
                                        $list->approved && !$list->rejected && !$list->tacit => 'green',
                                        !$list->approved && $list->rejected && !$list->tacit => 'red',
                                        $list->tacit => 'yellow',
                                        default => '',
                                    };
                                    $regiao =
                                        optional($cities->Where('rdMunicipio', $list->Note->nexp)->first())->regiao ??
                                        '';
                                    $orders = $list->Note->Orders->sortBy('ordem')->values();
                                @endphp

                                <tr wire:key="viability-{{ $list->id }}" class="align-middle hiring-rowbar"
                                    style="cursor:pointer; border-left-color: {{ $color }};">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input" value="{{ $list->id }}"
                                            wire:model="selected">
                                    </td>

                                    <td class="text-center fw-bold">{{ $list->Note->note }}</td>
                                    <td class="text-center">
                                        <x-files.select-download-list :files='$list->Note->Files' />
                                    </td>
                                    <td class="text-center">
                                        @if ($orders->isNotEmpty())
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                @foreach ($orders as $order)
                                                    @php
                                                        $hasOp0010Conf = $order->Operations->contains(function ($op) {
                                                            return $op->operacao === '0010' &&
                                                                str_starts_with(strtoupper(ltrim((string) $op->status)), 'CONF');
                                                        });
                                                    @endphp
                                                    <span class="d-inline-flex align-items-center gap-1">
                                                        {{ $order->ordem }}
                                                        @if (!$hasOp0010Conf)
                                                            <i class="ri-error-warning-line text-warning"
                                                                title="Sem operacao 0010 CONF"></i>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">
                                        {{ Carbon::parse($list->sended_at)->format('d/m/Y') }}
                                    </td>
                                    <td class="text-center text-success fw-bold">
                                        {{ isset($list->hired_at) ? Carbon::parse($list->hired_at)->format('d/m/Y') : '---' }}
                                    </td>
                                    <td class="text-center">{{ $list->Company->name ?? '---' }}</td>
                                    <td class="text-center">{{ $list->Engineer->name ?? '---' }}</td>
                                    <td class="text-center">{{ $list->Note->rubrica }}</td>
                                    <td class="text-center">{{ $regiao }}</td>
                                    <td class="text-center">{{ $list->Note->lexp }}</td>
                                    <td class="text-center">
                                        @php $v = Viabilitiesstatus::status($list->status); @endphp
                                        <span class="hiring-pill {{ $v->colorbg }}">{{ $v->status }}</span>
                                    </td>
                                    <td class="text-center">
                                        <i class="ri-pencil-fill text-primary fs-5" style="cursor:pointer"
                                            wire:click.prevent="$emitTo('construction.hiring.actions.edit','edit_hiring', {{ $list->id }})"></i>
                                        @if ((auth()->user()?->superadm) || ($list->created_at && $list->created_at->gt(now()->subHours(24))))
                                            <i class="ri-delete-bin-6-line text-danger fs-5 ms-2" style="cursor:pointer"
                                                title="Excluir registro"
                                                wire:click.prevent="requestDelete({{ $list->id }})"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="summary-bar">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-6">
                        {{ $lists->links() }}
                    </div>
                    <div class="col-12 col-lg-6 text-lg-end">
                        <div class="summary-item">
                            Exibindo <strong>{{ $lists->firstItem() }}</strong> ate
                            <strong>{{ $lists->lastItem() }}</strong> de
                            <strong>{{ $lists->total() }}</strong> registros.
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Livewire Modals --}}
        @livewire('partner.actions.responserviab', key('reesponser_modal_viab'))
        @livewire('construction.hiring.actions.edit', key('hiring-edit'))

        {{-- Modal: Busca Multi-notas --}}
        <div wire:ignore.self class="modal fade" id="multiSearchModal" tabindex="-1"
            aria-labelledby="multiSearchModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="multiSearchModalLabel">Busca Multi-notas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <textarea class="form-control" rows="12" wire:model.defer="advancedSearch"
                        wire:keydown.ctrl.enter="buscarMulti"
                        placeholder="Cole aqui varias notas, uma por linha.&#10;Exemplo:&#10;123456&#10;987654&#10;ABC-2024-001"></textarea>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" wire:click="buscarMulti">
                            <i class="ri-search-line me-1"></i> Buscar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('livewire:load', function() {
                const dateIn = document.getElementById('date_in');
                const dateOut = document.getElementById('date_out');

                function setMin() {
                    if (dateIn && dateOut && dateIn.value) dateOut.min = dateIn.value;
                }
                if (dateIn) {
                    dateIn.addEventListener('change', setMin);
                    setMin();
                    dateIn.addEventListener('keydown', e => e.preventDefault());
                }
                if (dateOut) {
                    dateOut.addEventListener('keydown', e => e.preventDefault());
                }
            });

            window.addEventListener('hide-bs-modal', (e) => {
                const id = e.detail?.id;
                if (!id) return;
                const el = document.getElementById(id);
                if (!el) return;
                const m = bootstrap.Modal.getOrCreateInstance(el);
                m.hide();
            });
        </script>
    </div>
</div>
