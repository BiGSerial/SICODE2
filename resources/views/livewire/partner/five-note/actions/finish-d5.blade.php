<div class="finish-five">
    <x-show-loading />
    <div class="modal fade" id="finishD5Modal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content fivefx-card">
                <div class="modal-header fivefx-header">
                    <h6 class="modal-title">
                        <i class="ri-check-double-line me-1"></i>
                        Finalizar atividade
                        @if (!empty($five))
                            <span class="fivefx-pill ms-2">D5: {{ $five->note_d5 ?? '---' }}</span>
                        @endif
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"
                        wire:click="clearAll"></button>
                </div>

                <div class="modal-body fivefx-body">
                    @if (!empty($five))
                        <div class="fivefx-grid mb-3">
                            <div>
                                <div class="fivefx-k">Nota</div>
                                <div class="fivefx-v">{{ $five->note?->note ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Local de Instalacao</div>
                                <div class="fivefx-v">{{ $five->loc_install ?? '---' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Conjunto</div>
                                <div class="fivefx-v">{{ $five->conjunto ?? '---' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">PEP</div>
                                <div class="fivefx-v">{{ $five->pep ?? '---' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Empresa</div>
                                <div class="fivefx-v">{{ $five->company?->name ?? '---' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Motivo</div>
                                <div class="fivefx-v">{{ $five->reason ?? '---' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Codificacao</div>
                                <div class="fivefx-v">{{ $five->codify ?? '---' }}</div>
                            </div>
                            <div class="fivefx-col-span">
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <div class="fivefx-k">Detalhes</div>
                                    @if ($five->isPassive)
                                        <button type="button" class="btn btn-sm btn-outline-info fivefx-inline-btn"
                                            wire:click="startEditDescription">
                                            <i class="ri-edit-2-line me-1"></i> Editar
                                        </button>
                                    @endif
                                </div>
                                <div class="fivefx-long">{{ $five->description ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="fivefx-k">Despachado em</div>
                                <div class="fivefx-v">
                                    {{ optional($five->dispatch_at)->format('d/m/Y H:i') ?? '---' }}
                                </div>
                            </div>
                        </div>

                        @if ($five->isPassive && $editingDescription)
                            <div class="fivefx-passive mb-4">
                                <div class="fivefx-passive-header">
                                    <div>
                                        <div class="fivefx-k">Edicao do Passivo</div>
                                        <div class="fivefx-muted">Edite apenas os detalhes antes de encerrar.</div>
                                    </div>
                                    <span class="fivefx-passive-pill">Passivo</span>
                                </div>

                                <div class="mt-3">
                                    <label class="fivefx-k" for="description">Detalhes</label>
                                    <textarea id="description" class="form-control fivefx-field" rows="3" wire:model.defer="five.description"></textarea>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button class="btn btn-outline-light fivefx-btn" wire:click="cancelEditDescription">
                                        <i class="ri-close-line me-1"></i> Fechar
                                    </button>
                                    <button class="btn btn-outline-info fivefx-btn" wire:click="savePassiveDetails">
                                        <i class="ri-save-3-line me-1"></i> Salvar detalhes
                                    </button>
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <h6 class="fivefx-subtitle mb-2">
                                <i class="ri-attachment-2 me-1"></i> Evidencias anexadas
                            </h6>

                            @php $files = $five->EvidenceFiles ?? collect(); @endphp
                            <x-files.attachments :files="$files" :downloadAction="'dowloadFile'" :showHeader="false"
                                :card="false" />
                        </div>

                        <div class="mb-3">
                            @livewire('files.evidence.upload-evidence', ['five' => $five, 'type' => 'D5', 'origin' => 'EMPREITEIRA'], key('finish-d5-evidence-' . $five->id . '-' . $evidenceKey))

                            <div class="fivefx-info mt-3">
                                <ul class="mb-0">
                                    <li>Ao encerrar, registraremos a data/hora de conclusao.</li>
                                    <li>As evidencias anexadas ficarao vinculadas a esta D5.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <h6 class="fivefx-subtitle mb-2">
                                <i class="ri-team-line me-1"></i> Historico de Conclusoes
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
                                                            {{ $production->user?->name ?? '---' }}
                                                            @if ($production->User?->email)
                                                                <span class="teams-contact-icon"
                                                                    title="Entrar em contato"
                                                                    onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $production->User?->email }}', '_blank')">
                                                                    <i
                                                                        class="bx bxl-microsoft-teams fs-4 align-middle"></i>
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <div class="fivefx-completion-role">
                                                            {{ $production->service?->service ?? '---' }}</div>
                                                    </div>
                                                </div>
                                                <i class="ri-microsoft-line fivefx-teams-icon"></i>
                                            </div>
                                            <div class="fivefx-completion-body">
                                                <div class="fivefx-completion-service">
                                                    {{ $production->analise?->conclusion ?? '---' }}</div>
                                                <div class="five-tl-text mb-3">
                                                    {!! nl2br($production->analise?->info ?? '') !!}
                                                </div>
                                                <div class="fivefx-completion-date">
                                                    <i class="ri-calendar-check-line me-1"></i>
                                                    Concluido em
                                                    {{ $production->completed_at?->format('d/m/Y H:i') ?? '---' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="fivefx-empty">Sem historico de conclusoes.</div>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="responsibleName" class="form-label fivefx-k">
                                Responsavel pela Informacao <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control fivefx-field @error('five.name') is-invalid @enderror"
                                id="responsibleName" wire:model.bounce.1s="five.name"
                                placeholder="Digite o nome do responsavel">
                            @error('five.name')
                                <div class="invalid-feedback" style="color: #f87171;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="observations" class="form-label fivefx-k">Observacoes</label>
                            <textarea class="form-control fivefx-field @error('observations') is-invalid @enderror" id="observations"
                                wire:model.defer="observations" placeholder="Digite as observacoes" rows="4"></textarea>
                            @error('observations')
                                <div class="invalid-feedback" style="color: #f87171;">{{ $message }}</div>
                            @enderror
                        </div>
                    @else
                        <div class="fivefx-empty text-center">Nenhuma informacao carregada.</div>
                    @endif
                </div>

                <div class="modal-footer fivefx-footer">
                    <button class="btn btn-outline-light fivefx-btn" data-bs-dismiss="modal"
                        wire:click="clearAll">Cancelar</button>
                    <button class="btn btn-success fivefx-btn"
                        wire:click="finishD5"
                        @disabled($isSaving)
                        wire:loading.attr="disabled" wire:target="finishD5,toSave">
                        <i class="ri-checkbox-circle-line me-1"></i> Encerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('css')
    <style>
        .finish-five .fivefx-card {
            background: linear-gradient(145deg, #0f172a, #111827);
            color: #e5e7eb;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 24px 60px rgba(3, 7, 18, .55)
        }

        .finish-five .fivefx-header {
            background: rgba(15, 23, 42, .95);
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
            background: linear-gradient(135deg, #22d3ee, #0ea5e9);
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

        .finish-five .fivefx-field {
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 10px;
            color: #f3f4f6;
            padding: 10px 12px;
        }

        .finish-five .fivefx-field:focus {
            border-color: rgba(34, 211, 238, .6);
            box-shadow: 0 0 0 .2rem rgba(14, 165, 233, .18);
        }

        .finish-five .fivefx-passive {
            background: linear-gradient(135deg, rgba(249, 115, 22, .08), rgba(251, 191, 36, .08));
            border: 1px solid rgba(249, 115, 22, .25);
            border-radius: 14px;
            padding: 16px;
        }

        .finish-five .fivefx-passive-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .finish-five .fivefx-passive-pill {
            background: linear-gradient(135deg, #f97316, #facc15);
            color: #1f2937;
            font-weight: 700;
            border-radius: 999px;
            padding: .2rem .6rem;
            font-size: .75rem;
        }

        .finish-five .fivefx-inline-btn {
            border-radius: 999px;
            font-weight: 600;
        }

        .finish-five .fivefx-subtitle {
            color: #f9fafb;
            font-weight: 700
        }

        .finish-five .fivefx-empty {
            color: #9ca3af;
            background: rgba(255, 255, 255, .03);
            border: 1px dashed rgba(255, 255, 255, .08);
            border-radius: 10px;
            padding: 12px
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

        .finish-five .teams-contact-icon {
            cursor: pointer;
            display: inline-block;
            transition: all 0.3s ease;
            padding: 4px;
            border-radius: 4px;
        }

        .finish-five .teams-contact-icon:hover {
            background-color: rgba(0, 120, 212, 0.1);
            transform: scale(1.1);
        }

        .finish-five .teams-contact-icon:hover i {
            color: #0078d4 !important;
        }

        .finish-five .upload-zone {
            color: #e2e8f0;
            border-color: rgba(226, 232, 240, .55) !important;
        }

        .finish-five .upload-zone .text-primary {
            color: #93c5fd !important;
        }

        .finish-five .upload-zone .text-muted {
            color: #cbd5f5 !important;
        }

        .finish-five .upload-zone .ri-cloud-line {
            color: #93c5fd !important;
        }

        .finish-five .upload-zone.drag-over {
            border-color: #22c55e !important;
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.18) 0%, rgba(16, 185, 129, 0.22) 100%);
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.35), 0 12px 30px rgba(16, 185, 129, 0.25);
            transform: scale(1.02);
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
@endpush
