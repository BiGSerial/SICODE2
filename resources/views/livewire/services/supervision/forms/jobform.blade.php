@php
    use App\Helpers\SelectOptions;
@endphp

<div>
@once
    <style>
        .supervision-files-panel {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            overflow: hidden;
        }

        .supervision-files-panel__head {
            align-items: center;
            background: #123f43;
            color: #f8fafc;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
            padding: 0.75rem 0.9rem;
        }

        .supervision-files-panel__title {
            align-items: center;
            color: #28ff52;
            display: inline-flex;
            font-size: 0.9rem;
            font-weight: 800;
            gap: 0.45rem;
            margin: 0;
            text-transform: uppercase;
        }

        .supervision-files-panel__count {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 999px;
            color: #ffffff;
            font-size: 0.74rem;
            font-weight: 800;
            padding: 0.25rem 0.6rem;
            white-space: nowrap;
        }

        .supervision-files-panel__body {
            max-height: none;
            overflow: auto;
            scrollbar-color: #0f766e #e2e8f0;
            scrollbar-width: thin;
        }

        .supervision-files-panel__body::-webkit-scrollbar {
            height: 10px;
            width: 10px;
        }

        .supervision-files-panel__body::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 999px;
        }

        .supervision-files-panel__body::-webkit-scrollbar-thumb {
            background: #0f766e;
            border: 2px solid #e2e8f0;
            border-radius: 999px;
        }

        .supervision-files-panel__grid {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            padding: 0.75rem;
        }

        .supervision-files-panel__activity {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .supervision-files-panel__activity-head {
            align-items: center;
            background: #f8fafc;
            color: #334155;
            display: flex;
            gap: 0.5rem;
            justify-content: space-between;
            font-size: 0.74rem;
            font-weight: 800;
            padding: 0.45rem 0.75rem;
            text-transform: uppercase;
        }

        .supervision-files-panel__activity-count {
            background: #e2e8f0;
            border-radius: 999px;
            color: #334155;
            font-size: 0.65rem;
            padding: 0.12rem 0.45rem;
        }

        .supervision-files-panel__activity-list {
            max-height: 168px;
            overflow: auto;
            scrollbar-color: #0f766e #e2e8f0;
            scrollbar-width: thin;
        }

        .supervision-files-panel__activity-list::-webkit-scrollbar {
            width: 8px;
        }

        .supervision-files-panel__activity-list::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 999px;
        }

        .supervision-files-panel__activity-list::-webkit-scrollbar-thumb {
            background: #0f766e;
            border: 2px solid #e2e8f0;
            border-radius: 999px;
        }

        .supervision-files-panel__file {
            align-items: center;
            border-top: 1px solid #f1f5f9;
            color: #1f2937;
            display: flex;
            gap: 0.55rem;
            padding: 0.45rem 0.75rem;
            text-decoration: none;
        }

        .supervision-files-panel__file:hover {
            background: #ecfdf5;
            color: #166534;
        }

        .supervision-files-panel__file.is-blocked {
            background: #f8fafc;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .supervision-files-panel__file.is-blocked:hover {
            background: #f8fafc;
            color: #94a3b8;
        }

        .supervision-files-panel__file-icon {
            flex: 0 0 auto;
            font-size: 1rem;
        }

        .supervision-files-panel__file-name {
            flex: 1;
            font-size: 0.84rem;
            font-weight: 700;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .supervision-files-panel__file-ext {
            background: #e2e8f0;
            border-radius: 4px;
            color: #475569;
            flex: 0 0 auto;
            font-size: 0.62rem;
            font-weight: 800;
            padding: 0.12rem 0.4rem;
        }

        .supervision-files-panel__empty {
            color: #64748b;
            font-size: 0.86rem;
            font-weight: 700;
            padding: 1rem;
            text-align: center;
        }

        .supervision-files-panel__blocked-badge {
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 4px;
            color: #92400e;
            flex: 0 0 auto;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 0.1rem 0.35rem;
        }

        .supervision-files-panel__thumb {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            flex: 0 0 42px;
            height: 42px;
            object-fit: cover;
            width: 42px;
        }

        .supervision-close-modal .modal-header {
            background: #123f43;
            border: 0;
            color: #f8fafc;
        }

        .supervision-close-modal .modal-body {
            background: #eef4f5;
        }

        .close-form-card {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .close-form-card__head {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 0.55rem;
            justify-content: space-between;
            padding: 0.85rem 1rem;
        }

        .close-form-card__title {
            align-items: center;
            color: #0f172a;
            display: inline-flex;
            font-size: 0.96rem;
            font-weight: 800;
            gap: 0.45rem;
            margin: 0;
        }

        .close-form-card__body {
            padding: 1rem;
        }

        .close-note-hero {
            border-left: 5px solid #0f766e;
            padding: 1.1rem;
        }

        .close-note-hero__top {
            align-items: flex-start;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .close-note-hero__label {
            color: #64748b;
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .close-note-hero__number {
            color: #0f172a;
            font-size: 1.25rem;
            font-weight: 900;
            line-height: 1.1;
        }

        .close-note-hero__description {
            color: #334155;
            font-size: 0.88rem;
            font-weight: 700;
            margin-top: 0.25rem;
        }

        .close-note-grid {
            display: grid;
            gap: 0.7rem;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        }

        .close-note-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.7rem 0.8rem;
        }

        .close-note-item__label {
            color: #64748b;
            font-size: 0.68rem;
            font-weight: 900;
            text-transform: uppercase;
        }

        .close-note-item__value {
            color: #0f172a;
            font-size: 0.9rem;
            font-weight: 800;
            margin-top: 0.18rem;
            overflow-wrap: anywhere;
        }

        .close-field .form-label {
            color: #334155;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }

        .close-field .form-control,
        .close-field .form-select {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px;
            min-height: 40px;
        }

        .close-field textarea.form-control {
            min-height: 112px;
        }

        .close-field .form-control:focus,
        .close-field .form-select:focus {
            border-color: #0f766e !important;
            box-shadow: 0 0 0 0.18rem rgba(15, 118, 110, 0.14);
        }

        .close-required {
            color: #dc2626;
            font-weight: 900;
        }

        .close-help {
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 600;
            margin-top: 0.28rem;
        }

        .close-alert-inline {
            align-items: flex-start;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            color: #92400e;
            display: flex;
            gap: 0.65rem;
            padding: 0.85rem;
        }

        .close-action-bar {
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

        .close-step {
            align-items: center;
            display: flex;
            gap: 0.55rem;
            min-width: min(100%, 280px);
        }

        .close-step__icon {
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

        .close-step__icon.is-warning {
            background: #d97706;
        }

        .close-step__icon.is-ready {
            background: #16a34a;
        }

        .close-step__eyebrow {
            color: #0f766e;
            font-size: 0.64rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .close-step__text {
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .close-step__message {
            color: #475569;
            font-size: 0.72rem;
            font-weight: 700;
            margin-top: 0.1rem;
        }

        .close-step-list {
            display: flex;
            flex: 1 1 100%;
            flex-wrap: wrap;
            gap: 0.35rem;
            order: 3;
        }

        .close-mini-alerts {
            align-items: center;
            display: flex;
            flex: 1 1 auto;
            flex-wrap: wrap;
            gap: 0.35rem;
            justify-content: center;
        }

        .close-step-pill {
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

        .close-step-pill.is-done {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }

        .close-step-pill.is-current {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.14);
        }

        .close-step-pill.is-warning {
            background: #fff7ed;
            border-color: #fdba74;
            color: #9a3412;
        }

        .close-action-bar .btn {
            align-items: center;
            border-radius: 6px;
            display: inline-flex;
            font-weight: 800;
            gap: 0.35rem;
            justify-content: center;
            min-height: 36px;
            min-width: 104px;
        }

        .close-action-bar .btn-close-finish {
            background: #16a34a;
            border-color: #16a34a;
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.20);
            min-width: 124px;
        }

        .close-action-bar .btn-close-finish:hover {
            background: #15803d;
            border-color: #15803d;
        }

        @media (max-width: 767.98px) {
            .close-action-bar {
                position: static;
            }

            .close-action-bar .btn {
                width: 100%;
            }
        }
    </style>
@endonce

<div wire:ignore.self class="modal fade supervision-close-modal" id="formProductionModal" tabindex="-1" aria-labelledby="formProductionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        @if ($production)
            @php
                $closeNote = $closeNoteDetails;
                $hasD5Reason = !empty($return['reason'] ?? null);
                $hasConclusion = !empty($analise['conclusion'] ?? null);
                $isInRevision = (bool) ($production->Note->WorkForm?->rejected);
            @endphp

            <div class="modal-content">
                {{-- HEADER --}}
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <div class="d-flex flex-column">
                        <h1 class="modal-title fs-5 m-0" id="formProductionModalLabel">
                            {{ mb_strtoupper($production->Service->service) }}
                            <span class="text-white-50 fw-normal"> • Nota/OV {{ $closeNote['note'] ?? $production->Note->note }}</span>
                            <span class="badge {{ $closeNote['typeClass'] ?? 'text-bg-secondary' }} ms-2">{{ $closeNote['type'] ?? '---' }}</span>
                        </h1>
                        <small class="text-white-50">
                            Município: {{ $closeNote['municipio'] ?? '---' }} • Rubrica: {{ $closeNote['rubrica'] ?? '---' }}
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-succes" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body edp-bg-stategrey-50 d-flex flex-column">
                    <div class="container">

                        {{-- Resumo de informações --}}
                        <div class="close-form-card">
                            <div class="close-form-card__body close-note-hero">
                                <div class="close-note-hero__top">
                                    <div>
                                        <div class="close-note-hero__label">Nota/OV</div>
                                        <div class="close-note-hero__number">{{ $closeNote['note'] ?? '---' }}</div>
                                        <div class="close-note-hero__description">{{ $closeNote['description'] ?? '---' }}</div>
                                    </div>
                                    <span class="badge {{ $closeNote['typeClass'] ?? 'text-bg-secondary' }} fs-6">
                                        {{ $closeNote['type'] ?? '---' }}
                                    </span>
                                </div>

                                <div class="close-note-grid">
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-list-ordered-2 text-primary"></i> Ordens</div>
                                        <div class="close-note-item__value">{{ $closeNote['orders'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-map-pin-line text-primary"></i> Município</div>
                                        <div class="close-note-item__value">{{ $closeNote['municipio'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-price-tag-3-line text-primary"></i> Rubrica</div>
                                        <div class="close-note-item__value">{{ $closeNote['rubrica'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-box-3-line text-primary"></i> Material</div>
                                        <div class="close-note-item__value">{{ $closeNote['material'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-calendar-check-line text-primary"></i> Data informada</div>
                                        <div class="close-note-item__value">{{ $closeNote['date'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-calendar-event-line text-primary"></i> Data SICODE</div>
                                        <div class="close-note-item__value">{{ $closeNote['sicodeDate'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-user-settings-line text-primary"></i> Responsável execução</div>
                                        <div class="close-note-item__value">{{ $closeNote['responsible'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-building-line text-primary"></i> Empresa</div>
                                        <div class="close-note-item__value">{{ $closeNote['company'] ?? '---' }}</div>
                                    </div>
                                    <div class="close-note-item">
                                        <div class="close-note-item__label"><i class="ri-focus-3-line text-primary"></i> Escopo fiscalizado</div>
                                        <div class="close-note-item__value">
                                            @foreach (($closeNote['scopeBadges'] ?? []) as $scopeBadge)
                                                <span class="badge {{ $scopeBadge['class'] ?? 'text-bg-secondary' }} mb-1">{{ $scopeBadge['label'] ?? 'Geral' }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($production->partial)
                            <div class="close-alert-inline mb-3">
                                <i class="ri-information-line fs-5"></i>
                                <div>
                                    <div class="fw-bold mb-1">Encerramento parcial</div>
                                    <p class="m-0">D5 não se aplica neste fluxo. A conclusão permite <strong>REJEITAR OBRA</strong> quando a fiscalização indicar necessidade de retorno.</p>
                                </div>
                            </div>
                        @endif

                        {{-- BLOCO: Decisão D5 --}}
                        @if (!$five && !$production->partial)
                            <div class="close-form-card">
                                <div class="close-form-card__head">
                                    <h5 class="close-form-card__title">
                                        <i class="ri-alert-line text-warning"></i>
                                        Necessidade de D5
                                    </h5>
                                </div>
                                <div class="close-form-card__body">
                                    <div class="row g-3">
                                        <div class="col-md-3 close-field">
                                            <label class="form-label">Necessidade de D5?</label>
                                            <select class="form-select border border-secondary" wire:model="d5"
                                                @disable($production->dfive)>
                                                <option value="" selected>Selecione</option>
                                                <option value="1">SIM</option>
                                                <option value="0">NÃO</option>
                                            </select>
                                        </div>

                                        @if ((string) $d5 === '1')
                                            <div class="col-md-4 close-field">
                                                <label class="form-label">Motivo <span class="close-required">*</span></label>
                                                <select class="form-select border border-secondary"
                                                    wire:model.defer="return.reason">
                                                    <option value="" selected>Selecione</option>
                                                    @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                                        <option value="{{ $reasonD5->value }}">{{ $reasonD5->reason }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if (!$hasD5Reason)
                                                    <div class="close-help text-danger">Obrigatório quando D5 = SIM.</div>
                                                @endif
                                            </div>

                                            <div class="col-md-4 close-field">
                                                <label class="form-label">Codigo <span class="close-required">*</span></label>
                                                <select class="form-select border border-secondary"
                                                    wire:model.defer="return.codify">
                                                    <option value="" selected>Selecione</option>
                                                    @foreach (SelectOptions::getD5codify() as $codifyD5)
                                                        <option value="{{ $codifyD5->value }}">{{ $codifyD5->reason }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if (!$hasD5Reason)
                                                    <div class="close-help text-danger">Obrigatório quando D5 = SIM.</div>
                                                @endif
                                            </div>
                                            <div class="col-md-4 close-field">
                                                <label class="form-label">Local Instalação <span class="close-required">*</span></label>
                                                <input type="text" class="form-control border border-secondary"
                                                    wire:model.defer="return.loc_install"
                                                    placeholder="Ex.: 708-EP-00459941" @disabled($return['loc_install'] ?? false)>

                                            </div>

                                            <div class="col-md-12 close-field">
                                                <label class="form-label">Observações da D5</label>
                                                <textarea class="form-control border border-secondary" rows="4" wire:model.defer="return.description"
                                                    placeholder="Descreva os apontamentos da D5"></textarea>
                                            </div>

                                            <div class="row my-3">
                                                <div class="col-md-6">
                                                    <div class="close-form-card mb-0">
                                                        <div class="close-form-card__head">
                                                            <h5 class="close-form-card__title">
                                                                <i class="ri-upload-cloud-2-line text-primary"></i>
                                                                Anexar Arquivos na D5
                                                            </h5>
                                                        </div>
                                                        <div class="card-body p-0">
                                                            @livewire('files.evidence.upload-evidence', ['type' => 'PRE_OBRA', 'origin' => $origin])
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="close-alert-inline h-100">
                                                        <i class="ri-information-line fs-5"></i>
                                                        <div>
                                                            <div class="fw-bold mb-1">Informação</div>
                                                            <p class="m-0">Os Arquivos Anexados aqui, somente estará
                                                                visível internamente para na D5 em questão.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif ($five)
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
                                                <div class="hi-ico text-primary"><i class="ri-error-warning-line"></i>
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
                                                <div class="hi-ico text-primary"><i class="ri-map-pin-line"></i></div>
                                                <div class="hi-body">
                                                    <div class="hi-k">Local de Instalação</div>
                                                    <div class="hi-v text-truncate" title="{{ $five->loc_install }}">
                                                        {{ $five->loc_install ?? '—' }}</div>
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
                                                        {{ $five->company->name ?? '—' }}</div>
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
                                                        {{ $five->name ?? '—' }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="hi d-flex align-items-start gap-2">
                                                <div class="hi-ico text-primary"><i class="ri-message-3-line"></i>
                                                </div>
                                                <div class="hi-body">
                                                    <div class="hi-k">Observação</div>
                                                    <div class="hi-v">

                                                        {!! nl2br(e($five->description)) !!}
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    {{-- datas/status em chips --}}
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="chip">
                                            <i class="ri-calendar-line"></i>
                                            Criada:
                                            {{ $five->created_at ? $five->created_at->format('d/m/Y H:i:s') : '—' }}
                                        </span>
                                        <span class="chip">
                                            <i class="ri-calendar-line"></i>
                                            Despachado em:
                                            {{ $five->dispatched_at ? $five->dispatched_at->format('d/m/Y H:i:s') : '—' }}
                                        </span>
                                        <span class="chip">
                                            <i class="ri-time-line"></i>
                                            Concluído Em:
                                            {{ $five->completed_at ? $five->completed_at->format('d/m/Y H:i:s') : '—' }}
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
                                                <span class="fw-semibold">Observações Empreiteira </span>
                                            </div>
                                            <div class="obs-box">
                                                {{ $five?->Comments?->last()?->message ?? 'Nenhuma Observação' }}

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
                                                                    <i class="ri-time-line"></i> {{ $doneAt }}
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

                        @if ($isInRevision)
                            <div class="alert alert-warning shadow-sm border-0 rounded-3 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="ri-error-warning-line fs-5 mt-1"></i>
                                    <div class="w-100">
                                        <h6 class="mb-2">Informe em revisão</h6>
                                        <p class="mb-2">Este informe foi retornado e está em revisão. A finalização permanece liberada.</p>

                                        <div class="small">
                                            <div><strong>Por quê:</strong> {{ $lastReturnwork->category ?? 'Não informado' }}</div>
                                            <div class="mt-1"><strong>Motivo:</strong></div>
                                            <div class="p-2 bg-light rounded border mt-1">
                                                {!! nl2br(e($lastReturnwork->text_obs ?? 'Não informado')) !!}
                                            </div>
                                            <div class="mt-2 text-muted">
                                                Último retorno:
                                                {{ optional($lastReturnwork->created_at)->format('d/m/Y H:i') ?? '-' }}
                                                por {{ $lastReturnwork->User->name ?? 'Sistema' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- BLOCO: Encerramento / Métricas --}}
                        <div class="close-form-card">
                            <div class="close-form-card__head">
                                <h5 class="close-form-card__title">
                                    <i class="ri-checkbox-circle-line text-success"></i>
                                    Parâmetros de Encerramento
                                </h5>
                            </div>
                            <div class="close-form-card__body">
                                <div class="row g-3">
                                    <div class="col-md-3 close-field">
                                        <label class="form-label">Postes</label>
                                        <input type="number" min="0"
                                            class="form-control border border-secondary"
                                            wire:model.debounce.500ms="analise.postes" placeholder="0">
                                    </div>

                                    <div class="col-md-4 close-field">
                                        <label class="form-label">Conclusão <span class="close-required">*</span></label>
                                        <select class="form-select border border-secondary"
                                            wire:model="analise.conclusion">
                                            <option value="" selected>Selecione</option>
                                            @foreach (SelectOptions::getSupervisionEnd() as $supEnd)
                                                <option value="{{ $supEnd->value }}">{{ $supEnd->reason }}</option>
                                            @endforeach
                                            @if ($production->partial)
                                                <option value="reject">REJEITAR OBRA</option>
                                            @endif
                                        </select>
                                        @if ($production->partial)
                                            <div class="close-help text-warning fw-bold">
                                                Fluxo parcial: a conclusão pode rejeitar a obra e não solicita D5.
                                            </div>
                                        @endif
                                        @if (!$hasConclusion)
                                            <div class="close-help text-danger">Selecione uma conclusão.</div>
                                        @endif
                                    </div>

                                    <div class="col-md-5 close-field">
                                        <label class="form-label">Fiscalização por fotos da parceira? <span class="close-required">*</span></label>
                                        <select class="form-select border border-secondary"
                                            wire:model="supervisionByPartnerPhotos">
                                            <option value="" selected>Selecione</option>
                                            <option value="1">SIM</option>
                                            <option value="0">NÃO</option>
                                        </select>
                                    </div>

                                    @if ($production->dfive)
                                        <div class="col-md-5">
                                            <div class="close-alert-inline">
                                                <i class="ri-error-warning-line fs-5"></i>
                                                <div>
                                                    <div class="fw-bold mb-1">Informação para encerramento D5</div>
                                                    <p>Ao selecionar conclusão que <strong>FISCALIZADO COM
                                                            PENDÊNCIA</strong>. A D5 retornará automáticamente para a
                                                        empreiteira.</p>
                                                    <p>Nesse caso, pedimos para que <strong>detalhe o motivo</strong> da pendência no
                                                        campo Observações no final deste formulário. E assim será gerado
                                                        histórico das tratativas desta D5. Conforme exibido no histórico
                                                        de produção.</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- BLOCO: Anexos & Observações --}}
                        <div class="close-form-card">
                            <div class="close-form-card__head">
                                <h5 class="close-form-card__title">
                                    <i class="ri-attachment-2 text-primary"></i>
                                    Anexos & Observações
                                </h5>
                            </div>
                            <div class="close-form-card__body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="supervision-files-panel">
                                            <div class="supervision-files-panel__head">
                                                <h6 class="supervision-files-panel__title">
                                                    <i class="ri-folder-open-line"></i>
                                                    Arquivos da Nota
                                                </h6>
                                                <span class="supervision-files-panel__count">
                                                    {{ $productionFilesCount }} arquivo{{ $productionFilesCount === 1 ? '' : 's' }}
                                                </span>
                                            </div>

                                            @if ($productionFilesCount > 0)
                                                <div class="supervision-files-panel__body">
                                                    <div class="supervision-files-panel__grid">
                                                        @foreach ($productionFileGroups as $group)
                                                            <div class="supervision-files-panel__activity">
                                                                <div class="supervision-files-panel__activity-head">
                                                                    <span>
                                                                        <i class="ri-briefcase-4-line align-middle"></i>
                                                                        {{ $group['service'] }}
                                                                    </span>
                                                                    <span class="supervision-files-panel__activity-count">
                                                                        {{ $group['count'] }}
                                                                    </span>
                                                                </div>
                                                                <div class="supervision-files-panel__activity-list">
                                                                    @foreach ($group['files'] as $file)
                                                                        @if ($file['isBlocked'])
                                                                            <span class="supervision-files-panel__file is-blocked"
                                                                                title="Arquivo de ADS tácita. Download bloqueado.">
                                                                                @if ($file['isImage'] && $file['thumb'])
                                                                                    <img class="supervision-files-panel__thumb"
                                                                                        src="{{ $file['thumb'] }}"
                                                                                        alt="{{ $file['name'] }}">
                                                                                @else
                                                                                    <i class="{{ $file['icon'] }} supervision-files-panel__file-icon"></i>
                                                                                @endif
                                                                                <span class="supervision-files-panel__file-name">{{ $file['name'] }}</span>
                                                                                <span class="supervision-files-panel__file-ext">{{ $file['ext'] }}</span>
                                                                                <span class="supervision-files-panel__blocked-badge">TÁCITO</span>
                                                                                <i class="ri-lock-line"></i>
                                                                            </span>
                                                                        @else
                                                                            <a href="#" class="supervision-files-panel__file"
                                                                                wire:click.prevent="downloadProductionFile({{ $file['id'] }})"
                                                                                title="{{ $file['name'] }}">
                                                                                @if ($file['isImage'] && $file['thumb'])
                                                                                    <img class="supervision-files-panel__thumb"
                                                                                        src="{{ $file['thumb'] }}"
                                                                                        alt="{{ $file['name'] }}">
                                                                                @else
                                                                                    <i class="{{ $file['icon'] }} supervision-files-panel__file-icon"></i>
                                                                                @endif
                                                                                <span class="supervision-files-panel__file-name">{{ $file['name'] }}</span>
                                                                                <span class="supervision-files-panel__file-ext">{{ $file['ext'] }}</span>
                                                                                @if ($file['isTacitAds'])
                                                                                    <span class="supervision-files-panel__blocked-badge">TÁCITO</span>
                                                                                @endif
                                                                                <i class="ri-download-line"></i>
                                                                            </a>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <div class="supervision-files-panel__empty">
                                                    Nenhum arquivo vinculado a esta nota.
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        @livewire('files.manager.create-prod-files', ['production' => $production, 'needFiles' => false], key('FilesSupervision-' . $production->id))
                                    </div>

                                    <div class="col-12 close-field">
                                        <label class="form-label">
                                            Observações
                                            <span class="fw-bold">
                                                <i class="ri-file-copy-line copyButton" data-id="infoTextArea2"
                                                    style="cursor:pointer;"></i>
                                            </span>
                                        </label>
                                        <textarea id="infoTextArea2" class="form-control border border-secondary @error('analise.info') is-invalid @enderror" rows="6"
                                            wire:model.defer="analise.info" placeholder="Contextualize a fiscalização, apontamentos e demais observações."></textarea>
                                        @error('analise.info')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="close-action-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
                                @php
                                    $closeSteps = $this->closeSteps;
                                    $closeStepSummary = $this->closeStepSummary;
                                    $canFinish = $this->canCloseFinish;
                                    $postesValue = data_get($analise, 'postes');
                                    $showFilesAlert = !$hasExistingProductionFiles && !$hasFile && !$hasEvidence;
                                    $showZeroPostesAlert = blank($postesValue) || (is_numeric($postesValue) && (float) $postesValue === 0.0);
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

                                @if ($showFilesAlert || $showZeroPostesAlert)
                                    <div class="close-mini-alerts">
                                        @if ($showFilesAlert)
                                            <span class="close-step-pill is-warning" title="Anexe os arquivos obrigatórios antes de encerrar.">
                                                <i class="ri-attachment-2"></i>
                                                Sem arquivos
                                            </span>
                                        @endif

                                        @if ($showZeroPostesAlert)
                                            <span class="close-step-pill is-warning" title="A quantidade de postes está zerada. Confirme se está correto antes de encerrar.">
                                                <i class="ri-alert-line"></i>
                                                Postes zerado
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                @if ($this->hasMultipleCloseFinalScopes())
                                    <div class="border rounded p-3 mb-3 bg-light">
                                        <div class="fw-bold mb-2">Escopo a encerrar</div>
                                        <div class="d-flex flex-wrap gap-3">
                                            @foreach ($this->closeFinalScopeOptions() as $scopeOption)
                                                <label class="form-check d-flex align-items-center gap-2 m-0">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model.defer="closeFinalScopeSelections.{{ $scopeOption['scope'] }}">
                                                    <span class="badge {{ $scopeOption['class'] }}">{{ $scopeOption['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-secondary" wire:click.prevent="saveForm()" wire:loading.attr="disabled" wire:target="saveForm">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" wire:loading wire:target="saveForm"></span>
                                        <i class="ri-save-3-line me-1" wire:loading.remove wire:target="saveForm"></i> SALVAR
                                    </button>

                                    <button type="button" class="btn btn-info" wire:click.prevent="waitingForm()" wire:loading.attr="disabled" wire:target="waitingForm">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" wire:loading wire:target="waitingForm"></span>
                                        <i class="ri-time-line me-1" wire:loading.remove wire:target="waitingForm"></i> ESPERAR
                                    </button>

                                    <button type="button" class="btn btn-warning"
                                        wire:click="$emitTo('components.pausenote.pausenote2', 'stop_note', {{ $production }})"
                                        wire:loading.attr="disabled">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"
                                            wire:loading></span>
                                        <i class="ri-pause-line me-1" wire:loading.remove></i> PAUSAR
                                    </button>

                                    <button type="button" class="btn btn-success btn-close-finish" wire:click.prevent="to_finish()"
                                        @disabled(!$canFinish) wire:loading.attr="disabled" wire:target="to_finish">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" wire:loading wire:target="to_finish"></span>
                                        <i class="ri-checkbox-circle-line me-1" wire:loading.remove wire:target="to_finish"></i> ENCERRAR
                                    </button>
                                </div>

                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
    {{-- Livewire Components --}}
    @livewire('components.pausenote.pausenote2', key('PauseNotes2'))
</div>

@once
    <script>
        (function() {
            let pendingSupervisionCloseModal = null;
            let hookRegistered = false;

            function openSupervisionCloseModal() {
                if (!pendingSupervisionCloseModal) {
                    return;
                }

                const modalEl = document.getElementById(pendingSupervisionCloseModal);

                if (!modalEl || !modalEl.querySelector('#formProductionModalLabel')) {
                    return;
                }

                pendingSupervisionCloseModal = null;
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            function registerLivewireHook() {
                if (hookRegistered || !window.Livewire || !window.Livewire.hook) {
                    return;
                }

                hookRegistered = true;
                window.Livewire.hook('message.processed', openSupervisionCloseModal);
            }

            window.addEventListener('supervisionCloseModalReady', function(e) {
                pendingSupervisionCloseModal = e.detail.id;
                window.requestAnimationFrame(openSupervisionCloseModal);
                window.setTimeout(openSupervisionCloseModal, 75);
                window.setTimeout(openSupervisionCloseModal, 200);
            });

            registerLivewireHook();
            document.addEventListener('livewire:load', registerLivewireHook);
        })();
    </script>
@endonce
</div>
