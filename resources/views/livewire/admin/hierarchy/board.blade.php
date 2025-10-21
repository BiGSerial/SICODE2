<div class="container-fluid py-3" wire:ignore.self>
    {{-- Top Bar --}}
    <div class="d-flex align-items-center justify-content-between mb-3 px-2">
        <button class="btn btn-dark d-flex align-items-center" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#leftOffcanvas" aria-controls="leftOffcanvas" title="Abrir Lista de Usuários">
            <i class="bi bi-person-lines-fill me-2"></i> <span class="d-none d-sm-inline">Usuários</span>
        </button>

        <h3 class="flex-grow-1 text-center text-primary mb-0">Gestão de Hierarquia de Usuários</h3>

        <button class="btn btn-dark d-flex align-items-center" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#rightOffcanvas" aria-controls="rightOffcanvas" title="Abrir Detalhes e Ações">
            <span class="d-none d-sm-inline">Detalhes</span> <i class="bi bi-info-circle-fill ms-2"></i>
        </button>
    </div>

    {{-- CONTEÚDO PRINCIPAL: Organograma --}}
    <div class="row g-3">
        <div class="col-12">
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
                            <span class="text-white-50">
                                Selecione um usuário na "Lista de Usuários" para visualizar sua hierarquia,
                                ou veja a visão geral completa abaixo.
                            </span>
                        @endif
                    </div>
                </div>

                <div class="card-body p-3"
                    style="min-height: calc(100vh - 200px); max-height: calc(100vh - 100px); overflow:auto;">
                    @if (!$selectedManagerId)
                        {{-- ====== VISÃO GERAL (FLORESTA COMPLETA) ====== --}}
                        @if (empty($fullHierarchy))
                            <div class="alert alert-info text-center mt-5" role="alert">
                                <i class="bi bi-info-circle me-2"></i>
                                Nenhum usuário ativo encontrado{{ $companyFilter ? ' para a empresa filtrada' : '' }}.
                            </div>
                        @else
                            <ul class="list-unstyled hierarchy-main-container">
                                <li class="hierarchy-focused-user">
                                    <div class="node node-primary-focus mx-auto" title="Visão geral da organização">
                                        <div class="node-header d-flex justify-content-center mb-1">
                                            <span class="badge bg-primary small-badge">Visão Geral</span>
                                        </div>
                                        <div class="node-body">
                                            <div class="node-title">Organograma Completo</div>
                                            <div class="node-subtitle">Clique em um usuário para focar.</div>
                                        </div>
                                    </div>
                                    {{-- Não há connection-line-vertical aqui, pois é o "root" da visão geral --}}
                                </li>

                                {{-- Raízes e suas subárvores --}}
                                <ul class="list-unstyled hierarchy-reports-subtree mt-3"> {{-- Adiciona margem top aqui --}}
                                    @foreach ($fullHierarchy as $root)
                                        @include('livewire.admin.hierarchy.partials.simple-node', [
                                            'node' => $root,
                                            'needle' => $treeSearch,
                                            'selectedManagerId' => $selectedManagerId, // Ainda nulo aqui, mas passado
                                        ])
                                    @endforeach
                                </ul>
                            </ul>
                        @endif
                    @elseif (empty($focusedHierarchy['focusedUser']))
                        <div class="alert alert-warning text-center mt-5" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Não foi possível carregar a hierarquia para o usuário selecionado (usuário não encontrado ou
                            excluído).
                        </div>
                    @else
                        <ul class="list-unstyled hierarchy-main-container">
                            {{-- Gerente Imediato --}}
                            @if ($focusedHierarchy['manager'])
                                @php
                                    $mgrCompanyBadge = !empty($focusedHierarchy['manager']['company_name'])
                                        ? explode(' ', $focusedHierarchy['manager']['company_name'])[0]
                                        : '';
                                @endphp
                                <li class="hierarchy-manager-above">
                                    <div class="node node-manager-above mx-auto"
                                        wire:click.prevent="selectManager('{{ $focusedHierarchy['manager']['id'] }}')"
                                        title="Clique para focar neste gerente">
                                        <div class="node-header d-flex justify-content-between align-items-center mb-1">
                                            @if ($mgrCompanyBadge)
                                                <span
                                                    class="badge bg-secondary small-badge">{{ $mgrCompanyBadge }}</span>
                                            @else
                                                <div></div>
                                            @endif
                                            <div></div> {{-- Para balancear justify-content-between --}}
                                        </div>
                                        <div class="node-body">
                                            <div class="node-title">{{ $focusedHierarchy['manager']['name'] }}</div>
                                            <div class="node-subtitle">{{ $focusedHierarchy['manager']['email'] }}</div>
                                        </div>
                                    </div>
                                    <div class="connection-line-vertical"></div>
                                </li>
                            @endif

                            {{-- Usuário Focado --}}
                            @php
                                $focusedUserCompanyBadge = !empty($focusedHierarchy['focusedUser']['company_name'])
                                    ? explode(' ', $focusedHierarchy['focusedUser']['company_name'])[0]
                                    : '';
                            @endphp
                            <li class="hierarchy-focused-user">
                                <div class="node node-primary-focus mx-auto"
                                    data-match="{{ $treeSearch ? (stripos($focusedHierarchy['focusedUser']['name'], $treeSearch) !== false || stripos($focusedHierarchy['focusedUser']['email'], $treeSearch) !== false ? '1' : '0') : '0' }}"
                                    wire:click.prevent="selectManager('{{ $focusedHierarchy['focusedUser']['id'] }}')"
                                    title="Você está focado neste usuário">
                                    <div class="node-header d-flex justify-content-between align-items-center mb-1">
                                        @if ($focusedUserCompanyBadge)
                                            <span
                                                class="badge bg-secondary small-badge">{{ $focusedUserCompanyBadge }}</span>
                                        @else
                                            <div></div>
                                        @endif
                                        <span class="badge bg-primary px-2 py-1 shadow-sm small-badge">FOCO</span>
                                    </div>
                                    <div class="node-body">
                                        <div class="node-title">{{ $focusedHierarchy['focusedUser']['name'] }}</div>
                                        <div class="node-subtitle">{{ $focusedHierarchy['focusedUser']['email'] }}
                                        </div>
                                    </div>
                                </div>
                                @if (!empty($focusedHierarchy['reportsTree']))
                                    <div class="connection-line-vertical"></div>
                                @endif
                            </li>

                            {{-- Subárvore de Relatórios --}}
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

    {{-- LEFT Offcanvas: Lista de Usuários + Filtro Empresa --}}
    <div wire:ignore.self class="offcanvas offcanvas-start bg-dark text-white shadow" tabindex="-1" id="leftOffcanvas"
        aria-labelledby="leftOffcanvasLabel">
        <div class="offcanvas-header bg-secondary border-bottom border-light-subtle">
            <h5 class="offcanvas-title" id="leftOffcanvasLabel">
                <i class="bi bi-person-lines-fill me-2"></i> Lista de Usuários
            </h5>
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
                {{ $directory->links('pagination::bootstrap-5') }}
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

    {{-- RIGHT Offcanvas: Detalhes + Delegações --}}
    <div wire:ignore.self class="offcanvas offcanvas-end bg-dark text-white shadow" tabindex="-1"
        id="rightOffcanvas" aria-labelledby="rightOffcanvasLabel">
        <div class="offcanvas-header bg-secondary border-bottom border-light-subtle">
            <h5 class="offcanvas-title" id="rightOffcanvasLabel">
                <i class="bi bi-info-circle-fill me-2"></i> Detalhes e Ações
            </h5>
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
                        @php $sel = \App\Models\User::select('id','name','email','manager_id','company_id')->with('company:id,name')->find($selectedManagerId); @endphp
                        @if ($sel)
                            <div class="mb-3">
                                <h5 class="mb-1 text-warning">{{ $sel->name }}</h5>
                                <p class="small text-white-50 mb-0">{{ $sel->email }}</p>
                                @if ($sel->company_id && $sel->company)
                                    <p class="small text-white-50 mb-0">Empresa: {{ $sel->company->name }}</p>
                                @endif
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
                                    wire:click="openMoveModal('{{ $selectedManagerId }}')">
                                    <i class="bi bi-arrows-move me-2"></i> Mover Posição...
                                </button>
                                @if ($sel->manager_id)
                                    <button class="btn btn-outline-info btn-sm"
                                        wire:click="setAsRoot('{{ $selectedManagerId }}')">
                                        <i class="bi bi-file-earmark-person me-2"></i> Tornar Raiz
                                    </button>
                                @else
                                    <button class="btn btn-outline-info btn-sm" disabled
                                        title="Este usuário já é uma raiz.">
                                        <i class="bi bi-file-earmark-person me-2"></i> Já é Raiz
                                    </button>
                                @endif
                                <button class="btn btn-success btn-sm" wire:click="openDelegation">
                                    <i class="bi bi-person-check-fill me-2"></i> Criar Nova Delegação...
                                </button>
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
                    <h6 class="modal-title">
                        <i class="bi bi-arrows-move me-2"></i> Mover <strong class="text-warning">Usuário</strong>
                        para…
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light text-dark">
                    <p class="small text-muted">Selecione o novo gerente. Não é permitido mover para si mesmo ou para
                        um descendente.</p>
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
                    <button class="btn btn-sm btn-primary" wire:click="confirmMove" @disabled(!$moveTargetId || $moveUserId === $moveTargetId)">
                        Mover
                    </button>
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
                        <div class="col">
                            <label class="form-label small">Início da Delegação</label>
                            <input type="date"
                                class="form-control form-control-sm bg-white text-dark @error('dlg_from') is-invalid @enderror"
                                wire:model="dlg_from">
                            @error('dlg_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col">
                            <label class="form-label small">Fim da Delegação (Opcional)</label>
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

    {{-- Estilos --}}
    <style>
        body {
            background-color: #f1f5f9;
        }

        .user-list-item {
            cursor: pointer;
            transition: background-color .2s, border-color .2s;
            position: relative;
            /* Para o badge da empresa */
        }

        .user-list-item:hover {
            background-color: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
            color: #fff !important;
        }

        .user-list-item:hover .small {
            color: rgba(255, 255, 255, .8) !important;
        }

        .user-list-item.bg-primary {
            color: #fff !important;
        }

        .user-list-item.bg-primary .small {
            color: rgba(255, 255, 255, .8) !important;
        }

        /* Organograma Estrutura Básica */
        .hierarchy-main-container {
            padding-left: 0;
            text-align: center;
        }

        .hierarchy-main-container>li {
            list-style: none;
            position: relative;
        }

        /* Conexões Verticais */
        .connection-line-vertical {
            width: 1px;
            height: 30px;
            /* Altura padrão para as linhas verticais */
            background-color: #adb5bd;
            margin: 0 auto;
            display: block;
            position: relative;
            z-index: 1;
            /* Para ficar acima da linha horizontal */
        }

        /* Estilo do Node (Card do Usuário) */
        .node {
            padding: .6rem 1rem;
            border: 1px solid #ced4da;
            border-radius: .5rem;
            background-color: #fff;
            transition: all .2s ease-in-out;
            cursor: pointer;
            word-break: break-word;
            max-width: 190px;
            min-height: 80px;
            /* Altura mínima para comportar conteúdo */
            display: flex;
            flex-direction: column;
            /* Coluna para header, body e actions */
            justify-content: center;
            align-items: center;
            box-shadow: 0 .1rem .2rem rgba(0, 0, 0, .05);
            margin-top: 30px;
            /* Empurra o nó para baixo para a linha vertical conectar no topo */
            position: relative;
            /* Para os badges dentro do nó */
        }

        /* Reset margin-top para o nó raiz (se for o primeiro item do UL principal e não tiver uma linha vertical acima) */
        .hierarchy-main-container>li>.node,
        .hierarchy-focused-user>.node {
            margin-top: 0;
        }

        .node:hover {
            border-color: var(--bs-primary);
            box-shadow: 0 .2rem .4rem rgba(13, 110, 253, .1);
        }

        .node[data-match="1"] {
            background-color: var(--bs-yellow-100);
            border-color: var(--bs-yellow-500);
            box-shadow: 0 .2rem .4rem rgba(255, 193, 7, .15);
        }

        /* Estrutura interna do Node */
        .node-header {
            width: 100%;
            margin-bottom: .25rem;
            /* Pequena margem abaixo do header */
            min-height: 1.5em;
            /* Garante espaço para o badge mesmo que vazio */
            display: flex;
            justify-content: center;
            /* Centraliza o badge se for o único item */
        }

        .node-body {
            text-align: center;
        }

        .node-title {
            font-weight: bold;
            color: #343a40;
            line-height: 1.2;
            font-size: 1.05em;
        }

        .node-subtitle {
            font-size: .8em;
            color: #6c757d;
        }

        .node-child-actions {
            margin-top: .5rem;
            display: flex;
            gap: .25rem;
            justify-content: center;
        }

        .node-child-actions .btn {
            --bs-btn-padding-y: .1rem;
            --bs-btn-padding-x: .4rem;
            --bs-btn-font-size: .75rem;
        }

        .small-badge {
            font-size: 0.65em;
            padding: 0.2em 0.4em;
            line-height: 1;
            /* position: static; ou relative dentro do flow do node-header */
        }

        /* Estilos específicos para tipos de nós */
        .hierarchy-manager-above .node {
            background-color: var(--bs-secondary-bg-subtle);
            border-color: var(--bs-secondary);
            cursor: pointer;
        }

        .hierarchy-manager-above .node:hover {
            background-color: var(--bs-secondary);
            border-color: var(--bs-dark);
            color: #fff;
        }

        .hierarchy-manager-above .node:hover .node-title,
        .hierarchy-manager-above .node:hover .node-subtitle {
            color: #fff;
        }

        .hierarchy-focused-user .node-primary-focus {
            background-color: var(--bs-primary-bg-subtle);
            border-color: var(--bs-primary);
            box-shadow: 0 0.25rem 0.5rem rgba(13, 110, 253, .2);
        }

        .hierarchy-focused-user .node-primary-focus:hover {
            background-color: var(--bs-primary-bg-subtle);
        }

        /* Estilos para a Sub-árvore de Relatórios */
        .hierarchy-reports-subtree {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
            padding-left: 0;
            margin-top: 0;
            padding-top: 30px;
            /* Espaço para as linhas que vêm de cima (do pai) */
        }

        /* Linha horizontal que conecta os irmãos */
        .hierarchy-reports-subtree::before {
            content: '';
            position: absolute;
            top: 0;
            /* Começa no topo do padding-top do UL */
            left: 0;
            right: 0;
            height: 1px;
            background-color: #adb5bd;
            z-index: 0;
            /* Fica abaixo das linhas verticais */
        }

        .hierarchy-reports-subtree>li {
            list-style: none;
            position: relative;
            text-align: center;
            margin: 0 10px;
            /* Espaçamento horizontal entre os nós irmãos */
            /* O padding-top para o li foi removido, a linha vertical será desenhada do topo do li até o node */
        }

        /* Linha vertical que desce da linha horizontal para cada nó filho */
        .hierarchy-reports-subtree>li::before {
            content: '';
            position: absolute;
            top: 0;
            /* Começa no topo do LI, alinhado com a linha horizontal */
            left: 50%;
            transform: translateX(-50%);
            width: 1px;
            height: 30px;
            /* Comprimento da linha vertical até o nó (que tem margin-top: 30px) */
            background-color: #adb5bd;
            z-index: 1;
            /* Acima da linha horizontal */
        }

        /* Offcanvas customização (manter como está) */
        .offcanvas.bg-dark {
            background-color: #212529 !important;
            color: #fff;
        }

        .offcanvas .offcanvas-header.bg-secondary,
        .offcanvas .card-header.bg-secondary,
        .offcanvas .card.bg-secondary {
            background-color: #343a40 !important;
            border-color: rgba(255, 255, 255, .1) !important;
        }

        .offcanvas .form-control,
        .offcanvas .form-select {
            background-color: rgba(255, 255, 255, .1) !important;
            color: #fff !important;
            border-color: rgba(255, 255, 255, .2) !important;
        }

        .offcanvas .form-control::placeholder {
            color: rgba(255, 255, 255, .6) !important;
        }

        .offcanvas .form-select option {
            background-color: #343a40;
            color: #fff;
        }

        .offcanvas .text-muted {
            color: rgba(255, 255, 255, .7) !important;
        }

        .offcanvas .user-list-item.bg-dark-subtle {
            background-color: #495057 !important;
            border-color: #6c757d !important;
        }

        .offcanvas .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .offcanvas .pagination .page-link {
            background-color: #495057;
            color: #fff;
            border-color: #6c757d;
        }

        .offcanvas .pagination .page-item.active .page-link,
        .offcanvas .pagination .page-link:hover {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        /* Ajustes para telas menores (responsividade) */
        @media (max-width: 991.98px) {
            .connection-line-vertical {
                height: 15px;
                /* Altura menor para mobile */
            }

            .node {
                margin-top: 15px;
                /* Ajusta a margem para mobile */
            }

            .hierarchy-reports-subtree {
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding-top: 15px;
            }

            .hierarchy-reports-subtree::before {
                display: none;
                /* Esconde a linha horizontal em modo coluna */
            }

            .hierarchy-reports-subtree>li {
                margin: 0;
            }

            .hierarchy-reports-subtree>li::before {
                height: 15px;
                /* Altura menor para mobile */
            }
        }
    </style>

    <script>
        document.addEventListener('livewire:load', () => {
            const mvModal = new bootstrap.Modal(document.getElementById('mvModal'));
            const dlgDelegationModal = new bootstrap.Modal(document.getElementById('dlgDelegation'));
            const leftOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('leftOffcanvas'));
            const rightOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById(
                'rightOffcanvas'));

            window.addEventListener('show-move-modal', (e) => {
                const mvModalEl = document.getElementById('mvModal');
                const strongEl = mvModalEl.querySelector('.modal-title strong');
                if (strongEl && e.detail?.userName) strongEl.textContent = e.detail.userName;
                mvModal.show();
            });
            window.addEventListener('hide-move-modal', () => mvModal.hide());
            window.addEventListener('show-delegation-modal', () => dlgDelegationModal.show());
            window.addEventListener('hide-delegation-modal', () => dlgDelegationModal.hide());

            window.addEventListener('hide-left-offcanvas', () => leftOffcanvas.hide());
            window.addEventListener('hide-right-offcanvas', () => rightOffcanvas.hide());

            window.addEventListener('toast', e => {
                console.log(`[toast] Tipo: ${e.detail.type}, Mensagem: ${e.detail.msg}`);
                // implemente o Toast do Bootstrap aqui se quiser
            });
        });
    </script>
</div>
