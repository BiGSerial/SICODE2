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
            <div class="mb-3 col-3">
                <label for="email" class="form-label">Icone</label>
                <input wire:model.debounce.1s="icon" type="text" class="form-control" name="status" id="icon">
                @if ($icon)
                    <i class="{{ $icon }} fw-bold fs-4 align-middle text-primary"></i>
                @endif
            </div>

        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="project" wire:model.defer="project">
            <label class="form-check-label" for="project">Projeto</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="project" wire:model.defer="returnable">
            <label class="form-check-label" for="project">Retornável</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="construction" wire:model.defer="construction">
            <label class="form-check-label" for="construction">Construção</label>
        </div>

    </form>

</div>
