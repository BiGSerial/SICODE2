<div x-data="{ confirm: false, text: '' }">
    <x-show-loading />
    <p class="fw-bold fs-6 my-0 py-0">Comentário:<span class="text-danger">*</span></p>
    <p class="mb-2 mb-0 py-0">
        <textarea class="form-control border border-secondary" cols="30" rows="6" wire:model.defer="comment"></textarea>
    </p>
    {{-- <div class="form-check">
        <input class="form-check-input border-1 border-secondary" type="checkbox" wire:model.defer="restrict">
        <label class="form-check-label" for="flexCheckDefault">
            Restrito
        </label>
    </div> --}}

    <p class="fw-bold fs-6 my-0 py-0">Selecione o Serviço para Devolução (RI):</p>
    <p class="mb-2 mb-3 py-0">
        <select class="form-select border border-primary" aria-label="Default select example"
            wire:model.defer="service_s">
            <option value="" selected>Selecione o Serviço</option>
            @if ($services)
                @foreach ($services as $service)
                    @if ($service->canReturn)
                        <option value="{{ $service->uuid }}">{{ $service->service }}</option>
                    @endif
                @endforeach
            @endif
        </select>
    </p>

    <div @click.away="confirm=false">
        <div class="text-center mb-3" x-show="!confirm">
            <button class="btn btn-sm btn-primary align-self-end" @click="confirm=true">ENVIAR</button>
        </div>

        <div class="card border border-secondary border-2" x-show="confirm" style="display: none;">
            <div class="card-body">
                <h4 class="text-center fw-bold mb-3">Deseja realmente retornar {{ $list->note }}?</h4>
                <p class="text-justify p-2 border border-1 rounded shadow">
                    Antes de enviar, verifique se o seviço para retorno, está devidamente selecionado, e se o texto de
                    comentário condiz para informação do receptor.
                </p>
                <div class="clear-fix">
                    <div class="center-text mt-2 d-flex justify-content-center">
                        <button class="btn btn-primary btn-sm" wire:click.prevent="returnD5">SIM</button>
                        <button class="btn btn-danger btn-sm ms-2" @click="confirm=false">NÃO</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
