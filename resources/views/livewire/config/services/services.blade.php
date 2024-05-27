<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />


    <div class="row justify-content-between">
        <div class="mb-3 col-3">
            <label for="search" class="form-label">Buscar</label>
            <input wire:model.bounce.2s="search" type="email" class="form-control border border-2 border-secondary"
                id="search" placeholder="Buscar">
        </div>

        <div class="mb-3 col-1">
            <button type="button" class="btn btn-primary align-end" data-bs-toggle="modal"
                data-bs-target="#create_modal" style="height: 50px">
                <i class="ri-settings-2-fill fs-4"></i><i class="ri-add-fill fs-4"></i>
            </button>
        </div>
    </div>



    @if (!$services->count())
        <div class="card">
            <h4 class="text-center card-header">SEM SERVIÇOS REGISTRADOS </h4>
        </div>
    @else
        @foreach ($services as $service)
            <div class="card" wire:key="service-{{ $service->id }}">
                <div class="card-header">
                    <div class="row">
                        <div class="col">
                            @if (isset($editName[$service->id]) && $editName[$service->id])
                                <div class="input-group">
                                    <input type="text" class="col-3 form-control border border-2 border-secondary"
                                        wire:model.defer="service_name" aria-label="Recipient's username"
                                        aria-describedby="button-addon2" wire:key="item-{{ $service->id }}">


                                    <select class="form-select border border-2 border-secondary"
                                        aria-label="Default select example" wire:model.defer="folder_s"
                                        wire:key="item-{{ $service->id }}">
                                        <option selected>Selecione Diretório</option>
                                        @if (isset($folders) && count($folders))
                                            @foreach ($folders as $folder)
                                                <option value="{{ $folder }}">{{ mb_strtoupper($folder) }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>

                                    <input wire:model="icon_s" type="text"
                                        class="form-control border-1 border-secondary">
                                    @if ($icon_s)
                                        <i class="{{ $icon_s }} fw-bold fs-4 align-middle text-primary mx-2"></i>
                                    @endif
                                    <button class="btn btn-outline-secondary" type="button" id="button-addon2"
                                        wire:click.prevent="update_name" wire:key="item-{{ $service->id }}">OK</button>
                                </div>
                            @else
                                <h4><i class="{{ $service->icon }} text-primary me-2"></i>{{ $service->service }}
                                    @if ($service->Status->count())
                                        @foreach ($service->Status->where('exclusion', false)->unique('value') as $status)
                                            <span class="fw-bold">({{ $status->value }})
                                            </span>
                                        @endforeach
                                    @endif
                                    <i class="ri-pencil-fill align-middle text-danger" style="cursor: pointer;"
                                        wire:click.prevent="edit_name_service({{ $service->id }})"></i>
                                </h4>
                            @endif
                        </div>

                        <div class="col d-flex justify-content-end">
                            <button
                                class="btn btn-sm
                                @if ($service->canReturn) btn-success
                                @else
                                btn-outline-secondary @endif
                             mx-1 align-middle"
                                wire:click.prevent="update_return({{ $service->id }})">
                                <i class="ri-arrow-go-back-fill align-middle"></i>
                                Retorno
                            </button>
                            <button class="btn btn-sm btn-outline-secondary mx-1 align-middle"
                                wire:click.prevent="addStatus({{ $service->id }})">
                                <i class="ri-scales-3-fill align-middle"></i>
                                Filtros
                            </button>
                            <button class="btn btn-sm btn-outline-secondary mx-1 align-middle"
                                wire:click.prevent="addRule({{ $service->id }})">
                                <i class="ri-file-text-line align-middle"></i>
                                Regras
                            </button>
                        </div>
                    </div>

                </div>
                <div class="card-body">
                    <h5>Regras</h5>
                    <div class="card">
                        @if (!$service->contracts->count())
                            <div class="card-body">
                                <h4 class="text-center">SEM REGRAS PARA O SERVIÇO</h4>
                            </div>
                        @else
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-condensed table-striped">
                                        <thead>
                                            <tr>
                                                <th scope="col">Empresa</th>
                                                <th scope="col">Contrato</th>
                                                <th scope="col">Despacho?</th>
                                                <th scope="col">Por Poste?</th>
                                                <th scope="col">Quantidade</th>
                                                <th scope="col">Dias</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($service->contracts as $contract)
                                                <tr>
                                                    <td class="fw-bold">{{ mb_strtoupper($contract->company->name) }}
                                                    </td>
                                                    <td>{{ $contract->number }}</td>
                                                    <td>{{ $contract->pivot->dispatch ? 'SIM' : 'NÃO' }}</td>
                                                    <td>{{ $contract->pivot->posts ? 'SIM' : 'NÃO' }}</td>
                                                    <td>{{ $contract->pivot->qtd }}</td>
                                                    <td>{{ $contract->pivot->days }}</td>
                                                    <td>@livewire('config.services.removerules', ['service' => $service->id, 'contract' => $contract->id, 'action_id' => hash('ripemd160', $service->id . now() . $contract->id)], key(hash('ripemd160', 'removeRules' . $service->id . now() . $contract->id)))</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif




    {{-- MODAIS --}}

    @livewire('config.services.delete')

    <div wire:ignore.self class="modal fade" id="create_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-customer-service-2-fill fs-4 align-middle"></i> CRIAR SERVIÇO</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @livewire('config.services.create', key(hash('ripemd160', 'config' . now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_create_service')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="add_rules_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-customer-service-2-fill fs-4 align-middle"></i> CRIAR REGRAS</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" wire:key="addRule">
                    @livewire('config.services.addrules', key(hash('ripemd160', 'addRules' . now())))
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary"
                        wire:click.prevent="$emit('save_add_rules')">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="add_status_modal" tabindex="-1" aria-labelledby="create"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h1 class="modal-title fs-5" id="exampleModalLabel"><i
                            class="ri-customer-service-2-fill fs-4 align-middle"></i> STATUS ATRIBUIDOS</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" wire:key="addStatus">
                    @livewire('config.services.addstatus', key('addStatus' . hash('ripemd160', 'addStatus' . now())))

                </div>
                <div class="modal-footer">

                    <button type="button" class="btn btn-primary" wire:click.prevent="$emit('refresh_service_list')"
                        data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>


</div>

@push('script')
    <script>
        window.addEventListener('alertar', function(e) {

            const Confirmation = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });

            Swal.fire({
                title: e.detail.title,
                html: e.detail.msg,
                icon: e.detail.icon,
                showCancelButton: true,
                confirmButtonText: e.detail.btnOktxt,
                cancelButtonText: e.detail.btnCanceltxt,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    Livewire.emit(e.detail.action, e.detail.action_id)

                } else if (
                    /* Read more about handling dismissals below */
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    Swal.fire(
                        e.detail.cancel_titulo,
                        e.detail.cancel_msg,
                        'success'
                    )
                }
            })
        });
    </script>
@endpush
