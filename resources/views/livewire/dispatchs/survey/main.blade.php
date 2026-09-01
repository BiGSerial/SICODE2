@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
    use App\Helpers\DaysLeft;
    $contractCompanyName = \App\Support\SicodeRules::primaryCompanyNameFor(Auth()->User());
@endphp
<div class="survey-main-page">

    <style>
        @keyframes flame {
            0% {
                transform: scaleX(1) scaleY(1);
            }

            25% {
                transform: scaleX(1) scaleY(0.8);
            }

            50% {
                transform: scaleX(-1) scaleY(0.8);
            }

            75% {
                transform: scaleX(-1) scaleY(1);
            }
        }

        .survey-main-page {
            --sp-bg: #f6f7fb;
            --sp-surface: #ffffff;
            --sp-ink: #1f2933;
            --sp-muted: #6b7280;
            --sp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--sp-bg);
            padding: 1.5rem 0;
        }

        .survey-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }

        .survey-header h2 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .survey-meta {
            color: rgba(248, 250, 252, 0.8);
            font-size: 0.9rem;
        }

        .survey-main-page .filter-shell {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 0.9rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .survey-main-page .filter-shell h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 600;
            color: var(--sp-muted);
            margin-bottom: 0.65rem;
        }

        .survey-main-page .summary-bar {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 0.9rem;
            padding: 0.75rem 1rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            margin-bottom: 1rem;
        }

        .survey-main-page .summary-item {
            color: var(--sp-muted);
            font-size: 0.92rem;
        }

        .survey-main-page .summary-item strong {
            color: var(--sp-ink);
        }

        .survey-main-page .table-card {
            background: var(--sp-surface);
            border: 1px solid var(--sp-border);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .survey-main-page .table-card .card-header {
            padding: 0.9rem 1.25rem;
        }

        .survey-main-page .table-card .table-responsive {
            padding: 0;
        }

        .survey-main-page .table-card .table thead th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
        }

        .survey-main-page .table-card .main-table {
            border-collapse: separate;
            border-spacing: 0 0.45rem;
            margin: 0;
        }

        .survey-main-page .table-card .main-table thead th {
            border: 0;
            background: #1f2937;
            color: #f8fafc;
            box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08);
        }

        .survey-main-page .table-card .main-table tbody tr {
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .survey-main-page .table-card .main-table tbody tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .survey-main-page .table-card .main-table tbody td {
            font-size: 0.9rem;
            vertical-align: middle;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            padding-top: 0.6rem;
            padding-bottom: 0.6rem;
        }

        .survey-main-page .table-card .main-table tbody td.table-primary,
        .survey-main-page .table-card .main-table tbody td.table-warning,
        .survey-main-page .table-card .main-table tbody td.table-success,
        .survey-main-page .table-card .main-table tbody td.table-danger,
        .survey-main-page .table-card .main-table tbody td.table-secondary {
            border-color: rgba(15, 23, 42, 0.08);
        }

        .survey-main-page .table-card .main-table tbody td:not(.table-primary):not(.table-warning):not(.table-success):not(.table-danger):not(.table-secondary):not(.text-bg-secondary) {
            background: #f8fafc;
        }

        .survey-main-page .table-card .main-table tbody td:first-child {
            border-left: 1px solid #e2e8f0;
            border-top-left-radius: 0.7rem;
            border-bottom-left-radius: 0.7rem;
        }

        .survey-main-page .table-card .main-table tbody td:last-child {
            border-right: 1px solid #e2e8f0;
            border-top-right-radius: 0.7rem;
            border-bottom-right-radius: 0.7rem;
        }

        .survey-main-page .control-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.9rem;
        }

        .survey-main-page .control-card {
            background: linear-gradient(160deg, #ffffff, #f8fafc);
            border: 1px solid #dbe3ef;
            border-radius: 0.9rem;
            padding: 0.85rem;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .survey-main-page .control-card h6 {
            margin-bottom: 0.55rem;
        }

        .survey-main-page .quick-actions {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }

        .survey-main-page .quick-actions .btn {
            min-height: 42px;
            border-radius: 0.65rem;
            font-weight: 600;
        }

        .survey-main-page .filters-row {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.85rem;
            padding: 0.7rem;
            justify-content: flex-end;
        }

        .survey-main-page .association-modal {
            border: 0;
            border-radius: 0.95rem;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
        }

        .survey-main-page .association-modal .modal-header,
        .survey-main-page .association-modal .modal-footer {
            background: #0f3f43;
            color: #f8fafc;
            border: 0;
        }

        .survey-main-page .association-modal .modal-header {
            padding: 1rem 1.25rem;
        }

        .survey-main-page .association-modal .modal-body {
            background: #f8fafc;
            padding: 1.25rem;
        }

        .survey-main-page .association-modal .modal-title {
            color: #22c55e;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.01em;
        }

        .survey-main-page .association-help {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 0.75rem;
            padding: 0.85rem 1rem;
            color: #334155;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
        }

        .survey-main-page .association-help strong {
            color: #0f172a;
        }

        .survey-main-page .association-example {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            border-radius: 999px;
            color: #075985;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.22rem 0.55rem;
        }

        .survey-main-page .association-textarea {
            min-height: 240px;
            resize: vertical;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.92rem;
            line-height: 1.6;
            border: 1px solid #cbd5e1;
            border-radius: 0.75rem;
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.05);
        }

        .survey-main-page .association-textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.16);
        }

        .survey-main-page .bulk-search-modal .modal-content {
            border: 0;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.28);
        }

        .survey-main-page .bulk-search-modal .modal-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 75%);
            color: #f8fafc;
            border: 0;
            padding: 1rem 1.25rem;
        }

        .survey-main-page .bulk-search-modal .modal-title {
            font-size: 1rem;
            font-weight: 700;
        }

        .survey-main-page .bulk-search-modal textarea {
            min-height: 12rem;
            resize: vertical;
            border-color: #cbd5e1;
        }

        .survey-main-page .bulk-search-warning {
            border: 1px solid #f59e0b;
            background: #fffbeb;
            color: #92400e;
            border-radius: 0.5rem;
            padding: 0.75rem 0.9rem;
            font-size: 0.88rem;
        }

        @media (min-width: 992px) {
            .survey-main-page .control-grid {
                grid-template-columns: 1fr 1fr 1fr 1.25fr;
            }

            .survey-main-page .quick-actions {
                grid-template-columns: repeat(2, minmax(130px, 1fr));
            }
        }
    </style>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <x-showselected :count="$selected" />

    <div class="container-fluid px-3 px-lg-4">
        <div class="survey-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2>LISTA PARA {{ mb_strtoupper($service->service) }}
                    @if ($contractCompanyName)
                        - {{ mb_strtoupper($contractCompanyName) }}
                    @endif
                </h2>
                <div class="survey-meta">
                    @if ($service->Status->count())
                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                            ({{ $sts->value }})
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="text-lg-end">
                @if ($update)
                    <div class="survey-meta">Última Atualização</div>
                    <strong>{{ Carbon::parse($last_update)->diffForHumans() }}</strong>
                @endif
            </div>
        </div>

        <div class="filter-shell mb-3">
            <div class="card-body p-3 p-lg-4">
                <div class="control-grid">
                    <div class="control-card">
                        <h6>Paginação</h6>
                        <div class="form-floating">
                            <select wire:model="perPage" class="form-select border border-secondary" id="surveyPerPage">
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                                <option value="250">250</option>
                                <option value="500">500</option>
                            </select>
                            <label for="surveyPerPage">Registros por página</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Busca</h6>
                        <div class="position-relative">
                            <input wire:model.bounce.2s="search" type="text"
                                class="form-control border border-secondary pe-5" id="surveySearch"
                                placeholder="Buscar">
                            <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                                data-bs-toggle="modal" data-bs-target="#buscar_multi">
                                <i class="ri-checkbox-multiple-blank-line"></i>
                            </button>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Tipo de Nota</h6>
                        <div class="btn-group w-100" role="group" aria-label="Tipo de nota">
                            <input type="radio" class="btn-check" name="note_type" wire:model="note_type" value="1"
                                id="surveyNoteType1">
                            <label class="btn btn-outline-primary" for="surveyNoteType1">Nota</label>
                            <input type="radio" class="btn-check" name="note_type" wire:model="note_type" value="2"
                                id="surveyNoteType2">
                            <label class="btn btn-outline-primary" for="surveyNoteType2">OV</label>
                            <input type="radio" class="btn-check" name="note_type" wire:model="note_type" value=""
                                id="surveyNoteType3">
                            <label class="btn btn-outline-primary" for="surveyNoteType3">Ambos</label>
                        </div>
                    </div>

                    <div class="control-card">
                        <h6>Ações Rápidas</h6>
                        <div class="quick-actions">
                            <button type="button" class="btn btn-{{ Notestatus::status(1)->color }}"
                                wire:click.prevent="filterStatus()" tabindex="0" data-bs-toggle="popover"
                                data-bs-trigger="hover focus" data-bs-placement="right"
                                data-bs-title="Exibir Apenas Notas Nao Atribuidas"
                                data-bs-content="Ao clicar, todas as notas que não contenham atribuição ficam visíveis. ON significa filtro ativo e OFF inativo.">
                                {{ Notestatus::status(1)->status }}
                                @if ($not_assigned)
                                    <span class="badge text-bg-success">ON</span>
                                @else
                                    <span class="badge text-bg-danger">OFF</span>
                                @endif
                            </button>

                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#add_mass_dds">
                                <i class="ri-checkbox-multiple-fill"></i> Att DD
                            </button>
                            <button class="btn btn-primary" wire:click.prevent='go_att_mass'>
                                <i class="ri-checkbox-multiple-fill"></i> Atribuir
                            </button>
                            <button class="btn btn-primary" wire:click.prevent='export_excel'>
                                <i class="ri-file-excel-2-line"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="filters-row d-flex flex-wrap align-items-center justify-content-end gap-2 mt-3">
                    <span class="small text-uppercase fw-semibold text-secondary me-1">Filtros adicionais</span>

                    <div class="dropdown">
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
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Região
                            @if (count($region_s))
                                <span class="badge text-bg-light">{{ count($region_s) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                            <form wire:submit.prevent="filter_save">
                                @if (isset($region_l) && $region_l->count() > 0)
                                    @foreach ($region_l as $region)
                                        @if ($region->regiao)
                                            <div class="dropdown-item">
                                                <input type="checkbox" wire:model.defer="region_s"
                                                    wire:key="{{ $region->regiao }}" value="{{ $region->regiao }}">
                                                <label for="opcao1">{{ $region->regiao }}</label>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </form>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Regional
                            @if (count($district_s))
                                <span class="badge text-bg-light">{{ count($district_s) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                            <form wire:submit.prevent="filter_save">
                                @if (isset($district_l) && $district_l->count() > 0)
                                    @foreach ($district_l as $district)
                                        @if ($district->baseConstrucao)
                                            <div class="dropdown-item">
                                                <input type="checkbox" wire:model.defer="district_s"
                                                    wire:key="{{ $district->baseConstrucao }}"
                                                    value="{{ $district->baseConstrucao }}">
                                                <label for="opcao1">{{ $district->baseConstrucao }}</label>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </form>
                        </div>
                    </div>

                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Município
                            @if (count($city_s))
                                <span class="badge text-bg-light">{{ count($city_s) }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu" style="max-height: 350px; overflow-y: auto;">
                            <form wire:submit.prevent="filter_save">
                                @if (isset($city_l) && $city_l->count() > 0)
                                    @foreach ($city_l as $city)
                                        @if ($city->cidade)
                                            <div class="dropdown-item">
                                                <input type="checkbox" wire:model.defer="city_s"
                                                    wire:key="{{ $city->cidade }}" value="{{ $city->cidade }}">
                                                <label for="opcao1">{{ $city->municipio }}</label>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </form>
                        </div>
                    </div>

                    <button class="btn btn-primary" wire:click.prevent="filter_save" tabindex="0"
                        data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                        data-bs-content="Aplicar Filtros">
                        <i class="ri-filter-fill"></i>
                    </button>
                    <button class="btn btn-primary" wire:click.prevent="filter_clean" tabindex="0"
                        data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top"
                        data-bs-content="Remover Filtros">
                        <i class="ri-filter-off-fill"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <div class="row align-items-center g-2">
                <div class="col-12 col-lg-6">
                    @if ($lists->count())
                        {{ $lists->links() }}
                    @endif
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

        <div class="table-card">

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
            <div class="card-header fw-bold text-bg-secondary">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">LISTA PARA {{ mb_strtoupper($service->service) }}
                            @if ($contractCompanyName)
                                - {{ mb_strtoupper($contractCompanyName) }}
                            @endif
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-condensed table-hover mb-0 main-table">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input class="form-check-input" type="checkbox" wire:model="selectall">
                            </th>
                            {{-- @can('management')
                                    <th scope="col" class="fw-bold">Note</th>
                                @endcan --}}
                            <th scope="col" class="fw-bold text-center">Note</th>
                            <th scope="col" class="fw-bold text-center">DD</th>
                            <th scope="col" class="fw-bold text-center">MMGD</th>
                            <th scope="col" class="fw-bold text-center">Criado Em</th>
                            <th scope="col" class="fw-bold text-center">numPedido</th>
                            <th scope="col" class="fw-bold text-center">Rubrica</th>
                            <th scope="col" class="fw-bold text-center">Municipio</th>
                            <th scope="col" class="fw-bold text-center">Grp1</th>
                            <th scope="col" class="fw-bold text-center">Grp2</th>
                            <th scope="col" class="fw-bold text-center">Grp4</th>
                            <th scope="col" class="fw-bold text-center">Grp5</th>
                            <th scope="col" class="fw-bold text-center">Levantamentos</th>
                            <th scope="col" class="fw-bold text-center">Status</th>
                            {{-- <th scope="col" class="fw-bold text-center">Pze</th> --}}

                            <th scope="col" class="fw-bold text-center">Prazo Real</th>
                            <th scope="col" class="fw-bold text-center">Situação</th>
                            <th scope="col" class="fw-bold text-center"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            if (!function_exists('dispatchMainShortName')) {
                                function dispatchMainShortName($name)
                                {
                                    $name = trim((string) $name);

                                    if ($name === '') {
                                        return 'Desconhecido';
                                    }

                                    $parts = collect(explode(' ', $name))->filter()->values();
                                    $shortName = $parts->count() > 1
                                        ? $parts->first() . ' ' . $parts->last()
                                        : $parts->first();

                                    return mb_convert_case(mb_strtolower($shortName, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
                                }
                            }
                        @endphp
                        @foreach ($lists as $list)
                            @php
                                $e = $this->needBlock($list);
                                $rowClass = $e['color'];
                                $block = (int) $e['block'];
                                $command = (bool) ($e['command'] ?? false);
                                $production = $e['production'];
                                $reason = $e['reason'];
                                $lastUser = '';

                                $count2 = $list->Productions
                                    ->where('service_id', $service->uuid)
                                    ->where('completed', true);

                                if ($count2->count()) {
                                    // $lastUser = $list->Productions
                                    //     ->where('service_id', $service->uuid)
                                    //     ->where('completed', true)
                                    //     ->last()->User->name;

                                    $lastUser = dispatchMainShortName($count2->last()->User?->name);
                                }

                                $stackProductionAvailable = \App\Support\SicodeRules::openCompanyStackProductionFor($list, Auth()->User(), $service->uuid);
                                $canDispatch = !$block || $command || $stackProductionAvailable;

                                if ($stackProductionAvailable) {
                                    $rowClass = '';
                                }

                                if (!$canDispatch) {
                                    $chave = array_search($list->id, $selected);

                                    if ($chave !== false) {
                                        unset($selected[$chave]);
                                        $selected = $selected;
                                    }
                                }

                                $currentAssignee = $production?->User
                                    ? dispatchMainShortName($production->User->name)
                                    : 'Pilha';

                                if ($production && $production->Company) {
                                    $lastCompany = explode(' ', $production->Company->name);
                                    $lastCompany = mb_convert_case(mb_strtolower($lastCompany[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
                                } else {
                                    $lastCompany = 'Desconhecido';
                                }
                            @endphp


                            <tr class="align-middle">
                                <td class="{{ $rowClass }}">
                                    <input class="form-check-input border border-1 border-primary" type="checkbox"
                                        value="{{ $list->id }}" wire:model.defer="selected"
                                        @disabled(!$canDispatch)>
                                </td>
                                {{-- @can('management')
                                        <td class="fw-bold copy-text" data-value="{{ $list->note }}">{{ $list->note }}
                                        </td>
                                    @endcan --}}
                                <td class="fw-bold copy-text text-center {{ $rowClass }} @if ($list->is45) text-bg-warning @endif"
                                    data-value="{{ $list->note }}">
                                    <span>
                                        {{ $list->note }}
                                        @if ($list->is45)
                                            <span tabindex="0" data-bs-toggle="popover"
                                                data-bs-trigger="hover focus" data-bs-placement="top"
                                                data-bs-title="NOTA EXPRESSA"
                                                data-bs-content="Nota com prazo de execução de 45 dias"
                                                style="z-index: 9999;" data-bs-toggle="tooltip"
                                                data-bs-placement="top">
                                                <i class="ri-fire-line text-danger fw-bold"
                                                    style="display: inline-block; animation: flame 1s steps(1) infinite;"></i>
                                            </span>
                                        @endif
                                    </span>
                                    <x-legal.note-demand-tags :note-id="$list->note_id ?? $list->id" :row-key="'dispatchs-survey-main-'.$list->id" />
                                </td>
                                <td class="fw-bold text-danger text-center {{ $rowClass }}">
                                    {{ \App\Support\SicodeRules::dispatchDdFor($list, $service->uuid) ?? '' }}
                                </td>
                                <td class="fw-bold text-danger text-center {{ $rowClass }}">
                                    {{ $list->mmgd ? 'MMGD' : '' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ date('d/m/Y', strToTime($list->dt_created)) }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ mb_strtoupper($list->numPedido) }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->rubrica }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if (!empty($list->lexp))
                                        {{ $list->lexp }}
                                    @else
                                        <span tabindex="1" data-bs-toggle="popover" data-bs-trigger="hover focus"
                                            data-bs-placement="top" data-bs-title="Editar Município"
                                            data-bs-content="Clique para editar o município faltante para esta nota.">
                                            <button class="btn btn-sm btn-secondary"
                                                wire:click.prevent="$emit('editMunicipio', '{{ $list->id }}')">Edit</button>
                                        </span>

                                    @endif

                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group1 }}</td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group2 ? $list->group2 : '_____' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group4 ? $list->group4 : '_____' }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">{{ $list->group5 ? $list->group5 : '_____' }}
                                </td>



                                <td class="fw-light text-center {{ $rowClass }}" tabindex="2" data-bs-toggle="popover"
                                    data-bs-trigger="hover focus" data-bs-placement="top"
                                    data-bs-title="Levantamentos Realizados"
                                    data-bs-content="Informa se esta NOTA/OV específica já passou por este estatus antes. Caso afirmativo, é exibido a quantidade de vezes e a última pessoa a encerrar esta NOTA/OV neste SERVIÇO.">
                                    @if ($count2->count())
                                        <span class="badge text-bg-dark">{{ $count2->count() }}</span><br>
                                        {{ $lastUser }}
                                    @else
                                        --
                                    @endif
                                </td>

                                <td class="fw-light text-center {{ $rowClass }}">
                                    {{ $list->nstats }}<br><span>{{ $list->centerjob }}</span></td>
                                {{-- <td class="fw-light text-center">{{ $list->pze }}</td> --}}
                                @php
                                    $days_left = (new DaysLeft($list))->getDaysLeft();
                                @endphp
                                <td scope="col"
                                    class="text-center
                                        @if ($days_left < 0) text-bg-secondary
                                        @elseif($days_left >= 0 && $days_left < 6)
                                        table-danger
                                        @elseif($days_left >= 6 && $days_left < 10)
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
                                    {{ 30 - $days_left }}
                                </td>
                                <td class="fw-light text-center {{ $rowClass }}">
                                    @if ($list->pze_parecer === 'Vencido')
                                        <span class="badge text-bg-danger">VENCIDO</span>
                                    @elseif ($list->pze_parecer === 'Não vencido')
                                        <span class="badge text-bg-success">EM PRAZO</span>
                                    @else
                                        <span class="badge text-bg-secondary">DESCONHECIDO</span>
                                    @endif
                                </td>


                                <td class="fw-bold text-center {{ $rowClass }}" data-bs-toggle="tooltip"
                                    data-bs-placement="top" data-bs-title="{{ $reason }}">

                                    @can('operator')
                                        @if ($canDispatch)
                                            <i class="ri-play-circle-line my-0 align-middle  text-success fs-4"
                                                style="cursor: pointer;"
                                                wire:click.prevent="$emitTo('dispatchs.shared.dispatch-modal', 'openForNotes', [{{ $list->id }}])"
                                                data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-custom-class="custom-tooltip"
                                                data-bs-title="{{ $stackProductionAvailable ? 'Assumir/atribuir Nota/OV da pilha da empresa' : 'Despachar esta Nota/OV' }}"></i>
                                        @else
                                            <div style="font-size: 11px; line-height: 1.15;">
                                                <strong>{{ $currentAssignee ?: 'Desconhecido' }}</strong>
                                                <div class="text-muted">{{ $lastCompany }}</div>
                                            </div>
                                        @endif
                                    @endcan

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif
        </div>

        <div class="summary-bar mt-3">
            <div class="row align-items-center g-2">
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
    </div>


    {{-- MODALS --}}

    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade bulk-search-modal" id="buscar_multi" tabindex="-1" aria-labelledby="buscarMultiLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel">Buscar em massa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="advanceSearch">Notas</label>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch"
                        wire:model.defer="advanceSearch" placeholder="Cole uma nota por linha ou separe por espaço, vírgula ou ponto e vírgula."></textarea>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="bulkSearchAnyStatusSurvey"
                            wire:model="bulkSearchAnyStatus">
                        <label class="form-check-label fw-semibold" for="bulkSearchAnyStatusSurvey">
                            Buscar em qualquer Status
                        </label>
                    </div>

                    @if ($bulkSearchAnyStatus)
                        <div class="bulk-search-warning mt-3">
                            Confirme que deseja ignorar o filtro de Status da lista. A busca continuará respeitando as regras de contrato e acesso.
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger" wire:click="clean">Limpar</button>
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">
                        <i class="ri-search-line"></i> Aplicar busca
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div wire:ignore.self class="modal fade" id="add_mass_dds" tabindex="-1" aria-labelledby="addMassDdsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content association-modal">
                <div class="modal-header">
                    <div>
                        <h1 class="modal-title" id="addMassDdsModalLabel">Associar Nota/OV e DD em {{ $service->service }}</h1>
                        <div class="small text-white-50 mt-1">Uma associação por linha. Use somente números.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    <div class="association-help mb-3">
                        <div class="fw-semibold mb-2">Formato aceito</div>
                        <div class="small mb-2">
                            Informe <strong>Nota/OV</strong> e <strong>DD</strong> na mesma linha. Separadores aceitos:
                            espaço, TAB, ponto e vírgula ou vírgula.
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="association-example">4001123232 14034330</span>
                            <span class="association-example">4001123232;14034330</span>
                            <span class="association-example">4001123232,14034330</span>
                        </div>
                        <div class="small text-secondary mt-2">
                            A associação informada aqui é soberana: se a DD já estiver em outra Nota/OV, o sistema move a DD para a Nota/OV informada.
                            Se já estiver na própria Nota/OV, o vínculo é mantido.
                        </div>
                    </div>

                    <label for="surveyMassDdInput" class="form-label fw-semibold">Notas/OVs e DDs</label>
                    <textarea class="form-control association-textarea" id="surveyMassDdInput" rows="10"
                        placeholder="4001123232 14034330&#10;4001123233 14034331&#10;4001123234 14034332"
                        wire:model.defer="enter_dd"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" wire:click.prevent="$set('enter_dd', '')">
                        Limpar
                    </button>
                    <button type="button" class="btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="mass_modal">
                        Associar DD
                    </button>
                </div>
            </div>
        </div>
    </div>

    @livewire('dispatchs.shared.dispatch-modal', ['serviceId' => $service->uuid], key('dispatch-modal-'.$service->uuid))




    {{-- END MODALS --}}



</div>
