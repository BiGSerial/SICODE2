@php
    $order = $state->Order;
    $ops   = $order?->Operations?->sortBy('operacao') ?? collect();
@endphp
<td class="text-center">{!! \App\Services\Closure\ClosureMetaClassifier::badge($state->meta_type) !!}</td>
<td>
    @if ($order?->Note)
        <a href="{{ route('closure.work.show', $order->note_id) }}" title="Abrir a gestão da obra">{{ $order->Note->note }}</a>
    @else
        —
    @endif
</td>
<td class="fw-semibold">{{ $order?->ordem ?? '—' }}</td>
<td class="small">
    <div>{{ $order?->Note?->pep ?? '—' }}</div>
    <div class="text-muted">{{ $order?->Note?->lexp ?? '—' }}</div>
</td>
<td><span class="badge bg-light text-dark border">{{ $order?->statusSist ?? '—' }}</span></td>
<td class="small">
    @forelse ($ops->take(8) as $op)
        <span class="badge bg-light text-dark border me-1" title="{{ $op->status }}">{{ $op->operacao }} {{ \Illuminate\Support\Str::limit((string) $op->status, 6, '') }}</span>
    @empty
        —
    @endforelse
</td>
