<div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <strong>Passivo</strong>
            <div class="text-muted small">Ordens de competências anteriores que ainda não encerraram no SAP.</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Ordem</th>
                        <th>Nota</th>
                        <th>Meta original</th>
                        <th>Aging</th>
                        <th>statusSist</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($targets as $target)
                        <tr>
                            <td class="ps-3">{{ $target->Order->ordem ?? '—' }}</td>
                            <td>{{ $target->Note->note ?? '—' }}</td>
                            <td>{{ $target->Cycle->label ?? '—' }}</td>
                            <td>{{ $target->frozen_at?->diffInDays(now()) }} dias</td>
                            <td class="text-muted small">{{ $target->Order->statusSist ?? '—' }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('closure.order.detail', $target->order_id) }}" class="small">ver detalhe</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted text-center py-3">Nenhuma Ordem em passivo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
