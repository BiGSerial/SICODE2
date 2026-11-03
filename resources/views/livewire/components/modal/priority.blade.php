@php
    use Carbon\Carbon;
@endphp
<div>
{{-- Uma raiz só: <style>/<script> fora dela quebravam o morph do Livewire (o modal abria vazio). --}}
    <div wire:ignore.self class="modal fade ops-modal" id="priorityModal" tabindex="-1" aria-labelledby="priorityModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            @if ($productions)
                <div class="modal-content closure-dash ops-list">
                    <div class="modal-header">
                        <h1 class="modal-title" id="priorityModalLabel">
                            <span class="modal-eyebrow">Definir prioridade</span>
                            {{ $productions->count() > 1 ? 'Prioridade em massa · ' . $productions->count() . ' notas/OVs' : $productions[0]->load('Note')->Note->note }}
                        </h1>
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="ops-modal-note">
                            Descreva de forma clara e objetiva o motivo da priorização. A informação fica disponível somente para esta produção enquanto ela estiver ativa, exceto quando a priorização for global. Todas as priorizações ficam registradas para a Nota/OV e suas produções.
                        </div>
                        <label for="priorityReason" class="form-label">Motivo <span class="text-danger">*</span></label>
                        <textarea id="priorityReason" class="form-control" wire:model.defer="priority" placeholder="Informe o motivo" rows="5"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="button" class="btn btn-primary" wire:click="givePriority" wire:loading.attr="disabled" wire:target="givePriority">
                            <span wire:loading wire:target="givePriority" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            <span wire:loading.remove wire:target="givePriority"><i class="ri-alert-fill" aria-hidden="true"></i> Priorizar</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div wire:ignore.self class="modal fade ops-modal" id="infoPrioridade" tabindex="-1" aria-labelledby="info_prioridade_label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content closure-dash ops-list">
                <div class="modal-header">
                    <h1 class="modal-title" id="info_prioridade_label">
                        <span class="modal-eyebrow">Prioridade</span>
                        <span data-priority-info-note></span>
                    </h1>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="ops-modal-card tone-red">
                        <div class="ops-modal-card-head"><i class="ri-alert-fill" aria-hidden="true"></i> Motivo da prioridade</div>
                        <div class="ops-modal-card-body" data-priority-info-reason>---</div>
                        <div class="ops-modal-meta">
                            <div><i class="ri-user-line" aria-hidden="true"></i><span data-priority-info-user>---</span></div>
                            <div><i class="ri-calendar-line" aria-hidden="true"></i><span data-priority-info-date>--:--</span></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
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
                noteEl.textContent = e.detail.note || '';
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
</div>
