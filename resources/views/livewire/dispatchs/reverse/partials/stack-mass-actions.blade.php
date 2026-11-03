{{-- Ações em massa do Controle do Fluxo Reverso (mesmo menu no painel e na barra de seleção). `$drop`: direção do menu (dropdown|dropup). --}}
<div class="{{ $drop ?? 'dropdown' }} ops-mass">
    <button class="btn btn-sm {{ $btn ?? 'btn-primary' }} btn-dash dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ri-list-settings-line" aria-hidden="true"></i> Ações em massa
    </button>
    <ul class="dropdown-menu dropdown-menu-end ops-mass-menu">
        <li>
            <a class="dropdown-item" href="#" wire:click.prevent="go_att_mass">
                <i class="ri-user-add-line text-primary" aria-hidden="true"></i>
                <span><strong>Atribuir em massa</strong><small>Troca o responsável, mesmo de quem já tem. Não vale para Finalizado nem Em pausa.</small></span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" wire:click.prevent="go_des_att_mass">
                <i class="ri-user-shared-line text-danger" aria-hidden="true"></i>
                <span><strong>Desatribuir em massa</strong><small>Devolve para a Lista para {{ $service->service }}. Não vale para Finalizado nem Em pausa.</small></span>
            </a>
            <label class="dropdown-item-text ops-mass-force">
                <input class="form-check-input" type="checkbox" wire:model.defer="forcar"> Forçar e ignorar a pausa
            </label>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="#" wire:click.prevent="go_priority_mass">
                <i class="ri-alert-fill text-danger" aria-hidden="true"></i>
                <span><strong>Priorizar em massa</strong><small>Um único motivo vale para todas as selecionadas.</small></span>
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="#" wire:click.prevent="go_des_priority_mass">
                <i class="ri-alert-line text-success" aria-hidden="true"></i>
                <span><strong>Despriorizar em massa</strong><small>Remove a prioridade das selecionadas.</small></span>
            </a>
        </li>
    </ul>
</div>
