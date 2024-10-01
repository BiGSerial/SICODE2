@php
    use Carbon\Carbon;
@endphp
<div>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="tacitresponse-show-modal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content edp-bg-gray">
                @if ($viability)
                    <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                        <h5> JUSTIFICATIVA TÁCITA {{ $viability->Note->note }}</h5>
                    </div>

                    <div class="modal-body">

                        <table class="table table-sm table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th class="col-2"></th>
                                    <th class="col"></th>
                                </tr>
                            </thead>
                            <tbody>

                                <tr>
                                    <td class="text-end align-middle fw-bold">Nota/OV:</td>
                                    <td class="align-middle text-start fw-bold">
                                        {{ $viability->Note->note }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Ordem:</td>
                                    <td class="align-middle text-start">
                                        @if ($viability->Note->Orders->isNotEmpty())
                                            @foreach ($viability->Note->Orders->filter(function ($order) {
        return !(strpos($order->statusSist, 'ENT') === 0 || strpos($order->statusSist, 'ENC') === 0);
    }) as $order)
                                                <p class="py-0 my-0">{{ $order->ordem }}</p>
                                            @endforeach
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Cliente:</td>
                                    <td class="align-middle text-start">{{ $viability->Note->client }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Descrição:</td>
                                    <td class="align-middle text-start">{{ $viability->Note->material }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Grupo2:</td>
                                    <td class="align-middle text-start">{{ $viability->Note->group2 }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Rubrica:</td>
                                    <td class="align-middle text-start">{{ $viability->Note->rubrica }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Data Envio:</td>
                                    <td class="align-middle text-start text-primary">
                                        {{ Carbon::parse($viability->sended_at)->format('d/m/Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Data Vencimento:</td>
                                    <td class="align-middle text-start text-primary">
                                        {{ Carbon::parse($viability->tacit_at)->format('d/m/Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end align-middle fw-bold">Status:</td>
                                    <td class="align-middle text-start text-primary">
                                        @if ($viability->Justification)
                                            @if ($viability->Justification->granted && !$viability->Justification->dismissed)
                                                <span class="badge bg-success">DEFERIDO</span>
                                            @elseif ($viability->Justification->dismissed && !$viability->Justification->granted)
                                                <span class="badge bg-danger">INDEFERIDO</span>
                                            @elseif (!$viability->Justification->dismissed && !$viability->Justification->granted)
                                                <span class="badge bg-primary">EM AVALIAÇÃO</span>
                                            @else
                                                <span class="badge bg-warning">INCONSISTÊNCIA</span>
                                            @endif
                                        @else
                                            @if ($viability->tacit)
                                                <span class="badge bg-secondary">SEM JUSTIFICATIVA</span>
                                            @else
                                                ---
                                            @endif
                                        @endif
                                    </td>
                                </tr>

                            </tbody>
                        </table>

                        @if ($viability->Justification && $viability->Justification->justification)
                            <div class="card">
                                <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                    <h5 class="my-0 py-0">JUSTIFICATIVA</h5>
                                </div>
                                <div class="card-body">
                                    <p>
                                        {{ $viability->Justification->justification }}
                                    </p>

                                </div>
                                <div class="card-footer">
                                    <p class="py-0 my-0"><span class="fw-bold">Usuario:
                                        </span>{{ $viability->justification->User->name }}</p>
                                    <p class="py-0 my-0"><span class="fw-bold">Data:
                                        </span>{{ Carbon::parse($viability->justification->justified_at)->format('d/m/Y H:i:s') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if ($viability->Justification && $viability->Justification->response)
                            <div class="card">
                                <div class="card-header edp-bg-sprucegreen-70 text-edp-verde">
                                    <h5 class="my-0 py-0">Resposta a Justificativa</h5>
                                </div>
                                <div class="card-body">
                                    <p>
                                        {{ $viability->Justification->response }}
                                    </p>

                                </div>
                                <div class="card-footer">
                                    <p class="py-0 my-0"><span class="fw-bold">Usuario:
                                        </span>{{ $viability->justification->Responsible->name }}</p>
                                    <p class="py-0 my-0"><span class="fw-bold">Data:
                                        </span>{{ Carbon::parse($viability->justification->answered_at)->format('d/m/Y H:i:s') }}
                                    </p>
                                </div>
                            </div>
                        @endif


                    </div>
                    <div class="model-footer">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = document.getElementById('response-viab-modal');
                modal.addEventListener('hidden.bs.modal', function() {
                    Livewire.emit('closeAll');
                });
            });
        </script>
    </div>
