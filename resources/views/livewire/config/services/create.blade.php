<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <form>
        <div class="mb-3">
            <label for="create-service-name" class="form-label">Nome do serviço</label>
            <input wire:model.defer="service" type="text" class="form-control" name="service"
                id="create-service-name" placeholder="Ex.: Levantamento" required>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <x-icon-picker wire-model="icon" :selected="$icon" id="create" />
            </div>

            <div class="col-md-6">
                <label for="create-service-folder" class="form-label">Diretório padrão</label>
                <select class="form-select" id="create-service-folder" wire:model.defer="folder_s">
                    <option value="">Selecione um diretório</option>
                    @forelse ($folders ?? [] as $folder)
                        <option value="{{ $folder }}">{{ mb_strtoupper($folder) }}</option>
                    @empty
                        <option value="" disabled>Nenhum diretório disponível</option>
                    @endforelse
                </select>
            </div>

            <div class="col-md-6">
                <label for="create-service-status" class="form-label">Status</label>
                <input wire:model.defer="status" type="number" class="form-control" name="status"
                    id="create-service-status">
                <div class="form-text">Código de status usado para identificar o serviço.</div>
            </div>
        </div>

        <hr class="my-3">

        <div class="mb-2 fw-semibold small text-uppercase text-muted">Classificação do serviço</div>
        <div class="d-flex flex-wrap gap-4">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="create-service-project"
                    wire:model.defer="project">
                <label class="form-check-label" for="create-service-project">Projeto</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="create-service-construction"
                    wire:model.defer="construction">
                <label class="form-check-label" for="create-service-construction">Construção</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="create-service-returnable"
                    wire:model.defer="returnable">
                <label class="form-check-label" for="create-service-returnable">Retornável</label>
            </div>
        </div>
        <div class="form-text">Ao menos uma das opções Projeto/Construção precisa ser marcada.</div>
    </form>
</div>
