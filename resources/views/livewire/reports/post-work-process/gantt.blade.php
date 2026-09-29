@php
    $col = 100 / max(1, $ganttMax);
    $pos = fn ($v) => min(100, $v * $col);
    $segNames = [
        'fiscal_dispatch'      => $stageLabels['fiscal_dispatch'],
        'fiscalization'        => $stageLabels['fiscalization'],
        'measurement_dispatch' => 'Despacho da medição',
        'measurement'          => $stageLabels['measurement'],
    ];
@endphp

<div class="ppr-card overflow-hidden mb-2">
    <div class="ppr-gantt-row" style="background:#0f172a;color:#f8fafc">
        <div class="small text-uppercase fw-semibold">Informe</div>
        <div class="ppr-gantt-axis" style="--ppr-col: {{ $col }}%">
            @for ($d = 0; $d <= $ganttMax; $d += ($ganttMax > 16 ? 2 : 1))
                <span class="ppr-gantt-tick" style="left:{{ $pos($d) }}%;color:#cbd5e1">{{ $d }}d</span>
            @endfor
        </div>
    </div>

    @forelse ($rows as $row)
        <div class="ppr-gantt-row" wire:key="gantt-{{ $row['type'] }}-{{ $row['id'] }}">
            <div>
                <span class="ppr-type ppr-type-{{ $row['type'] }}">{{ $row['type_label'] }}</span>
                <span class="ppr-id">#{{ $row['id'] }}</span>
                @foreach ($row['scopes'] as $scope)
                    <span class="badge {{ $scope['class'] }} ms-1">{{ $scope['label'] }}</span>
                @endforeach
                <div class="ppr-orders">Nota {{ $row['note'] ?? '—' }} · {{ $row['company'] ?? '—' }}</div>
            </div>
            <div class="ppr-gantt-track" style="--ppr-col: {{ $col }}%">
                @foreach ($row['gantt'] as $seg)
                    @php
                        $len = max($seg['end'] - $seg['start'], 0.35);
                        $over = ($seg['limit_end'] !== null && $seg['end'] > $seg['limit_end'])
                            ? ($seg['end'] - $seg['limit_end']) / $len * 100 : 0;
                    @endphp
                    <div class="ppr-gantt-seg ppr-seg-{{ $seg['key'] }} {{ $seg['open'] ? 'open' : '' }}"
                        style="left:{{ $pos($seg['start']) }}%;width:{{ min($len * $col, 100 - $pos($seg['start'])) }}%"
                        title="{{ $segNames[$seg['key']] }}: {{ $seg['start'] }}d → {{ $seg['end'] }}d{{ $seg['open'] ? ' (em aberto)' : '' }}{{ $seg['limit_end'] !== null ? ' · prazo até ' . $seg['limit_end'] . 'd' : '' }}">
                        @if ($over > 0)
                            <span class="over" style="width:{{ min($over, 100) }}%"></span>
                        @endif
                    </div>
                @endforeach
                <span class="ppr-gantt-limit" style="left:{{ $pos($limits['total']) }}%" title="Prazo total: {{ $limits['total'] }} dias úteis"></span>
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5">Nenhum informe encontrado para os filtros.</div>
    @endforelse

    <div class="p-3 border-top">
        <div class="ppr-legend">
            <span><i style="background:#94a3b8"></i>{{ $stageLabels['fiscal_dispatch'] }}</span>
            <span><i style="background:#14b8a6"></i>{{ $stageLabels['fiscalization'] }}</span>
            <span><i style="background:#cbd5e1"></i>Despacho da medição</span>
            <span><i style="background:#3b82f6"></i>{{ $stageLabels['measurement'] }}</span>
            <span><i style="background:#ef4444"></i>Tempo além do prazo da etapa</span>
            <span><i style="border:2px solid #f59e0b;background:#fff"></i>Etapa em aberto (até hoje)</span>
            <span><i style="border-left:2px dashed #dc2626;border-radius:0;width:0;height:.8rem"></i>Prazo total ({{ $limits['total'] }}d)</span>
        </div>
    </div>
</div>
