<div>
    <div wire:ignore.self class="modal fade" id="workAcceptanceInfoModal" tabindex="-1"
        aria-labelledby="workAcceptanceInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="workAcceptanceInfoModalLabel">Detalhes do Aceite do Informe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($workReport)
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Numero da Obra</small>
                                <strong>{{ $workReport->Note->note ?? '---' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Empresa</small>
                                <strong>{{ $workReport->Company->name ?? '---' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block">Usuario SICODE</small>
                                <strong>{{ $workReport->User->name ?? '---' }}</strong>
                                <small class="text-muted d-block">{{ $workReport->User->email ?? '' }}</small>
                            </div>
                        </div>

	                        <div class="border rounded p-3 mb-3 bg-light">
	                            <p class="mb-2 fw-semibold">Termo de aceite informado pelo parceiro</p>
	                            <p class="text-muted mb-0" style="white-space: pre-line;">{{ $acceptedText }}</p>
	                        </div>

	                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <small class="text-muted d-block">Nome do aceite</small>
                                <strong>{{ $workReport->acceptance_name ?: '---' }}</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Aceite</small>
                                @if ($workReport->acceptance_accepted)
                                    <span class="badge text-bg-success">ACEITO</span>
                                @else
                                    <span class="badge text-bg-secondary">NAO ACEITO</span>
                                @endif
                            </div>
	                            <div class="col-md-3">
	                                <small class="text-muted d-block">Quando</small>
	                                <strong>{{ $workReport->acceptance_at ? $workReport->acceptance_at->format('d/m/Y H:i') : '---' }}</strong>
	                            </div>
	                            <div class="col-md-2">
	                                <small class="text-muted d-block">Hash</small>
	                                <code class="small">{{ $signature['hash'] ?? '---' }}</code>
	                            </div>
	                            <div class="col-md-12 text-md-end">
	                                @if (!empty($workReport->acceptance_meta))
	                                    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
	                                        data-bs-target="#acceptanceMetaDetails">
                                        Mais
                                    </button>
                                @endif
	                        </div>

	                        <div class="border rounded p-3 mt-3">
	                            <div class="row g-3">
	                                <div class="col-md-4">
	                                    <small class="text-muted d-block">Nome assinado</small>
	                                    <strong>{{ $signature['signed_name'] ?? ($workReport->acceptance_name ?: '---') }}</strong>
	                                </div>
	                                <div class="col-md-4">
	                                    <small class="text-muted d-block">Data assinada</small>
	                                    <strong>{{ $signature['signed_at'] ?? ($workReport->acceptance_at ? $workReport->acceptance_at->toIso8601String() : '---') }}</strong>
	                                </div>
	                                <div class="col-md-4">
	                                    <small class="text-muted d-block">Algoritmo</small>
	                                    <strong>{{ $signature['hash_algorithm'] ?? '---' }}</strong>
	                                </div>
	                            </div>
	                        </div>
                        </div>

                        @if (!empty($workReport->acceptance_meta))
                            <div class="collapse mt-3" id="acceptanceMetaDetails">
                                <div class="border rounded p-2">
                                    <small class="text-muted d-block mb-1">Registros de meta</small>
                                    <pre class="small bg-white border rounded p-2 mb-0" style="max-height:220px; overflow:auto;">{{ json_encode($workReport->acceptance_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="text-muted mb-0">Nenhum aceite disponível para exibição.</p>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
