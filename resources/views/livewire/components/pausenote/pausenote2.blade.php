@php
    use App\Helpers\SelectOptions;
@endphp
<div>

    <div wire:ignore.self class="modal fade" id="pauseModal" tabindex="-1" aria-labelledby="pauseModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                @if ($production)
                    <div class="modal-header text-bg-warning">
                        <h1 class="modal-title fs-5 " id="pauseModalLabel">PAUSAR NOTA</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body edp-bg-stategrey-50">

                        <div class="card">
                            <div class="card-body">
                                <dl class="row p-0 m-0">
                                    <dt class="col-sm-4 fs-5">Nota/Ov:</dt>
                                    <dd class="col-sm-8 fs-5">{{ $production->Note->note }}</dd>
                                    <dt class="col-sm-4 fs-5">Data:</dt>
                                    <dd class="col-sm-8 fs-5">
                                        {{ date('d/m/Y', strToTime($production->Note->dt_status)) }}</dd>
                                    <dt class="col-sm-4 fs-5">Paradas:</dt>
                                    <dd class="col-sm-8 fs-5">{{ $count }} / {{ $limite }}</dd>
                                </dl>
                            </div>
                        </div>



                        <div class="mb-3">
                            <label for="exampleFormControlTextarea1" class="form-label">Detalhe motivo para Pausa<span
                                    class="text-danger fw-bold">*</span></label>
                            <textarea class="form-control border border-1 border-secondary" id="" rows="5"
                                placeholder="< Obrigatório >" wire:model.defer="info"></textarea>
                        </div>


                    </div>
                @endif
                <div class="modal-footer edp-bg-stategrey-100">

                    <button class="btn btn-primary me-2 col-4" wire:click.prevent="go_pause">PAUSAR</button>
                    <button class="btn btn-danger me-2 col-4" wire:click.prevent="clean" class="btn-close"
                        data-bs-dismiss="modal">CANCELAR</button>

                </div>
            </div>
        </div>
    </div>


</div>
