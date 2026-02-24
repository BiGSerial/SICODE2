<div>
    <x-show-loading />
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

                        {{-- ====== VISÃO GERAL (SEM FOCO) ====== --}}
                        @if (!$selectedManagerId)
                            @if (empty($fullHierarchy))
                                <div class="alert alert-info text-center mt-5" role="alert">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Nenhum usuário ativo
                                    encontrado{{ $companyFilter ? ' para a empresa filtrada' : '' }}.
                                </div>
                            @else
                                <div class="tree" id="orgTree">
                                    <svg class="org-lines"></svg>
                                    <ul>
                                        <li>
                                            <div class="node node-primary-focus" title="Visão geral da organização"
                                                data-node-id="overview">
                                                <div class="node-header d-flex justify-content-between">
                                                    <span></span>
                                                    <span class="badge bg-primary small-badge">Visão Geral</span>
                                                </div>
                                                <div class="node-body">
                                                    <div class="node-title">Organograma Completo</div>
                                                    <div class="node-subtitle">Clique em um usuário para focar.</div>
                                                </div>
                                            </div>

                                            {{-- Raízes e suas subárvores --}}
                                            <ul>
                                                @foreach ($fullHierarchy as $root)
                                                    @include(
                                                        'livewire.admin.hierarchy.partials.simple-node',
                                                        [
                                                            'node' => $root,
                                                            'needle' => $treeSearch,
                                                            'selectedManagerId' => $selectedManagerId,
                                                            'isRootOfFullHierarchy' => true,
                                                        ]
                                                    )
                                                @endforeach
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            @endif

                            {{-- ====== VISÃO COM FOCO ====== --}}
                        @elseif (empty($focusedHierarchy['focusedUser']))
                            <div class="alert alert-warning text-center mt-5" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Não foi possível carregar a hierarquia para o usuário selecionado (usuário não
                                encontrado ou
                                excluído).
                            </div>
                        @else
                            @php
                                $focusedUserCompanyBadge = !empty($focusedHierarchy['focusedUser']['company_name'])
                                    ? explode(' ', $focusedHierarchy['focusedUser']['company_name'])[0]
                                    : '';
                                $matchFocused = $treeSearch
                                    ? stripos($focusedHierarchy['focusedUser']['name'], $treeSearch) !== false ||
                                        stripos($focusedHierarchy['focusedUser']['email'], $treeSearch) !== false
                                    : false;

                                $mgr = $focusedHierarchy['manager'] ?? null;
                                $mgrBadge =
                                    $mgr && !empty($mgr['company_name']) ? explode(' ', $mgr['company_name'])[0] : '';

                                // === Preparar info de delegações (apenas para focado e gerente) ===
                                $focusedId = data_get($focusedHierarchy, 'focusedUser.id');
                                $mgrId = data_get($focusedHierarchy, 'manager.id');
                                $focusedDeleg = null; // ['role'=>'principal|delegate', 'principal'=>['id','name'], 'delegate'=>[...] ]
                                $mgrDeleg = null;

                                if (isset($delegations)) {
                                    foreach ($delegations as $d) {
                                        // Focado
                                        if (
                                            !$focusedDeleg &&
                                            ($d->principal_id === $focusedId || $d->delegate_id === $focusedId)
                                        ) {
                                            $focusedDeleg = [
                                                'role' => $d->principal_id === $focusedId ? 'principal' : 'delegate',
                                                'principal' => [
                                                    'id' => $d->principal_id,
                                                    'name' => data_get($d, 'principal.name'),
                                                ],
                                                'delegate' => [
                                                    'id' => $d->delegate_id,
                                                    'name' => data_get($d, 'delegate.name'),
                                                ],
                                            ];
                                        }
                                        // Gerente
                                        if (
                                            $mgrId &&
                                            !$mgrDeleg &&
                                            ($d->principal_id === $mgrId || $d->delegate_id === $mgrId)
                                        ) {
                                            $mgrDeleg = [
                                                'role' => $d->principal_id === $mgrId ? 'principal' : 'delegate',
                                                'principal' => [
                                                    'id' => $d->principal_id,
                                                    'name' => data_get($d, 'principal.name'),
                                                ],
                                                'delegate' => [
                                                    'id' => $d->delegate_id,
                                                    'name' => data_get($d, 'delegate.name'),
                                                ],
                                            ];
                                        }
                                        if ($focusedDeleg && $mgrDeleg) {
                                            break;
                                        }
                                    }
                                }
                            @endphp

                            <div class="tree" id="orgTree">
                                <svg class="org-lines"></svg>
                                <ul>
                                    <li>
                                        {{-- Se houver gerente, ele fica ACIMA e o focado é seu ÚNICO filho --}}
                                        @if ($mgr)
                                            <div class="node node-manager-above {{ data_get($mgrDeleg, 'role') === 'principal' ? 'node-delegator' : (data_get($mgrDeleg, 'role') === 'delegate' ? 'node-delegate' : '') }}"
                                                data-node-id="mgr-{{ $mgr['id'] }}"
                                                wire:click.prevent="selectManager('{{ $mgr['id'] }}')"
                                                title="Clique para focar neste gerente">
                                                <div class="node-header">
                                                    @if ($mgrBadge)
                                                        <span
                                                            class="badge bg-secondary small-badge">{{ $mgrBadge }}</span>
                                                    @else
                                                        <span></span>
                                                    @endif
                                                    @if (($mgr['observing_count'] ?? 0) > 0)
                                                        <span class="badge bg-info text-dark small-badge">
                                                            OBS: {{ $mgr['observing_count'] }}
                                                        </span>
                                                    @else
                                                        <span></span>
                                                    @endif
                                                </div>
                                                <div class="node-body">
                                                    <div class="node-title">{{ $mgr['name'] }}</div>
                                                    <div class="node-subtitle">{{ $mgr['email'] }}</div>

                                                    {{-- Chips de delegação do gerente --}}
                                                    @if (data_get($mgrDeleg, 'role') === 'principal')
                                                        {{-- O nó atual (gerente) é o Titular --}}
                                                        <div class="mt-1">
                                                            <span class="chip-principal">Titular:
                                                                {{ $mgr['name'] }}</span>
                                                            <span class="chip-with"> → delegou para
                                                                {{ data_get($mgrDeleg, 'delegate.name') }}</span>
                                                        </div>
                                                    @elseif (data_get($mgrDeleg, 'role') === 'delegate')
                                                        {{-- O nó atual (gerente) é o Delegado --}}
                                                        <div class="mt-1">
                                                            <span class="chip-delegate">Delegado:
                                                                {{ $mgr['name'] }}</span>
                                                            <span class="chip-with"> de
                                                                {{ data_get($mgrDeleg, 'principal.name') }}</span>
                                                        </div>
                                                    @endif

                                                    @if (($mgr['observing_count'] ?? 0) > 0)
                                                        <div class="mt-1">
                                                            <span class="chip-with">
                                                                Observa {{ $mgr['observing_count'] }} vínculo(s)
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>

                                            </div>

                                            {{-- Filho único do gerente = usuário focado --}}
                                            <ul>
                                                <li>
                                        @endif

                                        <div class="node node-primary-focus {{ data_get($focusedDeleg, 'role') === 'principal' ? 'node-delegator' : (data_get($focusedDeleg, 'role') === 'delegate' ? 'node-delegate' : '') }}"
                                            data-node-id="foc-{{ $focusedHierarchy['focusedUser']['id'] }}"
                                            data-match="{{ $matchFocused ? '1' : '0' }}"
                                            wire:click.prevent="selectManager('{{ $focusedHierarchy['focusedUser']['id'] }}')"
                                            title="Você está focado neste usuário">
                                            <div class="node-header">
                                                @if ($focusedUserCompanyBadge)
                                                    <span
                                                        class="badge bg-secondary small-badge">{{ $focusedUserCompanyBadge }}</span>
                                                @else
                                                    <span></span>
                                                @endif
                                                <span class="d-inline-flex gap-1">
                                                    @if (($focusedHierarchy['focusedUser']['observing_count'] ?? 0) > 0)
                                                        <span class="badge bg-info text-dark small-badge">
                                                            OBS: {{ $focusedHierarchy['focusedUser']['observing_count'] }}
                                                        </span>
                                                    @endif
                                                    <span
                                                        class="badge bg-primary px-2 py-1 shadow-sm small-badge">FOCO</span>
                                                </span>
                                            </div>
                                            <div class="node-body">
                                                <div class="node-title">{{ $focusedHierarchy['focusedUser']['name'] }}
                                                </div>
                                                <div class="node-subtitle">
                                                    {{ $focusedHierarchy['focusedUser']['email'] }}</div>

                                                @php
                                                    $fuName = $focusedHierarchy['focusedUser']['name'];
                                                    $fuRole = data_get($focusedDeleg, 'role'); // 'principal' | 'delegate' | null
                                                    $fuOther =
                                                        $fuRole === 'principal'
                                                            ? data_get($focusedDeleg, 'delegate.name')
                                                            : ($fuRole === 'delegate'
                                                                ? data_get($focusedDeleg, 'principal.name')
                                                                : null);
                                                @endphp

                                                @if ($fuRole)
                                                    <div class="mt-1">
                                                        @if ($fuRole === 'principal')
                                                            <span class="chip-principal">Titular</span>
                                                            @if ($fuOther && $fuOther !== $fuName)
                                                                <span class="chip-with"> → delegou para
                                                                    {{ $fuOther }}</span>
                                                            @endif
                                                        @else
                                                            <span class="chip-delegate">Delegado</span>
                                                            @if ($fuOther && $fuOther !== $fuName)
                                                                <span class="chip-with"> de {{ $fuOther }}</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @endif

                                                @if (($focusedHierarchy['focusedUser']['observing_count'] ?? 0) > 0)
                                                    <div class="mt-1">
                                                        <span class="chip-with">
                                                            Observa
                                                            {{ $focusedHierarchy['focusedUser']['observing_count'] }}
                                                            vínculo(s)
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>

                                        </div>

                                        {{-- filhos diretos do focado --}}
                                        @if (!empty($focusedHierarchy['reportsTree']))
                                            <ul>
                                                @foreach ($focusedHierarchy['reportsTree'] as $node)
                                                    <li wire:key="child-{{ $node['id'] }}">
                                                        @include(
                                                            'livewire.admin.hierarchy.partials.simple-node',
                                                            [
                                                                'node' => $node,
                                                                'needle' => $treeSearch,
                                                                'selectedManagerId' => $selectedManagerId,
                                                            ]
                                                        )
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @if ($mgr)
                                    </li>
                                </ul>
                        @endif
                        </li>
                        </ul>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

{{-- LEFT Offcanvas --}}
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
                        @checked(in_array((string) $u->id, $selectedCandidateIds, true)) title="Selecionar para atribuição">
                </div>
            @empty
                <div class="text-white-50 text-center p-3">Nenhum usuário encontrado com os critérios de busca.</div>
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

{{-- RIGHT Offcanvas --}}
<div wire:ignore.self class="offcanvas offcanvas-end bg-dark text-white shadow" tabindex="-1" id="rightOffcanvas"
    aria-labelledby="rightOffcanvasLabel">
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
                    @php
                        $sel = \App\Models\User::select('id', 'name', 'email', 'manager_id', 'company_id')
                            ->with('company:id,name')
                            ->find($selectedManagerId);
                    @endphp
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
                                <button class="btn btn-outline-info btn-sm" wire:click="setAsRootSelected"
                                    wire:loading.attr="disabled" wire:target="setAsRootSelected">
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

                            <button class="btn btn-info btn-sm text-dark" wire:click="openObservation">
                                <i class="bi bi-eye-fill me-2"></i> Criar Nova Observação...
                            </button>
                        </div>
                    @else
                        <div class="text-white-50 text-center p-3">Detalhes do usuário selecionado não encontrados.
                        </div>
                    @endif
                @else
                    <div class="text-white-50 text-center p-3">Selecione um usuário para ver seus detalhes e gerenciar
                        ações.</div>
                @endif
            </div>
        </div>

        {{-- Card de Delegações Ativas --}}
        <div class="card bg-secondary text-white mb-3 flex-grow-1 overflow-auto">
            <div class="card-header bg-secondary border-bottom border-light-subtle">
                <h6 class="mb-0">Delegações Ativas</h6>
            </div>
            <div class="card-body p-2">
                @if (!$selectedManagerId)
                    <div class="text-white-50 text-center p-2">Selecione um usuário para ver suas delegações ativas.
                    </div>
                @else
                    @forelse($delegations as $d)
                        <div class="bg-dark border border-info rounded p-2 mb-2 text-white shadow-sm">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="small">Titular: <strong>{{ data_get($d, 'principal.name') }}</strong>
                                    </div>
                                    <div class="small">Delegado: <strong>{{ data_get($d, 'delegate.name') }}</strong>
                                    </div>
                                    <div class="small text-muted">
                                        {{ $d->valid_from->format('d/m/Y') }} —
                                        {{ $d->valid_to ? $d->valid_to->format('d/m/Y') : 'sem fim' }}
                                    </div>
                                    <div class="small text-muted">Motivo: {{ $d->reason }}</div>
                                </div>

                                <div class="d-flex gap-2">
                                    {{-- Finalizar (encerra agora) --}}
                                    <button class="btn btn-outline-warning btn-sm"
                                        wire:click="toFinalizeDelegation('{{ $d->id }}')"
                                        title="Finalizar agora esta delegação (deixa de ficar ativa)">
                                        Finalizar
                                    </button>

                                    {{-- Remover (exclui o registro) --}}
                                    <button class="btn btn-outline-danger btn-sm"
                                        wire:click="toDeleteDelegation('{{ $d->id }}')"
                                        title="Remover esta delegação definitivamente">
                                        Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-white-50 text-center p-2">Nenhuma delegação ativa para este usuário.</div>
                    @endforelse
                @endif
            </div>
        </div>

        {{-- Card de Observações Ativas --}}
        <div class="card bg-secondary text-white flex-grow-1 overflow-auto">
            <div class="card-header bg-secondary border-bottom border-light-subtle">
                <h6 class="mb-0">Observações Ativas do Focado</h6>
            </div>
            <div class="card-body p-2">
                @if (!$selectedManagerId)
                    <div class="text-white-50 text-center p-2">Selecione um usuário para ver as observações.</div>
                @else
                    @forelse($observations as $o)
                        <div class="bg-dark border border-primary rounded p-2 mb-2 text-white shadow-sm">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <div class="small">Alvo: <strong>{{ data_get($o, 'target.name') }}</strong></div>
                                    <div class="small text-muted">{{ data_get($o, 'target.email') }}</div>
                                    <div class="small mt-1">
                                        Modo:
                                        <span class="badge {{ $o->mode === 'subtree' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                            {{ $o->mode === 'subtree' ? 'Subárvore' : 'Somente Nó' }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        {{ $o->valid_from->format('d/m/Y') }} —
                                        {{ $o->valid_to ? $o->valid_to->format('d/m/Y') : 'sem fim' }}
                                    </div>
                                    <div class="small text-muted">Motivo: {{ $o->reason ?: '—' }}</div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-warning btn-sm"
                                        wire:click="toFinalizeObservation('{{ $o->id }}')"
                                        title="Finalizar agora esta observação">
                                        Finalizar
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm"
                                        wire:click="toDeleteObservation('{{ $o->id }}')"
                                        title="Remover esta observação definitivamente">
                                        Remover
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-white-50 text-center p-2">Nenhuma observação ativa para este usuário.</div>
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
                    <i class="bi bi-arrows-move me-2"></i> Mover <strong class="text-warning">Usuário</strong> para…
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light text-dark">
                <p class="small text-muted">Selecione o novo gerente. Não é permitido mover para si mesmo ou para um
                    descendente.</p>
                <input class="form-control form-control-sm mb-2 bg-white text-dark" placeholder="Buscar gerente alvo…"
                    wire:model.debounce.300ms="moveTargetSearch">
                <div class="list-group small" style="max-height: 200px; overflow-y: auto;">
                    @forelse ($moveTargets as $t)
                        <label
                            class="list-group-item d-flex justify-content-between align-items-center bg-white border-light text-dark"
                            wire:key="mv-target-{{ $t->id }}">
                            <span>{{ $t->name }} <span class="text-muted">— {{ $t->email }}</span></span>
                            <input type="radio" name="mv_target" wire:model="moveTargetId"
                                value="{{ (string) $t->id }}">
                        </label>
                    @empty
                        <div class="list-group-item text-muted bg-white">Nenhum gerente alvo encontrado ou válido para
                            este movimento.</div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-primary" wire:click="confirmMove" wire:loading.attr="disabled"
                    wire:target="confirmMove">
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
                            {{ \App\Models\User::find($dlg_principal_id)?->name ?? 'Nenhum selecionado' }}
                        </option>
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
                <button class="btn btn-sm btn-success" wire:click="saveDelegation" wire:loading.attr="disabled"
                    wire:target="saveDelegation" @disabled(!$dlg_principal_id || !$dlg_delegate_id || !$dlg_from)>
                    Salvar Delegação
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Observação --}}
<div class="modal fade" id="obsModal" tabindex="-1" wire:ignore.self>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-dark">
                <h6 class="modal-title"><i class="bi bi-eye-fill me-2"></i> Registrar Nova Observação</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light text-dark">
                <div class="mb-2">
                    <label class="form-label small">Observador</label>
                    <select
                        class="form-select form-select-sm bg-white text-dark @error('obs_observer_id') is-invalid @enderror"
                        wire:model="obs_observer_id" disabled>
                        <option value="{{ $obs_observer_id }}">
                            {{ \App\Models\User::find($obs_observer_id)?->name ?? 'Nenhum selecionado' }}
                        </option>
                    </select>
                    @error('obs_observer_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label small">Buscar alvo</label>
                    <input class="form-control form-control-sm bg-white text-dark" wire:model.debounce.300ms="obsTargetSearch"
                        placeholder="Nome ou e-mail do alvo...">
                </div>

                <div class="mb-2">
                    <label class="form-label small">Alvo observado</label>
                    <select class="form-select form-select-sm bg-white text-dark @error('obs_target_id') is-invalid @enderror"
                        wire:model="obs_target_id">
                        <option value="">— selecione o alvo —</option>
                        @foreach ($observationTargets as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                        @endforeach
                    </select>
                    @error('obs_target_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label small">Modo de observação</label>
                    <select class="form-select form-select-sm bg-white text-dark @error('obs_mode') is-invalid @enderror"
                        wire:model="obs_mode">
                        <option value="subtree">Subárvore (alvo + abaixo dele)</option>
                        <option value="node_only">Somente o alvo</option>
                    </select>
                    @error('obs_mode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-2">
                    <div class="col">
                        <label class="form-label small">Início</label>
                        <input type="date"
                            class="form-control form-control-sm bg-white text-dark @error('obs_from') is-invalid @enderror"
                            wire:model="obs_from">
                        @error('obs_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col">
                        <label class="form-label small">Fim (Opcional)</label>
                        <input type="date"
                            class="form-control form-control-sm bg-white text-dark @error('obs_to') is-invalid @enderror"
                            wire:model="obs_to">
                        @error('obs_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-2">
                    <label class="form-label small">Motivo</label>
                    <input class="form-control form-control-sm bg-white text-dark" wire:model="obs_reason"
                        placeholder="Ex: suporte temporário, cobertura de equipe...">
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-sm btn-info text-dark" wire:click="saveObservation" wire:loading.attr="disabled"
                    wire:target="saveObservation" @disabled(!$obs_observer_id || !$obs_target_id || !$obs_from)>
                    Salvar Observação
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===== Estilos (com SVG) ===== --}}
<style>
    /* Base */
    body {
        background-color: #f1f5f9;
    }

    /* Lista lateral */
    .user-list-item {
        cursor: pointer;
        transition: background-color .2s, border-color .2s, color .2s
    }

    .user-list-item:hover {
        background-color: var(--bs-primary) !important;
        border-color: var(--bs-primary) !important;
        color: #fff !important
    }

    .user-list-item:hover .small {
        color: rgba(255, 255, 255, .8) !important
    }

    .user-list-item.bg-primary {
        color: #fff !important
    }

    .user-list-item.bg-primary .small {
        color: rgba(255, 255, 255, .8) !important
    }

    /* Offcanvas */
    .offcanvas.bg-dark {
        background-color: #212529 !important;
        color: #fff
    }

    .offcanvas .offcanvas-header.bg-secondary,
    .offcanvas .card-header.bg-secondary,
    .offcanvas .card.bg-secondary {
        background-color: #343a40 !important;
        border-color: rgba(255, 255, 255, .1) !important
    }

    .offcanvas .form-control,
    .offcanvas .form-select {
        background-color: rgba(255, 255, 255, .1) !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, .2) !important
    }

    .offcanvas .form-control::placeholder {
        color: rgba(255, 255, 255, .6) !important
    }

    .offcanvas .form-select option {
        background-color: #343a40;
        color: #fff
    }

    .offcanvas .text-muted {
        color: rgba(255, 255, 255, .7) !important
    }

    .offcanvas .user-list-item.bg-dark-subtle {
        background-color: #495057 !important;
        border-color: #6c757d !important
    }

    .offcanvas .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%)
    }

    .offcanvas .pagination .page-link {
        background-color: #495057;
        color: #fff;
        border-color: #6c757d
    }

    .offcanvas .pagination .page-item.active .page-link,
    .offcanvas .pagination .page-link:hover {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary)
    }

    /* ===== ÁRVORE (estrutura) ===== */
    .tree {
        position: relative;
        text-align: center;
        overflow-x: auto;
        padding: 24px 12px
    }

    .tree ul {
        padding-top: 24px;
        position: relative;
        padding-left: 0;
        margin: 0;
        display: inline-block;
        list-style: none
    }

    .tree li>ul {
        display: flex;
        flex-wrap: nowrap;
        justify-content: center;
        align-items: flex-start;
        gap: 36px
    }

    .tree li>ul>li {
        list-style: none;
        display: block;
        position: relative;
        padding: 0;
        margin: 0;
        text-align: center;
        white-space: normal
    }

    /* Cartões */
    .node {
        padding: .6rem 1rem;
        border: 1px solid #ced4da;
        border-radius: .5rem;
        background-color: #fff;
        transition: all .2s ease-in-out;
        cursor: pointer;
        word-break: break-word;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-shadow: 0 .1rem .2rem rgba(0, 0, 0, .05);
        box-sizing: border-box;
        width: 260px;
        max-width: 260px;
        margin: 0;
    }

    .node:hover {
        border-color: var(--bs-primary);
        box-shadow: 0 .2rem .4rem rgba(13, 110, 253, .1)
    }

    .node[data-match="1"] {
        background-color: var(--bs-yellow-100);
        border-color: var(--bs-yellow-500);
        box-shadow: 0 .2rem .4rem rgba(255, 193, 7, .15)
    }

    .node-header {
        width: 100%;
        margin-bottom: .25rem;
        min-height: 1.5em;
        display: flex;
        justify-content: space-between;
        align-items: center
    }

    .node-body {
        text-align: center
    }

    .node-title {
        font-weight: 700;
        color: #343a40;
        line-height: 1.2;
        font-size: 1.05em
    }

    .node-subtitle {
        font-size: .8em;
        color: #6c757d
    }

    .node-child-actions {
        margin-top: .5rem;
        display: flex;
        gap: .25rem;
        justify-content: center
    }

    .node-child-actions .btn {
        --bs-btn-padding-y: .1rem;
        --bs-btn-padding-x: .4rem;
        --bs-btn-font-size: .75rem
    }

    .small-badge {
        font-size: .65em;
        padding: .2em .4em;
        line-height: 1
    }

    .node-primary-focus {
        background-color: var(--bs-primary-bg-subtle);
        border-color: var(--bs-primary);
        box-shadow: 0 .25rem .5rem rgba(13, 110, 253, .2)
    }

    .node-manager-above {
        background-color: var(--bs-secondary-bg-subtle);
        border-color: var(--bs-secondary)
    }

    .node-manager-above:hover {
        background-color: var(--bs-secondary);
        border-color: var(--bs-dark);
        color: #fff
    }

    .node-manager-above:hover .node-title,
    .node-manager-above:hover .node-subtitle {
        color: #fff
    }

    /* Delegações */
    .node-acting {
        border-color: #198754 !important;
        background-color: #eaf7ee !important;
        box-shadow: 0 .2rem .4rem rgba(25, 135, 84, .12) !important
    }

    .badge-delegacao {
        background-color: #198754;
        color: #fff
    }

    .chip-principal {
        display: inline-block;
        background: #fff3cd;
        border: 1px solid #ffc107;
        color: #8a6d3b;
        border-radius: 10rem;
        padding: 2px 8px;
        font-size: .72rem;
        line-height: 1.2;
    }

    .chip-delegate {
        display: inline-block;
        background: #d1ecf1;
        border: 1px solid #0dcaf0;
        color: #0a4d57;
        border-radius: 10rem;
        padding: 2px 8px;
        font-size: .72rem;
        line-height: 1.2;
    }

    .chip-with {
        display: inline-block;
        background: #e2e3e5;
        border: 1px solid #adb5bd;
        color: #495057;
        border-radius: 10rem;
        padding: 2px 8px;
        font-size: .72rem;
        line-height: 1.2;
    }

    /* Camada SVG */
    .org-lines {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        overflow: visible
    }

    .org-lines line,
    .org-lines path {
        stroke: #c7ced6;
        stroke-width: 1.5;
        fill: none;
        shape-rendering: crispEdges
    }

    /* Responsivo */
    @media (max-width: 991.98px) {
        .tree li>ul {
            gap: 24px
        }

        .node {
            width: 210px;
            max-width: 210px
        }
    }
</style>

{{-- Linhas (SVG) --}}
<script>
    (function() {
        const TREE_SEL = '#orgTree';

        function cx(el, ref) {
            const r = el.getBoundingClientRect(),
                rr = ref.getBoundingClientRect();
            return (r.left + r.right) / 2 - rr.left + ref.scrollLeft
        }

        function ty(el, ref) {
            const r = el.getBoundingClientRect(),
                rr = ref.getBoundingClientRect();
            return r.top - rr.top + ref.scrollTop
        }

        function by(el, ref) {
            const r = el.getBoundingClientRect(),
                rr = ref.getBoundingClientRect();
            return r.bottom - rr.top + ref.scrollTop
        }

        function line(svg, x1, y1, x2, y2) {
            const l = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            l.setAttribute('x1', x1);
            l.setAttribute('y1', y1);
            l.setAttribute('x2', x2);
            l.setAttribute('y2', y2);
            svg.appendChild(l)
        }

        function drawForParent(svg, ref, parentNodeEl) {
            const ul = parentNodeEl.nextElementSibling;
            if (!ul || ul.tagName !== 'UL') return;
            const childNodes = Array.from(ul.querySelectorAll(':scope > li > .node'));
            if (!childNodes.length) return;

            const xs = childNodes.map(n => cx(n, ref));
            const minX = Math.min(...xs),
                maxX = Math.max(...xs);
            const yRail = ty(childNodes[0], ref) - 14;

            line(svg, minX, yRail, maxX, yRail);
            childNodes.forEach((n, i) => line(svg, xs[i], yRail, xs[i], ty(n, ref) - 1));

            const px = cx(parentNodeEl, ref),
                py = by(parentNodeEl, ref) + 6;
            let targetX;
            const N = xs.length;
            if (N === 1) {
                targetX = xs[0]
            } else if (N === 2) {
                targetX = (xs[0] + xs[1]) / 2
            } else if (N % 2 === 1) {
                targetX = xs[(N - 1) / 2]
            } else {
                const ml = xs[N / 2 - 1],
                    mr = xs[N / 2];
                targetX = (ml + mr) / 2
            }

            line(svg, px, py, px, yRail);
            if (Math.abs(px - targetX) > 0.5) line(svg, Math.min(px, targetX), yRail, Math.max(px, targetX), yRail);
        }

        function drawAll() {
            const ref = document.querySelector(TREE_SEL);
            if (!ref) return;
            let svg = ref.querySelector('svg.org-lines');
            if (!svg) {
                svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.classList.add('org-lines');
                ref.insertBefore(svg, ref.firstChild)
            }
            while (svg.firstChild) svg.removeChild(svg.firstChild);
            ref.querySelectorAll('.node').forEach(p => drawForParent(svg, ref, p));
        }

        window.addEventListener('load', drawAll);
        window.addEventListener('resize', () => requestAnimationFrame(drawAll));
        document.addEventListener('DOMContentLoaded', () => {
            const ref = document.querySelector(TREE_SEL);
            if (ref) ref.addEventListener('scroll', () => requestAnimationFrame(drawAll));
        });
        document.addEventListener('livewire:load', () => {
            if (window.Livewire && Livewire.hook) {
                Livewire.hook('message.processed', () => requestAnimationFrame(drawAll));
            }
            requestAnimationFrame(drawAll);
        });

        const moStart = () => {
            const ref = document.querySelector(TREE_SEL);
            if (!ref) return;
            const obs = new MutationObserver(() => requestAnimationFrame(drawAll));
            obs.observe(ref, {
                childList: true,
                subtree: true,
                attributes: true
            });
        };
        document.addEventListener('DOMContentLoaded', moStart);
    })();
</script>

{{-- Bridge de eventos (modais/offcanvas/toast) --}}
<script>
    document.addEventListener('livewire:load', () => {
        const mvModalEl = document.getElementById('mvModal');
        const dlgDelegationEl = document.getElementById('dlgDelegation');
        const obsModalEl = document.getElementById('obsModal');

        const mvModal = new bootstrap.Modal(mvModalEl);
        const dlgDelegationModal = new bootstrap.Modal(dlgDelegationEl);
        const obsModal = new bootstrap.Modal(obsModalEl);

        const leftOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('leftOffcanvas'));
        const rightOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById(
            'rightOffcanvas'));

        function openMoveModal(e) {
            const strongEl = mvModalEl.querySelector('.modal-title strong');
            if (strongEl && e?.detail?.userName) strongEl.textContent = e.detail.userName;
            mvModal.show();
        }
        window.addEventListener('show-move-modal', openMoveModal);
        if (window.Livewire?.on) {
            Livewire.on('show-move-modal', (payload) => openMoveModal({
                detail: payload || {}
            }));
        }
        window.addEventListener('hide-move-modal', () => mvModal.hide());
        window.addEventListener('show-delegation-modal', () => dlgDelegationModal.show());
        window.addEventListener('hide-delegation-modal', () => dlgDelegationModal.hide());
        window.addEventListener('show-observation-modal', () => obsModal.show());
        window.addEventListener('hide-observation-modal', () => obsModal.hide());

        window.addEventListener('hide-left-offcanvas', () => leftOffcanvas.hide());
        window.addEventListener('hide-right-offcanvas', () => rightOffcanvas.hide());

        window.addEventListener('toast', e => {
            console.log(`[toast] Tipo: ${e.detail.type}, Mensagem: ${e.detail.msg}`);
        });
    });
</script>

</div>
