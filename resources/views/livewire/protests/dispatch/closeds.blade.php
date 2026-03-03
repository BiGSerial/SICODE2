@php
    if (!function_exists('reduceName')) {
        function reduceName($name, bool $first = false)
        {
            $partName = explode(' ', trim((string) $name));

            if (count($partName) === 0) {
                return '';
            }

            if (count($partName) === 1) {
                return $partName[0];
            }

            if ($first) {
                return $partName[0];
            }

            return $partName[0] . ' ' . end($partName);
        }
    }

    if (!function_exists('getWishDateJob')) {
        function getWishDateJob($job)
        {
            $protest = $job->protest;
            $medProtest = $job->medProtest;

            if ($protest?->tipoNota === 'NA') {
                return $protest?->dtConclusaoDesej;
            }

            return $medProtest?->dtFimMedidaDesej;
        }
    }

    if (!function_exists('getApertureDateJob')) {
        function getApertureDateJob($job)
        {
            $protest = $job->protest;
            $medProtest = $job->medProtest;

            if ($protest?->tipoNota === 'NA') {
                return $protest?->dtAberturaNota;
            }

            return $medProtest?->dtCriacaoMedida;
        }
    }
@endphp

@push('css')
    <style>
        .protest-page {
            --pp-bg: #f6f7fb;
            --pp-surface: #ffffff;
            --pp-border: #e5e7eb;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                var(--pp-bg);
            padding: 1.5rem 0;
        }

        .protest-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
            margin-bottom: 1rem;
        }
    </style>
@endpush

<div class="protest-page">
    <x-show-loading />
    <div class="container-fluid">
        <div class="protest-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Histórico de Reclamações</h4>
                <small class="text-white-50">Dispatch - atividades concluídas e canceladas</small>
            </div>
            <button wire:click="exportToExcel" class="btn btn-light btn-sm text-dark">
                <i class="ri-file-excel-2-line me-1"></i> Exportar
            </button>
        </div>

    {{-- ================== TOP: BUSCA E FILTROS ================== --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                {{-- 1) Registros por página --}}
                <div class="col-12 col-sm-4 col-md-2">
                    <div class="form-floating w-100">
                        <select class="form-select border border-secondary" wire:model="perPage" id="perPageSelect">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="500">500</option>
                        </select>
                        <label for="perPageSelect">Registros</label>
                    </div>
                </div>

                {{-- 2) Busca principal (nota) --}}
                <div class="col-12 col-sm-8 col-md-4">
                    <div class="form-floating w-100 position-relative">
                        <input wire:model.debounce.800ms="search" type="text"
                            class="form-control border border-secondary" id="search" placeholder="Buscar nota">
                        <label for="search">Buscar nota da reclamação</label>

                        <button class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                            data-bs-toggle="modal" data-bs-target="#buscar_multi">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>
                </div>

                {{-- 3) Tipo de Nota --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Tipo de nota</label>
                    <div class="btn-group w-100" role="group" aria-label="Tipo de Nota">
                        <input type="radio" class="btn-check" name="typeNote" id="typeNote1" wire:model="typeNote"
                            value="NA">
                        <label class="btn btn-outline-primary" for="typeNote1">NA</label>

                        <input type="radio" class="btn-check" name="typeNote" id="typeNote2" wire:model="typeNote"
                            value="OU">
                        <label class="btn btn-outline-primary" for="typeNote2">OU</label>

                        <input type="radio" class="btn-check" name="typeNote" id="typeNote3" wire:model="typeNote"
                            value="">
                        <label class="btn btn-outline-primary" for="typeNote3">Ambos</label>
                    </div>
                </div>

                {{-- 4) SLA --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">SLA do Job</label>
                    <div class="btn-group w-100" role="group" aria-label="Prazo SLA">
                        <input type="radio" class="btn-check" name="inPrazo" id="inPrazo1" wire:model="inPrazo"
                            value="1">
                        <label class="btn btn-outline-primary" for="inPrazo1">Fora SLA</label>

                        <input type="radio" class="btn-check" name="inPrazo" id="inPrazo2" wire:model="inPrazo"
                            value="2">
                        <label class="btn btn-outline-primary" for="inPrazo2">Dentro SLA</label>

                        <input type="radio" class="btn-check" name="inPrazo" id="inPrazo3" wire:model="inPrazo"
                            value="">
                        <label class="btn btn-outline-primary" for="inPrazo3">Todos</label>
                    </div>
                </div>

                {{-- 5) Tipo de Protesto --}}
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Tipo de Protesto</label>
                    <div class="btn-group w-100" role="group" aria-label="Tipo de Protesto">
                        <input type="radio" class="btn-check" name="protestTypeFilter" id="ptype-bt"
                            wire:model="protestTypeFilter" value="only_btzero">
                        <label class="btn btn-outline-primary" for="ptype-bt">BT Zero</label>

                        <input type="radio" class="btn-check" name="protestTypeFilter" id="ptype-no-bt"
                            wire:model="protestTypeFilter" value="without_btzero">
                        <label class="btn btn-outline-primary" for="ptype-no-bt">Sem BT Zero</label>

                        <input type="radio" class="btn-check" name="protestTypeFilter" id="ptype-all"
                            wire:model="protestTypeFilter" value="all">
                        <label class="btn btn-outline-primary" for="ptype-all">Todos</label>
                    </div>
                </div>
            </div>

            <hr class="my-3">

            {{-- Linha 2: filtro por usuário + filtros externos --}}
            <div class="row g-3 align-items-end">
                {{-- 5) Busca por nome de usuário --}}
                <div class="col-12 col-md-3">
                    <div class="form-floating">
                        <input type="text" class="form-control border border-secondary" id="searchName"
                            placeholder="Buscar usuário" wire:model.debounce.500ms="searchName">
                        <label for="searchName">Buscar responsável</label>
                    </div>
                </div>

                {{-- 6) Combo de usuários (hierarquia) --}}
                <div class="col-12 col-md-3">
                    <label for="userViewer" class="form-label small mb-1">Responsável / Hierarquia</label>
                    <select class="form-select border border-secondary" id="userViewer" wire:model="userViewer">
                        <option value="">Todos os responsáveis</option>
                        @foreach ($userViewerList as $u)
                            <option value="{{ $u->id }}">
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 7) Filtros externos (grupo, região, cidade) --}}
                <div class="col-12 col-md-6">
                    <div class="d-flex flex-wrap justify-content-center justify-content-md-end gap-2">
                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'group',
                                'sendFilter' => '',
                                'modelClass' => \App\Models\Protest::class,
                                'column' => 'txtGrpCodificacao',
                                'filterLabel' => 'Grupo',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'txtGrpCodificacao',
                                'direction' => 'ASC',
                                'searchColumn' => '',
                                'sendSearchColumn' => 'entity_type_id',
                            ],
                            key('closed-entityTypes')
                        )

                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'region',
                                'sendFilter' => 'city',
                                'modelClass' => \App\Models\Edp_depc\City::class,
                                'column' => 'regiao',
                                'filterLabel' => 'Região',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'regiao',
                                'direction' => 'ASC',
                                'searchColumn' => 'regiao',
                                'sendSearchColumn' => 'regiao',
                            ],
                            key('closed-region')
                        )

                        @livewire(
                            'components.filter.filter2',
                            [
                                'myKey' => 'city',
                                'sendFilter' => '',
                                'modelClass' => \App\Models\Edp_depc\City::class,
                                'column' => 'cidade',
                                'filterLabel' => 'Município',
                                'groupFilter' => 'oexterno',
                                'displayColumn' => 'municipio',
                                'direction' => 'ASC',
                                'searchColumn' => 'municipio',
                                'sendSearchColumn' => 'cidade',
                            ],
                            key('closed-city')
                        )

                        @livewire('components.filter.remove-all', ['group_filter' => 'oexterno'], key('closed-removeAll'))
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== CARDS DE RESUMO ================== --}}
    @php
        $total = $stats['total'] ?? 0;
        $avgClosing = $stats['avg_closing_days'] ?? null;

        $withinContract = $stats['within_contract'] ?? 0;
        $withinContractPc = $stats['within_contract_pct'] ?? 0;
        $outContract = $stats['out_contract'] ?? 0;
        $outContractPc = $stats['out_contract_pct'] ?? 0;

        $withinSla = $stats['within_sla'] ?? 0;
        $withinSlaPc = $stats['within_sla_pct'] ?? 0;
        $outSla = $stats['out_sla'] ?? 0;
        $outSlaPc = $stats['out_sla_pct'] ?? 0;
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Jobs finalizados</span>
                        <i class="ri-task-fill fs-4 text-primary"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <h3 class="fw-bold mb-0">{{ $total }}</h3>
                        <span class="badge bg-light text-muted">100%</span>
                    </div>
                    <small class="text-muted mt-2">
                        Reclamações com job concluído ou cancelado.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Tempo médio de conclusão</span>
                        <i class="ri-time-line fs-4 text-secondary"></i>
                    </div>
                    <div class="d-flex justify-content-between align-items-end mb-1">
                        <h3 class="fw-bold mb-0">
                            {{ $avgClosing !== null ? $avgClosing . ' d' : '--' }}
                        </h3>
                    </div>
                    <small class="text-muted">
                        Da abertura da nota até o fim do job.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">Prazo contratual</span>
                        <i class="ri-calendar-check-line fs-4 text-success"></i>
                    </div>
                    <div class="mb-1">
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Dentro do prazo</span>
                            <span class="small fw-bold">
                                {{ $withinContract }} ({{ $withinContractPc }}%)
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Fora do prazo</span>
                            <span class="small fw-bold text-danger">
                                {{ $outContract }} ({{ $outContractPc }}%)
                            </span>
                        </div>
                    </div>
                    <small class="text-muted mt-auto">
                        Data desejada x fim do job.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted text-uppercase small">SLA</span>
                        <i class="ri-timer-flash-line fs-4 text-warning"></i>
                    </div>
                    <div class="mb-1">
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">Dentro do SLA</span>
                            <span class="small fw-bold">
                                {{ $withinSla }} ({{ $withinSlaPc }}%)
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="small text-muted">SLA estourado</span>
                            <span class="small fw-bold text-danger">
                                {{ $outSla }} ({{ $outSlaPc }}%)
                            </span>
                        </div>
                    </div>
                    <small class="text-muted mt-auto">
                        finished_at x sla_due_at.
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== PAGINAÇÃO TOPO ================== --}}
    <div class="row mb-2">
        @if ($lists->count())
            <div class="col-6">
                {{ $lists->links() }}
            </div>
        @endif
        <div class="col-6 d-flex justify-content-end align-middle">
            @if ($lists->count())
                <span class="align-middle">
                    Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }}
                    de {{ $lists->total() }} registros.
                </span>
    @endif
</div>
</div>
    </div>

    {{-- ================== TABELA DE JOBS FECHADOS ================== --}}
    <div class="card shadow-sm border-0">
        @if (!$lists->count())
            <div class="card-body">
                <h4 class="text-center">SEM JOBS NO HISTÓRICO PARA OS FILTROS ATUAIS</h4>
            </div>
        @else
            <div class="card-header fw-bold text-bg-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="ri-clipboard-check-line me-1"></i>
                    HISTÓRICO DE JOBS DE RECLAMAÇÕES
                </h5>
                <button wire:click="exportToExcel" class="btn btn-success btn-sm">
                    <i class="ri-file-excel-2-line me-2"></i>Exportar
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th>Prioridade</th>
                            <th>Despachante</th>
                            <th>Tipo</th>
                            <th></th>
                            <th>Nota</th>
                            <th>Medida</th>
                            <th>Cód</th>
                            <th>txCodMed</th>
                            <th>Tipo Reclamação</th>
                            <th>Município</th>
                            <th>Responsável</th>
                            <th>Empresa</th>
                            <th>Abertura</th>
                            <th>Fim desejado</th>
                            <th>Fim do Job</th>
                            <th>SLA</th>
                            <th>Prazo</th>
                            <th>Finalizado por</th>
                            <th style="width:48px;"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($lists as $item)
                            @php
                                $protest = $item->protest;
                                $medProtest = $item->medProtest;

                                $openedAt = getApertureDateJob($item);
                                $wishAt = getWishDateJob($item);
                                $finishedAt = $item->finished_at;
                                $endedAt = $item->finished_at ?? $item->closed_at;

                                $slaLabel = 'SEM SLA';
                                $slaClass = 'text-bg-secondary';

                                if ($item->sla_due_at && $finishedAt) {
                                    if ($finishedAt->gt($item->sla_due_at)) {
                                        $slaLabel = 'SLA ESTOURADO';
                                        $slaClass = 'text-bg-danger';
                                    } else {
                                        $slaLabel = 'SLA OK';
                                        $slaClass = 'text-bg-success';
                                    }
                                }

                                $contractLabel = '--';
                                $contractClass = 'text-bg-secondary';

                                if ($wishAt && $finishedAt) {
                                    if ($finishedAt->gt($wishAt)) {
                                        $contractLabel = 'FORA DO PRAZO';
                                        $contractClass = 'text-bg-danger';
                                    } else {
                                        $contractLabel = 'NO PRAZO';
                                        $contractClass = 'text-bg-success';
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
                                        {{ $protest?->tipoNota }}
                                    </span>
                                </td>

                                <td>
                                    @if ($item->is_advance)
                                        <span class="badge text-bg-info">A</span>
                                    @endif
                                </td>

                                <td class="fw-bold">
                                    {{ $protest?->nota }}
                                </td>

                                <td class="fw-bold">
                                    # {{ $medProtest?->med_id }}
                                </td>

                                <td>
                                    <span class="badge text-bg-secondary">
                                        {{ $protest?->statUsuar }}
                                    </span>
                                </td>
                                <td class="">
                                    {{ $medProtest?->txtCodMedida }}
                                </td>

                                <td class="text-uppercase">
                                    {{ $protest?->txtGrpCodificacao }}
                                </td>

                                <td>
                                    {{ $protest?->cidade }}
                                </td>

                                <td class="text-uppercase fw-bold">
                                    {{ reduceName($item->owner?->name) }}
                                </td>

                                <td class="text-uppercase">
                                    {{ reduceName($item->owner?->company?->name, true) }}
                                </td>

                                <td>
                                    {{ $openedAt ? $openedAt->format('d/m/Y') : '---' }}
                                </td>

                                <td>
                                    {{ $wishAt ? $wishAt->format('d/m/Y') : '---' }}
                                </td>

                                <td class="fw-bold">
                                    {{ $endedAt ? $endedAt->format('d/m/Y') : '---' }}
                                </td>

                                <td>
                                    <span class="badge {{ $slaClass }}">
                                        {{ $slaLabel }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge {{ $contractClass }}">
                                        {{ $contractLabel }}
                                    </span>
                                </td>

                                <td class="text-uppercase fw-bold">
                                    {{ reduceName($item->closer?->name) }}
                                </td>

                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            title="Visualizar Job"
                                            wire:click="$emitTo('protests.dispatch.actions.view-protest-job', 'open', {{ $item->id }})">
                                            <i class="ri-eye-line"></i>
                                        </button>

                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            wire:click="goTo({{ $protest?->nota }})" title="Abrir reclamação">
                                            <i class="ri-bookmark-line"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ================== PAGINAÇÃO BASE ================== --}}
    <div class="row mt-2">
        @if ($lists->count())
            <div class="col-6">
                {{ $lists->links() }}
            </div>
        @endif
        <div class="col-6 d-flex justify-content-end align-middle">
            @if ($lists->count())
                <span class="align-middle">
                    Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }}
                    de {{ $lists->total() }} registros.
                </span>
            @endif
        </div>
    </div>

    {{-- Drawer/modal compartilhado com Monitoring --}}
    @livewire('protests.dispatch.actions.view-protest-job', key('view-protest-job'))
</div>
</div>
