<div>
    <x-show-loading />
    {{-- @dump($note) --}}
    @section('title')
        ENTREGA VIABILIDADE TÉCNICA - {{ $note->note }}
    @endsection


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
                        <div class="col-3 mb-3">
                            <label class="form-label">Motivo da Alteração: <span
                                    class="text-danger fw-bold">*</span></label>
                            <select class="form-select border border-secondary fs-4" wire:model="result.reason">
                                <option value=""> ---- </option>
                                <option value="AJUSTE MATERIAL"> AJUSTE DE MATERIAL </option>
                                <option value="AJUSTE DE PROJETO"> AJUSTE DE PROJETO </option>
                                <option value="PROPOSTA MELHORIA"> PROPOSTA DE MELHORIA </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Detalhe brevemente o motivo da necessidade de alteração: <span
                                    class="text-danger fw-bold">*</span></label>
                            <textarea class="form-control border border-1 border-secondary fs-4" cols="30" rows="8"
                                wire:model.defer="result.reason_text"></textarea>
                        </div>
                        <div class="mb-3">

                            <label for="customRange2" class="form-label">Indique o nível de alteração: <span
                                    class="text-danger fw-bold">*</span></label>
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

                        {{-- <div class="mb-3">
                            <div class="my-2"> <button class="btn btn-sm btn-primary"
                                    onclick="document.getElementById('file-input').click()">CARREGAR CROQUI</button>
                                <span class="text-danger fw-bold">*</span>
                            </div>
                            <div x-data="{ isUploading: false, progress: 0 }" x-on:livewire-upload-start="isUploading = true"
                                x-on:livewire-upload-finish="isUploading = false"
                                x-on:livewire-upload-error="isUploading = false"
                                x-on:livewire-upload-progress="progress = $event.detail.progress">

                                <form wire:submit.prevent="saveFile">
                                    <input type="file" id="file-input" multiple wire:model="files" hidden>

                                </form>

                                <div x-show="isUploading" class="mb-3">

                                    <div class="progress my-0" role="progressbar" aria-label="Danger example"
                                        aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"
                                        style="width: 100%; border-radius: 0;">
                                        <span class="progress-bar bg-danger" x-bind:style="`width: ${progress}%`"
                                            x-text="`${progress}%`">
                                    </div>
                                </div>
                            </div>
                            <div class="edp-bg-gray mb-3 py-2 rounded ">
                                <div class="container">

                                    @if (count($show_files))
                                        @foreach ($show_files as $show)
                                            <div
                                                class="col-5 border border-secondary d-flex justify-content-between align-items-center p-0 mb-2 bg-white">
                                                <div class="p-1 m-0 border-end border-secondary"><i
                                                        class="bx bxs-file-{{ $show['ext'] }} text-danger fs-4"></i>
                                                </div>
                                                <div class="p-1 m-0 text-center no-wrap">
                                                    <p class="my-0 py-0">
                                                        {{ $show['name'] }}
                                                    </p>
                                                    <p class="my-0 py-0 text-danger" style="font-size: 12px;">
                                                        {{ $show['old_name'] }}
                                                    </p>
                                                </div>
                                                <div class="p-1 m-0 border-start border-secondary">
                                                    <i class="bx bxs-trash text-danger fs-4"
                                                        wire:click.prevent="delete_file({{ $show['id'] }})"
                                                        style="cursor: pointer;"></i>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="my-2 py-2 text-center">
                                            <h4 class="fw-bold">SEM ARQUIVOS</h4>
                                        </div>
                                    @endif

                                </div>
                            </div>
                        </div> --}}

                        @livewire('files.filepartners', ['note' => $note, 'needFiles' => true], key('FilesPartners'))
                    @endif

                    @if ($changes != '')
                        <div class="mb-3 col-3">
                            <label class="form-label">Responsável pelo informe: <span
                                    class="text-danger fw-bold">*</span></label>
                            <input type="text" class="form-control border border-1 border-secondary"
                                wire:model.defer="result.responsible" />
                        </div>
                        {{-- @if ($note->Viabilities->count())
                            <div class="mb-3 col-3">
                                <label class="form-label">Selecione a Ordem deste Informe: <span
                                        class="text-danger fw-bold">*</span></label>
                                <select name="" id=""
                                    class="form-select border border-1 border-secondary fs-4"
                                    wire:model="result.viability_id">
                                    <option value="" selected>---</option>
                                    @if ($note->Viabilities->count() > 1)
                                        <option value="0" selected>TODOS</option>
                                    @endif
                                    @foreach ($note->Viabilities as $viability)
                                        <option value="{{ $viability->id }}">{{ $viability->Order->ordem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif --}}
                    @endif
                </div>
                <div class="card-footer d-flex justify-content-end">
                    {{ $hasFile }}
                    <button class="btn btn-danger m-2" wire:click.prevent="toCancelForm">CANCELAR</button>
                    <button class="btn btn-primary m-2" @disabled($changes === '')
                        wire:click.prevent="toSaveForm">SALVAR</button>
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
