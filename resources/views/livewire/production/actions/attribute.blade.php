<div>
    @if ($production->status == 1)
        <li><a class="dropdown-item" href="#" wire:click.prevent="get_single_note({{ $production->id }})"><i
                    class="ri-user-shared-fill text-primary align-middle"></i>
                Atribuir</a></li>
    @else
        <li><a class="dropdown-item" href="#" wire:click.prevent="to_remove_add({{ $production->id }})"><i
                    class="ri-user-received-2-line text-danger align-middle"></i>
                Desatribuir</a></li>
        <li><a class="dropdown-item" href="#"><i class="ri-exchange-line text-primary align-middle"></i>
                Transferir</a></li>
    @endif



    <div wire:ignore.self class="modal fade" id="att_{{ $chave }}" tabindex="-1"
        aria-labelledby="exampleModalLabel" aria-hidden="true" style="z-index: 1050;">


        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">ATRIBUIR</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closeall"></button>
                </div>
                <div class="modal-body">
                    @if ($notes && $notes->count())
                        <div class="row">

                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Empresa:</label>
                                <select class="form-select form-select-sm" aria-label="" wire:model="company_s">
                                    <option selected>Selecione</option>
                                    @if ($company_l && $company_l->count())
                                        @foreach ($company_l as $company)
                                            <option value="{{ $company->id }}">{{ mb_strtoupper($company->name) }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>



                            <div class="mb-3 ">
                                <label for="exampleFormControlInput1" class="form-label">Usuário:</label>
                                <select class="form-select form-select-sm" aria-label="" wire:model="user_s">

                                    @if ($user_l && $user_l->count())
                                        <option value="" selected>Selecione um Usuário</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    @else
                                        <option selected>Escolha uma Empresa Primeiro</option>
                                    @endif
                                </select>
                            </div>


                            <div class="col-12 fw-bold">
                                DESPACHANDO {{ $notes->count() }} OV/NOTA(S)
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-condensed table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Note</th>
                                        <th scope="col">Desc</th>
                                        @if ($dd)
                                            <th scope="col">DD</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($notes as $index => $note)
                                        <tr>
                                            <td scope="col" class="fw-bold">{{ $index + 1 }}</td>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->material }}</td>
                                            @if ($dd)
                                                <td>
                                                    <input wire:model.defer="additionalData.{{ $index }}"
                                                        class="form-control form-control-sm" type="text"
                                                        placeholder="Informe a DD" aria-label="">
                                                </td>
                                            @endif


                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    @endif
                </div>
                <div class="modal-footer edp-bg-sprucegreen-70">
                    <button class="btn-sm btn btn-danger" wire:click.prevent="closeall">Cancelar</button>
                    <button class="btn-sm btn btn-primary" wire:click.prevent="confirm_att">Atribuir</button>
                </div>
            </div>
        </div>


    </div>




</div>
