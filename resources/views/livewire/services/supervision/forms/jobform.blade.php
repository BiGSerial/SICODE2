@php
    use App\Helpers\SelectOptions;
@endphp

<div wire:ignore.self class="modal fade" id="formProductionModal" tabindex="-1" aria-labelledby="formProductionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        @if ($production)
            @php
                // Regras de habilitação
                $d5Selected = $d5 === 0 || $d5 === 1 || $d5 === '0' || $d5 === '1';
                $needD5Reason = (string) $d5 === '1';
                $hasD5Reason = !empty($return['reason'] ?? null);
                $hasConclusion = !empty($analise['conclusion'] ?? null);
                $canFinish = ($d5Selected && $hasConclusion && (!$needD5Reason || $hasD5Reason)) || $production->dfive;
                $isInRevision = (bool) ($production->Note->WorkForm?->rejected);
            @endphp

            <div class="modal-content">
                {{-- HEADER --}}
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <div class="d-flex flex-column">
                        <h1 class="modal-title fs-5 m-0" id="formProductionModalLabel">
                            {{ mb_strtoupper($production->Service->service) }}
                            <span class="text-white-50 fw-normal"> • Nota/OV {{ $production->Note->note }}</span>
                        </h1>
                        <small class="text-white-50">
                            Município: {{ $production->Note->lexp }} • Rubrica: {{ $production->Note->rubrica }}
                        </small>
                    </div>
                    <button type="button" class="btn-close btn-succes" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body edp-bg-stategrey-50 d-flex flex-column">
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
                                                    @if ($production->Note->WorkForm && $production->Note->WorkForm->Orders->count())
                                                        {{ $production->Note->WorkForm->Orders->pluck('ordem')->join(', ') }}
                                                    @elseif ($production->partial && optional($production->Note->Partials->last())->Orders->count())
                                                        {{ $production->Note->Partials->last()->Orders->pluck('ordem')->join(', ') }}
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
                                                    @if ($production->Note->WorkForm)
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
                                            <i class="ri-calendar-event-line text-primary fs-5"></i>
                                            <div>
                                                <div class="text-muted small">Data SICODE</div>
                                                <div class="fw-semibold">
                                                    @if ($production->Note->WorkForm)
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
                                                    @if ($production->Note->WorkForm)
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

                        {{-- BLOCO: Decisão D5 --}}
                        @if (!$five)
                            <div class="card shadow-sm mb-3 border-0 rounded-3">
                                <div class="card-header py-2 bg-white border-0">
                                    <h5 class="m-0 d-flex align-items-center gap-2">
                                        <i class="ri-alert-line text-warning"></i> Necessidade de D5
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Necessidade de D5?</label>
                                            <select class="form-select border border-secondary" wire:model="d5"
                                                @disable($production->dfive)>
                                                <option value="" selected>Selecione</option>
                                                <option value="1">SIM</option>
                                                <option value="0">NÃO</option>
                                            </select>
                                        </div>

                                        @if ((string) $d5 === '1')
                                            <div class="col-md-4">
                                                <label class="form-label">Motivo <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select border border-secondary"
                                                    wire:model.defer="return.reason">
                                                    <option value="" selected>Selecione</option>
                                                    @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                                        <option value="{{ $reasonD5->value }}">{{ $reasonD5->reason }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if (!$hasD5Reason)
                                                    <small class="text-danger">Obrigatório quando D5 = SIM.</small>
                                                @endif
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label">Codigo <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select border border-secondary"
                                                    wire:model.defer="return.codify">
                                                    <option value="" selected>Selecione</option>
                                                    @foreach (SelectOptions::getD5codify() as $codifyD5)
                                                        <option value="{{ $codifyD5->value }}">{{ $codifyD5->reason }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if (!$hasD5Reason)
                                                    <small class="text-danger">Obrigatório quando D5 = SIM.</small>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Local Instalação <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control border border-secondary"
                                                    wire:model.defer="return.loc_install"
                                                    placeholder="Ex.: 708-EP-00459941" @disabled($return['loc_install'] ?? false)>

                                            </div>

                                            <div class="col-md-12">
                                                <label class="form-label">Observações da D5</label>
                                                <textarea class="form-control border border-secondary" rows="4" wire:model.defer="return.description"
                                                    placeholder="Descreva os apontamentos da D5"></textarea>
                                            </div>

                                            <div class="row my-3">
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <h5 class="card-header">Anexar Arquivos na D5</h5>
                                                        <div class="card-body p-0">
                                                            @livewire('files.evidence.upload-evidence', ['type' => 'PRE_OBRA', 'origin' => $origin])
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card">
                                                        <h5 class="card-header">Informação</h5>
                                                        <div class="card-body">
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
                        @else
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
                        <div class="card shadow-sm mb-3 border-0 rounded-3">
                            <div class="card-header py-2 bg-white border-0">
                                <h5 class="m-0 d-flex align-items-center gap-2">
                                    <i class="ri-checkbox-circle-line text-success"></i> Parâmetros de Encerramento
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Postes</label>
                                        <input type="number" min="0"
                                            class="form-control border border-secondary"
                                            wire:model.defer="analise.postes" placeholder="0">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Conclusão <span class="text-danger">*</span></label>
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
                                        @if (!$hasConclusion)
                                            <small class="text-danger">Selecione uma conclusão.</small>
                                        @endif
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label">Fiscalização se deu por fotos da parceira? <span class="text-danger">*</span></label>
                                        <select class="form-select border border-secondary"
                                            wire:model.defer="supervisionByPartnerPhotos">
                                            <option value="" selected>Selecione</option>
                                            <option value="1">SIM</option>
                                            <option value="0">NÃO</option>
                                        </select>
                                    </div>

                                    @if ($production->dfive)
                                        <div class="col-md-5">
                                            <div class="card card-warning">
                                                <h5 class="card-header">Informação para encerramento D5</h5>
                                                <div class="card-body">
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
                        <div class="card shadow-sm mb-3 border-0 rounded-3">
                            <div class="card-header py-2 bg-white border-0">
                                <h5 class="m-0 d-flex align-items-center gap-2">
                                    <i class="ri-attachment-2 text-primary"></i> Anexos & Observações
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        {{-- Gerenciador de arquivos (mantido) --}}
                                        @livewire('files.manager.create-prod-files', ['production' => $production, 'needFiles' => false], key('FilesSupervision'))
                                    </div>

                                    <div class="col-12">
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

                        <div class="card shadow-sm border-top rounded-0 mt-4">
                            <div class="card-body edp-bg-stategrey-100 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="text-muted small">
                                    <i class="ri-information-line"></i>
                                    @if (!$d5Selected)
                                        Selecione se há D5.
                                    @elseif($needD5Reason && !$hasD5Reason)
                                        Informe o motivo da D5.
                                    @elseif(!$hasConclusion)
                                        Selecione a conclusão.
                                    @else
                                        Pronto para encerrar.
                                    @endif
                                </div>

                                <div class="d-flex gap-2 flex-wrap">{{ $hasEvidence }}
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

                                    <button type="button" class="btn btn-success" wire:click.prevent="to_finish()"
                                        @disabled(!$canFinish) wire:loading.attr="disabled" wire:target="to_finish">
                                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" wire:loading wire:target="to_finish"></span>
                                        <i class="ri-checkbox-circle-line me-1" wire:loading.remove wire:target="to_finish"></i> ENCERRAR
                                    </button>
                                </div>
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
