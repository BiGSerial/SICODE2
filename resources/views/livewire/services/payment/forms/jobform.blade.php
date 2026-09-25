@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <x-show-loading />
    @once
        <style>
            .payment-close-modal .modal-header {
                background: #123f43;
                border: 0;
                color: #f8fafc;
            }

            .payment-close-modal .modal-body {
                background: #eef4f5;
            }

            .payment-close-modal .close-form-card {
                background: #ffffff;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
                margin-bottom: 1rem;
                overflow: hidden;
            }

            .payment-close-modal .close-form-card__head {
                align-items: center;
                background: #ffffff;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                justify-content: space-between;
                padding: 0.85rem 1rem;
            }

            .payment-close-modal .close-form-card__title {
                align-items: center;
                color: #0f172a;
                display: inline-flex;
                font-size: 0.96rem;
                font-weight: 800;
                gap: 0.45rem;
                margin: 0;
            }

            .payment-close-modal .close-form-card__body {
                padding: 1rem;
            }

            .payment-close-modal .close-field .form-label {
                color: #334155;
                font-size: 0.74rem;
                font-weight: 800;
                letter-spacing: 0.02em;
                margin-bottom: 0.35rem;
                text-transform: uppercase;
            }

            .payment-close-modal .close-field .form-control,
            .payment-close-modal .close-field .form-select {
                border: 1px solid #cbd5e1 !important;
                border-radius: 6px;
                min-height: 40px;
            }

            .payment-close-modal .close-field textarea.form-control {
                min-height: 112px;
            }

            .payment-close-modal .close-required {
                color: #dc2626;
                font-weight: 900;
            }

            .payment-close-modal .close-help {
                color: #64748b;
                font-size: 0.76rem;
                font-weight: 600;
                margin-top: 0.28rem;
            }

            .payment-close-modal .close-action-bar {
                background: #ffffff;
                border: 1px solid #dbe3ef;
                border-radius: 8px;
                bottom: 0;
                box-shadow: 0 -8px 22px rgba(15, 23, 42, 0.10);
                margin-top: 1rem;
                padding: 0.75rem 0.9rem;
                position: sticky;
                z-index: 3;
            }

            .payment-close-modal .close-step {
                align-items: center;
                display: flex;
                gap: 0.55rem;
                min-width: min(100%, 280px);
            }

            .payment-close-modal .close-step__icon {
                align-items: center;
                background: #0f766e;
                border-radius: 7px;
                color: #ffffff;
                display: inline-flex;
                flex: 0 0 34px;
                font-size: 1rem;
                height: 34px;
                justify-content: center;
                width: 34px;
            }

            .payment-close-modal .close-step__icon.is-warning {
                background: #d97706;
            }

            .payment-close-modal .close-step__icon.is-ready {
                background: #16a34a;
            }

            .payment-close-modal .close-step__eyebrow {
                color: #0f766e;
                font-size: 0.64rem;
                font-weight: 900;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .payment-close-modal .close-step__text {
                color: #0f172a;
                font-size: 0.86rem;
                font-weight: 900;
                line-height: 1.2;
            }

            .payment-close-modal .close-step__message {
                color: #475569;
                font-size: 0.72rem;
                font-weight: 700;
                margin-top: 0.1rem;
            }

            .payment-close-modal .close-mini-alerts {
                align-items: center;
                display: flex;
                flex: 1 1 auto;
                flex-wrap: wrap;
                gap: 0.35rem;
                justify-content: center;
            }

            .payment-close-modal .close-step-pill {
                align-items: center;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 999px;
                color: #475569;
                display: inline-flex;
                font-size: 0.68rem;
                font-weight: 800;
                gap: 0.3rem;
                min-height: 24px;
                padding: 0.14rem 0.5rem;
            }

            .payment-close-modal .close-step-pill.is-warning {
                background: #fff7ed;
                border-color: #fdba74;
                color: #9a3412;
            }

            .payment-close-modal .close-action-bar .btn {
                align-items: center;
                border-radius: 6px;
                display: inline-flex;
                font-weight: 800;
                gap: 0.35rem;
                justify-content: center;
                min-height: 36px;
                min-width: 104px;
            }

            .payment-close-modal .close-action-bar .btn-close-finish {
                background: #16a34a;
                border-color: #16a34a;
                box-shadow: 0 8px 18px rgba(22, 163, 74, 0.20);
                min-width: 124px;
            }

            @media (max-width: 767.98px) {
                .payment-close-modal .close-action-bar {
                    position: static;
                }

                .payment-close-modal .close-action-bar .btn {
                    width: 100%;
                }
            }
        </style>
    @endonce

    <div wire:ignore.self class="modal fade payment-close-modal" id="formProductionModal" tabindex="-1"
        aria-labelledby="formProductionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            @if ($production)
                @php
                    // Helpers visuais iguais ao layout-base
                    $hasWorkForm = isset($production->Note->WorkForm);
                    $ordersWf = $hasWorkForm ? $production->Note->WorkForm->Orders : collect();
                    $hasPartial = $production->partial && optional($production->Note->Partials->last())->Orders;
                    $ordersPartial = $hasPartial ? $production->Note->Partials->last()->Orders : collect();
                    $orders = $ordersWf->count() ? $ordersWf : $ordersPartial;
                    $scopeBadge = collect($production->visibleWorkReportScopeBadges(\App\Models\WorkReportFlowProduction::STAGE_PAYMENT))->first() ?? ['label' => 'Geral', 'class' => 'text-bg-success'];
                    $scopeLabel = $scopeBadge['label'] ?? 'Geral';
                    $scopeClass = $scopeBadge['class'] ?? 'text-bg-success';
                @endphp

                <div class="modal-content">
                    {{-- HEADER (mesmo visual) --}}
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <div class="d-flex flex-column">
                            <h1 class="modal-title fs-5 m-0" id="formProductionModalLabel">
                                {{ mb_strtoupper($production->Service->service) }}
                                <span class="text-white-50 fw-normal"> • Nota/OV {{ $production->Note->note }}</span>
                                <span class="badge {{ $scopeClass }} ms-2">ESCOPO: {{ $scopeLabel }}</span>
                            </h1>
                            <small class="text-white-50">
                                Município: {{ $production->Note->lexp }} • Rubrica: {{ $production->Note->rubrica }}
                            </small>
                        </div>
                        <button type="button" class="btn-close btn-succes" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    {{-- BODY (mesma hierarquia de cards) --}}
                    <div class="modal-body edp-bg-stategrey-50">
                        <div class="container">

                            {{-- Resumo de informações (card slim) --}}
                            <div class="card shadow-sm mb-3 border-0 rounded-3">
                                <div class="card-body py-3">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-list-ordered-2 text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Ordens</div>
                                                    <div class="fw-semibold">
                                                        @if ($orders->count())
                                                            {{ $orders->pluck('ordem')->join(', ') }}
                                                        @else
                                                            —
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-calendar-check-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Data informada</div>
                                                    <div class="fw-semibold">
                                                        @if ($hasWorkForm)
                                                            {{ date('d/m/Y', strtotime($production->Note->WorkForm->date)) }}
                                                        @elseif ($production->partial)
                                                            {{ date('d/m/Y', strtotime(optional($production->Note->Partials->last())->created_at ?? now())) }}
                                                        @else
                                                            —
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-focus-3-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Escopo medido</div>
                                                    <div class="fw-semibold">
                                                        @forelse ($production->visibleWorkReportScopeBadges(\App\Models\WorkReportFlowProduction::STAGE_PAYMENT) as $scopeBadge)
                                                            <span class="badge {{ $scopeBadge['class'] }} fs-6 mb-1">{{ $scopeBadge['label'] }}</span>
                                                        @empty
                                                            <span class="badge text-bg-secondary">Geral</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-calendar-event-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Data SICODE</div>
                                                    <div class="fw-semibold">
                                                        @if ($hasWorkForm)
                                                            {{ date('d/m/Y H:i:s', strtotime($production->Note->WorkForm->informed_at)) }}
                                                        @elseif ($production->partial)
                                                            Não aplica
                                                        @else
                                                            —
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-user-settings-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Responsável Execução</div>
                                                    <div class="fw-semibold">
                                                        @if ($hasWorkForm)
                                                            {{ $production->Note->WorkForm->responsible }}
                                                        @elseif ($production->partial)
                                                            {{ optional($production->Note->Partials->last())->responsible ?? '—' }}
                                                        @else
                                                            —
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            @if ($five && !$five->is_supervisioned)
                                <div class="card shadow-sm mb-3 border-0 rounded-3">
                                    <div class="card-header py-2 bg-white border-0">
                                        <h5 class="m-0 d-flex align-items-center gap-2">
                                            <i class="ri-file-text-line text-primary"></i> NOTA D5
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning border-warning mb-3" role="alert">
                                            <div class="d-flex align-items-start gap-3">
                                                <i class="ri-alert-line text-warning fs-4 mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <h6 class="alert-heading mb-2">
                                                        <i class="ri-information-line me-1"></i>
                                                        Atenção: Conferência Obrigatória das Informações D5
                                                    </h6>
                                                    <p class="mb-2">
                                                        <strong>Antes de encerrar este processo, verifique
                                                            cuidadosamente todas as informações da Nota D5.</strong>
                                                    </p>
                                                    <ul class="mb-2 ps-3">
                                                        <li>Confirme se o <strong>número da D5</strong> está correto
                                                        </li>
                                                        <li>Verifique se a <strong>empresa selecionada</strong>
                                                            corresponde à execução</li>
                                                        <li>Confira os dados de <strong>localização e conjunto</strong>
                                                        </li>
                                                        <li>Revise a <strong>descrição</strong> do apontamento</li>
                                                    </ul>
                                                    <div class="text-danger small fw-semibold">
                                                        <i class="ri-error-warning-line me-1"></i>
                                                        Após o encerramento, não será possível retornar ou alterar estas
                                                        informações.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">

                                            <div class="col-md-3">
                                                <label class="form-label">Número da D5 <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control border border-secondary"
                                                    wire:model.defer="five.note_d5" placeholder="Insira o numero da D5"
                                                    @disabled($five->is_supervisioned)>
                                                @error('five.note_d5')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Empresa <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select border border-secondary"
                                                    wire:model.defer="five.company_id" @disabled($five->is_supervisioned)>
                                                    <option value="">Selecione...</option>
                                                    @foreach ($companies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('five.company_id')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Motivo D5 <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select border border-secondary"
                                                    wire:model.defer="five.reason" @disabled(true)>
                                                    <option value="">Selecione...</option>
                                                    @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                                        <option value="{{ $reasonD5->value }}">{{ $reasonD5->reason }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('five.reason')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Codificação <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select border border-secondary"
                                                    wire:model.defer="five.codify" @disabled(true)>
                                                    <option value="">Selecione...</option>
                                                    @foreach (SelectOptions::getD5codify() as $codifyD5)
                                                        <option value="{{ $codifyD5->value }}">{{ $codifyD5->reason }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('five.codify')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Local de Instalação <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control border border-secondary"
                                                    wire:model.defer="five.loc_install" placeholder=""
                                                    @disabled(true)>
                                                @error('five.loc_install')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-2">
                                                <label class="form-label">Conjunto</label>
                                                <input type="text" class="form-control border border-secondary"
                                                    wire:model.defer="five.conjunto" placeholder="Ex.: 12"
                                                    @disabled($five->is_supervisioned)>
                                                @error('five.conjunto')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label">Elemento PEP</label>
                                                <input type="text" class="form-control border border-secondary"
                                                    wire:model.defer="five.pep" placeholder="Ex.: PEP-123"
                                                    @disabled($five->is_supervisioned)>
                                                @error('five.pep')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label">Descrição</label>
                                                <textarea rows="3" class="form-control border border-secondary" wire:model.defer="five.description"
                                                    placeholder="Detalhe o apontamento e a correção esperada" @disabled(true)></textarea>
                                                @error('five.description')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>

                                            @if ($five->is_supervisioned)
                                                <div class="col-md-2">
                                                    <label class="form-label">Despachado em</label>
                                                    <input type="datetime-local"
                                                        class="form-control border border-secondary"
                                                        wire:model="five.dispatch_at" disabled>
                                                    @error('five.dispatch_at')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>



                                                <div class="col-md-2">
                                                    <label class="form-label">Supervisionado em</label>
                                                    <input type="datetime-local"
                                                        class="form-control border border-secondary"
                                                        wire:model="five.supervisioned_at" disabled>
                                                    @error('five.supervisioned_at')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>

                                                <div class="col-md-2">
                                                    <label class="form-label">Pago em</label>
                                                    <input type="datetime-local"
                                                        class="form-control border border-secondary"
                                                        wire:model="five.payed_at" disabled>
                                                    @error('five.payed_at')
                                                        <small class="text-danger">{{ $message }}</small>
                                                    @enderror
                                                </div>
                                            @endif


                                        </div>
                                    </div>
                                </div>
                            @elseif ($five && $five->is_supervisioned)
                                {{-- BLOCO: Informações da D5 (quando já supervisionada) --}}
                                <div class="card shadow-sm border-0 rounded-3 five-info">
                                    <div
                                        class="card-header py-3 bg-white border-0 d-flex justify-content-between align-items-center">
                                        <h5 class="m-0 d-flex align-items-center gap-2">
                                            <i class="ri-alert-line text-warning"></i> D5 • Informações
                                        </h5>

                                        <div class="d-flex align-items-center gap-2">
                                            <span
                                                class="badge rounded-pill bg-success-subtle text-success d-inline-flex align-items-center gap-1">
                                                <i class="ri-check-double-line"></i> Processada
                                            </span>
                                            @if ($five?->note_d5)
                                                <span
                                                    class="badge rounded-pill bg-primary-subtle text-primary">#{{ $five->note_d5 }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="card-body pt-0">
                                        {{-- aviso compacto --}}
                                        <div class="alert alert-info d-flex align-items-start gap-3 mb-3">
                                            <i class="ri-information-line fs-4 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">Esta nota já possui D5 registrada.</div>
                                                <small class="text-muted">Revise os detalhes e as observações
                                                    abaixo.</small>
                                            </div>
                                        </div>

                                        {{-- highlights em 3 colunas --}}
                                        <div class="row g-3 mb-3">

                                            <div class="col-md-3">
                                                <div class="hi d-flex align-items-start gap-2">
                                                    <div class="hi-ico text-primary"><i class="ri-file-text-line"></i>
                                                    </div>
                                                    <div class="hi-body">
                                                        <div class="hi-k">Nota D5</div>
                                                        <div class="hi-v">{{ $five->note_d5 ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="hi d-flex align-items-start gap-2">
                                                    <div class="hi-ico text-primary"><i
                                                            class="ri-error-warning-line"></i>
                                                    </div>
                                                    <div class="hi-body">
                                                        <div class="hi-k">Motivo</div>
                                                        <div class="hi-v">{{ $five->reason ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="hi d-flex align-items-start gap-2">
                                                    <div class="hi-ico text-primary"><i class="ri-hashtag"></i></div>
                                                    <div class="hi-body">
                                                        <div class="hi-k">Código</div>
                                                        <div class="hi-v">{{ $five->codify ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="hi d-flex align-items-start gap-2">
                                                    <div class="hi-ico text-primary"><i class="ri-map-pin-line"></i>
                                                    </div>
                                                    <div class="hi-body">
                                                        <div class="hi-k">Local de Instalação</div>
                                                        <div class="hi-v text-truncate"
                                                            title="{{ $five?->loc_install }}">
                                                            {{ $five?->loc_install ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="hi d-flex align-items-start gap-2">
                                                    <div class="hi-ico text-primary"><i class="ri-building-line"></i>
                                                    </div>
                                                    <div class="hi-body">
                                                        <div class="hi-k">Empresa</div>
                                                        <div class="hi-v">
                                                            {{ $five?->company?->name ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="hi d-flex align-items-start gap-2">
                                                    <div class="hi-ico text-primary"><i class="ri-user-line"></i>
                                                    </div>
                                                    <div class="hi-body">
                                                        <div class="hi-k">Usuario</div>
                                                        <div class="hi-v">
                                                            {{ $five?->name ?? '—' }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- datas/status em chips --}}
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <span class="chip">
                                                <i class="ri-calendar-line"></i>
                                                Criada:
                                                {{ $five->created_at ? $five->created_at?->format('d/m/Y H:i:s') : '—' }}
                                            </span>
                                            <span class="chip">
                                                <i class="ri-calendar-line"></i>
                                                Despachado em:
                                                {{ $five->dispatched_at ? $five->dispatched_at?->format('d/m/Y H:i:s') : '—' }}
                                            </span>
                                            <span class="chip">
                                                <i class="ri-time-line"></i>
                                                Concluído Em:
                                                {{ $five->completed_at ? $five->completed_at?->format('d/m/Y H:i:s') : '—' }}
                                            </span>
                                            <span class="chip chip-ok">
                                                <i class="ri-shield-check-line"></i> Status: Finalizado
                                            </span>
                                        </div>

                                        {{-- observações (se houver) --}}
                                        @if ($five->description)
                                            <div class="obs">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <i class="ri-chat-3-line text-primary"></i>
                                                    <span class="fw-semibold">Observações</span>
                                                </div>
                                                <div class="obs-box">
                                                    {!! nl2br(e($five?->description)) !!}
                                                </div>
                                            </div>
                                        @endif


                                        @if ($files = $five->EvidenceFiles)
                                            <div class="obs">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <i class="ri-file-3-line text-primary"></i>
                                                    <span class="fw-semibold">Arquivos Evidências</span>
                                                </div>
                                                <div class="obs-box">
                                                    <x-files.attachments :files="$files" :downloadAction="'downloadFile'"
                                                        :showHeader="false" :card="false" />
                                                </div>
                                            </div>
                                        @endif

                                        {{-- =======================
                                         HISTÓRICO DE PRODUÇÃO
                                         (somente quando $five)
                                    ======================== --}}
                                        @if ($five->productions && $five->productions->count())
                                            <div class="mt-4">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <i class="ri-history-line text-primary"></i>
                                                    <h6 class="m-0">Histórico de Produção</h6>
                                                </div>

                                                <div class="five-timeline">
                                                    @foreach ($five->productions as $p)
                                                        @php
                                                            $userName = $p->User->name ?? 'Usuário';
                                                            $serviceName = $p->Service->service ?? 'Serviço';
                                                            $doneAt = $p->completed_at
                                                                ? $p->completed_at->format('d/m/Y H:i')
                                                                : '—';
                                                            $conclusion =
                                                                data_get($p->analise, 'conclusion') ??
                                                                'Conclusão não informada';
                                                            $info = trim((string) data_get($p->analise, 'info', ''));
                                                        @endphp

                                                        <div class="five-tl-item">
                                                            <div class="five-tl-dot"></div>
                                                            <div class="five-tl-card">
                                                                <div
                                                                    class="five-tl-head d-flex flex-wrap align-items-center justify-content-between">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <span class="five-tl-badge">
                                                                            <i class="ri-user-3-line"></i>
                                                                            {{ $userName }}
                                                                        </span>
                                                                        <span class="five-tl-badge five-tl-badge-alt">
                                                                            <i class="ri-briefcase-line"></i>
                                                                            {{ $serviceName }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="five-tl-date">
                                                                        <i class="ri-time-line"></i>
                                                                        {{ $doneAt }}
                                                                    </div>
                                                                </div>

                                                                <div class="five-tl-title">
                                                                    {{ $conclusion }}
                                                                </div>

                                                                @if ($info !== '')
                                                                    <div class="five-tl-text">
                                                                        {!! nl2br(e($info)) !!}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        {{-- ====== FIM HISTÓRICO ====== --}}

                                    </div>
                                </div>

                                <style>
                                    /* escopo local */
                                    .five-info .chip {
                                        background: #f8fafc;
                                        border: 1px solid #eef2f7;
                                        color: #334155;
                                        padding: .35rem .6rem;
                                        border-radius: 999px;
                                        display: inline-flex;
                                        align-items: center;
                                        gap: .4rem;
                                        font-size: .85rem;
                                    }

                                    .five-info .chip-ok {
                                        background: #ecfdf5;
                                        border-color: #d1fae5;
                                        color: #065f46;
                                    }

                                    .five-info .hi-ico i {
                                        font-size: 1.1rem;
                                    }

                                    .five-info .hi-k {
                                        font-size: .75rem;
                                        color: #6b7280;
                                        text-transform: uppercase;
                                        letter-spacing: .02em;
                                    }

                                    .five-info .hi-v {
                                        font-weight: 600;
                                        color: #111827;
                                    }

                                    .five-info .obs-box {
                                        background: #f8fafc;
                                        border: 1px solid #eef2f7;
                                        border-radius: 12px;
                                        padding: 12px;
                                        color: #374151;
                                        line-height: 1.5;
                                    }

                                    /* ======= HISTÓRICO (timeline) ======= */
                                    .five-timeline {
                                        position: relative;
                                        margin-left: .5rem;
                                        padding-left: 1.25rem;
                                    }

                                    .five-timeline::before {
                                        content: "";
                                        position: absolute;
                                        left: 6px;
                                        top: 0;
                                        bottom: 0;
                                        width: 2px;
                                        background: #e5e7eb;
                                    }

                                    .five-tl-item {
                                        position: relative;
                                        margin-bottom: 1rem;
                                    }

                                    .five-tl-dot {
                                        position: absolute;
                                        left: -1px;
                                        top: 8px;
                                        width: 14px;
                                        height: 14px;
                                        background: #fff;
                                        border: 2px solid #3b82f6;
                                        border-radius: 999px;
                                        z-index: 1;
                                    }

                                    .five-tl-card {
                                        margin-left: 1rem;
                                        background: #ffffff;
                                        border: 1px solid #eef2f7;
                                        border-radius: 12px;
                                        padding: .75rem .9rem;
                                        box-shadow: 0 1px 2px rgba(0, 0, 0, .03);
                                    }

                                    .five-tl-head {
                                        font-size: .85rem;
                                        color: #334155;
                                        margin-bottom: .35rem;
                                    }

                                    .five-tl-badge {
                                        background: #eef2ff;
                                        color: #3730a3;
                                        border: 1px solid #e0e7ff;
                                        border-radius: 999px;
                                        padding: .2rem .5rem;
                                        display: inline-flex;
                                        align-items: center;
                                        gap: .35rem;
                                        font-weight: 600;
                                    }

                                    .five-tl-badge-alt {
                                        background: #ecfeff;
                                        border-color: #cffafe;
                                        color: #155e75;
                                    }

                                    .five-tl-date {
                                        color: #6b7280;
                                        font-size: .8rem;
                                        white-space: nowrap;
                                    }

                                    .five-tl-title {
                                        font-weight: 700;
                                        color: #111827;
                                        margin-top: .25rem;
                                    }

                                    .five-tl-text {
                                        color: #374151;
                                        margin-top: .25rem;
                                        white-space: pre-line;
                                    }
                                </style>

                            @endif


                            <div class="close-form-card">
                                <div class="close-form-card__head">
                                    <h5 class="close-form-card__title">
                                        <i class="ri-checkbox-circle-line text-success"></i>
                                        Parâmetros de Encerramento
                                    </h5>
                                </div>
                                <div class="close-form-card__body">
                                    <div class="row g-3">
                                        <div class="col-md-4 close-field">
                                            <label for="resultado" class="form-label">Resultado <span
                                                    class="close-required">*</span></label>
                                            <select id="resultado"
                                                class="form-select border border-secondary @error('analise.conclusion') is-invalid @enderror"
                                                wire:model="analise.conclusion">
                                                <option value="">Selecione...</option>
                                                @foreach (SelectOptions::getPaymentsOptions() as $item)
                                                    <option value="{{ $item->value }}">{{ $item->info }}</option>
                                                @endforeach
                                            </select>
                                            @error('analise.conclusion')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                            @if (!data_get($analise, 'conclusion'))
                                                <div class="close-help text-danger">Selecione um resultado para encerrar.</div>
                                            @endif
                                        </div>

                                        @if ($this->hasMultipleCloseFinalScopes())
                                            <div class="col-md-8 close-field">
                                                <label class="form-label">Escopo a encerrar <span class="close-required">*</span></label>
                                                <div class="d-flex flex-wrap gap-3 rounded border bg-light p-3">
                                                    @foreach ($this->closeFinalScopeOptions() as $scopeOption)
                                                        <label class="form-check d-flex align-items-center gap-2 m-0">
                                                            <input class="form-check-input" type="checkbox"
                                                                wire:model="closeFinalScopeSelections.{{ $scopeOption['scope'] }}">
                                                            <span class="badge {{ $scopeOption['class'] }}">{{ $scopeOption['label'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @if (!$this->hasValidCloseFinalScopeSelection())
                                                    <div class="close-help text-danger">Selecione ao menos um escopo.</div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="close-form-card">
                                <div class="close-form-card__head">
                                    <h5 class="close-form-card__title">
                                        <i class="ri-message-3-line text-primary"></i>
                                        Observações
                                    </h5>
                                </div>
                                <div class="close-form-card__body">
                                    <div class="row g-3">
                                        <div class="col-12 close-field">
                                            <label for="info" class="form-label">
                                                Observação
                                                <span class="fw-bold">
                                                    <i class="ri-file-copy-line copyButton" data-id="infoTextArea2"
                                                        style="cursor:pointer;"></i>
                                                </span>
                                            </label>
                                            <textarea id="infoTextArea2" rows="5" class="form-control border border-secondary"
                                                wire:model.defer="analise.info" placeholder="Contextualize a medição, liberação de D5 ou demais observações."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="close-action-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
                                @php
                                    $closeStepSummary = $this->closeStepSummary;
                                    $canFinish = $this->canCloseFinish;
                                    $showD5Alert = $five && !$five->is_supervisioned;
                                @endphp

                                <div class="close-step">
                                    <span class="close-step__icon {{ $closeStepSummary['iconClass'] }}">
                                        <i class="{{ $closeStepSummary['icon'] }}"></i>
                                    </span>
                                    <div>
                                        <div class="close-step__eyebrow">Próximo passo</div>
                                        <div class="close-step__text">{{ $closeStepSummary['label'] }}</div>
                                        <div class="close-step__message">{{ $closeStepSummary['message'] }}</div>
                                    </div>
                                </div>

                                @if ($showD5Alert)
                                    <div class="close-mini-alerts">
                                        <span class="close-step-pill is-warning" title="A medição irá criar e despachar a D5 para a empreiteira.">
                                            <i class="ri-file-text-line"></i>
                                            D5 pendente
                                        </span>
                                    </div>
                                @endif

                                <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-secondary" wire:click.prevent="saveForm()">
                                <i class="ri-save-3-line me-1"></i> SALVAR
                            </button>

                            <button type="button" class="btn btn-info" wire:click.prevent="waitingForm()">
                                <i class="ri-time-line me-1"></i> ESPERAR
                            </button>

                            <button type="button" class="btn btn-warning"
                                wire:click="$emitTo('components.pausenote.pausenote2', 'stop_note', {{ $production }})">
                                <i class="ri-pause-line me-1"></i> PAUSAR
                            </button>

                                    <button type="button" class="btn btn-success btn-close-finish" wire:click.prevent="to_finish()"
                                        @disabled(!$canFinish) wire:loading.attr="disabled" wire:target="to_finish">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" wire:loading wire:target="to_finish"></span>
                                <i class="ri-checkbox-circle-line me-1"></i> ENCERRAR
                            </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Livewire Components --}}
    @livewire('components.pausenote.pausenote2', key('PauseNotes2'))
</div>
