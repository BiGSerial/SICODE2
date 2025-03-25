<div>
    <div wire:ignore.self class="modal fade" id="edit_production" tabindex="-1" aria-labelledby="edit_production"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="modal-title text-uppercase fw-bold">Editar
                        {{ $production?->note->note }} em
                        {{ $production ? $production->service->service : '' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click.prevent="closeall"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($production)
                        <div class="card">
                            <table class="table table-sm table-condensed table-striped-columns">
                                <thead>
                                    <tr>
                                        <th style="max-width: 10%"></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold">Note:</td>
                                        <td><span class="small">{{ $production->note->note }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Municipio:</td>
                                        <td><span class="small">{{ $production->note->lexp }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Rubrica:</td>
                                        <td><span class="small">{{ $production->note->rubrica }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Descrição:</td>
                                        <td><span class="small">{{ $production->note->material }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Gruop 4:</td>
                                        <td><span class="small">{{ $production->note->group4 }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Usuario:</td>
                                        <td><span class="small">{{ $production->user?->name }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Empresa:</td>
                                        <td><span class="small">{{ $production->company?->name }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Atribuido em:</td>
                                        <td><span class="small">{{ $production->att_at->format('d/m/Y') }}</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Empresa:</label>
                            <select class="form-select form-select-sm" wire:model="companySelected">
                                <option value="" selected>Selecione</option>
                                @if ($companies && $companies->count())
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('companySelected')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Usuário:</label>
                            <select class="form-select form-select-sm" wire:model.defer="userSelected">
                                @if ($users && $users->count())
                                    <option value="" selected>Selecione um Usuário</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                @else
                                    <option value="" selected>Escolha uma Empresa Primeiro</option>
                                @endif
                            </select>
                            @error('userSelected')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" wire:model.defer="ri" id="ri_checkbox">
                            <label class="form-check-label" for="ri_checkbox">Retorno Interno</label>
                        </div>

                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button type="button" class="btn-sm btn btn-danger" wire:click.prevent="closeall"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-sm btn btn-primary" wire:click="toCreateNewProduction"
                        wire:loading.attr="disabled">
                        Nova Atribuição
                    </button>

                    <button type="button" class="btn-sm btn btn-primary" wire:loading.attr="disabled"
                        wire:click="toTransferProduction">
                        Transferir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
