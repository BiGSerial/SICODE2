@php
    use Carbon\Carbon;
    use Carbon\CarbonInterval;
    use App\Custom\Viabilitiesstatus;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    <div class="card">
        <h4 class="card-header">
            RELATÓRIO DE VIABILIDADE
        </h4>
        <div class="card-body">
            <div class="row">

                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Coluna Referência</label>
                    <select class="form-select form-select-sm" aria-label="Small select example" wire:model="column">
                        <option value="" selected>Por Intervalo</option>
                        <option value="completed_at" selected>COMPLETADO EM</option>
                        <option value="hired_at" selected>CONTRATADO EM</option>
                        <option value="sended_at" selected>ENVIADO EM</option>
                    </select>
                </div>

                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Apartir de:</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_init">
                </div>
                <div class="mb-3 col-2">
                    <label for="exampleFormControlInput1" class="form-label">Até:</label>
                    <input type="date" class="form-control form-control-sm" wire:model="dt_end"
                        min="{{ $dt_init }}">
                </div>



            </div>
        </div>
    </div>

    @if ($lists)
        <div class="row">
            <div class="col-1">
                <button class="btn btn-sm btn-primary mb-3" wire:click.prevent='Export'>Exportar</button>
            </div>

            <div class="col-6">
                {{ $lists->links() }}
            </div>
            <div class="col-5 d-flex justify-content-end align-middle">
                <span class="align-middle"> Exibindo {{ $lists->firstItem() }} até
                    {{ $lists->lastItem() }}
                    de {{ $lists->total() }}
                    registros.</span>
            </div>

        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-condensed table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Contratante</th>
                                <th scope="col">Empresa</th>
                                <th scope="col">Ordem</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Contratado</th>
                                <th scope="col">Enviado Em</th>
                                <th scope="col">Contratado Em</th>
                                <th scope="col">Viabilizado Em</th>
                                <th scope="col">Completado em</th>
                                <th scope="col">Responsável</th>
                                <th scope="col">Empreiteira</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr>
                                    <td class="align-middlw">{{ $list->User->name }}</td>
                                    <td class="align-middlw">{{ $list->User->Employee->Contract->Company->name }}</td>
                                    <td class="align-middlw">{{ $list->Order->ordem }}</td>
                                    <td class="align-middlw">{{ $list->Order->Note->note }}</td>
                                    <td class="align-middlw">{{ $list->hired ? 'SIM' : 'NÃO' }}</td>
                                    <td class="align-middlw">
                                        {{ $list->sended_at ? date('d/m/Y', strToTime($list->sended_at)) : '---' }}
                                    </td>
                                    <td class="align-middlw">
                                        {{ $list->hired_at ? date('d/m/Y', strToTime($list->hired_at)) : '---' }}
                                    </td>
                                    <td class="align-middlw">
                                        {{ $list->returned_at ? date('d/m/Y', strToTime($list->returned_at)) : '---' }}
                                    </td>
                                    <td class="align-middlw">
                                        {{ $list->completed_at ? date('d/m/Y', strToTime($list->completed_at)) : '---' }}
                                    </td>
                                    <td class="align-middlw">{{ $list->Engineer->name }}</td>
                                    <td class="align-middlw">{{ $list->Company->name }}</td>
                                    <td class="align-middlw"><span
                                            class="badge {{ Viabilitiesstatus::status($list->status)->colorbg }}">{{ Viabilitiesstatus::status($list->status)->status }}</span>
                                    </td>
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

</div>
