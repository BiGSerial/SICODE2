@php
    use Carbon\Carbon;
@endphp

<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade" id="modal_partial_info" tabindex="-1"
        aria-labelledby="modalPartialInfoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content partial-info-modal">
                <div class="modal-header partial-info-header">
                    <div>
                        <h4 class="my-0 fw-bold" id="modalPartialInfoLabel">INFORME PARCIAL</h4>
                        @if ($form)
                            <small class="d-block mt-1">
                                Nota/OV {{ $form->Note->note }} - {{ mb_strtoupper($form->Note->lexp ?? '') }}
                            </small>
                        @endif
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    @if ($form)
                        @php
                            $status = 'AVALIAÇÃO';
                            $statusClass = 'text-bg-warning';

                            if ($form->deny) {
                                $status = 'REJEITADO';
                                $statusClass = 'text-bg-danger';
                            } elseif ($form->payment && $form->allow) {
                                $status = 'PAGO';
                                $statusClass = 'text-bg-success';
                            } elseif ($form->supervision && !$form->payment) {
                                $status = 'EM PAGAMENTO';
                                $statusClass = 'text-bg-info';
                            } elseif ($form->allow && !$form->supervision) {
                                $status = 'EM FISCALIZAÇÃO';
                                $statusClass = 'text-bg-info';
                            }

                            $orders = $form->Orders->pluck('ordem')
                                ->merge($form->Note?->Orders?->pluck('ordem') ?? collect())
                                ->filter()
                                ->unique()
                                ->values();
                        @endphp

                        <div class="partial-info-summary mb-3">
                            <div class="partial-info-summary-item">
                                <span>Nota/OV</span>
                                <strong>{{ $form->Note->note }}</strong>
                            </div>
                            <div class="partial-info-summary-item">
                                <span>Empreiteira</span>
                                <strong>{{ mb_strtoupper($form->Company->name ?? 'Desconhecido') }}</strong>
                            </div>
                            <div class="partial-info-summary-item">
                                <span>Valor ADS</span>
                                <strong>{{ 'R$ ' . number_format($form->value, 2, ',', '.') }}</strong>
                            </div>
                            <div class="partial-info-summary-item">
                                <span>Status</span>
                                <strong><span class="badge {{ $statusClass }}">{{ $status }}</span></strong>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="partial-info-card h-100">
                                    <div class="partial-info-card-header">
                                        <h5>Informações da nota</h5>
                                    </div>
                                    <div class="partial-info-grid">
                                        <div class="partial-info-row">
                                            <span>Cliente</span>
                                            <strong>{{ mb_strtoupper($form->Note->client ?? '---') }}</strong>
                                        </div>
                                        <div class="partial-info-row">
                                            <span>Rubrica</span>
                                            <strong>{{ mb_strtoupper($form->Note->rubrica ?? '---') }}</strong>
                                        </div>
                                        <div class="partial-info-row">
                                            <span>Município</span>
                                            <strong>{{ mb_strtoupper($form->Note->lexp ?? '---') }}</strong>
                                        </div>
                                        <div class="partial-info-row">
                                            <span>Material</span>
                                            <strong>{{ mb_strtoupper($form->Note->material ?? '---') }}</strong>
                                        </div>
                                        <div class="partial-info-row">
                                            <span>Status SAP</span>
                                            <strong>{{ $form->Note->type_note == 2 ? $form->Note->nstats : $form->Note->centerjob }}</strong>
                                        </div>
                                        <div class="partial-info-row">
                                            <span>Ordens</span>
                                            <strong>
                                                @forelse ($orders as $order)
                                                    <span class="badge text-bg-light border me-1 mb-1">{{ $order }}</span>
                                                @empty
                                                    ---
                                                @endforelse
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="partial-info-card h-100">
                                    <div class="partial-info-card-header">
                                        <h5>Fluxo do informe</h5>
                                    </div>
                                    <div class="partial-info-timeline">
                                        <div class="partial-info-step done">
                                            <div class="partial-info-step-icon"><i class="ri-send-plane-line"></i></div>
                                            <div>
                                                <span>Enviado</span>
                                                <strong>{{ Carbon::parse($form->created_at)->format('d/m/Y H:i:s') }}</strong>
                                                <small>{{ $form->responsible ?: 'Responsável não informado' }}</small>
                                            </div>
                                        </div>
                                        <div class="partial-info-step {{ $form->allow || $form->deny ? 'done' : '' }}">
                                            <div class="partial-info-step-icon"><i class="ri-check-double-line"></i></div>
                                            <div>
                                                <span>Decisão da engenharia</span>
                                                <strong>{{ $form->decision_at ? $form->decision_at->format('d/m/Y H:i:s') : 'EM APROVAÇÃO' }}</strong>
                                                <small>{{ $form->Engineer?->name ?? '---' }}</small>
                                            </div>
                                        </div>
                                        <div class="partial-info-step {{ $form->supervision ? 'done' : '' }}">
                                            <div class="partial-info-step-icon"><i class="ri-shield-check-line"></i></div>
                                            <div>
                                                <span>Fiscalização</span>
                                                <strong>{{ $form->supervision_at ? $form->supervision_at->format('d/m/Y H:i:s') : 'EM FISCALIZAÇÃO' }}</strong>
                                                <small>{{ $form->Supervisor?->name ?? '---' }}</small>
                                            </div>
                                        </div>
                                        <div class="partial-info-step {{ $form->payment ? 'done' : '' }}">
                                            <div class="partial-info-step-icon"><i class="ri-money-dollar-circle-line"></i></div>
                                            <div>
                                                <span>Pagamento</span>
                                                <strong>{{ $form->payment_at ? $form->payment_at->format('d/m/Y H:i:s') : 'EM PAGAMENTO' }}</strong>
                                                <small>{{ $form->Payer?->name ?? '---' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="partial-info-card mt-3">
                            <div class="partial-info-card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <h5>Arquivos anexados</h5>
                                <span class="badge text-bg-light">{{ $form->Files->count() }} arquivo(s)</span>
                            </div>
                            <div class="partial-info-card-body">
                                @if ($form->Files->isNotEmpty())
                                    <x-files.attachments :files="$form->Files" downloadAction="downloadFile"
                                        :showHeader="false" :card="false" />
                                @else
                                    <div class="partial-info-empty">
                                        <i class="ri-folder-open-line"></i>
                                        <strong>NENHUM ARQUIVO ANEXADO</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-lg-6">
                                <div class="partial-info-card h-100">
                                    <div class="partial-info-card-header">
                                        <h5>Observação da empreiteira</h5>
                                    </div>
                                    <div class="partial-info-card-body">
                                        @if (trim((string) $form->observation))
                                            <div class="partial-info-rich-text">{!! nl2br(e($form->observation)) !!}</div>
                                            <div class="partial-info-footer">
                                                Responsável: <strong>{{ $form->responsible ?: '---' }}</strong>
                                            </div>
                                        @else
                                            <div class="partial-info-empty small-state">NENHUMA OBSERVAÇÃO</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="partial-info-card h-100">
                                    <div class="partial-info-card-header">
                                        <h5>Parecer da engenharia</h5>
                                    </div>
                                    <div class="partial-info-card-body">
                                        @if (trim((string) $form->engineer_info))
                                            <div class="partial-info-rich-text">{!! nl2br(e($form->engineer_info)) !!}</div>
                                            <div class="partial-info-footer">
                                                Responsável: <strong>{{ $form->Engineer?->name ?? '---' }}</strong>
                                            </div>
                                        @else
                                            <div class="partial-info-empty small-state">NENHUM PARECER INFORMADO</div>
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

@pushOnce('css')
    <style>
        .partial-info-modal {
            background: #f6f7fb;
            border: 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .partial-info-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 70%);
            color: #f8fafc;
            border: 0;
            padding: 1.2rem 1.5rem;
        }

        .partial-info-header small {
            color: rgba(248, 250, 252, .78);
        }

        .partial-info-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        .partial-info-summary-item,
        .partial-info-card {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, .06);
        }

        .partial-info-summary-item {
            padding: .85rem 1rem;
        }

        .partial-info-summary-item span,
        .partial-info-row span,
        .partial-info-step span {
            display: block;
            color: #6c757d;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .partial-info-summary-item strong,
        .partial-info-row strong,
        .partial-info-step strong {
            display: block;
            margin-top: .2rem;
            color: #212529;
            overflow-wrap: anywhere;
        }

        .partial-info-card {
            overflow: hidden;
        }

        .partial-info-card-header {
            background: #0f766e;
            color: #fff;
            padding: .75rem 1rem;
        }

        .partial-info-card-header h5 {
            font-size: .95rem;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
        }

        .partial-info-card-body {
            padding: 1rem;
        }

        .partial-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
            padding: 1rem;
        }

        .partial-info-row {
            min-height: 64px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fbfbfc;
            padding: .65rem .75rem;
        }

        .partial-info-timeline {
            display: grid;
            gap: .75rem;
            padding: 1rem;
        }

        .partial-info-step {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: .75rem;
            align-items: start;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: #fbfbfc;
            padding: .75rem;
        }

        .partial-info-step.done {
            border-color: rgba(15, 118, 110, .25);
            background: #f0fdfa;
        }

        .partial-info-step-icon {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: #e9ecef;
            color: #495057;
            font-size: 1.25rem;
        }

        .partial-info-step.done .partial-info-step-icon {
            background: #0f766e;
            color: #fff;
        }

        .partial-info-step small {
            display: block;
            margin-top: .15rem;
            color: #6c757d;
            overflow-wrap: anywhere;
        }

        .partial-info-empty {
            display: grid;
            place-items: center;
            min-height: 120px;
            border: 1px dashed #cfd6dd;
            border-radius: 8px;
            background: #fff;
            color: #6c757d;
            text-align: center;
            padding: 1rem;
        }

        .partial-info-empty i {
            font-size: 2rem;
        }

        .partial-info-empty.small-state {
            min-height: 96px;
            font-weight: 700;
        }

        .partial-info-rich-text {
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .partial-info-footer {
            margin-top: 1rem;
            border-top: 1px solid #e9ecef;
            padding-top: .75rem;
            color: #6c757d;
        }

        @media (max-width: 992px) {
            .partial-info-summary,
            .partial-info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 576px) {
            .partial-info-summary,
            .partial-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpushOnce
