@php
    use App\Helpers\DaysLeft;
    use Carbon\Carbon;
@endphp
<div>

    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_partial_info" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        OBRA {{ isset($form) && $form ? $form->Note->note : '' }} INFORMADA PARCIALMENTE
                    </h4>
                </div>
                <div class="modal-body">

                    @if ($form)
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                Informações da Nota
                            </div>

                            <table class="table table-striped-columns table-condensed">
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Cliente:</strong></td>
                                    <td>{{ mb_strToUpper($form->Note->client) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Nota:</strong></td>
                                    <td>{{ $form->Note->note }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Rubrica:</strong></td>
                                    <td>{{ mb_strToUpper($form->Note->rubrica) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Municipio:</strong></td>
                                    <td>{{ mb_strToUpper($form->Note->lexp) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Material:</strong></td>
                                    <td>{{ mb_strToUpper($form->Note->material) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Status:</strong></td>
                                    <td>{{ $form->Note->type_note == 2 ? $form->Note->nstats : $form->Note->centerjob }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Prazo:</strong></td>
                                    <td class="text-primary fw-bold">{{ (new DaysLeft($form->Note))->getLastDate() }}
                                    </td>

                                </tr>
                                <tr class="align-middle">
                                    <td class="text-end" style="width: 25%"><strong>Valor Planejado (Obra):</strong>
                                    </td>
                                    <td class="text-primary fw-bold">
                                        {{ $form->Orders ? 'R$ ' . number_format($form->Orders->sum('custPlanejado'), 2, ',', '.') : '' }}
                                    </td>
                                    </td>

                                </tr>
                            </table>

                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                Informações do Pedido Parcial
                            </div>
                            <table class="table table-striped-columns table-condensed">
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Empresa:</strong></td>
                                    <td>{{ mb_strToUpper($form->Company->name) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Data de Envio:</strong></td>
                                    <td>{{ Carbon::parse($form->created_at)->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                @if (trim($form->observation))
                                    <tr>
                                        <td class="text-end" style="width: 25%"><strong>Observação:</strong></td>
                                        <td>{{ $form->observation }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="text-end align-middle" style="width: 25%"><strong>Responsável:</strong>
                                    </td>
                                    <td>{{ $form->User->name }}</td>

                                </tr>
                            </table>
                        </div>


                        <div class="card mt-2">
                            <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ARQUIVOS ANEXADOS
                            </h5>
                            <div class="card-body py-2 px-3">
                                @livewire('components.files.show-files-pool', ['files' => $form->Files], key('filesView-' . $form->id))
                            </div>
                        </div>


                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                Parecer da Engenharia *
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <textarea class="form-control border border-1 border-secondary" wire:model.defer="engineer_feedback" rows="3"
                                        placeholder="Digite seu parecer aqui..."></textarea>
                                </div>
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-success mx-2" wire:click="toApprove">Aprovar</button>
                                    <button class="btn btn-danger mx-2" wire:click="toReject">Rejeitar</button>
                                </div>
                            </div>
                        </div>
                    @endif




                </div>
            </div>
        </div>
    </div>
</div>
