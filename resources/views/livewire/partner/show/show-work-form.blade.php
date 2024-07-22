@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_form_work" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        OBRA INFORMADA
                    </h4>
                </div>
                <div class="modal-body">
                    @if ($form)
                        <div class="card">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÕES</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-condensed table-striped-columns">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">NOTA/OV:</td>
                                            <td class="align-middle fw-bold">{{ $form->Note->note }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">ORDEM:</td>
                                            <td class="align-middle">
                                                @if ($form->Orders->count())
                                                    @foreach ($form->Orders as $order)
                                                        <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">RUBRICA:</td>
                                            <td class="align-middle text-uppercase">{{ $form->Note->rubrica }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">MUNICIPIO:</td>
                                            <td class="align-middle text-uppercase">{{ $form->Note->lexp }}</td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold col-2 align-middle">EMPREITEIRA:</td>
                                            <td class="align-middle text-uppercase">{{ $form->Company->name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">MUDANÇA NO PROJETO:</td>
                                            <td class="align-middle">{{ $form->changes ? 'SIM' : 'NÃO' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">DATA DE EXECUÇÃO:</td>
                                            <td class="align-middle">{{ date('d/m/Y', strToTime($form->date)) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">DATA DO INFORME:</td>
                                            <td class="align-middle">
                                                {{ date('d/m/Y H:i:s', strToTime($form->created_at)) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">NÚMERO DD:</td>
                                            <td class="align-middle fw-bold">
                                                {{ $form->dd }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">EQUIPE WPA:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ $form->team }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">ENCARREGADO RESPONSÁVEL:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ $form->responsible }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">RESPONSÁVEL PELO INFORME:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ $form->informer }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">OBSERVAÇÕES
                                    </h5>
                                    <div class="card-body">
                                        @if (!trim($form->observation))
                                            <h5 class="text-center">NENHUMA OBSERVAÇÃO</h5>
                                        @else
                                            <p class="my-1 p-2">
                                                {!! $form->observation !!}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                        EQUIPAMENTOS
                                    </h5>
                                    <div class="card-body">
                                        @if (!$form->Equipment)
                                            <h5 class="text-center">NENHUM EQUIPAMENTO INSTALADO INFORMADO</h5>
                                        @else
                                            <table class="table table-sm table-condensed table-striped">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" class="text-center align-middle">Tipo
                                                        </th>
                                                        <th scope="col" class="text-center align-middle">
                                                            Patrimonio</th>
                                                        <th scope="col" class="text-center align-middle">
                                                            Movimento</th>
                                                        <th scope="col" class="text-center align-middle">Fases
                                                        </th>
                                                        <th scope="col" class="text-center align-middle">Poste
                                                            RF</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($form->Equipment as $equip)
                                                        <tr>
                                                            <td scope="col" class="text-center align-middle">
                                                                {{ $equip->type }}
                                                            </td>
                                                            <td scope="col" class="text-center align-middle">
                                                                {{ $equip->patrimony }}</td>
                                                            <td scope="col" class="text-center align-middle">
                                                                @if ($equip->installed)
                                                                    <i
                                                                        class="ri-arrow-right-line fs-3 text-success"></i>
                                                                @else
                                                                    <i class="ri-arrow-left-line fs-3 text-danger"></i>
                                                                @endif
                                                            </td>
                                                            <td scope="col" class="text-center align-middle">
                                                                {{ $equip->fases }}</td>
                                                            <td scope="col" class="text-center align-middle">
                                                                {{ $equip->pole }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÃO DE
                                        DANOS
                                    </h5>
                                    <div class="card-body">
                                        @if (!trim($form->description))
                                            <h5 class="text-center">NENHUMA INFORMAÇÃO</h5>
                                        @else
                                            <p class="my-1 p-2">
                                                {!! $form->description !!}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                                        MEDIDORES
                                    </h5>
                                    <div class="card-body">
                                        @if (!$form->Meeters->count())
                                            <h5 class="text-center">NENHUM MEDIDOR INSTALADO INFORMADO</h5>
                                        @else
                                            <table class="table table-sm table-condensed table-striped">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" class="text-center align-middle">Medidor
                                                        </th>
                                                        <th scope="col" class="text-center align-middle">
                                                            Borne</th>
                                                        <th scope="col" class="text-center align-middle">
                                                            Fases</th>

                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($form->Meeters as $meeter)
                                                        <tr>
                                                            <td scope="col" class="text-center align-middle">
                                                                {{ $meeter->number }}
                                                            </td>
                                                            <td scope="col" class="text-center align-middle">
                                                                {{ $meeter->borne }}</td>
                                                            <td scope="col" class="text-center align-middle">
                                                                {{ $meeter->fases }}</td>

                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
