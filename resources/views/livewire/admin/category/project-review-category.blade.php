<div class="card mt-4">
    <h4 class="card-header mb-3 edp-bg-sprucegreen-70 text-edp-verde">Categorias - Análise de Projetos</h4>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Nova categoria</label>
                <input type="text" class="form-control" wire:model.defer="category_name" placeholder="Ex: POSTE">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ordem</label>
                <input type="number" min="0" class="form-control" wire:model.defer="category_sort">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="d-flex w-100 gap-2">
                    <button class="btn btn-primary w-100" wire:click="saveCategory">Salvar categoria</button>
                    <button class="btn btn-outline-secondary w-100" wire:click="openBulkModal('category')">Massa</button>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <h6 class="mb-2">Categorias</h6>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <tbody>
                            @forelse ($categories as $cat)
                                <tr class="{{ (int) $category_id === (int) $cat->id ? 'table-primary' : '' }}" style="cursor:pointer;"
                                    wire:click="$set('category_id', {{ $cat->id }})">
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $cat->sort_order }} - {{ $cat->name }}</span>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-secondary" wire:click.stop="toggleCategory({{ $cat->id }})">
                                                    {{ $cat->active ? 'Ativa' : 'Inativa' }}
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" wire:click.stop="removeCategory({{ $cat->id }})">X</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-muted">Sem categorias.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <h6 class="mb-2">Subcategorias</h6>
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <input type="text" class="form-control" wire:model.defer="subcategory_name" @disabled(!$category_id)
                            placeholder="Ex: ESTRUTURA PRIMÁRIA">
                    </div>
                    <div class="col-8">
                        <input type="number" min="0" class="form-control" wire:model.defer="subcategory_sort" @disabled(!$category_id)>
                    </div>
                    <div class="col-4">
                        <div class="d-flex w-100 gap-2">
                            <button class="btn btn-primary w-100" wire:click="saveSubcategory" @disabled(!$category_id)>Salvar</button>
                            <button class="btn btn-outline-secondary w-100" wire:click="openBulkModal('subcategory')" @disabled(!$category_id)>Massa</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <tbody>
                            @forelse ($subcategories as $sub)
                                <tr class="{{ (int) $subcategory_id === (int) $sub->id ? 'table-primary' : '' }}" style="cursor:pointer;"
                                    wire:click="$set('subcategory_id', {{ $sub->id }})">
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $sub->sort_order }} - {{ $sub->name }}</span>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-secondary" wire:click.stop="toggleSubcategory({{ $sub->id }})">
                                                    {{ $sub->active ? 'Ativa' : 'Inativa' }}
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" wire:click.stop="removeSubcategory({{ $sub->id }})">X</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-muted">Sem subcategorias.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-4">
                <h6 class="mb-2">Itens</h6>
                <div class="row g-2 mb-2">
                    <div class="col-12">
                        <input type="text" class="form-control" wire:model.defer="item_name" @disabled(!$subcategory_id)
                            placeholder="Ex: ISOLADOR">
                    </div>
                    <div class="col-4">
                        <input type="number" min="0" class="form-control" wire:model.defer="item_sort" @disabled(!$subcategory_id)>
                    </div>
                    <div class="col-8">
                        <div class="d-flex w-100 gap-2">
                            <button class="btn btn-primary w-100" wire:click="saveItem" @disabled(!$subcategory_id)>Salvar item</button>
                            <button class="btn btn-outline-secondary w-100" wire:click="openBulkModal('item')" @disabled(!$subcategory_id)>Massa</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $item->sort_order }} - {{ $item->name }}</span>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-secondary" wire:click="toggleItem({{ $item->id }})">
                                                    {{ $item->active ? 'Ativo' : 'Inativo' }}
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" wire:click="removeItem({{ $item->id }})">X</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="text-muted">Sem itens.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="projectReviewBulkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-bg-dark">
                    <h5 class="modal-title">Inserção em Massa -
                        @if ($bulk_target === 'category')
                            Categorias
                        @elseif($bulk_target === 'subcategory')
                            Subcategorias
                        @else
                            Itens
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">
                        Cole os nomes separados por <strong>linha</strong>, <strong>vírgula</strong> ou <strong>ponto e vírgula</strong>.
                    </p>
                    <p class="small text-muted mb-3">
                        Duplicidade é ignorada sem acentuação: <code>ESTRUTURA PRIMÁRIA</code> e <code>ESTRUTURA PRIMARIA</code> são consideradas iguais.
                    </p>
                    <textarea class="form-control" rows="10" wire:model.defer="bulk_payload"
                        placeholder="Exemplo:&#10;POSTE&#10;REDE PRIMÁRIA&#10;REDE SECUNDÁRIA"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" wire:click="saveBulk">Inserir</button>
                </div>
            </div>
        </div>
    </div>
</div>
