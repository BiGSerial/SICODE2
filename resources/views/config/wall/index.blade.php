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

        <div class="card mb-3">
            <div class="card-header fw-bold">Configuração Global</div>
            <div class="card-body">
                <form method="POST" action="{{ route('config.wall.settings') }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Rotação padrão de tela (s)</label>
                        <input type="number" min="10" max="3600" class="form-control" name="rotation_seconds"
                            value="{{ old('rotation_seconds', $rotationSeconds) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Refresh API (s)</label>
                        <input type="number" min="10" max="3600" class="form-control" name="refresh_seconds"
                            value="{{ old('refresh_seconds', $refreshSeconds) }}">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-bold">Novo Wall</div>
            <div class="card-body">
                <form method="POST" action="{{ route('config.wall.wall.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Nome do Wall</label>
                        <input type="text" class="form-control" name="name" required placeholder="Ex: Wall Monitor 1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ordem</label>
                        <input type="number" min="0" class="form-control" name="display_order" value="0">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" name="enabled" checked>
                            <label class="form-check-label">Ativo</label>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-success w-100">Criar Wall</button>
                    </div>
                </form>
            </div>
        </div>

        @forelse($walls as $wall)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $wall->name }}</strong>
                        <span class="ms-2 badge {{ $wall->enabled ? 'bg-success' : 'bg-secondary' }}">{{ $wall->enabled ? 'Ativo' : 'Inativo' }}</span>
                    </div>
                    <a class="btn btn-sm btn-outline-light" target="_blank"
                        href="{{ route('reports.wall.production_v2', ['wall' => $wall->id]) }}">Abrir Wall #{{ $wall->id }}</a>
                </div>

                <div class="card-body border-bottom">
                    <form method="POST" action="{{ route('config.wall.wall.update', $wall) }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-md-4">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="name" value="{{ $wall->name }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ordem</label>
                            <input type="number" min="0" class="form-control" name="display_order" value="{{ $wall->display_order }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="enabled" {{ $wall->enabled ? 'checked' : '' }}>
                                <label class="form-check-label">Ativo</label>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Salvar Wall</button></div>
                    </form>
                    <form method="POST" action="{{ route('config.wall.wall.delete', $wall) }}" class="mt-2"
                        onsubmit="return confirm('Remover o wall e todas as telas?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">Excluir Wall</button>
                    </form>
                </div>

                <div class="card-body border-bottom">
                    <h6 class="fw-bold">Nova Tela no {{ $wall->name }}</h6>
                    <form method="POST" action="{{ route('config.wall.screen.store') }}" class="row g-2" data-wall-screen-form>
                        @csrf
                        <input type="hidden" name="wall_id" value="{{ $wall->id }}">
                        <div class="col-md-3">
                            <label class="form-label">Nome da tela</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tipo de tela</label>
                            <select name="screen_type" class="form-select js-screen-type" required>
                                @foreach ($screenTypes as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gráfico fixo (se tipo FIXO)</label>
                            <select name="fixed_chart" class="form-select js-fixed-chart">
                                @foreach ($fixedCharts as $k => $label)
                                    <option value="{{ $k }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Ordem</label>
                            <input type="number" min="0" class="form-control" name="display_order" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Duração tela (s)</label>
                            <input type="number" min="10" max="3600" class="form-control" name="duration_seconds" value="600">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Rotação serviço (s)</label>
                            <input type="number" min="10" max="3600" class="form-control js-service-rotation" name="service_rotation_seconds" value="180">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="enabled" checked>
                                <label class="form-check-label">Ativa</label>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-success w-100">Criar tela</button>
                        </div>
                    </form>
                </div>

                <div class="card-body">
                    @forelse($wall->screens as $screen)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ $screen->name }}</strong>
                                <a class="btn btn-sm btn-outline-primary" target="_blank"
                                    href="{{ route('reports.wall.production_v2.screen', ['wall' => $wall->id, 'screen' => $screen->id]) }}">Abrir tela fixa</a>
                            </div>

                            <div class="alert alert-secondary py-2 small">
                                URL: <a target="_blank"
                                    href="{{ route('reports.wall.production_v2.screen', ['wall' => $wall->id, 'screen' => $screen->id]) }}">{{ route('reports.wall.production_v2.screen', ['wall' => $wall->id, 'screen' => $screen->id]) }}</a>
                                <br>Filtro opcional de serviços: <code>?services=uuid1,uuid2</code>
                            </div>

                            <form method="POST" action="{{ route('config.wall.screen.update', $screen) }}" class="row g-2 mb-2" data-wall-screen-form>
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="wall_id" value="{{ $wall->id }}">
                                <div class="col-md-3"><input class="form-control" name="name" value="{{ $screen->name }}"></div>
                                <div class="col-md-2">
                                    <select name="screen_type" class="form-select js-screen-type">
                                        @foreach ($screenTypes as $k => $label)
                                            <option value="{{ $k }}" {{ $screen->screen_type === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="fixed_chart" class="form-select js-fixed-chart">
                                        @foreach ($fixedCharts as $k => $label)
                                            <option value="{{ $k }}" {{ (($screen->screen_config['fixed_chart'] ?? (($screen->screen_type ?? '') === 'ads_chart' ? 'ads_dashboard' : 'ads_dashboard'))) === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1"><input class="form-control" type="number" name="display_order" value="{{ $screen->display_order }}"></div>
                                <div class="col-md-2"><input class="form-control" type="number" name="duration_seconds" value="{{ $screen->duration_seconds }}"></div>
                                <div class="col-md-2"><input class="form-control js-service-rotation" type="number" name="service_rotation_seconds" value="{{ $screen->service_rotation_seconds }}"></div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="form-check me-3">
                                        <input class="form-check-input" type="checkbox" value="1" name="enabled" {{ $screen->enabled ? 'checked' : '' }}>
                                        <label class="form-check-label">Ativa</label>
                                    </div>
                                    <button class="btn btn-primary btn-sm">Salvar</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('config.wall.screen.delete', $screen) }}" class="mb-2" onsubmit="return confirm('Excluir tela?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm">Excluir tela</button>
                            </form>

                            @if ($screen->screen_type === 'production_services')
                                <form method="POST" action="{{ route('config.wall.item.store', $screen) }}" class="row g-2 mb-2">
                                    @csrf
                                    <div class="col-md-3">
                                        <select name="service_id" class="form-select" required>
                                            <option value="">Serviço da atividade</option>
                                            @foreach ($services as $service)
                                                <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="previous_service_id" class="form-select">
                                            <option value="">Serviço anterior (opcional)</option>
                                            @foreach ($services as $service)
                                                <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1"><input class="form-control" type="number" name="display_order" value="0"></div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-check me-3">
                                            <input class="form-check-input" type="checkbox" value="1" name="enabled" checked>
                                            <label class="form-check-label">Ativo</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1" name="use_rule_builder" checked>
                                            <label class="form-check-label">RuleBuilder</label>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button class="btn btn-success btn-sm">Adicionar serviço</button>
                                    </div>
                                </form>

                                @if ($screen->items->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Serviço</th>
                                                    <th>Anterior</th>
                                                    <th>Ordem</th>
                                                    <th>Flags</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($screen->items as $item)
                                                    <tr>
                                                        <td>{{ $item->service?->service ?? $item->service_id }}</td>
                                                        <td>{{ $item->previousService?->service ?? '-' }}</td>
                                                        <td>{{ $item->display_order }}</td>
                                                        <td>
                                                            <span class="badge {{ $item->enabled ? 'bg-success' : 'bg-secondary' }}">{{ $item->enabled ? 'Ativo' : 'Inativo' }}</span>
                                                            <span class="badge {{ $item->use_rule_builder ? 'bg-info' : 'bg-dark' }}">{{ $item->use_rule_builder ? 'RuleBuilder' : 'Status fixo' }}</span>
                                                        </td>
                                                        <td>
                                                            <form method="POST" action="{{ route('config.wall.item.delete', $item) }}" onsubmit="return confirm('Excluir item?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-outline-danger btn-sm">Excluir</button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @else
                                <div class="text-muted small">
                                    Tela do tipo FIXO: os serviços desta tela não são usados.
                                    @if (($screen->screen_config['fixed_chart'] ?? null) === 'ads_dashboard' || $screen->screen_type === 'ads_chart')
                                        Gráfico selecionado: <strong>ADS</strong>
                                    @elseif (($screen->screen_config['fixed_chart'] ?? null) === 'complaints_dashboard')
                                        Gráfico selecionado: <strong>RECLAMAÇÃO</strong>
                                    @elseif (($screen->screen_config['fixed_chart'] ?? null) === 'project_review_dashboard')
                                        Gráfico selecionado: <strong>ANALISE DE PROJETO</strong>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted">Nenhuma tela neste wall.</div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body py-4 text-muted">Nenhum wall cadastrado ainda.</div>
            </div>
        @endforelse
    </div>
@endsection

@push('js')
    <script>
        (function() {
            function syncForm(form) {
                const typeNode = form.querySelector('.js-screen-type');
                const fixedNode = form.querySelector('.js-fixed-chart');
                const rotationNode = form.querySelector('.js-service-rotation');
                if (!typeNode) return;

                const type = String(typeNode.value || '');
                const isFixed = type === 'fixed_chart' || type === 'ads_chart';
                const isProduction = type === 'production_services';

                if (fixedNode) fixedNode.disabled = !isFixed;
                if (rotationNode) rotationNode.disabled = !isProduction;
            }

            document.querySelectorAll('form[data-wall-screen-form]').forEach((form) => {
                syncForm(form);
                const typeNode = form.querySelector('.js-screen-type');
                if (typeNode) {
                    typeNode.addEventListener('change', () => syncForm(form));
                }
            });
        })();
    </script>
@endpush
