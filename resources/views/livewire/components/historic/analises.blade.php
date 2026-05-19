@php
    $modalId = $isSingleton ? 'analiseModalSingleton' : 'analise-' . ($production->id ?? 0);
@endphp

<div>
    @if (!$isSingleton && $conclusion)
        <a href="#" class="link-secondary fw-bold"
           data-bs-toggle="modal" data-bs-target="#{{ $modalId }}"
           onclick="event.preventDefault()">{{ $conclusion }}</a>
    @endif

    @if ($isSingleton || $conclusion)
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content analise-modal-content">

                {{-- ── Header ── --}}
                <div class="analise-header">
                    <div class="analise-header-inner">
                        <div class="analise-header-badge">Análise Técnica</div>
                        <h5 class="analise-header-title">
                            {{ $production ? mb_strtoupper($production->Service->service) : '…' }}
                        </h5>
                        @if ($production)
                        <div class="analise-header-sub">
                            <span><i class="ri-file-text-line me-1"></i>Nota&nbsp;<strong>{{ $production->load('Note')->Note->note ?? '—' }}</strong></span>
                            <span class="analise-dot"></span>
                            <span>Produção&nbsp;<strong>#{{ $production->id }}</strong></span>
                        </div>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white align-self-start mt-1" data-bs-dismiss="modal"></button>
                </div>

                {{-- ── Body ── --}}
                <div class="modal-body p-0">

                    @if (!$production || !$conclusion)
                        <div class="analise-loading">
                            <span class="spinner-border text-secondary analise-spinner" role="status"></span>
                            <span class="text-muted small mt-2">Carregando…</span>
                        </div>
                    @else
                    @php
                        $longKeys  = ['Informação', 'Motivo', 'Restrição'];
                        $skipKeys  = ['production_id', 'Conclusão'];
                        $allFields = collect($exibition ?? [])
                            ->filter(fn($r) => !in_array($r['chave'], $skipKeys) && $r['valor'] !== null);
                        $tableFields = $allFields->reject(fn($r) => in_array($r['chave'], $longKeys));
                        $wideFields  = $allFields->filter(fn($r) => in_array($r['chave'], $longKeys));
                    @endphp

                        {{-- Conclusão --}}
                        <div class="analise-conclusion">
                            <div class="analise-conclusion-icon">
                                <i class="ri-shield-check-fill"></i>
                            </div>
                            <div>
                                <div class="analise-conclusion-label">Conclusão</div>
                                <div class="analise-conclusion-text">{{ $conclusion }}</div>
                            </div>
                        </div>

                        {{-- Tabela de campos --}}
                        @if ($tableFields->isNotEmpty())
                        <div class="analise-section">
                            <div class="analise-section-title">Dados Técnicos</div>
                            <div class="analise-fields-grid">
                                @foreach ($tableFields as $row)
                                @php $vals = preg_split('/<br\s*\/?>/i', (string) $row['valor']); @endphp
                                <div class="analise-field-row">
                                    <div class="analise-field-key">{{ $row['chave'] }}</div>
                                    <div class="analise-field-val">
                                        @foreach ($vals as $i => $v)@if($i)&nbsp;·&nbsp;@endif{{ $v }}@endforeach
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Campos longos --}}
                        @foreach ($wideFields as $row)
                        <div class="analise-section analise-section--wide">
                            <div class="analise-section-title">{{ $row['chave'] }}</div>
                            <div class="analise-wide-text">{!! nl2br(e($row['valor'])) !!}</div>
                        </div>
                        @endforeach

                    @endif
                </div>

                {{-- ── Footer ── --}}
                <div class="analise-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-4" data-bs-dismiss="modal">Fechar</button>
                </div>

            </div>
        </div>
    </div>
    @endif
</div>

<style>
    /* Modal wrapper */
    .analise-modal-content {
        border: none;
        border-radius: 14px;
        overflow: hidden;
        background: #f4f7fb;
        box-shadow: 0 20px 60px rgba(15,23,42,.18);
    }

    /* Header */
    .analise-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.15rem 1.4rem 1.1rem;
        background: linear-gradient(120deg, #0f172a 0%, #0f766e 60%, #225E66 100%);
        color: #f8fafc;
    }

    .analise-header-inner { display: flex; flex-direction: column; gap: .2rem; }

    .analise-header-badge {
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #5eead4;
    }

    .analise-header-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
    }

    .analise-header-sub {
        font-size: .8rem;
        color: rgba(248,250,252,.65);
        display: flex;
        align-items: center;
        gap: .4rem;
        flex-wrap: wrap;
    }

    .analise-dot {
        width: 3px; height: 3px;
        border-radius: 50%;
        background: rgba(248,250,252,.4);
        display: inline-block;
    }

    /* Loading */
    .analise-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4rem 1rem;
        gap: .5rem;
    }

    .analise-spinner { width: 2.2rem; height: 2.2rem; }

    /* Conclusão */
    .analise-conclusion {
        display: flex;
        align-items: center;
        gap: .9rem;
        margin: 1.1rem 1.25rem .5rem;
        padding: .85rem 1rem;
        border-radius: 10px;
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border: 1px solid #6ee7b7;
    }

    .analise-conclusion-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.2rem;
        height: 2.2rem;
        border-radius: 50%;
        background: #059669;
        color: #fff;
        font-size: 1.05rem;
        flex-shrink: 0;
    }

    .analise-conclusion-label {
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #065f46;
        margin-bottom: .1rem;
    }

    .analise-conclusion-text {
        font-size: .95rem;
        font-weight: 600;
        color: #064e3b;
        line-height: 1.3;
    }

    /* Sections */
    .analise-section {
        margin: .75rem 1.25rem;
    }

    .analise-section-title {
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: #64748b;
        padding-bottom: .35rem;
        margin-bottom: .45rem;
        border-bottom: 1px solid #dde5ef;
    }

    /* Grid 2-col para campos */
    .analise-fields-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        border: 1px solid #dde5ef;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
    }

    @media (max-width: 575px) {
        .analise-fields-grid { grid-template-columns: 1fr; }
    }

    .analise-field-row {
        display: flex;
        align-items: baseline;
        gap: .5rem;
        padding: .48rem .75rem;
        border-bottom: 1px solid #f1f5f9;
        background: #fff;
        transition: background .12s;
    }

    .analise-field-row:hover { background: #f8fafc; }

    .analise-field-row:nth-child(odd) { background: #fafbfd; }
    .analise-field-row:nth-child(odd):hover { background: #f3f6fa; }

    .analise-field-row:last-child,
    .analise-field-row:nth-last-child(2):nth-child(odd) {
        border-bottom: none;
    }

    .analise-field-key {
        font-size: .72rem;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
        min-width: 7rem;
        flex-shrink: 0;
    }

    .analise-field-val {
        font-size: .85rem;
        color: #1e293b;
        word-break: break-word;
        line-height: 1.4;
    }

    /* Wide text fields */
    .analise-section--wide .analise-wide-text {
        background: #fff;
        border: 1px solid #dde5ef;
        border-radius: 8px;
        padding: .7rem .9rem;
        font-size: .88rem;
        color: #1e293b;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    /* Footer */
    .analise-footer {
        display: flex;
        justify-content: flex-end;
        padding: .6rem 1.25rem .85rem;
        background: #f4f7fb;
        border-top: 1px solid #e2e8f0;
    }
</style>

@push('script')
    <script>
        if (!window.__analiseModalSingletonListener) {
            window.__analiseModalSingletonListener = true;
            window.addEventListener('show-analise-modal-singleton', function() {
                const modalEl = document.getElementById('analiseModalSingleton');
                if (!modalEl) return;
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.show();
            });
        }
    </script>
@endpush
