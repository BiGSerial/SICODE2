<div>
    @php
        use App\Helpers\SelectOptions;
        use Carbon\Carbon;
    @endphp

    <x-show-loading />

    <div class="modal fade" id="manualCreateFiveModal" tabindex="-1" aria-labelledby="modalD5Label" aria-hidden="true"
        wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-3 shadow">

                {{-- HEADER --}}
                <div class="modal-header sicode-header border-0">
                    <div>
                        <h5 class="modal-title d-flex align-items-center gap-2 text-white mb-0" id="modalD5Label">
                            <i class="ri-file-text-line"></i> NOTA D5
                        </h5>
                        <small class="text-white-50">Cadastro e vínculo da D5 à Obra/Ordem</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body sicode-body">

                    @if (!$five)

                        <div class="card border-0 shadow-sm mb-3">
                            <div
                                class="card-header sicode-header text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 d-flex align-items-center gap-2">
                                    <i class="ri-search-line"></i> Buscar Obra/Ordem para criar D5
                                </h6>
                            </div>

                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label sicode-label">Número da Obra/Ordem</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control sicode-control"
                                                wire:model.defer="search" placeholder="Ex.: 123456 ou ORD-2025-0001">
                                            <button class="btn sicode-btn-primary" type="button"
                                                wire:click="searchNotes">
                                                <i class="ri-search-line me-1"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                @if (!empty($availableNotes))
                                    <div class="mt-4">
                                        <h6 class="text-muted mb-2">Notas disponíveis</h6>
                                        <div class="list-group">
                                            @foreach ($availableNotes as $note)
                                                @php
                                                    $exists = (bool) $note->FiveNote;
                                                @endphp
                                                <button type="button"
                                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center sicode-list-item {{ $exists ? 'disabled' : '' }}"
                                                    @if (!$exists) wire:click.prevent="selectNote({{ $note->id }})" @endif
                                                    @disabled($exists)>
                                                    <div class="d-flex flex-column">
                                                        <span
                                                            class="fw-semibold sicode-text-strong">{{ $note->note }}</span>
                                                        <small class="text-muted">Ordem vinculada:
                                                            {{ $note->Orders->first()->ordem ?? '-' }}</small>
                                                    </div>
                                                    <span
                                                        class="badge {{ $exists ? 'bg-danger-subtle text-danger-emphasis' : 'bg-success-subtle text-success-emphasis' }}">
                                                        {{ $exists ? 'JÁ EXISTE D5' : 'CRIAR D5' }}
                                                    </span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($search && empty($availableNotes))
                                    <div class="alert sicode-alert-info mt-4 mb-0">
                                        <i class="ri-information-line me-2"></i>
                                        Nenhuma nota encontrada para o número informado.
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($five)
                        {{-- RESUMO DA NOTA / ORDEM – aparece acima do formulário quando $five estiver ativo --}}
                        @if ($note)
                            @php

                                $ordersList =
                                    $note->Orders && $note->Orders->count()
                                        ? $note->Orders->pluck('ordem')->join(', ')
                                        : '—';
                                $pep = $note->Orders->sortBy('ordem')->first()->pep ?? null;

                                $wf = $note->WorkForm ?? null;
                                $lastPartial =
                                    $note->Partials && $note->Partials->count() ? $note->Partials->last() : null;

                                $dataInformada = $wf?->date
                                    ? Carbon::parse($wf->date)->format('d/m/Y')
                                    : ($lastPartial
                                        ? Carbon::parse($lastPartial->created_at)->format('d/m/Y')
                                        : '—');

                                $dataSicode = $wf?->informed_at
                                    ? Carbon::parse($wf->informed_at)->format('d/m/Y H:i:s')
                                    : ($lastPartial
                                        ? 'Não aplica'
                                        : '—');

                                $responsavel = $wf?->responsible ?? ($lastPartial->responsible ?? '—');
                            @endphp

                            <div class="card shadow-sm mb-3 border-0 rounded-3">
                                <div class="card-body py-3">
                                    <div class="row g-3 align-items-center">

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-list-ordered-2 text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Nota</div>
                                                    <div class="fw-semibold">{{ $note->note }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-list-ordered-2 text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Ordens</div>
                                                    <div class="fw-semibold">{{ $ordersList }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-file-text-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">PEP</div>
                                                    <div class="fw-semibold">{{ $pep }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-file-text-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Rubrica</div>
                                                    <div class="fw-semibold">{{ $note->rubrica }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-file-text-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Municipio</div>
                                                    <div class="fw-semibold">{{ $note->lexp }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-calendar-event-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Data Informe</div>
                                                    <div class="fw-semibold">{{ $dataSicode }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ri-user-settings-line text-primary fs-5"></i>
                                                <div>
                                                    <div class="text-muted small">Responsável Execução</div>
                                                    <div class="fw-semibold">{{ $responsavel }}</div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Dados da D5</h6>
                                <button type="button" class="btn btn-sm sicode-btn-soft"
                                    wire:click="$set('five', null)">
                                    <i class="ri-close-line me-1"></i> Cancelar
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label sicode-label">Número da D5 <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control sicode-control @error('five.note_d5') is-invalid @enderror"
                                            wire:model.defer="five.note_d5" placeholder="Ex.: 161234556">
                                        @error('five.note_d5')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label sicode-label">Empresa <span
                                                class="text-danger">*</span></label>
                                        <select
                                            class="form-select sicode-control @error('five.company_id') is-invalid @enderror"
                                            wire:model.defer="five.company_id">
                                            <option value="">Selecione...</option>
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('five.company_id')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label sicode-label">Motivo D5 <span
                                                class="text-danger">*</span></label>
                                        <select
                                            class="form-select sicode-control @error('five.reason') is-invalid @enderror"
                                            wire:model.defer="five.reason">
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
                                        <label class="form-label sicode-label">Codificação <span
                                                class="text-danger">*</span></label>
                                        <select
                                            class="form-select sicode-control @error('five.codify') is-invalid @enderror"
                                            wire:model.defer="five.codify">
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
                                        <label class="form-label sicode-label">Local de Instalação <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control sicode-control @error('five.loc_install') is-invalid @enderror"
                                            wire:model.defer="five.loc_install" placeholder="Ex.: 708-EP-00459941">
                                        @error('five.loc_install')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label sicode-label">Conjunto</label>
                                        <input type="text"
                                            class="form-control sicode-control @error('five.conjunto') is-invalid @enderror"
                                            wire:model.defer="five.conjunto" placeholder="Ex.: 12">
                                        @error('five.conjunto')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label sicode-label">Elemento PEP</label>
                                        <input type="text"
                                            class="form-control sicode-control @error('five.pep') is-invalid @enderror"
                                            wire:model.defer="five.pep" placeholder="Ex.: PEP-123">
                                        @error('five.pep')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label sicode-label">Descrição</label>
                                        <textarea rows="6" class="form-control sicode-control @error('five.description') is-invalid @enderror"
                                            wire:model.defer="five.description" placeholder="Detalhe o apontamento e a correção esperada"></textarea>
                                        @error('five.description')
                                            <small class="text-danger">{{ $message }}</small>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- FOOTER --}}
                <div class="modal-footer sicode-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line me-1"></i> Fechar
                    </button>
                    <button type="button" class="btn sicode-btn-primary" wire:click="saveD5">
                        <i class="ri-save-3-line me-1"></i> Salvar
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- PALETA / UTILITÁRIOS SICODE (sem conflitar com Bootstrap) --}}
<style>
    :root {
        /* ajuste se sua paleta tiver outras cores */
        --sicode-primary: #1c5f52;
        --sicode-primary-600: #164e44;
        --sicode-primary-050: #e8f3ef;
        --sicode-gray-025: #f6f7f9;

        --sicode-border: rgba(28, 95, 82, .45);
        --sicode-border-soft: rgba(28, 95, 82, .20);

        --sicode-info-bg: #e8f2ff;
        --sicode-info-text: #1f4ea3;
    }

    .sicode-header {
        background: var(--sicode-primary) !important;
    }

    .sicode-footer {
        background: var(--sicode-primary-050) !important;
    }

    .sicode-body {
        background: var(--sicode-gray-025) !important;
    }

    .sicode-label {
        font-size: .825rem;
        text-transform: uppercase;
        color: #6c757d;
    }

    .sicode-control {
        border-color: var(--sicode-border) !important;
    }

    .sicode-control:focus {
        border-color: var(--sicode-primary) !important;
        box-shadow: 0 0 0 .2rem rgba(28, 95, 82, .15) !important;
    }

    .sicode-btn-primary {
        background: var(--sicode-primary) !important;
        border-color: var(--sicode-primary) !important;
        color: #fff !important;
    }

    .sicode-btn-primary:hover {
        background: var(--sicode-primary-600) !important;
        border-color: var(--sicode-primary-600) !important;
    }

    .sicode-btn-soft {
        background: var(--sicode-primary-050) !important;
        border: 1px solid var(--sicode-border-soft) !important;
        color: var(--sicode-primary) !important;
    }

    .sicode-list-item {
        border: 1px solid var(--sicode-border-soft) !important;
        border-left-width: 4px !important;
        border-left-color: var(--sicode-primary) !important;
        transition: transform .06s ease-in-out, background-color .12s ease-in-out;
    }

    .sicode-list-item:hover {
        transform: translateY(-1px);
        background: #fff;
    }

    .sicode-list-item.disabled,
    .sicode-list-item:disabled {
        opacity: .7;
        cursor: not-allowed;
        border-left-color: #dc3545 !important;
    }

    .sicode-text-strong {
        color: var(--sicode-primary) !important;
    }

    .sicode-alert-info {
        background: var(--sicode-info-bg) !important;
        color: var(--sicode-info-text) !important;
        border: 1px solid rgba(31, 78, 163, .2) !important;
    }
</style>
