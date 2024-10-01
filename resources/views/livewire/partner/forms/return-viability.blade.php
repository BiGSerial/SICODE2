<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="returnViabilityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content  edp-bg-gray">
                <div class="modal-header edp-bg-seoweedgreen-100 text-white">
                    <h5 class="modal-title">ENTREGA DA VIABILIDADE</h5>
                </div>
                <div class="modal-body">
                    <div class="container">
                        @if ($viability)
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="fw-bold">DADOS DA OBRA</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        @if ($viability->Note->client)
                                            <div
                                                class="col-md-12 border-4 border-start border-end border-2 border-bottom border-secondary">
                                                <p class="fw-bold my-0">Cliente:</p>
                                                <p class="my-0 fs-4 text-uppercase">{{ $viability->Note->client }}</p>
                                            </div>
                                        @endif
                                        <div class="col-md-6 border-start border-end border-4 border-secondary">
                                            <p class="fw-bold my-0">Nota/Ov:</p>
                                            <p class="my-0 fs-4 text-uppercase">{{ $viability->Note->note }}</p>
                                        </div>
                                        <div class="col-md-6 border-start border-end border-4 border-secondary">
                                            <p class="fw-bold my-0">Ordem:</p>
                                            @if ($viability->Note->Orders)
                                                @foreach ($viability->Note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                    <p class="my-0 fs-4 text-uppercase">{{ $order->ordem }}</p>
                                                @endforeach
                                            @endif
                                        </div>

                                        @if ($viability->Note->group1)
                                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                                <p class="fw-bold my-0">Area:</p>
                                                <p class="my-0 fs-4 text-uppercase">{{ $viability->Note->group1 }}</p>
                                            </div>
                                        @endif

                                        @if ($viability->Note->rubrica)
                                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                                <p class="fw-bold my-0">Tipo:</p>
                                                <p class="my-0 fs-4 text-uppercase">{{ $viability->Note->rubrica }}</p>
                                            </div>
                                        @endif
                                        @if ($viability->Note->group2)
                                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                                <p class="fw-bold my-0">Grupo2:</p>
                                                <p class="my-0 fs-4 text-uppercase">{{ $viability->Note->group2 }}</p>
                                            </div>
                                        @endif

                                        @if ($viability->Note->material)
                                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                                <p class="fw-bold my-0">Descricao:</p>
                                                <p class="my-0 fs-4 text-uppercase">{{ $viability->Note->material }}</p>
                                            </div>
                                        @endif

                                        @if ($this->cities)
                                            @if ($viability->Note->nexp)
                                                <div class="col-md-6 border-start border-end border-4 border-secondary">
                                                    <p class="fw-bold my-0">Regiao:</p>
                                                    <p class="my-0 fs-4 text-uppercase">
                                                        {{ $this->cities->where('rdMunicipio', $viability->Note->nexp)->first()->regiao }}
                                                    </p>
                                                </div>
                                                <div class="col-md-6 border-start border-end border-4 border-secondary">
                                                    <p class="fw-bold my-0">Municipio:</p>
                                                    <p class="my-0 fs-4 text-uppercase">
                                                        {{ $this->cities->where('rdMunicipio', $viability->Note->nexp)->first()->municipio }}
                                                    </p>
                                                </div>
                                            @endif
                                        @endif

                                    </div>
                                </div>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4 class="fw-bold">INFORME VIABILIDADE DE OBRA</h4>
                                </div>
                                <div class="card-body">
                                    <div class="col-3 mb-3">
                                        <label class="form-label">Nescessário Alteração? <span
                                                class="text-danger fs-6">* <i>(Rejeita a Viabilidade)</i></span></label>
                                        <select class="form-select border border-secondary fs-4" wire:model="changes">
                                            <option value=""> ---- </option>
                                            <option value="SIM"> SIM </option>
                                            <option value="NAO"> NÃO </option>
                                        </select>
                                    </div>
                                    @if ($changes)
                                        @if ($changes === 'SIM')
                                            <div class="col-3 mb-3">
                                                <label class="form-label">Motivo da Alteração: <span
                                                        class="text-danger fw-bold">*</span></label>
                                                <select
                                                    class="form-select border border-secondary fs-4 @error('reason.reason') is-invalid @enderror"
                                                    wire:model="reason.reason">
                                                    <option value=""> ---- </option>
                                                    <option value="AJUSTE MATERIAL"> AJUSTE DE MATERIAL </option>
                                                    <option value="AJUSTE DE PROJETO"> AJUSTE DE PROJETO </option>
                                                    <option value="PROPOSTA MELHORIA"> PROPOSTA DE MELHORIA </option>
                                                </select>
                                                @error('reason.reason')
                                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Detalhe brevemente o motivo da necessidade de
                                                    alteração: <span class="text-danger fw-bold">*</span></label>
                                                <textarea class="form-control border border-1 border-secondary fs-4 @error('reason.description') is-invalid @enderror"
                                                    cols="30" rows="8" wire:model.defer="reason.description"></textarea>
                                                @error('reason.description')
                                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="customRange2" class="form-label">Indique o nível de
                                                    alteração:
                                                    <span class="text-danger fw-bold">*</span>
                                                </label>
                                                <input type="range"
                                                    class="form-range border border-1 border-secondary @error('reason.changes') is-invalid @enderror"
                                                    min="0" max="10" wire:model="reason.changes"
                                                    value="0">
                                                <div class="progress" role="progressbar" aria-label="Basic example"
                                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                                    style="height: 15px;">
                                                    <div class="progress-bar progress-bar-lg progress-bar-striped progress-bar-animated bg-danger"
                                                        style="width: {{ isset($reason['changes']) ? $reason['changes'] * 10 : 0 }}%;">
                                                        {{ isset($reason['changes']) ? $reason['changes'] * 10 : 0 }}%
                                                    </div>
                                                </div>
                                                @error('reason.changes')
                                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                        <div class="mb-3">
                                            @livewire('files.manager.create-gen-files', ['note' => $viability->Note, 'service' => 'VIABILIDADE'], key('FilesUploadVIability'))
                                        </div>


                                        <div class="mb-3 col-3">
                                            <label class="form-label">Responsável pelo informe: <span
                                                    class="text-danger fw-bold">*</span></label>
                                            <input type="text"
                                                class="form-control border border-1 border-secondary @error('reason.responsible') is-invalid @enderror"
                                                wire:model.defer="reason.responsible" />
                                            @error('reason.responsible')
                                                <span class="invalid-feedback d-block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                    @endif



                                </div>
                            </div>
                        @else
                            <h3 class="center-text">DADOS NÃO CARREGADO</h3>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    {{ $hasFile ? 'SIM' : 'Não' }}<button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="save">SUBMETER
                        VIABILIDADE</button>
                </div>
            </div>
        </div>
    </div>



</div>
