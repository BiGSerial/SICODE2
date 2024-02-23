@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp

<div>
    <x-show-loading />
    <x-showselected :count="$selected" />


    <div class="row mb-3">
        <div class="col-1">
            <label for="" class="form-label">Por Página</label>
            <select wire:model="perPage" class="form-select form-control-sm  border border-2 border-secondary">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="250">250</option>
                <option value="500">500</option>
            </select>
        </div>

        <div class="mb-3 col-md-2">
            <label for="search" class="form-label">Buscar</label>
            <div class="input-group">
                <input wire:model.bounce.2s="search" type="text"
                    class="form-control border border-2 border-secondary" id="search" placeholder="Buscar">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#buscar_multi"><i
                        class="ri-checkbox-multiple-blank-line"></i></button>
            </div>
        </div>

    </div>


    @if (!$lists->count())
        <div class="card">
            <div class="card-body">
                <h3 class="text-center">NENHUM REGISTRO ENCONTRADO</h3>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                <div class="row">
                    <div class="col">
                        <h4 class="my-0">LISTA PARA {{ mb_strtoupper($service->service) }}
                            @if ($service->Status->count())
                                @foreach ($service->Status->where('exclusion', false)->unique('value') as $sts)
                                    ({{ $sts->value }})
                                @endforeach
                            @endif
                        </h4>
                    </div>
                    <div class="col-3 d-flex justify-content-end">
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='go_att_mass'><i
                                class="ri-checkbox-multiple-fill"></i> Atribuir</button>
                        <button class="btn btn-sm btn-primary me-2" wire:click.prevent='export_excel'><i
                                class="ri-file-excel-2-line"></i> Exportar</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-condensed">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input class="form-check-input" type="checkbox">
                                </th>
                                <th scope="col" class="fw-bold text-center">Ordem</th>
                                <th scope="col" class="fw-bold text-center">Nota</th>
                                <th scope="col" class="fw-bold text-center">Criado Em</th>
                                <th scope="col" class="fw-bold text-center">denConjunto</th>
                                <th scope="col" class="fw-bold text-center">Rubrica</th>
                                <th scope="col" class="fw-bold text-center">Municipio</th>
                                <th scope="col" class="fw-bold text-center">Status</th>
                                <th scope="col" class="fw-bold text-center">Prazo Real</th>
                                <th scope="col" class="fw-bold text-center">Situação</th>
                                <th scope="col" class="fw-bold text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr>
                                    <td><input class="form-check-input" type="checkbox"></td>
                                    <td class="fw-bold">{{ $list->ordem }}</td>
                                    <td>{{ $list->Note->note }}</td>
                                    <td>{{ date('d/m/Y', strToTime($list->dtEntrada)) }}</td>
                                    <td>{{ $list->denConjunto }}</td>
                                    <td>{{ $list->Note->rubrica }}</td>
                                    <td>{{ $list->Note->lexp }}</td>
                                    <td>{{ $list->Operations()->where('operacao', '0010')->first()->status }}</td>
                                    <td>{{ $list->days_left - 30 }}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-6 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                    {{ $lists->lastItem() }}
                    de {{ $lists->total() }}
                    registros.</span>
            </div>
        </div>
    @endif




    {{-- MODALS --}}
    <div wire:ignore.self class="modal fade" id="buscar_multi" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">


        <div class="modal-dialog">

            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    Buscar Multi-Notas
                </div>
                <div>
                    <textarea class="form-control" name="advanceSearch" id="advanceSearch" cols="50" rows="10"
                        wire:model.defer="advanceSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="buscarMulti">OK</button>
                </div>
            </div>

        </div>

    </div>
</div>
