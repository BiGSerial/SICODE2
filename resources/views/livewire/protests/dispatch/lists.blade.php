@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;
    use App\Enum\ProtestJobStatus;
@endphp

<div>
    {{-- Loading --}}
    <x-show-loading />

    {{-- Top Controls --}}
    <div class="d-flex flex-wrap gap-3 mb-3 align-items-center">
        <div class="flex-grow-1 position-relative">
            <input wire:model.debounce.500ms="search" class="form-control" id="searchInput" placeholder="Buscar..." />
            <button type="button"
                class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2 border-0"
                data-bs-toggle="modal" data-bs-target="#buscarMultiModal" title="Busca mÃºltipla">
                <i class="ri-checkbox-multiple-blank-line"></i>
            </button>
        </div>

        <select class="form-select w-auto" wire:model="perPage">
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>

        @livewire('components.filter.filter', ['myKey' => 'regiao', 'sendFilter' => 'regional', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'protests', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('regiao'))
        @livewire('components.filter.filter', ['myKey' => 'regional', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regional', 'filter' => 'Regional', 'group_filter' => 'protests', 'values' => 'regional', 'direction' => 'ASC', 'query' => ''], key('regional'))
        @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'protests', 'values' => 'cidade', 'direction' => 'ASC', 'query' => ''], key('city'))
        @livewire('components.filter.remove-all', ['group_filter' => 'protests'], key('removeAll'))

        <div class="d-flex gap-2">
            <div class="form-group">
                <label for="protestTypeFilter" class="form-label small mb-1">Tipo de Protesto</label>
                <select id="protestTypeFilter" class="form-select form-select-sm" wire:model="selectedProtestType"
                    style="min-width: 180px;">
                    <option value="">Todos</option>
                    @foreach ($protest_Types as $type)
                        <option value="{{ $type->protest_type }}">{{ $type->protest_type_label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="tipoNotaFilter" class="form-label small mb-1">Tipo de Nota</label>
                <select id="tipoNotaFilter" class="form-select form-select-sm" wire:model="selectedTipoNota"
                    style="min-width: 150px;">
                    <option value="">Todos</option>
                    @foreach ($tipoNotas as $tipo)
                        <option value="{{ $tipo->tipoNota }}">{{ $tipo->tipoNota }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Cards resumo por status --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-6">
            <button type="button"
                class="status-summary-card status-summary-card--warning {{ $statusCardFilter === 'due_today' ? 'is-active' : '' }}"
                wire:click="setStatusCardFilter('due_today')">
                <div class="status-summary-icon">
                    <i class="ri-timer-2-line"></i>
                </div>
                <div class="status-summary-body">
                    <span class="status-summary-label">Vencendo hoje</span>
                    <span class="status-summary-value">{{ $dueTodayCount }}</span>
                    <small>dtFimMedidaDesej = hoje</small>
                </div>
            </button>
        </div>
        <div class="col-md-6 col-lg-6">
            <button type="button"
                class="status-summary-card status-summary-card--danger {{ $statusCardFilter === 'overdue' ? 'is-active' : '' }}"
                wire:click="setStatusCardFilter('overdue')">
                <div class="status-summary-icon">
                    <i class="ri-error-warning-line"></i>
                </div>
                <div class="status-summary-body">
                    <span class="status-summary-label">Vencidos</span>
                    <span class="status-summary-value">{{ $overdueCount }}</span>
                    <small>dtFimMedidaDesej < hoje</small>
                </div>
            </button>
        </div>
    </div>

    {{-- Header da tabela / aÃ§Ãµes --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0 text-uppercase d-flex align-items-center gap-2">
            <i class="ri-alert-line"></i>
            Reclamações
        </h5>

        <button wire:click="exportToExcel" class="btn btn-success btn-sm">
            <i class="ri-file-excel-2-line me-1"></i>
            Exportar Excel
        </button>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
        {{ $lists->links() }}
        <div class="text-muted small">
            Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
            registros
        </div>
    </div>
    {{-- Tabela compacta --}}
    <div class="table-responsive bg-white shadow-sm rounded">
        <table class="table table-sm table-hover modern-table align-middle mb-0">
            <thead class="table-dark">
                <tr class="align-middle text-center">
                    {{-- <th style="width:15px;">#M</th> --}}
                    <th style="width:15px;">M</th>
                    <th>Nota</th>
                    <th>Tipo</th>
                    <th>Cod</th>
                    <th>TipoReclamação</th>
                    <th>TxCodeMedida</th>
                    <th>CausaRaiz</th>
                    <th>Origem</th>
                    <th>Município</th>
                    <th>Abertura Reclamação</th>
                    <th>Abertura Medida</th>
                    <th>Tempo Medida</th>
                    <th>Desejada</th>
                    <th>Status Resposta</th>
                    <th style="width:48px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lists as $protest)
                    @php
                        $activeMed =
                            $protest->medProtests->sortByDesc('dtCriacaoMedida')->firstWhere('statusSist', 'MEDA') ??
                            $protest->medProtests->sortByDesc('dtCriacaoMedida')->first();
                        $startDate = $protest->dtAberturaNota;
                        $startMedDate = optional($activeMed)->dtCriacaoMedida;

                        $deadline =
                            $protest->tipoNota === 'NA'
                                ? $protest->dtConclusaoDesej
                                : optional($activeMed)->dtFimMedidaDesej;

                        $elapsed = $startDate
                            ? Carbon::parse($startDate)
                                ->startOfDay()
                                ->diffInDays(now()->startOfDay())
                            : '—';
                        $elapsedMed = $startMedDate
                            ? Carbon::parse($startMedDate)
                                ->startOfDay()
                                ->diffInDays(now()->startOfDay())
                            : '—';

                        $deadlineBadge = [
                            'label' => 'Sem prazo',
                            'class' => 'badge bg-secondary-subtle text-secondary',
                            'date' => '—',
                        ];

                        if ($deadline) {
                            $deadlineDate = Carbon::parse($deadline);
                            $deadlineBadge['date'] = $deadlineDate->format('d/m/Y');

                            if ($deadlineDate->endOfDay()->isPast()) {
                                $deadlineBadge['label'] = 'Vencido';
                                $deadlineBadge['class'] = 'badge text-bg-danger bg-opacity-70';
                            } elseif ($deadlineDate->diffInDays() <= 2) {
                                $deadlineBadge['label'] = 'Vencendo';
                                $deadlineBadge['class'] = 'badge  text-bg-warning bg-opacity-50';
                            } else {
                                $deadlineBadge['label'] = 'No prazo';
                                $deadlineBadge['class'] = 'badge  text-bg-success bg-opacity-70';
                            }
                        }

                        $latestJob = $activeMed?->ProtestJobs->first();
                        $jobStatusLabel = 'Sem Job';
                        $jobStatusClass = 'badge bg-secondary-subtle text-secondary';

                        if ($latestJob) {
                            $statusValue = $latestJob->status;
                            $enum =
                                $statusValue instanceof ProtestJobStatus
                                    ? $statusValue
                                    : ProtestJobStatus::tryFrom((string) $statusValue);

                            $jobStatusLabel = $enum ? $enum->label() : Str::headline((string) $statusValue);

                            $jobStatusClass = match ($enum?->value ?? (string) $statusValue) {
                                'done' => 'badge bg-success-subtle text-success',
                                'waiting' => 'badge bg-dark-subtle text-dark',
                                'in_progress' => 'badge bg-warning-subtle text-warning',
                                'canceled' => 'badge bg-danger-subtle text-danger',
                                default => 'badge bg-primary-subtle text-primary',
                            };
                        }
                    @endphp
                    <tr wire:key="list-{{ $protest->id }}"
                        ondblclick="window.location.href='{{ route('protests.dispatch.view', ['protest' => $protest->nota]) }}'"
                        class="align-middle text-center">
                        {{-- <td>{{ $protest->medProtests->count() }}</td> --}}
                        <td>{{ $activeMed?->med_id ?? '—' }}</td>
                        <td class="fw-semibold">{{ $protest->nota }}</td>
                        <td>{{ $protest->tipoNota }}</td>
                        <td>{{ $activeMed?->codMedida ?? '—' }}</td>
                        <td>{{ $activeMed?->txtCodMedida ?? '—' }}</td>
                        <td class="small">{{ $activeMed?->txtCodCodificacao ?? '—' }}</td>
                        <td class="small">{{ Str::limit($protest->descCausa ?? '—', 22) }}</td>
                        <td class="small">{{ Str::limit($protest->descricao ?? '—', 22) }}</td>
                        <td class="small">{{ $protest->cidade ?? '—' }}</td>
                        <td>{{ optional($startDate)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ optional($startMedDate)->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <div class="d-flex flex-column lh-1">
                                <span class="fw-semibold badge text-bg-secondary">{{ $elapsedMed }} d</span>
                                @if ($startMedDate)
                                    <small
                                        class="text-muted">{{ $startMedDate->diffForHumans(['short' => true]) }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="{{ $deadlineBadge['class'] }}">
                                {{ $deadlineBadge['date'] }} · {{ $deadlineBadge['label'] }}
                            </span>
                        </td>
                        <td>
                            <span class="{{ $jobStatusClass }}">{{ $jobStatusLabel }}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" type="button"
                                            wire:click.prevent="$emitTo('protests.dispatch.actions.control-med-protest', 'openModProtestControl', {{ $activeMed->id }})">
                                            <i class="ri-send-plane-line me-1"></i> Gerenciar / Criar atividade
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button"
                                            wire:click="confirmAutoDemand({{ $activeMed->id }})">
                                            <i class="ri-robot-line me-1"></i> Auto demanda
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" type="button"
                                            wire:click="goTo({{ $protest->nota }})">
                                            <i class="ri-external-link-line me-1"></i> Abrir protesto
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="text-center py-4 text-muted">
                            Nenhuma reclamação encontrada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PaginaÃ§Ã£o --}}
    <div class="d-flex justify-content-between align-items-center mt-2">
        {{ $lists->links() }}
        <div class="text-muted small">
            Exibindo {{ $lists->firstItem() ?? 0 }} - {{ $lists->lastItem() ?? 0 }} de {{ $lists->total() }}
            registros
        </div>
    </div>

    {{-- Drawer lateral de detalhes --}}
    @if ($showDetails && $selected)
        <div class="details-drawer details-drawer--modern shadow">
            <!-- Header -->
            <div class="drawer-header">
                <div class="drawer-title">
                    <div class="drawer-icon">
                        <i class="ri-file-list-3-line"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">Nota #{{ $selected->nota }}</h5>
                        <small class="text-muted">Ficha detalhada</small>
                    </div>
                </div>

                <button class="btn btn-light btn-sm drawer-close" wire:click="closeDetails" aria-label="Fechar">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <!-- Status Strip -->
            <div class="drawer-strip">
                <span
                    class="badge rounded-pill bg-{{ !$selected->dtConclusaoDesej->isPast() ? 'success' : 'danger' }} me-2">
                    <i
                        class="{{ !$selected->dtConclusaoDesej->isPast() ? 'ri-check-line' : 'ri-error-warning-line' }} me-1"></i>
                    {{ !$selected->dtConclusaoDesej->isPast() ? 'No Prazo' : 'Vencido' }}
                </span>

                <div class="chip">
                    <i class="ri-community-line me-1"></i>{{ $selected->cidade }}
                </div>
                <div class="chip">
                    <i class="ri-price-tag-3-line me-1"></i>{{ $selected->txtGrpCodificacao }}
                </div>
            </div>

            <!-- Content (scrollable) -->
            <div class="drawer-content">
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label"><i class="ri-map-pin-line me-1"></i>Município</div>
                        <div class="info-value">{{ $selected->cidade }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="ri-folder-2-line me-1"></i>Grupo</div>
                        <div class="info-value">{{ $selected->txtGrpCodificacao }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="ri-time-line me-1"></i>Abertura</div>
                        <div class="info-value">{{ $selected->dtAberturaNota?->format('d/m/Y') }}</div>
                    </div>
                    <div class="info-card">
                        <div class="info-label"><i class="ri-flag-line me-1"></i>Desejada</div>
                        <div class="info-value">{{ $selected->dtConclusaoDesej?->format('d/m/Y') }}</div>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="desc-block">
                    <div class="desc-title">
                        <i class="ri-information-line me-2"></i>Descrição
                    </div>
                    <p class="mb-0 text-secondary">
                        {{ $selected->comments->last()?->message }}
                    </p>
                </div>

                {{-- Timeline opcional (sÃ³ exibe se tiver datas) --}}
                {{-- @php
                    $timeline = [
                        [
                            'icon' => 'ri-file-add-line',
                            'label' => 'Abertura',
                            'date' => $selected->dtAberturaNota?->format('d/m/Y'),
                        ],
                        [
                            'icon' => 'ri-flag-2-line',
                            'label' => 'Desejada',
                            'date' => $selected->dtConclusaoDesej?->format('d/m/Y'),
                        ],
                    ];
                @endphp
                <div class="divider"></div>
                <div class="timeline">
                    @foreach ($timeline as $t)
                        @if (!empty($t['date']))
                            <div class="timeline-item">
                                <div class="timeline-dot"><i class="{{ $t['icon'] }}"></i></div>
                                <div class="timeline-content">
                                    <div class="timeline-label">{{ $t['label'] }}</div>
                                    <div class="timeline-date">{{ $t['date'] }}</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div> --}}

                @if ($selected->medProtests->isNotEmpty())
                    <div class="divider"></div>
                    <h6 class="mb-3"><i class="ri-shield-check-line me-2"></i>Medidas</h6>
                    <table class="table table-sm table-condensed table-striped">
                        <tr class='text-center'>
                            <th>Status</th>
                            <th>Dt Abertura</th>
                            <th>Desejada</th>
                            <th>Responsável</th>
                            <th>Enviado Em</th>
                        </tr>
                        @foreach ($selected->medProtests as $medida)
                            <tr class="text-center align-middle">
                                <td
                                    class='@if ($medida->statusSist == 'MEDE') text-bg-secondary
                                    @elseif($medida->statusSist == 'MEDA') text-bg-success @endif'>
                                    {{ $medida->statusSist }}</td>
                                <td>{{ $medida->dtCriacaoMedida?->format('d/m/Y') }}</td>
                                <td>{{ $medida->dtFimMedidaDesej?->format('d/m/Y') }}</td>
                                <td>{{ $medida->assignments?->where('user', true)->first()?->User?->name ?? '---' }}
                                </td>
                                <td>{{ $medida->assignments?->where('user', true)->first()?->created_at?->format('d/m/Y H:i') ?? '---' }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <div class="alert alert-info mt-3">
                        <i class="ri-information-line me-1"></i> Nenhuma medida direcionada.
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="drawer-footer">
                <button class="btn btn-outline-secondary" wire:click="closeDetails">
                    <i class="ri-arrow-go-back-line me-1"></i> Fechar
                </button>
                <button class="btn btn-primary" wire:click="goTo({{ $selected->nota }})">
                    <i class="ri-external-link-line me-1"></i> Abrir Detalhes
                </button>
            </div>
        </div>
        <div class="details-drawer-backdrop" wire:click="closeDetails"></div>
    @endif


    {{-- Modal: Busca MÃºltipla --}}
    <div wire:ignore.self class="modal fade" id="buscarMultiModal" tabindex="-1" aria-labelledby="buscarMultiLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="buscarMultiLabel">
                        <i class="ri-search-2-line me-2"></i>
                        Busca Multiplas de Notas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating">
                        <textarea class="form-control" id="advanceSearch" style="height: 200px;"
                            placeholder="Cole aqui vários valores (vírgula ou quebra de linha)" wire:model.defer="advanceSearch"></textarea>
                        <label for="advanceSearch">Números / valores</label>
                    </div>
                    <div class="form-text">
                        Separe por vírgula <strong>,</strong> ou por quebra de linha.
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" wire:click="buscarMulti" data-bs-dismiss="modal">
                        <i class="ri-check-line me-1"></i>Aplicar Filtro
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Components Livewire --}}
    @livewire('protests.dispatch.actions.control-med-protest', key('control-med-protest'))

</div>

<style>
    .status-summary-card {
        border: none;
        border-radius: 16px;
        padding: 1rem 1.2rem;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all .2s ease;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        cursor: pointer;
        position: relative;
        background: #fff;
    }

    .status-summary-card .status-summary-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        font-size: 1.5rem;
        background: rgba(255, 255, 255, 0.25);
    }

    .status-summary-card .status-summary-label {
        font-size: .9rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-weight: 600;
        display: block;
    }

    .status-summary-card .status-summary-value {
        font-size: 1.9rem;
        font-weight: 700;
        line-height: 1;
        display: block;
    }

    .status-summary-card small {
        font-size: .78rem;
        opacity: .8;
    }

    .status-summary-card.is-active {
        transform: translateY(-4px);
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.18);
    }

    .status-summary-card--warning {
        background: linear-gradient(135deg, #fff7e6, #ffe3b3);
        color: #7a4d00;
    }

    .status-summary-card--danger {
        background: linear-gradient(135deg, #ffe4e6, #ffb3c0);
        color: #7c1d2c;
    }

    .status-summary-card--success {
        background: linear-gradient(135deg, #e1f6ea, #a7e3c6);
        color: #0f5132;
    }

    .status-summary-card--warning .status-summary-icon {
        color: #a35d00;
        background: rgba(255, 255, 255, 0.6);
    }

    .status-summary-card--danger .status-summary-icon {
        color: #b4233b;
        background: rgba(255, 255, 255, 0.6);
    }

    .status-summary-card--success .status-summary-icon {
        color: #198754;
        background: rgba(255, 255, 255, 0.6);
    }

    .modern-table th,
    .modern-table td {
        font-size: 0.98em;
        vertical-align: middle;
        padding: .40em .75em !important;
    }

    .modern-table .badge {
        font-size: 1em;
        padding: .36em 1.2em;
        letter-spacing: .03em;
    }

    .details-drawer {
        position: fixed;
        top: 0;
        right: 0;
        height: 100vh;
        width: 400px;
        background: #fff;
        border-left: 1px solid #eee;
        z-index: 1201;
        padding: 2rem 1.5rem 1rem 2rem;
        box-shadow: -2px 0 18px rgba(0, 0, 0, 0.10);
        animation: slideInDrawer .21s cubic-bezier(.6, -0.28, .74, .05);
    }

    /* --- Drawer Moderno --- */
    .details-drawer--modern {
        background: #ffffff;
        border-left: 0;
        width: 460px;
        padding: 0;
        overflow: hidden;
        border-radius: 16px 0 0 16px;
        box-shadow: -8px 0 28px rgba(0, 0, 0, .12);
        backdrop-filter: saturate(1.2) blur(6px);
    }

    @media (max-width: 900px) {
        .details-drawer--modern {
            width: 100vw;
            border-radius: 0;
        }
    }

    /* Header com gradiente e blur */
    .details-drawer--modern .drawer-header {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(135deg, #0d6efd 0%, #4f8cff 100%);
        color: #fff;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 4px 16px rgba(13, 110, 253, .2);
    }

    .details-drawer--modern .drawer-title {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .details-drawer--modern .drawer-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .15);
        display: grid;
        place-items: center;
        font-size: 1.2rem;
    }

    /* BotÃ£o fechar */
    .details-drawer--modern .drawer-close {
        background: rgba(255, 255, 255, .15);
        border: 0;
        color: #fff;
        transition: transform .15s ease, background .15s ease;
    }

    .details-drawer--modern .drawer-close:hover {
        transform: rotate(90deg) scale(1.05);
        background: rgba(255, 255, 255, .25);
    }

    /* Faixa de status + chips */
    .details-drawer--modern .drawer-strip {
        padding: .75rem 1.25rem;
        background: linear-gradient(180deg, rgba(13, 110, 253, .06), rgba(13, 110, 253, 0));
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .details-drawer--modern .chip {
        font-size: .82rem;
        background: #f1f5ff;
        color: #2752d3;
        border: 1px solid #e3ebff;
        padding: .25rem .6rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
    }

    /* ConteÃºdo rolÃ¡vel */
    .details-drawer--modern .drawer-content {
        height: calc(100vh - 176px);
        /* header + strip + footer */
        overflow-y: auto;
        padding: 1.25rem 1.25rem 1rem;
        scrollbar-width: thin;
        scrollbar-color: #b8c9ff transparent;
    }

    .details-drawer--modern .drawer-content::-webkit-scrollbar {
        width: 6px;
    }

    .details-drawer--modern .drawer-content::-webkit-scrollbar-thumb {
        background: #b8c9ff;
        border-radius: 3px;
    }

    /* Grid de infos */
    .details-drawer--modern .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    @media (max-width: 480px) {
        .details-drawer--modern .info-grid {
            grid-template-columns: 1fr;
        }
    }

    .details-drawer--modern .info-card {
        border: 1px solid #eef1f6;
        border-radius: 12px;
        padding: .75rem .9rem;
        background: #fff;
        transition: box-shadow .15s ease, transform .15s ease;
    }

    .details-drawer--modern .info-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
    }

    .details-drawer--modern .info-label {
        font-size: .78rem;
        color: #6b7a90;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .details-drawer--modern .info-value {
        font-weight: 600;
        color: #2a2f3a;
        margin-top: .15rem;
    }

    /* DescriÃ§Ã£o */
    .details-drawer--modern .desc-block .desc-title {
        font-weight: 700;
        color: #334155;
        margin-bottom: .4rem;
        display: flex;
        align-items: center;
    }

    .details-drawer--modern .desc-block p {
        background: #f8fafc;
        border: 1px dashed #e5e7eb;
        border-radius: 12px;
        padding: .75rem .9rem;
    }

    /* Timeline */
    .details-drawer--modern .timeline {
        position: relative;
        margin-top: .5rem;
        padding-left: .75rem;
    }

    .details-drawer--modern .timeline:before {
        content: '';
        position: absolute;
        left: 10px;
        top: 6px;
        bottom: 6px;
        width: 2px;
        background: #e6ebff;
    }

    .details-drawer--modern .timeline-item {
        display: flex;
        gap: .75rem;
        position: relative;
        margin-bottom: .75rem;
    }

    .details-drawer--modern .timeline-dot {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #eaf0ff;
        color: #345bff;
        display: grid;
        place-items: center;
        z-index: 1;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #e6ebff;
    }

    .details-drawer--modern .timeline-content .timeline-label {
        font-size: .82rem;
        color: #64748b;
        margin-bottom: .1rem;
    }

    .details-drawer--modern .timeline-date {
        font-weight: 600;
        color: #1f2937;
    }

    /* Footer fixo */
    .details-drawer--modern .drawer-footer {
        position: sticky;
        bottom: 0;
        background: linear-gradient(0deg, #ffffff 80%, rgba(255, 255, 255, 0));
        padding: .9rem 1.25rem 1.1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        border-top: 1px solid #eef1f6;
    }

    /* Divider suave */
    .details-drawer--modern .divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, #eef1f6, transparent);
        margin: .9rem 0 1rem;
    }
</style>
