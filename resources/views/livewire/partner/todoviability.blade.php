@php
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div class="card mb-3">
        <div class="card-body">

            <div class="row">
                <div class="col-1">
                    <select name="" id="" class="form-select border border-secondary">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-2">
                    <input type="text" class="form-control border border-secondary" placeholder="Buscar">
                </div>

                <div class="col-3">

                </div>

                <div class="col-6 d-flex justify-content-end">
                    @livewire('components.filter.filter', ['myKey' => 'rubrica', 'sendFilter' => '', 'model' => 'App\Models\Note', 'column' => 'rubrica', 'filter' => 'Rubrica', 'group_filter' => 'partner', 'values' => 'rubrica', 'direction' => 'ASC', 'query' => ''], key('rubrica'))
                    @livewire('components.filter.filter', ['myKey' => 'region', 'sendFilter' => 'city', 'model' => 'App\Models\Edp_depc\City', 'column' => 'regiao', 'filter' => 'Regiao', 'group_filter' => 'partner', 'values' => 'regiao', 'direction' => 'ASC', 'query' => ''], key('region'))
                    @livewire('components.filter.filter', ['myKey' => 'city', 'sendFilter' => '', 'model' => 'App\Models\Edp_depc\City', 'column' => 'cidade', 'filter' => 'Municipio', 'group_filter' => 'partner', 'values' => 'municipio', 'direction' => 'ASC', 'query' => ''], key('city'))
                    @livewire('components.filter.remove-all', ['group_filter' => 'partner'], key('removeAll'))
                </div>
            </div>

        </div>
    </div>


    <div class="card mb-2 edp-bg-seoweedgreen-100 text-white">
        <h4 class="card-header">VIABILIDADE A EXECUTAR</h4>
    </div>

    @if (!$lists)
        <div class="text-center my-5 py-3">
            <h3>NENHUMA ATIVIDADE ENCONTRADA</h3>
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
        @foreach ($lists as $list)
            <div class="card my-3">
                <div class="card-body">
                    <table class="table table-sm my-0">
                        <thead>
                            <th scope="col">Nota/Ov</th>
                            <th scope="col">Ordem</th>
                            <th scope="col">Rubrica</th>
                            <th scope="col">Arquivos</button>
                            </th>
                            <th scope="col">Regiao</th>
                            <th scope="col">Centro</th>
                            <th scope="col">Municipio</th>

                        </thead>
                        <tbody class="table-group-divider">

                            <tr>
                                <td class="fw-bold">{{ $list->note }}</td>
                                <td>
                                    @if ($list->Viabilities->count())
                                        @foreach ($list->Viabilities as $order)
                                            <p class="p-0 m-0">{{ $order->Order->ordem }}</p>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-uppercase">{{ $list->rubrica }}</td>
                                <td>
                                    @if ($list->Files->count())
                                        @foreach ($list->Files as $file)
                                            <p class="p-0 m-0"><input class="form-check-input border border-secondary"
                                                    type="checkbox" value="{{ $file->id }}"
                                                    wire:model.defer="files_selected"> <i
                                                    class="bx bxs-file-{{ $file->ext }} text-danger"></i>
                                                <span wire:click.prevent="downloadFile({{ $file->id }})"
                                                    style="cursor: pointer;">{{ $file->file_name }}</span>
                                            </p>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-uppercase">
                                    {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->regiao : '' }}
                                </td>
                                <td class="text-uppercase">
                                    {{ $cities->Where('rdMunicipio', $list->nexp)->first() ? $cities->Where('rdMunicipio', $list->nexp)->first()->centroHana : '' }}
                                </td>
                                <td class="text-uppercase">{{ $list->lexp }}</td>

                            </tr>

                        </tbody>
                        <tfoot>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                @if ($list->Files->count())
                                    <span wire:click.prevent="downloadZip" style="cursor: pointer;"><i
                                            class="bx bx-cloud-download text-primary fs-5 align-middle"></i> Baixar
                                    </span>
                                @endif
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tfoot>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-end border-top border-2 border-secondary">
                    <div class="col-6">
                        <table class="table table-sm my-0">
                            <thead style="font-size: 10px;">
                                <th scope="col">Recebido Em</th>
                                <th scope="col">Prazo Estimado</th>
                                <th scope="col">Status</th>
                                <th scope="col">Ação</th>
                            </thead>
                            <tbody>
                                @php
                                    $status = null;
                                    $dueDate = $list->Viabilities->count()
                                        ? Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)
                                        : null;
                                    $today = Carbon::now();

                                    if ($dueDate) {
                                        $daysDifference = $dueDate ? $today->diffInDays($dueDate) : null;

                                        if ($daysDifference < 1) {
                                            $status = [
                                                'color' => 'text-bg-danger',
                                                'info' => 'VENCIDO',
                                            ];
                                        } elseif ($daysDifference >= 1 && $daysDifference < 3) {
                                            $status = [
                                                'color' => 'text-bg-warning',
                                                'info' => 'VENCENDO',
                                            ];
                                        } elseif ($daysDifference >= 3) {
                                            $status = [
                                                'color' => 'text-bg-success',
                                                'info' => 'NO PRAZO',
                                            ];
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td class="fw-bold">
                                        {{ Carbon::parse($list->Viabilities->last()->sended_at)->format('d/m/Y') }}
                                    </td>
                                    <td class="fw-bold text-danger">
                                        {{ Carbon::parse($list->Viabilities->last()->sended_at)->addDays(7)->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        @if ($status)
                                            <span class="badge {{ $status['color'] }}">{{ $status['info'] }}</span>
                                        @endif
                                    </td>
                                    <td><i class="bx bx-printer text-primary fs-5 me-2"></i>
                                        <i class="bx bx-play-circle text-success fs-5 me-2"></i>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

    @endif
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




</div>
