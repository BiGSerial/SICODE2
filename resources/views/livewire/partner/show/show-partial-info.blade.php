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
                            <div class="card-body">
                                <p class="py-1 my-0"><strong>Cliente:</strong> {{ mb_strToUpper($form->Note->client) }}
                                </p>
                                <p class="py-1 my-0"><strong>Nota:</strong> {{ $form->Note->note }}</p>
                                <p class="py-1 my-0"><strong>Rubrica:</strong> {{ mb_strToUpper($form->Note->rubrica) }}
                                </p>
                                <p class="py-1 my-0"><strong>Municipio:</strong> {{ mb_strToUpper($form->Note->lexp) }}
                                </p>
                                <p class="py-1 my-0"><strong>Material:</strong>
                                    {{ mb_strToUpper($form->Note->material) }}</p>
                                <p class="py-1 my-0"><strong>Status:</strong>
                                    {{ $form->Note->type_note == 2 ? $form->Note->nstats : $form->Note->centerjob }}</p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                Informações do Pedido Parcial
                            </div>
                            <div class="card-body">
                                <p class="py-1 my-0"><strong>Empresa:</strong>
                                    {{ mb_strToUpper($form->Company->name) }}</p>
                                <p class="py-1 my-0"><strong>Data de Envio:</strong>
                                    {{ Carbon::parse($form->created_at)->format('d/m/Y H:i:s') }}</p>
                                <p class="py-1 my-0"><strong>Data da Aprovação:</strong>
                                    {{ $form->allow || $form->deny ? Carbon::parse($form->decision_at)->format('d/m/Y H:i:s') : 'EM APROVAÇÃO' }}
                                </p>
                                <p class="py-1 my-0"><strong>Nome do Aprovador:</strong>
                                    {{ $form->allow || $form->deny ? $form->Engineer->name : '---' }}</p>
                                <p class="py-1 my-0"><strong>Data de Fiscalização:</strong>
                                    {{ ($form->allow && $form->supervision ? Carbon::parse($form->supervision_at)->format('d/m/Y H:i:s') : $form->allow && !$form->supervision) ? 'EM FISCALIZAÇÃO' : '---' }}
                                </p>
                                <p class="py-1 my-0"><strong>Fiscalizador:</strong>
                                    {{ $form->allow && $form->supervision ? $form->supervisor->name : '---' }}</p>
                                </p>
                                <p class="py-1 my-0"><strong>Data de Pagamento:</strong>
                                    {{ ($form->supervision && $form->supervision ? Carbon::parse($form->payment_at)->format('d/m/Y H:i:s') : $form->supervision && !$form->payment) ? 'EM PAGAMENTO' : '---' }}
                                </p>
                                </p>
                                <p class="py-1 my-0"><strong>Pagador:</strong>
                                    {{ $form->supervision && $form->payment ? $form->payer->name : '---' }}</p>
                            </div>
                        </div>

                        @if ($form->Files)
                            @livewire('components.files.show-files-pool', ['files' => $form->Files], key('filesView-' . $form->id))
                        @endif

                        <div class="row">
                            @if (trim($form->observation))
                                <div class="col-6">
                                    <div class="card">
                                        <div class="card-header text-bg-primary">Observação da Empreiteira</div>
                                        <div class="card-body">
                                            <p class="card-text">{{ $form->observation }}</p>
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
                                            <p class="card-text">{{ $form->engineer_info }}</p>
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
