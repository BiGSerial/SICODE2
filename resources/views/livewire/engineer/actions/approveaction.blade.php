<div x-data="{ show1: false, show2: false, text: '' }">
    <x-show-loading />
    <p class="fw-bold fs-6 my-0 py-0">Comentário:</p>
    <p class="mb-2 mb-0 py-0">
        <textarea class="form-control border border-secondary" cols="30" rows="6" wire:model.defer="comment"></textarea>
    </p>
    {{-- <div class="form-check">
        <input class="form-check-input border-1 border-secondary" type="checkbox" wire:model.defer="restrict">
        <label class="form-check-label" for="flexCheckDefault">
            Restrito
        </label>
    </div> --}}
    <div class="d-flex justify-content-end mb-3" x-show="show1 = false && show2 = false">
        <button class="btn btn-sm btn-danger mx-2"
            @click="show1 = true, show2 = false, text = 'Deseja comfirmar Improcedente?'">IMPROCEDENTE</button>
        <button class="btn btn-sm btn-primary mx-2"
            @click="show1 = false, show2 = true, text = 'Deseja comfirmar Procedente?'">PROCEDENTE</button>
    </div>


    <div class="card" x-show="show1 || show2">
        <div class="card-body">
            <p class="fs-4 fw-bold text-center mb-3" x-text="text"></p>
            <div class="d-flex justify-content-center">

                <button class="btn btn-sm btn-primary mx-2" wire:click.prevent="approved" x-show="show1">SIM</button>
                <button class="btn btn-sm btn-primary mx-2" wire:click.prevent="desapproved" x-show="show2">SIM</button>
                <button class="btn btn-sm btn-danger mx-2" @click="show1 = show2 = false">CANCELAR</button>
            </div>
        </div>
    </div>

</div>
