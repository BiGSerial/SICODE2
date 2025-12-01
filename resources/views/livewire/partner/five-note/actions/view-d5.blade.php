<div>
    <div class="modal fade finish finish-five" id="finishFiveModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content fivefx-card">
                {{-- HEADER --}}
                <div class="modal-header fivefx-header">
                    <h6 class="modal-title">
                        <i class="ri-check-double-line me-1"></i>
                        VISUALIZAR ATIVIDADE
                        @if (!empty($five))
                            <span class="fivefx-pill ms-2">D5: {{ $five->note_d5 ?? '—' }}</span>
                        @endif
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                {{-- BODY (só mostra conteúdo se existir $five) --}}
                <div class="modal-body fivefx-body">
                    @if (!empty($five))
                        {{-- Resumo compacto da D5 --}}
                        <div class="fivefx-grid mb-3">
                            <div>
                                <div class="fivefx-k">Local de Instalação</div>
                                <div class="fivefx-v">{{ $five->loc_install ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Conjunto</div>
                                <div class="fivefx-v">{{ $five->conjunto ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">PEP</div>
                                <div class="fivefx-v">{{ $five->pep ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Empresa</div>
                                <div class="fivefx-v">{{ $five->company->name ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Motivo</div>
                                <div class="fivefx-v">{{ $five->reason ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Codificação</div>
                                <div class="fivefx-v">{{ $five->codify ?? '—' }}</div>
                            </div>
                            <div class="fivefx-col-span">
                                <div class="fivefx-k">Detalhes</div>
                                <div class="fivefx-long">{{ $five->description ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Despachado em</div>
                                <div class="fivefx-v">
                                    {{ optional($five->dispatch_at)->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </div>
                        </div>

                        {{-- Evidências já anexadas --}}
                        <div class="mb-3">
                            <h6 class="fivefx-subtitle mb-2">
                                <i class="ri-attachment-2 me-1"></i> Evidências anexadas
                            </h6>

                            @php $files = $five->EvidenceFiles ?? collect(); @endphp
                            <x-files.attachments :files="$files" :downloadAction="'dowloadFile'" :showHeader="false"
                                :card="false" />
                        </div>

                        {{-- Área para anexar novos arquivos (plugue seu componente aqui se quiser) --}}
                        <div class="mb-3">
                            <h6 class="fivefx-subtitle mb-2">
                                <i class="ri-team-line me-1"></i> Histórico de Conclusões
                            </h6>

                            <div class="fivefx-completion-list">
                                @if ($five?->productions?->isNotEmpty())
                                    @foreach ($five->productions as $production)
                                        <div class="fivefx-completion-card">
                                            <div class="fivefx-completion-header">
                                                <div class="fivefx-completion-user">
                                                    <i class="ri-user-3-line fivefx-completion-avatar"></i>
                                                    <div>
                                                        <div class="fivefx-completion-name">
                                                            {{ $production->user->name }}
                                                            @if ($production->User?->email)
                                                                <span class="teams-contact-icon"
                                                                    title="Entrar em contato"
                                                                    onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $production->User?->email }}', '_blank')">
                                                                    <i
                                                                        class="bx bxl-microsoft-teams fs-4 align-middle"></i>
                                                                </span>

                                                                <style>
                                                                    .teams-contact-icon {
                                                                        cursor: pointer;
                                                                        display: inline-block;
                                                                        transition: all 0.3s ease;
                                                                        padding: 4px;
                                                                        border-radius: 4px;
                                                                    }

                                                                    .teams-contact-icon:hover {
                                                                        background-color: rgba(0, 120, 212, 0.1);
                                                                        transform: scale(1.1);
                                                                    }

                                                                    .teams-contact-icon:hover i {
                                                                        color: #0078d4 !important;
                                                                    }
                                                                </style>
                                                            @endif
                                                        </div>
                                                        <div class="fivefx-completion-role">
                                                            {{ $production->service?->service }}</div>
                                                    </div>
                                                </div>
                                                <i class="ri-microsoft-line fivefx-teams-icon"></i>
                                            </div>
                                            <div class="fivefx-completion-body">
                                                <div class="fivefx-completion-service">
                                                    {{ $production->analise?->conclusion }}</div>
                                                <div class="five-tl-text mb-3">
                                                    {!! nl2br($production->analise?->info) !!}
                                                </div>
                                                <div class="fivefx-completion-date">
                                                    <i class="ri-calendar-check-line me-1"></i>
                                                    Concluído em {{ $production->completed_at?->format('d/m/Y H:i') }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <style>
                                .finish-five .fivefx-completion-list {
                                    display: flex;
                                    flex-direction: column;
                                    gap: 12px;
                                }

                                .finish-five .fivefx-completion-card {
                                    background: rgba(255, 255, 255, .06);
                                    border: 1px solid rgba(255, 255, 255, .12);
                                    border-radius: 12px;
                                    padding: 16px;
                                    transition: all 0.3s ease;
                                }

                                .finish-five .fivefx-completion-card:hover {
                                    background: rgba(255, 255, 255, .08);
                                    transform: translateY(-2px);
                                    box-shadow: 0 8px 25px rgba(0, 0, 0, .3);
                                }

                                .finish-five .fivefx-completion-header {
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                    margin-bottom: 12px;
                                }

                                .finish-five .fivefx-completion-user {
                                    display: flex;
                                    align-items: center;
                                    gap: 12px;
                                }

                                .finish-five .fivefx-completion-avatar {
                                    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
                                    color: white;
                                    width: 40px;
                                    height: 40px;
                                    border-radius: 50%;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 18px;
                                }

                                .finish-five .fivefx-completion-name {
                                    color: #f3f4f6;
                                    font-weight: 700;
                                    font-size: 0.95rem;
                                }

                                .finish-five .fivefx-completion-role {
                                    color: #9ca3af;
                                    font-size: 0.82rem;
                                    font-weight: 500;
                                }

                                .finish-five .fivefx-teams-icon {
                                    color: #0ea5e9;
                                    font-size: 24px;
                                    opacity: 0.8;
                                    transition: opacity 0.3s ease;
                                }

                                .finish-five .fivefx-completion-card:hover .fivefx-teams-icon {
                                    opacity: 1;
                                }

                                .finish-five .fivefx-completion-service {
                                    color: #e5e7eb;
                                    font-weight: 600;
                                    margin-bottom: 8px;
                                    font-size: 0.9rem;
                                }

                                .finish-five .fivefx-completion-date {
                                    color: #9ca3af;
                                    font-size: 0.82rem;
                                    font-weight: 500;
                                    display: flex;
                                    align-items: center;
                                }
                            </style>
                        </div>
                    @else
                        <div class="fivefx-empty text-center">Nenhuma informação carregada.</div>
                    @endif

                    {{-- Campo para nome do responsável --}}
                    <div class="mb-3">
                        <label for="responsibleName" class="form-label fivefx-k">Responsável pela Informação</label>
                        <input type="text" class="form-control" id="responsibleName" wire:model.bounce.1s="five.name"
                            placeholder="Digite o nome do responsável"
                            style="background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .08); border-radius: 10px; color: #f3f4f6; padding: 10px 12px;"
                            disabled>
                    </div>

                    <div class="mb-3">
                        <label for="responsibleName" class="form-label fivefx-k">Observações</label>
                        <div class="fivefx-completion-card">
                            {{ $five?->Comments?->last()?->message ?? 'Nenhuma Observação' }}
                        </div>
                    </div>
                </div>



                {{-- FOOTER --}}
                <div class="modal-footer fivefx-footer">
                    <button class="btn btn-outline-light fivefx-btn" data-bs-dismiss="modal">Cancelar</button>
                    {{-- Ligue este botão ao seu método Livewire --}}
                </div>
            </div>
        </div>
    </div>

    {{-- CSS ESCOPO EXCLUSIVO DO MODAL --}}
    <style>
        .finish-five .fivefx-card {
            background: linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5)
        }

        .finish-five .fivefx-header {
            background: rgba(31, 41, 55, .95);
            border: 0;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .08)
        }

        .finish-five .btn-close {
            filter: invert(1);
            opacity: .9
        }

        .finish-five .fivefx-body {
            padding: 1rem 1.25rem
        }

        .finish-five .fivefx-pill {
            display: inline-block;
            background: #0ea5e9;
            color: #fff;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-size: .77rem;
            font-weight: 700
        }

        .finish-five .fivefx-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px
        }

        .finish-five .fivefx-col-span {
            grid-column: 1/-1
        }

        .finish-five .fivefx-k {
            color: #9ca3af;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 2px
        }

        .finish-five .fivefx-v {
            color: #f3f4f6;
            font-weight: 600
        }

        .finish-five .fivefx-long {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 10px 12px;
            color: #d1d5db;
            white-space: pre-wrap
        }

        .finish-five .fivefx-subtitle {
            color: #f9fafb;
            font-weight: 700
        }

        .finish-five .fivefx-filelist {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 10px
        }

        .finish-five .fivefx-fileitem {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #e5e7eb;
            background: rgba(255, 255, 255, .03);
            border: 1px dashed rgba(255, 255, 255, .08);
            border-radius: 8px;
            padding: 6px 10px
        }

        .finish-five .fivefx-meta {
            color: #9ca3af;
            font-size: .82rem
        }

        .finish-five .fivefx-empty {
            color: #9ca3af;
            background: rgba(255, 255, 255, .03);
            border: 1px dashed rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 12px
        }

        .finish-five .fivefx-dropzone {
            background: rgba(59, 130, 246, .08);
            border: 1px dashed rgba(59, 130, 246, .35);
            border-radius: 12px;
            padding: 16px;
            text-align: center
        }

        .finish-five .fivefx-drophint {
            color: #e5e7eb;
            font-weight: 600
        }

        .finish-five .fivefx-browse {
            display: inline-block;
            margin-top: .35rem;
            background: #3b82f6;
            color: #fff;
            padding: .35rem .75rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700
        }

        .finish-five .fivefx-note {
            color: #9ca3af;
            font-size: .82rem;
            margin-top: .35rem
        }

        .finish-five .fivefx-muted {
            color: #9ca3af;
            font-weight: 500;
            font-size: .85rem
        }

        .finish-five .fivefx-info {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 10px;
            color: #d1d5db
        }

        .finish-five .fivefx-footer {
            background: rgba(31, 41, 55, .95);
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .08);
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px
        }

        .finish-five .fivefx-btn {
            font-weight: 700;
            border-radius: 10px
        }

        @media (max-width:576px) {
            .finish-five .modal-dialog {
                margin: .5rem
            }

            .finish-five .fivefx-grid {
                grid-template-columns: 1fr
            }
        }
    </style>
</div>
