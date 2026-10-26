@php
    $showHolder = ($controlMode ?? false) || auth()->user()->can('closure.manage');
    $initials = fn (?string $name) => collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $chips    = [
        'ASSIGNED'         => ['#e8f0ff', '#263cc8'],
        'IN_PROGRESS'      => ['#dbeafe', '#1d4ed8'],
        'PAUSED'           => ['#f1f5f9', '#475569'],
        'WAITING_PARTNER'  => ['#fef3c7', '#b45309'],
        'DEPOSIT_BLOCKED'  => ['#fee2e2', '#b91c1c'],
        'DEPOSIT_RELEASED' => ['#dcfce7', '#15803d'],
        'FINISHED'         => ['#dcfce7', '#15803d'],
        'WAITING'          => ['#ede9fe', '#6d28d9'],
        'AVAILABLE'        => ['#f1f5f9', '#475569'],
    ];
    $allSelected = $states->count() > 0 && $states->every(fn ($st) => in_array($st->id, $selectedStateIds));
@endphp
@php
    $informValues = $this->informValues($states);
    $riByNote     = app(\App\Services\Closure\ClosureInternalReturnService::class)->activeForNotes(collect($states instanceof \Illuminate\Pagination\AbstractPaginator ? $states->getCollection() : $states)->map(fn ($st) => $st->Order?->note_id)->filter());
@endphp
<x-closure.signal-legend class="px-3 py-2 border-bottom" :executor="!($controlMode ?? false)" />
<table class="table table-sm align-middle mb-0 dash-table">
    <colgroup>
        <col style="width: 44px;"><col style="width: 56px;"><col style="width: 170px;"><col style="width: 90px;"><col>
        <col style="width: 122px;"><col style="width: 110px;"><col style="width: 80px;">@if ($showHolder)<col style="width: 200px;">@endif<col style="width: 160px;"><col style="width: 110px;"><col style="width: 100px;">
    </colgroup>
    <thead>
        <tr>
            <th class="ps-3"><input type="checkbox" class="form-check-input" @checked($allSelected) wire:click="toggleVisibleStates" aria-label="Selecionar/limpar visíveis" title="Selecionar/limpar visíveis"></th>
            <th class="text-center">Tipo</th>
            <th>Ordem / Nota</th>
            <th title="Mês de competência da meta">Compet.</th>
            <th>Obra / Município</th>
            <th class="text-end" title="Valor da ADS do informe a que a Ordem pertence">Valor da obra (ADS)</th>
            <th>Status SAP</th>
            <th title="Operações confirmadas / total">Ops.</th>
            @if ($showHolder)<th>Responsável</th>@endif
            <th>Status da atividade</th>
            <th>Tempo no status</th>
            <th class="pe-3 text-end">Ações</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($states as $state)
            @php
                $selected = in_array($state->id, $selectedStateIds);
                $order    = $state->Order;
                $ops      = $order?->Operations ?? collect();
                $conf     = $ops->filter(fn ($op) => $this->isConfirmedOperation($op->status))->count();
                $holder   = $state->CurrentHolderUser?->name;
                $colors   = $chips[$state->operational_status] ?? ['#f1f5f9', '#475569'];
                $signal   = \App\Services\Closure\ClosureSignal::forState($state);

                // Tela do executor: o azul (atribuída) e o amarelo (pilha) são da visão de controle; aqui só vale atenção (vermelho) e concluída (verde).
                if (!($controlMode ?? false) && $signal !== null && in_array($signal['key'], ['assigned', 'pile'], true)) {
                    $signal = null;
                }
            @endphp
            <tr wire:key="closure-work-state-{{ $state->id }}" @class(['is-selected' => $selected, 'signal-row signal-' . ($signal['key'] ?? '') => $signal !== null]) @if ($signal) data-signal="{{ $signal['key'] }}" title="{{ $signal['label'] }}: {{ $signal['hint'] }}" @endif>
                <td class="ps-3"><input type="checkbox" class="form-check-input" @checked($selected) wire:click="toggleState({{ $state->id }})" aria-label="Selecionar Ordem {{ $order?->ordem }}"></td>
                <td class="text-center"><span class="type-dot badge {{ \App\Services\Closure\ClosureMetaClassifier::badgeClass($state->meta_type) }}" title="{{ \App\Services\Closure\ClosureMetaClassifier::label($state->meta_type) }}">{{ \App\Services\Closure\ClosureMetaClassifier::letter($state->meta_type) ?? '—' }}</span></td>
                <td>
                    <div class="ord-main">{{ $order?->ordem ?? '—' }} @if ($state->priority)<i class="ri-flag-2-fill text-danger" title="Prioritária"></i>@endif</div>
                    <div class="ord-sub">Nota {{ $order?->Note?->note ?? '—' }}</div>
                </td>
                <td class="small text-nowrap">{{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($state->competence ?? null) }}</td>
                <td>
                    <div class="fw-bold text-uppercase text-truncate" style="font-size:.82rem;" title="{{ $order?->Note?->lexp }}">{{ $order?->Note?->lexp ?: '—' }}</div>
                    <div class="ord-sub text-truncate" title="{{ $order?->Note?->pep }}">{{ $order?->Note?->pep ?: '—' }}</div>
                </td>
                <td class="small text-end text-nowrap" title="Valor da ADS do informe a que a Ordem pertence">{{ \App\Services\Closure\ClosureInformValue::format($informValues[$state->order_id] ?? null) }}</td>
                <td><span class="sap-chip" title="{{ $order?->statusSist }}">{{ $this->sapHeadline($order?->statusSist) }}</span></td>
                <td><button type="button" class="ops-btn @if ($ops->count() && $conf === $ops->count()) is-done @endif" wire:click="showOperations({{ $state->id }})" title="Ver operações da Ordem" aria-label="Ver operações da Ordem {{ $order?->ordem }}">{{ $conf }}/{{ $ops->count() }}</button></td>
                @if ($showHolder)
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar-sm" aria-hidden="true">{{ $holder ? $initials($holder) : '—' }}</span>
                        <div class="lh-sm" style="min-width:0;">
                            <div class="fw-semibold small text-truncate" title="{{ $holder ?? 'Sem responsável' }}">{{ $holder ?? 'Sem responsável' }}</div>
                            <div class="ord-sub text-truncate">{{ $state->CurrentHolderCompany?->name ?? '—' }}</div>
                        </div>
                    </div>
                </td>
                @endif
                <td>
                    <span class="st-chip" style="background: {{ $colors[0] }}; color: {{ $colors[1] }};">{{ \App\Enum\ClosureOperationalStatus::labelFor($state->operational_status) }}</span>
                    @if ($ri = $riByNote->get($order?->note_id))
                        <div class="mt-1"><x-closure.lock-badge reason="RI com o Desenho" :since="$ri->sent_at" /></div>
                    @endif
                    @if ($state->operational_status === 'WAITING')
                        <div class="ord-sub">Aguarda o mês da meta</div>
                    @endif
                    @if ($sig = ($depositSignals[$state->id] ?? null))
                        <div class="mt-1"><span class="st-chip" style="background:#fee2e2;color:#b91c1c;" title="Depósito {{ $sig->deposit_code }}{{ $sig->deposit_label ? ' · ' . $sig->deposit_label : '' }} bloqueado desde {{ $sig->created_at?->format('d/m/Y') }}{{ $sig->origin === 'CALENDAR' ? ' (inventário)' : '' }}. Volta ao status anterior quando o depósito for liberado."><i class="ri-lock-line"></i> Depósito {{ $sig->deposit_code }}</span></div>
                    @endif
                </td>
                <td class="small text-muted" title="{{ $this->holderLabel($state) }}">{{ $this->elapsedLabel($state) }}</td>
                <td class="pe-3 text-end cell-visible">@include('livewire.closure.dispatch._control-actions')</td>
            </tr>
        @empty
            <tr><td colspan="12" class="text-center text-muted py-4">Não há Ordens nesta lista com os filtros atuais.@if ($selfAssignEnabled && !($controlMode ?? false)) Use "Pegar atividades" para assumir novas.@endif</td></tr>
        @endforelse
    </tbody>
</table>
