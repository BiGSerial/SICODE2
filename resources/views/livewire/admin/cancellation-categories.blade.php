<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page {
                --oe-bg: #f6f7fb;
                --oe-surface: #ffffff;
                --oe-ink: #1f2933;
                --oe-muted: #6b7280;
                --oe-accent: #0f766e;
                --oe-border: #e5e7eb;
                background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                    var(--oe-bg);
                padding: 1.5rem 0;
            }

            .oexterno-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.5rem 2rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1.5rem;
            }

            .oexterno-card {
                background: var(--oe-surface);
                border: 1px solid var(--oe-border);
                border-radius: 0.9rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .order-actions {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                white-space: nowrap;
            }

            .order-btn {
                line-height: 1;
            }
        </style>

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Categorias de Cancelamento</h2>
                <span class="meta">Gerencie motivos e regras de evidência.</span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="oexterno-card p-3">
                    <div class="fw-semibold mb-2">{{ $editingId ? 'Editar Categoria' : 'Nova Categoria' }}</div>
                    <div class="mb-2">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" wire:model.defer="name" />
                        @error('name')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Slug/Código</label>
                        <input type="text" class="form-control" value="{{ $editingId ? $slug : ($slugPreview ?: '-') }}" readonly />
                        <small class="text-muted">
                            @if($editingId)
                                O slug é imutável após criação.
                            @else
                                Gerado automaticamente a partir do nome.
                            @endif
                        </small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" rows="3" wire:model.defer="description"></textarea>
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
            <div class="col-lg-7">
                <div class="oexterno-card p-3">
                    <div class="fw-semibold mb-2">Categorias cadastradas</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 120px;">Ordem</th>
                                    <th>Nome</th>
                                    <th>Slug</th>
                                    <th>Ativa</th>
                                    <th>Evidência</th>
                                    <th>Min</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cancellation-categories-sortable-{{ $this->id }}">
                                @forelse($categories as $cat)
                                    <tr data-id="{{ $cat->id }}" wire:key="cat-row-{{ $cat->id }}">
                                        <td class="text-center align-middle">
                                            <div class="order-actions">
                                                <span class="text-muted drag-handle" style="cursor: grab;" title="Arraste para reordenar">
                                                    <i class="ri-drag-move-2-line"></i>
                                                </span>
                                                @if(!$loop->first)
                                                    <button class="btn btn-sm btn-light px-2 py-1 order-btn" wire:click="moveUp({{ $cat->id }})" title="Subir">
                                                        <i class="ri-arrow-up-s-line"></i>
                                                    </button>
                                                @endif
                                                @if(!$loop->last)
                                                    <button class="btn btn-sm btn-light px-2 py-1 order-btn"
                                                        wire:click="moveDown({{ $cat->id }})" title="Descer">
                                                        <i class="ri-arrow-down-s-line"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
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
                                        <td colspan="7" class="text-center">Nenhuma categoria cadastrada.</td>
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

<script>
    (function () {
        function ensureSortableLoaded(callback) {
            if (window.Sortable) {
                callback();
                return;
            }

            const existing = document.getElementById('sortablejs-cdn');
            if (existing) {
                existing.addEventListener('load', callback, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.id = 'sortablejs-cdn';
            script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js';
            script.onload = callback;
            document.head.appendChild(script);
        }

        function initCancellationCategoriesSortable() {
            const tbody = document.getElementById('cancellation-categories-sortable-{{ $this->id }}');
            if (!tbody || tbody.dataset.sortableInit === '1') {
                return;
            }

            tbody.dataset.sortableInit = '1';
            ensureSortableLoaded(() => {
                if (!window.Sortable) return;
                new Sortable(tbody, {
                    animation: 140,
                    ghostClass: 'table-warning',
                    handle: '.drag-handle',
                    onEnd: () => {
                        const orderedIds = Array.from(tbody.querySelectorAll('tr[data-id]'))
                            .map(row => Number(row.dataset.id))
                            .filter(Boolean);
                        const componentEl = tbody.closest('[wire\\:id]');
                        if (!componentEl) return;
                        const component = window.Livewire.find(componentEl.getAttribute('wire:id'));
                        if (!component) return;
                        component.call('reorder', orderedIds);
                    }
                });
            });
        }

        document.addEventListener('livewire:load', initCancellationCategoriesSortable);
        document.addEventListener('livewire:update', initCancellationCategoriesSortable);
        document.addEventListener('livewire:load', () => {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('message.processed', () => initCancellationCategoriesSortable());
            }
        });
    })();
</script>
