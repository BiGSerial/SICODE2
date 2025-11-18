@php
    if (!function_exists('reduceName')) {
        function reduceName(string $name, bool $first = false)
        {
            $name = explode(' ', trim($name));

            if ($first) {
                return $name[0] ?? '';
            }

            $firstName = $name[0] ?? '';
            $lastName = count($name) > 1 ? end($name) : '';

            return trim($firstName . ' ' . $lastName);
        }
    }

    if (!function_exists('getWishDate')) {
        function getWishDate($item)
        {
            if ($item->protest?->tipoNota === 'NA') {
                return $item->protest?->dtConclusaoDesej;
            }

            return $item->medProtest?->dtFimMedidaDesej;
        }
    }

    if (!function_exists('getApertureDate')) {
        function getApertureDate($item)
        {
            if ($item->protest?->tipoNota === 'NA') {
                return $item->protest?->dtAberturaNota;
            }

            return $item->medProtest?->dtCriacaoMedida;
        }
    }
@endphp

<div>
    {{-- Loading --}}
    <x-show-loading />

    {{-- ================== TOP: BUSCA E FILTROS ================== --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            {{-- Linha 1: perPage + busca geral --}}
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-4 col-md-2">
                    <div class="form-floating w-100">
                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label for="perPageSelect">Registros</label>
                    </div>
                </div>

                <div class="col-12 col-sm-8 col-md-7">
                    <div class="form-floating w-100 position-relative">
                        <input wire:model.debounce.500ms="search" class="form-control border border-secondary"
                            id="searchInput" placeholder="Buscar por nota, cidade, responsável..." />
                        <label for="searchInput">Buscar por nota, cidade, responsável</label>

                        <button type="button"
                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2 border-0"
                            data-bs-toggle="modal" data-bs-target="#buscarMultiModal" title="Busca múltipla">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-md-3 d-flex justify-content-md-end gap-2">
                    <button type="button" class="btn btn-primary" wire:click="applyFilters">
                        <i class="ri-filter-3-line me-1"></i>
                        Aplicar
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="cleanFilters">
                        <i class="ri-eraser-line me-1"></i>
                        Limpar
                    </button>
                </div>
            </div>

            <hr class="my-3">

            {{-- Linha 2: filtro por usuario / hierarquia --}}
            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-4">
                    <div class="form-floating">
                        <input type="text" class="form-control border border-secondary" id="searchName"
                            wire:model.debounce.300ms="searchName" placeholder="Filtrar lista de usuarios">
                        <label for="searchName">Filtrar lista de usuarios</label>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <label for="filterUser" class="form-label small mb-1">Usuario responsavel / hierarquia</label>
                    <select class="form-select border border-secondary" id="filterUser" wire:model.defer="userViewer">
                        <option value="">Todos</option>

                        @forelse ($userViewerList as $user)
                            <option value="{{ $user->id }}">
                                {{ reduceName($user->name) }}
                            </option>
                        @empty
                            <option value="" disabled>Nenhum usuario encontrado</option>
                        @endforelse
                    </select>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterTypeNote" wire:model="typeNote">
                            <option value="">Todos os tipos</option>

                            @forelse ($noteTypeOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @empty
                                <option value="" disabled>Nenhum tipo disponivel</option>
                            @endforelse
                        </select>
                        <label for="filterTypeNote">Tipo de nota</label>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-lg-8">
                    <small class="text-muted d-block">
                        Selecione um usuario para ver apenas os jobs da sua hierarquia (descendentes).
                    </small>
                </div>
                <div class="col-12 col-lg-4">
                    <small class="text-muted d-block">
                        Tipos listados a partir dos valores unicos em Protest->tipoNota.
                    </small>
                </div>
            </div>

        </div>
    </div>

    @php
        $total = $stats['total'] ?? 0;

        $overdue = $stats['overdue'] ?? 0;
        $overdue_pct = $stats['overdue_pct'] ?? 0;
        $dueSoon = $stats['dueSoon'] ?? 0;
        $dueSoon_pct = $stats['dueSoon_pct'] ?? 0;
        $within = $stats['within'] ?? 0;
        $within_pct = $stats['within_pct'] ?? 0;

        $msgResponded = $stats['responded_messages'] ?? 0;
        $msgPending = $stats['pending_messages_for_you'] ?? 0;
    @endphp

    {{-- ================== CARDS DE SLA ================== --}}
    <div class="row g-3 mb-3">
        {{-- Total em andamento (zera filtro SLA) --}}
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100 {{ $slaFilter === null ? 'border-primary border-2' : '' }}"
                style="cursor:pointer" wire:click="setSlaFilter(null)">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Total em andamento</span>
                        <i class="ri-list-check-2 fs-4 text-primary"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <h3 class="fw-bold mb-0">{{ $total }}</h3>
                        <span class="badge bg-light text-muted">100%</span>
                    </div>
                    <small class="text-muted mt-2">
                        Reclamacoes abertas considerando os filtros e os prazos desejados.
                    </small>
                </div>
            </div>
        </div>

        {{-- SLA vencidos --}}
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100 {{ $slaFilter === 'overdue' ? 'border-danger border-2' : '' }}"
                style="cursor:pointer" wire:click="setSlaFilter('overdue')">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Prazo vencido</span>
                        <i class="ri-timer-off-line fs-4 text-danger"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <h3 class="fw-bold mb-0 text-danger">{{ $overdue }}</h3>
                        <span class="badge text-bg-danger">{{ $overdue_pct }}%</span>
                    </div>
                    <small class="text-muted">
                        Atividades com a data desejada vencida.
                    </small>
                </div>
            </div>
        </div>

        {{-- Vencendo em ate 3 dias --}}
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100 {{ $slaFilter === 'dueSoon' ? 'border-warning border-2' : '' }}"
                style="cursor:pointer" wire:click="setSlaFilter('dueSoon')">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Vencendo em 3 dias</span>
                        <i class="ri-timer-line fs-4 text-warning"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <h3 class="fw-bold mb-0 text-warning">{{ $dueSoon }}</h3>
                        <span class="badge text-bg-warning">{{ $dueSoon_pct }}%</span>
                    </div>
                    <small class="text-muted">
                        Itens com data desejada em ate 3 dias.
                    </small>
                </div>
            </div>
        </div>

        {{-- Dentro do prazo (SLA saudavel) --}}
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100 {{ $slaFilter === 'within' ? 'border-success border-2' : '' }}"
                style="cursor:pointer" wire:click="setSlaFilter('within')">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Dentro do prazo</span>
                        <i class="ri-checkbox-circle-line fs-4 text-success"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <h3 class="fw-bold mb-0 text-success">{{ $within }}</h3>
                        <span class="badge text-bg-success">{{ $within_pct }}%</span>
                    </div>
                    <small class="text-muted">
                        Jobs com data desejada acima de 3 dias.
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== CARDS DE MENSAGENS ================== --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Mensagens</span>
                        <i class="ri-message-3-line fs-4 text-info"></i>
                    </div>

                    <div class="mb-2">
                        <div class="text-muted small">Jobs já respondidos</div>
                        <h4 class="fw-bold mb-0 text-info">{{ $msgResponded }}</h4>
                    </div>

                    <div class="mt-auto">
                        <small class="text-muted d-block">
                            Pendentes para você:
                            <span class="fw-bold {{ $msgPending > 0 ? 'text-danger' : '' }}">
                                {{ $msgPending }}
                            </span>
                        </small>
                        <small class="text-muted">
                            (Considerando a última mensagem da Medida.)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== LISTA PRINCIPAL ================== --}}
    @if ($lists->count())
        {{-- PaginaÃ§Ã£o topo --}}
        <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
            {{ $lists->links() }}
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                {{ $lists->total() }} registros
            </div>
        </div>

        <div class="card">
            <div class="card-header py-0 text-bg-danger d-flex justify-content-between align-items-center">
                <h5 class="card-title my-0">RECLAMAÇÕES EM ANDAMENTO</h5>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-light" title="Exportar para Excel"
                        wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel">
                        <span wire:loading.remove wire:target="exportToExcel">
                            <i class="ri-file-excel-line me-1"></i>
                            Exportar Excel
                        </span>
                        <span wire:loading wire:target="exportToExcel">
                            <i class="spinner-border spinner-border-sm me-1" role="status"></i>
                            Exportando...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Tabela --}}
            <table class="table table-sm table-striped table-condensed mb-0">
                <thead class="table-dark">
                    <tr class="align-middle text-center sticky-top" style="top: 60px;">
                        <th>Prioridade</th>
                        <th>Despachante</th>
                        <th>Tipo</th>
                        <th></th>
                        <th>Nota</th>
                        <th>Medida</th>
                        <th>Cód</th>
                        <th>Tipo Reclamação</th>
                        <th>Municí­pio</th>
                        <th>Responsável</th>
                        <th>Empresa</th>
                        <th>Abertura</th>
                        <th>Fim desejado</th>
                        <th>Status</th>
                        <th>Prazo (dias)</th>
                        <th>
                            <i class="ri-message-3-line" title="Mensagens na Medida"></i>
                        </th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($lists as $item)
                        @php
                            // Prazo desejado
                            $wish = getWishDate($item);

                            if ($wish) {
                                $slaLeft = now()->diffInDays($wish, false);

                                if ($slaLeft < 0) {
                                    $slaClass = 'text-bg-danger';
                                } elseif ($slaLeft <= 3) {
                                    $slaClass = 'text-bg-warning';
                                } else {
                                    $slaClass = 'text-bg-success';
                                }
                            } else {
                                $slaLeft = null;
                                $slaClass = 'text-bg-secondary';
                            }

                            // Mensagens (Ãºltima da MedProtest)
                            $currentUserId = auth()->id();
                            $creatorId = $item->created_by ?? ($item->creator_id ?? optional($item->creator)->id);
                            $lastComment = $item->medProtest?->Comments?->first();

                            $hasMessage = false;
                            $pendingForYou = false;

                            if ($creatorId && $lastComment) {
                                $authorId = $lastComment->user_id;

                                if ($authorId) {
                                    $isFromDispatcher = $authorId === $creatorId;
                                    $isFromCurrentUser = $currentUserId && $authorId === $currentUserId;

                                    $hasMessage = !$isFromDispatcher;
                                    $pendingForYou = $hasMessage && !$isFromCurrentUser;
                                }
                            }
                        @endphp

                        <tr class="text-center">
                            <td>
                                <span class="badge {{ $item->priority_badge_class }}">
                                    {{ $item->priority_label }}
                                </span>
                            </td>

                            <td class="fw-bold">
                                {{ reduceName($item->creator?->name) }}
                            </td>

                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $item->protest?->tipoNota }}
                                </span>
                            </td>

                            <td>
                                @if ($item->is_advance)
                                    <span class="badge text-bg-info">A</span>
                                @endif
                            </td>

                            <td class="fw-bold">
                                {{ $item->protest?->nota }}
                            </td>

                            <td class="fw-bold">
                                # {{ $item->medProtest?->med_id }}
                            </td>

                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $item->protest?->codecodf }}
                                </span>
                            </td>

                            <td class="text-uppercase">
                                {{ $item->protest?->txtGrpCodificacao }}
                            </td>

                            <td>
                                {{ $item->protest?->cidade }}
                            </td>

                            <td class="text-uppercase fw-bold">
                                {{ reduceName($item->owner?->name) }}
                            </td>

                            <td class="text-uppercase">
                                {{ reduceName($item->owner?->company?->name, true) }}
                            </td>

                            <td>
                                @php $aperture = getApertureDate($item); @endphp
                                {{ $aperture ? $aperture->format('d/m/Y') : '---' }}
                            </td>

                            <td>
                                {{ $wish ? $wish->format('d/m/Y') : '---' }}
                            </td>

                            <td>
                                <span class="badge {{ $item->statusBadgeClass }}">{{ $item->statusLabel }}</span>
                            </td>

                            <td class="fw-bold">
                                @if ($slaLeft !== null)
                                    <span class="badge {{ $slaClass }}" title="Dias para a data desejada">
                                        {{ $slaLeft }} d
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">---</span>
                                @endif
                            </td>

                            <td>
                                @if ($pendingForYou)
                                    <i class="ri-message-3-fill text-info"
                                        title="Última mensagem da Medida é de outro usuário, aguardando sua resposta"></i>
                                @elseif ($hasMessage)
                                    <i class="ri-message-2-line text-muted"
                                        title="Última mensagem da Medida é sua (respondido por você)"></i>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td style="width:48px;">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Visualizar"
                                        wire:click="$emitTo('protests.dispatch.actions.view-protest-job', 'open', {{ $item->id }})">
                                        <i class="ri-eye-line"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        wire:click="goTo({{ $item->protest?->nota }})" title="Seguir">
                                        <i class="ri-bookmark-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- PaginaÃ§Ã£o base --}}
        <div class="d-flex justify-content-between align-items-center mt-2">
            {{ $lists->links() }}
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de
                {{ $lists->total() }} registros
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center">
                <p class="mb-0">Não há registros para exibir com os filtros atuais.</p>
            </div>
        </div>
    @endif

    {{-- Drawer lateral de detalhes --}}
    @livewire('protests.dispatch.actions.view-protest-job', key('view-protest-job'))

    {{-- Modal de busca mÃºltipla (jÃ¡ existente em outro lugar) --}}
</div>
