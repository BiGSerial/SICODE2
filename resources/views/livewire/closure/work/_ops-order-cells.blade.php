@php
    $classifier = \App\Services\Closure\ClosureMetaClassifier::class;
    $order      = $state->Order;
    $ops        = $order?->Operations ?? collect();
    $conf       = $ops->filter(fn ($op) => str_starts_with(strtoupper((string) $op->status), 'CONF'))->count();
    $letter     = $classifier::letter($state->meta_type);
@endphp
<td>
    @if ($letter)
        <x-ops.badge :tone="['P' => 'red', 'M' => 'green', 'F' => 'blue'][$letter] ?? 'muted'" mono :label="$letter" :hint="$classifier::label($state->meta_type)" />
    @else
        <span class="ops-empty">—</span>
    @endif
</td>
<td class="is-left copy-text" data-value="{{ $order?->ordem }}">
    <div class="ops-note">{{ $order?->ordem ?? '—' }} @if ($state->priority)<i class="ri-flag-2-fill ops-priority-flag" title="Prioritária" aria-label="Prioritária"></i>@endif</div>
    @if ($order?->Note)
        <a class="ops-sub" href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'service']) }}" title="Abrir a gestão da obra">Nota {{ $order->Note->note }}</a>
    @else
        <span class="ops-sub">Nota —</span>
    @endif
</td>
<td><span class="ops-sub text-body">{{ $classifier::competenceLabel($state->competence ?? null) }}</span></td>
<td class="is-left">
    <div class="text-truncate fw-semibold" title="{{ $order?->Note?->lexp }}">{{ $order?->Note?->lexp ?: '—' }}</div>
    <span class="ops-sub text-truncate" title="{{ $order?->Note?->pep }}">{{ $order?->Note?->pep ?: '—' }}</span>
</td>
<x-closure.order-cost :planned="$order?->custPlanejado" :actual="$order?->custRealizado" />
<td><x-ops.badge tone="muted" mono :label="$order?->statusSist ? implode(' ', array_slice(preg_split('/\s+/', trim($order->statusSist)), 0, 2)) : '—'" :hint="'Status do sistema (SAP): ' . ($order?->statusSist ?: '—')" /></td>
<td><span class="ops-sub text-body" title="Operações confirmadas / total">{{ $conf }}/{{ $ops->count() }}</span></td>
