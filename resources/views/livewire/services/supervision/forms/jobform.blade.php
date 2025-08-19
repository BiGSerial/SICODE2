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
                $canFinish = $d5Selected && $hasConclusion && (!$needD5Reason || $hasD5Reason);
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
                                            <label class="form-label">Motivo <span class="text-danger">*</span></label>
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
                                            <label class="form-label">Codigo <span class="text-danger">*</span></label>
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
                                                wire:model.defer="return.loc_install" placeholder="Local Instalação">

                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">Observações da D5</label>
                                            <textarea class="form-control border border-secondary" rows="4" wire:model.defer="return.description"
                                                placeholder="Descreva os apontamentos da D5 (opcional)"></textarea>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

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
                                                <option value="reject">Rejeitar Obra</option>
                                            @endif
                                        </select>
                                        @if (!$hasConclusion)
                                            <small class="text-danger">Selecione uma conclusão.</small>
                                        @endif
                                    </div>
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
                                        <textarea id="infoTextArea2" class="form-control border border-secondary" rows="6"
                                            wire:model.defer="analise.info" placeholder="Contextualize a fiscalização, apontamentos e demais observações."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- FOOTER (fixo, com estados) --}}
                <div class="modal-footer edp-bg-stategrey-100 d-flex justify-content-between">
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

                    <div class="d-flex gap-2">
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

                        <button type="button" class="btn btn-success" wire:click.prevent="to_finish()"
                            @disabled(!$canFinish)>
                            <i class="ri-checkbox-circle-line me-1"></i> ENCERRAR
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
    {{-- Livewire Components --}}
    @livewire('components.pausenote.pausenote2', key('PauseNotes2'))
</div>
