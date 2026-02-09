<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="massReturnAtt" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        ALTERAR USUARIO EM MASSA
                    </h4>
                </div>
                <div class="modal-body">

                    {{-- FILES --}}
                    <div class="card mb-3">
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between">
                            <h6 class="my-auto fw-bold">USUARIO DE DESTINO</h6>
                            <span class="badge text-bg-light my-auto">
                                {{ $companies->count() }} empresa(s) elegível(is)
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="form-label" class="text-secondary">Selecione a Empresa</label>
                                    <select class="form-select" wire:model="companySelected">
                                        <option value="">Selecione uma empresa</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="form-label" class="text-secondary">Buscar Usuário</label>
                                    <input class="form-control" type="text" wire:model.debounce.300ms="search"
                                        placeholder="Digite o nome do usuário" @disabled(!$companySelected) />
                                </div>

                                <div class="col-12">
                                    <label for="form-label" class="text-secondary">Selecione o Usuário</label>
                                    <select class="form-select" wire:model="userSelected" @disabled(!$companySelected)>
                                        <option value="">
                                            {{ $companySelected ? 'Selecione um usuário' : 'Selecione uma empresa primeiro' }}
                                        </option>
                                        @foreach ($users as $usr)
                                            <option value="{{ $usr->id }}">{{ $usr->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if ($companySelected && $users->isEmpty())
                                    <div class="col-12">
                                        <div class="alert alert-warning py-2 mb-0">
                                            Nenhum usuário disponível para esta empresa neste serviço.
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @if ($reclaims)
                    <h6 class="px-2 py-1 mb-0 edp-bg-sprucegreen-70 text-edp-verde">
                        DADOS DA TROCA ({{ $reclaims->count() }})
                    </h6>
                    <div>
                        <table class="table table-sm table-striped-columns">
                            <thead>
                                <th class="text-center align-middle">Nota</th>
                                <th class="text-center align-middle">Usuario Origem</th>
                                <th class="text-center align-middle">Empresa Origem</th>
                                <th class="text-center align-middle">Usuario Destino</th>
                                <th class="text-center align-middle">Empresa Destino</th>
                            </thead>
                            <tbody>
                                @foreach ($reclaims as $reclaim)
                                    <tr wire:key="changeUsers-{{ $reclaim->id }}">
                                        <td class="text-center align-middle">{{ $reclaim->Note->note }}</td>
                                        <td class="text-center align-middle">
                                            {{ $reclaim->Production?->User ? $reclaim->Production->User->name : '---' }}
                                        </td>
                                        <td class="text-center align-middle">
                                            {{ $reclaim->Production?->Company ? explode(' ', $reclaim->Production->Company?->name)[0] : '---' }}
                                        </td>
                                        <td class="text-center align-middle">{{ $user ? $user->name : '' }}</td>
                                        <td class="text-center align-middle">
                                            {{ $user ? explode(' ', $user->Employee->Contract->company->name)[0] : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="modal-footer edp-bg-sprucegreen-70 text-edp-verde">
                    <div class="me-3 align-middle" wire:target='updatedUploadsfiles()' wire:loading>
                        <div class="spinner-border text-light" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Aguarde.
                    </div>

                    <button class="btn btn-primary btn-sm" wire:click.prevent="goChangeInMassUser"
                        wire:loading.attr='disabled'>ALTERAR
                        USUARIO</button>
                    <button class="btn btn-danger btn-sm" wire:click.prevent="cancelReturnMass()"
                        wire:loading.attr='disabled'>CANCELAR</button>
                </div>
            </div>
        </div>
    </div>


</div>
