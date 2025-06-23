@php
    use App\Custom\Viabilitiesstatus;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
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
                            </table>

                        </div>

                        <div
                            class="card mb-3 @if ($form->allow) text-bg-success @elseif ($form->deny) text-bg-danger @endif">
                            <div class="card-header text-white">
                                Informações do Pedido Parcial @if ($form->allow)
                                    (APROVADO)
                                @elseif ($form->deny)
                                    (REJEITADO)
                                @endif
                            </div>
                            <table class="table table-striped-columns table-condensed">
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Empresa:</strong></td>
                                    <td>{{ mb_strToUpper($form->Company->name) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Valor Ads:</strong></td>
                                    <td class="fs-6 fw-bold">
                                        {{ 'R$ ' . number_format($form->value, 2, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Data de Envio:</strong></td>
                                    <td>{{ Carbon::parse($form->created_at)->format('d/m/Y H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Data da Decisão:</strong></td>
                                    <td>{{ $form->allow || $form->deny ? Carbon::parse($form->decision_at)->format('d/m/Y H:i:s') : 'EM APROVAÇÃO' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Responsável Decisão:</strong></td>
                                    <td>{{ $form->Engineer?->name ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Data de Fiscalização:</strong>
                                    </td>
                                    <td>{{ $form->supervision_at?->format('d/m/Y') ?? 'EM FISCALIZAÇÃO' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Fiscalizador:</strong></td>
                                    <td>{{ $form->supervision?->nome ?? '---' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Data de Pagamento:</strong></td>
                                    <td>{{ $form->payment_at?->format('d/m/Y') ?? 'EM PAGAMENTO' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-end" style="width: 25%"><strong>Pagador:</strong></td>
                                    <td>{{ $form->payment?->name ?? '---' }}</td>
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

                        <div class="row">
                            @if (trim($form->observation))
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-header text-bg-primary">Observação da Empreiteira</div>
                                        <div class="card-body">
                                            <p class="card-text">{!! nl2br($form->observation) !!}</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="py-1 my-0"><strong>Responsável: </strong>{{ $form->responsible }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if (trim($form->engineer_info))
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-header text-bg-info">Parecer da Engenharia</div>
                                        <div class="card-body">
                                            <p class="card-text">{!! nl2br($form->engineer_info) !!}</p>
                                        </div>
                                        <div class="card-footer">
                                            <p class="py-1 my-0"><strong>Responsável:
                                                </strong>{{ $form->Engineer->name }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif




                </div>
            </div>
        </div>
    </div>
</div>
