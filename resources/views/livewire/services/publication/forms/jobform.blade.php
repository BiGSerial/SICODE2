@php
    use App\Helpers\SelectOptions;
@endphp

<div>
    <x-show-loading />

    @push('css')
        <style>
            .publication-close-modal {
                --pcm-bg: #f6f8fb;
                --pcm-surface: #ffffff;
                --pcm-ink: #0f172a;
                --pcm-muted: #64748b;
                --pcm-border: #dbe2ea;
                --pcm-primary: #0f766e;
                --pcm-primary-2: #0891b2;
                --pcm-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
            }

            .publication-close-modal .modal-content {
                border: 0;
                background: var(--pcm-bg);
            }

            .publication-close-modal .close-header {
                background: linear-gradient(120deg, #0f172a, var(--pcm-primary) 70%);
                color: #f8fafc;
                border-bottom: 0;
            }

            .publication-close-modal .close-title {
                font-weight: 700;
                letter-spacing: .02em;
            }

            .publication-close-modal .close-subtitle {
                font-size: .85rem;
                opacity: .82;
                margin-top: .15rem;
            }

            .publication-close-modal .close-body {
                background: radial-gradient(circle at 8% 0%, #ecfeff 0, transparent 34%), var(--pcm-bg);
            }

            .publication-close-modal .section-card {
                background: var(--pcm-surface);
                border: 1px solid var(--pcm-border);
                border-radius: 12px;
                box-shadow: var(--pcm-shadow);
                overflow: hidden;
            }

            .publication-close-modal .section-header {
                padding: .8rem 1rem;
                border-bottom: 1px solid var(--pcm-border);
                background: linear-gradient(135deg, rgba(15, 118, 110, .08), rgba(8, 145, 178, .08));
                font-size: .78rem;
                font-weight: 700;
                letter-spacing: .05em;
                text-transform: uppercase;
                color: #334155;
            }

            .publication-close-modal .section-body {
                padding: 1rem;
            }

            .publication-close-modal .info-table td {
                vertical-align: top;
                font-size: .88rem;
            }

            .publication-close-modal .info-grid {
                display: grid;
                gap: .75rem;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            }

            .publication-close-modal .info-item {
                border: 1px solid var(--pcm-border);
                border-radius: 10px;
                background: #f8fafc;
                padding: .65rem .75rem;
                min-height: 72px;
            }

            .publication-close-modal .info-item-label {
                font-size: .74rem;
                letter-spacing: .04em;
                text-transform: uppercase;
                color: #64748b;
                font-weight: 700;
                margin-bottom: .2rem;
            }

            .publication-close-modal .info-item-value {
                font-size: .92rem;
                color: #0f172a;
                font-weight: 600;
                line-height: 1.25rem;
            }

            .publication-close-modal .info-group-title {
                font-size: .78rem;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: #475569;
                font-weight: 700;
                margin-bottom: .7rem;
            }

            .publication-close-modal .info-label {
                width: 210px;
                color: #475569;
                font-weight: 700;
                text-transform: uppercase;
            }

            .publication-close-modal .footer-bar {
                border-top: 1px solid var(--pcm-border);
                background: #eef2f7;
            }
        </style>
    @endpush

    <div wire:ignore.self class="modal fade publication-close-modal" id="formProductionModal" tabindex="-1"
        aria-labelledby="formProductionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            @if ($production)
                <div class="modal-content">
                    <div class="modal-header close-header">
                        <div>
                            <h1 class="modal-title fs-5 close-title" id="formProductionModalLabel">
                                {{ mb_strtoupper($production->Service->service) }} - {{ $production->Note->note }}
                            </h1>
                            <div class="close-subtitle">Encerramento e acompanhamento da publicação</div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body close-body">
                        <div class="container py-2">
                            <div class="section-card mb-3">
                                <div class="section-header">Informações</div>
                                <div class="section-body">
                                    <div class="info-group-title">Resumo do Registro</div>
                                    <div class="info-grid mb-3">
                                        <div class="info-item">
                                            <div class="info-item-label">Nota/OV</div>
                                            <div class="info-item-value">{{ $production->Note->note }}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-item-label">Rubrica</div>
                                            <div class="info-item-value text-uppercase">{{ $production->Note->rubrica }}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-item-label">Município</div>
                                            <div class="info-item-value">{{ $production->Note->lexp }}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-item-label">Mudança no projeto</div>
                                            <div class="info-item-value">
                                                @if (isset($production->Note->WorkForm))
                                                    {{ $production->Note->WorkForm->changes ? 'SIM' : 'NÃO' }}
                                                @else
                                                    ---
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-group-title">Detalhamento</div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-item-label">Ordem</div>
                                            <div class="info-item-value">
                                                @if (isset($production->Note->WorkForm) && $production->Note->WorkForm->Orders->count())
                                                    @foreach ($production->Note->WorkForm->Orders as $order)
                                                        <div>{{ $order->ordem }}</div>
                                                    @endforeach
                                                @elseif (isset($production->Note->RamalForm) && $production->Note->RamalForm->Orders->count())
                                                    @foreach ($production->Note->RamalForm->Orders as $order)
                                                        <div>{{ $order->ordem }}</div>
                                                    @endforeach
                                                @else
                                                    ---
                                                @endif
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-item-label">Data informada</div>
                                            <div class="info-item-value">
                                                @if (isset($production->Note->WorkForm))
                                                    {{ date('d/m/Y', strtotime($production->Note->WorkForm->date)) }}
                                                @else
                                                    ---
                                                @endif
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-item-label">Data no Sicode</div>
                                            <div class="info-item-value">
                                                @if (isset($production->Note->WorkForm))
                                                    {{ date('d/m/Y H:i:s', strtotime($production->Note->WorkForm->informed_at)) }}
                                                @else
                                                    ---
                                                @endif
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-item-label">Equipe WPA</div>
                                            <div class="info-item-value">{{ $production->Note->WorkForm->team ?? '---' }}</div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-item-label">Responsável execução</div>
                                            <div class="info-item-value">{{ $production->Note->WorkForm->responsible ?? '---' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($production->Note->RamalForm && !$production->Note->WorkForm)
                                <div class="alert alert-danger text-center fw-bold mb-3">
                                    Favor não confirmar a 20 no SAP
                                </div>
                            @endif

                            <div class="section-card mb-3">
                                <div class="section-header">Resolução</div>
                                <div class="section-body">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-2">
                                            <label for="ativos" class="form-label">Qtd Ativos</label>
                                            <input type="number" id="ativos" class="form-control border-secondary"
                                                wire:model.defer="analise.postes" placeholder="Qtd Ativos">
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <label for="resultado" class="form-label">Resultado</label>
                                            <select id="resultado" class="form-select border-secondary"
                                                wire:model.defer="analise.conclusion">
                                                <option value="">Selecione...</option>
                                                @foreach (SelectOptions::getPublicationOptions() as $item)
                                                    <option value="{{ $item->value }}">{{ $item->info }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="info" class="form-label">Observação</label>
                                            <textarea id="info" class="form-control border-secondary" rows="4" wire:model.defer="analise.info"
                                                placeholder="Observação"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="section-card">
                                <div class="section-header">Arquivos</div>
                                <div class="section-body">
                                    @livewire('files.manager.create-prod-files', ['production' => $production, 'needFiles' => false, 'filesTypeMethod' => 'getPublicationFilesType'], key('create-prod-files-' . $production->id))
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer footer-bar">
                        <button type="button" class="btn btn-secondary" wire:click.prevent="saveForm()">Salvar</button>
                        <button type="button" class="btn btn-info" wire:click.prevent="waitingForm()">Esperar</button>
                        <button type="button" class="btn btn-warning"
                            wire:click="$emitTo('components.pausenote.pausenote2', 'stop_note', {{ $production }})">Pausar</button>
                        @if ($production->Note->WorkForm)
                            <button type="button" class="btn btn-success"
                                wire:click.prevent="to_finish()">Encerrar</button>
                        @elseif($production->Note->RamalForm)
                            <button type="button" class="btn btn-success"
                                wire:click.prevent="to_Publish()">Encerrar</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @livewire('components.pausenote.pausenote2', key('PauseNotes2'))

    <script>
        document.getElementById('formProductionModal').addEventListener('hidden.bs.modal', () => {
            document.getElementById('formProductionModal').removeAttribute('data-backdrop');
            Livewire.emitTo('services.publication.forms.jobform', 'closeAll');
        });
    </script>
</div>
