<div>
    <x-show-loading />

    <style>
        .hiring-edit-modal .modal-content {
            border: 0;
            border-radius: .85rem;
            box-shadow: 0 22px 70px rgba(15, 23, 42, .28);
            overflow: hidden;
        }

        .hiring-edit-modal .modal-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 78%);
            color: #f8fafc;
            border: 0;
            padding: 1.15rem 1.5rem;
        }

        .hiring-edit-modal .modal-title {
            font-weight: 700;
            letter-spacing: .02em;
        }

        .hiring-edit-modal .modal-subtitle {
            color: rgba(248, 250, 252, .72);
            font-size: .82rem;
        }

        .hiring-edit-modal .modal-body {
            background: #f6f7fb;
            padding: 1.25rem;
        }

        .hiring-edit-modal .modal-footer {
            background: #ffffff;
            border-color: #e5e7eb;
            padding: 1rem 1.25rem;
        }

        .hiring-edit-modal .summary-card,
        .hiring-edit-modal .action-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: .75rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
        }

        .hiring-edit-modal .section-title {
            color: #0f172a;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .hiring-edit-modal .info-item {
            min-width: 0;
        }

        .hiring-edit-modal .info-label {
            color: #6b7280;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .hiring-edit-modal .info-value {
            color: #1f2937;
            font-size: .9rem;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .hiring-edit-modal .action-choice {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .hiring-edit-modal .action-choice .btn {
            border-radius: .65rem;
            min-height: 76px;
            padding: .85rem 1rem;
            text-align: left;
        }

        .hiring-edit-modal .btn-check:checked + .btn {
            background: #0f766e;
            border-color: #0f766e;
            color: #ffffff;
        }

        .hiring-edit-modal .choice-title {
            display: flex;
            align-items: center;
            gap: .45rem;
            font-weight: 700;
        }

        .hiring-edit-modal .choice-copy {
            display: block;
            font-size: .76rem;
            line-height: 1.25;
            margin-top: .3rem;
            opacity: .78;
        }

        @media (max-width: 767.98px) {
            .hiring-edit-modal .action-choice {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div wire:ignore.self class="modal fade" id="modal_edit_hiring" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered hiring-edit-modal">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-start">
                    <div class="d-flex flex-column">
                        @if ($isBulk)
                            <h4 class="modal-title fs-5 m-0">Contratação em massa</h4>
                            <span class="modal-subtitle">{{ count($ids) }} viabilidade(s) selecionada(s)</span>
                        @elseif ($viability)
                            <h4 class="modal-title fs-5 m-0">Contratação • {{ $viability->Note->note }}</h4>
                            <span class="modal-subtitle">
                                {{ $viability->Note->lexp ?? 'Município não informado' }}
                                @if ($viability->Note->rubrica)
                                    • {{ $viability->Note->rubrica }}
                                @endif
                            </span>
                        @else
                            <h4 class="modal-title fs-5 m-0">Editar viabilidade</h4>
                            <span class="modal-subtitle">Defina a operação e o novo destino</span>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    {{-- Dados da nota: só no modo individual --}}
                    @if (!$isBulk && $viability)
                        <div class="summary-card p-3 mb-3">
                            <div class="section-title mb-3 d-flex align-items-center gap-2">
                                <i class="ri-file-list-3-line text-primary"></i>
                                Dados da nota
                            </div>

                            <div class="row g-3">
                                <div class="col-6 col-lg-3 info-item">
                                    <div class="info-label">Nota/OV</div>
                                    <div class="info-value">{{ $viability->Note->note }}</div>
                                </div>
                                <div class="col-6 col-lg-3 info-item">
                                    <div class="info-label">Status</div>
                                    <div class="info-value">{{ $viability->Note->nstats }}</div>
                                </div>
                                <div class="col-12 col-lg-6 info-item">
                                    <div class="info-label">Situação</div>
                                    <div class="info-value">{{ $viability->Note->status }}</div>
                                </div>
                                <div class="col-6 col-lg-3 info-item">
                                    <div class="info-label">Município</div>
                                    <div class="info-value">{{ $viability->Note->lexp }}</div>
                                </div>
                                <div class="col-6 col-lg-3 info-item">
                                    <div class="info-label">Rubrica</div>
                                    <div class="info-value">{{ $viability->Note->rubrica }}</div>
                                </div>
                                <div class="col-12 col-lg-6 info-item">
                                    <div class="info-label">Material</div>
                                    <div class="info-value">{{ $viability->Note->material }}</div>
                                </div>
                                <div class="col-12 col-lg-6 info-item">
                                    <div class="info-label">Responsável atual</div>
                                    <div class="info-value">
                                        {{ optional($viability->Engineer)->name ? $viability->Engineer->name . " ({$viability->Engineer->email})" : '---' }}
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6 info-item">
                                    <div class="info-label">Parceira atual</div>
                                    <div class="info-value">{{ optional($viability->Company)->name ?? '---' }}</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Formulário de alteração (individual e massa) --}}
                    <div class="action-card p-3">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                            <div>
                                <div class="section-title d-flex align-items-center gap-2">
                                    <i class="ri-route-line text-primary"></i>
                                    @if ($newsend)
                                        {{ $isBulk ? 'Recontratar selecionados' : 'Recontratar viabilidade' }}
                                    @else
                                        {{ $isBulk ? 'Alterar selecionados' : 'Alterar viabilidade' }}
                                    @endif
                                </div>
                                <div class="text-muted small mt-1">
                                    @if ($newsend)
                                        Novo envio para a parceira com prazo reiniciado e histórico preservado.
                                    @else
                                        Atualização de parceira e responsável para viabilidades abertas.
                                    @endif
                                </div>
                            </div>
                            @if ($isBulk)
                                <span class="badge rounded-pill bg-primary-subtle text-primary align-self-start">
                                    {{ count($ids) }} selecionado(s)
                                </span>
                            @endif
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Ação</label>
                            <div class="action-choice" role="group" aria-label="Ação da viabilidade">
                                <div>
                                    <input type="radio" class="btn-check" name="hiringAction"
                                        id="hiringActionChange" value="0" wire:model="newsend">
                                    <label class="btn btn-outline-secondary w-100" for="hiringActionChange">
                                        <span class="choice-title">
                                            <i class="ri-arrow-left-right-line"></i>
                                            Alterar destino
                                        </span>
                                        <span class="choice-copy">
                                            Troca parceira e responsável da viabilidade aberta.
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <input type="radio" class="btn-check" name="hiringAction"
                                        id="hiringActionRehire" value="1" wire:model="newsend">
                                    <label class="btn btn-outline-secondary w-100" for="hiringActionRehire">
                                        <span class="choice-title">
                                            <i class="ri-repeat-line"></i>
                                            Recontratação
                                        </span>
                                        <span class="choice-copy">
                                            Cria novo envio sem alterar finalizações anteriores.
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <label class="form-label">Parceira</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="ri-building-4-line"></i>
                                    </span>
                                    <select class="form-select border-secondary"
                                        aria-label="Selecionar parceira" wire:model="companyS">
                                        <option value=""> --- </option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <label class="form-label">Responsável</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">
                                        <i class="ri-user-settings-line"></i>
                                    </span>
                                    <select class="form-select border-secondary"
                                        aria-label="Selecionar responsável" wire:model.defer="user_s">
                                        <option value=""> --- </option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        @if ($isBulk)
                            <div class="alert alert-info d-flex align-items-start gap-2 mt-3 mb-0">
                                <i class="ri-information-line fs-5"></i>
                                <div>
                                    <div class="fw-semibold">Aplicação em massa</div>
                                    <small>Todos os itens selecionados receberão a mesma parceira e o mesmo responsável.</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i>Fechar
                    </button>

                    @if (!$isBulk)
                        <button class="btn btn-primary" wire:click.prevent="toAlterViability()">
                            @if ($newsend)
                                <i class="ri-repeat-line me-1"></i>Criar recontratação
                            @else
                                <i class="ri-save-3-line me-1"></i>Salvar alteração
                            @endif
                        </button>
                    @else
                        <button class="btn btn-primary" wire:click.prevent="toAlterViability()">
                            @if ($newsend)
                                <i class="ri-repeat-line me-1"></i>Recontratar em massa
                            @else
                                <i class="ri-check-line me-1"></i>Aplicar em massa
                            @endif
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
