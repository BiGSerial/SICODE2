@php
    use App\Helpers\SelectOptions;
    use App\Custom\NoteStatus;
@endphp

<div class="finish-five">
    <x-show-loading />

    <div class="modal fade" id="editFiveModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            @if ($five)
                <form class="modal-content fivefx-card" wire:submit.prevent="toSave">
                    {{-- HEADER --}}
                    <div class="modal-header fivefx-header py-2">
                        <h6 class="modal-title d-flex align-items-center gap-2">
                            <i class="ri-edit-2-line me-1"></i>
                            <span>Editar D5</span>
                            @if ($five?->note_d5)
                                <span class="fivefx-pill">D5: {{ $five->note_d5 }}</span>
                            @endif
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    {{-- BODY --}}
                    <div class="modal-body fivefx-body">
                        {{-- Linha 1: Básico --}}
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Nota D5</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.note_d5') is-invalid @enderror"
                                    wire:model.defer="five.note_d5" placeholder="Ex.: 16512345">
                                @error('five.note_d5')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fivefx-k">Local de Instalação</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.loc_install') is-invalid @enderror"
                                    wire:model.defer="five.loc_install" placeholder="Ex.: Poste 123 / Endereço">
                                @error('five.loc_install')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fivefx-k">Conjunto</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.conjunto') is-invalid @enderror"
                                    wire:model.defer="five.conjunto" placeholder="Ex.: 01">
                                @error('five.conjunto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fivefx-k">PEP</label>
                                <input type="text"
                                    class="form-control fivefx-control @error('five.pep') is-invalid @enderror"
                                    wire:model.defer="five.pep" placeholder="Ex.: PEP-0000">
                                @error('five.pep')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Linha 2: Codificação / Motivo / Empresa --}}
                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Codificação</label>
                                <div class="fivefx-select-wrap">
                                    <select class="form-select fivefx-select @error('five.codify') is-invalid @enderror"
                                        wire:model.defer="five.codify">
                                        <option value="" selected>Selecione</option>
                                        @foreach (SelectOptions::getD5codify() as $codifyD5)
                                            <option value="{{ $codifyD5->value }}">{{ $codifyD5->reason }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('five.codify')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fivefx-k">Motivo</label>
                                <div class="fivefx-select-wrap">
                                    <select class="form-select fivefx-select @error('five.reason') is-invalid @enderror"
                                        wire:model.defer="five.reason">
                                        <option value="" selected>Selecione</option>
                                        @foreach (SelectOptions::getD5Reasons() as $reasonD5)
                                            <option value="{{ $reasonD5->value }}">{{ $reasonD5->reason }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('five.reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fivefx-k">Empresa</label>
                                <div class="fivefx-select-wrap">
                                    <select
                                        class="form-select fivefx-select @error('five.company_id') is-invalid @enderror"
                                        wire:model.defer="five.company_id">
                                        <option value="" selected>Selecione</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('five.company_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Descrição (ampliada) --}}
                        <div class="mt-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <label for="fiveDescription" class="form-label fivefx-k mb-0">Descrição</label>
                                <small class="fivefx-muted" id="descCounter">0 caracteres</small>
                            </div>

                            <textarea id="fiveDescription"
                                class="form-control fivefx-control fivefx-textarea @error('five.description') is-invalid @enderror"
                                wire:model.defer="five.description" placeholder="Detalhes adicionais..."></textarea>

                            @error('five.description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>



                        {{-- Produções Associadas D5 (incompletas) --}}
                        <div class="mt-4">
                            <h6 class="fivefx-k mb-3">Produções Associadas (D5 pendentes)</h6>

                            @if (
                                $five->note &&
                                    $five->note->productions &&
                                    $five->note->productions->where('dfive', true)->where('completed', false)->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-dark table-hover border-secondary">
                                        <thead>
                                            <tr class="text-secondary">
                                                <th scope="col">Nota</th>
                                                <th scope="col">Serviço</th>
                                                <th scope="col">Usuário</th>
                                                <th scope="col">Atribuído Em</th>
                                                <th scope="col">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($five->note->productions->where('dfive', true)->where('completed', false) as $production)
                                                <tr>
                                                    <td>{{ $production->note->note }}</td>
                                                    <td>{{ $production->service?->service ?? 'N/A' }}</td>
                                                    <td>{{ $production->user->name ?? 'N/A' }}</td>
                                                    <td>{{ $production->att_at?->format('d/m/Y H:i') }}</td>
                                                    <td>
                                                        <span
                                                            class="badge {{ Notestatus::status($production->status)->colorbg }}">{{ Notestatus::status($production->status)->status }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-secondary bg-opacity-25 mb-0">
                                    <i class="ri-information-line me-1"></i>
                                    Nenhuma produção D5 pendente associada a esta nota.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- FOOTER --}}
                    <div class="modal-footer fivefx-footer py-2 d-flex align-items-center">
                        <div class="form-check me-auto">
                            <input class="form-check-input" type="checkbox" id="resendCheck" wire:model.defer="resend">
                            <label class="form-check-label" for="resendCheck">Reenviar</label>
                        </div>
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
        /* ====== BASE / TEMA ESCURO ====== */
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

        .finish-five .fivefx-card:focus-within {
            transform: translateY(-1px)
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

        /* ====== TIPOGRAFIA AUXILIAR ====== */
        .finish-five .fivefx-k {
            color: #9ca3af;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 2px
        }

        .finish-five .fivefx-muted {
            color: #a3a3a3;
            font-weight: 500;
            font-size: .82rem
        }

        /* ====== CONTROLES PADRÃO (inputs + textarea) ====== */
        .finish-five .form-label {
            color: #d1d5db
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

        .finish-five .fivefx-control::placeholder {
            color: #9ca3af;
            opacity: .95
        }

        .finish-five .fivefx-control:focus {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15), inset 0 0 0 9999px rgba(255, 255, 255, .01);
            color: #f3f4f6
        }

        .finish-five .form-control.is-invalid,
        .finish-five .form-select.is-invalid,
        .finish-five textarea.form-control.is-invalid {
            border-color: #ef4444;
            box-shadow: 0 0 0 .18rem rgba(239, 68, 68, .12)
        }

        .finish-five .invalid-feedback {
            color: #fecaca
        }

        /* ====== TEXTAREA (maior + auto-resize) ====== */
        .finish-five .fivefx-textarea {
            min-height: 180px;
            max-height: 60vh;
            resize: vertical;
            overflow: auto;
            scrollbar-width: thin;
            scrollbar-color: #3b82f6 transparent
        }

        .finish-five .fivefx-textarea::-webkit-scrollbar {
            height: 8px;
            width: 8px
        }

        .finish-five .fivefx-textarea::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px
        }

        /* ====== SELECTS (visual consistente no dark) ====== */
        .finish-five .fivefx-select-wrap {
            position: relative
        }

        .finish-five .fivefx-select {
            background-color: #0f172a;
            /* corpo do select fechado */
            border: 1px solid rgba(255, 255, 255, .18);
            color: #e5e7eb;
            border-radius: 12px;
            min-height: 42px;
            padding: .55rem 2.2rem .55rem .85rem;
            appearance: none;
            /* esconde caret nativo em alguns browsers */
            background-image:
                var(--bs-form-select-bg-img),
                /* caret do Bootstrap */
                linear-gradient(#0f172a, #0f172a);
            /* garante cor uniforme */
            background-repeat: no-repeat;
            background-position: right .8rem center, 0 0;
            background-size: 16px 12px, 100% 100%;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease
        }

        .finish-five .fivefx-select:focus {
            background-color: #0b1220;
            color: #f3f4f6;
            border-color: rgba(59, 130, 246, .65);
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .15)
        }

        /* Dropdown de options: limite de estilização conforme navegador */
        .finish-five .fivefx-select option,
        .finish-five .fivefx-select optgroup {
            background-color: #ffffff !important;
            color: #111827 !important
        }

        .finish-five .fivefx-select option:checked,
        .finish-five .fivefx-select option:hover {
            background-color: #e5e7eb !important;
            color: #111827 !important
        }

        .finish-five .fivefx-select option[disabled] {
            color: #9ca3af !important
        }

        /* CHECK/OUTROS */
        .finish-five .form-check-input {
            background-color: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .25)
        }

        .finish-five .form-check-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6
        }

        .finish-five .form-check-input:focus {
            box-shadow: 0 0 0 .18rem rgba(59, 130, 246, .2)
        }

        /* BOTÕES */
        .finish-five .fivefx-btn {
            font-weight: 700;
            border-radius: 10px
        }

        .finish-five .btn-light {
            color: #111827;
            background: #e5e7eb;
            border: 1px solid #e5e7eb
        }

        .finish-five .btn-primary {
            background: #2563eb;
            border-color: #2563eb
        }

        .finish-five .btn-primary:disabled {
            opacity: .7
        }

        /* RESPONSIVO */
        @media (max-width:576px) {
            .finish-five .modal-dialog {
                margin: .5rem
            }
        }

        /* HINT DE TEMA */
        .finish-five {
            color-scheme: dark;
        }
    </style>

    {{-- Feedback + Fechar modal via JS quando salvar + auto-resize/contador --}}
    <script>
        // Fecha modal após salvar (evento Livewire disparado no backend)
        window.addEventListener('five:saved', () => {
            const modalEl = document.getElementById('editFiveModal');
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.hide();
        });

        // Auto-resize + contador de caracteres da descrição
        (function() {
            const ta = document.getElementById('fiveDescription');
            const counter = document.getElementById('descCounter');

            const syncCounter = () => {
                if (!ta || !counter) return;
                const len = (ta.value || '').length;
                counter.textContent = `${len} ${len === 1 ? 'caractere' : 'caracteres'}`;
            };

            const autoResize = () => {
                if (!ta) return;
                ta.style.height = 'auto';
                // padding extra para conforto visual
                ta.style.height = Math.min(ta.scrollHeight + 6, window.innerHeight * 0.6) + 'px';
            };

            if (ta) {
                // Inicializa
                autoResize();
                syncCounter();
                // Eventos
                ['input', 'change'].forEach(evt => ta.addEventListener(evt, () => {
                    autoResize();
                    syncCounter();
                }));
                // Recalcula ao abrir o modal
                const modalEl = document.getElementById('editFiveModal');
                if (modalEl) {
                    modalEl.addEventListener('shown.bs.modal', () => {
                        autoResize();
                        syncCounter();
                    });
                }
            }
        })();
    </script>
</div>
