{{-- resources/views/livewire/protests/dispatch/config-user.blade.php --}}
<div id="protest-config-{{ $this->id }}" wire:ignore.self class="container-fluid">

    <x-show-loading />

    <div class="row g-3">

        {{-- Coluna 1: Usuários disponíveis --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Usuários disponíveis</h6>
                    <input type="text" class="form-control form-control-sm w-auto"
                        placeholder="Buscar (vários termos ok)" wire:model.debounce.500ms="search">
                </div>

                <div class="card-body" style="max-height: 70vh; overflow: auto;">
                    {{-- Toolbar de seleção em massa --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="small text-muted">
                            Total: {{ $this->users->count() }} — Selecionados:
                            <strong id="selection-count">0</strong>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button id="select-all-btn" class="btn btn-outline-secondary" type="button">Selecionar
                                todos</button>
                            <button id="clear-selection-btn" class="btn btn-outline-secondary"
                                type="button">Limpar</button>
                        </div>
                    </div>

                    {{-- “Arraste N selecionados” (quando N>1) --}}
                    <div id="group-drag-bar" class="alert alert-secondary py-2 mb-2" draggable="true"
                        style="display:none;">
                        Arraste <strong id="group-drag-count">0</strong> selecionados
                    </div>

                    @foreach ($this->users as $u)
                        <div class="user-card border rounded p-2 mb-2 d-flex justify-content-between align-items-center"
                            data-user-id="{{ $u->id }}" draggable="true" wire:key="user-{{ $u->id }}">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="user-checkbox form-check-input">
                                <div>
                                    <strong>{{ $u->name }}</strong>
                                    <div class="small text-muted">{{ $u->email }}</div>
                                </div>
                            </div>
                            <i class="bi bi-grip-vertical text-muted"></i>
                        </div>
                    @endforeach

                    <hr>

                    {{-- Busca / inclusão em massa (cola emails/UUIDs) --}}
                    <details>
                        <summary class="small text-muted">Busca/Incluir em massa</summary>
                        <div class="mt-2">
                            <textarea class="form-control form-control-sm" rows="4"
                                placeholder="Cole emails e/ou UUIDs (um por linha, vírgula ou ponto e vírgula)" wire:model.defer="bulkInput"></textarea>
                            <div class="d-flex gap-2 mt-2">
                                <button class="btn btn-sm btn-outline-primary" wire:click="bulkAddToTrigger">
                                    Adicionar ao Trigger (coluna do meio)
                                </button>
                                <button class="btn btn-sm btn-outline-primary" wire:click="bulkAddToChain"
                                    @disabled(!$selectedProtestUserId)>
                                    Adicionar à Cadeia (precisa selecionar um Trigger)
                                </button>
                            </div>
                            <div class="form-text">Suporta emails e UUIDs. Duplicados são ignorados.</div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        {{-- Coluna 2: Usuários Trigger (drop aqui para criar ProtestUser) --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">Usuários Trigger (globais)</h6>
                </div>

                <div id="trigger-drop-zone" class="card-body" style="max-height: 70vh; overflow: auto;">
                    <div class="alert alert-info py-2">
                        Arraste da coluna da esquerda para adicionar (suporta múltiplos selecionados).
                    </div>

                    @forelse($this->protestUsers as $pu)
                        <div class="base-card border rounded p-2 mb-2" data-base-id="{{ $pu->id }}"
                            wire:key="pu-{{ $pu->id }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="radio" name="selected_pu" class="form-check-input"
                                        wire:click="selectProtestUser({{ $pu->id }})"
                                        @checked($selectedProtestUserId === $pu->id)>
                                    <strong>{{ $pu->user->name }}</strong>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check m-0">
                                        <input type="checkbox" class="form-check-input"
                                            id="default-{{ $pu->id }}"
                                            wire:click="setDefault({{ $pu->id }})" @checked($pu->default)>
                                        <label class="form-check-label small"
                                            for="default-{{ $pu->id }}">Default</label>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger"
                                        wire:click="removeTriggerUser({{ $pu->id }})" title="Remover">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="small text-muted mt-1">
                                Dica: solte um usuário <u>em cima deste card</u> para encadear direto.
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">Nenhum trigger configurado.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Coluna 3: Cadeia do Trigger selecionado (drop aqui para criar ProtestUserTrigger) --}}
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Cadeia do Trigger selecionado</h6>
                    @if ($selectedProtestUserId)
                        <span class="badge bg-primary">PU #{{ $selectedProtestUserId }}</span>
                    @endif
                </div>

                <div id="chain-drop-zone" class="card-body" style="max-height: 70vh; overflow: auto;">
                    @if (!$selectedProtestUserId)
                        <div class="alert alert-warning py-2">
                            Selecione um Trigger na coluna do meio para habilitar a cadeia.
                        </div>
                    @else
                        <div class="alert alert-info py-2">
                            Arraste da coluna da esquerda para adicionar à cadeia (suporta múltiplos selecionados).
                        </div>

                        @forelse($this->selectedChain as $ct)
                            <div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center"
                                wire:key="chain-{{ $ct->id }}">
                                <div>
                                    <strong>{{ $ct->user->name }}</strong>
                                    <div class="small text-muted">{{ $ct->user->email }}</div>
                                </div>
                                <button class="btn btn-sm btn-outline-danger"
                                    wire:click="removeChainUser({{ $ct->id }})" title="Remover">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        @empty
                            <div class="text-muted">Sem usuários na cadeia.</div>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>

    </div> {{-- row --}}

    <style>
        .user-card.selected {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1);
        }

        .base-card.drop-highlight {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.08);
        }
    </style>

    <script>
        // Controller “sem Alpine” com prevenção de bubbling/duplicidade de listeners
        function protestConfigController(componentId) {
            const state = {
                selected: new Set(),
                dragged: null
            };

            const root = document.getElementById(`protest-config-${componentId}`);
            if (!root) return;
            const lw = Livewire.find(componentId);

            // helpers
            const $ = (sel, ctx = root) => ctx.querySelector(sel);
            const $$ = (sel, ctx = root) => Array.from(ctx.querySelectorAll(sel));
            const bind = (el, evt, fn, opts) => {
                if (!el) return;
                el.__l = el.__l || {};
                if (el.__l[evt]) el.removeEventListener(evt, el.__l[evt]);
                el.__l[evt] = fn;
                el.addEventListener(evt, fn, opts || false);
            };

            const $selectionCount = $('#selection-count');
            const $groupBar = $('#group-drag-bar');
            const $groupCount = $('#group-drag-count');
            const $selectAllBtn = $('#select-all-btn');
            const $clearSelBtn = $('#clear-selection-btn');
            const $triggerZone = $('#trigger-drop-zone');
            const $chainZone = $('#chain-drop-zone');

            const updateUI = () => {
                $$('.user-card').forEach(card => {
                    const id = card.dataset.userId;
                    const cb = card.querySelector('.user-checkbox');
                    const sel = state.selected.has(id);
                    card.classList.toggle('selected', sel);
                    if (cb) cb.checked = sel;
                });
                if ($selectionCount) $selectionCount.textContent = state.selected.size;

                if (state.selected.size > 1) {
                    if ($groupCount) $groupCount.textContent = state.selected.size;
                    if ($groupBar) $groupBar.style.display = 'block';
                } else {
                    if ($groupBar) $groupBar.style.display = 'none';
                }
            };

            // Cards de usuário (coluna 1)
            $$('.user-card').forEach(card => {
                const id = card.dataset.userId;
                const cb = card.querySelector('.user-checkbox');

                bind(card, 'click', (e) => {
                    if (e.target === cb) return;
                    if (state.selected.has(id)) state.selected.delete(id);
                    else state.selected.add(id);
                    updateUI();
                });

                if (cb) {
                    bind(cb, 'change', () => {
                        if (cb.checked) state.selected.add(id);
                        else state.selected.delete(id);
                        updateUI();
                    });
                }

                bind(card, 'dragstart', (e) => {
                    if (!state.selected.has(id)) {
                        state.selected.clear();
                        state.selected.add(id);
                        updateUI();
                    }
                    state.dragged = {
                        ids: Array.from(state.selected)
                    };
                    try {
                        e.dataTransfer.setData('text/plain', 'user-drag');
                    } catch (_) {}
                    e.dataTransfer.effectAllowed = 'move';
                });
            });

            // Barra de arraste em grupo
            if ($groupBar) {
                bind($groupBar, 'dragstart', (e) => {
                    if (state.selected.size > 1) {
                        state.dragged = {
                            ids: Array.from(state.selected)
                        };
                        try {
                            e.dataTransfer.setData('text/plain', 'group-drag');
                        } catch (_) {}
                        e.dataTransfer.effectAllowed = 'move';
                    }
                });
            }

            // Botões seleção
            if ($selectAllBtn) bind($selectAllBtn, 'click', () => {
                $$('.user-card').forEach(c => state.selected.add(c.dataset.userId));
                updateUI();
            });
            if ($clearSelBtn) bind($clearSelBtn, 'click', () => {
                state.selected.clear();
                updateUI();
            });

            // ZONA PAI (coluna 2): só aceita drop quando NÃO é sobre um .base-card
            if ($triggerZone) {
                bind($triggerZone, 'dragover', (e) => {
                    if (e.target && e.target.closest('.base-card')) return; // deixa o card tratar
                    e.preventDefault();
                });
                bind($triggerZone, 'drop', (e) => {
                    if (e.target && e.target.closest('.base-card')) return; // ignore: card cuidou
                    e.preventDefault();
                    if (!state.dragged?.ids?.length) return;
                    lw.call('addTriggerUsers', state.dragged.ids);
                    state.dragged = null;
                    state.selected.clear();
                    updateUI();
                });
            }

            // CARD (coluna 2): encadear direto NO card (para baseId específico)
            $$('.base-card').forEach(card => {
                const baseId = card.dataset.baseId;

                bind(card, 'dragover', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    card.classList.add('drop-highlight');
                });
                bind(card, 'dragleave', (e) => {
                    e.stopPropagation();
                    card.classList.remove('drop-highlight');
                });
                bind(card, 'drop', (e) => {
                    e.preventDefault();
                    e.stopPropagation(); // evita o pai
                    card.classList.remove('drop-highlight');
                    if (!state.dragged?.ids?.length) return;
                    lw.call('addChainUsers', state.dragged.ids, baseId);
                    state.dragged = null;
                    state.selected.clear();
                    updateUI();
                });
            });

            // ZONA (coluna 3): encadear no selecionado
            if ($chainZone) {
                bind($chainZone, 'dragover', (e) => e.preventDefault());
                bind($chainZone, 'drop', (e) => {
                    e.preventDefault();
                    if (!state.dragged?.ids?.length) return;
                    lw.call('addChainUsers', state.dragged.ids);
                    state.dragged = null;
                    state.selected.clear();
                    updateUI();
                });
            }

            // Limpa arraste “órfão” se soltar fora
            bind(document, 'dragend', () => {
                state.dragged = null;
            });
        }

        // Boot + rebind após cada atualização do Livewire
        document.addEventListener('livewire:load', function() {
            const root = document.querySelector('[id^="protest-config-"]');
            if (!root) return;
            const componentId = root.id.replace('protest-config-', '');

            protestConfigController(componentId);

            Livewire.hook('message.processed', (message, component) => {
                if (component.id === componentId) {
                    protestConfigController(componentId);
                }
            });
        });
    </script>
</div>
