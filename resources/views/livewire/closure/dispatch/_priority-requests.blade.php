{{-- Pedidos de prioridade (painel analítico). Aceitar marca todas as Ordens abertas da obra. --}}
<section class="mp-panel mb-4" id="closure-priority-requests">
    <div class="mp-panel-head">
        <div>
            <h3 class="mp-panel-title">Pedidos de prioridade</h3>
            <div class="mp-panel-sub">{{ $priorities->count() }} pendente(s). Aceitar marca todas as Ordens abertas da obra.</div>
        </div>
    </div>
    <div class="ops-table-wrap">
        <table class="table mp-table align-middle mb-0">
            <colgroup><col style="width: 130px"><col style="width: 190px"><col><col style="width: 150px"><col style="width: 220px"></colgroup>
            <thead><tr><th class="text-start">Obra</th><th class="text-start">Solicitante</th><th class="text-start">Motivo</th><th class="text-center">Pedido em</th><th><span class="visually-hidden">Ações</span></th></tr></thead>
            <tbody>
                @forelse ($priorities as $request)
                    <tr wire:key="priority-{{ $request->id }}">
                        <td class="text-start fw-semibold"><a href="{{ route('closure.work.show', ['note' => $request->note_id, 'ctx' => 'dispatch']) }}">{{ $request->Note?->note ?? $request->note_id }}</a></td>
                        <td class="text-start">{{ $request->RequestedBy?->name ?? '—' }}</td>
                        <td class="text-start" title="{{ $request->reason }}">{{ $request->reason }}</td>
                        <td class="text-center">{{ $request->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="ops-buttons">
                                <button type="button" class="btn btn-primary btn-sm ops-action" onclick="closureDashDecide(this, {{ $request->id }}, 'accept')">Aceitar</button>
                                <button type="button" class="btn btn-outline-danger btn-sm ops-action" onclick="closureDashDecide(this, {{ $request->id }}, 'decline')">Recusar</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted py-4 text-center">Nenhum pedido pendente.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
