<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_compareForm" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold ">
                        COMPARAÇÃO DE INFORMES
                    </h4>
                </div>
                <div class="modal-body">
                    @if ($note)

                        <div class="card">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">INFORMAÇÕES</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-condensed table-striped-columns">

                                    <tbody>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">NOTA/OV:</td>
                                            <td class="align-middle fw-bold">{{ $note->note }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">ORDEM:</td>
                                            <td class="align-middle">
                                                @if ($note->Orders->count())
                                                    @foreach ($note->Orders as $order)
                                                        <p class="my-1 py-0">{{ $order->ordem }}</p>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">MATERIAL:</td>
                                            <td class="align-middle fw-bold">
                                                {{ $note->material }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">RUBRICA:</td>
                                            <td class="align-middle text-uppercase">{{ $note->rubrica }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold col-2 align-middle">MUNICIPIO:</td>
                                            <td class="align-middle text-uppercase">{{ $note->lexp }}</td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold col-2 align-middle">EMPREITEIRA:</td>
                                            <td class="align-middle text-uppercase">
                                                @if ($note->WorkForm)
                                                    {{ $note->WorkForm->Company->name }}
                                                @elseif($note->RamalForm)
                                                    {{ $note->RamalForm->Company->name }}
                                                @else
                                                    Desconhecido
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="table-success">
                                            <td class="fw-bold col-2 align-middle">DATA DO INFORME SMC:</td>
                                            <td class="align-middle">
                                                @if ($note->RamalForm)
                                                    {{ isset($note->RamalForm->created_at) ? date('d/m/Y H:i:s', strToTime($note->RamalForm->created_at)) : '' }}
                                                @else
                                                    Não Informado
                                                @endif

                                            </td>
                                        </tr>


                                        <tr class="table-success">
                                            <td class="fw-bold col-2 align-middle">RESPONSÁVEL PELO INFORME SMC:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ $note->RamalForm ? $note->RamalForm->User->name : 'Não Informado' }}
                                            </td>
                                        </tr>

                                        <tr class="table-primary">
                                            <td class="fw-bold col-2 align-middle">MUDANÇA NO PROJETO:</td>
                                            <td class="align-middle text-uppercase">
                                                @if ($note->WorkForm)
                                                    {{ $note->WorkForm->Company->name }}
                                                @else
                                                    Não Informado
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td class="fw-bold col-2 align-middle">DATA DE EXECUÇÃO:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ $note->WorkForm ? date('d/m/Y', strToTime($note->WorkForm->date)) : 'Não Informado' }}
                                            </td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td class="fw-bold col-2 align-middle">DATA DO INFORME:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ isset($note->workForm->informed_at) ? date('d/m/Y H:i:s', strToTime($note->workForm->informed_at)) : 'Não Informado' }}

                                            </td>
                                        </tr>

                                        <tr class="table-primary">
                                            <td class="fw-bold col-2 align-middle">ENCARREGADO RESPONSÁVEL:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ $note->WorkForm ? $note->WorkForm->responsible : 'Não Informado' }}
                                            </td>
                                        </tr>
                                        <tr class="table-primary">
                                            <td class="fw-bold col-2 align-middle">RESPONSÁVEL PELO INFORME:</td>
                                            <td class="align-middle text-uppercase">
                                                {{ $note->WorkForm ? $note->WorkForm->informer : 'Não Informado' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- <div class="card shadow-lg">
                            <div class="card-header bg-success text-white  py-1 my-0">
                                <h5 class="card-title mb-0  py-0 my-0">Informações</h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover table-borderless mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:15%"></th>
                                            <th style="width:35%"></th>
                                            <th style="width:15%"></th>
                                            <th style="width:35%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-end" style="widtd: 25%;">Nota/OV:</td>
                                            <td>{{ $note }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-end">Ordem:</td>
                                            <td>
                                                @if ($note->WorkForm && $note->WorkForm->Orders->isNotEmpty())

                                                    <ul class="list-unstyled mb-0">
                                                        @foreach ($note->WorkForm->Orders as $order)
                                                            <li>{{ $order->ordem }}</li>
                                                        @endforeach

                                                    </ul>
                                                @elseif ($note->RamalForm && $note->RamalForm->Orders->isNotEmpty())
                                                    <ul class="list-unstyled mb-0">
                                                        @foreach ($note->RamalForm->Orders as $order)
                                                            <li>{{ $order->ordem }}</li>
                                                        @endforeach

                                                    </ul>

                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-end">Rubrica:</td>
                                            <td>{{ $note->rubrica }}</td>
                                        </tr>
                                        <tr>

                                        </tr>
                                        <tr>
                                            @php
                                                $empreiteira = 'Desconhecido';

                                                if ($note->WorkForm && $note->WorkForm->Company) {
                                                    $empreiteira = $note->WorkForm->Company->name;
                                                } elseif ($note->RamalForm && $note->RamalForm->Company) {
                                                    $empreiteira = $note->RamalForm->Company->name;
                                                }
                                            @endphp
                                            <td class="fw-bold text-end">Empreiteira:</td>
                                            <td class="text-uppercase">{{ $empreiteira }}</td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold text-end">Resp. Inf SMC:</td>

                                            <td>{{ $note->RamalForm ? $note->RamalForm->User->name : 'Não Informado' }}
                                            </td>
                                            <td class="fw-bold text-end">Resp Inf. Conclusao:</td>

                                            <td>{{ $note->WorkForm ? $note->WorkForm->responsible : 'Não Informado' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="fw-bold text-end">Data Inf SMC:</td>

                                            <td>{{ $note->RamalForm ? date('d/m/Y', strToTime($note->RamalForm->created_at)) : 'Não Informado' }}
                                            </td>
                                            <td class="fw-bold text-end">Data Inf. Conclusao:</td>

                                            <td>{{ $note->WorkForm ? date('d/m/Y', strToTime($note->WorkForm->created_at)) : 'Não Informado' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-end"></td>

                                            <td>
                                            </td>
                                            <td class="fw-bold text-end">Alteração Projeto:</td>

                                            <td>
                                                @if ($note->WorkForm && $note->WorkForm->changes)
                                                    <span class="badge bg-danger">Sim</span>
                                                @elseif($note->WorkForm && !$note->WorkForm->changes)
                                                    <span class="badge bg-primary">Não</span>
                                                @else
                                                    <span class="badge bg-secondary">Não Informado</span>
                                                @endif
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div> --}}





                        <div class="card mt-2">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ARQUIVOS ANEXADOS
                            </h5>
                            <div class="card-body py-2 px-3">
                                @livewire('components.files.show-files-pool', ['files' => $note->Files], key('filesView-' . $note->id))
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-lg">
                                    <div class="card-header bg-success text-white py-1 my-0">
                                        <h5 class="card-title py-0 my-0">Informe SMC</h5>
                                    </div>
                                    @if ($note->RamalForm)
                                        <div class="card-body p-1">
                                            <p>{{ $note->RamalForm->observation }}</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="text-muted my-0 py-0">Data de Digitação:
                                                {{ $note->RamalForm->created_at->format('d/m/Y H:i:s') }}</p>
                                            <p class="text-muted my-0 py-0">User:
                                                {{ $note->RamalForm->User->name }}</p>
                                        </div>
                                    @else
                                        <div class="card-body p-1">
                                            <h5 class="text-center">SEM INFORME DE SMC</h5>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-lg">
                                    <div class="card-header bg-primary text-white py-1 my-0">
                                        <h5 class="card-title py-0 my-0">Informe Conclusão</h5>
                                    </div>
                                    @if ($note->WorkForm)
                                        <div class="card-body p-1">
                                            <p>{{ $note->WorkForm->observation }}</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="text-muted my-0 py-0">Data de Digitação:
                                                {{ $note->WorkForm->created_at->format('d/m/Y H:i:s') }}</p>
                                            <p class="text-muted my-0 py-0">User:
                                                {{ $note->WorkForm->responsible }}</p>
                                        </div>
                                    @else
                                        <div class="card-body p-1">
                                            <h5 class="text-center">SEM INFORME DE CONCLUSÃO</h5>
                                        </div>
                                    @endif
                                </div>



                            </div>
                        </div>





                        <div class="row">
                            <div class="col-md-6">

                                <div class="card shadow-lg">
                                    <div class="card-header bg-success text-white  py-1 my-0">
                                        <h5 class="card-title py-0 my-0">Equipamentos Declarados</h5>
                                    </div>
                                    @if ($note->RamalForm && $note->RamalForm->BtzeroEquipment->isNotEmpty())
                                        <table class="table-sm table-condensed table-stripped">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Tipo</th>
                                                    <th class="text-center">Patrimonio</th>
                                                    <th class="text-center">Movimento</th>
                                                    <th class="text-center">Poste Ref.</th>
                                                    <th class="text-center">Fases</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($note->RamalForm->BtzeroEquipment as $equipment)
                                                    <tr>
                                                        <td class="fw-bold text-center">{{ $equipment->type }}</td>
                                                        <td class="fw-bold text-center">{{ $equipment->patrimony }}
                                                        </td>
                                                        <td class="fw-bold text-center">
                                                            @if ($equipment->installed)
                                                                <i
                                                                    class="ri-arrow-right-line text-success fw-bold fs-4 align-middle"></i>
                                                            @else
                                                                <i
                                                                    class="ri-arrow-left-line text-danger fw-bold fs-4 align-middle"></i>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">{{ $equipment->pole }}</td>
                                                        <td class="text-center">{{ $equipment->fases }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="card-body p-1">
                                            <h5 class="text-center">SEM INFORME DE SMC</h5>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">

                                <div class="card shadow-lg">
                                    <div class="card-header bg-primary text-white  py-1 my-0">
                                        <h5 class="card-title py-0 my-0">Equipamentos Declarados</h5>
                                    </div>
                                    @if ($note->WorkForm && $note->WorkForm->Equipment->isNotEmpty())
                                        <table class="table-sm table-condensed table-stripped">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">Tipo</th>
                                                    <th class="text-center">Patrimonio</th>
                                                    <th class="text-center">Movimento</th>
                                                    <th class="text-center">Poste Ref.</th>
                                                    <th class="text-center">Fases</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($note->WorkForm->Equipment as $equipment)
                                                    <tr>
                                                        <td class="fw-bold text-center">{{ $equipment->type }}</td>
                                                        <td class="fw-bold text-center">{{ $equipment->patrimony }}
                                                        </td>
                                                        <td class="fw-bold text-center">
                                                            @if ($equipment->installed)
                                                                <i
                                                                    class="ri-arrow-right-line text-success fw-bold fs-4 align-middle"></i>
                                                            @else
                                                                <i
                                                                    class="ri-arrow-left-line text-danger fw-bold fs-4 align-middle"></i>
                                                            @endif
                                                        </td>
                                                        <td class="text-center">{{ $equipment->pole }}</td>
                                                        <td class="text-center">{{ $equipment->fases }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="card-body p-1">
                                            <h5 class="text-center">SEM INFORME DE CONCLUSÃO</h5>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

</div>
