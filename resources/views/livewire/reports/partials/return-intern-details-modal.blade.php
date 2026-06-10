<div class="modal fade ri-reason-modal" id="riReasonModal" tabindex="-1"
    aria-labelledby="riReasonModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="small text-white-50 text-uppercase fw-bold">Retorno interno</div>
                    <h5 class="modal-title" id="riReasonModalLabel">
                        Detalhes do retorno · {{ $returnDetails['note'] ?? 'Nota não informada' }}
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Fechar"></button>
            </div>

            <div class="modal-body p-4">
                @if ($returnDetails)
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <section class="ri-detail-section ri-detail-note h-100">
                                <div class="ri-detail-title">
                                    <span class="ri-detail-icon"><i class="ri-file-list-3-line"></i></span>
                                    Dados da nota
                                </div>
                                <div class="ri-detail-grid">
                                    <div><span>Nota/OV</span><strong>{{ $returnDetails['note'] }}</strong></div>
                                    <div>
                                        <span>Ordens</span>
                                        <strong>{{ count($returnDetails['orders']) ? implode(', ', $returnDetails['orders']) : '—' }}</strong>
                                    </div>
                                    <div><span>Status</span><strong>{{ $returnDetails['note_status_code'] }}</strong></div>
                                    <div><span>Situação</span><strong>{{ $returnDetails['note_status'] }}</strong></div>
                                    <div><span>Município</span><strong>{{ $returnDetails['municipality'] }}</strong></div>
                                    <div><span>Rubrica</span><strong>{{ $returnDetails['rubric'] }}</strong></div>
                                    <div class="ri-detail-wide">
                                        <span>Material</span><strong>{{ $returnDetails['material'] }}</strong>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="col-lg-6">
                            <section class="ri-detail-section ri-detail-return h-100">
                                <div class="ri-detail-title">
                                    <span class="ri-detail-icon"><i class="ri-arrow-go-back-line"></i></span>
                                    Retorno interno
                                </div>
                                <div class="ri-detail-grid">
                                    <div><span>RI</span><strong>#{{ $returnDetails['reclaim_id'] }}</strong></div>
                                    <div><span>Origem</span><strong>{{ $returnDetails['origin'] }}</strong></div>
                                    <div><span>Serviço</span><strong>{{ $returnDetails['service'] }}</strong></div>
                                    <div><span>Categoria</span><strong>{{ $returnDetails['category'] }}</strong></div>
                                    <div class="ri-detail-wide ri-detail-emphasis">
                                        <span>Motivo</span><strong>{{ $returnDetails['reason'] }}</strong>
                                    </div>
                                    <div><span>Criado em</span><strong>{{ $returnDetails['created_at'] }}</strong></div>
                                    <div>
                                        <span>Concluído em</span>
                                        <strong>{{ $returnDetails['completed_at'] ?? 'Em aberto' }}</strong>
                                    </div>
                                </div>
                            </section>
                        </div>

                        @if ($returnDetails['viability_return'])
                            <div class="col-12">
                                <section class="ri-detail-section ri-detail-viability">
                                    <div class="ri-detail-title">
                                        <span class="ri-detail-icon"><i class="ri-route-line"></i></span>
                                        Retorno de viabilidade
                                    </div>
                                    <div class="ri-detail-grid ri-detail-grid-four">
                                        <div>
                                            <span>Motivo</span>
                                            <strong>{{ $returnDetails['viability_return']['reason'] }}</strong>
                                        </div>
                                        <div class="ri-detail-emphasis">
                                            <span>Impacto</span>
                                            <strong>{{ $returnDetails['viability_return']['impact'] }}</strong>
                                        </div>
                                        <div class="ri-detail-emphasis">
                                            <span>Responsável</span>
                                            <strong>{{ $returnDetails['viability_return']['responsible'] }}</strong>
                                        </div>
                                        <div class="ri-detail-wide">
                                            <span>Descrição</span>
                                            <strong>{{ $returnDetails['viability_return']['description'] }}</strong>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        @endif

                        <div class="col-12">
                            <section class="ri-detail-section ri-detail-production">
                                <div class="ri-detail-title">
                                    <span class="ri-detail-icon"><i class="ri-tools-line"></i></span>
                                    Produção associada
                                </div>
                                @if ($returnDetails['production'])
                                    <div class="ri-detail-grid ri-detail-grid-four">
                                        <div><span>Produção</span><strong>#{{ $returnDetails['production']['id'] }}</strong></div>
                                        <div>
                                            <span>Status</span>
                                            <strong>
                                                <span class="badge {{ $returnDetails['production']['status_class'] }}">
                                                    {{ $returnDetails['production']['status'] }}
                                                </span>
                                            </strong>
                                        </div>
                                        <div class="ri-detail-emphasis"><span>Responsável</span><strong>{{ $returnDetails['production']['responsible'] }}</strong></div>
                                        <div><span>Empresa</span><strong>{{ $returnDetails['production']['company'] }}</strong></div>
                                        <div><span>Despachante</span><strong>{{ $returnDetails['production']['dispatcher'] }}</strong></div>
                                        <div><span>Despachada em</span><strong>{{ $returnDetails['production']['dispatch_at'] }}</strong></div>
                                        <div><span>Atuação em</span><strong>{{ $returnDetails['production']['att_at'] }}</strong></div>
                                        <div><span>Concluída em</span><strong>{{ $returnDetails['production']['completed_at'] }}</strong></div>
                                    </div>
                                @else
                                    <div class="ri-detail-empty">Este retorno não possui produção associada.</div>
                                @endif
                            </section>
                        </div>

                        <div class="col-lg-6">
                            <section class="ri-detail-section ri-detail-files h-100">
                                @php
                                    $fileServices = collect($returnDetails['files'])
                                        ->map(fn ($file) => [
                                            'id' => $file['service_id'],
                                            'name' => $file['service'],
                                        ])
                                        ->unique('id')
                                        ->sortBy('name')
                                        ->values();

                                    $filteredFiles = collect($returnDetails['files'])
                                        ->when(
                                            $fileServiceFilter !== '',
                                            fn ($files) => $files->where('service_id', $fileServiceFilter),
                                        )
                                        ->values();
                                @endphp
                                <div class="ri-detail-title">
                                    <span class="ri-detail-icon"><i class="ri-attachment-2"></i></span>
                                    Arquivos da nota
                                    <span class="ri-detail-count ms-auto">{{ $filteredFiles->count() }}</span>
                                </div>
                                <div class="ri-detail-file-filter">
                                    <label for="riFileServiceFilter">Serviço</label>
                                    <select id="riFileServiceFilter" class="form-select form-select-sm"
                                        wire:model="fileServiceFilter">
                                        <option value="">Todos os serviços</option>
                                        @foreach ($fileServices as $service)
                                            <option value="{{ $service['id'] }}">{{ $service['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @forelse ($filteredFiles as $file)
                                    <div class="ri-detail-file">
                                        <span class="ri-detail-file-type">{{ $file['extension'] ?: 'ARQ' }}</span>
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="fw-semibold text-truncate">{{ $file['name'] }}</div>
                                            <div class="small text-muted">{{ $file['service'] }}</div>
                                        </div>
                                        @if ($file['exists'])
                                            <button type="button" class="btn btn-sm ri-detail-download"
                                                wire:click="downloadReturnFile({{ $file['id'] }})"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadReturnFile({{ $file['id'] }})"
                                                title="Baixar arquivo">
                                                <i class="ri-download-2-line"></i>
                                                <span class="d-none d-sm-inline">Baixar</span>
                                            </button>
                                        @else
                                            <span class="ri-detail-missing" title="Arquivo físico não localizado">
                                                <i class="ri-file-warning-line"></i>
                                                <span class="d-none d-sm-inline">Indisponível</span>
                                            </span>
                                        @endif
                                    </div>
                                @empty
                                    <div class="ri-detail-empty">
                                        Nenhum arquivo encontrado para o serviço selecionado.
                                    </div>
                                @endforelse
                            </section>
                        </div>

                        <div class="col-lg-6">
                            <section class="ri-detail-section ri-detail-comments-card h-100">
                                <div class="ri-detail-title">
                                    <span class="ri-detail-icon"><i class="ri-chat-3-line"></i></span>
                                    Comentários
                                    <span class="ri-detail-count ms-auto">{{ count($returnDetails['comments']) }}</span>
                                </div>
                                <div class="ri-detail-comments">
                                    @forelse ($returnDetails['comments'] as $index => $comment)
                                        <article class="ri-detail-comment">
                                            <div class="d-flex justify-content-between gap-2 mb-2">
                                                <div>
                                                    <strong>#{{ $index + 1 }} · {{ $comment['author'] }}</strong>
                                                    @if ($comment['email'])
                                                        <div class="small text-muted">{{ $comment['email'] }}</div>
                                                    @endif
                                                </div>
                                                <time class="small text-muted text-nowrap">{{ $comment['created_at'] }}</time>
                                            </div>
                                            <div class="ri-detail-comment-message">{{ $comment['message'] }}</div>
                                        </article>
                                    @empty
                                        <div class="ri-detail-empty">Nenhum comentário registrado.</div>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4"
                    data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('css')
        <style>
            .ri-reason-modal .modal-dialog {
                height: calc(100vh - 2rem);
                margin-bottom: 1rem;
                margin-top: 1rem;
                max-height: calc(100vh - 2rem);
            }

            .ri-reason-modal .modal-content {
                max-height: 100%;
            }

            .ri-reason-modal .modal-content,
            .ri-reason-modal .modal-content * {
                font-family: var(--bs-body-font-family) !important;
            }

            .ri-reason-modal .modal-body {
                min-height: 0;
                overflow-y: auto;
            }

            .ri-reason-modal i[class^="ri-"],
            .ri-reason-modal i[class*=" ri-"] {
                font-family: "remixicon" !important;
            }

            .ri-detail-section {
                --section-accent: #315f87;
                --section-soft: #edf5fb;
                background: #fff;
                border: 1px solid var(--ri-border, #dce5ea);
                border-radius: .85rem;
                box-shadow: 0 8px 22px rgba(20, 40, 61, .06);
                overflow: hidden;
                position: relative;
            }

            .ri-detail-section::before {
                background: var(--section-accent);
                content: "";
                inset: 0 auto 0 0;
                position: absolute;
                width: 4px;
                z-index: 2;
            }

            .ri-detail-note {
                --section-accent: #2563a6;
                --section-soft: #edf5ff;
            }

            .ri-detail-return {
                --section-accent: #d97706;
                --section-soft: #fff7e8;
            }

            .ri-detail-viability {
                --section-accent: #7c3aed;
                --section-soft: #f5f0ff;
            }

            .ri-detail-production {
                --section-accent: #16836f;
                --section-soft: #ebf8f4;
            }

            .ri-detail-files {
                --section-accent: #0e7490;
                --section-soft: #ecfeff;
            }

            .ri-detail-comments-card {
                --section-accent: #536471;
                --section-soft: #f1f5f7;
            }

            .ri-detail-title {
                align-items: center;
                background: var(--section-soft);
                border-bottom: 1px solid var(--ri-border, #dce5ea);
                color: var(--section-accent);
                display: flex;
                font-size: .82rem;
                font-weight: 750;
                gap: .65rem;
                letter-spacing: .04em;
                padding: .8rem 1rem .8rem 1.15rem;
                text-transform: uppercase;
            }

            .ri-detail-icon {
                align-items: center;
                background: var(--section-accent);
                border-radius: .55rem;
                color: #fff;
                display: inline-flex;
                flex: 0 0 2rem;
                font-size: 1.05rem;
                height: 2rem;
                justify-content: center;
                width: 2rem;
            }

            .ri-detail-icon i {
                color: inherit;
                font-family: "remixicon" !important;
                font-style: normal;
                line-height: 1;
            }

            .ri-detail-count {
                align-items: center;
                background: #fff;
                border: 1px solid var(--section-accent);
                border-radius: 999px;
                color: var(--section-accent);
                display: inline-flex;
                font-size: .75rem;
                height: 1.65rem;
                justify-content: center;
                min-width: 1.65rem;
                padding: 0 .45rem;
            }

            .ri-detail-emphasis {
                background: var(--section-soft);
                box-shadow: inset 3px 0 0 var(--section-accent);
            }

            .ri-detail-emphasis strong {
                color: var(--section-accent);
                font-weight: 750;
            }

            .ri-detail-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .ri-detail-grid-four {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .ri-detail-grid>div {
                border-bottom: 1px solid #e8eef1;
                min-width: 0;
                padding: .75rem 1rem;
                transition: background-color .15s ease;
            }

            .ri-detail-grid>div:hover {
                background: var(--section-soft);
            }

            .ri-detail-grid>div:nth-last-child(-n+2) {
                border-bottom: 0;
            }

            .ri-detail-grid span:not(.badge) {
                color: #667684;
                display: block;
                font-size: .72rem;
                margin-bottom: .2rem;
            }

            .ri-detail-grid strong {
                color: #22303d;
                display: block;
                font-size: .92rem;
                overflow-wrap: anywhere;
            }

            .ri-detail-wide {
                grid-column: 1 / -1;
            }

            .ri-detail-file {
                align-items: center;
                border-bottom: 1px solid #e8eef1;
                display: flex;
                gap: .75rem;
                padding: .72rem 1rem .72rem 1.15rem;
                transition: background-color .15s ease;
            }

            .ri-detail-files {
                max-height: 430px;
            }

            .ri-detail-files .ri-detail-file-filter {
                position: sticky;
                top: 0;
                z-index: 2;
            }

            .ri-detail-files {
                overflow-y: auto;
            }

            .ri-detail-file-filter {
                align-items: center;
                background: #f8fbfc;
                border-bottom: 1px solid #e8eef1;
                display: grid;
                gap: .45rem;
                grid-template-columns: auto minmax(0, 1fr);
                padding: .65rem 1rem .65rem 1.15rem;
            }

            .ri-detail-file-filter label {
                color: #536471;
                font-size: .72rem;
                font-weight: 700;
                margin: 0;
                text-transform: uppercase;
            }

            .ri-detail-file-filter .form-select {
                border-color: #cbd9df;
                color: #22303d;
            }

            .ri-detail-file:hover {
                background: var(--section-soft);
            }

            .ri-detail-file:last-child {
                border-bottom: 0;
            }

            .ri-detail-file-type {
                background: var(--section-accent);
                border-radius: .4rem;
                color: #fff;
                flex: 0 0 auto;
                font-size: .65rem;
                font-weight: 750;
                letter-spacing: .04em;
                min-width: 2.7rem;
                padding: .32rem .45rem;
                text-align: center;
            }

            .ri-detail-download {
                align-items: center;
                background: var(--section-accent);
                border: 1px solid var(--section-accent);
                color: #fff;
                display: inline-flex;
                gap: .35rem;
                white-space: nowrap;
            }

            .ri-detail-download:hover,
            .ri-detail-download:focus {
                background: #095f75;
                border-color: #095f75;
                color: #fff;
            }

            .ri-detail-download i,
            .ri-detail-missing i {
                font-family: "remixicon" !important;
            }

            .ri-detail-missing {
                align-items: center;
                color: #b42318;
                display: inline-flex;
                font-size: .75rem;
                font-weight: 700;
                gap: .3rem;
                white-space: nowrap;
            }

            .ri-detail-comments {
                max-height: 340px;
                overflow-y: auto;
                padding: .75rem;
            }

            .ri-detail-comment {
                background: #fff;
                border: 1px solid #dce5ea;
                border-left: 3px solid var(--section-accent);
                border-radius: .7rem;
                box-shadow: 0 4px 12px rgba(20, 40, 61, .05);
                margin-bottom: .65rem;
                padding: .8rem;
            }

            .ri-detail-comment:last-child {
                margin-bottom: 0;
            }

            .ri-detail-comment-message {
                color: #344454;
                line-height: 1.55;
                white-space: pre-wrap;
            }

            .ri-detail-empty {
                color: #667684;
                padding: 1.25rem;
                text-align: center;
            }

            @media (max-width: 991px) {
                .ri-detail-grid-four {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 575px) {
                .ri-reason-modal .modal-dialog {
                    height: calc(100vh - 1rem);
                    margin: .5rem;
                    max-height: calc(100vh - 1rem);
                }

                .ri-detail-grid,
                .ri-detail-grid-four {
                    grid-template-columns: 1fr;
                }

                .ri-detail-grid>div:nth-last-child(-n+2) {
                    border-bottom: 1px solid #e8eef1;
                }

                .ri-detail-grid>div:last-child {
                    border-bottom: 0;
                }
            }
        </style>
    @endpush

    @push('script')
        <script>
            if (!window.__riReasonModalListener) {
                window.__riReasonModalListener = true;
                window.addEventListener('show-ri-reason-modal', function() {
                    const modalElement = document.getElementById('riReasonModal');
                    if (!modalElement) return;

                    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                    modal.show();
                });
            }
        </script>
    @endpush
@endonce
