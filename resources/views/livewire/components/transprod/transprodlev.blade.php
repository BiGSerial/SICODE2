<div>
    <!-- Modal Transferencia de Produção -->
    <div wire:ignore.self class="modal fade" id="transfer_modal" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content transfer-modal">
                <div class="modal-header transfer-modal__header">
                    <div>
                        <div class="transfer-modal__eyebrow">Transferência de titularidade</div>
                        <h1 class="modal-title transfer-modal__title" id="exampleModalLabel">Produção
                            {{ $production ? $production->Note->note : '' }}</h1>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body transfer-modal__body">
                    @if ($transfer_view)
                        <div class="transfer-modal__notice">
                            <i class="ri-information-line"></i>
                            <div>
                                <strong>A titularidade e os tempos desta produção serão creditados ao novo titular.</strong>
                                <span>Use a empresa para mostrar apenas usuários habilitados nesta atividade.</span>
                            </div>
                        </div>

                        <div class="transfer-modal__grid">
                            <div>
                                <label for="transfer_company_lev" class="form-label transfer-modal__label">Empresa</label>
                                <select id="transfer_company_lev" class="form-select transfer-modal__control"
                                    wire:model="company_transfer_id">
                                    <option value="">Todas as empresas habilitadas</option>
                                    @foreach ($company_list as $company)
                                        <option wire:key="transfer-company-lev-{{ $company->id }}" value="{{ $company->id }}">
                                            {{ $company->display_name ?? $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="transfer_search_lev" class="form-label transfer-modal__label">Buscar usuário</label>
                                <div class="transfer-modal__search">
                                    <i class="ri-search-line"></i>
                                    <input id="transfer_search_lev" type="text" class="form-control transfer-modal__control"
                                        wire:model.debounce.500ms="search" placeholder="Nome do usuário">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="transfer_user_lev" class="form-label transfer-modal__label">Novo titular</label>
                            <select id="transfer_user_lev" class="form-select transfer-modal__control"
                                wire:model.defer="user_transfer_id">
                                <option value="">Selecione um usuário habilitado</option>
                                @foreach ($user_list as $user)
                                    @php
                                        $nameParts = explode(' ', trim($user->name));
                                        $name = $nameParts[0] . ' ' . end($nameParts);
                                        $companyName = $user->Employee?->Contract?->company?->name
                                            ?? $user->Company?->name
                                            ?? $user->Companies?->first()?->name
                                            ?? 'Empresa não informada';
                                    @endphp
                                    <option wire:key="transfer-user-lev-{{ $user->id }}" value="{{ $user->id }}">
                                        {{ $name }} - {{ $companyName }}
                                    </option>
                                @endforeach
                            </select>
                            @if (!$user_list->count())
                                <div class="transfer-modal__empty">
                                    Nenhum usuário habilitado nesta atividade para os filtros selecionados.
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="transfer_reason_lev" class="form-label transfer-modal__label">Motivo <span
                                    class="text-danger fw-bold">*</span></label>
                            <textarea id="transfer_reason_lev" class="form-control transfer-modal__control" wire:model.defer="user_transfer_info"
                                placeholder="Informe o motivo da transferência" rows="5"></textarea>
                        </div>
                    @endif
                </div>
                <div class="modal-footer transfer-modal__footer">
                    <button type="button" class="btn transfer-modal__cancel" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn transfer-modal__submit" wire:click="transfer_prod">
                        <i class="ri-arrow-left-right-line me-1"></i> Solicitar
                        Transferência</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .transfer-modal {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(17, 24, 39, .28);
        }

        .transfer-modal__header {
            align-items: center;
            background: #0f4a50;
            border: 0;
            color: #43ff80;
            padding: 18px 22px;
        }

        .transfer-modal__eyebrow {
            color: rgba(255, 255, 255, .72);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .transfer-modal__title {
            color: #43ff80;
            font-size: 1.12rem;
            font-weight: 700;
            letter-spacing: 0;
            margin: 2px 0 0;
        }

        .transfer-modal__body {
            background: #f4f7f8;
            padding: 22px;
        }

        .transfer-modal__notice {
            align-items: flex-start;
            background: #0f4a50;
            border-left: 4px solid #43ff80;
            border-radius: 8px;
            color: #dfffe9;
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
            padding: 14px 16px;
        }

        .transfer-modal__notice i {
            color: #43ff80;
            font-size: 1.25rem;
            line-height: 1.2;
        }

        .transfer-modal__notice strong,
        .transfer-modal__notice span {
            display: block;
            line-height: 1.45;
        }

        .transfer-modal__notice span {
            color: rgba(255, 255, 255, .82);
            margin-top: 2px;
        }

        .transfer-modal__grid {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            margin-bottom: 14px;
        }

        .transfer-modal__label {
            color: #344054;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .transfer-modal__control {
            border: 1px solid #d0d7de;
            border-radius: 8px;
            color: #243447;
            min-height: 44px;
        }

        .transfer-modal__control:focus {
            border-color: #17666f;
            box-shadow: 0 0 0 .2rem rgba(23, 102, 111, .16);
        }

        .transfer-modal__search {
            position: relative;
        }

        .transfer-modal__search i {
            color: #667085;
            left: 13px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        .transfer-modal__search input {
            padding-left: 38px;
        }

        .transfer-modal__empty {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            color: #9a3412;
            font-size: .86rem;
            margin-top: 8px;
            padding: 10px 12px;
        }

        .transfer-modal__footer {
            background: #eef3f5;
            border-top: 1px solid #dce4e8;
            padding: 16px 22px;
        }

        .transfer-modal__cancel,
        .transfer-modal__submit {
            border-radius: 8px;
            font-weight: 700;
            min-height: 40px;
        }

        .transfer-modal__cancel {
            background: #1f6b72;
            color: #fff;
        }

        .transfer-modal__submit {
            background: #2845d9;
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .transfer-modal__grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</div>
