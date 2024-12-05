@php
    use Carbon\Carbon;
@endphp

<div>
    <x-show-loading />
    <div class="card">
        <div class="card-header edp-bg-seoweedgreen-100 py-1">
            <h4 class="my-0 text-white">Informes Digitados sem Informes Final</h4>
        </div>

        @if ($lists)
            <table class="table table-stripped table-condensed table-sm table-hover">
                <thead>
                    <tr class="text-center">
                        <th>Nota</th>
                        <th>Empresa</th>
                        <th>Usuário</th>
                        <th>Informe Digitado</th>
                        <th>Informe Empreiteira</th>
                        <th>Dias</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        <tr class="text-center @if ($list->id == $selected) table-primary @elseif ($list->RamalForm && $list->WorkForm) table-success @endif"
                            wire:dblClick="$emitTo('btzero.view.compare-form', 'showCompareForm', {{ $list }})"
                            wire:click="selectNote({{ $list->id }})" style="cursor: pointer;">
                            <td class="fw-bold">{{ $list->note }}</td>
                            <td>{{ $list->RamalForm ? $list->RamalForm->Company->name : '---' }}</td>
                            <td>{{ $list->RamalForm ? $list->RamalForm->User->name : '---' }}</td>
                            <td>{{ $list->RamalForm ? Carbon::parse($list->RamalForm->created_at)->format('d/m/Y') : 'Não Informado' }}
                            </td>
                            <td>{{ $list->WorkForm ? Carbon::parse($list->WorkForm->created_at)->format('d/m/Y') : 'Não Informado' }}
                            </td>

                            @php
                                $daysDifference = $list->WorkForm
                                    ? Carbon::parse($list->RamalForm->created_at)->startOfDay()->diffInDays(
                                        Carbon::parse($list->WorkForm->created_at)->startOfDay(),
                                    )
                                    : Carbon::parse($list->RamalForm->created_at)->startOfDay()->diffInDays(Carbon::now()->startOfDay());
                            @endphp

                            <td class="text-center fw-bold">{{ $daysDifference }}</td>

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
