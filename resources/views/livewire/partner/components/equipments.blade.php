@php
    use App\Helpers\SelectOptions;
@endphp
<div>
    <x-show-loading />
    <div class="card mb-3">
        <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Equipamentos
        </h5>
        <div class="row p-2">
            <div class="mb-3 col-md-5">
                <label for="exampleFormControlInput1" class="form-label">Tipo
                    de
                    Equipamento:</label>
                <select
                    class="form-select form-select-sm border-secondary @error('model_equipment.type') is-invalid @enderror""
                    aria-label="Default select example" id="type" wire:model.defer="model_equipment.type">
                    <option value="" selected>Selecione</option>
                    @foreach (SelectOptions::getEquipmentOptions() as $item)
                        <option value="{{ $item->nick }}">
                            {{ $item->info }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 col-md-4">
                <label for="exampleFormControlInput1" class="form-label">Patrimônio:</label>
                <input type="text"
                    class="form-control form-control-sm border-secondary @error('model_equipment.patrimony') is-invalid @enderror"
                    id="patrimony" wire:model.defer="model_equipment.patrimony">

            </div>
            <div class="mb-3 col-md-3">
                <label for="exampleFormControlInput1" class="form-label">Movimento:</label>
                <select
                    class="form-select form-select-sm border-secondary @error('model_equipment.installed') is-invalid @enderror""
                    aria-label="Default select example" wire:model.defer="model_equipment.installed">
                    <option selected>Selecione</option>
                    <option value="1">Instalação</option>
                    <option value="0">Desinstalação</option>
                </select>
            </div>
            <div class="mb-3 col-md-5">
                <label for="exampleFormControlInput1" class="form-label">Fases
                    Ligadas:</label>
                <select
                    class="form-select form-select-sm border-secondary @error('model_equipment.fases') is-invalid @enderror""
                    aria-label="Default select example" id="fases" wire:model.defer="model_equipment.fases">
                    <option selected>Selecione</option>
                    @foreach (SelectOptions::getFasesOptions() as $item)
                        <option value="{{ $item->nick }}">
                            {{ $item->info }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 col-md-4">
                <label for="exampleFormControlInput1" class="form-label">Poste
                    Referencial:</label>
                <input type="text"
                    class="form-control form-control-sm border-secondary @error('model_equipment.pole') is-invalid @enderror""
                    id="pole" wire:model.defer="model_equipment.pole">
            </div>
            <div class="mb-3 col-md-3">

                <button type="button" class="btn btn-sm btn-primary mt-4"
                    wire:click="addEquipment()">Adicionar</button>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger text-center mx-2 py-1">
                <ul class="list-unstyled my-0 py-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($workRP->Equipment->count())
            <div class="card-body">



                <table class="table table-sm table-condensed table-striped">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center align-middle">Tipo
                            </th>
                            <th scope="col" class="text-center align-middle">
                                Patrimonio</th>
                            <th scope="col" class="text-center align-middle">
                                Movimento</th>
                            <th scope="col" class="text-center align-middle">Fases
                            </th>
                            <th scope="col" class="text-center align-middle">Poste
                                RF</th>
                            <th scope="col" class="text-center align-middle"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($workRP->Equipment as $equip)
                            <tr wire:key='equip-{{ $equip->id }}'>
                                <td scope="col" class="text-center align-middle">
                                    {{ $equip->type }}
                                </td>
                                <td scope="col" class="text-center align-middle">
                                    {{ $equip->patrimony }}</td>
                                <td scope="col" class="text-center align-middle">
                                    @if ($equip->installed)
                                        <i class="ri-arrow-right-line fs-3 text-success"></i>
                                    @else
                                        <i class="ri-arrow-left-line fs-3 text-danger"></i>
                                    @endif
                                </td>
                                <td scope="col" class="text-center align-middle">
                                    {{ $equip->fases }}</td>
                                <td scope="col" class="text-center align-middle">
                                    {{ $equip->pole }}</td>

                                <td scope="col" class="text-center align-middle">
                                    <i class="ri-delete-bin-2-line fs-5 text-danger"
                                        wire:click="removeEquipment({{ $equip }})" style="cursor: pointer;"></i>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <h4 class="text-center">SEM EQUIPAMENTOS</h4>
            </div>
        @endif
    </div>
</div>
