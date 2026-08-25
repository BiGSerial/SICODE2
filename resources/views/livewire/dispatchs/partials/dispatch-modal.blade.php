@once
    @push('css')
        <style>
            .dispatch-modal .modal-dialog {
                max-width: min(980px, calc(100vw - 1.5rem));
            }

            .dispatch-modal [x-cloak] {
                display: none !important;
            }

            .dispatch-modal .modal-content {
                border: 0;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
            }

            .dispatch-modal__header {
                background: #123f43;
                color: #f8fafc;
                padding: 1rem 1.25rem;
            }

            .dispatch-modal__title {
                font-size: 1rem;
                font-weight: 700;
                margin: 0;
            }

            .dispatch-modal__subtitle {
                color: rgba(248, 250, 252, 0.74);
                font-size: 0.78rem;
                margin-top: 0.15rem;
            }

            .dispatch-modal__body {
                background: #f8fafc;
                padding: 1rem 1.25rem;
            }

            .dispatch-modal__controls {
                display: grid;
                grid-template-columns: minmax(180px, 0.75fr) minmax(220px, 1fr);
                gap: 0.75rem;
                align-items: end;
            }

            .dispatch-modal__user-controls {
                display: grid;
                grid-template-columns: minmax(180px, 0.75fr) minmax(220px, 1fr);
                gap: 0.75rem;
                grid-column: 1 / -1;
            }

            .dispatch-modal .form-label {
                color: #334155;
                font-size: 0.75rem;
                font-weight: 700;
                margin-bottom: 0.3rem;
                text-transform: uppercase;
            }

            .dispatch-modal .form-control,
            .dispatch-modal .form-select {
                border-color: #cbd5e1;
                border-radius: 6px;
                min-height: 38px;
            }

            .dispatch-modal__type-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.35rem;
                background: #e2e8f0;
                border-radius: 8px;
                padding: 0.25rem;
            }

            .dispatch-modal__type-group.is-single {
                grid-template-columns: 1fr;
            }

            .dispatch-modal__type-option {
                align-items: center;
                border-radius: 6px;
                color: #334155;
                cursor: pointer;
                display: inline-flex;
                font-size: 0.86rem;
                font-weight: 700;
                gap: 0.4rem;
                justify-content: center;
                min-height: 36px;
                margin: 0;
            }

            .dispatch-modal .btn-check:checked + .dispatch-modal__type-option {
                background: #ffffff;
                color: #0f766e;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.14);
            }

            .dispatch-modal__summary {
                align-items: center;
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                justify-content: space-between;
                margin: 1rem 0 0.6rem;
            }

            .dispatch-modal__count {
                color: #0f172a;
                font-weight: 800;
                letter-spacing: 0.01em;
            }

            .dispatch-modal__hint {
                color: #64748b;
                font-size: 0.78rem;
                font-weight: 600;
            }

            .dispatch-modal__loading {
                align-items: center;
                color: #0f766e;
                display: inline-flex;
                font-size: 0.74rem;
                font-weight: 700;
                gap: 0.35rem;
                margin-bottom: 0.3rem;
            }

            .dispatch-modal__loading-field {
                opacity: 0.62;
            }

            .dispatch-modal__table-wrap {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                max-height: min(42vh, 420px);
                overflow: auto;
            }

            .dispatch-modal__table {
                margin: 0;
            }

            .dispatch-modal__table thead th {
                background: #eef2f7;
                border-bottom: 1px solid #dbe3ef;
                color: #475569;
                font-size: 0.72rem;
                position: sticky;
                text-transform: uppercase;
                top: 0;
                z-index: 1;
            }

            .dispatch-modal__table td {
                color: #1f2937;
                font-size: 0.86rem;
                vertical-align: middle;
            }

            .dispatch-modal__note {
                font-weight: 800;
                white-space: nowrap;
            }

            .dispatch-modal__material {
                min-width: 220px;
            }

            .dispatch-modal__dd {
                min-width: 170px;
            }

            .dispatch-modal__empty {
                background: #ffffff;
                border: 1px dashed #cbd5e1;
                border-radius: 8px;
                color: #64748b;
                padding: 1rem;
                text-align: center;
            }

            .dispatch-modal__footer {
                align-items: center;
                background: #ffffff;
                border-top: 1px solid #e2e8f0;
                display: flex;
                gap: 0.5rem;
                justify-content: flex-end;
                padding: 0.85rem 1.25rem;
            }

            .dispatch-modal__footer .btn {
                border-radius: 6px;
                font-weight: 700;
                min-height: 38px;
                min-width: 112px;
            }

            @media (max-width: 767.98px) {
                .dispatch-modal__controls,
                .dispatch-modal__user-controls {
                    grid-template-columns: 1fr;
                }

                .dispatch-modal__footer {
                    align-items: stretch;
                    flex-direction: column-reverse;
                }

                .dispatch-modal__footer .btn {
                    width: 100%;
                }
            }
        </style>
    @endpush
@endonce

<div wire:ignore.self class="modal fade dispatch-modal" id="add_mass_notes" tabindex="-1"
    aria-labelledby="dispatchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="dispatch-modal__header d-flex align-items-start justify-content-between gap-3">
                <div>
                    <h1 class="dispatch-modal__title" id="dispatchModalLabel">Despachar {{ $service->service }}</h1>
                    <div class="dispatch-modal__subtitle">
                        {{ $notes && $notes->count() ? $notes->count() . ' OV/Nota(s) selecionada(s)' : 'Nenhuma OV/Nota selecionada' }}
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    wire:click.prevent="closeAll"
                    aria-label="Fechar"></button>
            </div>

            <div class="dispatch-modal__body">
                @if ($notes && $notes->count())
                    <div class="dispatch-modal__controls">
                        <div>
                            <label class="form-label">Destino</label>
                            <div class="dispatch-modal__type-group {{ $contractMode ? 'is-single' : '' }}">
                                @unless ($contractMode)
                                    <input class="btn-check" type="radio" name="dispatch_type_{{ $service->uuid }}"
                                        id="dispatch_type_stack_{{ $service->uuid }}" value="1"
                                        wire:model="type" wire:change="dispatchTypeChanged($event.target.value)">
                                    <label class="dispatch-modal__type-option"
                                        for="dispatch_type_stack_{{ $service->uuid }}"
                                        wire:click="dispatchTypeChanged('1')">
                                        <i class="ri-stack-line"></i>
                                        Pilha
                                    </label>
                                @endunless

                                <input class="btn-check" type="radio" name="dispatch_type_{{ $service->uuid }}"
                                    id="dispatch_type_user_{{ $service->uuid }}" value="2"
                                    wire:model="type" wire:change="dispatchTypeChanged($event.target.value)">
                                <label class="dispatch-modal__type-option"
                                    for="dispatch_type_user_{{ $service->uuid }}"
                                    wire:click="dispatchTypeChanged('2')">
                                    <span wire:loading.remove wire:target="dispatchTypeChanged,loadDispatchUsers">
                                        <i class="ri-user-follow-line"></i>
                                        Individual
                                    </span>
                                    <span wire:loading wire:target="dispatchTypeChanged,loadDispatchUsers">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                        Carregando
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Empresa</label>
                            <select class="form-select" wire:model.defer="company_s"
                                wire:change="dispatchCompanyChanged($event.target.value)">
                                <option value="">Selecione uma empresa</option>
                                @if ($company_l && $company_l->count())
                                    @foreach ($company_l as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        @if ($type === '2')
                            <div class="dispatch-modal__user-controls">
                                <div>
                                    <label class="form-label">Buscar Usuario</label>
                                    <div class="input-group">
                                        <input wire:model.defer="search_user" wire:keydown.enter.prevent="loadDispatchUsers"
                                            class="form-control" type="text" placeholder="Digite um nome">
                                        <button class="btn btn-outline-secondary" type="button"
                                            wire:click.prevent="loadDispatchUsers" title="Buscar usuario"
                                            aria-label="Buscar usuario">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <label class="form-label">Usuario</label>
                                        <span class="dispatch-modal__loading" wire:loading
                                            wire:target="dispatchTypeChanged,dispatchCompanyChanged,loadDispatchUsers">
                                            <span class="spinner-border spinner-border-sm" role="status"
                                                aria-hidden="true"></span>
                                            Carregando lista
                                        </span>
                                    </div>
                                    <div wire:loading.class="dispatch-modal__loading-field"
                                        wire:target="dispatchTypeChanged,dispatchCompanyChanged,loadDispatchUsers">
                                        <select class="form-select" wire:model.defer="user_s"
                                            wire:loading.attr="disabled"
                                            wire:target="dispatchTypeChanged,dispatchCompanyChanged,loadDispatchUsers">
                                            @if ($user_l && $user_l->count())
                                                <option value="">Selecione um usuario</option>
                                                @foreach ($user_l as $user)
                                                    <option wire:key="dispatch-user-{{ $user->id }}"
                                                        value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            @else
                                                <option value="">{{ $company_s ? 'Nenhum usuario encontrado' : 'Escolha uma empresa primeiro' }}</option>
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="dispatch-modal__summary">
                        <div class="dispatch-modal__count">Itens para despacho</div>
                        <div class="dispatch-modal__hint">DD deve ser preenchida quando a regra da atividade exigir.</div>
                    </div>

                    <div class="dispatch-modal__table-wrap">
                        <table class="table table-sm table-hover dispatch-modal__table">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-center" style="width:52px;">#</th>
                                    <th scope="col">Nota/OV</th>
                                    <th scope="col">Descricao</th>
                                    <th scope="col">DD</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($notes as $index => $note)
                                    <tr>
                                        <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                        <td class="dispatch-modal__note">{{ $note->note }}</td>
                                        <td class="dispatch-modal__material">{{ $note->material }}</td>
                                        <td class="dispatch-modal__dd">
                                            <input wire:model.defer="additionalData.{{ $index }}"
                                                class="form-control form-control-sm" type="text"
                                                placeholder="DD, se aplicavel">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dispatch-modal__empty">Nenhum item selecionado para despacho.</div>
                @endif
            </div>

            <div class="dispatch-modal__footer">
                <button class="btn btn-outline-secondary" wire:click.prevent="closeAll">Cancelar</button>
                <button class="btn btn-primary" wire:click.prevent="confirmAtt" wire:loading.attr="disabled"
                    wire:target="confirmAtt" @disabled(!$notes || !$notes->count())>
                    <span wire:loading.remove wire:target="confirmAtt">Despachar</span>
                    <span wire:loading wire:target="confirmAtt">Enviando...</span>
                </button>
            </div>
        </div>
    </div>
</div>
