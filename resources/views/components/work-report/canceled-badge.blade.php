@props(['report' => null])

@if ($report)
    <button type="button" class="badge text-bg-danger border-0 d-inline-flex align-items-center gap-1"
        title="Informe cancelado: não publique nem execute. Clique para ver quem, quando e por quê."
        wire:click.prevent.stop="$emitTo('components.workform.canceled-info', 'openCanceledInfo', {{ (int) $report['id'] }})">
        <i class="ri-close-circle-line"></i> INFORME CANCELADO
    </button>
@endif
