{{--
    "Buscar em massa" (cole várias Notas). Espera $bulkScope, $bulkNotes e $bulkReport.
    Mostra o botão, o estado ativo e o relatório de Notas que não apareceram (com o motivo).
--}}
<div class="d-flex flex-wrap align-items-center gap-2">
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quality-bulk-modal"><i class="ri-checkbox-multiple-blank-line"></i> Buscar em massa</button>
    @if (!empty($bulkNotes))
        <span class="badge text-bg-primary fs-6"><i class="ri-filter-3-line"></i> {{ count($bulkNotes) }} Nota(s) na busca</span>
        <form method="post" action="{{ route('quality.bulk-search') }}" class="d-inline">@csrf<input type="hidden" name="scope" value="{{ $bulkScope }}"><input type="hidden" name="clear" value="1"><button class="btn btn-sm btn-outline-secondary"><i class="ri-close-line"></i> Limpar busca em massa</button></form>
    @endif
</div>

@if (!empty($bulkNotes))
    @php $foundCount = count($bulkNotes) - count($bulkReport); @endphp
    <div class="alert {{ $bulkReport ? 'alert-warning' : 'alert-success' }} small py-2 mt-2 mb-0">
        <strong>{{ $foundCount }} de {{ count($bulkNotes) }}</strong> Nota(s) encontradas nesta lista.
        @if ($bulkReport)
            <details class="mt-1">
                <summary class="fw-bold" style="cursor: pointer;">{{ count($bulkReport) }} não apareceram — ver motivo</summary>
                <ul class="mb-0 mt-1" style="max-height: 180px; overflow-y: auto;">
                    @foreach (array_slice($bulkReport, 0, 200) as $item)<li><strong>{{ $item['note'] }}</strong> — {{ $item['reason'] }}</li>@endforeach
                </ul>
                @if (count($bulkReport) > 200)<div class="text-muted">e mais {{ count($bulkReport) - 200 }}…</div>@endif
            </details>
        @endif
    </div>
@endif

@once
    <div class="modal fade" id="quality-bulk-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="post" action="{{ route('quality.bulk-search') }}" class="modal-content">
                @csrf
                <input type="hidden" name="scope" value="{{ $bulkScope }}">
                <div class="modal-header">
                    <div><h5 class="modal-title"><i class="ri-checkbox-multiple-blank-line me-1"></i> Buscar em massa</h5><div class="small text-muted">Cole as Notas separadas por linha, espaço, vírgula ou ponto e vírgula (até 2.000).</div></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold" for="quality-bulk-notes">Notas</label>
                    <textarea class="form-control" id="quality-bulk-notes" name="notes" rows="10" placeholder="Ex.:&#10;15116145&#10;15005764&#10;15709963">{{ implode("\n", $bulkNotes ?? []) }}</textarea>
                    <div class="form-text">A busca também aceita o número do pedido. Notas que não aparecerem serão listadas com o motivo.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary"><i class="ri-search-line"></i> Buscar</button>
                </div>
            </form>
        </div>
    </div>
@endonce
