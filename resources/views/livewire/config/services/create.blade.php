<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <form>

        <div class="row g-2">
            <div class="mb-3">
                <label for="email" class="form-label">Serviço</label>
                <input wire:model.defer="service" type="text" class="form-control" name="service" id="service" required>
            </div>
            <div class="mb-3 col-3">
                <label for="email" class="form-label">Status</label>
                <input wire:model.defer="status" type="number" class="form-control" name="status" id="status"
                    required>
            </div>
            <div class="mb-3 col-4">
                <label for="email" class="form-label">Diretório Padrão</label>
                <select class="form-select" aria-label="Default select example" wire:model.defer="folder_s">
                    <option selected>Selecione</option>
                    @if (isset($folders) && count($folders))
                        @foreach ($folders as $folder)
                            <option value="{{ $folder }}">{{ mb_strtoupper($folder) }}</option>
                        @endforeach
                    @endif
                </select>

            </div>
        </div>

    </form>

</div>
