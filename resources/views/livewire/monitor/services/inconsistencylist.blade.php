@php
    use Carbon\Carbon;
@endphp
<div>
    {{-- Carrega o Loading da página --}}
    <x-show-loading />

    {{-- @dump($lists) --}}
    @if (!$lists->count())
        <div class="card">
            <div class="card-body">
                <h4 class="text-center">SEM INCONSISTÊNCIAS EM ABERTO</h4>
            </div>
        </div>
    @else
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
        <div class="card">
            <h4 class="card-header">INCONSISTÊNCIAS - <span
                    class="fs-6 fw-bold align-middle">({{ date('d/m/Y - H:i') }})</span> -
                <span class="fs-6 fw-bold align-middle">{{ $lists->count() }}</span>
            </h4>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-condensed table-stripped">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <div class="form-check">
                                        <input class="form-check-input border border-1 border-secondary" type="checkbox"
                                            value="" id="flexCheckDefault">

                                    </div>
                                </th>

                                <th scope="col">Usuário</th>
                                <th scope="col">Empresa</th>
                                <th scope="col">Serviço</th>
                                <th scope="col">Nota</th>
                                <th scope="col">Sts Prod</th>
                                <th scope="col">Sts Nota</th>
                                <th scope="col">Data Conclusão</th>
                                <th scope="col">Tentativas</th>
                                <th scope="col">Manual</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lists as $list)
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input border border-1 border-secondary"
                                                type="checkbox" value="" id="flexCheckDefault">

                                        </div>
                                    </td>
                                    @php
                                        $name = explode(' ', $list->User->name);
                                        $name = $name[0] . ' ' . end($name);
                                        $company = explode(' ', $list->User->load('Employee.Contract.company')->Employee->Contract->company->name);
                                        $company = $company[0];
                                    @endphp
                                    <td>{{ $name }}</td>
                                    <td>{{ $company }}
                                    </td>
                                    <td>{{ $list->Service->service }}</td>
                                    <td>{{ $list->Note->note }}</td>
                                    <td>{{ $list->status_note }}</td>
                                    <td>{{ $list->Note->nstats }}</td>
                                    <td>{{ date('d/m/Y H:i:s', strToTime($list->completed_at)) }}</td>
                                    <td>{{ $list->tries }}</td>
                                    <td>{{ $list->manual ? 'SIM' : 'NÃO' }}</td>
                                    <td><button class="btn btn-sm btn-primary"
                                            wire:click.prevent="historicsql({{ $list->Note->note }},{{ $list->id }})"><i
                                                class="ri-history-line"></i></button>
                                        <button class="btn btn-sm btn-success"
                                            wire:click.prevent="confirm_prod({{ $list->id }})"><i
                                                class="ri-checkbox-circle-line"></i></button>
                                        <button class="btn btn-sm btn-danger"
                                            wire:click.prevent="reject({{ $list->id }})"><i
                                                class="ri-close-circle-line"></i></button>
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

    <!-- Modal -->
    <div class="modal fade" id="historicnotes" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h5 class="modal-title" id="exampleModalLabel">{{ $titleModal }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($historics)
                        @if ($date_complete->Note && $date_complete->Note->type_note == 2)
                            <div class="table-responsive">
                                <table class="table table-sm table-stripped">
                                    <theader>
                                        <tr>
                                            <th scope="col" class="text-center">
                                                Usuario Sicode
                                            </th>
                                            <th scope="col" class="text-center">
                                                Usuario Base
                                            </th>
                                            <th scope="col" class="text-center">
                                                Transição
                                            </th>
                                            <th scope="col" class="text-center">
                                                Data Base
                                            </th>
                                            <th scope="col" class="text-center">
                                                Data Sicode
                                            </th>
                                            <th scope="col" class="text-center">
                                                Status Base
                                            </th>
                                            <th scope="col" class="text-center">
                                                Status Sicode
                                            </th>
                                        </tr>
                                    </theader>
                                    <tbody>


                                        @foreach ($historics as $hist)
                                            @php
                                                $completedAt = Carbon::parse($date_complete->completed_at);
                                                $dhStat = Carbon::parse($hist->dhStat);
                                                $diferencaEmDias = $completedAt->diffInDays($dhStat);

                                                $ok = false;
                                                $text1 = (string) $date_complete->status_note . ' para';
                                                $text2 = (string) $date_complete->Service->status . ' para';

                                                if (strstr($hist->transicao, $text1) != false || strstr($hist->transicao, $text2) != false) {
                                                    if (($diferencaEmDias >= -2 && $diferencaEmDias <= 2) || $hist->transicaoUsuario == $date_complete->User->Registration) {
                                                        $ok = true;
                                                    }
                                                }

                                            @endphp
                                            <tr class="@if (false) table-secondary @endif">

                                                <td class="text-center">{{ $date_complete->User->Registration }}</td>
                                                <td class="text-center">{{ $hist->usuario }}</td>
                                                <td class="text-center">{{ $hist->transicao }}</td>
                                                <td class="text-center">
                                                    {{ date('d/m/Y H:i:s', strToTime($hist->dhStat)) }}</td>
                                                <td class="text-center">
                                                    {{ date('d/m/Y H:i:s', strToTime($date_complete->completed_at)) }}
                                                </td>
                                                <td class="text-center">{{ $hist->numStat }}</td>
                                                <td class="text-center">{{ $date_complete->status_note }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm table-stripped">
                                    <theader>
                                        <tr>
                                            <th scope="col" class="text-center">
                                                Usuario Sicode
                                            </th>
                                            <th scope="col" class="text-center">
                                                CentroTrab Base
                                            </th>
                                            <th scope="col" class="text-center">
                                                CentroTrab Sicode
                                            </th>
                                            <th scope="col" class="text-center">
                                                Status Base
                                            </th>
                                            <th scope="col" class="text-center">
                                                Status Sicode
                                            </th>
                                    </theader>
                                    <tbody>
                                        @foreach ($historics as $hist)
                                            <tr class="">
                                                <td class="text-center">{{ $date_complete->User->Registration }}</td>
                                                <td class="text-center">{{ $hist->cenTrabResp }}</td>
                                                <td class="text-center">{{ $date_complete->centroTrab }}</td>
                                                <td class="text-center">{{ $hist->statusUsuario }}</td>
                                                <td class="text-center">{{ $date_complete->status_note }}</td>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click.prevent="closemodal">Fechar</button>

                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="actionConfirm" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content edp-bg-gray">
                <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                    <h5 class="modal-title" id="exampleModalLabel">{{ $titleModal }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click.prevent="closemodal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="info" class="form-label">Motivo: <span
                                class="text-danger fw-bold">*</span></label>
                        <textarea id="" class="form-control" cols="30" rows="10" wire:model.defer="info"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click.prevent="closemodal">Cancelar</button>
                    <button type="button"
                        class="btn @if ($action == 'REJEITAR') btn-danger
                    @else
                    btn-success @endif"
                        data-bs-dismiss="modal" wire:click.prevent="confirm">{{ $action }}</button>

                </div>
            </div>
        </div>
    </div>

</div>
