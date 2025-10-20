<div class="container-fluid py-3" wire:ignore.self>
    {{-- Top Bar com Título e Botões para Abrir/Fechar Offcanvas --}}
    <div class="d-flex align-items-center justify-content-between mb-3 px-2">
        {{-- Botão para Offcanvas Esquerda (agora sempre visível) --}}
        <button class="btn btn-dark d-flex align-items-center" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#leftOffcanvas" aria-controls="leftOffcanvas" title="Abrir Lista de Usuários">
            <i class="bi bi-person-lines-fill me-2"></i> <span class="d-none d-sm-inline">Usuários</span>
        </button>

        <h3 class="flex-grow-1 text-center text-primary mb-0">Gestão de Hierarquia de Usuários</h3>

        {{-- Botão para Offcanvas Direita (agora sempre visível) --}}
        <button class="btn btn-dark d-flex align-items-center" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#rightOffcanvas" aria-controls="rightOffcanvas" title="Abrir Detalhes e Ações">
            <span class="d-none d-sm-inline">Detalhes</span> <i class="bi bi-info-circle-fill ms-2"></i>
        </button>
    </div>

    {{-- CONTEÚDO PRINCIPAL: Organograma --}}
    <div class="row g-3">
        <div class="col-12"> {{-- Ocupa toda a largura disponível no layout --}}
            <div class="card h-100 shadow-sm border-0 bg-light">
                <div class="card-header bg-primary text-white d-flex flex-column gap-2">
                    <div class="d-flex align-items-center">
                        <h6 class="mb-0"><i class="bi bi-diagram-3-fill me-2"></i> Organograma Focado</h6>
                        <input class="form-control form-control-sm ms-auto bg-white text-dark border-0"
                            placeholder="Buscar na árvore..." wire:model.debounce.400ms="treeSearch">
                    </div>
                    <div class="small">
                        @if ($selectedManagerId && $breadcrumb)
                            <span class="text-white-50">Caminho: </span>
                            @foreach ($breadcrumb as $b)
                                <a href="#" wire:click.prevent="selectManager('{{ $b->id }}')"
                                    class="text-decoration-none {{ $b->id === $selectedManagerId ? 'fw-bold text-warning' : 'text-white' }}">
                                    {{ $b->name }}
                                </a>
                                @if (!$loop->last)
                                    <span class="mx-1 text-white-50">&rsaquo;</span>
                                @endif
                            @endforeach
                        @else
                            <span class="text-white-50">Selecione um usuário na "Lista de Usuários" para visualizar sua
                                hierarquia.</span>
                        @endif
                    </div>
                </div>

                <div class="card-body p-3"
                    style="min-height: calc(100vh - 200px); max-height: calc(100vh - 100px); overflow:auto;">
                    @if (!$selectedManagerId)
                        <div class="alert alert-info text-center mt-5" role="alert">
                            <i class="bi bi-info-circle me-2"></i> Por favor, selecione um usuário da lista (usando o
                            botão "Usuários" acima) para visualizar sua hierarquia.
                        </div>
                    @elseif (empty($focusedHierarchy['focusedUser']))
                        <div class="alert alert-warning text-center mt-5" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i> Não foi possível carregar a hierarquia para
                            o usuário selecionado (usuário não encontrado ou excluído).
                        </div>
                    @else
                        <ul class="list-unstyled hierarchy-main-container">

                            {{-- Gerente Imediato (acima do usuário focado) --}}
                            @if ($focusedHierarchy['manager'])
                                <li class="hierarchy-manager-above">
                                    <div class="node node-manager-above mx-auto"
                                        wire:click.prevent="selectManager('{{ $focusedHierarchy['manager']['id'] }}')"
                                        title="Clique para focar neste gerente">
                                        <div class="node-title">{{ $focusedHierarchy['manager']['name'] }}</div>
                                        <div class="node-subtitle">{{ $focusedHierarchy['manager']['email'] }}</div>
                                    </div>
                                    <div class="connection-line-vertical"></div>
                                </li>
                            @endif

                            {{-- Usuário Focado (o centro da visualização) --}}
                            <li class="hierarchy-focused-user">
                                <div class="node node-primary-focus mx-auto"
                                    data-match="{{ $treeSearch ? (stripos($focusedHierarchy['focusedUser']['name'], $treeSearch) !== false || stripos($focusedHierarchy['focusedUser']['email'], $treeSearch) !== false ? '1' : '0') : '0' }}"
                                    wire:click.prevent="selectManager('{{ $focusedHierarchy['focusedUser']['id'] }}')"
                                    title="Você está focado neste usuário">
                                    <div class="node-title">{{ $focusedHierarchy['focusedUser']['name'] }}</div>
                                    <div class="node-subtitle">{{ $focusedHierarchy['focusedUser']['email'] }}</div>
                                    <span
                                        class="badge bg-primary position-absolute top-0 end-0 mt-1 me-1 px-2 py-1 shadow-sm">FOCO</span>
                                </div>
                                @if (!empty($focusedHierarchy['reportsTree']))
                                    <div class="connection-line-vertical"></div>
                                @endif
                            </li>

                            {{-- Relatórios e Sub-árvore (abaixo do usuário focado) --}}
                            @if (!empty($focusedHierarchy['reportsTree']))
                                <ul class="list-unstyled hierarchy-reports-subtree">
                                    @foreach ($focusedHierarchy['reportsTree'] as $node)
                                        @include('livewire.admin.hierarchy.partials.simple-node', [
                                            'node' => $node,
                                            'needle' => $treeSearch,
                                            'selectedManagerId' => $selectedManagerId,
                                        ])
                                    @endforeach
                                </ul>
                            @endif
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- LEFT Offcanvas: Lista de Usuários com Filtro de Empresa --}}
    <div wire:ignore.self class="offcanvas offcanvas-start bg-dark text-white shadow" tabindex="-1" id="leftOffcanvas"
        aria-labelledby="leftOffcanvasLabel">
        <div class="offcanvas-header bg-secondary border-bottom border-light-subtle">
            <h5 class="offcanvas-title" id="leftOffcanvasLabel"><i class="bi bi-person-lines-fill me-2"></i> Lista de
                Usuários</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <div class="mb-3">
                <input class="form-control form-control-sm mb-2 bg-secondary text-white border-secondary"
                    placeholder="Buscar por nome ou e-mail..." wire:model.debounce.400ms="leftSearch">
                <select class="form-select form-select-sm bg-secondary text-white border-secondary"
                    wire:model.debounce.400ms="companyFilter">
                    <option value="">Todas as Empresas</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-grow-1 overflow-auto">
                @forelse ($directory as $u)
                    <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2 user-list-item {{ $u->id === $selectedManagerId ? 'bg-primary border-primary text-white' : 'bg-dark-subtle border-secondary text-white-50' }}"
                        wire:click="selectUserFromList('{{ $u->id }}')" style="cursor: pointer;">
                        <div>
                            <div><strong>{{ $u->name }}</strong></div>
                            <div class="small text-white-50">{{ $u->email }}</div>
                        </div>
                        <input type="checkbox" wire:click.stop="toggleCandidate('{{ $u->id }}')"
                            @checked(in_array($u->id, $selectedCandidateIds, true)) title="Selecionar para atribuição">
                    </div>
                @empty
                    <div class="text-white-50 text-center p-3">Nenhum usuário encontrado com os critérios de busca.
                    </div>
                @endforelse
            </div>
            <div class="mt-auto pt-3 border-top border-secondary">
                {{ $directory->links('pagination::bootstrap-5') }} {{-- Garante que a paginação use Bootstrap 5 --}}
            </div>
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-sm btn-light" wire:click="clearCandidates">Limpar Seleção</button>
                <button class="btn btn-sm btn-warning ms-auto" wire:click="assignCandidatesToManager"
                    @disabled(!$selectedManagerId || empty($selectedCandidateIds))
                    title="Atribui os usuários selecionados da lista ao gerente no Organograma">
                    Atribuir ao focado
                </button>
            </div>
        </div>
    </div>

    {{-- RIGHT Offcanvas: Detalhes e Ações do Usuário Focado + Delegações --}}
    <div wire:ignore.self class="offcanvas offcanvas-end bg-dark text-white shadow" tabindex="-1" id="rightOffcanvas"
        aria-labelledby="rightOffcanvasLabel">
        <div class="offcanvas-header bg-secondary border-bottom border-light-subtle">
            <h5 class="offcanvas-title" id="rightOffcanvasLabel"><i class="bi bi-info-circle-fill me-2"></i> Detalhes
                e Ações</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            {{-- Card de Detalhes e Ações --}}
            <div class="card bg-secondary text-white mb-3 flex-grow-0">
                <div class="card-header bg-secondary border-bottom border-light-subtle">
                    <h6 class="mb-0">Detalhes do Usuário Focado</h6>
                </div>
                <div class="card-body">
                    @if ($selectedManagerId)
                        @php $sel = \App\Models\User::select('id','name','email','manager_id')->find($selectedManagerId); @endphp
                        @if ($sel)
                            <div class="mb-3">
                                <h5 class="mb-1 text-warning">{{ $sel->name }}</h5>
                                <p class="small text-white-50 mb-0">{{ $sel->email }}</p>
                                @if ($sel->manager_id)
                                    @php $parent = \App\Models\User::find($sel->manager_id); @endphp
                                    <p class="small text-white-50 mt-1 mb-0">Gerente direto:
                                        {{ $parent->name ?? 'Não encontrado' }}</p>
                                @else
                                    <p class="small text-white-50 mt-1 mb-0">Este usuário é uma raiz na hierarquia.</p>
                                @endif
                            </div>

                            <hr class="border-secondary">

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-warning btn-sm"
                                    wire:click="openMoveModal('{{ $selectedManagerId }}')"><i
                                        class="bi bi-arrows-move me-2"></i> Mover Posição...</button>
                                @if ($sel->manager_id)
                                    <button class="btn btn-outline-info btn-sm"
                                        wire:click="setAsRoot('{{ $selectedManagerId }}')"><i
                                            class="bi bi-file-earmark-person me-2"></i> Tornar Raiz</button>
                                @else
                                    <button class="btn btn-outline-info btn-sm" disabled
                                        title="Este usuário já é uma raiz."><i
                                            class="bi bi-file-earmark-person me-2"></i> Já é Raiz</button>
                                @endif
                                <button class="btn btn-success btn-sm" wire:click="openDelegation"><i
                                        class="bi bi-person-check-fill me-2"></i> Criar Nova Delegação...</button>
                            </div>
                        @else
                            <div class="text-white-50 text-center p-3">Detalhes do usuário selecionado não encontrados.
                            </div>
                        @endif
                    @else
                        <div class="text-white-50 text-center p-3">Selecione um usuário para ver seus detalhes e
                            gerenciar ações.</div>
                    @endif
                </div>
            </div>

            {{-- Card de Delegações Ativas --}}
            <div class="card bg-secondary text-white flex-grow-1 overflow-auto">
                <div class="card-header bg-secondary border-bottom border-light-subtle">
                    <h6 class="mb-0">Delegações Ativas</h6>
                </div>
                <div class="card-body p-2">
                    @if (!$selectedManagerId)
                        <div class="text-white-50 text-center p-2">Selecione um usuário para ver suas delegações
                            ativas.</div>
                    @else
                        @forelse($delegations as $d)
                            <div class="bg-dark-subtle border border-info rounded p-2 mb-2 text-white shadow-sm">
                                <div class="small">Titular: <strong>{{ $d->principal->name }}</strong></div>
                                <div class="small">Delegado: <strong>{{ $d->delegate->name }}</strong></div>
                                <div class="small text-muted">
                                    {{ $d->valid_from->format('d/m/Y') }} —
                                    {{ $d->valid_to ? $d->valid_to->format('d/m/Y') : 'sem fim' }}
                                </div>
                                <div class="small text-muted">Motivo: {{ $d->reason }}</div>
                            </div>
                        @empty
                            <div class="text-white-50 text-center p-2">Nenhuma delegação ativa para este usuário.</div>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Mover Para... --}}
    <div class="modal fade" id="mvModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title"><i class="bi bi-arrows-move me-2"></i> Mover <strong
                            class="text-warning">Usuário</strong> para…</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light text-dark">
                    <p class="small text-muted">Selecione o novo gerente para o usuário. Você não pode mover para si
                        mesmo ou para alguém em sua própria sub-árvore.</p>
                    <input class="form-control form-control-sm mb-2 bg-white text-dark"
                        placeholder="Buscar gerente alvo…" wire:model.debounce.300ms="moveTargetSearch">
                    <div class="list-group small" style="max-height: 200px; overflow-y: auto;">
                        @forelse ($moveTargets as $t)
                            <label
                                class="list-group-item d-flex justify-content-between align-items-center bg-white border-light text-dark">
                                <span>{{ $t->name }} <span class="text-muted">—
                                        {{ $t->email }}</span></span>
                                <input type="radio" name="mv_target" wire:model="moveTargetId"
                                    value="{{ $t->id }}">
                            </label>
                        @empty
                            <div class="list-group-item text-muted bg-white">Nenhum gerente alvo encontrado ou válido
                                para este movimento.</div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm btn-primary" wire:click="confirmMove"
                        @disabled(!$moveTargetId || $moveUserId === $moveTargetId)">Mover</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Delegação --}}
    <div class="modal fade" id="dlgDelegation" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title"><i class="bi bi-person-check-fill me-2"></i> Registrar Nova Delegação</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light text-dark">
                    <p class="small text-muted">A delegação será criada para o usuário atualmente focado (Titular).</p>
                    <div class="mb-2">
                        <label class="form-label small">Titular (quem delega)</label>
                        <select
                            class="form-select form-select-sm bg-white text-dark @error('dlg_principal_id') is-invalid @enderror"
                            wire:model="dlg_principal_id" disabled>
                            <option value="{{ $dlg_principal_id }}">
                                {{ \App\Models\User::find($dlg_principal_id)?->name ?? 'Nenhum selecionado' }}</option>
                        </select>
                        @error('dlg_principal_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Delegado (quem recebe)</label>
                        <select
                            class="form-select form-select-sm bg-white text-dark @error('dlg_delegate_id') is-invalid @enderror"
                            wire:model="dlg_delegate_id">
                            <option value="">— selecione o delegado —</option>
                            @foreach (\App\Models\User::orderBy('name')->whereNull('deleted_at')->get(['id', 'name']) as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('dlg_delegate_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row g-2">
                        <div class="col"><label class="form-label small">Início da Delegação</label>
                            <input type="date"
                                class="form-control form-control-sm bg-white text-dark @error('dlg_from') is-invalid @enderror"
                                wire:model="dlg_from">
                            @error('dlg_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col"><label class="form-label small">Fim da Delegação (Opcional)</label>
                            <input type="date"
                                class="form-control form-control-sm bg-white text-dark @error('dlg_to') is-invalid @enderror"
                                wire:model="dlg_to">
                            @error('dlg_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label small">Motivo</label>
                        <input class="form-control form-control-sm bg-white text-dark" wire:model="dlg_reason"
                            placeholder="Ex: Férias, licença, viagem...">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-sm btn-success" wire:click="saveDelegation" @disabled(!$dlg_principal_id || !$dlg_delegate_id || !$dlg_from)">
                        Salvar Delegação
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos CSS Customizados --}}
    <style>
        body {
            background-color: #f1f5f9;
            /* Um cinza bem claro para o fundo geral */
        }

        /* Estilos para a lista de usuários na offcanvas esquerda */
        .user-list-item {
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .user-list-item:hover {
            background-color: var(--bs-primary) !important;
            /* Mais destaque ao passar o mouse */
            border-color: var(--bs-primary) !important;
            color: white !important;
        }

        .user-list-item:hover .small {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        .user-list-item.bg-primary {
            /* Estilo para o item selecionado na lista */
            color: white !important;
        }

        .user-list-item.bg-primary .small {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        /* Estilos gerais da árvore (para a árvore focada) */
        .hierarchy-main-container {
            padding-left: 0;
            text-align: center;
        }

        .hierarchy-main-container>li {
            list-style: none;
            position: relative;
        }

        /* Linha vertical de conexão */
        .connection-line-vertical {
            width: 1px;
            height: 20px;
            background-color: #adb5bd;
            /* Um cinza um pouco mais escuro para as linhas */
            margin: 0 auto;
            display: block;
        }

        /* Nó do Gerente Acima */
        .hierarchy-manager-above .node {
            background-color: var(--bs-secondary-bg-subtle);
            /* Fundo claro para o gerente acima */
            border-color: var(--bs-secondary);
            cursor: pointer;
        }

        .hierarchy-manager-above .node:hover {
            background-color: var(--bs-secondary);
            border-color: var(--bs-dark);
            color: white;
        }

        .hierarchy-manager-above .node:hover .node-title,
        .hierarchy-manager-above .node:hover .node-subtitle {
            color: white;
        }


        /* Nó do Usuário Focado */
        .hierarchy-focused-user .node-primary-focus {
            background-color: var(--bs-primary-bg-subtle);
            /* Azul claro para o nó principal */
            border-color: var(--bs-primary);
            position: relative;
            box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, 0.2);
            /* Sombra azul para destacar */
        }

        .hierarchy-focused-user .node-primary-focus:hover {
            background-color: var(--bs-primary-bg-subtle);
            /* Mantém o hover */
        }

        /* Sub-árvore de Relatórios */
        .hierarchy-reports-subtree {
            padding-left: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
            position: relative;
            margin-top: 0;
            padding-top: 20px;
        }

        .hierarchy-reports-subtree::before {
            /* Linha vertical do foco para a linha horizontal dos filhos */
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 1px;
            height: 20px;
            background-color: #adb5bd;
        }

        .hierarchy-reports-subtree>li {
            list-style: none;
            position: relative;
            text-align: center;
            padding-top: 20px;
        }

        .hierarchy-reports-subtree>li::before {
            /* Linha vertical para o nó filho */
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 1px;
            height: 20px;
            background-color: #adb5bd;
        }

        /* Linhas horizontais conectando irmãos */
        .hierarchy-reports-subtree:has(> li)::after {
            /* Linha horizontal que conecta todos os irmãos */
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 1px;
            background-color: #adb5bd;
            z-index: -1;
        }


        /* Estilo base para cada nó (cartão de usuário) */
        .node {
            padding: .6rem 1rem;
            border: 1px solid #ced4da;
            border-radius: .5rem;
            background-color: #fff;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
            word-break: break-word;
            max-width: 190px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0.1rem 0.2rem rgba(0, 0, 0, 0.05);
        }

        .node:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 0.2rem 0.4rem rgba(13, 110, 253, 0.1);
        }

        /* Destaque para nós que correspondem à busca */
        .node[data-match="1"] {
            background-color: var(--bs-yellow-100);
            /* Amarelo claro */
            border-color: var(--bs-yellow-500);
            /* Amarelo */
            box-shadow: 0 0.2rem 0.4rem rgba(255, 193, 7, 0.15);
        }

        .node-title {
            font-weight: bold;
            color: #343a40;
            line-height: 1.2;
            font-size: 1.05em;
        }

        .node-subtitle {
            font-size: 0.8em;
            color: #6c757d;
        }

        /* Ações dentro dos nós filhos */
        .node-child-actions {
            margin-top: .5rem;
            display: flex;
            gap: 0.25rem;
            justify-content: center;
        }

        .node-child-actions .btn {
            --bs-btn-padding-y: .1rem;
            --bs-btn-padding-x: .4rem;
            --bs-btn-font-size: .75rem;
        }

        /* Offcanvas customização */
        .offcanvas.bg-dark {
            background-color: #212529 !important;
            /* Cor de fundo mais escura para offcanvas */
            color: #fff;
        }

        .offcanvas .offcanvas-header.bg-secondary {
            background-color: #343a40 !important;
            /* Header ainda mais escuro */
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        .offcanvas .card.bg-secondary {
            background-color: #343a40 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        .offcanvas .card-header.bg-secondary {
            background-color: #343a40 !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        .offcanvas .form-control,
        .offcanvas .form-select {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        .offcanvas .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        .offcanvas .form-select option {
            background-color: #343a40;
            /* Cor para opções do select */
            color: #fff;
        }

        .offcanvas .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .offcanvas .user-list-item.bg-dark-subtle {
            background-color: #495057 !important;
            /* Um cinza mais escuro para itens não selecionados */
            border-color: #6c757d !important;
        }

        .offcanvas .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
            /* Torna o X branco */
        }

        .offcanvas .pagination .page-link {
            background-color: #495057;
            color: white;
            border-color: #6c757d;
        }

        .offcanvas .pagination .page-item.active .page-link {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .offcanvas .pagination .page-link:hover {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
    </style>

    <script>
        document.addEventListener('livewire:load', () => {
            const mvModal = new bootstrap.Modal(document.getElementById('mvModal'));
            const dlgDelegationModal = new bootstrap.Modal(document.getElementById('dlgDelegation'));
            // Instâncias de offcanvas (getOrCreateInstance é bom para evitar múltiplos objetos)
            const leftOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('leftOffcanvas'));
            const rightOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById(
                'rightOffcanvas'));

            window.addEventListener('show-move-modal', (e) => {
                const mvModalEl = document.getElementById('mvModal');
                const mvModalTitleStrong = mvModalEl.querySelector('.modal-title strong');
                if (mvModalTitleStrong && e.detail.userName) {
                    mvModalTitleStrong.textContent = e.detail.userName;
                }
                mvModal.show();
            });
            window.addEventListener('hide-move-modal', () => mvModal.hide());
            window.addEventListener('show-delegation-modal', () => dlgDelegationModal.show());
            window.addEventListener('hide-delegation-modal', () => dlgDelegationModal.hide());

            // Eventos para fechar as offcanvas programaticamente (ex: ao selecionar item da lista em mobile)
            window.addEventListener('hide-left-offcanvas', () => leftOffcanvas.hide());
            window.addEventListener('hide-right-offcanvas', () => rightOffcanvas.hide());


            window.addEventListener('toast', e => {
                console.log(`[toast] Tipo: ${e.detail.type}, Mensagem: ${e.detail.msg}`);
                // Implementação de Toast (exemplo básico, você pode usar uma biblioteca ou Bootstrap Toast)
                // Para usar o Toast do Bootstrap, você precisaria de um container e um toast div no seu HTML:
                /*
                <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
                    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-primary text-white">
                            <strong class="me-auto">Notificação</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body"></div>
                    </div>
                </div>
                */
                // E no JS:
                // const liveToast = document.getElementById('liveToast');
                // if (liveToast) {
                //     const toastBody = liveToast.querySelector('.toast-body');
                //     toastBody.textContent = e.detail.msg;
                //     liveToast.classList.remove('bg-success', 'bg-warning', 'bg-danger');
                //     if (e.detail.type === 'success') liveToast.classList.add('bg-success');
                //     else if (e.detail.type === 'warning') liveToast.classList.add('bg-warning');
                //     else if (e.detail.type === 'error') liveToast.classList.add('bg-danger');
                //     const toast = bootstrap.Toast.getOrCreateInstance(liveToast);
                //     toast.show();
                // }
            });
        });
    </script>
</div>
