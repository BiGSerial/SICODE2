@extends('layouts.padrao')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="py-0 my-0">
        <ol class="breadcrumb bg-light px-3 pt-3 rounded-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('config.main') }}">Configurações</a></li>
                <li class="breadcrumb-item active" aria-current="page">WALL Produção</li>
            </ol>
        </ol>
    </nav>
@endsection

@section('menu')
    @include('config.wall.menu')
@endsection

@push('css')
    <style>
        .wall-board {
            display: grid;
            grid-template-columns: repeat(3, minmax(340px, 1fr));
            grid-auto-rows: minmax(120px, auto);
            gap: 1.1rem;
            align-items: start;
        }

        .wall-card {
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(15, 23, 42, .04);
        }

        .wall-card--wide {
            grid-column: span 2;
        }

        .wall-card--tall .wall-card__body {
            min-height: 520px;
            overflow: auto;
        }

        .wall-card__head {
            padding: .75rem .9rem;
            background: #f8fafc;
            border-bottom: 1px solid rgba(15, 23, 42, .1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .wall-card__title {
            margin: 0;
            font-size: .95rem;
            font-weight: 700;
            color: #0f172a;
        }

        .wall-card__body {
            padding: .9rem;
        }

        .wall-muted {
            font-size: .8rem;
            color: #64748b;
        }

        .wall-list {
            display: grid;
            gap: .45rem;
            max-height: 520px;
            overflow: auto;
        }

        .wall-list-item {
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 8px;
            padding: .5rem .55rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            background: #fff;
            cursor: pointer;
        }

        .wall-list-item.active {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .wall-list-item[draggable="true"] {
            cursor: grab;
        }

        .wall-list-item.dragging {
            opacity: .5;
        }

        .screen-meta {
            font-size: .74rem;
            color: #64748b;
        }

        .danger-icon-btn {
            border: 0;
            background: transparent;
            color: #dc2626;
            padding: .15rem .3rem;
            border-radius: 6px;
        }

        .danger-icon-btn:hover {
            background: rgba(220, 38, 38, .1);
        }

        .hidden-card {
            display: none;
        }

        .service-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .5rem;
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 8px;
            padding: .6rem .65rem;
            background: #fff;
        }

        .service-item.dragging {
            opacity: .5;
        }

        .service-list {
            display: grid;
            gap: .6rem;
            max-height: 420px;
            overflow: auto;
        }

        #edit-screen-production-sources-json,
        #create-screen-production-sources-json {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: .78rem;
        }

        #edit-screen-production-area .screen-meta {
            font-size: .76rem;
        }

        .section-divider {
            border-top: 1px dashed rgba(15, 23, 42, .16);
            margin: .75rem 0;
        }

        @media (max-width: 1280px) {
            .wall-board {
                grid-template-columns: repeat(2, minmax(280px, 1fr));
            }

            .wall-card--wide {
                grid-column: span 1;
            }

            .wall-card--tall .wall-card__body {
                min-height: 420px;
            }
        }

        @media (max-width: 992px) {
            .wall-board {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-3">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="wall-board" id="wall-board">
            <div class="wall-card" id="card-1">
                <div class="wall-card__head">
                    <h6 class="wall-card__title">1. Padrão Global</h6>
                </div>
                <div class="wall-card__body">
                    <form method="POST" action="{{ route('config.wall.settings') }}" class="row g-2">
                        @csrf
                        <div class="col-6">
                            <label class="form-label">Rotação tela (s)</label>
                            <input type="number" min="10" max="3600" class="form-control" name="rotation_seconds"
                                value="{{ old('rotation_seconds', $rotationSeconds) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Refresh API (s)</label>
                            <input type="number" min="10" max="3600" class="form-control" name="refresh_seconds"
                                value="{{ old('refresh_seconds', $refreshSeconds) }}">
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center mt-2">
                            <span class="wall-muted">Walls cadastrados: <strong id="walls-count">{{ $walls->count() }}</strong></span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-open-create-wall">+ Novo Wall</button>
                                <button class="btn btn-primary btn-sm">Salvar padrão</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="wall-card hidden-card" id="card-2">
                <div class="wall-card__head">
                    <h6 class="wall-card__title">2. Criar Wall</h6>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-close-create-wall">Fechar</button>
                </div>
                <div class="wall-card__body">
                    <form method="POST" action="{{ route('config.wall.wall.store') }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Nome do Wall</label>
                            <input type="text" class="form-control" name="name" required placeholder="Ex: WALL 2">
                        </div>
                        <div class="col-12 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" value="1" name="enabled" checked>
                                <label class="form-check-label">Ativo</label>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-create-wall">Cancelar</button>
                            <button class="btn btn-success btn-sm">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="wall-card" id="card-3">
                <div class="wall-card__head">
                    <h6 class="wall-card__title">3. Lista de Walls</h6>
                </div>
                <div class="wall-card__body">
                    <div class="wall-list" id="wall-list"></div>
                    <div class="wall-muted mt-2">Clique em um wall para abrir suas telas.</div>
                </div>
            </div>

            <div class="wall-card wall-card--wide hidden-card" id="card-4">
                <div class="wall-card__head">
                    <h6 class="wall-card__title" id="card-4-title">4. Telas do Wall</h6>
                    <button class="btn btn-sm btn-outline-primary" type="button" id="btn-open-create-screen">+ Tela</button>
                </div>
                <div class="wall-card__body">
                    <div class="wall-list" id="screen-list"></div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="wall-muted">Arraste e solte para reordenar (salvamento automático).</span>
                    </div>
                </div>
            </div>

            <div class="wall-card wall-card--wide hidden-card" id="card-5">
                <div class="wall-card__head">
                    <h6 class="wall-card__title">5. Nova Tela</h6>
                </div>
                <div class="wall-card__body">
                    <form method="POST" action="{{ route('config.wall.screen.store') }}" id="form-create-screen" class="row g-2">
                        @csrf
                        <input type="hidden" name="wall_id" id="create-screen-wall-id">
                        <div class="col-12">
                            <label class="form-label">Nome da tela</label>
                            <input class="form-control" name="name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Duração da tela (s)</label>
                            <input class="form-control" type="number" min="10" max="3600" name="duration_seconds" value="{{ $rotationSeconds }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tipo da tela</label>
                            <select class="form-select" name="screen_type" id="create-screen-type" required>
                                @foreach ($screenTypes as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 hidden-card" id="create-screen-fixed-wrap">
                            <label class="form-label">Gráfico fixo</label>
                            <select class="form-select" name="fixed_chart">
                                @foreach ($fixedCharts as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12" id="create-screen-service-rotation-wrap">
                            <label class="form-label">Rotação serviço (s)</label>
                            <input class="form-control" type="number" min="10" max="3600" name="service_rotation_seconds" value="{{ $rotationSeconds }}">
                        </div>
                        <div class="col-12" id="create-screen-production-source-wrap">
                            <label class="form-label">Fonte padrão (produção)</label>
                            <select class="form-select" name="production_source" id="create-screen-production-source">
                                @foreach ($productionSources as $k => $label)
                                    <option value="{{ $k }}" @selected($k === 'rule_builder')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12" id="create-screen-production-sources-wrap">
                            <label class="form-label">Fontes por serviço (JSON gerado automaticamente)</label>
                            <input type="hidden" name="production_sources_json" id="create-screen-production-sources-json" value="{}">
                            <pre class="form-control" id="create-screen-production-sources-json-view" style="min-height:140px;white-space:pre-wrap;">{}</pre>
                            <small class="text-muted">Este JSON é gerado automaticamente quando você configurar os serviços na edição da tela.</small>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-create-screen">Cancelar</button>
                            <button class="btn btn-success btn-sm">Criar tela</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="wall-card wall-card--wide wall-card--tall hidden-card" id="card-6">
                <div class="wall-card__head">
                    <h6 class="wall-card__title" id="card-6-title">6. Editar Tela</h6>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="btn-close-edit-screen">Fechar</button>
                </div>
                <div class="wall-card__body">
                    <form method="POST" id="form-edit-screen" class="row g-2" data-wall-screen-form>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="wall_id" id="edit-screen-wall-id">
                        <div class="col-12">
                            <label class="form-label">Nome da tela</label>
                            <input class="form-control" name="name" id="edit-screen-name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Duração da tela (s)</label>
                            <input class="form-control" type="number" min="10" max="3600" name="duration_seconds" id="edit-screen-duration" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tipo da tela</label>
                            <select class="form-select" name="screen_type" id="edit-screen-type" required>
                                @foreach ($screenTypes as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 hidden-card" id="edit-screen-fixed-wrap">
                            <label class="form-label">Gráfico fixo</label>
                            <select class="form-select" name="fixed_chart" id="edit-screen-fixed-chart">
                                @foreach ($fixedCharts as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12" id="edit-screen-service-rotation-wrap">
                            <label class="form-label">Rotação serviço (s)</label>
                            <input class="form-control" type="number" min="10" max="3600" name="service_rotation_seconds" id="edit-screen-service-rotation">
                        </div>
                        <div class="col-12" id="edit-screen-production-source-wrap">
                            <label class="form-label">Fonte padrão (produção)</label>
                            <select class="form-select" name="production_source" id="edit-screen-production-source">
                                @foreach ($productionSources as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12" id="edit-screen-production-sources-wrap">
                            <label class="form-label">Fontes por serviço (JSON gerado automaticamente)</label>
                            <input type="hidden" name="production_sources_json" id="edit-screen-production-sources-json" value="{}">
                            <pre class="form-control" id="edit-screen-production-sources-json-view" style="min-height:160px;white-space:pre-wrap;">{}</pre>
                            <small class="text-muted">Configure por item abaixo; o JSON é montado automaticamente.</small>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary btn-sm">Salvar tela</button>
                        </div>
                    </form>

                    <div class="section-divider" id="edit-screen-prod-divider"></div>

                    <div id="edit-screen-production-area">
                        <div class="alert alert-light border mb-2 py-2">
                            <div class="fw-bold mb-1">Referência técnica de filtros</div>
                            <div class="small text-muted mb-1">
                                Modelo base: <code>{{ $productionFilterSchema['base']['model'] ?? 'App\\Models\\Note' }}</code>.
                                Em <strong>escopo base</strong>, filtre colunas do modelo base.
                                Em <strong>escopo relation</strong>, escolha relação e coluna da relação.
                            </div>
                            <div class="small text-muted mb-1">
                                Colunas base (fillable):
                                @foreach (($productionFilterSchema['base']['columns'] ?? []) as $column)
                                    <code>{{ $column }}</code>@if (!$loop->last), @endif
                                @endforeach
                            </div>
                            <div class="small text-muted">
                                Relações e modelos:
                                @foreach (($productionFilterSchema['relations'] ?? []) as $relationName => $meta)
                                    <div>
                                        <code>{{ $relationName }}</code>
                                        @if (!empty($meta['model']))
                                            => <code>{{ $meta['model'] }}</code>
                                        @endif
                                        @if (!empty($meta['columns']))
                                            | fillable:
                                            @foreach ($meta['columns'] as $col)
                                                <code>{{ $col }}</code>@if (!$loop->last), @endif
                                            @endforeach
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label">Atividade anterior (opcional)</label>
                                <select class="form-select form-select-sm" id="new-item-previous-service">
                                    <option value="">-</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Serviço</label>
                                <select class="form-select form-select-sm" id="new-item-service">
                                    <option value="">Selecione</option>
                                    @foreach ($services as $service)
                                        <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-success btn-sm w-100" type="button" id="btn-add-item">Adicionar serviço</button>
                            </div>
                        </div>

                        <div class="service-list" id="item-list"></div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="wall-muted">Arraste para reordenar atividades (salvamento automático).</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="wall-hidden-actions" class="d-none">
            @foreach ($walls as $wall)
                <form method="POST" action="{{ route('config.wall.wall.delete', $wall) }}" id="delete-wall-form-{{ $wall->id }}">
                    @csrf
                    @method('DELETE')
                </form>

                @foreach ($wall->screens as $screen)
                    <form method="POST" action="{{ route('config.wall.screen.delete', $screen) }}" id="delete-screen-form-{{ $screen->id }}">
                        @csrf
                        @method('DELETE')
                    </form>

                    @foreach ($screen->items as $item)
                        <form method="POST" action="{{ route('config.wall.item.delete', $item) }}" id="delete-item-form-{{ $item->id }}">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                @endforeach
            @endforeach
        </div>
    </div>
@endsection

@php
    $wallsPayload = $walls->map(function ($wall) {
        return [
            'id' => (int) $wall->id,
            'name' => (string) $wall->name,
            'enabled' => (bool) $wall->enabled,
            'display_order' => (int) $wall->display_order,
            'open_url' => route('reports.wall.production_v2', ['wall' => $wall->id]),
            'screens' => $wall->screens->map(function ($screen) {
                return [
                    'id' => (int) $screen->id,
                    'wall_id' => (int) $screen->wall_id,
                    'name' => (string) $screen->name,
                    'enabled' => (bool) $screen->enabled,
                    'display_order' => (int) $screen->display_order,
                    'screen_type' => (string) $screen->screen_type,
                    'duration_seconds' => (int) ($screen->duration_seconds ?? 0),
                    'service_rotation_seconds' => (int) ($screen->service_rotation_seconds ?? 0),
                    'fixed_chart' => (string) (($screen->screen_config['fixed_chart'] ?? (($screen->screen_type ?? '') === 'ads_chart' ? 'ads_dashboard' : ''))),
                    'production_source' => (string) (($screen->screen_config['production_source'] ?? 'rule_builder')),
                    'production_sources_map' => (array) ($screen->screen_config['production_sources'] ?? []),
                    'production_sources_json' => json_encode((array) ($screen->screen_config['production_sources'] ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    'update_url' => route('config.wall.screen.update', ['screen' => $screen->id]),
                    'delete_url' => route('config.wall.screen.delete', ['screen' => $screen->id]),
                    'open_url' => route('reports.wall.production_v2.screen', ['wall' => $screen->wall_id, 'screen' => $screen->id]),
                    'store_item_url' => route('config.wall.item.store', ['screen' => $screen->id]),
                    'items' => $screen->items->map(function ($item) {
                        return [
                            'id' => (int) $item->id,
                            'service_id' => (string) $item->service_id,
                            'service_name' => (string) ($item->service?->service ?? $item->service_id),
                            'previous_service_id' => (string) ($item->previous_service_id ?? ''),
                            'previous_service_name' => (string) ($item->previousService?->service ?? '-'),
                            'enabled' => (bool) $item->enabled,
                            'use_rule_builder' => (bool) $item->use_rule_builder,
                            'display_order' => (int) $item->display_order,
                            'update_url' => route('config.wall.item.update', ['item' => $item->id]),
                            'delete_url' => route('config.wall.item.delete', ['item' => $item->id]),
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrf = '{{ csrf_token() }}';
            const rotationDefault = Number(@json($rotationSeconds));
            const productionSourceOptions = @json($productionSources);
            const productionFilterSchema = @json($productionFilterSchema);

            const WALLS_RAW = @json($wallsPayload);
            const WALLS = Array.isArray(WALLS_RAW) ? WALLS_RAW : Object.values(WALLS_RAW || {});

            const state = {
                walls: WALLS,
                selectedWallId: null,
                selectedScreenId: null,
                draggingScreenId: null,
                draggingItemId: null,
                orderSavingScreens: false,
                orderSavingItems: false,
            };
            const uiStateKey = 'wall-config-ui-state-v1';

            const el = {
                card2: document.getElementById('card-2'),
                card5: document.getElementById('card-5'),
                card6: document.getElementById('card-6'),
                card4: document.getElementById('card-4'),
                wallList: document.getElementById('wall-list'),
                screenList: document.getElementById('screen-list'),
                itemList: document.getElementById('item-list'),
                card4Title: document.getElementById('card-4-title'),
                card6Title: document.getElementById('card-6-title'),
                wallsCount: document.getElementById('walls-count'),
                createScreenWallId: document.getElementById('create-screen-wall-id'),
                createScreenType: document.getElementById('create-screen-type'),
                createScreenFixedWrap: document.getElementById('create-screen-fixed-wrap'),
                createScreenSrvWrap: document.getElementById('create-screen-service-rotation-wrap'),
                createScreenProductionSourceWrap: document.getElementById('create-screen-production-source-wrap'),
                createScreenProductionSourcesWrap: document.getElementById('create-screen-production-sources-wrap'),
                createScreenProductionSource: document.getElementById('create-screen-production-source'),
                createScreenProductionSourcesJson: document.getElementById('create-screen-production-sources-json'),
                createScreenProductionSourcesJsonView: document.getElementById('create-screen-production-sources-json-view'),
                editScreenForm: document.getElementById('form-edit-screen'),
                editScreenWallId: document.getElementById('edit-screen-wall-id'),
                editScreenName: document.getElementById('edit-screen-name'),
                editScreenType: document.getElementById('edit-screen-type'),
                editScreenDuration: document.getElementById('edit-screen-duration'),
                editScreenServiceRotation: document.getElementById('edit-screen-service-rotation'),
                editScreenFixedWrap: document.getElementById('edit-screen-fixed-wrap'),
                editScreenSrvWrap: document.getElementById('edit-screen-service-rotation-wrap'),
                editScreenFixedChart: document.getElementById('edit-screen-fixed-chart'),
                editScreenProductionSourceWrap: document.getElementById('edit-screen-production-source-wrap'),
                editScreenProductionSourcesWrap: document.getElementById('edit-screen-production-sources-wrap'),
                editScreenProductionSource: document.getElementById('edit-screen-production-source'),
                editScreenProductionSourcesJson: document.getElementById('edit-screen-production-sources-json'),
                editScreenProductionSourcesJsonView: document.getElementById('edit-screen-production-sources-json-view'),
                editScreenProdArea: document.getElementById('edit-screen-production-area'),
                editScreenProdDivider: document.getElementById('edit-screen-prod-divider'),
                newItemService: document.getElementById('new-item-service'),
                newItemPreviousService: document.getElementById('new-item-previous-service'),
            };

            if (!el.wallList || !el.screenList || !el.wallsCount) {
                return;
            }

            const labels = {
                production_services: 'Produção',
                fixed_chart: 'FIXO',
                ads_chart: 'ADS',
            };

            const sourceDefaults = {
                publication_note_filter: { filter_group: 'publication', btzeroform: true },
                payment_note_filter: { filter_group: 'payment', search: null },
                publish_repository: { all_services: false },
            };

            function relationOptions() {
                return Object.keys(productionFilterSchema?.relations || {});
            }

            function baseColumns() {
                return Array.isArray(productionFilterSchema?.base?.columns)
                    ? productionFilterSchema.base.columns
                    : [];
            }

            function relationColumns(relationName) {
                return Array.isArray(productionFilterSchema?.relations?.[relationName]?.columns)
                    ? productionFilterSchema.relations[relationName].columns
                    : [];
            }

            function selectedWall() {
                return state.walls.find(w => w.id === state.selectedWallId) || null;
            }

            function selectedScreen() {
                const wall = selectedWall();
                if (!wall) return null;
                return wall.screens.find(s => s.id === state.selectedScreenId) || null;
            }

            function toggleCreateScreenType() {
                const type = String(el.createScreenType.value || 'production_services');
                const isFixed = type === 'fixed_chart' || type === 'ads_chart';
                const isProduction = type === 'production_services';
                el.createScreenFixedWrap.classList.toggle('hidden-card', !isFixed);
                el.createScreenSrvWrap.classList.toggle('hidden-card', !isProduction);
                el.createScreenProductionSourceWrap.classList.toggle('hidden-card', !isProduction);
                el.createScreenProductionSourcesWrap.classList.toggle('hidden-card', !isProduction);
            }

            function toggleEditScreenType() {
                const type = String(el.editScreenType.value || 'production_services');
                const isFixed = type === 'fixed_chart' || type === 'ads_chart';
                const isProduction = type === 'production_services';
                el.editScreenFixedWrap.classList.toggle('hidden-card', !isFixed);
                el.editScreenSrvWrap.classList.toggle('hidden-card', !isProduction);
                el.editScreenProductionSourceWrap.classList.toggle('hidden-card', !isProduction);
                el.editScreenProductionSourcesWrap.classList.toggle('hidden-card', !isProduction);
                el.editScreenProdArea.classList.toggle('hidden-card', !isProduction);
                el.editScreenProdDivider.classList.toggle('hidden-card', !isProduction);
            }

            function openCard2(show) {
                el.card2.classList.toggle('hidden-card', !show);
            }

            function openCard5(show) {
                el.card5.classList.toggle('hidden-card', !show);
                if (show) {
                    el.card6.classList.add('hidden-card');
                }
            }

            function openCard6(show) {
                el.card6.classList.toggle('hidden-card', !show);
                if (show) {
                    el.card5.classList.add('hidden-card');
                }
            }

            function renderWalls() {
                el.wallsCount.textContent = String(state.walls.length);
                if (!state.walls.length) {
                    el.wallList.innerHTML = '<div class="wall-muted">Nenhum wall cadastrado.</div>';
                    el.card4.classList.add('hidden-card');
                    el.screenList.innerHTML = '<div class="wall-muted">Selecione um wall para ver as telas.</div>';
                    openCard5(false);
                    openCard6(false);
                    return;
                }

                const html = state.walls
                    .slice()
                    .sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id))
                    .map((wall) => {
                        const active = wall.id === state.selectedWallId ? 'active' : '';
                        return `
                            <div class="wall-list-item ${active}" data-wall-id="${wall.id}">
                                <div>
                                    <div><strong>${escapeHtml(wall.name)}</strong></div>
                                    <div class="screen-meta">#${wall.id} · ${wall.enabled ? 'Ativo' : 'Inativo'}</div>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="${wall.open_url}">Abrir</a>
                                    <button type="button" class="danger-icon-btn" data-delete-wall-id="${wall.id}" title="Excluir wall">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('');

                el.wallList.innerHTML = html;

                el.wallList.querySelectorAll('[data-wall-id]').forEach((node) => {
                    node.addEventListener('click', (ev) => {
                        if (ev.target.closest('[data-delete-wall-id]')) return;
                        state.selectedWallId = Number(node.dataset.wallId);
                        state.selectedScreenId = null;
                        el.card4.classList.remove('hidden-card');
                        openCard5(false);
                        openCard6(false);
                        renderWalls();
                        renderScreens();
                    });
                });

                el.wallList.querySelectorAll('[data-delete-wall-id]').forEach((btn) => {
                    btn.addEventListener('click', (ev) => {
                        ev.stopPropagation();
                        const id = Number(btn.dataset.deleteWallId);
                        if (!confirm('Confirma excluir este wall e todas as telas?')) return;
                        const form = document.getElementById(`delete-wall-form-${id}`);
                        if (form) {
                            persistUiState();
                            form.submit();
                        }
                    });
                });
            }

            function renderScreens() {
                const wall = selectedWall();
                if (!wall) {
                    el.card4.classList.add('hidden-card');
                    el.card4Title.textContent = '4. Telas do Wall';
                    el.screenList.innerHTML = '<div class="wall-muted">Nenhum wall selecionado.</div>';
                    return;
                }

                el.card4.classList.remove('hidden-card');
                el.card4Title.textContent = `4. Telas do ${wall.name}`;
                el.createScreenWallId.value = String(wall.id);

                const screens = wall.screens.slice().sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id));
                if (!screens.length) {
                    el.screenList.innerHTML = '<div class="wall-muted">Nenhuma tela criada. Use o botão + Tela.</div>';
                    return;
                }

                el.screenList.innerHTML = screens.map((screen) => {
                    const active = screen.id === state.selectedScreenId ? 'active' : '';
                    return `
                        <div class="wall-list-item ${active}" draggable="true" data-screen-id="${screen.id}">
                            <div>
                                <div><strong>${escapeHtml(screen.name)}</strong></div>
                                <div class="screen-meta">${labels[screen.screen_type] || screen.screen_type} · ${screen.duration_seconds || rotationDefault}s · <strong>${screen.enabled ? 'Ativa' : 'Inativa'}</strong></div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <a class="btn btn-sm btn-outline-primary" data-open-screen-link="1" target="_blank" rel="noopener noreferrer" href="${screen.open_url}" title="Abrir esta tela do WALL em nova aba">
                                    <i class="ri-external-link-line"></i>
                                </a>
                                <label class="form-check-label screen-meta d-flex align-items-center gap-1 mb-0">
                                    <input type="checkbox" class="form-check-input m-0" data-toggle-screen-enabled="${screen.id}" ${screen.enabled ? 'checked' : ''}>
                                    Ativa
                                </label>
                                <button type="button" class="danger-icon-btn" data-delete-screen-id="${screen.id}" title="Excluir tela">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                bindScreenDnD();

                el.screenList.querySelectorAll('[data-screen-id]').forEach((node) => {
                    node.addEventListener('click', (ev) => {
                        if (ev.target.closest('[data-open-screen-link]')) return;
                        if (ev.target.closest('[data-toggle-screen-enabled]')) return;
                        if (ev.target.closest('[data-delete-screen-id]')) return;
                        state.selectedScreenId = Number(node.dataset.screenId);
                        openCard6(true);
                        renderScreens();
                        fillScreenEditor();
                        renderItems();
                    });
                });

                el.screenList.querySelectorAll('[data-toggle-screen-enabled]').forEach((input) => {
                    input.addEventListener('change', async (ev) => {
                        const id = Number(input.dataset.toggleScreenEnabled);
                        const wall = selectedWall();
                        if (!wall) return;
                        const screen = wall.screens.find(s => s.id === id);
                        if (!screen) return;
                        const enabled = ev.target.checked ? 1 : 0;
                        const ok = await put(screen.update_url, {
                            wall_id: screen.wall_id,
                            name: screen.name,
                            screen_type: screen.screen_type,
                            fixed_chart: screen.fixed_chart || '',
                            production_source: screen.production_source || 'rule_builder',
                            production_sources_json: screen.production_sources_json || '{}',
                            enabled,
                            display_order: screen.display_order,
                            duration_seconds: screen.duration_seconds || rotationDefault,
                            service_rotation_seconds: screen.service_rotation_seconds || rotationDefault,
                        });
                        if (ok) {
                            screen.enabled = !!enabled;
                            renderScreens();
                        } else {
                            ev.target.checked = !ev.target.checked;
                            alert('Não foi possível atualizar o status da tela.');
                        }
                    });
                });

                el.screenList.querySelectorAll('[data-delete-screen-id]').forEach((btn) => {
                    btn.addEventListener('click', (ev) => {
                        ev.stopPropagation();
                        const id = Number(btn.dataset.deleteScreenId);
                        if (!confirm('Confirma excluir esta tela?')) return;
                        const form = document.getElementById(`delete-screen-form-${id}`);
                        if (form) {
                            persistUiState();
                            form.submit();
                        }
                    });
                });
            }

            function fillScreenEditor() {
                const wall = selectedWall();
                const screen = selectedScreen();
                if (!wall || !screen) return;

                ensureScreenProductionSourcesState(screen);
                el.card6Title.textContent = `6. Editar Tela: ${screen.name}`;
                el.editScreenForm.action = screen.update_url;
                el.editScreenWallId.value = String(wall.id);
                el.editScreenName.value = screen.name;
                el.editScreenType.value = screen.screen_type;
                el.editScreenDuration.value = screen.duration_seconds || rotationDefault;
                el.editScreenServiceRotation.value = screen.service_rotation_seconds || rotationDefault;
                el.editScreenFixedChart.value = screen.fixed_chart || 'ads_dashboard';
                el.editScreenProductionSource.value = screen.production_source || 'rule_builder';
                syncProductionSourcesJson(screen);

                toggleEditScreenType();
            }

            function ensureScreenProductionSourcesState(screen) {
                if (!screen) return;
                if (!screen.production_sources_map || typeof screen.production_sources_map !== 'object') {
                    try {
                        screen.production_sources_map = JSON.parse(screen.production_sources_json || '{}') || {};
                    } catch (e) {
                        screen.production_sources_map = {};
                    }
                }
                if (Array.isArray(screen.production_sources_map)) {
                    screen.production_sources_map = {};
                }
                if (!screen.production_source) {
                    screen.production_source = 'rule_builder';
                }
            }

            function syncProductionSourcesJson(screen) {
                if (!screen) return;
                ensureScreenProductionSourcesState(screen);
                screen.production_sources_json = JSON.stringify(screen.production_sources_map || {}, null, 2);
                if (el.editScreenProductionSourcesJson) {
                    el.editScreenProductionSourcesJson.value = screen.production_sources_json;
                }
                if (el.editScreenProductionSourcesJsonView) {
                    el.editScreenProductionSourcesJsonView.textContent = screen.production_sources_json || '{}';
                }
                if (el.createScreenProductionSourcesJson) {
                    el.createScreenProductionSourcesJson.value = screen.production_sources_json;
                }
                if (el.createScreenProductionSourcesJsonView) {
                    el.createScreenProductionSourcesJsonView.textContent = screen.production_sources_json || '{}';
                }
            }

            function sourceConfigForService(screen, serviceId) {
                ensureScreenProductionSourcesState(screen);
                const raw = screen.production_sources_map?.[serviceId];
                if (!raw) {
                    return { source: screen.production_source || 'rule_builder' };
                }
                if (typeof raw === 'string') {
                    return { source: raw };
                }
                const obj = { ...raw };
                if (!obj.source) obj.source = screen.production_source || 'rule_builder';
                return obj;
            }

            function setSourceConfigForService(screen, serviceId, source, patch = {}) {
                ensureScreenProductionSourcesState(screen);
                const next = { ...(sourceDefaults[source] || {}), ...(patch || {}), source };
                screen.production_sources_map[serviceId] = next;
                syncProductionSourcesJson(screen);
            }

            function pruneSourceMapByCurrentItems(screen) {
                ensureScreenProductionSourcesState(screen);
                const items = Array.isArray(screen.items) ? screen.items : [];
                const allowed = new Set(items.map((it) => String(it.service_id || '')).filter(Boolean));
                Object.keys(screen.production_sources_map || {}).forEach((serviceId) => {
                    if (!allowed.has(String(serviceId))) {
                        delete screen.production_sources_map[serviceId];
                    }
                });
                syncProductionSourcesJson(screen);
            }

            function sourceOptionsHtml(selected) {
                return Object.entries(productionSourceOptions || {})
                    .map(([value, label]) => `<option value="${escapeHtml(value)}" ${String(selected) === String(value) ? 'selected' : ''}>${escapeHtml(label)}</option>`)
                    .join('');
            }

            function sourceParamsHtml(serviceId, source, config) {
                const sid = escapeHtml(serviceId);
                if (source === 'publication_note_filter') {
                    return `
                        <div class="d-flex gap-2 align-items-center mt-1 flex-wrap">
                            <input class="form-control form-control-sm" style="max-width:220px;" data-source-param="${sid}" data-param-key="filter_group" value="${escapeHtml(config.filter_group || 'publication')}" placeholder="filter_group">
                            <label class="form-check-label screen-meta d-flex align-items-center gap-1 mb-0">
                                <input type="checkbox" class="form-check-input m-0" data-source-param="${sid}" data-param-key="btzeroform" ${config.btzeroform === false ? '' : 'checked'}>
                                btzeroform
                            </label>
                        </div>
                    `;
                }
                if (source === 'payment_note_filter') {
                    return `
                        <div class="d-flex gap-2 align-items-center mt-1 flex-wrap">
                            <input class="form-control form-control-sm" style="max-width:220px;" data-source-param="${sid}" data-param-key="filter_group" value="${escapeHtml(config.filter_group || 'payment')}" placeholder="filter_group">
                            <input class="form-control form-control-sm" style="max-width:220px;" data-source-param="${sid}" data-param-key="search" value="${escapeHtml(config.search ?? '')}" placeholder="search (opcional)">
                        </div>
                    `;
                }
                if (source === 'publish_repository') {
                    return `
                        <div class="d-flex gap-2 align-items-center mt-1">
                            <label class="form-check-label screen-meta d-flex align-items-center gap-1 mb-0">
                                <input type="checkbox" class="form-check-input m-0" data-source-param="${sid}" data-param-key="all_services" ${config.all_services ? 'checked' : ''}>
                                all_services
                            </label>
                        </div>
                    `;
                }
                return '';
            }

            function normalizeQueryFilters(config) {
                const raw = Array.isArray(config?.query_filters) ? config.query_filters : [];
                return raw
                    .filter((f) => f && typeof f === 'object')
                    .map((f) => ({
                        mode: String(f.mode || 'exclude'),
                        scope: String(f.scope || 'base'),
                        relation: String(f.relation || ''),
                        column: String(f.column || ''),
                        operator: String(f.operator || 'equals'),
                        value: String(f.value ?? ''),
                    }));
            }

            function queryFiltersHtml(serviceId, config) {
                const sid = escapeHtml(serviceId);
                const filters = normalizeQueryFilters(config);
                const rows = filters.map((f, idx) => `
                    <div class="d-flex gap-2 align-items-center flex-wrap mt-1" data-filter-row="${sid}" data-filter-index="${idx}">
                        <select class="form-select form-select-sm" style="max-width:110px;" data-filter-field="${sid}" data-filter-index="${idx}" data-filter-key="mode">
                            <option value="include" ${f.mode === 'include' ? 'selected' : ''}>Incluir</option>
                            <option value="exclude" ${f.mode === 'exclude' ? 'selected' : ''}>Excluir</option>
                        </select>
                        <select class="form-select form-select-sm" style="max-width:110px;" data-filter-field="${sid}" data-filter-index="${idx}" data-filter-key="scope">
                            <option value="base" ${f.scope === 'base' ? 'selected' : ''}>Base</option>
                            <option value="relation" ${f.scope === 'relation' ? 'selected' : ''}>Relação</option>
                        </select>
                        <select class="form-select form-select-sm ${f.scope === 'relation' ? '' : 'd-none'}" style="max-width:180px;" data-filter-field="${sid}" data-filter-index="${idx}" data-filter-key="relation">
                            <option value="">Relação...</option>
                            ${relationOptions().map((rel) => `<option value="${escapeHtml(rel)}" ${rel === f.relation ? 'selected' : ''}>${escapeHtml(rel)}</option>`).join('')}
                        </select>
                        <select class="form-select form-select-sm" style="max-width:190px;" data-filter-field="${sid}" data-filter-index="${idx}" data-filter-key="column">
                            <option value="">Coluna...</option>
                            ${(f.scope === 'relation' ? relationColumns(f.relation) : baseColumns())
                                .map((col) => `<option value="${escapeHtml(col)}" ${col === f.column ? 'selected' : ''}>${escapeHtml(col)}</option>`)
                                .join('')}
                        </select>
                        <input class="form-control form-control-sm" style="max-width:170px;" placeholder="Coluna manual (opcional)" value="" data-filter-field="${sid}" data-filter-index="${idx}" data-filter-key="column_custom">
                        <select class="form-select form-select-sm" style="max-width:130px;" data-filter-field="${sid}" data-filter-index="${idx}" data-filter-key="operator">
                            <option value="equals" ${f.operator === 'equals' ? 'selected' : ''}>=</option>
                            <option value="starts_with" ${f.operator === 'starts_with' ? 'selected' : ''}>Inicia com</option>
                            <option value="contains" ${f.operator === 'contains' ? 'selected' : ''}>Contém</option>
                            <option value="ends_with" ${f.operator === 'ends_with' ? 'selected' : ''}>Termina com</option>
                        </select>
                        <input class="form-control form-control-sm" style="max-width:190px;" placeholder="Valor" value="${escapeHtml(f.value || '')}" data-filter-field="${sid}" data-filter-index="${idx}" data-filter-key="value">
                        <button type="button" class="btn btn-outline-danger btn-sm" data-remove-filter="${sid}" data-filter-index="${idx}">Remover</button>
                    </div>
                `).join('');

                return `
                    <div class="mt-1">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="screen-meta">Filtros da query para este serviço</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-add-filter="${sid}">+ Filtro</button>
                        </div>
                        <div data-filter-list="${sid}">
                            ${rows || '<div class="screen-meta mt-1">Sem filtros extras.</div>'}
                        </div>
                    </div>
                `;
            }

            function renderItems() {
                const screen = selectedScreen();
                if (!screen) {
                    el.itemList.innerHTML = '<div class="wall-muted">Selecione uma tela.</div>';
                    return;
                }

                ensureScreenProductionSourcesState(screen);
                pruneSourceMapByCurrentItems(screen);

                const items = (screen.items || []).slice().sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id));
                if (!items.length) {
                    el.itemList.innerHTML = '<div class="wall-muted">Nenhum serviço adicionado.</div>';
                    return;
                }

                el.itemList.innerHTML = items.map((item) => {
                    const sourceConfig = sourceConfigForService(screen, String(item.service_id || ''));
                    const source = String(sourceConfig.source || screen.production_source || 'rule_builder');
                    return `
                        <div class="service-item" draggable="true" data-item-id="${item.id}">
                            <div class="w-100">
                                <div><strong>${escapeHtml(item.service_name)}</strong></div>
                                <div class="screen-meta">Anterior: ${escapeHtml(item.previous_service_name || '-')} · ${item.use_rule_builder ? 'RuleBuilder' : 'Status fixo'}</div>
                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                    <label class="screen-meta mb-0">Fonte:</label>
                                    <select class="form-select form-select-sm" style="max-width:280px;" data-source-select-service="${escapeHtml(item.service_id)}">
                                        ${sourceOptionsHtml(source)}
                                    </select>
                                </div>
                                ${sourceParamsHtml(String(item.service_id || ''), source, sourceConfig)}
                                ${queryFiltersHtml(String(item.service_id || ''), sourceConfig)}
                            </div>
                            <div class="d-flex align-items-start gap-1 ms-2">
                                <button type="button" class="danger-icon-btn" data-delete-item-id="${item.id}" title="Excluir item">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');

                bindItemDnD();

                el.itemList.querySelectorAll('[data-source-select-service]').forEach((select) => {
                    select.addEventListener('change', () => {
                        const serviceId = String(select.dataset.sourceSelectService || '');
                        if (!serviceId) return;
                        const source = String(select.value || 'rule_builder');
                        const current = sourceConfigForService(screen, serviceId);
                        setSourceConfigForService(screen, serviceId, source, {
                            ...current,
                            query_filters: normalizeQueryFilters(current),
                        });
                        renderItems();
                    });
                });

                el.itemList.querySelectorAll('[data-source-param]').forEach((input) => {
                    const commit = () => {
                        const serviceId = String(input.dataset.sourceParam || '');
                        const key = String(input.dataset.paramKey || '');
                        if (!serviceId || !key) return;
                        const current = sourceConfigForService(screen, serviceId);
                        const source = String(current.source || screen.production_source || 'rule_builder');
                        const patch = {};
                        if (input.type === 'checkbox') {
                            patch[key] = !!input.checked;
                        } else {
                            const val = String(input.value || '').trim();
                            patch[key] = val === '' ? null : val;
                        }
                        setSourceConfigForService(screen, serviceId, source, { ...current, ...patch });
                    };

                    input.addEventListener('change', commit);
                    if (input.type !== 'checkbox') {
                        input.addEventListener('blur', commit);
                    }
                });

                el.itemList.querySelectorAll('[data-add-filter]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const serviceId = String(btn.dataset.addFilter || '');
                        if (!serviceId) return;
                        const current = sourceConfigForService(screen, serviceId);
                        const filters = normalizeQueryFilters(current);
                        filters.push({
                            mode: 'exclude',
                            scope: 'base',
                            relation: '',
                            column: '',
                            operator: 'equals',
                            value: '',
                        });
                        setSourceConfigForService(screen, serviceId, current.source || screen.production_source || 'rule_builder', {
                            ...current,
                            query_filters: filters,
                        });
                        renderItems();
                    });
                });

                el.itemList.querySelectorAll('[data-remove-filter]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const serviceId = String(btn.dataset.removeFilter || '');
                        const idx = Number(btn.dataset.filterIndex || -1);
                        if (!serviceId || idx < 0) return;
                        const current = sourceConfigForService(screen, serviceId);
                        const filters = normalizeQueryFilters(current);
                        filters.splice(idx, 1);
                        setSourceConfigForService(screen, serviceId, current.source || screen.production_source || 'rule_builder', {
                            ...current,
                            query_filters: filters,
                        });
                        renderItems();
                    });
                });

                el.itemList.querySelectorAll('[data-filter-field]').forEach((input) => {
                    const commit = () => {
                        const serviceId = String(input.dataset.filterField || '');
                        const idx = Number(input.dataset.filterIndex || -1);
                        const key = String(input.dataset.filterKey || '');
                        if (!serviceId || idx < 0 || !key) return;

                        const current = sourceConfigForService(screen, serviceId);
                        const source = String(current.source || screen.production_source || 'rule_builder');
                        const filters = normalizeQueryFilters(current);
                        if (!filters[idx]) return;

                        let nextValue = '';
                        if (input instanceof HTMLSelectElement) {
                            nextValue = String(input.value || '');
                        } else {
                            nextValue = String(input.value || '').trim();
                        }

                        if (key === 'column_custom') {
                            if (nextValue !== '') {
                                filters[idx] = {
                                    ...filters[idx],
                                    column: nextValue,
                                };
                            }
                        } else {
                            const nextFilter = {
                                ...filters[idx],
                                [key]: nextValue,
                            };

                            if (key === 'scope' && nextValue === 'base') {
                                nextFilter.relation = '';
                            }

                            filters[idx] = nextFilter;
                        }

                        setSourceConfigForService(screen, serviceId, source, {
                            ...current,
                            query_filters: filters,
                        });
                        renderItems();
                    };

                    input.addEventListener('change', commit);
                    if (!(input instanceof HTMLSelectElement)) {
                        input.addEventListener('blur', commit);
                    }
                });

                el.itemList.querySelectorAll('[data-delete-item-id]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const id = Number(btn.dataset.deleteItemId);
                        if (!confirm('Confirma excluir este serviço da tela?')) return;
                        const form = document.getElementById(`delete-item-form-${id}`);
                        if (form) {
                            persistUiState();
                            form.submit();
                        }
                    });
                });
            }

            function bindScreenDnD() {
                const nodes = Array.from(el.screenList.querySelectorAll('[data-screen-id]'));
                nodes.forEach((node) => {
                    node.addEventListener('dragstart', () => {
                        state.draggingScreenId = Number(node.dataset.screenId);
                        node.classList.add('dragging');
                    });
                    node.addEventListener('dragend', () => {
                        node.classList.remove('dragging');
                    });
                    node.addEventListener('dragover', (e) => e.preventDefault());
                    node.addEventListener('drop', (e) => {
                        e.preventDefault();
                        const targetId = Number(node.dataset.screenId);
                        if (!state.draggingScreenId || state.draggingScreenId === targetId) return;
                        reorderScreens(state.draggingScreenId, targetId);
                    });
                });
            }

            function bindItemDnD() {
                const nodes = Array.from(el.itemList.querySelectorAll('[data-item-id]'));
                nodes.forEach((node) => {
                    node.addEventListener('dragstart', () => {
                        state.draggingItemId = Number(node.dataset.itemId);
                        node.classList.add('dragging');
                    });
                    node.addEventListener('dragend', () => {
                        node.classList.remove('dragging');
                    });
                    node.addEventListener('dragover', (e) => e.preventDefault());
                    node.addEventListener('drop', (e) => {
                        e.preventDefault();
                        const targetId = Number(node.dataset.itemId);
                        if (!state.draggingItemId || state.draggingItemId === targetId) return;
                        reorderItems(state.draggingItemId, targetId);
                    });
                });
            }

            function reorderScreens(fromId, toId) {
                const wall = selectedWall();
                if (!wall) return;
                const list = wall.screens.slice().sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id));
                const fromIndex = list.findIndex(s => s.id === fromId);
                const toIndex = list.findIndex(s => s.id === toId);
                if (fromIndex < 0 || toIndex < 0) return;
                const [moved] = list.splice(fromIndex, 1);
                list.splice(toIndex, 0, moved);
                list.forEach((screen, idx) => {
                    screen.display_order = idx;
                });
                wall.screens = list;
                renderScreens();
                saveScreenOrder();
            }

            function reorderItems(fromId, toId) {
                const screen = selectedScreen();
                if (!screen) return;
                const list = (screen.items || []).slice().sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id));
                const fromIndex = list.findIndex(i => i.id === fromId);
                const toIndex = list.findIndex(i => i.id === toId);
                if (fromIndex < 0 || toIndex < 0) return;
                const [moved] = list.splice(fromIndex, 1);
                list.splice(toIndex, 0, moved);
                list.forEach((item, idx) => {
                    item.display_order = idx;
                });
                screen.items = list;
                renderItems();
                saveItemOrder();
            }

            async function put(url, payload) {
                const body = new URLSearchParams(payload);
                body.set('_method', 'PUT');
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                    credentials: 'same-origin',
                });
                return res.ok;
            }

            async function saveScreenOrder() {
                if (state.orderSavingScreens) return;
                const wall = selectedWall();
                if (!wall) return;
                state.orderSavingScreens = true;
                const screens = wall.screens.slice().sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id));
                for (const screen of screens) {
                    await put(screen.update_url, {
                        wall_id: screen.wall_id,
                        name: screen.name,
                        screen_type: screen.screen_type,
                        fixed_chart: screen.fixed_chart || '',
                        production_source: screen.production_source || 'rule_builder',
                        production_sources_json: screen.production_sources_json || '{}',
                        enabled: screen.enabled ? 1 : 0,
                        display_order: screen.display_order,
                        duration_seconds: screen.duration_seconds || rotationDefault,
                        service_rotation_seconds: screen.service_rotation_seconds || rotationDefault,
                    });
                }
                state.orderSavingScreens = false;
            }

            async function saveItemOrder() {
                if (state.orderSavingItems) return;
                const screen = selectedScreen();
                if (!screen) return;
                state.orderSavingItems = true;
                const items = (screen.items || []).slice().sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id));
                for (const item of items) {
                    await put(item.update_url, {
                        service_id: item.service_id,
                        previous_service_id: item.previous_service_id || '',
                        enabled: item.enabled ? 1 : 0,
                        use_rule_builder: item.use_rule_builder ? 1 : 0,
                        display_order: item.display_order,
                    });
                }
                state.orderSavingItems = false;
            }

            function addItemToScreen() {
                const screen = selectedScreen();
                if (!screen) return;
                const serviceId = String(el.newItemService.value || '');
                const previousServiceId = String(el.newItemPreviousService.value || '');
                if (!serviceId) {
                    alert('Selecione um serviço para adicionar.');
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = screen.store_item_url;

                const fields = {
                    _token: csrf,
                    service_id: serviceId,
                    previous_service_id: previousServiceId,
                    enabled: 1,
                    use_rule_builder: 1,
                };

                Object.entries(fields).forEach(([k, v]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = k;
                    input.value = String(v ?? '');
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                persistUiState();
                form.submit();
            }

            function escapeHtml(str) {
                return String(str || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function persistUiState() {
                const payload = {
                    selectedWallId: state.selectedWallId,
                    selectedScreenId: state.selectedScreenId,
                    card2Open: !el.card2.classList.contains('hidden-card'),
                    card5Open: !el.card5.classList.contains('hidden-card'),
                    card6Open: !el.card6.classList.contains('hidden-card'),
                };
                sessionStorage.setItem(uiStateKey, JSON.stringify(payload));
            }

            function restoreUiState() {
                try {
                    const raw = sessionStorage.getItem(uiStateKey);
                    if (!raw) return;
                    const saved = JSON.parse(raw);
                    if (saved?.selectedWallId && state.walls.some(w => w.id === Number(saved.selectedWallId))) {
                        state.selectedWallId = Number(saved.selectedWallId);
                    }
                    const wall = selectedWall();
                    if (wall) {
                        el.card4.classList.remove('hidden-card');
                        if (saved?.selectedScreenId && wall.screens.some(s => s.id === Number(saved.selectedScreenId))) {
                            state.selectedScreenId = Number(saved.selectedScreenId);
                        }
                    }
                    if (saved?.card2Open) openCard2(true);
                    if (saved?.card5Open) openCard5(true);
                    if (saved?.card6Open && state.selectedScreenId) openCard6(true);
                } catch (_e) {
                }
            }

            document.getElementById('btn-open-create-wall')?.addEventListener('click', () => openCard2(true));
            document.getElementById('btn-close-create-wall')?.addEventListener('click', () => openCard2(false));
            document.getElementById('btn-cancel-create-wall')?.addEventListener('click', () => openCard2(false));

            document.getElementById('btn-open-create-screen')?.addEventListener('click', () => {
                if (!selectedWall()) {
                    alert('Selecione um wall primeiro.');
                    return;
                }
                openCard5(true);
            });

            document.getElementById('btn-cancel-create-screen')?.addEventListener('click', () => openCard5(false));
            document.getElementById('btn-close-edit-screen')?.addEventListener('click', () => openCard6(false));

            document.getElementById('btn-add-item')?.addEventListener('click', () => addItemToScreen());

            el.createScreenType?.addEventListener('change', toggleCreateScreenType);
            el.editScreenType?.addEventListener('change', toggleEditScreenType);
            el.editScreenProductionSource?.addEventListener('change', () => {
                const screen = selectedScreen();
                if (!screen) return;
                screen.production_source = String(el.editScreenProductionSource.value || 'rule_builder');
                syncProductionSourcesJson(screen);
                renderItems();
            });

            document.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', () => {
                    const screen = selectedScreen();
                    if (screen) {
                        syncProductionSourcesJson(screen);
                    }
                    persistUiState();
                });
            });

            toggleCreateScreenType();
            restoreUiState();
            renderWalls();
            renderScreens();
            if (state.selectedScreenId) {
                openCard6(true);
                fillScreenEditor();
                renderItems();
            }
        });
    </script>
@endpush
