<div>
    <style>
        .work-acceptance-modal .acceptance-summary {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .work-acceptance-modal .acceptance-card {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            padding: 0.9rem 1rem;
        }

        .work-acceptance-modal .acceptance-label {
            color: #64748b;
            display: block;
            font-size: 0.74rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
        }

        .work-acceptance-modal .acceptance-value {
            color: #1f2937;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .work-acceptance-modal .acceptance-term {
            background: #f8fafc;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            color: #334155;
            line-height: 1.65;
            padding: 1rem 1.1rem;
        }

        .work-acceptance-modal .acceptance-term p:last-child,
        .work-acceptance-modal .acceptance-term ul:last-child,
        .work-acceptance-modal .acceptance-term ol:last-child {
            margin-bottom: 0;
        }

        .work-acceptance-modal .signature-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: 1.1fr 0.8fr 1fr;
        }

        .work-acceptance-modal .hash-value {
            color: #be185d;
            display: block;
            font-size: 0.78rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        @media (max-width: 767.98px) {
            .work-acceptance-modal .acceptance-summary,
            .work-acceptance-modal .signature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div wire:ignore.self class="modal fade" id="workAcceptanceInfoModal" tabindex="-1"
        aria-labelledby="workAcceptanceInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content work-acceptance-modal">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title" id="workAcceptanceInfoModalLabel">Detalhes do Aceite do Informe</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if ($workReport)
                        <div class="acceptance-summary mb-3">
                            <div class="acceptance-card">
                                <span class="acceptance-label">Número da obra</span>
                                <div class="acceptance-value">{{ $workReport->Note->note ?? '---' }}</div>
                            </div>
                            <div class="acceptance-card">
                                <span class="acceptance-label">Empresa</span>
                                <div class="acceptance-value">{{ $workReport->Company->name ?? '---' }}</div>
                            </div>
                            <div class="acceptance-card">
                                <span class="acceptance-label">Usuário SICODE</span>
                                <div class="acceptance-value">{{ $workReport->User->name ?? '---' }}</div>
                                <small class="text-muted">{{ $workReport->User->email ?? '' }}</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <span class="acceptance-label">Termo de aceite informado pelo parceiro</span>
                            <div class="acceptance-term">{!! $acceptedHtml !!}</div>
                        </div>

                        <div class="signature-grid mb-3">
                            <div class="acceptance-card">
                                <span class="acceptance-label">Nome do aceite</span>
                                <div class="acceptance-value">{{ $workReport->acceptance_name ?: '---' }}</div>
                            </div>
                            <div class="acceptance-card">
                                <span class="acceptance-label">Aceite</span>
                                @if ($workReport->acceptance_accepted)
                                    <span class="badge text-bg-success">ACEITO</span>
                                @else
                                    <span class="badge text-bg-secondary">NÃO ACEITO</span>
                                @endif
                            </div>
                            <div class="acceptance-card">
                                <span class="acceptance-label">Quando</span>
                                <div class="acceptance-value">{{ $workReport->acceptance_at ? $workReport->acceptance_at->format('d/m/Y H:i') : '---' }}</div>
                            </div>
                        </div>

                        <div class="acceptance-card">
                            <div class="row g-3 align-items-start">
                                <div class="col-md-4">
                                    <span class="acceptance-label">Nome assinado</span>
                                    <div class="acceptance-value">{{ $signature['signed_name'] ?? ($workReport->acceptance_name ?: '---') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <span class="acceptance-label">Data assinada</span>
                                    <div class="acceptance-value">
                                        @if (!empty($signature['signed_at']))
                                            {{ \Carbon\Carbon::parse($signature['signed_at'])->format('d/m/Y H:i:s') }}
                                        @elseif ($workReport->acceptance_at)
                                            {{ $workReport->acceptance_at->format('d/m/Y H:i:s') }}
                                        @else
                                            ---
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <span class="acceptance-label">Algoritmo</span>
                                    <div class="acceptance-value">{{ $signature['hash_algorithm'] ?? '---' }}</div>
                                </div>
                                <div class="col-12">
                                    <span class="acceptance-label">Hash da assinatura</span>
                                    <code class="hash-value">{{ $signature['hash'] ?? '---' }}</code>
                                </div>
                            </div>
                        </div>

                        @if (!empty($workReport->acceptance_meta))
                            <div class="text-end mt-3">
                                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                                    data-bs-target="#acceptanceMetaDetails">
                                    Ver metadados
                                </button>
                            </div>

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
