@php
    use App\Helpers\SelectOptions;
@endphp
<div>
    <x-show-loading />
    <div class="card mb-3">
        <h5 class="card-header py-0 my-0 edp-bg-sprucegreen-70 text-edp-verde">Medidores
        </h5>
        <div class="row  p-2">

            <div class="col-md-4">
                <label for="exampleFormControlInput1" class="form-label">Número:</label>
                <input type="text"
                    class="form-control border-secondary @error('model_meeter.number') is-invalid @enderror"
                    id="number" wire:model.defer="model_meeter.number">
            </div>

            <div class="col-md-4">
                <label for="exampleFormControlInput1" class="form-label">Bornes:</label>
                <input type="text"
                    class="form-control border-secondary @error('model_meeter.borne') is-invalid @enderror"
                    id="borne" wire:model.defer="model_meeter.borne">
            </div>
            <div class="col-md-4">
                <label for="exampleFormControlInput1" class="form-label">Fases
                    Ligadas:</label>
                <select class="form-select border-secondary @error('model_meeter.fases') is-invalid @enderror"
                    aria-label="Default select example" id="m_fases" wire:model.defer="model_meeter.fases">
                    <option selected>Selecione</option>
                    @foreach (SelectOptions::getFasesOptions() as $item)
                        <option value="{{ $item->nick }}">
                            {{ $item->info }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 col-md-2">

                <button type="button" class="btn btn-sm btn-primary mt-4" wire:click="addMeeters()">Adicionar</button>
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

        </div>

        @if ($workReport->Meeters->count())
            <div class="card-body">



                <table class="table table-sm table-condensed table-striped">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center align-middle">Numero
                            </th>
                            <th scope="col" class="text-center align-middle">
                                Borne</th>
                            <th scope="col" class="text-center align-middle">
                                Fases</th>

                            <th scope="col" class="text-center align-middle"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($workReport->Meeters as $meeter)
                            <tr class="px-2" wire:key='meeter-{{ $meeter->id }}'>
                                <td class="text-center align-middle">
                                    {{ $meeter->number }}
                                </td>
                                <td class="text-center align-middle">
                                    {{ $meeter->borne }}</td>
                                <td class="text-center align-middle">
                                    {{ $meeter->fases }}
                                </td>

                                <td class="text-center align-middle"><i class="ri-delete-bin-2-line fs-3 text-danger"
                                        wire:click="remMeeters({{ $meeter }})" style="cursor: pointer;"></i></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body">
                <h4 class="text-center">SEM MEDIDORES</h4>
            </div>
        @endif
    </div>
</div>
