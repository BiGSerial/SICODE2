{{-- Ações em massa do Controle da atividade padrão (mesmo menu no painel e na barra de seleção). `$drop`: direção do menu (dropdown|dropup). --}}
<div class="{{ $drop ?? 'dropdown' }} ops-mass">
    <button class="btn btn-sm {{ $btn ?? 'btn-primary' }} btn-dash dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="ri-list-settings-line" aria-hidden="true"></i> Ações em massa
    </button>
    <ul class="dropdown-menu dropdown-menu-end ops-mass-menu">
        <li>
            <a class="dropdown-item" href="#" wire:click.prevent="go_att_mass">
                <i class="ri-user-add-line text-primary" aria-hidden="true"></i>
                <span><strong>Atribuir em massa</strong><small>Define a empresa e o responsável das selecionadas de uma vez, mesmo de quem já tem.</small></span>
            </a>
        </li>
    </ul>
</div>
