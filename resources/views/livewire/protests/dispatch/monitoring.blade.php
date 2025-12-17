@push('css')
    <style>
        .highlightable-row {
            transition: background-color .2s, box-shadow .2s;
            cursor: pointer;
        }

        .highlightable-row td {
            vertical-align: middle;
        }

        .highlightable-row.highlight-active {
            background-color: rgba(59, 130, 246, 0.08) !important;
            box-shadow: inset 0 0 0 999px rgba(59, 130, 246, 0.08);
        }

        .highlightable-row.highlight-active td {
            background-color: transparent !important;
        }
    </style>
@endpush

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
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <label for="perPageSelect">Registros por página</label>
                    </div>
                </div>

                <div class="col-12 col-md-7 col-lg-8">
                    <div class="form-floating position-relative">
                        <input wire:model.debounce.500ms="search" class="form-control border border-secondary"
                            id="searchInput" placeholder="Buscar por nota, cidade, responsável..." />
                        <label for="searchInput">Buscar por nota, cidade ou responsável</label>

                        <button type="button"
                            class="btn btn-outline-secondary position-absolute top-50 translate-middle-y me-2 border-0"
                            style="right: .35rem;" data-bs-toggle="modal" data-bs-target="#buscarMultiModal"
                            title="Busca múltipla">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-2 col-lg-2">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterTypeNote" wire:model="typeNote">
                            <option value="">Todos os tipos</option>

                            @forelse ($noteTypeOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @empty
                                <option value="" disabled>Nenhum tipo disponível</option>
                            @endforelse
                        </select>
                        <label for="filterTypeNote">Tipo de nota</label>
                    </div>
                </div>

            </div>

            <div class="row g-3 align-items-end mt-0 mt-md-3">
                <div class="col-12 col-lg-5">
                    <div class="form-floating">
                        <select class="form-select border border-secondary" id="filterUser"
                            wire:model.defer="userViewer">
                            <option value="">Todos os responsáveis</option>

                            @forelse ($userViewerList as $user)
                                <option value="{{ $user->id }}">
                                    {{ reduceName($user->name) }}
                                </option>
                            @empty
                                <option value="" disabled>Nenhum usuário encontrado</option>
                            @endforelse
                        </select>
                        <label for="filterUser">Responsável / hierarquia</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="form-floating">
                        <input type="text" class="form-control border border-secondary" id="searchName"
                            wire:model.debounce.300ms="searchName" placeholder="Filtrar lista de usuários">
                        <label for="searchName">Filtrar responsáveis</label>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="border rounded py-3 px-3 h-100">
                        <div class="form-check mb-1">
                            <input type="checkbox" class="form-check-input" id="onlySelectedUser"
                                wire:model.defer="onlySelectedUser" {{ empty($userViewer) ? 'disabled' : '' }}>
                            <label class="form-check-label" for="onlySelectedUser">
                                Apenas usuário selecionado
                            </label>
                        </div>
                        <small class="text-muted">Ignora descendentes na consulta.</small>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-secondary" wire:click="cleanFilters">
                        <i class="ri-eraser-line me-1"></i>
                        Limpar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="applyFilters">
                        <i class="ri-filter-3-line me-1"></i>
                        Aplicar
                    </button>
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
                        <th>Despachado Em</th>
                        <th>Status</th>
                        <th>Sla Definido</th>
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

                            $DueLeft = $item->sla_due_at;

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

                            if ($DueLeft) {
                                $dueDaysLeft = now()->diffInDays($DueLeft, false);

                                if ($dueDaysLeft < 0) {
                                    $dueSlaClass = 'text-bg-danger';
                                } elseif ($dueDaysLeft <= 3) {
                                    $dueSlaClass = 'text-bg-warning';
                                } else {
                                    $dueSlaClass = 'text-bg-success';
                                }
                            } else {
                                $dueDaysLeft = null;
                                $dueSlaClass = 'text-bg-secondary';
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

                        <tr class="text-center align-middle highlightable-row" data-row-id="{{ $item->id }}">
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
                                <span class="badge text-bg-secondary" title="Dias Aberto">
                                    {{ $aperture?->diffInDays(now(), true) }} d
                                </span>
                            </td>

                            <td>
                                {{ $wish ? $wish->format('d/m/Y') : '---' }}
                                @if ($slaLeft !== null)
                                    <span class="badge {{ $slaClass }}" title="Dias para a data desejada">
                                        {{ $slaLeft }} d
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">---</span>
                                @endif
                            </td>

                            <td>
                                {{ $item->sent_at ? $item->sent_at->format('d/m/Y') : '---' }}
                                <span class="badge text-bg-secondary" title="Dias Aberto">
                                    {{ $item->sent_at?->diffInDays(now(), true) }} d
                                </span>
                            </td>

                            <td>
                                <span class="badge {{ $item->statusBadgeClass }}">{{ $item->statusLabel }}</span>
                            </td>

                            <td class="fw-bold">
                                @if ($dueDaysLeft !== null)
                                    <span class="badge {{ $dueSlaClass }}" title="Dias para a data Sla">
                                        {{ $dueDaysLeft }} d
                                    </span>
                                @else
                                    <span class="badge text-bg-secondary">---</span>
                                @endif
                            </td>

                            <td>
                                @if ($item->medProtest?->id)
                                    @php
                                        $messageTitle = 'Abrir mensagens da Medida';

                                        if ($pendingForYou) {
                                            $messageTitle =
                                                'Última mensagem da Medida é de outro usuário, aguardando sua resposta';
                                        } elseif ($hasMessage) {
                                            $messageTitle = 'Última mensagem da Medida é sua (respondido por você)';
                                        }
                                    @endphp

                                    <button type="button"
                                        class="btn btn-link p-0 border-0 text-decoration-none align-middle"
                                        title="{{ $messageTitle }}"
                                        wire:click="$emitTo('protests.common.messages', 'openMessagesModal', {{ $item->medProtest->id }})">
                                        @if ($pendingForYou)
                                            <i class="ri-message-3-fill text-info"></i>
                                        @elseif ($hasMessage)
                                            <i class="ri-message-2-line text-muted"></i>
                                        @else
                                            <i class="ri-chat-1-line text-muted"></i>
                                        @endif
                                    </button>
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

    {{-- Modal de mensagens da Medida --}}
    @livewire('protests.common.messages', key('dispatch-messages-modal'))

    {{-- Modal de busca mÃºltipla (jÃ¡ existente em outro lugar) --}}
    @push('scripts')
        <script>
            document.addEventListener('livewire:load', () => {
                let currentHighlightedId = null;

                const applyHighlight = () => {
                    document.querySelectorAll('.highlightable-row').forEach(row => {
                        if (!row.dataset.rowId) {
                            return;
                        }
                        if (row.dataset.rowId === currentHighlightedId) {
                            row.classList.add('highlight-active');
                        } else {
                            row.classList.remove('highlight-active');
                        }
                    });
                };

                const highlightRow = (row) => {
                    if (!row || !row.dataset.rowId) {
                        return;
                    }
                    currentHighlightedId = row.dataset.rowId;
                    applyHighlight();
                };

                document.addEventListener('click', (event) => {
                    const row = event.target.closest('.highlightable-row');
                    if (row) {
                        highlightRow(row);
                    }
                });

                Livewire.hook('message.processed', (message, component) => {
                    if (component.fingerprint.name === 'protests.dispatch.monitoring') {
                        applyHighlight();
                    }
                });
            });
        </script>
    @endpush
</div>
