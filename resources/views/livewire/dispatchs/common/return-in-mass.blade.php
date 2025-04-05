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
                        <div class="card-header edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-start">
                            <h6 class="my-auto">USUARIO DE DESTINO</h6>
                        </div>
                        <div class="card-body justify-content-between">
                            <div class="clear-fix">
                                <div class="row">
                                    <div class="mb-3 col-6">
                                        <label for="form-label" class="text-secondary">Selecione a Empresa</label>
                                        <select class="form-select" wire:model="companySelected">
                                            <option>----</option>
                                            @if ($companies)
                                                @foreach ($companies as $company)
                                                    <option value="{{ $company->id }}">{{ $company->name }}
                                                    </option>
                                                @endforeach
                                            @endif

                                        </select>


                                    </div>
                                    <div class="mb-3 col-6">
                                        <label for="form-label" class="text-secondary">Buscar Usuario</label>
                                        <input class="form-control" type="text" wire:model="search" />



                                    </div>

                                    <div class="mb-3 col-6 offset-md-6">
                                        <label for="form-label" class="text-secondary">Selecione o Usuario</label>
                                        <select class="form-select" wire:model="userSelected">

                                            @if ($users)
                                                <option selected>Selecione um usuário</option>
                                                @foreach ($users as $usr)
                                                    <option value="{{ $usr->id }}">{{ $usr->name }}</option>
                                                @endforeach
                                            @else
                                                <option selected>----</option>
                                            @endif

                                        </select>


                                    </div>
                                </div>
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
