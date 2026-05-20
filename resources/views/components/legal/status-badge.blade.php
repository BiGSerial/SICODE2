@props([
    'status',
    'size' => 'sm',
])
@php
    $value = $status instanceof \App\Enum\LegalDemandInternalStatus ? $status->value : $status;
    $map = [
        'new_imported'              => ['label' => 'Nova',             'class' => 'bg-info text-white'],
        'triage'                    => ['label' => 'Triagem',          'class' => 'bg-warning text-dark'],
        'waiting_controller_action' => ['label' => 'Aguard. Controle','class' => 'bg-warning text-dark'],
        'sent_to_field'             => ['label' => 'Em Campo',         'class' => 'bg-primary text-white'],
        'field_received'            => ['label' => 'Recebido',         'class' => 'bg-primary text-white'],
        'waiting_field_response'    => ['label' => 'Aguard. Resposta', 'class' => 'bg-primary text-white'],
        'returned_by_field'         => ['label' => 'Retornado',        'class' => 'badge-legal-accent'],
        'under_controller_review'   => ['label' => 'Em Revisão',       'class' => 'badge-legal-accent'],
        'returned_for_correction'   => ['label' => 'Devolução',        'class' => 'bg-danger bg-opacity-10 text-danger border border-danger'],
        'ready_to_close_external'   => ['label' => 'Pronto Fechar',    'class' => 'bg-success bg-opacity-10 text-success border border-success'],
        'closed_internal'           => ['label' => 'Fechado SICODE',   'class' => 'bg-secondary text-white'],
        'closed_external'           => ['label' => 'Fechado Ext.',     'class' => 'bg-success text-white'],
        'cancelled'                 => ['label' => 'Cancelado',        'class' => 'bg-dark text-white'],
        'ignored'                   => ['label' => 'Ignorado',         'class' => 'bg-light text-muted border'],
        'reopened'                  => ['label' => 'Reaberto',         'class' => 'bg-info text-white'],
    ];
    $config = $map[$value] ?? ['label' => $value, 'class' => 'bg-secondary text-white'];
    $sizeClass = match($size) {
        'lg'    => 'fs-6 px-3 py-2',
        'md'    => 'fs-7 px-2 py-1',
        default => 'fs-8 px-2 py-1',
    };
@endphp
<span class="badge rounded-pill {{ $config['class'] }} {{ $sizeClass }}">
    {{ $config['label'] }}
</span>
