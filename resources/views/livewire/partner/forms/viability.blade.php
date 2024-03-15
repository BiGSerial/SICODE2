<div>
    <x-show-loading />
    {{-- @dump($note) --}}
    @if ($note)
        <div class="container">
            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="fw-bold">DADOS DA OBRA</h4>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 border-start border-end border-4 border-secondary">
                            <p class="fw-bold my-0">Nota/Ov:</p>
                            <p class="my-0 fs-4 text-uppercase">{{ $note->note }}</p>
                        </div>
                        <div class="col-md-6 border-start border-end border-4 border-secondary">
                            <p class="fw-bold my-0">Ordem:</p>
                            @if ($note->Viabilities->count())
                                @foreach ($note->Viabilities as $order)
                                    <p class="my-0 fs-4 text-uppercase">{{ $order->Order->ordem }}</p>
                                @endforeach
                            @endif
                        </div>
                        @if ($note->group1)
                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                <p class="fw-bold my-0">Area:</p>
                                <p class="my-0 fs-4 text-uppercase">{{ $note->group1 }}</p>
                            </div>
                        @endif

                        @if ($note->rubrica)
                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                <p class="fw-bold my-0">Tipo:</p>
                                <p class="my-0 fs-4 text-uppercase">{{ $note->rubrica }}</p>
                            </div>
                        @endif
                        @if ($note->group2)
                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                <p class="fw-bold my-0">Grupo2:</p>
                                <p class="my-0 fs-4 text-uppercase">{{ $note->group2 }}</p>
                            </div>
                        @endif

                        @if ($note->material)
                            <div class="col-md-6 border-start border-end border-4 border-secondary">
                                <p class="fw-bold my-0">Descricao:</p>
                                <p class="my-0 fs-4 text-uppercase">{{ $note->material }}</p>
                            </div>
                        @endif

                        @if ($this->cities)
                            @if ($note->nexp)
                                <div class="col-md-6 border-start border-end border-4 border-secondary">
                                    <p class="fw-bold my-0">Regiao:</p>
                                    <p class="my-0 fs-4 text-uppercase">
                                        {{ $this->cities->where('rdMunicipio', $note->nexp)->first()->regiao }}</p>
                                </div>
                                <div class="col-md-6 border-start border-end border-4 border-secondary">
                                    <p class="fw-bold my-0">Municipio:</p>
                                    <p class="my-0 fs-4 text-uppercase">
                                        {{ $this->cities->where('rdMunicipio', $note->nexp)->first()->municipio }}</p>
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
                        <label class="form-label">Nescessário Alteração?</label>
                        <select class="form-select border border-secondary fs-4" wire:model="changes">
                            <option value=""> ---- </option>
                            <option value="SIM"> SIM </option>
                            <option value="NAO"> NÃO </option>
                        </select>
                    </div>

                    @if ($changes === 'SIM')
                        <div class="mb-3">
                            <label class="form-label">Detalhe brevemente o motivo da necessidade de alteração:</label>
                            <textarea class="form-control border border-1 border-secondary" cols="30" rows="10"
                                wire:model.defer="result.reason"></textarea>
                        </div>
                        <div class="mb-3">

                            <label for="customRange2" class="form-label">Indique o nível de alteração:</label>
                            <input type="range" class="form-range border border-1 border-secondary" min="0"
                                max="10" wire:model="result.sizechange" value="0">
                            <div class="progress" role="progressbar" aria-label="Basic example" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100" style="height: 15px;">
                                <div class="progress-bar progress-bar-lg progress-bar-striped progress-bar-animated bg-danger"
                                    style="width: {{ isset($result['sizechange']) ? $result['sizechange'] * 10 : 0 }}%;">
                                    {{ isset($result['sizechange']) ? $result['sizechange'] * 10 : 0 }}%
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($changes)
                        <div class="mb-3 col-3">
                            <label class="form-label">Responsável pelo informe:</label>
                            <input type="text" class="form-control border border-1 border-secondary"
                                wire:model.defer="result.responsible" />
                        </div>
                        @if ($note->Viabilities->count())
                            <div class="mb-3 col-3">
                                <label class="form-label">Selecione a Ordem deste Informe:</label>
                                <select name="" id=""
                                    class="form-select border border-1 border-secondary fs-4"
                                    wire:model="result.viability_id">
                                    <option selected>---</option>
                                    @foreach ($note->Viabilities as $viability)
                                        <option value="{{ $viability->id }}">{{ $viability->Order->ordem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                    @endif
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button class="btn btn-danger m-2" wire:click.prevent="toCancelForm">CANCELAR</button>
                    <button class="btn btn-primary m-2">SALVAR</button>
                </div>
            </div>

        </div>
    @endif
</div>

@push('script')
    <script>
        window.addEventListener('alertar', function(e) {

            const Confirmation = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });

            Swal.fire({
                title: e.detail.title,
                html: e.detail.msg,
                icon: e.detail.icon,
                showCancelButton: true,
                confirmButtonText: e.detail.btnOktxt,
                cancelButtonText: e.detail.btnCanceltxt,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    Livewire.emit(e.detail.action, e.detail.chave)

                } else if (
                    /* Read more about handling dismissals below */
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    Swal.fire(
                        e.detail.cancel_titulo,
                        e.detail.cancel_msg,
                        'success'
                    )
                }
            })
        });
    </script>
@endpush
