<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    @if ($showAddstatus)
        <form>

            <div class="row g-2">
                <h4 class="fw-bold">{{ $service->service }} - Filtros</h4>
                <div class="mb-3 row">

                    {{-- <select class="form-select form-select-sm" aria-label="" wire:model.defer='status_s'>
                        @if ($status_l->count())
                            <option selected>Selecione um Status</option>
                            @foreach ($status_l as $sts)
                                <option value="{{ $sts->nstats }}">{{ $sts->nstats }} - {{ $sts->status }}</option>
                            @endforeach
                        @else
                            <option selected disabled>Nenhuma empresa com contrato</option>
                        @endif
                    </select> --}}

                    <div class="mb-2 col-6">
                        <label for="exampleFormControlInput1" class="form-label">Coluna de Busca:</label>
                        <select class="form-select form-select-sm" aria-label="" wire:model.defer='column_search'>
                            @if (count($columns_l))
                                <option selected>Selecione o Campo</option>
                                @foreach ($columns_l as $column)
                                    <option value="{{ $column }}">{{ $column }}
                                    </option>
                                @endforeach
                            @else
                                <option selected disabled>Nenhuma empresa com contrato</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-2 col-6">
                        <label for="exampleFormControlInput1" class="form-label">Condição</label>
                        <select class="form-select form-select-sm" aria-label="" wire:model.defer='condition'>
                            <option selected>Selecione</option>
                            <option value="Exatamente">Exatamente</option>
                            <option value="Inicia por">Inicia por</option>
                            <option value="Termina por">Termina por</option>
                            <option value="Contem">Contém</option>


                        </select>
                    </div>
                    <div class="mb-2 col-6">
                        <label for="exampleFormControlInput1" class="form-label">Valor:</label>
                        <input type="text" class="form-control" wire:model.defer="value">
                    </div>
                    <div class="mb-2 col-6">
                        <label for="exampleFormControlInput1" class="form-label">Exclusao?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="true" wire:model.defer="exclusion">
                            <label class="form-check-label" for="flexCheckDefault">
                                SIM
                            </label>
                        </div>
                    </div>

                    @if ($view_and)
                        <div class="card mt-2 edp-bg-stategrey-20">
                            <h4 class="card-title">AND</h4>
                            <div class="card-body py-0">
                                <div class="mb-2 col-6">
                                    <label for="exampleFormControlInput1" class="form-label">Coluna de Busca:</label>
                                    <select class="form-select form-select-sm" aria-label=""
                                        wire:model.defer='column_search2'>
                                        @if (count($columns_l))
                                            <option selected>Selecione o Campo</option>
                                            @foreach ($columns_l as $column)
                                                <option value="{{ $column }}">{{ $column }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option selected disabled>Nenhuma empresa com contrato</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="mb-2 col-6">
                                    <label for="exampleFormControlInput1" class="form-label">Condição</label>
                                    <select class="form-select form-select-sm" aria-label=""
                                        wire:model.defer='condition2'>
                                        <option selected>Selecione</option>
                                        <option value="Exatamente">Exatamente</option>
                                        <option value="Inicia por">Inicia por</option>
                                        <option value="Termina por">Termina por</option>
                                        <option value="Contem">Contém</option>


                                    </select>
                                </div>
                                <div class="mb-2 col-6">
                                    <label for="exampleFormControlInput1" class="form-label">Valor:</label>
                                    <input type="text" class="form-control" wire:model.defer="value2">
                                </div>
                                <div class="mb-2 col-6">
                                    <label for="exampleFormControlInput1" class="form-label">Exclusao?</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="true"
                                            wire:model.defer="exclusion2">
                                        <label class="form-check-label" for="flexCheckDefault">
                                            SIM
                                        </label>
                                    </div>
                                </div>


                            </div>
                        </div>
                        <div class="col-6 align-self-end">
                            <button wire:click.prevent="and" class="btn btn-sm btn-secondary">- AND</button>
                        </div>
                    @else
                        <div class="col-6 align-self-end">
                            <button wire:click.prevent="and" class="btn btn-sm btn-secondary">+ AND</button>
                        </div>
                    @endif



                    <div class="col-6 align-self-end">
                        <button wire:click.prevent="add" class="btn btn-sm btn-success">ADICIONAR FILTRO</button>
                    </div>

                </div>

                @if ($status_list->count())
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">Coluna</th>
                                <th scope="col" class="text-center">Condição</th>
                                <th scope="col" class="text-center">Valor</th>
                                <th scope="col" class="text-center">Exclusão</th>

                                <th scope="col" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($status_list as $sts)
                                <tr>
                                    <td class="text-center">
                                        <p class="my-0">{{ $sts->column_search }}</p>
                                        @if ($sts->column_search2)
                                            <p class="text-danger my-0" style="font-size: 10px;">
                                                {{ $sts->column_search2 }}</p>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <p class="my-0">{{ $sts->condition }}</p>
                                        @if ($sts->condition2)
                                            <p class="text-danger my-0" style="font-size: 10px;">
                                                {{ $sts->condition2 }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <p class="my-0">{{ $sts->value }}</p>
                                        @if ($sts->value2)
                                            <p class="text-danger my-0" style="font-size: 10px;">{{ $sts->value2 }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <p class="my-0">{{ $sts->exclusion ? 'SIM' : 'NÃO' }}</p>

                                        @if ($sts->value2)
                                            <p class="text-danger my-0" style="font-size: 10px;">
                                                {{ $sts->exclusion2 ? 'SIM' : 'NÃO' }}</p>
                                        @endif

                                    </td>

                                    <td class="text-center"><i class="ri-delete-bin-2-line text-danger"
                                            wire:click.prevent="remove({{ $sts->id }})"
                                            style="cursor: pointer;"></i></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
        </form>
    @endif
</div>
