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
                        <tr class="text-center @if ($list->RamalForm && $list->WorkForm) table-success @endif"
                            wire:dblClick="$emitTo('btzero.view.compare-form', 'showCompareForm', {{ $list }})">
                            <td class="fw-bold">{{ $list->note }}</td>
                            <td>{{ $list->RamalForm ? $list->RamalForm->Company->name : '---' }}</td>
                            <td>{{ $list->RamalForm ? $list->RamalForm->User->name : '---' }}</td>
                            <td>{{ $list->RamalForm ? Carbon::parse($list->RamalForm->created_at)->format('d/m/Y') : 'Não Informado' }}
                            </td>
                            <td>{{ $list->WorkForm ? Carbon::parse($list->WorkForm->created_at)->format('d/m/Y') : 'Não Informado' }}
                            </td>

                            @php
                                $daysDifference = $list->WorkForm
                                    ? Carbon::parse($list->RamalForm->created_at)->diffInDays(
                                        Carbon::parse($list->WorkForm->created_at),
                                    )
                                    : Carbon::parse($list->RamalForm->created_at)->diffInDays(Carbon::now());
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
    {{-- Componentes Livewire --}}
    @livewire('btzero.view.compare-form', key('btZeroCompareForm'))
</div>
