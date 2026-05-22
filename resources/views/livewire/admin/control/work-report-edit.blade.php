<div class="finish-five">
    <x-show-loading />

    <div class="modal fade" id="adminWorkReportModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xxl-down">
            @if ($workReport)
                <form class="modal-content fivefx-card" wire:submit.prevent="save">
                    <div class="modal-header fivefx-header py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2">
                            <i class="ri-edit-2-line me-1"></i>
                            <span>Editar WorkReport</span>
                            <span class="fivefx-pill">ID: {{ $workReport->id }}</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body fivefx-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Nota</small>
                                    <strong>{{ $workReport->Note?->note ?? '---' }}</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Empresa</small>
                                    <strong>{{ $workReport->Company?->name ?? '---' }}</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Aceite</small>
                                    @if ($workReport->acceptance_accepted)
                                        <span class="badge text-bg-success">ACEITO</span>
                                    @else
                                        <span class="badge text-bg-secondary">SEM ACEITE</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-xl-7">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Dados principais</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Nota ID</label>
                                            <input type="number" class="form-control fivefx-control"
                                                wire:model.defer="workReport.note_id">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Numero da Nota</label>
                                            <input type="text" class="form-control fivefx-control"
                                                value="{{ $workReport->Note?->note ?? '---' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Empresa</label>
                                            <select class="form-select fivefx-select"
                                                wire:model.defer="workReport.company_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($companies as $company)
                                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Usuario</label>
                                            <select class="form-select fivefx-select"
                                                wire:model.defer="workReport.user_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Data (obra)</label>
                                            <input type="date" class="form-control fivefx-control"
                                                wire:model.defer="workReport.date">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">DD</label>
                                            <input type="text" class="form-control fivefx-control"
                                                wire:model.defer="workReport.dd">
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Informer</label>
                                            <input type="text" class="form-control fivefx-control"
                                                wire:model.defer="workReport.informer">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Equipe</label>
                                            <input type="text" class="form-control fivefx-control"
                                                wire:model.defer="workReport.team">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Responsavel</label>
                                            <input type="text" class="form-control fivefx-control"
                                                wire:model.defer="workReport.responsible">
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Datas de informe/aceite</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Informado em</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="informedAt">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Aceite em</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="acceptanceAt">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Nome do aceite</label>
                                            <input type="text" class="form-control fivefx-control"
                                                wire:model.defer="workReport.acceptance_name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Aceite aprovado</label>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="acceptanceAccepted"
                                                    wire:model.defer="workReport.acceptance_accepted">
                                                <label class="form-check-label" for="acceptanceAccepted">Aprovado</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="form-label fivefx-k">Acceptance meta (JSON)</label>
                                        <div class="small text-muted mb-1">Exibicao livre para suporte/controle. Edite somente se necessario.</div>
                                        <textarea class="form-control fivefx-control fivefx-textarea" rows="5"
                                            wire:model.defer="acceptanceMetaJson"></textarea>
                                    </div>
                                </div>

                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Observacoes</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Observacao</label>
                                            <textarea class="form-control fivefx-control fivefx-textarea" rows="4"
                                                wire:model.defer="workReport.observation"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Descricao</label>
                                            <textarea class="form-control fivefx-control fivefx-textarea" rows="4"
                                                wire:model.defer="workReport.description"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section">
                                    <h6 class="fivefx-k mb-2">Flags</h6>
                                    <div class="row g-2">
                                        @php
                                            $flags = [
                                                'equipment' => 'Teve equipamento',
                                                'connection' => 'Teve ligacao',
                                                'changes' => 'Teve alteracoes',
                                                'damage' => 'Teve danos',
                                                'approved' => 'Aprovado',
                                                'rejected' => 'Rejeitado',
                                                'retry' => 'Reenvio',
                                            ];
                                        @endphp
                                        @foreach ($flags as $field => $label)
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        id="flag-{{ $field }}"
                                                        wire:model.defer="workReport.{{ $field }}">
                                                    <label class="form-check-label"
                                                        for="flag-{{ $field }}">{{ $label }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Orders vinculadas</h6>
                                    @if (!empty($availableOrders))
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-dark table-hover border-secondary mb-0">
                                                <thead>
                                                    <tr class="text-secondary">
                                                        <th scope="col">Ordem</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($availableOrders as $order)
                                                        <tr>
                                                            <td>{{ $order['ordem'] ?? '' }}</td>
                                                            <td>{{ $order['statusSist'] ?? '' }}</td>
                                                            <td class="text-end">
                                                                <button type="button"
                                                                    class="btn btn-outline-primary btn-sm fivefx-btn"
                                                                    wire:click="addOrder({{ $order['id'] ?? 0 }})">
                                                                    <i class="ri-add-line me-1"></i>Adicionar
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary bg-opacity-25 mb-0">
                                            Sem orders disponiveis para essa nota.
                                        </div>
                                    @endif
                                </div>

                                <div class="fivefx-section">
                                    <h6 class="fivefx-k mb-2">Orders relacionadas</h6>
                                    @if (!empty($linkedOrders))
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-dark table-hover border-secondary mb-0">
                                                <thead>
                                                    <tr class="text-secondary">
                                                        <th scope="col">Ordem</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($linkedOrders as $order)
                                                        <tr>
                                                            <td>{{ $order['ordem'] ?? '' }}</td>
                                                            <td>{{ $order['statusSist'] ?? '' }}</td>
                                                            <td class="text-end">
                                                                <button type="button"
                                                                    class="btn btn-outline-danger btn-sm fivefx-btn"
                                                                    wire:click="removeOrder({{ $order['id'] ?? 0 }})">
                                                                    <i class="ri-link-unlink-m me-1"></i>Desatribuir
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary bg-opacity-25 mb-0">
                                            Sem orders vinculadas no momento.
                                        </div>
                                    @endif
                                </div>

                                <div class="fivefx-section mt-4">
                                    <h6 class="fivefx-k mb-2">Primeira solicitação ADS válida</h6>

                                    @if ($firstValidAdsRequest)
                                        <div class="table-responsive mb-0">
                                            <table class="table table-sm table-dark table-hover border-secondary mb-0">
                                                <thead>
                                                    <tr class="text-secondary">
                                                        <th scope="col">Solicitação</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Solicitado por</th>
                                                        <th scope="col">Data entrega</th>
                                                        <th scope="col">Tempo</th>
                                                        <th scope="col">Link</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>#{{ $firstValidAdsRequest['id'] }}</td>
                                                        <td>{{ $firstValidAdsRequest['status'] }}</td>
                                                        <td>{{ $firstValidAdsRequest['requested_by_name'] ?? '---' }}</td>
                                                        <td>{{ $firstValidAdsRequest['delivered_at'] ?? '---' }}</td>
                                                        <td>
                                                            @php
                                                                $withinDeadline = $firstValidAdsRequest['within_deadline'] ?? null;
                                                                $badgeClass = $withinDeadline === null
                                                                    ? 'text-bg-secondary'
                                                                    : ($withinDeadline ? 'text-bg-success' : 'text-bg-danger');
                                                            @endphp
                                                            <span class="badge {{ $badgeClass }}">
                                                                {{ $firstValidAdsRequest['elapsed'] ?? '---' }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ $firstValidAdsRequest['url'] }}" target="_blank" rel="noopener noreferrer"
                                                                class="btn btn-outline-info btn-sm fivefx-btn">
                                                                <i class="ri-link me-1"></i>Abrir
                                                            </a>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary bg-opacity-25 mb-0">
                                            Nenhuma solicitação ADS válida encontrada (status DONE e link preenchido).
                                        </div>
                                    @endif
                                </div>

                                <div class="fivefx-section mt-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fivefx-k mb-0">ADSForm vinculado</h6>
                                        @if ($adsFormId)
                                            <span class="badge text-bg-info">ID ADS: {{ $adsFormId }}</span>
                                        @else
                                            <span class="badge text-bg-secondary">Nao cadastrado</span>
                                        @endif
                                    </div>

                                    @if (!$adsFormEnabled)
                                        <div class="alert alert-secondary bg-opacity-25 mb-3">
                                            Este WorkReport ainda nao possui ADSForm editavel no modal.
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm fivefx-btn"
                                            wire:click="enableAdsForm">
                                            <i class="ri-add-circle-line me-1"></i>Criar/Editar ADSForm
                                        </button>
                                    @else
                                        <div class="small text-muted mb-2">
                                            Arquivos vinculados ao ADSForm: <strong>{{ $adsFilesCount }}</strong>
                                        </div>

                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label fivefx-k">Responsavel ADS</label>
                                                <input type="text" class="form-control fivefx-control"
                                                    wire:model.defer="adsName">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fivefx-k">Valor ADS</label>
                                                <input type="text" class="form-control fivefx-control"
                                                    wire:model.defer="adsAmount" placeholder="0,00">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fivefx-k">Contrato</label>
                                                <input type="text" class="form-control fivefx-control"
                                                    wire:model.defer="adsContract">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fivefx-k">Centro</label>
                                                <input type="text" class="form-control fivefx-control"
                                                    wire:model.defer="adsCenter">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fivefx-k">Deposito</label>
                                                <input type="text" class="form-control fivefx-control"
                                                    wire:model.defer="adsDeposit">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fivefx-k">Observacao ADS</label>
                                                <textarea class="form-control fivefx-control fivefx-textarea" rows="3"
                                                    wire:model.defer="adsObs"></textarea>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="adsPartial"
                                                        wire:model.defer="adsPartial">
                                                    <label class="form-check-label" for="adsPartial">ADS Parcial</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" id="adsTacit"
                                                        wire:model.defer="adsTacit">
                                                    <label class="form-check-label" for="adsTacit">ADS Tacita</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6"></div>
                                            <div class="col-md-6">
                                                <label class="form-label fivefx-k">Prazo tacito</label>
                                                <input type="datetime-local" class="form-control fivefx-control"
                                                    wire:model.defer="adsTacitDueAt">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fivefx-k">Tacita entregue em</label>
                                                <input type="datetime-local" class="form-control fivefx-control"
                                                    wire:model.defer="adsTacitDeliveredAt">
                                            </div>
                                        </div>

                                        @if ($adsFormId)
                                            <div class="mt-3">
                                                <button type="button" class="btn btn-outline-danger btn-sm fivefx-btn"
                                                    wire:click="requestDeleteAdsForm">
                                                    <i class="ri-delete-bin-6-line me-1"></i>Excluir ADSForm
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer fivefx-footer py-2">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary fivefx-btn">
                            <i class="ri-save-3-line me-1"></i>Salvar alteracoes
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <style>
        .finish-five .fivefx-section {
            padding: 1rem;
            background: rgba(255, 255, 255, .04);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 14px;
        }

        .finish-five .fivefx-card {
            background: radial-gradient(1200px 600px at 100% -20%, rgba(37, 99, 235, .12), transparent 40%),
                radial-gradient(1200px 600px at -10% 120%, rgba(14, 165, 233, .10), transparent 35%),
                linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border: 0;
            border-radius: 14px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, .55), 0 12px 30px rgba(0, 0, 0, .35);
            backdrop-filter: saturate(120%) blur(2px);
            transition: transform .18s ease, box-shadow .18s ease
        }

        .finish-five .fivefx-header {
            background: rgba(17, 24, 39, .9);
            border: 0;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, .06)
        }

        .finish-five .fivefx-footer {
            background: rgba(17, 24, 39, .9);
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .06);
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px
        }

        .finish-five .btn-close {
            filter: invert(1);
            opacity: .9
        }

        .finish-five .fivefx-body {
            padding: 1.1rem 1.25rem
        }

        .finish-five .fivefx-pill {
            display: inline-block;
            background: #0ea5e9;
            color: #fff;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .77rem;
            font-weight: 700
        }

        .finish-five .fivefx-k {
            color: #9ca3af;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 2px
        }

        .finish-five .fivefx-control {
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .12);
            color: #e5e7eb;
            border-radius: 12px;
            padding: .6rem .85rem;
            line-height: 1.35;
            min-height: 42px;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease
        }

        .finish-five .fivefx-control:focus {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15), inset 0 0 0 9999px rgba(255, 255, 255, .01);
            color: #f3f4f6
        }

        .finish-five .fivefx-select {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, .18);
            color: #e5e7eb;
            border-radius: 12px;
            min-height: 42px;
            padding: .55rem 2.2rem .55rem .85rem;
            appearance: none;
            background-image:
                var(--bs-form-select-bg-img),
                linear-gradient(#0f172a, #0f172a);
            background-repeat: no-repeat;
            background-position: right .8rem center, 0 0;
            background-size: 16px 12px, 100% 100%;
        }

        .finish-five .fivefx-select:focus {
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15);
            color: #f3f4f6;
        }

        .finish-five .fivefx-textarea {
            min-height: 120px;
            resize: vertical;
        }
    </style>

    <script>
        (function() {
            const modalEl = document.getElementById('adminWorkReportModal');
            if (!modalEl) return;

            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.control.work-report-edit', 'resetForm');
            });
        })();
    </script>
</div>
