@php
    $hasSelection = count($selectedStateIds) > 0;
    $avgMin       = $stats['avg_min'];
    $avgLabel     = $avgMin >= 1440 ? intdiv($avgMin, 1440) . 'd ' . intdiv($avgMin % 1440, 60) . 'h' : ($avgMin >= 60 ? intdiv($avgMin, 60) . 'h ' . str_pad((string) ($avgMin % 60), 2, '0', STR_PAD_LEFT) . 'm' : $avgMin . ' min');
    $pct          = fn (int $n) => $stats['total'] > 0 ? number_format($n / $stats['total'] * 100, 1, ',', '.') . '%' : '0%';
    $isControl    = (bool) ($controlMode ?? false);
    $kpis         = [
        ['Total na fila', $stats['total'], 'ri-list-check', '#155f67', 'conforme filtros'],
        ['Em andamento', $stats['progress'], 'ri-user-settings-line', '#263cc8', $pct($stats['progress'])],
        ['Prioritárias', $stats['priority'], 'ri-flag-2-line', '#c0392b', $stats['priority'] ? 'atenção imediata' : 'nenhuma'],
        $isControl
            ? ['Sem responsável', $stats['available'], 'ri-user-unfollow-line', '#b45309', 'na pilha da empresa']
            : ['Pausadas / aguardando', $stats['blocked'], 'ri-pause-circle-line', '#b45309', 'dependem de retorno'],
        ['Tempo médio no status', $avgLabel, 'ri-time-line', '#0f8a77', 'média da fila'],
    ];
@endphp

@if ($isControl && ($dueDeposits ?? 0) > 0)
    <a href="{{ \App\Services\Closure\ClosureServiceLocator::url('dispatch.deposits') }}" class="d-flex flex-wrap align-items-center gap-3 p-3 mb-3 rounded text-decoration-none" style="background:#fff8e6;border:1px solid #f1d58a;color:#7a4b00;">
        <i class="ri-calendar-event-line" style="font-size:1.5rem;"></i>
        <div class="flex-grow-1"><strong>{{ $dueDeposits }} depósito(s) em data de inventário</strong> aguardando a confirmação do bloqueio. As Ordens dos centros de trabalho vinculados só passam a Depósito Bloqueado depois que você confirmar.</div>
        <span class="btn btn-sm btn-warning fw-semibold">Abrir calendário <i class="ri-arrow-right-line"></i></span>
    </a>
@endif

<div class="row g-2 mb-3">
    @foreach ($kpis as [$label, $value, $icon, $color, $note])
        <div class="col-6 col-md-4 col-xl" wire:key="kpi-{{ $loop->index }}">
            <div class="kpi" style="--k: {{ $color }};">
                <div class="kpi-top"><span>{{ $label }}</span><i class="{{ $icon }}"></i></div>
                <div class="kpi-value">{{ is_int($value) ? number_format($value, 0, ',', '.') : $value }}</div>
                <div class="kpi-note">{{ $note }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="table-card filter-bar p-3 mb-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2 fw-bold"><i class="ri-filter-3-line"></i> Filtros operacionais @if (count($applied))<span class="badge bg-light text-dark border">{{ count($applied) }} ativo(s)</span>@endif</div>
        @if (count($applied))
            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-danger" wire:click="clearFilters"><i class="ri-close-circle-line"></i> Limpar filtros</button>
        @endif
    </div>
    <x-closure.bulk-search-result :report="$this->bulkReport()" class="mb-3" />
    <div class="row g-3">
        <div class="col-12 col-md-6 {{ $isControl ? 'col-xl-2' : 'col-xl-3' }}">
            <label class="ctl-label">Tipo de ordem</label>
            <select class="form-select" wire:model="type" aria-label="Filtrar por tipo">
                <option value="all">Todos (P / M / F)</option>
                <option value="P">P — Passivo</option>
                <option value="M">M — Meta atual</option>
                <option value="F">F — Meta futura</option>
            </select>
        </div>
        <div class="col-12 col-md-6 {{ $isControl ? 'col-xl-3' : 'col-xl-3' }}">
            <label class="ctl-label">Busca específica</label>
            <div class="input-group"><span class="input-group-text bg-white"><i class="ri-search-line"></i></span><input type="search" class="form-control" wire:model.debounce.400ms="search" placeholder="Ordem ou nota" aria-label="Buscar por Ordem ou Nota"></div>
            <div class="mt-1"><x-closure.bulk-search :terms="$bulkTerms" :open="$bulkOpen" /></div>
        </div>
        @if ($isControl)
        <div class="col-12 col-md-4 col-xl-2">
            <label class="ctl-label" for="closure-control-company">Empresa (posse atual)</label>
            <select id="closure-control-company" class="form-select" wire:model="filterCompany">
                <option value="">Todas</option>
                @foreach ($this->destinationCompanies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-4 col-xl-2">
            <label class="ctl-label" for="closure-control-user">Responsável</label>
            <select id="closure-control-user" class="form-select" wire:model="filterUser">
                <option value="">Todos</option>
                @foreach ($this->controlUsers as $controlUser)
                    <option value="{{ $controlUser->id }}">{{ $controlUser->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-12 col-md-4 col-xl-2">
            <label class="ctl-label">Status usuário</label>
            @include('livewire.closure.work._status-user-filter')
        </div>
        <div class="col-12 col-md-4 col-xl-3">
            <label class="ctl-label" for="closure-work-status">Status da atividade</label>
            <select id="closure-work-status" class="form-select" wire:model="status">
                <option value="all">Em tratativa (todos)</option>
                @foreach ($statusOptions as $option)
                    <option value="{{ $option->value }}">{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="small text-muted">Filtros aplicados:</span>
            @forelse ($applied as $chip)
                <span class="ctl-chip" wire:key="chip-{{ $chip['kind'] }}-{{ md5($chip['value'] ?? '') }}">{{ $chip['label'] }}<button type="button" wire:click="removeFilter('{{ $chip['kind'] }}', @js($chip['value'] ?? null))" aria-label="Remover filtro"><i class="ri-close-line"></i></button></span>
            @empty
                <span class="small text-muted">nenhum</span>
            @endforelse
        </div>
        <div class="form-check form-switch mb-0">
            <input id="closure-control-priority" type="checkbox" role="switch" class="form-check-input" wire:model="onlyPriority">
            <label for="closure-control-priority" class="form-check-label small fw-semibold">Apenas prioritárias</label>
        </div>
    </div>
</div>

@if ($hasSelection)
    <div class="ctl-bulk p-3 mb-3" id="closure-work-bulk">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success px-3 py-2">{{ count($selectedStateIds) }} ordem(ns) selecionada(s)</span>
                <span class="small text-muted">Escolha uma ação, confira a prévia e confirme.</span>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearSelectionNow"><i class="ri-close-line"></i> Limpar seleção</button>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-lg-3">
                <label for="closure-work-batch" class="ctl-label">Ação</label>
                <select id="closure-work-batch" class="form-select" wire:model="batchAction">
                    <option value="">Escolha uma ação</option>
                    @unless ($isControl)
                        <option value="start">Iniciar</option>
                        <option value="change_status">Alterar status</option>
                        <option value="finish">Finalizar</option>
                    @endunless
                    <option value="return_to_pool">Devolver à pilha</option>
                    @can('closure.manage')
                        <option value="assign">Atribuir responsável</option>
                        <option value="transfer">Transferir empresa</option>
                        <option value="set_priority">Ajustar prioridade</option>
                    @endcan
                </select>
            </div>
            @if ($batchAction === 'change_status')
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="closure-work-batch-status" class="ctl-label">Novo status</label>
                    <select id="closure-work-batch-status" class="form-select" wire:model="batchStatus">
                        <option value="">Selecione</option>
                        <option value="PAUSED">Pausa</option>
                        <option value="DEPOSIT_BLOCKED">Depósito Bloqueado</option>
                        <option value="DEPOSIT_RELEASED">Depósito Liberado</option>
                        <option value="IN_PROGRESS">Em andamento</option>
                    </select>
                </div>
            @endif
            @if (in_array($batchAction, ['assign', 'transfer'], true))
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="closure-work-destination-company" class="ctl-label">Empresa de destino</label>
                    <select id="closure-work-destination-company" class="form-select" wire:model="managerCompanyId">
                        <option value="">Selecione</option>
                        @foreach ($this->destinationCompanies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="closure-work-destination-user" class="ctl-label">Usuário de destino {{ $batchAction === 'assign' ? '' : '(opcional)' }}</label>
                    <select id="closure-work-destination-user" class="form-select" wire:model="managerAssigneeId" @disabled(!$managerCompanyId)>
                        <option value="">{{ $batchAction === 'assign' ? 'Selecione' : 'Enviar para a pilha' }}</option>
                        @foreach ($this->destinationUsers as $destinationUser)
                            <option value="{{ $destinationUser->id }}">{{ $destinationUser->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if ($batchAction === 'set_priority')
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="closure-work-priority" class="ctl-label">Prioridade</label>
                    <select id="closure-work-priority" class="form-select" wire:model="managerPriority">
                        <option value="0">Normal</option>
                        <option value="1">Alta</option>
                    </select>
                </div>
            @endif
            @if (in_array($batchAction, ['return_to_pool', 'transfer'], true) || ($batchAction === 'change_status' && in_array($batchStatus, ['PAUSED', 'DEPOSIT_BLOCKED'], true)))
                <div class="col-12 col-lg-6">
                    <label for="closure-work-batch-reason" class="ctl-label">Motivo *</label>
                    <textarea id="closure-work-batch-reason" class="form-control" rows="1" wire:model.defer="batchReason" placeholder="Explique o motivo."></textarea>
                </div>
            @endif
            <div class="col-12 col-md-auto">
                <button type="button" class="btn btn-success fw-semibold" wire:click="prepareBatchPreview" @disabled(!$batchAction)><i class="ri-eye-line"></i> Ver prévia</button>
            </div>
        </div>
    </div>
@endif
