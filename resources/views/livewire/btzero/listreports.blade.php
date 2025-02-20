@php
    use Carbon\Carbon;
    use App\Custom\Notestatus;
@endphp

<div>
    <x-show-loading />

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-start my-1">
                <input type="text" class="form-control w-25 border border-secondary" placeholder="Buscar..."
                    wire:model="search">


                <select class="form-select form-select-sm ms-2 w-25 border border-secondary" wire:model="company">
                    <option value="">Selecione uma opção</option>
                    @if ($companies)
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

    </div>
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 py-1 d-flex justify-content-between align-items-center">
            <h4 class="my-0 text-white">Informes Digitados sem Informes Final</h4>
            <button class="btn btn-sm btn-primary" title="Exportar para Excel" wire:click.prevent="export_excel">
                <i class="ri-file-excel-2-line fs-5"></i>
            </button>
        </div>

        @if ($lists)
            {{-- @dump($lists[0]) --}}
            <table class="table table-stripped table-condensed table-sm table-hover">
                <thead>
                    <tr class="text-center">
                        <th>Nota</th>
                        <th>Empresa</th>
                        <th>Usuário</th>
                        <th>Informe Digitado</th>
                        <th>Informe Empreiteira</th>
                        <th>Publicação Parcial</th>
                        <th>Publicação Final</th>
                        <th>Atualização</th>
                        <th>Dias</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        @php
                            $daysDifference = $list->WorkForm
                                ? Carbon::parse($list->RamalForm->created_at)
                                    ->startOfDay()
                                    ->diffInDays(Carbon::parse($list->WorkForm->created_at)->startOfDay())
                                : Carbon::parse($list->RamalForm->created_at)
                                    ->startOfDay()
                                    ->diffInDays(Carbon::now()->startOfDay());

                            $daysRowClass = '';

                            if ($daysDifference > 3) {
                                $daysRowClass = 'text-bg-danger';
                            } elseif ($daysDifference > 2) {
                                $daysRowClass = 'text-bg-warning';
                            } elseif ($daysDifference > 1) {
                                $daysRowClass = 'text-bg-info';
                            } else {
                                $daysRowClass = 'text-bg-success';
                            }

                            $rowClass = '';

                            if ($list->rejected) {
                                $rowClass = 'table-warning';
                            } elseif ($list->RamalForm && $list->WorkForm) {
                                $rowClass = 'table-success';
                            }

                        @endphp
                        <tr class="text-center"
                            wire:dblClick="$emitTo('btzero.view.compare-form', 'showCompareForm', {{ $list }})"
                            style="cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="left"
                            data-bs-title="Duplo Clique para abrir a comparação">

                            <td class="fw-bold {{ $rowClass }}">{{ $list->note }}</td>
                            <td class="{{ $rowClass }}">
                                {{ $list->RamalForm ? $list->RamalForm->Company->name : '---' }}</td>
                            <td class="{{ $rowClass }}">
                                {{ $list->RamalForm ? $list->RamalForm->User->name : '---' }}</td>
                            <td class="{{ $rowClass }}">
                                {{ $list->RamalForm ? Carbon::parse($list->RamalForm->created_at)->format('d/m/Y') : 'Não Informado' }}
                            </td>
                            <td class="{{ $rowClass }}">
                                {{ $list->WorkForm ? Carbon::parse($list->WorkForm->created_at)->format('d/m/Y') : 'Não Informado' }}
                            </td>
                            <td class="{{ $rowClass }}">
                                @if ($list->productions && isset($list->productions->last()->partial_at))
                                    {{ $list->productions->last()->partial_at->format('d/m/Y') }}
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="{{ $rowClass }}">
                                @if ($list->productions && isset($list->productions->last()->completed_at))
                                    {{ $list->productions->last()->completed_at->format('d/m/Y') }}
                                @else
                                    ---
                                @endif
                            </td>
                            <td class="{{ $rowClass }}">
                                @if ($list->productions && $list->productions->last())
                                    @if ($list->productions->last()->status == 5)
                                        <span class="badge bg-success">Publicado</span>
                                    @elseif ($list->productions->last()->status == 28)
                                        <span class="badge bg-success">Publicado Parcial</span>
                                    @else
                                        <span
                                            class="badge {{ Notestatus::status($list->productions->last()->status)->colorbg }}">{{ Notestatus::status($list->productions->last()->status)->status }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-primary">Não Publicado</span>
                                @endif
                            </td>



                            <td class="text-center fw-bold {{ $daysRowClass }}">{{ $daysDifference }}</td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="card-body">
                <h5 class="text-center">SEM INFORMES</h5>
            </div>
        @endif
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $lists->links() }}
        <div class="mt-2">
            <p class="text-center">
                Exibindo de {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de um total de
                {{ $lists->total() }} registros
            </p>
        </div>
    </div>

    {{-- Componentes Livewire --}}
    @livewire('btzero.view.compare-form', key('btZeroCompareForm'))
</div>
