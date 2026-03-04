<div>
    <x-show-loading />

    <style>
        .rv-modal .modal-content {
            border: 1px solid #dbe5ef;
            border-radius: 1rem;
            background: radial-gradient(circle at 0% 0%, #dbe7f4 0%, transparent 38%),
                        radial-gradient(circle at 95% 10%, #d8f3e8 0%, transparent 34%),
                        #e8edf5;
        }

        .rv-modal .modal-header {
            background: linear-gradient(120deg, #0f172a, #0f766e 78%);
            color: #f8fafc;
            border-bottom: 0;
            padding: .9rem 1rem;
        }

        .rv-modal .rv-chip {
            background: rgba(248, 250, 252, .12);
            border: 1px solid rgba(248, 250, 252, .35);
            border-radius: .6rem;
            padding: .35rem .6rem;
            font-size: .8rem;
        }

        .rv-modal .rv-chip strong {
            display: block;
            font-size: .92rem;
            color: #fff;
        }

        .rv-modal .rv-panel {
            border: 1px solid #d7e2ee;
            border-radius: .85rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
        }

        .rv-modal .rv-panel .rv-head {
            padding: .75rem .9rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-size: .78rem;
            color: #0f172a;
        }

        .rv-modal .rv-panel .rv-body {
            padding: .9rem;
        }

        .rv-modal .rv-kv {
            display: grid;
            grid-template-columns: 135px 1fr;
            gap: .45rem .6rem;
            font-size: .88rem;
        }

        .rv-modal .rv-kv .k {
            color: #64748b;
            font-weight: 700;
        }

        .rv-modal .rv-kv .v {
            color: #0f172a;
            font-weight: 600;
            word-break: break-word;
        }

        .rv-modal .rv-steps {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .6rem;
            margin-bottom: .8rem;
        }

        .rv-modal .rv-step {
            border: 1px solid #d9e2ec;
            border-radius: .7rem;
            background: #fff;
            padding: .65rem;
        }

        .rv-modal .rv-step .n {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #334155;
            font-size: .72rem;
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .rv-modal .rv-step.active {
            border-color: #79cec5;
            box-shadow: 0 8px 16px rgba(15, 118, 110, 0.14);
        }

        .rv-modal .rv-step.active .n {
            background: #0f766e;
            color: #fff;
        }

        .rv-modal .rv-alert {
            border-radius: .7rem;
            border: 1px solid #e2e8f0;
            padding: .7rem .8rem;
            font-size: .9rem;
            background: #fff;
        }

        .rv-modal .rv-alert.warn {
            background: #fffbeb;
            border-color: #fcd34d;
            color: #92400e;
        }

        .rv-modal .rv-alert.ok {
            background: #ecfdf5;
            border-color: #86efac;
            color: #166534;
        }

        .rv-modal .rv-footer {
            background: #dfe7f2;
            border-top: 1px solid #cbd5e1;
        }

        @media (max-width: 991px) {
            .rv-modal .rv-steps {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .rv-modal .rv-kv {
                grid-template-columns: 110px 1fr;
            }
        }
    </style>

    <div wire:ignore.self class="modal fade rv-modal" id="returnViabilityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="modal-title mb-0">Entrega da Viabilidade</h5>
                        <div class="small opacity-75">Preencha os dados, anexe evidências e finalize a entrega.</div>
                    </div>
                    @if ($viability)
                        <div class="d-flex gap-2">
                            <div class="rv-chip">
                                Nota/OV
                                <strong>{{ $viability->Note->note ?? '---' }}</strong>
                            </div>
                            <div class="rv-chip">
                                Status
                                <strong>{{ \App\Custom\Viabilitiesstatus::status($viability->status)->status ?? '---' }}</strong>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-body">
                    <div class="container">
                        @if ($viability)
                            @php
                                $city = null;
                                if ($this->cities && $viability->Note->nexp) {
                                    $city = $this->cities->where('rdMunicipio', $viability->Note->nexp)->first();
                                }

                                $orders = $viability->Note->Orders->filter(function ($order) {
                                    return !(str_starts_with((string) $order->statusSist, 'ENT') || str_starts_with((string) $order->statusSist, 'ENC'));
                                });
                            @endphp

                            <div class="rv-panel mt-3">
                                <div class="rv-head">Dados da Obra</div>
                                <div class="rv-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="rv-kv">
                                                <div class="k">Cliente</div>
                                                <div class="v">{{ $viability->Note->client ?? '---' }}</div>
                                                <div class="k">Nota/OV</div>
                                                <div class="v">{{ $viability->Note->note ?? '---' }}</div>
                                                <div class="k">Tipo</div>
                                                <div class="v">{{ $viability->Note->rubrica ?? '---' }}</div>
                                                <div class="k">Grupo 2</div>
                                                <div class="v">{{ $viability->Note->group2 ?? '---' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="rv-kv">
                                                <div class="k">Área</div>
                                                <div class="v">{{ $viability->Note->group1 ?? '---' }}</div>
                                                <div class="k">Região</div>
                                                <div class="v">{{ $city?->regiao ?? '---' }}</div>
                                                <div class="k">Município</div>
                                                <div class="v">{{ $city?->municipio ?? '---' }}</div>
                                                <div class="k">Ordens</div>
                                                <div class="v">
                                                    @forelse($orders as $order)
                                                        <span class="badge text-bg-light border text-dark me-1 mb-1">{{ $order->ordem }}</span>
                                                    @empty
                                                        ---
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="rv-kv">
                                                <div class="k">Descrição</div>
                                                <div class="v">{{ $viability->Note->material ?? '---' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rv-panel mt-3">
                                <div class="rv-head">Fluxo de Entrega</div>
                                <div class="rv-body">
                                    <div class="rv-steps">
                                        <div class="rv-step {{ $changes ? 'active' : '' }}">
                                            <div class="n">1</div>
                                            <div class="fw-semibold">Resultado</div>
                                            <div class="small text-muted">Informe se precisa alterar.</div>
                                        </div>
                                        <div class="rv-step {{ $changes === 'SIM' ? 'active' : '' }}">
                                            <div class="n">2</div>
                                            <div class="fw-semibold">Justificativa</div>
                                            <div class="small text-muted">Motivo e detalhamento técnico.</div>
                                        </div>
                                        <div class="rv-step {{ $changes ? 'active' : '' }}">
                                            <div class="n">3</div>
                                            <div class="fw-semibold">Anexos</div>
                                            <div class="small text-muted">Ficha FVTO e documentos obrigatórios.</div>
                                        </div>
                                        <div class="rv-step {{ filled(data_get($reason, 'responsible')) ? 'active' : '' }}">
                                            <div class="n">4</div>
                                            <div class="fw-semibold">Responsável</div>
                                            <div class="small text-muted">Nome e envio final.</div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label fw-semibold">
                                                Necessita alteração? <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" wire:model="changes">
                                                <option value="">Selecione</option>
                                                <option value="SIM">SIM</option>
                                                <option value="NAO">NÃO</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8 mb-3 d-flex align-items-end">
                                            @if ($changes === 'SIM')
                                                <div class="rv-alert warn w-100">
                                                    Resultado da entrega: <strong>Com alteração</strong>. Preencha os campos obrigatórios de justificativa.
                                                </div>
                                            @elseif($changes === 'NAO')
                                                <div class="rv-alert ok w-100">
                                                    Resultado da entrega: <strong>Sem alteração</strong>. O sistema enviará como viabilizado.
                                                </div>
                                            @else
                                                <div class="rv-alert w-100">Escolha uma opção para liberar os próximos passos.</div>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($changes)
                                        @if ($changes === 'SIM')
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">
                                                        Motivo da alteração <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-select @error('reason.reason') is-invalid @enderror" wire:model="reason.reason">
                                                        <option value="">Selecione</option>
                                                        <option value="AJUSTE MATERIAL">AJUSTE DE MATERIAL</option>
                                                        <option value="AJUSTE DE PROJETO">AJUSTE DE PROJETO</option>
                                                        <option value="PROPOSTA MELHORIA">PROPOSTA DE MELHORIA</option>
                                                    </select>
                                                    @error('reason.reason')
                                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label fw-semibold">
                                                        Detalhamento técnico <span class="text-danger">*</span>
                                                    </label>
                                                    <textarea class="form-control @error('reason.description') is-invalid @enderror" cols="30" rows="5"
                                                        wire:model.defer="reason.description"
                                                        placeholder="Descreva o motivo da alteração de forma objetiva."></textarea>
                                                    @error('reason.description')
                                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label fw-semibold">
                                                        Nível de alteração <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="range" class="form-range @error('reason.changes') is-invalid @enderror"
                                                        min="0" max="10" wire:model="reason.changes" value="0">
                                                    <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                                        style="height: 16px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger"
                                                            style="width: {{ (int) data_get($reason, 'changes', 0) * 10 }}%;">
                                                            {{ (int) data_get($reason, 'changes', 0) * 10 }}%
                                                        </div>
                                                    </div>
                                                    @error('reason.changes')
                                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Anexos obrigatórios</label>
                                            <div class="small text-muted mb-2">
                                                Inclua a Ficha de Viabilidade Técnica de Execução de Obras (FVTO) e documentos contratuais necessários.
                                            </div>
                                            @livewire('files.manager.create-gen-files', ['note' => $viability->Note, 'service' => 'VIABILIDADE', 'viability_id' => $viability->id], key('FilesUploadVIability-' . $viability->id))
                                        </div>

                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-5">
                                                <label class="form-label fw-semibold">
                                                    Responsável pelo informe <span class="text-danger">*</span>
                                                </label>
                                                <input type="text"
                                                    class="form-control @error('reason.responsible') is-invalid @enderror"
                                                    wire:model.defer="reason.responsible" />
                                                @error('reason.responsible')
                                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-7">
                                                <div class="rv-alert {{ $hasFile && $hasFVTO ? 'ok' : 'warn' }}">
                                                    <strong>Validação de anexos:</strong>
                                                    Arquivos anexados: <strong>{{ $hasFile ? 'SIM' : 'NÃO' }}</strong> ·
                                                    FVTO identificada: <strong>{{ $hasFVTO ? 'SIM' : 'NÃO' }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <h4 class="fw-bold text-muted">Dados da viabilidade não carregados</h4>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer rv-footer d-flex justify-content-between">
                    <div class="small text-muted">
                        @if ($viability)
                            Viabilidade #{{ $viability->id }} em entrega.
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" wire:click.prevent="toSave">Submeter Viabilidade</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

            Confirmation.fire({
                title: e.detail.title,
                html: e.detail.msg,
                icon: e.detail.icon,
                showCancelButton: true,
                confirmButtonText: e.detail.btnOktxt,
                cancelButtonText: e.detail.btnCanceltxt,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.emit(e.detail.action, e.detail.chave);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire(e.detail.cancel_titulo, e.detail.cancel_msg, 'success');
                }
            });
        });
    </script>
@endpush
