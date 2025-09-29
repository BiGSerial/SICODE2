<div>

    <x-show-loading />
    <div class="row g-3 mb-4">
        <!-- Registros por página -->
        <div class="col-md-2">
            <div class="form-floating">
                <select wire:model="perPage" id="perPage" class="form-select">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <label for="perPage">Registros por página</label>
            </div>
        </div>



        {{-- <!-- Buscar -->
        <div class="col-md-3">
            <div class="form-floating">
                <input wire:model.debounce.300ms="search" type="text" id="search" class="form-control"
                    placeholder="Digite para buscar...">
                <label for="search">Buscar</label>
            </div>
        </div>

        <!-- Mês -->
        <div class="col-md-2">
            <div class="form-floating">
                <input wire:model="month" type="month" id="month" class="form-control" max="{{ date('Y-m') }}">
                <label for="month">Mês</label>
            </div>
        </div>

        <!-- Data início -->
        <div class="col-md-2">
            <div class="form-floating">
                <input wire:model="dt_start" type="date" id="dt_start" class="form-control"
                    max="{{ $dt_end ?? date('Y-m-d') }}">
                <label for="dt_start">Data início</label>
            </div>
        </div>

        <!-- Data fim -->
        <div class="col-md-2">
            <div class="form-floating">
                <input wire:model="dt_end" type="date" id="dt_end" class="form-control" min="{{ $dt_start }}"
                    max="{{ date('Y-m-d') }}">
                <label for="dt_end">Data fim</label>
            </div>
        </div>

        <!-- Botão limpar filtros -->
        <div class="col-md-1 d-flex align-items-end">
            <button wire:click="clearFilters" type="button" class="btn btn-outline-secondary w-100">
                <i class="ri-refresh-line"></i>
            </button>
        </div> --}}
    </div>
    @if ($list->count() > 0)
        <div class=" d-flex justify-content-between align-items-center">
            <div>
                <i class="ri-information-line"></i>
                Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
            </div>
            <div>
                {{ $list->links() }}
            </div>
        </div>
    @endif
    <div class="card">
        <h4 class="card-header">
            RECLAMAÇÃO AGUARDANDO USUARIO
        </h4>
        <div class="table-responsive">

            @if ($list->count() > 0)

                <table class="table table-striped table-bordered">
                    <thead>
                        <tr class="text-center align-middle">

                            <th scope="col-1" class="col-1">Numero Reclamação</th>
                            <th scope="col-1" class="col-1">Tipo:</th>
                            <th scope="col-1" class="col-1">Usuario:</th>
                            <th class="col-1">Abertura Reclamação</th>
                            <th class="col-1">Conclusão Desejada</th>
                            <th class="col-1">Data da Medida</th>
                            <th class="col-1">Note Ref</th>
                            <th class="col-1">Enviado Em</th>
                            <th class="col-1">Dias Atv</th>
                            <th class="col-1">Enviado Por:</th>

                            <th class="col-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($list as $item)
                            @php
                                $status = $status = [
                                    'class' => '',
                                    'message' => '',
                                    'days' => '',
                                ];
                                if ($item?->started_at) {
                                    // conta dias decorridos desde started_at até agora
                                    $days = $item->started_at->startOfDay()->diffInDays(now()->startOfDay());

                                    if ($days > 5) {
                                        $status = [
                                            'class' => 'text-bg-danger',
                                            'message' => 'VENCIDO',
                                            'days' => $days,
                                        ];
                                    } elseif ($days < 2) {
                                        $status = [
                                            'class' => 'text-bg-success',
                                            'message' => 'PRAZO',
                                            'days' => $days,
                                        ];
                                    } else {
                                        $status = [
                                            'class' => 'text-bg-warning',
                                            'message' => 'VENCENDO',
                                            'days' => $days,
                                        ];
                                    }
                                }
                            @endphp
                            <tr class="text-center align-middle">

                                <td>{{ $item->assignable?->protest?->nota }}</td>
                                <td class='fw-bold'>{{ $item->assignable?->protest?->tipoNota }}</td>
                                <td>{{ $item->User?->name }}</td>
                                <td class="fw-bold">{{ $item->assignable?->protest?->dtAberturaNota->format('d/m/Y') }}
                                </td>
                                <td class="fw-bold">
                                    {{ $item->assignable?->protest?->dtConclusaoDesej?->format('d/m/Y') }}</td>
                                <td class="fw-bold">{{ $item->assignable?->dtCriacaoMedida?->format('d/m/Y') }}</td>
                                <td>{{ $item->assignable?->Notes->isNotEmpty() ? $item->assignable?->Notes?->last()?->note : 'SEM NOTA REFERÊNCIA' }}
                                </td>
                                <td class="fw-bold text-primary">{{ $item->started_at?->format('d/m/Y H:i') }}</td>
                                <td class="{{ $status['class'] }}">
                                    {{ $item?->started_at?->startOfDay()->diffInDays() }}</td>

                                <td>{{ $item->assignable?->Assignments->where('responsible', true)->first()?->User->name }}
                                </td>

                                </td>
                                <td><a href="{{ route('protests.services.view', $item->assignable->id) }}"><i
                                            class="ri-play-circle-fill fs-4 align-middle text-success"
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
    @if ($list->count() > 0)
        <div class=" d-flex justify-content-between align-items-center">
            <div>
                <i class="ri-information-line"></i>
                Exibindo {{ $list->firstItem() }} a {{ $list->lastItem() }} de {{ $list->total() }} registros.
            </div>
            <div>
                {{ $list->links() }}
            </div>
        </div>
    @endif
</div>
