@php
    use App\Helpers\SelectOptions;
@endphp

<div class="finish-five">
    <x-show-loading />

    <div class="modal fade" id="adminEditFiveModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xxl-down">
            @if ($five)
                <form class="modal-content fivefx-card" wire:submit.prevent="save">
                    <div class="modal-header fivefx-header py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2">
                            <i class="ri-edit-2-line me-1"></i>
                            <span>Editar D5</span>
                            @if ($five?->note_d5)
                                <span class="fivefx-pill">D5: {{ $five->note_d5 }}</span>
                            @endif
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body fivefx-body">
                        <div class="row g-4">
                            <div class="col-12 col-xl-7">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Dados principais</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Nota</label>
                                            <input type="text" class="form-control fivefx-control"
                                                value="{{ $five->note?->note ?? '---' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Nota D5</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('five.note_d5') is-invalid @enderror"
                                                wire:model.defer="five.note_d5">
                                            @error('five.note_d5')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Local de Instalacao</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('five.loc_install') is-invalid @enderror"
                                                wire:model.defer="five.loc_install">
                                            @error('five.loc_install')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Conjunto</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('five.conjunto') is-invalid @enderror"
                                                wire:model.defer="five.conjunto">
                                            @error('five.conjunto')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">PEP</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('five.pep') is-invalid @enderror"
                                                wire:model.defer="five.pep">
                                            @error('five.pep')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">E-PEP</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('five.e_pep') is-invalid @enderror"
                                                wire:model.defer="five.e_pep">
                                            @error('five.e_pep')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Empresa</label>
                                            <input type="text" class="form-control fivefx-control"
                                                value="{{ $five->company?->name }}" readonly>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Codificacao</label>
                                            <div class="fivefx-select-wrap">
                                                <select class="form-select fivefx-select @error('five.codify') is-invalid @enderror"
                                                    wire:model.defer="five.codify">
                                                    <option value="" selected>Selecione</option>
                                                    @foreach (SelectOptions::getD5codify() as $codifyD5)
                                                        <option value="{{ $codifyD5->value }}">{{ $codifyD5->reason }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('five.codify')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Motivo</label>
                                            <div class="fivefx-select-wrap">
                                                <select class="form-select fivefx-select @error('five.reason') is-invalid @enderror"
                                                    wire:model.defer="five.reason">
                                                    <option value="" selected>Selecione</option>
                                                    @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                                        <option value="{{ $reasonD5->value }}">{{ $reasonD5->reason }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @error('five.reason')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Sintomas</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('five.sintoms') is-invalid @enderror"
                                                wire:model.defer="five.sintoms">
                                            @error('five.sintoms')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Responsavel</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('five.name') is-invalid @enderror"
                                                wire:model.defer="five.name">
                                            @error('five.name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label for="fiveDescription" class="form-label fivefx-k mb-0">Descricao</label>
                                        <textarea id="fiveDescription"
                                            class="form-control fivefx-control fivefx-textarea @error('five.description') is-invalid @enderror"
                                            wire:model.defer="five.description"></textarea>
                                        @error('five.description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Datas e status</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Despachado em</label>
                                            <input type="datetime-local"
                                                class="form-control fivefx-control @error('five.dispatch_at') is-invalid @enderror"
                                                wire:model.defer="five.dispatch_at">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Concluido em</label>
                                            <input type="datetime-local"
                                                class="form-control fivefx-control @error('five.completed_at') is-invalid @enderror"
                                                wire:model.defer="five.completed_at"
                                                @if ($lockCompleted) disabled @endif>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Fiscalizado em</label>
                                            <input type="datetime-local"
                                                class="form-control fivefx-control @error('five.supervisioned_at') is-invalid @enderror"
                                                wire:model.defer="five.supervisioned_at">
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Pago em</label>
                                            <input type="datetime-local"
                                                class="form-control fivefx-control @error('five.payed_at') is-invalid @enderror"
                                                wire:model.defer="five.payed_at">
                                        </div>
                                    </div>

                                    <div class="row g-2 mt-3">
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isCompleted"
                                                    wire:model.defer="five.is_completed" @if ($lockCompleted) disabled @endif>
                                                <label class="form-check-label" for="isCompleted">Concluido</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isSupervisioned"
                                                    wire:model.defer="five.is_supervisioned">
                                                <label class="form-check-label" for="isSupervisioned">Fiscalizado</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isPayed"
                                                    wire:model.defer="five.is_payed">
                                                <label class="form-check-label" for="isPayed">Pago</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isArchived"
                                                    wire:model.defer="five.is_archived">
                                                <label class="form-check-label" for="isArchived">Arquivado</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isPassive"
                                                    wire:model.defer="five.isPassive">
                                                <label class="form-check-label" for="isPassive">Passivo</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isReturned"
                                                    wire:model.defer="five.returned">
                                                <label class="form-check-label" for="isReturned">Retornado</label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="isVisible"
                                                    wire:model.defer="five.visible_partner">
                                                <label class="form-check-label" for="isVisible">Visivel</label>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($lockCompleted)
                                        <div class="alert alert-warning mt-2 mb-0">
                                            Concluido e data ficam bloqueados quando fiscalizado ou arquivado.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Evidencias</h6>
                                    @php $files = $five?->EvidenceFiles ?? collect(); @endphp
                                    <x-files.attachments :files="$files" :downloadAction="'downloadEvidence'"
                                        :deleteAction="'deleteEvidence'" :showHeader="false" :card="false" />

                                    <div class="mt-3">
                                        @livewire('files.evidence.upload-evidence', ['five' => $five, 'type' => 'D5', 'origin' => 'CONTROLE'], key('admin-d5-evidence-' . $five->id))
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mt-2">
                            <div class="col-12">
                                <div class="fivefx-section">
                                    <h6 class="fivefx-k mb-2">Producoes disponiveis</h6>
                                    @if (!empty($availableProductions))
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-dark table-hover border-secondary mb-0">
                                                <thead>
                                                    <tr class="text-secondary">
                                                        <th scope="col">ID</th>
                                                        <th scope="col">Servico</th>
                                                        <th scope="col">Usuario</th>
                                                        <th scope="col"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($availableProductions as $production)
                                                        <tr>
                                                            <td>{{ $production['id'] ?? '' }}</td>
                                                            <td>{{ $production['service']['service'] ?? '---' }}</td>
                                                            <td>{{ $production['user']['name'] ?? '---' }}</td>
                                                            <td class="text-end">
                                                                <button type="button"
                                                                    class="btn btn-outline-primary btn-sm fivefx-btn"
                                                                    wire:click="addProduction({{ $production['id'] ?? 0 }})">
                                                                    <i class="ri-add-line me-1"></i>Adicionar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary bg-opacity-25 mb-0">
                                            SEM PRODUCAO EXISTENTE PARA NOTA.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="fivefx-section">
                                    <h6 class="fivefx-k mb-2">Producoes vinculadas</h6>
                                    @if (!empty($linkedProductions))
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-dark table-hover border-secondary mb-0">
                                                <thead>
                                                    <tr class="text-secondary">
                                                        <th scope="col">ID</th>
                                                        <th scope="col">Servico</th>
                                                        <th scope="col">Usuario</th>
                                                        <th scope="col">D5</th>
                                                        <th scope="col"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($linkedProductions as $production)
                                                        <tr>
                                                            <td>{{ $production['id'] ?? '' }}</td>
                                                            <td>{{ $production['service']['service'] ?? '---' }}</td>
                                                            <td>{{ $production['user']['name'] ?? '---' }}</td>
                                                            <td>
                                                                <button type="button"
                                                                    class="btn btn-sm {{ ($production['dfive'] ?? false) ? 'btn-success' : 'btn-outline-secondary' }}"
                                                                    wire:click="toggleProductionD5({{ $production['id'] ?? 0 }})">
                                                                    {{ ($production['dfive'] ?? false) ? 'D5' : 'Normal' }}
                                                                </button>
                                                            </td>
                                                            <td class="text-end">
                                                                <button type="button"
                                                                    class="btn btn-outline-danger btn-sm fivefx-btn"
                                                                    wire:click="removeProduction({{ $production['id'] ?? 0 }})">
                                                                    <i class="ri-link-unlink-m me-1"></i>Desatribuir
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary bg-opacity-25 mb-0">
                                            SEM PRODUCAO RELACIONADO.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 d-none">
                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Nota D5</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.note_d5') is-invalid @enderror"
                                    wire:model.defer="five.note_d5">
                                @error('five.note_d5')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Local de Instalacao</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.loc_install') is-invalid @enderror"
                                    wire:model.defer="five.loc_install">
                                @error('five.loc_install')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fivefx-k">Conjunto</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.conjunto') is-invalid @enderror"
                                    wire:model.defer="five.conjunto">
                                @error('five.conjunto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fivefx-k">PEP</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.pep') is-invalid @enderror"
                                    wire:model.defer="five.pep">
                                @error('five.pep')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-1 d-none">
                            <div class="col-md-3">
                                <label class="form-label fivefx-k">E-PEP</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.e_pep') is-invalid @enderror"
                                    wire:model.defer="five.e_pep">
                                @error('five.e_pep')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Codificacao</label>
                                <div class="fivefx-select-wrap">
                                    <select class="form-select fivefx-select @error('five.codify') is-invalid @enderror"
                                        wire:model.defer="five.codify">
                                        <option value="" selected>Selecione</option>
                                        @foreach (SelectOptions::getD5codify() as $codifyD5)
                                            <option value="{{ $codifyD5->value }}">{{ $codifyD5->reason }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('five.codify')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Motivo</label>
                                <div class="fivefx-select-wrap">
                                    <select class="form-select fivefx-select @error('five.reason') is-invalid @enderror"
                                        wire:model.defer="five.reason">
                                        <option value="" selected>Selecione</option>
                                        @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                            <option value="{{ $reasonD5->value }}">{{ $reasonD5->reason }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('five.reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Empresa</label>
                                <input type="text" class="form-control fivefx-control" value="{{ $five->company?->name }}"
                                    readonly>
                            </div>
                        </div>

                        <div class="row g-3 mt-1 d-none">
                            <div class="col-md-6">
                                <label class="form-label fivefx-k">Sintomas</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.sintoms') is-invalid @enderror"
                                    wire:model.defer="five.sintoms">
                                @error('five.sintoms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fivefx-k">Responsavel</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.name') is-invalid @enderror"
                                    wire:model.defer="five.name">
                                @error('five.name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3 d-none">
                            <label for="fiveDescription" class="form-label fivefx-k mb-0">Descricao</label>
                            <textarea id="fiveDescription"
                                class="form-control fivefx-control fivefx-textarea @error('five.description') is-invalid @enderror"
                                wire:model.defer="five.description"></textarea>
                            @error('five.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-3 d-none">
                            <label class="form-label fivefx-k">Datas</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fivefx-k">Despachado em</label>
                                    <input type="datetime-local"
                                        class="form-control fivefx-control @error('five.dispatch_at') is-invalid @enderror"
                                        wire:model.defer="five.dispatch_at">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fivefx-k">Concluido em</label>
                                    <input type="datetime-local"
                                        class="form-control fivefx-control @error('five.completed_at') is-invalid @enderror"
                                        wire:model.defer="five.completed_at"
                                        @if ($lockCompleted) disabled @endif>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fivefx-k">Fiscalizado em</label>
                                    <input type="datetime-local"
                                        class="form-control fivefx-control @error('five.supervisioned_at') is-invalid @enderror"
                                        wire:model.defer="five.supervisioned_at">
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 d-none">
                            <label class="form-label fivefx-k">Status</label>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isCompleted"
                                            wire:model.defer="five.is_completed" @if ($lockCompleted) disabled @endif>
                                        <label class="form-check-label" for="isCompleted">Concluido</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isSupervisioned"
                                            wire:model.defer="five.is_supervisioned">
                                        <label class="form-check-label" for="isSupervisioned">Fiscalizado</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isPayed"
                                            wire:model.defer="five.is_payed">
                                        <label class="form-check-label" for="isPayed">Pago</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isArchived"
                                            wire:model.defer="five.is_archived">
                                        <label class="form-check-label" for="isArchived">Arquivado</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isPassive"
                                            wire:model.defer="five.isPassive">
                                        <label class="form-check-label" for="isPassive">Passivo</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isReturned"
                                            wire:model.defer="five.returned">
                                        <label class="form-check-label" for="isReturned">Retornado</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isVisible"
                                            wire:model.defer="five.visible_partner">
                                        <label class="form-check-label" for="isVisible">Visivel</label>
                                    </div>
                                </div>
                            </div>
                            @if ($lockCompleted)
                                <div class="alert alert-warning mt-2 mb-0">
                                    Concluido e data ficam bloqueados quando fiscalizado ou arquivado.
                                </div>
                            @endif
                        </div>

                        <div class="mt-3">
                            <label class="form-label fivefx-k">Pagamentos</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fivefx-k">Pago em</label>
                                    <input type="datetime-local"
                                        class="form-control fivefx-control @error('five.payed_at') is-invalid @enderror"
                                        wire:model.defer="five.payed_at">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer fivefx-footer py-2 d-flex align-items-center">
                        <button type="button" class="btn btn-light fivefx-btn"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fivefx-btn" wire:loading.attr="disabled">
                            <span wire:loading.remove>Salvar</span>
                            <span wire:loading>Salvando...</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <style>
        .finish-five .fivefx-section {
            padding: 1rem;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 14px;
        }

        .finish-five .upload-zone {
            color: #e2e8f0;
            border-color: rgba(226, 232, 240, .55) !important;
        }

        .finish-five .upload-zone .text-primary {
            color: #93c5fd !important;
        }

        .finish-five .upload-zone .text-muted {
            color: #cbd5f5 !important;
        }

        .finish-five .upload-zone .ri-cloud-line {
            color: #93c5fd !important;
        }


        .finish-five .fivefx-card {
            background: radial-gradient(1200px 600px at 100% -20%, rgba(37, 99, 235, .12), transparent 40%),
                radial-gradient(1200px 600px at -10% 120%, rgba(14, 165, 233, .10), transparent 35%),
                linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, .55), 0 12px 30px rgba(0, 0, 0, .35);
            backdrop-filter: saturate(120%) blur(2px);
            transition: transform .18s ease, box-shadow .18s ease
        }

        .finish-five .fivefx-header {
            background: rgba(17, 24, 39, .9);
            border: 0;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .06)
        }

        .finish-five .fivefx-footer {
            background: rgba(17, 24, 39, .9);
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .06);
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px
        }

        .finish-five .btn-close {
            filter: invert(1);
            opacity: .9
        }

        .finish-five .fivefx-body {
            padding: 1.1rem 1.25rem
        }

        .finish-five .fivefx-pill {
            display: inline-block;
            background: #0ea5e9;
            color: #fff;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .77rem;
            font-weight: 700
        }

        .finish-five .fivefx-k {
            color: #9ca3af;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 2px
        }

        .finish-five .fivefx-control {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #e5e7eb;
            border-radius: 12px;
            padding: .6rem .85rem;
            line-height: 1.35;
            min-height: 42px;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease
        }

        .finish-five .fivefx-control::placeholder {
            color: #9ca3af;
            opacity: .95
        }

        .finish-five .fivefx-control:focus {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15), inset 0 0 0 9999px rgba(255, 255, 255, .01);
            color: #f3f4f6
        }

        .finish-five .fivefx-textarea {
            min-height: 160px;
            max-height: 60vh;
            resize: vertical;
            overflow: auto;
        }

        .finish-five .fivefx-select-wrap {
            position: relative
        }

        .finish-five .fivefx-select {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, .18);
            color: #e5e7eb;
            border-radius: 12px;
            min-height: 42px;
            padding: .55rem 2.2rem .55rem .85rem;
            appearance: none;
            background-image:
                var(--bs-form-select-bg-img),
                linear-gradient(#0f172a, #0f172a);
            background-repeat: no-repeat;
            background-position: right .8rem center, 0 0;
            background-size: 16px 12px, 100% 100%;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease
        }

        .finish-five .fivefx-select:focus {
            background-color: #0b1220;
            color: #f3f4f6;
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15)
        }

        .finish-five .form-check-input {
            background-color: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .25)
        }

        .finish-five .form-check-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6
        }

        .finish-five .fivefx-btn {
            font-weight: 700;
            border-radius: 10px
        }

        .finish-five .btn-light {
            color: #111827;
            background: #e5e7eb;
            border: 1px solid #e5e7eb
        }

        .finish-five .btn-primary {
            background: #2563eb;
            border-color: #2563eb
        }

        .finish-five {
            color-scheme: dark;
        }
    </style>

    <script>
        (function() {
            const modalEl = document.getElementById('adminEditFiveModal');
            if (!modalEl) return;

            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.control.d5-edit', 'resetForm');
            });
        })();
    </script>
</div>
