<div>
    <div wire:ignore.self class="modal fade five-modal" id="fiveNoteModal" tabindex="-1"
        aria-labelledby="fiveNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content five-card">

                <!-- Cabeçalho -->
                <div class="modal-header five-header">
                    <h5 class="modal-title" id="fiveNoteModalLabel">
                        Detalhes da D5 — {{ $five && $five->note_d5 ? $five->note_d5 : 'Número Não Gerado' }}
                        <div class="five-row five-muted">
                            <i class="ri-time-line"></i>
                            <span>{{ $five?->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- Corpo -->
                <div class="modal-body five-body">
                    @if ($five)
                        <!-- Grid de informações principais -->
                        <div class="five-grid">
                            <div>
                                <p class="five-label">Local Instalação</p>
                                <p class="five-text">{{ $five->loc_install ?? '---' }}</p>
                            </div>
                            <div>
                                <p class="five-label">Conjunto</p>
                                <p class="five-text">{{ $five->conjunto ?? '---' }}</p>
                            </div>
                            <div>
                                <p class="five-label">PEP</p>
                                <p class="five-text">{{ $five->pep ?? '---' }}</p>
                            </div>
                            <div>
                                <p class="five-label">Empresa</p>
                                <p class="five-text">{{ $five->company->name ?? '---' }}</p>
                            </div>
                            <div>
                                <p class="five-label">Causa</p>
                                <p class="five-text">{{ $five->reason ?? '---' }}</p>
                            </div>
                            <div>
                                <p class="five-label">Motivo</p>
                                <p class="five-text">{{ $five->codify ?? '---' }}</p>
                            </div>

                            <!-- Detalhes: ocupa toda a largura do grid -->
                            <div class="five-grid-full">
                                <p class="five-label mb-1">Detalhes</p>
                                <div class="five-details-box">
                                    {{ $five->description ?? '---' }}
                                </div>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="five-section">
                            <h6 class="five-title">Histórico de Produções</h6>
                            <div class="five-timeline">
                                @forelse($five->productions as $p)
                                    @php
                                        $dotClass = 'is-assigned';

                                        if ((bool) $p->completed || (int) $p->status === 5) {
                                            $dotClass = 'is-finished';
                                        } elseif ((int) $p->status === 4) {
                                            $dotClass = 'is-paused';
                                        }
                                    @endphp
                                    <div class="five-timeline-item">
                                        <div class="five-timeline-dot {{ $dotClass }}"></div>
                                        <div class="five-timeline-content">
                                            <div class="five-row">
                                                <span class="five-k">Serviço:</span>
                                                <span class="five-v">{{ $p->service?->service }}</span>
                                            </div>
                                            <div class="five-row">
                                                <span class="five-k">Responsável:</span>
                                                <span class="five-v">{{ $p->user?->name }}</span>
                                            </div>
                                            @if (!empty($p->analise))
                                                <div class="five-row">
                                                    <span class="five-k">Resultado:</span>
                                                    <div class="five-details">{{ $p->analise->conclusion }}</div>
                                                </div>
                                            @endif
                                            <div class="five-row five-muted">
                                                <i class="ri-time-line"></i>
                                                <span>{{ $p->created_at?->format('d/m/Y H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="five-empty">Nenhuma produção relacionada.</p>
                                @endforelse
                            </div>
                        </div>

                        <!-- Galeria -->
                        <div class="five-section">
                            <h6 class="five-title">Galeria de Arquivos</h6>
                            {{-- @livewire('components.files.galeria', ['model' => $five]) --}}
                        </div>
                    @else
                        <p class="five-empty text-center">Nenhuma informação carregada.</p>
                    @endif
                </div>

                <!-- Rodapé -->
                <div class="modal-footer five-footer">
                    <button type="button" class="five-btn" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ==== FIVE MODAL (escopo isolado) ==== */
        .five-modal .five-card {
            background: linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border: 0;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5);
        }

        .five-modal .five-header {
            background: rgba(31, 41, 55, .95);
            border: 0;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }

        .five-modal .five-header .modal-title {
            color: #f9fafb;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .five-modal .btn-close {
            filter: invert(1);
            opacity: .9;
        }

        .five-modal .five-body {
            padding: 1.25rem 1.25rem 1rem;
        }

        /* Grid infos */
        .five-modal .five-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .five-modal .five-grid-full {
            grid-column: 1 / -1;
        }

        .five-modal .five-label {
            color: #9ca3af;
            margin: 0;
            font-size: .82rem;
            font-weight: 600;
        }

        .five-modal .five-text {
            margin: 2px 0 0;
            color: #f3f4f6;
            font-weight: 500;
        }

        /* Caixa de detalhes (full width) */
        .five-modal .five-details-box {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .10);
            border-radius: 12px;
            padding: 12px 14px;
            color: #e5e7eb;
            line-height: 1.6;
            /* suporta texto longo com rolagem */
            max-height: 40vh;
            overflow: auto;
            /* rolagem agradável */
            scrollbar-width: thin;
        }

        .five-modal .five-details-box::-webkit-scrollbar {
            width: 8px;
        }

        .five-modal .five-details-box::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .18);
            border-radius: 8px;
        }

        /* Section */
        .five-modal .five-section {
            margin-top: 18px;
        }

        .five-modal .five-title {
            color: #f9fafb;
            font-weight: 700;
            margin: 0 0 10px;
        }

        /* Timeline */
        .five-modal .five-timeline {
            position: relative;
            padding-left: 14px;
        }

        .five-modal .five-timeline:before {
            content: "";
            position: absolute;
            left: 6px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: rgba(255, 255, 255, .16);
        }

        .five-modal .five-timeline-item {
            position: relative;
            margin: 0 0 16px 0;
        }

        .five-modal .five-timeline-dot {
            position: absolute;
            left: -1px;
            top: 4px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .five-modal .five-timeline-dot.is-assigned {
            background: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
        }

        .five-modal .five-timeline-dot.is-paused {
            background: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, .2);
        }

        .five-modal .five-timeline-dot.is-finished {
            background: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, .18);
        }

        .five-modal .five-timeline-content {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 10px 12px;
        }

        .five-modal .five-row {
            display: flex;
            gap: 6px;
            align-items: center;
            line-height: 1.4;
        }

        .five-modal .five-k {
            color: #9ca3af;
            min-width: 96px;
            font-weight: 600;
        }

        .five-modal .five-v {
            color: #e5e7eb;
            font-weight: 500;
        }

        .five-modal .five-muted {
            color: #9ca3af;
            font-size: .85rem;
            margin-top: 4px;
            gap: 6px;
        }

        .five-modal .five-details {
            margin-top: 6px;
            color: #d1d5db;
        }

        .five-modal .five-empty {
            color: #9ca3af;
            margin: 8px 0;
        }

        /* Footer */
        .five-modal .five-footer {
            background: rgba(31, 41, 55, .95);
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .08);
            border-bottom-left-radius: 16px;
            border-bottom-right-radius: 16px;
        }

        .five-modal .five-btn {
            background: #374151;
            color: #f3f4f6;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 6px 14px;
            font-weight: 600;
        }

        .five-modal .five-btn:hover {
            background: #4b5563;
        }

        /* Responsivo */
        @media (max-width: 576px) {
            .five-modal .five-grid {
                grid-template-columns: 1fr;
            }

            .five-modal .five-details-box {
                max-height: 45vh;
            }
        }
    </style>
</div>
