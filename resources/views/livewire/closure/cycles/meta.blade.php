<div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex align-items-center gap-3">
            <strong>Meta da Competência</strong>
            <select wire:model="cycleId" class="form-select form-select-sm w-auto">
                @foreach ($cycles as $cycle)
                    <option value="{{ $cycle->id }}">{{ $cycle->label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @forelse ($targets as $noteLabel => $noteTargets)
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>{{ $noteLabel }}</strong>
                <span class="text-muted small">
                    {{ $noteTargets->filter(fn ($t) => $t->Order && (str_starts_with((string) $t->Order->statusSist, 'ENTE') || str_starts_with((string) $t->Order->statusSist, 'ENCE')))->count() }}/{{ $noteTargets->count() }} encerradas
                </span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach ($noteTargets as $target)
                            @php
                                $status = (string) ($target->Order->statusSist ?? '');
                                $closed = str_starts_with($status, 'ENTE') || str_starts_with($status, 'ENCE');
                            @endphp
                            <tr>
                                <td class="ps-3">Ordem {{ $target->Order->ordem ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $closed ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ $closed ? 'ENCERRADA' : 'EM ABERTO' }}
                                    </span>
                                </td>
                                <td class="text-muted small">{{ $status }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('closure.order.detail', $target->order_id) }}" class="small">ver detalhe</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="text-muted">Nenhuma Ordem nesta competência.</div>
    @endforelse
</div>
