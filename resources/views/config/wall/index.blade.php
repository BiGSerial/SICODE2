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
            grid-template-columns: repeat(3, minmax(300px, 1fr));
            gap: 1rem;
            align-items: start;
        }

        .wall-card {
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
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
            max-height: 420px;
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
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            border: 1px solid rgba(15, 23, 42, .12);
            border-radius: 8px;
            padding: .45rem .55rem;
            background: #fff;
        }

        .service-item.dragging {
            opacity: .5;
        }

        .service-list {
            display: grid;
            gap: .45rem;
            max-height: 260px;
            overflow: auto;
        }

        .section-divider {
            border-top: 1px dashed rgba(15, 23, 42, .16);
            margin: .75rem 0;
        }

        @media (max-width: 1280px) {
            .wall-board {
                grid-template-columns: repeat(2, minmax(280px, 1fr));
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

            <div class="wall-card hidden-card" id="card-4">
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

            <div class="wall-card hidden-card" id="card-5">
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
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-cancel-create-screen">Cancelar</button>
                            <button class="btn btn-success btn-sm">Criar tela</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="wall-card hidden-card" id="card-6">
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
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary btn-sm">Salvar tela</button>
                        </div>
                    </form>

                    <div class="section-divider" id="edit-screen-prod-divider"></div>

                    <div id="edit-screen-production-area">
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
                editScreenForm: document.getElementById('form-edit-screen'),
                editScreenWallId: document.getElementById('edit-screen-wall-id'),
                editScreenName: document.getElementById('edit-screen-name'),
                editScreenType: document.getElementById('edit-screen-type'),
                editScreenDuration: document.getElementById('edit-screen-duration'),
                editScreenServiceRotation: document.getElementById('edit-screen-service-rotation'),
                editScreenFixedWrap: document.getElementById('edit-screen-fixed-wrap'),
                editScreenSrvWrap: document.getElementById('edit-screen-service-rotation-wrap'),
                editScreenFixedChart: document.getElementById('edit-screen-fixed-chart'),
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
                el.createScreenFixedWrap.classList.toggle('hidden-card', !isFixed);
                el.createScreenSrvWrap.classList.toggle('hidden-card', type !== 'production_services');
            }

            function toggleEditScreenType() {
                const type = String(el.editScreenType.value || 'production_services');
                const isFixed = type === 'fixed_chart' || type === 'ads_chart';
                el.editScreenFixedWrap.classList.toggle('hidden-card', !isFixed);
                el.editScreenSrvWrap.classList.toggle('hidden-card', type !== 'production_services');
                el.editScreenProdArea.classList.toggle('hidden-card', type !== 'production_services');
                el.editScreenProdDivider.classList.toggle('hidden-card', type !== 'production_services');
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

                el.card6Title.textContent = `6. Editar Tela: ${screen.name}`;
                el.editScreenForm.action = screen.update_url;
                el.editScreenWallId.value = String(wall.id);
                el.editScreenName.value = screen.name;
                el.editScreenType.value = screen.screen_type;
                el.editScreenDuration.value = screen.duration_seconds || rotationDefault;
                el.editScreenServiceRotation.value = screen.service_rotation_seconds || rotationDefault;
                el.editScreenFixedChart.value = screen.fixed_chart || 'ads_dashboard';

                toggleEditScreenType();
            }

            function renderItems() {
                const screen = selectedScreen();
                if (!screen) {
                    el.itemList.innerHTML = '<div class="wall-muted">Selecione uma tela.</div>';
                    return;
                }

                const items = (screen.items || []).slice().sort((a, b) => (a.display_order - b.display_order) || (a.id - b.id));
                if (!items.length) {
                    el.itemList.innerHTML = '<div class="wall-muted">Nenhum serviço adicionado.</div>';
                    return;
                }

                el.itemList.innerHTML = items.map((item) => `
                    <div class="service-item" draggable="true" data-item-id="${item.id}">
                        <div>
                            <div><strong>${escapeHtml(item.service_name)}</strong></div>
                            <div class="screen-meta">Anterior: ${escapeHtml(item.previous_service_name || '-')} · ${item.use_rule_builder ? 'RuleBuilder' : 'Status fixo'}</div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="danger-icon-btn" data-delete-item-id="${item.id}" title="Excluir item">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </div>
                `).join('');

                bindItemDnD();

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

            document.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', () => {
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
