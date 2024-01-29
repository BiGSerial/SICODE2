<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <form>

        <div class="row g-2">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input wire:model.defer="email" type="email" class="form-control" name="email" id="email">
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Nome</label>
                <input wire:model.defer="name" type="text" class="form-control" name="name" id="name">
            </div>
            <div class="mb-3">
                <label for="street" class="form-label">Endereço</label>
                <input wire:model.defer="street" type="text" class="form-control" name="street" id="street">
            </div>
            <div class="mb-3">
                <label for="complement" class="form-label">Complemento</label>
                <input wire:model.defer="complement" type="text" class="form-control" name="complement"
                    id="complement">
            </div>
            <div class="mb-3 col-8">
                <label for="city" class="form-label">Município</label>
                <input wire:model.defer="city" type="text" class="form-control" name="city" id="city">
            </div>
            <div class="mb-3 col-4">
                <label for="uf" class="form-label">UF</label>
                <input wire:model.defer="uf" type="text" class="form-control" name="uf" id="uf">
            </div>
            <div class="mb-3">
                <label for="telephone" class="form-label">Telefone</label>
                <input wire:model.defer="telephone" type="text" class="form-control" name="telephone" id="telephone">
            </div>
        </div>

    </form>


</div>
