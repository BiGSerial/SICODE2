@php
    $field = fn (string $name) => $errors->has($name) ? 'is-invalid' : (filled($this->{$name}) ? 'is-valid' : '');
@endphp
<div>
    <x-show-loading />

    <form wire:submit.prevent="save" novalidate>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="form-check form-switch mb-0">
                    <input wire:model="isBranch" class="form-check-input" type="checkbox" id="isBranch" role="switch">
                    <label class="form-check-label fw-semibold" for="isBranch">Cadastrar como unidade de uma empresa existente</label>
                    <div class="form-text">Use para filiais, obras ou regionais que pertencem a uma empresa matriz.</div>
                </div>
                @if ($isBranch)
                    <div class="mt-3">
                        <label class="form-label" for="parent_id">Empresa concentradora <span class="text-danger">*</span></label>
                        <select wire:model="parent_id" id="parent_id" class="form-select {{ $field('parent_id') }}">
                            <option value="">Selecione a empresa concentradora</option>
                            @foreach ($parentCompanies as $parentCompany)
                                <option value="{{ $parentCompany->id }}">{{ $parentCompany->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="fw-bold mb-3"><i class="ri-building-line text-success"></i> Identificação</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="name" class="form-label">Nome <span class="text-danger">*</span></label>
                        <input wire:model.lazy="name" type="text" class="form-control {{ $field('name') }}" id="name" maxlength="255" autocomplete="off">
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-7">
                        <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input wire:model.lazy="email" type="email" class="form-control {{ $field('email') }}" id="email" placeholder="contato@empresa.com.br" maxlength="255" autocomplete="off">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label for="telephone" class="form-label">Telefone</label>
                        <input wire:model.lazy="telephone" type="text" inputmode="tel" class="form-control {{ $field('telephone') }}" id="telephone" placeholder="(27) 99999-9999" maxlength="20" autocomplete="off"
                            x-data x-on:input="let d = $event.target.value.replace(/\D/g, '').slice(0, 11); $event.target.value = d.length > 10 ? d.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3') : d.length > 6 ? d.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3') : d.length > 2 ? d.replace(/(\d{2})(\d{0,5})/, '($1) $2') : d">
                        @error('telephone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="fw-bold mb-3"><i class="ri-map-pin-line text-success"></i> Endereço principal <span class="text-muted fw-normal small">(opcional)</span></div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="street" class="form-label">Endereço</label>
                        <input wire:model.defer="street" type="text" class="form-control" id="street" maxlength="255" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="complement" class="form-label">Complemento</label>
                        <input wire:model.defer="complement" type="text" class="form-control" id="complement" maxlength="255" autocomplete="off">
                    </div>
                    <div class="col-md-8">
                        <label for="city" class="form-label">Município</label>
                        <input wire:model.defer="city" type="text" class="form-control" id="city" maxlength="255" autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="uf" class="form-label">UF</label>
                        <select wire:model="uf" id="uf" class="form-select {{ $field('uf') }}">
                            <option value="">--</option>
                            @foreach ($ufs as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('uf') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span class="spinner-border spinner-border-sm" wire:loading wire:target="save"></span> Salvar
            </button>
        </div>
    </form>
</div>
