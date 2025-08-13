<div>

    <div class="mb-4">


        <div class="row g-3 mb-4">
            <!-- Registros por página -->
            <div class="col-md-2">
                <div class="form-floating">
                    <select wire:model.live="perPage" id="perPage" class="form-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <label for="perPage">Registros por página</label>
                </div>
            </div>

            <!-- Buscar -->
            <div class="col-md-3">
                <div class="form-floating">
                    <input wire:model.live.debounce.300ms="search" type="text" id="search" class="form-control"
                        placeholder="Digite para buscar...">
                    <label for="search">Buscar</label>
                </div>
            </div>

            <!-- Mês -->
            <div class="col-md-2">
                <div class="form-floating">
                    <input wire:model.live="month" type="month" id="month" class="form-control"
                        max="{{ date('Y-m') }}">
                    <label for="month">Mês</label>
                </div>
            </div>

            <!-- Data início -->
            <div class="col-md-2">
                <div class="form-floating">
                    <input wire:model.live="dt_start" type="date" id="dt_start" class="form-control"
                        max="{{ $dt_end ?? date('Y-m-d') }}">
                    <label for="dt_start">Data início</label>
                </div>
            </div>

            <!-- Data fim -->
            <div class="col-md-2">
                <div class="form-floating">
                    <input wire:model.live="dt_end" type="date" id="dt_end" class="form-control"
                        min="{{ $dt_start }}" max="{{ date('Y-m-d') }}">
                    <label for="dt_end">Data fim</label>
                </div>
            </div>

            <!-- Botão limpar filtros -->
            <div class="col-md-1 d-flex align-items-end">
                <button wire:click="clearFilters" type="button" class="btn btn-outline-secondary w-100">
                    <i class="ri-refresh-line"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card">
        <h4 class="card-header">
            RECLAMAÇÕES ENCERRADAS POR MIM
        </h4>
        <div class="table-responsive">

            @if ($list->count() > 0)
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr class="text-center align-middle">

                            <th scope="col-1" class="col-1">Numero Reclamação</th>
                            <th scope="col-1" class="col-1">Tipo:</th>
                            <th scope="col-1" class="col-1">Numero Medida:</th>
                            <th class="col-1">Abertura Reclamação</th>
                            <th class="col-1">Conclusão Desejada</th>
                            <th class="col-1">Data da Medida</th>
                            <th class="col-1">Medida Encerrada</th>
                            <th class="col-1">Encerrada Sicode</th>
                            <th class="col-1">Note Ref</th>
                            <th class="col-1">Enviado Por:</th>
                            <th class="col">Obs:</th>
                            <th class="col-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($list as $item)
                            @php
                                $assigment = $item->Assignments->where('user', true)->where('completed', true)->first();
                            @endphp
                            <tr class="text-center align-middle">
                                <td>{{ $item->protest->nota }}</td>
                                <td class='fw-bold'>{{ $item->protest->tipoNota }}</td>
                                <td>{{ $item->med_id }}</td>
                                <td class="fw-bold">{{ $item->protest->dtAberturaNota->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $item->protest?->dtConclusaoDesej?->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $item->dtCriacaoMedida?->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $item->dtFimMedida?->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $assigment?->ended_at?->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $item->Notes->isNotEmpty() ? $item->Notes?->last()?->note : 'SEM NOTA REFERÊNCIA' }}
                                </td>
                                <td>{{ $item->Assignments->where('responsible', true)->first()?->User->name }}</td>
                                <td>{{ $item->comments->isNotEmpty() ? $item->comments->first()->message : 'SEM OBSERVAÇÃO' }}
                                </td>
                                <td><a href="{{ route('protests.view_only', $item->id) }}"><i
                                            class="ri-play-circle-fill fs-4 align-middle text-primary"
                                            style="cursor: pointer;"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info">
                    Nenhum registro encontrado.
                </div>
            @endif
        </div>
    </div>
</div>
