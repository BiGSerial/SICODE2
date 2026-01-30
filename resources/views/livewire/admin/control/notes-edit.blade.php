<div class="finish-five">
    <x-show-loading />

    <div class="modal fade" id="adminNoteModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-xxl-down">
            @if ($note)
                <form class="modal-content fivefx-card" wire:submit.prevent="save">
                    <div class="modal-header fivefx-header py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2">
                            <i class="ri-edit-2-line me-1"></i>
                            <span>Editar Nota</span>
                            <span class="fivefx-pill">Nota: {{ $note->note }}</span>
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body fivefx-body">
                        <div class="row g-4">
                            <div class="col-12 col-xl-6">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Identificacao</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Nota</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.note') is-invalid @enderror"
                                                wire:model.defer="note.note">
                                            @error('note.note')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Cliente</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.client') is-invalid @enderror"
                                                wire:model.defer="note.client">
                                            @error('note.client')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Usuario</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.user') is-invalid @enderror"
                                                wire:model.defer="note.user">
                                            @error('note.user')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Criado por</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.created_by') is-invalid @enderror"
                                                wire:model.defer="note.created_by">
                                            @error('note.created_by')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Data criacao</label>
                                            <input type="datetime-local"
                                                class="form-control fivefx-control @error('note.dt_created') is-invalid @enderror"
                                                wire:model.defer="note.dt_created">
                                            @error('note.dt_created')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Data status</label>
                                            <input type="datetime-local"
                                                class="form-control fivefx-control @error('note.dt_status') is-invalid @enderror"
                                                wire:model.defer="note.dt_status">
                                            @error('note.dt_status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Status</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.status') is-invalid @enderror"
                                                wire:model.defer="note.status">
                                            @error('note.status')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Nstats</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.nstats') is-invalid @enderror"
                                                wire:model.defer="note.nstats">
                                            @error('note.nstats')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Type note</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.type_note') is-invalid @enderror"
                                                wire:model.defer="note.type_note">
                                            @error('note.type_note')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Financeiro</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Valor</label>
                                            <input type="number" step="0.01"
                                                class="form-control fivefx-control @error('note.value') is-invalid @enderror"
                                                wire:model.defer="note.value">
                                            @error('note.value')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Moeda</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.currency') is-invalid @enderror"
                                                wire:model.defer="note.currency">
                                            @error('note.currency')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Eq. venda</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.eq_venda') is-invalid @enderror"
                                                wire:model.defer="note.eq_venda">
                                            @error('note.eq_venda')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Rubrica</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.rubrica') is-invalid @enderror"
                                                wire:model.defer="note.rubrica">
                                            @error('note.rubrica')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fivefx-k">Transaction</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.transaction') is-invalid @enderror"
                                                wire:model.defer="note.transaction">
                                            @error('note.transaction')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section">
                                    <h6 class="fivefx-k mb-2">Flags</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="mmgdSwitch" wire:model.defer="note.mmgd">
                                                <label class="form-check-label" for="mmgdSwitch">MMGD</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="doeSwitch" wire:model.defer="note.doe">
                                                <label class="form-check-label" for="doeSwitch">DOE</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    id="is45Switch" wire:model.defer="note.is45">
                                                <label class="form-check-label" for="is45Switch">Is45</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-xl-6">
                                <div class="fivefx-section mb-4">
                                    <h6 class="fivefx-k mb-2">Detalhes</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Pedido</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.numPedido') is-invalid @enderror"
                                                wire:model.defer="note.numPedido">
                                            @error('note.numPedido')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">PEP</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.pep') is-invalid @enderror"
                                                wire:model.defer="note.pep">
                                            @error('note.pep')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">PZE</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.pze') is-invalid @enderror"
                                                wire:model.defer="note.pze">
                                            @error('note.pze')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Material</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.material') is-invalid @enderror"
                                                wire:model.defer="note.material">
                                            @error('note.material')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Num material</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.num_material') is-invalid @enderror"
                                                wire:model.defer="note.num_material">
                                            @error('note.num_material')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Center job</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.centerjob') is-invalid @enderror"
                                                wire:model.defer="note.centerjob">
                                            @error('note.centerjob')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Nexp</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.nexp') is-invalid @enderror"
                                                wire:model.defer="note.nexp">
                                            @error('note.nexp')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Lexp</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.lexp') is-invalid @enderror"
                                                wire:model.defer="note.lexp">
                                            @error('note.lexp')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Validar prazo</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.validar_prazo') is-invalid @enderror"
                                                wire:model.defer="note.validar_prazo">
                                            @error('note.validar_prazo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">PZE tratado</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.pze_tratado') is-invalid @enderror"
                                                wire:model.defer="note.pze_tratado">
                                            @error('note.pze_tratado')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">PZE parecer</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.pze_parecer') is-invalid @enderror"
                                                wire:model.defer="note.pze_parecer">
                                            @error('note.pze_parecer')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Days left</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.days_left') is-invalid @enderror"
                                                wire:model.defer="note.days_left">
                                            @error('note.days_left')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Days</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.days') is-invalid @enderror"
                                                wire:model.defer="note.days">
                                            @error('note.days')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Days stat</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.days_stat') is-invalid @enderror"
                                                wire:model.defer="note.days_stat">
                                            @error('note.days_stat')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Postes</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.postes') is-invalid @enderror"
                                                wire:model.defer="note.postes">
                                            @error('note.postes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Mesalization</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.mesalization') is-invalid @enderror"
                                                wire:model.defer="note.mesalization">
                                            @error('note.mesalization')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">TX priority</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.txpriority') is-invalid @enderror"
                                                wire:model.defer="note.txpriority">
                                            @error('note.txpriority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">MA</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.ma') is-invalid @enderror"
                                                wire:model.defer="note.ma">
                                            @error('note.ma')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="fivefx-section">
                                    <h6 class="fivefx-k mb-2">Grupos</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Grupo 1</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.group1') is-invalid @enderror"
                                                wire:model.defer="note.group1">
                                            @error('note.group1')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Grupo 2</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.group2') is-invalid @enderror"
                                                wire:model.defer="note.group2">
                                            @error('note.group2')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Grupo 3</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.group3') is-invalid @enderror"
                                                wire:model.defer="note.group3">
                                            @error('note.group3')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Grupo 4</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.group4') is-invalid @enderror"
                                                wire:model.defer="note.group4">
                                            @error('note.group4')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fivefx-k">Grupo 5</label>
                                            <input type="text"
                                                class="form-control fivefx-control @error('note.group5') is-invalid @enderror"
                                                wire:model.defer="note.group5">
                                            @error('note.group5')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer fivefx-footer py-2 d-flex align-items-center">
                        <button type="button" class="btn btn-light fivefx-btn" data-bs-dismiss="modal">Cancelar</button>
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
            const modalEl = document.getElementById('adminNoteModal');
            if (!modalEl) return;

            modalEl.addEventListener('hidden.bs.modal', () => {
                Livewire.emitTo('admin.control.notes-edit', 'resetForm');
            });
        })();
    </script>
</div>
