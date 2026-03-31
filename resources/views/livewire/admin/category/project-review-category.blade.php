<div class="prc-page mt-4">
    <style>
        .prc-page {
            --prc-bg: #f6f7fb;
            --prc-surface: #ffffff;
            --prc-ink: #1f2933;
            --prc-muted: #6b7280;
            --prc-accent: #0f766e;
            --prc-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--prc-bg);
            padding: 1rem;
        }

        .prc-main-card {
            background: var(--prc-surface);
            border: 1px solid var(--prc-border);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        }

        .prc-config-card {
            background: var(--prc-surface);
            border: 1px solid var(--prc-border);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }

        .prc-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            padding: 1rem 1.25rem;
        }

        .prc-header h4 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .prc-section-title {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: var(--prc-muted);
        }

        .prc-table-wrap {
            background: var(--prc-surface);
            border: 1px solid var(--prc-border);
            border-radius: 0;
            overflow: hidden;
        }

        .prc-table-wrap .table {
            margin-bottom: 0;
        }

        .prc-table-wrap .table td {
            padding: 0.6rem 0.75rem;
            color: var(--prc-ink);
        }
    </style>
    <div class="card prc-config-card mb-3">
        <div class="card-body">
            <h6 class="prc-section-title mb-2">Configuração de Levantamento</h6>
            <div class="form-floating mb-2">
                <select class="form-select border border-secondary" wire:model="selectedProjectReviewSurveyServiceId"
                    id="projectReviewSurveyService">
                    <option value="">Detectar automaticamente por nome (Levantamento)</option>
                    @foreach ($serviceOptions as $service)
                        <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                    @endforeach
                </select>
                <label for="projectReviewSurveyService">Serviço de levantamento (Análise de Projetos)</label>
            </div>
            <div class="small text-muted">
                Define qual serviço será usado para localizar o último levantador nos relatórios da análise.
            </div>
        </div>
    </div>
    <div class="card prc-main-card">
        <h4 class="prc-header">Categorias - Análise de Projetos</h4>
        <div class="card-body p-4">
        <div class="row g-3 mb-3">
            <div class="col-md-9">
                <label class="form-label">Nova categoria</label>
                <input type="text" class="form-control border border-secondary" wire:model.defer="category_name"
                    placeholder="Ex: POSTE">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="d-flex w-100 gap-2">
                    <button class="btn btn-primary w-100" wire:click="saveCategory" title="Salvar categoria" aria-label="Salvar categoria">
                        <i class="ri-save-3-line"></i>
                    </button>
                    <button class="btn btn-outline-secondary w-100" wire:click="openBulkModal('category')" title="Inserção em massa" aria-label="Inserção em massa de categorias">
                        <i class="ri-file-copy-2-line"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <h6 class="mb-2 prc-section-title">Categorias</h6>
                <div class="table-responsive prc-table-wrap" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <tbody>
                            @forelse ($categories as $cat)
                                <tr wire:key="pr-cat-{{ $cat->id }}"
                                    class="{{ (int) $category_id === (int) $cat->id ? 'table-primary' : '' }}" style="cursor:pointer;"
                                    wire:click="selectCategory({{ $cat->id }})">
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $cat->name }}</span>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm {{ $cat->active ? 'btn-outline-success' : 'btn-outline-secondary' }}"
                                                    wire:click.stop="toggleCategory({{ $cat->id }})"
                                                    title="{{ $cat->active ? 'Desativar categoria' : 'Ativar categoria' }}"
                                                    aria-label="{{ $cat->active ? 'Desativar categoria' : 'Ativar categoria' }}">
                                                    <i class="{{ $cat->active ? 'ri-toggle-fill' : 'ri-toggle-line' }}"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" wire:click.stop="removeCategory({{ $cat->id }})" title="Remover categoria" aria-label="Remover categoria">
                                                    <i class="ri-delete-bin-6-line"></i>
                                                </button>
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
                <h6 class="mb-2 prc-section-title">Subcategorias</h6>
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-8">
                        <input type="text" class="form-control border border-secondary" wire:model.defer="subcategory_name"
                            @disabled(!$category_id)
                            placeholder="Ex: ESTRUTURA PRIMÁRIA">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <div class="d-flex w-100 gap-2">
                            <button class="btn btn-primary w-100" wire:click="saveSubcategory" @disabled(!$category_id) title="Salvar subcategoria" aria-label="Salvar subcategoria">
                                <i class="ri-save-3-line"></i>
                            </button>
                            <button class="btn btn-outline-secondary w-100" wire:click="openBulkModal('subcategory')" @disabled(!$category_id) title="Inserção em massa" aria-label="Inserção em massa de subcategorias">
                                <i class="ri-file-copy-2-line"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive prc-table-wrap" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <tbody>
                            @forelse ($subcategories as $sub)
                                <tr wire:key="pr-sub-{{ $sub->id }}"
                                    class="{{ (int) $subcategory_id === (int) $sub->id ? 'table-primary' : '' }}" style="cursor:pointer;"
                                    wire:click="selectSubcategory({{ $sub->id }})">
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $sub->name }}</span>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm {{ $sub->active ? 'btn-outline-success' : 'btn-outline-secondary' }}"
                                                    wire:click.stop="toggleSubcategory({{ $sub->id }})"
                                                    title="{{ $sub->active ? 'Desativar subcategoria' : 'Ativar subcategoria' }}"
                                                    aria-label="{{ $sub->active ? 'Desativar subcategoria' : 'Ativar subcategoria' }}">
                                                    <i class="{{ $sub->active ? 'ri-toggle-fill' : 'ri-toggle-line' }}"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" wire:click.stop="removeSubcategory({{ $sub->id }})" title="Remover subcategoria" aria-label="Remover subcategoria">
                                                    <i class="ri-delete-bin-6-line"></i>
                                                </button>
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
                <h6 class="mb-2 prc-section-title">Itens</h6>
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-8">
                        <input type="text" class="form-control border border-secondary" wire:model.defer="item_name"
                            @disabled(!$subcategory_id)
                            placeholder="Ex: ISOLADOR">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <div class="d-flex w-100 gap-2">
                            <button class="btn btn-primary w-100" wire:click="saveItem" @disabled(!$subcategory_id) title="Salvar item" aria-label="Salvar item">
                                <i class="ri-save-3-line"></i>
                            </button>
                            <button class="btn btn-outline-secondary w-100" wire:click="openBulkModal('item')" @disabled(!$subcategory_id) title="Inserção em massa" aria-label="Inserção em massa de itens">
                                <i class="ri-file-copy-2-line"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive prc-table-wrap" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <tbody>
                            @forelse ($items as $item)
                                <tr wire:key="pr-item-{{ $item->id }}">
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>{{ $item->name }}</span>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm {{ $item->active ? 'btn-outline-success' : 'btn-outline-secondary' }}"
                                                    wire:click="toggleItem({{ $item->id }})"
                                                    title="{{ $item->active ? 'Desativar item' : 'Ativar item' }}"
                                                    aria-label="{{ $item->active ? 'Desativar item' : 'Ativar item' }}">
                                                    <i class="{{ $item->active ? 'ri-toggle-fill' : 'ri-toggle-line' }}"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" wire:click="removeItem({{ $item->id }})" title="Remover item" aria-label="Remover item">
                                                    <i class="ri-delete-bin-6-line"></i>
                                                </button>
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
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" title="Cancelar" aria-label="Cancelar">
                        <i class="ri-close-line"></i>
                    </button>
                    <button class="btn btn-primary" wire:click="saveBulk" title="Inserir" aria-label="Inserir">
                        <i class="ri-check-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>
