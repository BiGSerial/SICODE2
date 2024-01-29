<div>
    @if ($show_pause)
        <div class="card">
            <div class="card-body">
                <dl class="row p-0 m-0">
                    <dt class="col-sm-4 fs-5">Nota/Ov:</dt>
                    <dd class="col-sm-8 fs-5">{{ $note->note }}</dd>
                    <dt class="col-sm-4 fs-5">Data:</dt>
                    <dd class="col-sm-8 fs-5">{{ date('d/m/Y', strToTime($note->dt_status)) }}</dd>
                    <dt class="col-sm-4 fs-5">Paradas:</dt>
                    <dd class="col-sm-8 fs-5">{{ $count }} / {{ $limit_pause }}</dd>
                </dl>
            </div>
        </div>
        <div class="mb-3">
            <label for="exampleFormControlTextarea1" class="form-label">Motivo para Pausa<span
                    class="text-danger fw-bold">*</span></label>
            <textarea class="form-control border border-1 border-secondary" id="" rows="5"
                placeholder="< Obrigatório >" wire:model.defer="info"></textarea>
        </div>
        <div class="row g-3 justify-content-end">
            <button class="btn btn-primary me-2 col-4" wire:click.prevent="go_pause">PAUSAR</button>
            <button class="btn btn-danger me-2 col-4" wire:click.prevent="clean" class="btn-close"
                data-bs-dismiss="modal">CANCELAR</button>
        </div>
    @else
        <div class="card-body my-5 text-center">
            <div class="spinner-border text-warning mb-3" style="width: 50px; height: 50px;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h3 class="text-center">CARREGANDO.... AGUARDE.</h3>
        </div>
    @endif
</div>
