@once
    @push('css')
        <style>
            .production-priority-modal .modal-dialog {
                max-width: min(720px, calc(100vw - 1.5rem));
            }

            .production-priority-modal .modal-content {
                border: 0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            }

            .production-priority-modal__header {
                align-items: flex-start;
                background: #123f43;
                color: #f8fafc;
                display: flex;
                gap: 1rem;
                justify-content: space-between;
                padding: 1rem 1.25rem;
            }

            .production-priority-modal__title {
                font-size: 1rem;
                font-weight: 800;
                line-height: 1.2;
                margin: 0;
                text-transform: uppercase;
            }

            .production-priority-modal__close {
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 6px;
                color: #ffffff;
                height: 34px;
                line-height: 1;
                min-width: 34px;
            }

            .production-priority-modal__close:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .production-priority-modal__body {
                background: #f8fafc;
                padding: 1rem 1.25rem 1.15rem;
            }

            .production-priority-modal__summary {
                background: #ffffff;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                overflow: hidden;
            }

            .production-priority-modal__summary-header {
                align-items: center;
                background: #fff7ed;
                border-bottom: 1px solid #fed7aa;
                display: flex;
                gap: 0.5rem;
                justify-content: space-between;
                padding: 0.75rem 0.9rem;
            }

            .production-priority-modal__summary-title {
                color: #9a3412;
                font-size: 0.82rem;
                font-weight: 800;
                margin: 0;
                text-transform: uppercase;
            }

            .production-priority-modal__badge {
                align-items: center;
                background: #fee2e2;
                border-radius: 999px;
                color: #991b1b;
                display: inline-flex;
                font-size: 0.72rem;
                font-weight: 800;
                gap: 0.25rem;
                padding: 0.25rem 0.55rem;
                text-transform: uppercase;
            }

            .production-priority-modal__facts {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .production-priority-modal__fact {
                border-bottom: 1px solid #edf2f7;
                display: grid;
                gap: 0.15rem;
                min-width: 0;
                padding: 0.7rem 0.9rem;
            }

            .production-priority-modal__fact:nth-child(odd) {
                border-right: 1px solid #edf2f7;
            }

            .production-priority-modal__fact:nth-last-child(-n + 2) {
                border-bottom: 0;
            }

            .production-priority-modal__label {
                color: #64748b;
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
            }

            .production-priority-modal__value {
                color: #1f2937;
                font-size: 0.86rem;
                font-weight: 700;
                line-height: 1.25;
                min-width: 0;
                overflow-wrap: anywhere;
            }

            .production-priority-modal__note {
                color: #b91c1c;
                font-size: 1rem;
                font-weight: 900;
            }

            .production-priority-modal__form {
                margin-top: 1rem;
            }

            .production-priority-modal .form-label {
                color: #475569;
                font-size: 0.72rem;
                font-weight: 800;
                margin-bottom: 0.3rem;
                text-transform: uppercase;
            }

            .production-priority-modal .form-control {
                border-color: #cbd5e1;
                border-radius: 6px;
                color: #1f2937;
                min-height: 132px;
                resize: vertical;
            }

            .production-priority-modal__error {
                color: #dc2626;
                display: block;
                font-size: 0.76rem;
                font-weight: 700;
                margin-top: 0.3rem;
            }

            .production-priority-modal__footer {
                align-items: center;
                background: #ffffff;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 0.55rem;
                justify-content: flex-end;
                padding: 0.85rem 1.25rem;
            }

            .production-priority-modal__footer .btn {
                align-items: center;
                border-radius: 6px;
                display: inline-flex;
                font-size: 0.8rem;
                font-weight: 800;
                gap: 0.35rem;
                min-height: 36px;
            }

            @media (max-width: 640px) {
                .production-priority-modal__facts {
                    grid-template-columns: 1fr;
                }

                .production-priority-modal__fact,
                .production-priority-modal__fact:nth-child(odd),
                .production-priority-modal__fact:nth-last-child(-n + 2) {
                    border-bottom: 1px solid #edf2f7;
                    border-right: 0;
                }

                .production-priority-modal__fact:last-child {
                    border-bottom: 0;
                }

                .production-priority-modal__footer {
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .production-priority-modal__footer .btn {
                    justify-content: center;
                    width: 100%;
                }
            }
        </style>
    @endpush
@endonce

<div>
    <div wire:ignore.self class="modal fade production-priority-modal" id="set_priority" tabindex="-1"
        aria-labelledby="set_priority_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="production-priority-modal__header">
                    <h5 class="production-priority-modal__title" id="set_priority_label">
                        Definir prioridade {{ $production ? '- '.$production->service->service : '' }}
                    </h5>
                    <button type="button" class="production-priority-modal__close" data-bs-dismiss="modal"
                        aria-label="Fechar" wire:click.prevent="closeAll">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="production-priority-modal__body">
                    @if ($production)
                        <div class="production-priority-modal__summary">
                            <div class="production-priority-modal__summary-header">
                                <h6 class="production-priority-modal__summary-title">
                                    <i class="ri-alarm-warning-line"></i>
                                    Produção selecionada
                                </h6>
                                <span class="production-priority-modal__badge">
                                    <i class="ri-flag-2-line"></i>
                                    Prioridade
                                </span>
                            </div>

                            <div class="production-priority-modal__facts">
                                <div class="production-priority-modal__fact">
                                    <span class="production-priority-modal__label">Note</span>
                                    <span class="production-priority-modal__value production-priority-modal__note">
                                        {{ $production->note->note }}
                                    </span>
                                </div>
                                <div class="production-priority-modal__fact">
                                    <span class="production-priority-modal__label">Município</span>
                                    <span class="production-priority-modal__value">{{ $production->note->lexp ?: '---' }}</span>
                                </div>
                                <div class="production-priority-modal__fact">
                                    <span class="production-priority-modal__label">Rubrica</span>
                                    <span class="production-priority-modal__value">{{ $production->note->rubrica ?: '---' }}</span>
                                </div>
                                <div class="production-priority-modal__fact">
                                    <span class="production-priority-modal__label">Grupo 4</span>
                                    <span class="production-priority-modal__value">{{ $production->note->group4 ?: '---' }}</span>
                                </div>
                                <div class="production-priority-modal__fact">
                                    <span class="production-priority-modal__label">Descrição</span>
                                    <span class="production-priority-modal__value">{{ $production->note->material ?: '---' }}</span>
                                </div>
                                <div class="production-priority-modal__fact">
                                    <span class="production-priority-modal__label">Usuário atual</span>
                                    <span class="production-priority-modal__value">{{ $production->user?->name ?: '---' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="production-priority-modal__form">
                            <label class="form-label" for="priority_reason">
                                Motivo da prioridade <span class="text-danger">*</span>
                            </label>
                            <textarea id="priority_reason" class="form-control" wire:model.defer="priority_reason" rows="5"
                                placeholder="Informe o motivo da prioridade"></textarea>
                            @error('priority_reason')
                                <span class="production-priority-modal__error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>

                <div class="production-priority-modal__footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" wire:click.prevent="closeAll">
                        <i class="ri-close-line"></i>
                        Cancelar
                    </button>
                    <button class="btn btn-danger" wire:loading.attr="disabled" wire:click="executeSetPriority">
                        <span wire:loading.remove wire:target="executeSetPriority">
                            <i class="ri-flag-2-line"></i>
                            Atribuir
                        </span>
                        <span wire:loading wire:target="executeSetPriority">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Salvando
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
