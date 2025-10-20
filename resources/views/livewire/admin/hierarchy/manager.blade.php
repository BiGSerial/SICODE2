<div id="hierarchy-manager-{{ $this->id }}" class="container-fluid" wire:ignore.self>
    <x-show-loading />

    <div class="row g-3">
        {{-- Coluna 1: Usuários (drag source) --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Usuários</h6>
                    <input type="text" class="form-control form-control-sm w-auto" placeholder="Buscar..."
                        wire:model.debounce.400ms="search">
                </div>
                <div class="card-body" style="max-height:70vh; overflow:auto;">
                    @foreach ($users as $u)
                        <div class="user-item border rounded p-2 mb-2 d-flex justify-content-between align-items-center"
                            data-user-id="{{ $u->id }}" draggable="true" wire:key="user-{{ $u->id }}">
                            <div>
                                <strong>{{ $u->name }}</strong>
                                <div class="small text-muted">{{ $u->email }}</div>
                            </div>
                            <span class="badge bg-secondary">{{ $u->manager_id ? 'Subordinado' : 'Raiz' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Coluna 2: Árvore (drop target) --}}
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Hierarquia (arraste um usuário sobre outro para torná-lo chefe)</h6>
                    <button class="btn btn-sm btn-outline-primary" onclick="window.location.reload()">
                        Recarregar
                    </button>
                </div>
                <div class="card-body" style="max-height:70vh; overflow:auto;">
                    @if (empty($tree))
                        <div class="alert alert-warning">Nenhum usuário encontrado.</div>
                    @else
                        <ul class="tree list-unstyled">
                            @foreach ($tree as $node)
                                @include('livewire.admin.hierarchy.partials.node', ['node' => $node])
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Coluna 3: Detalhes + Delegações --}}
        <div class="col-12 col-lg-3">
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Detalhes</h6>
                    @if ($selected)
                        <button class="btn btn-sm btn-outline-secondary"
                            wire:click="setAsRoot('{{ $selected->id }}')">Tornar raiz</button>
                    @endif
                </div>
                <div class="card-body" style="min-height: 140px;">
                    @if ($selected)
                        <div class="mb-2"><strong>{{ $selected->name }}</strong></div>
                        <div class="small text-muted">{{ $selected->email }}</div>
                        <div class="mt-2">
                            <span class="badge {{ $selected->manager_id ? 'bg-secondary' : 'bg-success' }}">
                                {{ $selected->manager_id ? 'Subordinado' : 'Raiz' }}
                            </span>
                        </div>
                    @else
                        <div class="text-muted">Selecione um nó na árvore.</div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Delegações ativas</h6>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                        data-bs-target="#dlgDelegation">
                        Nova
                    </button>
                </div>
                <div class="card-body" style="max-height: 40vh; overflow:auto;">
                    @forelse ($delegations as $d)
                        <div class="border rounded p-2 mb-2">
                            <div class="small">Titular: <strong>{{ $d->principal->name }}</strong></div>
                            <div class="small">Delegado: <strong>{{ $d->delegate->name }}</strong></div>
                            <div class="small text-muted">
                                {{ $d->valid_from->format('d/m/Y') }} —
                                {{ $d->valid_to ? $d->valid_to->format('d/m/Y') : 'sem fim' }}
                            </div>
                            <div class="d-flex justify-content-end">
                                @if (!$d->valid_to)
                                    <button class="btn btn-sm btn-outline-danger"
                                        wire:click="endDelegation('{{ $d->id }}')">
                                        Encerrar
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">Nenhuma delegação.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Delegação --}}
    <div wire:ignore.self class="modal fade" id="dlgDelegation" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Nova delegação</h6>
                    <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small mb-1">Titular (principal)</label>
                    <select class="form-select form-select-sm mb-2" wire:model="dlg_principal_id">
                        <option value="">— selecione —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                        @endforeach
                    </select>

                    <label class="form-label small mb-1">Delegado (cobertura)</label>
                    <select class="form-select form-select-sm mb-2" wire:model="dlg_delegate_id">
                        <option value="">— selecione —</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                        @endforeach
                    </select>

                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label small mb-1">Início</label>
                            <input type="date" class="form-control form-control-sm" wire:model="dlg_from">
                        </div>
                        <div class="col">
                            <label class="form-label small mb-1">Fim (opcional)</label>
                            <input type="date" class="form-control form-control-sm" wire:model="dlg_to">
                        </div>
                    </div>

                    <label class="form-label small mt-2 mb-1">Motivo</label>
                    <input type="text" class="form-control form-control-sm" wire:model="dlg_reason"
                        placeholder="Férias, licença...">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button class="btn btn-sm btn-primary" wire:click="openDelegation">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .drop-target {
            border: 1px dashed transparent;
        }

        .drop-target.over {
            border-color: #0d6efd;
            background: rgba(13, 110, 253, 0.06);
        }

        .tree .node {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            border-radius: .5rem;
            margin-bottom: .4rem;
        }

        .tree .node .actions .btn {
            --bs-btn-padding-y: .15rem;
            --bs-btn-padding-x: .35rem;
            --bs-btn-font-size: .75rem;
        }
    </style>

    {{-- Partial de nó (recursivo) --}}
    <script>
        document.addEventListener('livewire:load', function() {
            const root = document.querySelector('#hierarchy-manager-{{ $this->id }}');
            if (!root) return;
            const lw = Livewire.find('{{ $this->id }}');

            // fonte: cartões da coluna de usuários
            root.querySelectorAll('.user-item[draggable="true"]').forEach(el => {
                el.addEventListener('dragstart', e => {
                    e.dataTransfer.setData('text/plain', el.dataset.userId);
                    e.dataTransfer.effectAllowed = 'move';
                });
            });

            // targets: nós da árvore
            const bindDropTargets = () => {
                root.querySelectorAll('.drop-target').forEach(dt => {
                    dt.addEventListener('dragover', e => {
                        e.preventDefault();
                        dt.classList.add('over');
                    });
                    dt.addEventListener('dragleave', () => dt.classList.remove('over'));
                    dt.addEventListener('drop', e => {
                        e.preventDefault();
                        dt.classList.remove('over');
                        const draggedUserId = e.dataTransfer.getData('text/plain');
                        const targetManagerId = dt.dataset.nodeId;
                        if (draggedUserId && targetManagerId) {
                            lw.emit('lwMoveUserUnder', draggedUserId, targetManagerId);
                        }
                    });
                });
            };

            // ação “Raiz”
            window.__hier = {
                onSetRoot: function(userId) {
                    lw.emit('lwSetAsRoot', userId);
                }
            };

            bindDropTargets();

            // rebinda após updates
            Livewire.hook('message.processed', (m, c) => {
                if (c.id === '{{ $this->id }}') {
                    bindDropTargets();
                }
            });

            // toasts básicos (use seu componente preferido)
            window.addEventListener('toast', e => {
                const {
                    type,
                    msg
                } = e.detail || {};
                console.log(`[${type}] ${msg}`);
            });
            window.addEventListener('hide-delegation-modal', () => {
                const m = document.getElementById('dlgDelegation');
                if (m) bootstrap.Modal.getOrCreateInstance(m).hide();
            });
        });
    </script>

</div>
