@php
    $order = $state->Order;
    $ops   = $order?->Operations?->sortBy('operacao') ?? collect();
@endphp
<td class="text-center"><span class="type-dot badge {{ \App\Services\Closure\ClosureMetaClassifier::badgeClass($state->meta_type) }}" title="{{ \App\Services\Closure\ClosureMetaClassifier::label($state->meta_type) }}">{{ \App\Services\Closure\ClosureMetaClassifier::letter($state->meta_type) ?? '—' }}</span></td>
<td>
    @if ($order?->Note)
        <a href="{{ route('closure.work.show', ['note' => $order->note_id, 'ctx' => 'service']) }}" title="Abrir a gestão da obra">{{ $order->Note->note }}</a>
    @else
        —
    @endif
</td>
<td class="fw-semibold">{{ $order?->ordem ?? '—' }}</td>
<td class="small text-nowrap">{{ \App\Services\Closure\ClosureMetaClassifier::competenceLabel($state->competence ?? null) }}</td>
<td class="small">
    <div>{{ $order?->Note?->pep ?? '—' }}</div>
    <div class="text-muted">{{ $order?->Note?->lexp ?? '—' }}</div>
</td>
<td class="small text-end text-nowrap" title="Valor da ADS do informe a que a Ordem pertence">{{ \App\Services\Closure\ClosureInformValue::format(($informValues ?? [])[$state->order_id] ?? null) }}</td>
<td><span class="badge bg-light text-dark border">{{ $order?->statusSist ?? '—' }}</span></td>
<td class="small">
    @forelse ($ops->take(8) as $op)
        <span class="badge bg-light text-dark border me-1" title="{{ $op->status }}">{{ $op->operacao }} {{ \Illuminate\Support\Str::limit((string) $op->status, 6, '') }}</span>
    @empty
        —
    @endforelse
</td>
