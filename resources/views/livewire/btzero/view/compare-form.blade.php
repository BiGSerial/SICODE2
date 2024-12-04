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
                        <div class="card shadow-lg">
                            <div class="card-header bg-success text-white  py-1 my-0">
                                <h5 class="card-title mb-0  py-0 my-0">Informações</h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-hover table-borderless mb-0">
                                    <tbody>
                                        <tr>
                                            <th class="bg-light text-end" style="width: 25%;">Nota/OV:</th>
                                            <td>{{ $note->note }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-end">Ordem:</th>
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
                                            <th class="bg-light text-end">Rubrica:</th>
                                            <td>{{ $note->rubrica }}</td>
                                        </tr>
                                        <tr>
                                            <th class="bg-light text-end">Município:</th>
                                            <td class="text-uppercase">{{ $note->lexp }}</td>
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
                                            <th class="bg-light text-end">Empreiteira:</th>
                                            <td class="text-uppercase">{{ $empreiteira }}</td>
                                        </tr>

                                        <tr>
                                            <th class="bg-light text-end">Responsável pelo Informe:</th>
                                            @php
                                                $responsavel = 'Desconhecido';

                                                if ($note->WorkForm && $note->WorkForm->responsible) {
                                                    $responsavel = $note->WorkForm->responsible;
                                                } elseif ($note->RamalForm && $note->RamalForm->User) {
                                                    $responsavel = $note->RamalForm->User->name;
                                                }

                                            @endphp
                                            <td>{{ $responsavel }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-lg">
                                    <div class="card-header bg-success text-white py-1 my-0">
                                        <h5 class="card-title py-0 my-0">Informe Digitação</h5>
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
                                            <h5 class="text-center">SEM INFORME DE DIGITAÇÂO</h5>
                                        </div>
                                    @endif
                                </div>

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
                                            <h5 class="text-center">SEM INFORME DE DIGITAÇÂO</h5>
                                        </div>
                                    @endif
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-lg">
                                    <div class="card-header bg-success text-white py-1 my-0">
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

                                <div class="card shadow-lg">
                                    <div class="card-header bg-success text-white  py-1 my-0">
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
