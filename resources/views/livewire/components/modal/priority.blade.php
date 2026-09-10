@php
    use Carbon\Carbon;
@endphp
@once
    <style>
        .priority-info-modal .modal-dialog {
            max-width: min(640px, calc(100vw - 1.5rem));
        }

        .priority-info-modal .modal-content {
            border: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
        }

        .priority-info-modal__header {
            align-items: flex-start;
            background: #123f43;
            color: #f8fafc;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            padding: 1rem 1.25rem;
        }

        .priority-info-modal__title {
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.2;
            margin: 0;
            text-transform: uppercase;
        }

        .priority-info-modal__close {
            align-items: center;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: #ffffff;
            display: inline-flex;
            height: 34px;
            justify-content: center;
            line-height: 1;
            min-width: 34px;
        }

        .priority-info-modal__body {
            background: #f8fafc;
            padding: 1rem 1.25rem 1.15rem;
        }

        .priority-info-modal__reason {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            color: #1f2937;
            font-size: 0.92rem;
            font-weight: 600;
            line-height: 1.5;
            margin: 0;
            min-height: 120px;
            overflow-wrap: anywhere;
            padding: 0.9rem;
            white-space: pre-line;
        }

        .priority-info-modal__meta {
            align-items: center;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            color: #9a3412;
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem 0.8rem;
            justify-content: space-between;
            margin-top: 0.75rem;
            padding: 0.65rem 0.8rem;
        }

        .priority-info-modal__meta-item {
            align-items: center;
            display: inline-flex;
            font-size: 0.78rem;
            font-weight: 800;
            gap: 0.3rem;
        }

        .priority-info-modal__footer {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 0.85rem 1.25rem;
        }

        .priority-info-modal__footer .btn {
            align-items: center;
            border-radius: 6px;
            display: inline-flex;
            font-size: 0.8rem;
            font-weight: 800;
            gap: 0.35rem;
            min-height: 36px;
        }
    </style>
@endonce
<div>
    <div wire:ignore.self class="modal fade" id="priorityModal" tabindex="-1" aria-labelledby="transferencia"
        aria-hidden="true">
        <div class="modal-dialog">
            @if ($productions)
                <div class="modal-content edp-bg-gray">
                    <div class="modal-header edp-bg-sprucegreen-100 edp-text-verde-dark">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Prioridade
                            {{ $productions && $productions->count() > 1 ? 'PRIORIDADE EM MASSA ' . $productions->count() . ' NOTAS/OVS' : $productions[0]->load('Note')->Note->note }}
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h4 class="text-center">Definir Prioridade de Produção.</h4>
                        <p
                            class="text-justify p-2 border border-1 border-secondary edp-bg-sprucegreen-100 edp-text-verde-dark my-2">
                            Descreva de forma clara e objetiva a motivação para priorização da NOTA/OV. Essa informação
                            estará disponível somente para essa produção
                            enquanto esta se mantiver ativa, exceto quando a priorização for global. Todas as
                            priorizações ficarão registradas para as NOTA/OV e suas Produções.
                        </p>
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Motivo: <span
                                    class="text-danger fw-bold">*</span></label>
                            <textarea type="text" class="form-control" wire:model.defer="priority" placeholder="Informe o motivo" rows="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" wire:click="givePriority"
                            wire:loading.attr="disabled">
                            <span wire:loading class="spinner-border spinner-border-sm" role="status"
                                aria-hidden="true"></span>
                            <span wire:loading.remove>
                                Priorizar
                            </span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div wire:ignore.self class="modal fade priority-info-modal" id="infoPrioridade" tabindex="-1" aria-labelledby="info_prioridade_label"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="priority-info-modal__header">
                    <h1 class="priority-info-modal__title" id="info_prioridade_label">
                        Prioridade <span data-priority-info-note></span>
                    </h1>
                    <button type="button" class="priority-info-modal__close" data-bs-dismiss="modal" aria-label="Fechar">
                        <i class="ri-close-line"></i>
                    </button>
                </div>

                <div class="priority-info-modal__body">
                    <p class="priority-info-modal__reason" data-priority-info-reason>---</p>
                    <div class="priority-info-modal__meta">
                        <div class="priority-info-modal__meta-item">
                            <i class="ri-user-line"></i>
                            <span data-priority-info-user>---</span>
                        </div>
                        <div class="priority-info-modal__meta-item">
                            <i class="ri-calendar-line"></i>
                            <span data-priority-info-date>--:--</span>
                        </div>
                    </div>
                </div>

                <div class="priority-info-modal__footer d-flex justify-content-end">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line"></i>
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

@once
    <script>
        window.addEventListener('priorityInfoLoaded', function(e) {
            const modalEl = document.getElementById(e.detail.id);

            if (!modalEl) {
                return;
            }

            const noteEl = modalEl.querySelector('[data-priority-info-note]');
            const reasonEl = modalEl.querySelector('[data-priority-info-reason]');
            const userEl = modalEl.querySelector('[data-priority-info-user]');
            const dateEl = modalEl.querySelector('[data-priority-info-date]');

            if (noteEl) {
                noteEl.textContent = e.detail.note ? `- ${e.detail.note}` : '';
            }

            if (reasonEl) {
                reasonEl.textContent = e.detail.reason || '---';
            }

            if (userEl) {
                userEl.textContent = e.detail.user || '---';
            }

            if (dateEl) {
                dateEl.textContent = e.detail.created_at || '--:--';
            }

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    </script>
@endonce
