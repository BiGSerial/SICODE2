<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="modal_partial_info" tabindex="-1"
        aria-labelledby="modalPartialInfoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content inform-modal">
                <div class="modal-header inform-header">
                    <div>
                        <h4 class="my-0 fw-bold" id="modalPartialInfoLabel">
                            INFORME PARCIAL
                            @if ($form)
                                <span class="badge {{ $statusClass }} ms-2 align-middle">{{ $status }}</span>
                            @endif
                        </h4>
                        @if ($form)
                            <small class="d-block mt-1">
                                Nota/OV {{ $form->Note->note }} · {{ mb_strtoupper($form->Note->lexp ?? '') }}
                            </small>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    @if ($form)
                        <x-inform.kpis :items="[
                            ['label' => 'Nota/OV', 'value' => $form->Note->note],
                            ['label' => 'Empreiteira', 'value' => mb_strtoupper($form->Company->name ?? 'Desconhecido')],
                            ['label' => 'Valor ADS', 'value' => 'R$ ' . number_format((float) $form->value, 2, ',', '.')],
                            ['label' => 'Enviado em', 'value' => $form->created_at?->format('d/m/Y H:i')],
                        ]" />

                        @if ($form->deny)
                            <x-inform.card title="Motivo da rejeição" tone="danger">
                                @if (filled(trim((string) $form->engineer_info)))
                                    <div class="inform-note is-danger">
                                        <div class="inform-text">{!! nl2br(e($form->engineer_info)) !!}</div>
                                        <small>{{ $form->decision_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                @else
                                    <x-inform.empty text="Motivo não informado" icon="ri-chat-off-line" />
                                @endif
                            </x-inform.card>
                        @endif

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <x-inform.card title="Informações da nota" class="h-100">
                                    <x-inform.facts :items="[
                                        ['label' => 'Cliente', 'value' => $form->Note->client],
                                        ['label' => 'Rubrica', 'value' => $form->Note->rubrica],
                                        ['label' => 'Município', 'value' => $form->Note->lexp],
                                        ['label' => 'Material', 'value' => $form->Note->material],
                                        ['label' => 'Status SAP', 'value' => $form->Note->type_note == 2 ? $form->Note->nstats : $form->Note->centerjob],
                                        ['label' => 'Ordens', 'value' => $orders->implode(', ')],
                                    ]" />
                                </x-inform.card>
                            </div>
                            <div class="col-lg-6">
                                <x-inform.card title="Andamento do informe" class="h-100">
                                    <x-inform.timeline :steps="[
                                        ['label' => 'Enviado', 'at' => $form->created_at, 'who' => $form->responsible, 'icon' => 'ri-send-plane-line', 'done' => true, 'tone' => null],
                                        ['label' => $form->deny ? 'Rejeitado' : 'Aprovação', 'at' => $form->decision_at, 'who' => $internal ? $form->Engineer?->name : null, 'icon' => $form->deny ? 'ri-close-circle-line' : 'ri-check-double-line', 'done' => $form->allow || $form->deny, 'tone' => $form->deny ? 'danger' : null],
                                        ['label' => 'Fiscalização', 'at' => $form->supervision_at, 'who' => $internal ? $form->Supervisor?->name : null, 'icon' => 'ri-shield-check-line', 'done' => (bool) $form->supervision, 'tone' => null],
                                        ['label' => 'Medição', 'at' => $form->payment_at, 'who' => $internal ? $form->Payer?->name : null, 'icon' => 'ri-money-dollar-circle-line', 'done' => (bool) $form->payment, 'tone' => null],
                                    ]" />
                                </x-inform.card>
                            </div>
                        </div>

                        <x-inform.card title="Arquivos anexados" :count="$form->Files->count() . ' arquivo(s)'">
                            @if ($form->Files->isNotEmpty())
                                <x-files.attachments :files="$form->Files" downloadAction="downloadFile" :showHeader="false" :card="false" />
                            @else
                                <x-inform.empty text="Nenhum arquivo anexado" icon="ri-folder-open-line" />
                            @endif
                        </x-inform.card>

                        <x-inform.card title="Observação da empreiteira">
                            @if (trim((string) $form->observation))
                                <div class="inform-text">{!! nl2br(e($form->observation)) !!}</div>
                                <small class="text-muted d-block mt-2">Responsável: {{ $form->responsible ?: '—' }}</small>
                            @else
                                <x-inform.empty text="Nenhuma observação" icon="ri-chat-off-line" />
                            @endif
                        </x-inform.card>

                        @if ($internal)
                            <x-inform.card title="Parecer da engenharia">
                                @if (trim((string) $form->engineer_info))
                                    <div class="inform-text">{!! nl2br(e($form->engineer_info)) !!}</div>
                                    <small class="text-muted d-block mt-2">Engenheiro: {{ $form->Engineer?->name ?? '—' }}</small>
                                @else
                                    <x-inform.empty text="Nenhum parecer informado" icon="ri-file-list-3-line" />
                                @endif
                            </x-inform.card>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
