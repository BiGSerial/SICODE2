<div>
    <div wire:ignore.self class="modal fade" id="workCanceledInfoModal" tabindex="-1"
        aria-labelledby="workCanceledInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="workCanceledInfoModalLabel">
                        <i class="ri-close-circle-line align-middle"></i>
                        Informe cancelado — Nota {{ $note?->note }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger small">
                        Este informe foi cancelado sistemicamente. Não publique, fiscalize nem meça até a regularização.
                    </div>
                    @foreach ($items as $item)
                        <div class="border rounded p-3 mb-3" wire:key="canceled-{{ $item['id'] }}">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                <span class="badge text-bg-dark">Informe #{{ $item['id'] }}</span>
                                @foreach ($item['orders'] as $ordem)
                                    <span class="badge text-bg-light border">{{ $ordem }}</span>
                                @endforeach
                            </div>
                            <div class="row g-3 small">
                                <div class="col-md-4">
                                    <div class="text-muted text-uppercase fw-bold">Quem cancelou</div>
                                    <div>{{ $item['canceled_by'] ?? '-' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted text-uppercase fw-bold">Quando</div>
                                    <div>{{ $item['canceled_at'] ?? '-' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted text-uppercase fw-bold">Solicitante</div>
                                    <div>{{ $item['requested_by'] ?? '-' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted text-uppercase fw-bold">Categoria</div>
                                    <div>{{ $item['category'] ?? '-' }}</div>
                                </div>
                                <div class="col-md-8">
                                    <div class="text-muted text-uppercase fw-bold">Por quê</div>
                                    <div>{{ $item['reason'] ?: 'Motivo não registrado.' }}</div>
                                    @if ($item['closure_note'])
                                        <div class="text-muted mt-1">Parecer: {{ $item['closure_note'] }}</div>
                                    @endif
                                </div>
                            </div>
                            @if ($item['request_id'])
                                <div class="text-muted small mt-2">Solicitação de cancelamento #{{ $item['request_id'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
