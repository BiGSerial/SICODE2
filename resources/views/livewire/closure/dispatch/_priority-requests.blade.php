    {{-- Pedidos de prioridade --}}
    <div class="table-card mb-3" id="closure-priority-requests">
        <div class="chart-head">
            <div>
                <h3 class="chart-title">Pedidos de prioridade</h3>
                <div class="chart-subtitle">{{ $priorities->count() }} pendente(s). Aceitar marca todas as Ordens abertas da obra.</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <colgroup><col style="width: 130px"><col style="width: 190px"><col><col style="width: 150px"><col style="width: 330px"></colgroup>
                <thead><tr><th class="ps-3">Obra</th><th>Solicitante</th><th>Motivo</th><th>Pedido em</th><th class="pe-3">Ações</th></tr></thead>
                <tbody>
                    @forelse ($priorities as $request)
                        <tr wire:key="priority-{{ $request->id }}">
                            <td class="ps-3 fw-semibold"><a href="{{ route('closure.work.show', ['note' => $request->note_id, 'ctx' => 'dispatch']) }}">{{ $request->Note?->note ?? $request->note_id }}</a></td>
                            <td>{{ $request->RequestedBy?->name ?? '—' }}</td>
                            <td title="{{ $request->reason }}">{{ $request->reason }}</td>
                            <td>{{ $request->created_at->format('d/m/Y H:i') }}</td>
                            <td class="pe-3">
                                <button type="button" class="btn btn-success btn-sm btn-dash" onclick="closureDashDecide(this, {{ $request->id }}, 'accept')">Aceitar</button>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-dash" onclick="closureDashDecide(this, {{ $request->id }}, 'decline')">Recusar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted py-4 text-center">Nenhum pedido pendente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
