<div class="container-fluid">
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <strong>{{ $editingId ? 'Editar Categoria' : 'Nova Categoria' }}</strong>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" wire:model.defer="name" />
                        @error('name')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Slug/Código</label>
                        <input type="text" class="form-control" wire:model.defer="slug" />
                        @error('slug')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" rows="3" wire:model.defer="description"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Ordem de exibição</label>
                        <input type="number" class="form-control" wire:model.defer="display_order" />
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" wire:model.defer="active" id="catActive">
                        <label class="form-check-label" for="catActive">Ativa</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" wire:model.defer="require_evidence" id="catEvidence">
                        <label class="form-check-label" for="catEvidence">Exige evidência</label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Qtd. mínima de evidências</label>
                        <input type="number" class="form-control" wire:model.defer="min_evidence_files" />
                        @error('min_evidence_files')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" wire:click="save">Salvar</button>
                        <button class="btn btn-outline-secondary" wire:click="resetForm">Limpar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <strong>Categorias cadastradas</strong>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Slug</th>
                                    <th>Ativa</th>
                                    <th>Evidência</th>
                                    <th>Min</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $cat)
                                    <tr>
                                        <td>{{ $cat->name }}</td>
                                        <td>{{ $cat->slug }}</td>
                                        <td>{{ $cat->active ? 'Sim' : 'Não' }}</td>
                                        <td>{{ $cat->require_evidence ? 'Sim' : 'Não' }}</td>
                                        <td>{{ $cat->min_evidence_files }}</td>
                                        <td class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" wire:click="edit({{ $cat->id }})">Editar</button>
                                            <button class="btn btn-sm btn-outline-warning" wire:click="toggleActive({{ $cat->id }})">
                                                {{ $cat->active ? 'Desativar' : 'Ativar' }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhuma categoria cadastrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
