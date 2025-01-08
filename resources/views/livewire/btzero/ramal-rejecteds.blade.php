@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    @if (!$lists->count())
        <div class="card">
            <div class="h4 text-center my-2">NENHUM INFORME SMC REJEITADO POR AQUI</div>
        </div>
    @else
        <div class="row mt-3">
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

        <div class="card">
            <div class="card-header edp-bg-seoweedgreen-100 text-white">
                <div class="col">
                    <h4 class="card-title edp-bg-seoweedgreen-100 text-white">INFORMES SMC REJEITADOS</h4>
                </div>
            </div>
            <table class="table table-sm table-striped table-condensed table-hover">
                <thead>
                    <tr class="table-dark">
                        <th class="text-center align-middle" scope="col">NOTA/OV</th>
                        <th class="text-center align-middle" scope="col">ORDEM</th>
                        <th class="text-center align-middle" scope="col">RUBRICA</th>
                        <th class="text-center align-middle" scope="col">MUNICIPIO</th>
                        <th class="text-center align-middle" scope="col">MOTIVO</th>
                        <th class="text-center align-middle" scope="col">DEVOLVIDO POR</th>
                        <th class="text-center align-middle" scope="col">DATA DEVOLUCAO</th>
                        <th class="text-center align-middle" scope="col">TEMPO</th>
                        <th class="text-center align-middle" scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $list)
                        <tr wire:key='ret-{{ $list->id }}'>
                            <td class="text-center align-middle">{{ $list->Note->note }}</td>
                            <td class="text-center align-middle">
                                @if ($list->Orders->count())
                                    @foreach ($list->Orders as $order)
                                        <p class="my-0 py-0">{{ $order->ordem }}</p>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-center align-middle">{{ $list->Note->rubrica }}</td>
                            <td class="text-center align-middle">{{ $list->Note->lexp }}</td>
                            <td class="text-center align-middle text-danger fw-bold">
                                {{ $list->ReturnRamal->last()->category }}</td>
                            <td class="text-center align-middle">{{ $list->ReturnRamal->last()->User->name }}</td>
                            <td class="text-center align-middle">
                                {{ date('d/m/Y H:i:s', strToTime($list->ReturnRamal->last()->created_at)) }}</td>
                            <td class="text-center align-middle text-primary fw-bold">
                                {{ Carbon::parse($list->ReturnRamal->last()->created_at)->diffForHumans(null, true) }}
                            </td>
                            <td class="text-center align-middle">
                                <i class="ri-play-circle-fill align-middle text-success fs-4" style="cursor: pointer;"
                                    wire:click="$emitTo('btzero.actions.worked-return-form', 'toReturnWork', {{ $list }})"></i>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- LivewireComponents --}}
    @livewire('btzero.actions.worked-return-form', key('worked-return-form'))
</div>
