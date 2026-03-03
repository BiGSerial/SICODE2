<div>
    <x-show-loading />
    @section('title')
        ENTREGA VIABILIDADE TÉCNICA - {{ $note->note }}
    @endsection

    @if ($note)
        <style>
            .viab-form-page {
                --vf-bg: #eef2f6;
                --vf-panel: #ffffff;
                --vf-border: #d8e0ea;
                --vf-ink: #0f172a;
                --vf-muted: #64748b;
                --vf-primary: #0f766e;
                --vf-primary-dark: #0f172a;
                --vf-warn: #b45309;
                --vf-danger: #b91c1c;
                background: radial-gradient(circle at 0% 0%, #dbeafe, transparent 42%),
                            radial-gradient(circle at 100% 0%, #dcfce7, transparent 36%),
                            var(--vf-bg);
                padding-bottom: 1.5rem;
            }

            .viab-form-page .vf-shell {
                max-width: 1180px;
            }

            .viab-form-page .vf-hero {
                margin-top: 1rem;
                border-radius: 1rem;
                background: linear-gradient(120deg, var(--vf-primary-dark), var(--vf-primary) 75%);
                color: #f8fafc;
                padding: 1.2rem 1.4rem;
                box-shadow: 0 16px 30px rgba(15, 23, 42, 0.28);
            }

            .viab-form-page .vf-eyebrow {
                text-transform: uppercase;
                letter-spacing: .08em;
                font-size: .72rem;
                opacity: .8;
                margin-bottom: .35rem;
            }

            .viab-form-page .vf-hero h4 {
                margin: 0;
                font-weight: 700;
            }

            .viab-form-page .vf-chip {
                background: rgba(248, 250, 252, .14);
                border: 1px solid rgba(248, 250, 252, .35);
                border-radius: .6rem;
                padding: .45rem .7rem;
                font-size: .82rem;
                min-width: 155px;
            }

            .viab-form-page .vf-chip strong {
                display: block;
                font-size: .95rem;
            }

            .viab-form-page .vf-card {
                background: var(--vf-panel);
                border: 1px solid var(--vf-border);
                border-radius: .9rem;
                box-shadow: 0 12px 22px rgba(15, 23, 42, 0.08);
            }

            .viab-form-page .vf-card .card-header {
                border-bottom: 1px solid #e2e8f0;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                border-radius: .9rem .9rem 0 0;
                font-weight: 700;
                color: #0f172a;
            }

            .viab-form-page .vf-step-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: .7rem;
            }

            .viab-form-page .vf-step {
                border: 1px solid var(--vf-border);
                border-radius: .75rem;
                background: #fff;
                padding: .7rem;
            }

            .viab-form-page .vf-step .n {
                width: 24px;
                height: 24px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: .74rem;
                font-weight: 700;
                background: #e2e8f0;
                color: #334155;
                margin-bottom: .4rem;
            }

            .viab-form-page .vf-step.active {
                border-color: #8dd3cc;
                box-shadow: 0 10px 18px rgba(15, 118, 110, 0.12);
            }

            .viab-form-page .vf-step.active .n {
                background: #0f766e;
                color: #fff;
            }

            .viab-form-page .vf-data-item {
                border-left: 4px solid #94a3b8;
                background: #f8fafc;
                border-radius: .55rem;
                padding: .5rem .6rem;
            }

            .viab-form-page .vf-data-item .k {
                font-size: .72rem;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: var(--vf-muted);
                font-weight: 700;
            }

            .viab-form-page .vf-data-item .v {
                color: var(--vf-ink);
                font-weight: 700;
                word-break: break-word;
            }

            .viab-form-page .vf-alert {
                border-radius: .75rem;
                border: 1px solid #e2e8f0;
                padding: .65rem .8rem;
                font-size: .9rem;
            }

            .viab-form-page .vf-alert.ok {
                background: #ecfdf5;
                border-color: #86efac;
                color: #166534;
            }

            .viab-form-page .vf-alert.warn {
                background: #fffbeb;
                border-color: #fcd34d;
                color: var(--vf-warn);
            }

            .viab-form-page .vf-required {
                color: var(--vf-danger);
                font-weight: 700;
            }

            .viab-form-page .vf-section-note {
                color: var(--vf-muted);
                font-size: .82rem;
                margin-top: .25rem;
            }

            .viab-form-page .vf-footer {
                border-top: 1px solid #e2e8f0;
                background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
                border-radius: 0 0 .9rem .9rem;
            }

            @media (max-width: 991px) {
                .viab-form-page .vf-step-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
        </style>

        @php
            $city = null;
            if ($this->cities && $note->nexp) {
                $city = $this->cities->where('rdMunicipio', $note->nexp)->first();
            }
        @endphp

        <div class="container viab-form-page">
            <div class="vf-shell mx-auto">
                <div class="vf-hero d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div>
                        <div class="vf-eyebrow">Fluxo de Entrega</div>
                        <h4>Análise de Viabilidade Técnica</h4>
                        <div class="small opacity-75">Preencha o formulário e envie com evidências para avaliação.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="vf-chip">
                            Nota/OV
                            <strong>{{ $note->note }}</strong>
                        </div>
                        <div class="vf-chip">
                            Ordens
                            <strong>{{ $note->Viabilities->count() }}</strong>
                        </div>
                        <div class="vf-chip">
                            Região
                            <strong>{{ $city?->regiao ?? '---' }}</strong>
                        </div>
                    </div>
                </div>

            <div class="card vf-card mt-3">
                <div class="card-header">
                    Dados da Obra
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <div class="vf-data-item">
                                <div class="k">Nota/OV</div>
                                <div class="v">{{ $note->note }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="vf-data-item">
                                <div class="k">Ordens</div>
                                <div class="v">
                                    @if ($note->Viabilities->count())
                                        @foreach ($note->Viabilities as $order)
                                            <span class="badge text-bg-light border text-dark me-1 mb-1">{{ $order->Order->ordem ?? '---' }}</span>
                                        @endforeach
                                    @else
                                        ---
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="vf-data-item">
                                <div class="k">Tipo</div>
                                <div class="v">{{ $note->rubrica ?? '---' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="vf-data-item">
                                <div class="k">Área</div>
                                <div class="v">{{ $note->group1 ?? '---' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="vf-data-item">
                                <div class="k">Grupo 2</div>
                                <div class="v">{{ $note->group2 ?? '---' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="vf-data-item">
                                <div class="k">Município</div>
                                <div class="v">{{ $city?->municipio ?? '---' }}</div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="vf-data-item">
                                <div class="k">Descrição</div>
                                <div class="v">{{ $note->material ?? '---' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card vf-card mt-3 mb-4">
                <div class="card-header">Envio da Viabilidade</div>
                <div class="card-body">
                    <div class="vf-step-grid mb-3">
                        <div class="vf-step {{ $changes !== '' ? 'active' : '' }}">
                            <div class="n">1</div>
                            <div class="fw-semibold">Defina o Resultado</div>
                            <div class="small text-muted">Informe se há alteração necessária.</div>
                        </div>
                        <div class="vf-step {{ $changes === 'SIM' ? 'active' : '' }}">
                            <div class="n">2</div>
                            <div class="fw-semibold">Justifique a Alteração</div>
                            <div class="small text-muted">Descreva motivo e impacto técnico.</div>
                        </div>
                        <div class="vf-step {{ $changes !== '' ? 'active' : '' }}">
                            <div class="n">3</div>
                            <div class="fw-semibold">Anexe Evidências</div>
                            <div class="small text-muted">Carregue croqui e arquivos necessários.</div>
                        </div>
                        <div class="vf-step {{ isset($result['responsible']) && trim((string) $result['responsible']) !== '' ? 'active' : '' }}">
                            <div class="n">4</div>
                            <div class="fw-semibold">Finalize o Envio</div>
                            <div class="small text-muted">Informe o responsável e confirme.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Necessita alteração? <span class="vf-required">*</span></label>
                            <select class="form-select" wire:model="changes">
                                <option value="">Selecione</option>
                                <option value="SIM">SIM</option>
                                <option value="NAO">NÃO</option>
                            </select>
                            <div class="vf-section-note">Essa decisão define o fluxo de preenchimento abaixo.</div>
                        </div>
                        <div class="col-md-8 mb-3 d-flex align-items-end">
                            @if ($changes === 'NAO')
                                <div class="vf-alert ok w-100">
                                    Resultado atual: <strong>Viabilidade aprovada sem necessidade de alteração.</strong>
                                </div>
                            @elseif ($changes === 'SIM')
                                <div class="vf-alert warn w-100">
                                    Resultado atual: <strong>Viabilidade com ajuste.</strong> Preencha motivo, detalhamento e nível de alteração.
                                </div>
                            @else
                                <div class="vf-alert w-100">
                                    Selecione uma opção para liberar os próximos campos obrigatórios.
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($changes === 'SIM')
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Motivo da Alteração <span class="vf-required">*</span></label>
                                <select class="form-select" wire:model="result.reason">
                                    <option value="">Selecione</option>
                                    <option value="AJUSTE MATERIAL">AJUSTE DE MATERIAL</option>
                                    <option value="AJUSTE DE PROJETO">AJUSTE DE PROJETO</option>
                                    <option value="PROPOSTA MELHORIA">PROPOSTA DE MELHORIA</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Detalhamento técnico <span class="vf-required">*</span></label>
                                <textarea class="form-control" cols="30" rows="5" wire:model.defer="result.reason_text"
                                    placeholder="Descreva claramente o que precisa ser alterado e por quê."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label for="size-change-range" class="form-label fw-semibold">
                                    Nível de alteração <span class="vf-required">*</span>
                                </label>
                                <input id="size-change-range" type="range" class="form-range" min="0" max="10"
                                    wire:model="result.sizechange" value="0">
                                <div class="progress" role="progressbar" aria-valuenow="0" aria-valuemin="0"
                                    aria-valuemax="100" style="height: 16px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                                        style="width: {{ isset($result['sizechange']) ? $result['sizechange'] * 10 : 0 }}%;">
                                        {{ isset($result['sizechange']) ? $result['sizechange'] * 10 : 0 }}%
                                    </div>
                                </div>
                                <div class="vf-section-note">Use 0-3 para ajustes leves, 4-7 para médios e 8-10 para altos impactos.</div>
                            </div>
                        </div>
                    @endif

                    @if ($changes != '')
                        <hr>
                        <div class="mb-3">
                            <h6 class="fw-bold mb-1">Evidências do informe</h6>
                            <div class="vf-section-note">Anexe os arquivos necessários para comprovar sua análise.</div>
                        </div>
                        @livewire('files.filepartners', ['note' => $note, 'needFiles' => true], key('FilesPartners'))

                        <div class="row mt-1">
                            <div class="mb-3 col-md-4">
                                <label class="form-label fw-semibold">Responsável pelo informe <span class="vf-required">*</span></label>
                                <input type="text" class="form-control"
                                wire:model.defer="result.responsible" />
                                <div class="vf-section-note">Informe o nome de quem está assinando tecnicamente esta entrega.</div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer vf-footer d-flex justify-content-end gap-2">
                    <button class="btn btn-danger m-2" wire:click.prevent="toCancelForm">CANCELAR</button>
                    <button class="btn btn-primary m-2" @disabled($changes === '')
                        wire:click.prevent="toSaveForm">ENTREGAR ANÁLISE</button>
                </div>
            </div>
        </div>
        </div>
    @endif
</div>

@push('script')
    <script>
        window.addEventListener('alertar', function(e) {

            const Confirmation = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });

            Swal.fire({
                title: e.detail.title,
                html: e.detail.msg,
                icon: e.detail.icon,
                showCancelButton: true,
                confirmButtonText: e.detail.btnOktxt,
                cancelButtonText: e.detail.btnCanceltxt,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    Livewire.emit(e.detail.action, e.detail.chave)

                } else if (
                    /* Read more about handling dismissals below */
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    Swal.fire(
                        e.detail.cancel_titulo,
                        e.detail.cancel_msg,
                        'success'
                    )
                }
            })
        });
    </script>
@endpush
