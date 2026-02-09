<div class="finish-five">
    <x-show-loading />

    <div class="modal fade" id="adminViabilityModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xxl-down">
            @if ($viability)
                <form class="modal-content fivefx-card" wire:submit.prevent="save">
                    <div class="modal-header fivefx-header py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2">
                            <i class="ri-edit-2-line me-1"></i>
                            <span>Editar Viabilidade</span>
                            <span class="fivefx-pill">ID: {{ $viability->id }}</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body fivefx-body">
                        @if ($errors->any())
                            <div class="alert alert-danger py-2">
                                <strong>Erros de validacao:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row g-4">
                            <div class="col-12 col-xl-7">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Dados principais</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Nota</label>
                                            <input type="text" class="form-control fivefx-control"
                                                value="{{ $viability->Note?->note ?? '---' }}" readonly>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Empresa</label>
                                            <select class="form-select fivefx-select" wire:model.defer="viability.company_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($companies as $company)
                                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Usuario</label>
                                            <select class="form-select fivefx-select" wire:model.defer="viability.user_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($companyUsers as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Engenheiro</label>
                                            <select class="form-select fivefx-select" wire:model.defer="viability.engineer_id">
                                                <option value="">Selecione...</option>
                                                @foreach ($engineers as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Order principal</label>
                                            <input type="number" class="form-control fivefx-control"
                                                wire:model.defer="viability.order_id">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Status</label>
                                            <input type="number" class="form-control fivefx-control"
                                                wire:model.defer="viability.status">
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Valor</label>
                                            <input type="number" step="0.01" class="form-control fivefx-control"
                                                wire:model.defer="viability.value">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Visivel parceira</label>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" id="visiblePartner"
                                                    wire:model.defer="viability.visible_partner">
                                                <label class="form-check-label" for="visiblePartner">Visivel</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Datas</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Inicio</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="initAt">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Enviado</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="sendedAt">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Retornado</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="returnedAt">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Tacito</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="tacitAt">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Concluido</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="completedAt">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Engenheiro em</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="engineerAt">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Contratado em</label>
                                            <input type="datetime-local" class="form-control fivefx-control"
                                                wire:model.defer="hiredAt">
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section">
                                    <h6 class="fivefx-k mb-2">Flags</h6>
                                    <div class="row g-2">
                                        @php
                                            $flags = [
                                                'tacit' => 'Tacito',
                                                'completed' => 'Concluido',
                                                'canceled' => 'Cancelado',
                                                'rejected' => 'Rejeitado',
                                                'approved' => 'Aprovado',
                                                'engineer' => 'Engenheiro',
                                                'hired' => 'Contratado',
                                                'replica' => 'Replica',
                                                'treplica' => 'TReplica',
                                                'inActivity' => 'Em atividade',
                                                'rehired' => 'Recontratado',
                                            ];
                                        @endphp
                                        @foreach ($flags as $field => $label)
                                            <div class="col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="flag-{{ $field }}"
                                                        wire:model.defer="viability.{{ $field }}">
                                                    <label class="form-check-label" for="flag-{{ $field }}">{{ $label }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-5">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Arquivos</h6>
                                    @if ($viability->Files?->count())
                                        <div class="table-responsive mb-3">
                                            <table class="table table-sm table-dark table-hover border-secondary mb-0">
                                                <thead>
                                                    <tr class="text-secondary">
                                                        <th scope="col">Arquivo</th>
                                                        <th scope="col">Ext</th>
                                                        <th scope="col"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($viability->Files as $file)
                                                        <tr>
                                                            <td>{{ $file->file_name }}</td>
                                                            <td>{{ $file->ext }}</td>
                                                            <td class="text-end">
                                                                <button type="button" class="btn btn-sm btn-outline-light"
                                                                    wire:click="downloadFile({{ $file->id }})">
                                                                    <i class="ri-download-line"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    wire:click="requestDeleteFile({{ $file->id }})">
                                                                    <i class="ri-delete-bin-line"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary bg-opacity-25 mb-0">
                                            Sem arquivos vinculados.
                                        </div>
                                    @endif

                                    <div class="mt-3">
                                        @livewire('files.manager.create-viab-files', ['viability' => $viability, 'service' => 'VIABILIDADE'], key('files-viab-control-' . $viability->id))
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mt-2">
                            <div class="col-12">
                                <div class="fivefx-section">
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
                                            Sem orders disponiveis.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-12">
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
                                                                    <i class="ri-link-unlink-m me-1"></i>Remover
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-secondary bg-opacity-25 mb-0">
                                            Sem orders relacionadas.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer fivefx-footer py-2 d-flex align-items-center">
                        <button type="button" class="btn btn-light fivefx-btn"
                            data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary fivefx-btn" wire:loading.attr="disabled">
                            <span wire:loading.remove>Salvar</span>
                            <span wire:loading>Salvando...</span>
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
    </style>

    <script>
        (function() {
            const modalEl = document.getElementById('adminViabilityModal');
            if (!modalEl) return;

            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.control.viability-edit', 'resetForm');
            });
        })();
    </script>
</div>
