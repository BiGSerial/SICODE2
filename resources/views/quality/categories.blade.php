@extends('layouts.padrao')

@section('breadcrumb')
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item">Qualidade</li>
        <li class="breadcrumb-item active" aria-current="page">Motivos de rejeição</li>
    </ol>
@endsection

@section('menu')
    @include('quality._menu')
@endsection

@section('content')
    @include('quality._styles')
    <div class="closure-dash">
        <div class="dash-hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h1 class="dash-title">Motivos de rejeição</h1>
                <div class="dash-subtitle">Categorias e subcategorias usadas pelo N1 e pelo N2. Cada motivo pode valer para o 1º ciclo, o 2º ciclo ou ambas.</div>
            </div>
            <button class="btn btn-light btn-dash" data-bs-toggle="modal" data-bs-target="#quality-category-modal" data-mode="create"><i class="ri-add-line"></i> Novo motivo</button>
        </div>

        @include('quality._feedback')

        <form method="get" class="table-card p-3 mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4"><label class="form-label small mb-1">Buscar</label><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nome do motivo ou detalhe"></div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Aplica-se a</label>
                    <select name="applies" class="form-select form-select-sm"><option value="">Qualquer ciclo</option><option value="PROJECT" @selected(request('applies') === 'PROJECT')>1º ciclo</option><option value="BUDGET" @selected(request('applies') === 'BUDGET')>2º ciclo</option></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Situação</label>
                    <select name="active" class="form-select form-select-sm"><option value="">Todos</option><option value="1" @selected(request('active') === '1')>Ativos</option><option value="0" @selected(request('active') === '0')>Inativos</option></select>
                </div>
                <div class="col-md-2 d-grid"><button class="btn btn-sm btn-primary btn-dash"><i class="ri-search-line"></i> Filtrar</button></div>
            </div>
        </form>

        @php
            $appliesLabel = fn ($c) => $c->applies_project && $c->applies_budget ? 'Ambos os ciclos' : ($c->applies_project ? '1º ciclo' : '2º ciclo');
            $editButton = function ($item, $hasChildren) {
                return 'data-bs-toggle="modal" data-bs-target="#quality-category-modal" data-mode="edit" data-action="' . e(route('quality.categories.update', $item)) . '" data-name="' . e($item->name) . '" data-code="' . e($item->code) . '" data-description="' . e($item->description) . '" data-parent="' . $item->parent_id . '" data-sort="' . $item->sort_order . '" data-project="' . (int) $item->applies_project . '" data-budget="' . (int) $item->applies_budget . '" data-active="' . (int) $item->active . '" data-has-children="' . (int) $hasChildren . '"';
            };
        @endphp
        <div class="row g-3">
            @forelse ($categories as $category)
                <div class="col-xl-6">
                    <div class="table-card h-100 {{ $category->active ? '' : 'opacity-75' }}">
                        <div class="p-3 d-flex align-items-start gap-2">
                            <div class="kpi-ico flex-shrink-0"><i class="ri-price-tag-3-line"></i></div>
                            <div class="flex-grow-1">
                                <div class="fw-bold fs-5">{{ $category->name }}</div>
                                <div class="d-flex flex-wrap gap-1 mt-1">
                                    <span class="chip chip-cycle">{{ $appliesLabel($category) }}</span>
                                    <span class="chip">{{ $category->Children->count() }} subcategoria(s)</span>
                                    @if ($category->code)<span class="chip">{{ $category->code }}</span>@endif
                                    <span class="chip {{ $category->active ? '' : 'chip-reason' }}">{{ $category->active ? 'Ativa' : 'Inativa' }}</span>
                                </div>
                                @if ($category->description)<div class="small text-muted mt-1">{{ $category->description }}</div>@endif
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary" {!! $editButton($category, $category->Children->isNotEmpty()) !!}><i class="ri-edit-line"></i></button>
                                <form method="post" action="{{ route('quality.categories.toggle', $category) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-{{ $category->active ? 'danger' : 'success' }}" title="{{ $category->active ? 'Desativar' : 'Ativar' }}"><i class="ri-{{ $category->active ? 'eye-off-line' : 'eye-line' }}"></i></button></form>
                            </div>
                        </div>
                        <div class="border-top p-3 pt-2">
                            @forelse ($category->Children as $child)
                                <div class="usr-act {{ $child->active ? '' : 'opacity-50' }}">
                                    <i class="ri-corner-down-right-line text-muted"></i>
                                    <span class="fw-bold">{{ $child->name }}</span>
                                    <span class="chip">{{ $appliesLabel($child) }}</span>
                                    <span class="ms-auto d-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0" {!! $editButton($child, false) !!}><i class="ri-edit-line"></i></button>
                                        <form method="post" action="{{ route('quality.categories.toggle', $child) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-{{ $child->active ? 'danger' : 'success' }} py-0"><i class="ri-{{ $child->active ? 'eye-off-line' : 'eye-line' }}"></i></button></form>
                                    </span>
                                </div>
                            @empty
                                <div class="small text-muted fst-italic">Sem subcategorias. Use “Novo motivo” e escolha esta categoria como principal.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="table-card"><div class="ob-empty"><i class="ri-price-tag-3-line"></i><div class="fw-bold">Nenhum motivo encontrado.</div><div class="small">Use “Novo motivo” para cadastrar.</div></div></div></div>
            @endforelse
        </div>
    </div>

    <div class="modal fade" id="quality-category-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="{{ route('quality.categories.store') }}" class="modal-content" id="quality-category-form">
                @csrf
                <input type="hidden" name="_method" value="POST" id="quality-category-method">
                <div class="modal-header"><h5 class="modal-title" id="quality-category-title">Novo motivo</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small mb-1">Nome</label><input name="name" class="form-control" required maxlength="120"></div>
                    <div class="row g-2 mb-2">
                        <div class="col-8">
                            <label class="form-label small mb-1">Motivo principal (para criar um detalhe)</label>
                            <select name="parent_id" class="form-select"><option value="">— É um motivo principal —</option>@foreach ($parents as $parent)<option value="{{ $parent->id }}">{{ $parent->name }}</option>@endforeach</select>
                        </div>
                        <div class="col-4"><label class="form-label small mb-1">Ordem</label><input type="number" min="0" name="sort_order" value="0" class="form-control"></div>
                    </div>
                    <div class="mb-2"><label class="form-label small mb-1">Código (opcional)</label><input name="code" class="form-control" maxlength="60"></div>
                    <div class="mb-2"><label class="form-label small mb-1">Descrição (opcional)</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                    <div class="d-flex gap-4">
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="applies_project" value="1" id="qc-project" checked><label class="form-check-label" for="qc-project">1º ciclo</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="applies_budget" value="1" id="qc-budget" checked><label class="form-check-label" for="qc-budget">2º ciclo</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="active" value="1" id="qc-active" checked><label class="form-check-label" for="qc-active">Ativo</label></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Salvar</button></div>
            </form>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const modal = document.getElementById('quality-category-modal');
            const form = document.getElementById('quality-category-form');
            const store = @json(route('quality.categories.store'));

            modal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                const edit = button.dataset.mode === 'edit';
                form.action = edit ? button.dataset.action : store;
                document.getElementById('quality-category-method').value = edit ? 'PUT' : 'POST';
                document.getElementById('quality-category-title').textContent = edit ? 'Editar motivo' : 'Novo motivo';
                form.name.value = edit ? button.dataset.name : '';
                form.code.value = edit ? (button.dataset.code || '') : '';
                form.description.value = edit ? (button.dataset.description || '') : '';
                form.sort_order.value = edit ? button.dataset.sort : 0;
                form.parent_id.value = edit ? (button.dataset.parent || '') : '';
                form.parent_id.disabled = edit && button.dataset.hasChildren === '1';
                form.applies_project.checked = edit ? button.dataset.project === '1' : true;
                form.applies_budget.checked = edit ? button.dataset.budget === '1' : true;
                form.active.checked = edit ? button.dataset.active === '1' : true;
            });
        })();
    </script>
@endpush
