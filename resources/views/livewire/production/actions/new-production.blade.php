@once
    @push('css')
        <style>
            .production-edit-modal .modal-dialog {
                max-width: min(760px, calc(100vw - 1.5rem));
            }

            .production-edit-modal .modal-content {
                border: 0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            }

            .production-edit-modal__header {
                align-items: flex-start;
                background: #123f43;
                color: #f8fafc;
                display: flex;
                gap: 1rem;
                justify-content: space-between;
                padding: 1rem 1.25rem;
            }

            .production-edit-modal__title {
                font-size: 1rem;
                font-weight: 800;
                line-height: 1.2;
                margin: 0;
                text-transform: uppercase;
            }

            .production-edit-modal__close {
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 6px;
                color: #ffffff;
                height: 34px;
                line-height: 1;
                min-width: 34px;
            }

            .production-edit-modal__close:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .production-edit-modal__body {
                background: #f8fafc;
                padding: 1rem 1.25rem 1.15rem;
            }

            .production-edit-modal__summary {
                background: #ffffff;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                overflow: hidden;
            }

            .production-edit-modal__summary-header {
                align-items: center;
                background: #eef2f7;
                border-bottom: 1px solid #dbe3ef;
                display: flex;
                gap: 0.5rem;
                justify-content: space-between;
                padding: 0.75rem 0.9rem;
            }

            .production-edit-modal__summary-title {
                color: #0f172a;
                font-size: 0.82rem;
                font-weight: 800;
                margin: 0;
                text-transform: uppercase;
            }

            .production-edit-modal__status {
                align-items: center;
                background: #dbeafe;
                border-radius: 999px;
                color: #1e40af;
                display: inline-flex;
                font-size: 0.72rem;
                font-weight: 800;
                gap: 0.25rem;
                padding: 0.25rem 0.55rem;
                text-transform: uppercase;
            }

            .production-edit-modal__facts {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .production-edit-modal__fact {
                border-bottom: 1px solid #edf2f7;
                display: grid;
                gap: 0.15rem;
                min-width: 0;
                padding: 0.7rem 0.9rem;
            }

            .production-edit-modal__fact:nth-child(odd) {
                border-right: 1px solid #edf2f7;
            }

            .production-edit-modal__fact:nth-last-child(-n + 2) {
                border-bottom: 0;
            }

            .production-edit-modal__label {
                color: #64748b;
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
            }

            .production-edit-modal__value {
                color: #1f2937;
                font-size: 0.86rem;
                font-weight: 700;
                line-height: 1.25;
                min-width: 0;
                overflow-wrap: anywhere;
            }

            .production-edit-modal__note {
                color: #0f766e;
                font-size: 1rem;
                font-weight: 900;
            }

            .production-edit-modal__section {
                margin-top: 1rem;
            }

            .production-edit-modal__section-title {
                align-items: center;
                color: #334155;
                display: flex;
                font-size: 0.78rem;
                font-weight: 800;
                gap: 0.35rem;
                margin-bottom: 0.55rem;
                text-transform: uppercase;
            }

            .production-edit-modal__controls {
                display: grid;
                gap: 0.75rem;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .production-edit-modal .form-label {
                color: #475569;
                font-size: 0.72rem;
                font-weight: 800;
                margin-bottom: 0.3rem;
                text-transform: uppercase;
            }

            .production-edit-modal .form-control,
            .production-edit-modal .form-select {
                border-color: #cbd5e1;
                border-radius: 6px;
                color: #1f2937;
                min-height: 40px;
            }

            .production-edit-modal__toggle {
                align-items: center;
                background: #ffffff;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                display: flex;
                gap: 0.7rem;
                grid-column: 1 / -1;
                min-height: 48px;
                padding: 0.65rem 0.85rem;
            }

            .production-edit-modal__toggle .form-check-input {
                height: 1.05rem;
                margin: 0;
                width: 1.05rem;
            }

            .production-edit-modal__toggle-label {
                color: #1f2937;
                font-size: 0.86rem;
                font-weight: 800;
                margin: 0;
            }

            .production-edit-modal__error {
                color: #dc2626;
                display: block;
                font-size: 0.76rem;
                font-weight: 700;
                margin-top: 0.3rem;
            }

            .production-edit-modal__footer {
                align-items: center;
                background: #ffffff;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 0.55rem;
                justify-content: flex-end;
                padding: 0.85rem 1.25rem;
            }

            .production-edit-modal__footer .btn {
                align-items: center;
                border-radius: 6px;
                display: inline-flex;
                font-size: 0.8rem;
                font-weight: 800;
                gap: 0.35rem;
                min-height: 36px;
            }

            @media (max-width: 640px) {
                .production-edit-modal__facts,
                .production-edit-modal__controls {
                    grid-template-columns: 1fr;
                }

                .production-edit-modal__fact,
                .production-edit-modal__fact:nth-child(odd),
                .production-edit-modal__fact:nth-last-child(-n + 2) {
                    border-bottom: 1px solid #edf2f7;
                    border-right: 0;
                }

                .production-edit-modal__fact:last-child {
                    border-bottom: 0;
                }

                .production-edit-modal__footer {
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .production-edit-modal__footer .btn {
                    justify-content: center;
                    width: 100%;
                }
            }
        </style>
    @endpush
@endonce

<div>
    <div wire:ignore.self class="modal fade production-edit-modal" id="edit_production" tabindex="-1"
        aria-labelledby="edit_production_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="production-edit-modal__header">
                    <div>
                        <h5 class="production-edit-modal__title" id="edit_production_label">
                            Editar {{ $production?->note->note }} em {{ $production ? $production->service->service : '' }}
                        </h5>
                    </div>
                    <button type="button" class="production-edit-modal__close" data-bs-dismiss="modal"
                        wire:click.prevent="closeall" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="production-edit-modal__body">
                    @if ($production)
                        <div class="production-edit-modal__summary">
                            <div class="production-edit-modal__summary-header">
                                <h6 class="production-edit-modal__summary-title">
                                    <i class="ri-file-list-3-line"></i>
                                    Dados atuais
                                </h6>
                                @if ($production->d5)
                                    <span class="production-edit-modal__status">
                                        <i class="ri-loop-left-line"></i>
                                        Retorno interno
                                    </span>
                                @endif
                            </div>

                            <div class="production-edit-modal__facts">
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Note</span>
                                    <span class="production-edit-modal__value production-edit-modal__note">
                                        {{ $production->note->note }}
                                    </span>
                                </div>
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Município</span>
                                    <span class="production-edit-modal__value">{{ $production->note->lexp ?: '---' }}</span>
                                </div>
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Rubrica</span>
                                    <span class="production-edit-modal__value">{{ $production->note->rubrica ?: '---' }}</span>
                                </div>
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Grupo 4</span>
                                    <span class="production-edit-modal__value">{{ $production->note->group4 ?: '---' }}</span>
                                </div>
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Descrição</span>
                                    <span class="production-edit-modal__value">{{ $production->note->material ?: '---' }}</span>
                                </div>
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Atribuído em</span>
                                    <span class="production-edit-modal__value">
                                        {{ $production->att_at ? $production->att_at->format('d/m/Y') : '---' }}
                                    </span>
                                </div>
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Usuário atual</span>
                                    <span class="production-edit-modal__value">{{ $production->user?->name ?: '---' }}</span>
                                </div>
                                <div class="production-edit-modal__fact">
                                    <span class="production-edit-modal__label">Empresa atual</span>
                                    <span class="production-edit-modal__value">{{ $production->company?->name ?: '---' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="production-edit-modal__section">
                            <div class="production-edit-modal__section-title">
                                <i class="ri-user-settings-line"></i>
                                Novo destino
                            </div>

                            <div class="production-edit-modal__controls">
                                <div>
                                    <label class="form-label" for="edit_production_company">Empresa</label>
                                    <select id="edit_production_company" class="form-select" wire:model="companySelected">
                                        <option value="" selected>Selecione</option>
                                        @if ($companies && $companies->count())
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('companySelected')
                                        <span class="production-edit-modal__error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="form-label" for="edit_production_user">Usuário</label>
                                    <select id="edit_production_user" class="form-select" wire:model.defer="userSelected">
                                        @if ($users && $users->count())
                                            <option value="" selected>Selecione um usuário</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        @else
                                            <option value="" selected>Escolha uma empresa primeiro</option>
                                        @endif
                                    </select>
                                    @error('userSelected')
                                        <span class="production-edit-modal__error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="production-edit-modal__toggle">
                                    <input class="form-check-input" type="checkbox" wire:model.defer="ri" id="ri_checkbox">
                                    <label class="production-edit-modal__toggle-label" for="ri_checkbox">
                                        Retorno interno
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="production-edit-modal__footer">
                    <button type="button" class="btn btn-outline-secondary" wire:click.prevent="closeall"
                        data-bs-dismiss="modal">
                        <i class="ri-close-line"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="toCreateNewProduction"
                        wire:loading.attr="disabled">
                        <i class="ri-add-circle-line"></i>
                        Nova atribuição
                    </button>
                    <button type="button" class="btn btn-danger" wire:loading.attr="disabled"
                        wire:click="toTransferProduction">
                        <i class="ri-arrow-left-right-line"></i>
                        Transferir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
