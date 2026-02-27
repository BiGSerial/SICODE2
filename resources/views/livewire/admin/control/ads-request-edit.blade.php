<div class="finish-five">
    <x-show-loading />

    <div class="modal fade" id="adminAdsRequestModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xxl-down">
            @if ($requestModel)
                <form class="modal-content fivefx-card" wire:submit.prevent="save">
                    <div class="modal-header fivefx-header py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2">
                            <i class="ri-edit-2-line me-1"></i>
                            <span>Editar ADS Solicitada</span>
                            <span class="fivefx-pill">ID: {{ $requestModel->id }}</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body fivefx-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Nota</small>
                                    <strong>{{ $requestModel->note?->note ?? '---' }}</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Empresa</small>
                                    <strong>{{ $requestModel->company?->name ?? '---' }}</strong>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-2 border rounded bg-light">
                                    <small class="text-muted d-block">Status atual</small>
                                    <span class="badge {{ $requestModel->status?->badgeClass() ?? 'text-bg-secondary' }}">
                                        {{ $requestModel->status?->label() ?? $requestModel->status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fivefx-k">Usuário destinatário</label>
                                <select class="form-select fivefx-select" wire:model.defer="requestedBy">
                                    <option value="">Selecione...</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </select>
                                @error('requestedBy')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Status</label>
                                <select class="form-select fivefx-select" wire:model.defer="statusValue">
                                    @foreach ($statusOptions as $statusOption)
                                        <option value="{{ $statusOption->value }}">{{ $statusOption->value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Versão</label>
                                <input type="number" min="1" class="form-control fivefx-control"
                                    wire:model.defer="version">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Tentativas</label>
                                <input type="number" min="0" class="form-control fivefx-control"
                                    wire:model.defer="attempts">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Partner</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="adsPartner"
                                        wire:model.defer="partner">
                                    <label class="form-check-label" for="adsPartner">Sim</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Concluído</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="adsCompleted"
                                        wire:model.defer="completed">
                                    <label class="form-check-label" for="adsCompleted">Sim</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fivefx-k">Descrição / Observação</label>
                                <textarea class="form-control fivefx-control fivefx-textarea" rows="3"
                                    wire:model.defer="description"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fivefx-k">URL ADS</label>
                                <input type="text" class="form-control fivefx-control"
                                    wire:model.defer="url">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fivefx-k">Último erro</label>
                                <textarea class="form-control fivefx-control fivefx-textarea" rows="3"
                                    wire:model.defer="lastError"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fivefx-k">Início processamento</label>
                                        <input type="datetime-local" class="form-control fivefx-control"
                                            wire:model.defer="startedAt">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fivefx-k">Próxima tentativa</label>
                                        <input type="datetime-local" class="form-control fivefx-control"
                                            wire:model.defer="nextRetryAt">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Concluído em</label>
                                <input type="datetime-local" class="form-control fivefx-control"
                                    wire:model.defer="completedAt">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Entregue em</label>
                                <input type="datetime-local" class="form-control fivefx-control"
                                    wire:model.defer="deliveredAt">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Cancelado em</label>
                                <input type="datetime-local" class="form-control fivefx-control"
                                    wire:model.defer="canceledAt">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer fivefx-footer py-2">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary fivefx-btn">
                            <i class="ri-save-3-line me-1"></i>Salvar alterações
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
            const modalEl = document.getElementById('adminAdsRequestModal');
            if (!modalEl) return;

            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.control.ads-request-edit', 'resetForm');
            });
        })();
    </script>
</div>
