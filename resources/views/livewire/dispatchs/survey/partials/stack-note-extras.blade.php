{{-- Selos extras sob a nota do Controle (prioridade e demanda jurídica). Usa $row e $legalNoteIds da view que inclui. --}}
@if ($row['priority'])
    <x-ops.badge sm tone="red" icon="ri-alert-fill" class="ops-clickable" label="Prioridade" hint="Prioritária – clique para ver o motivo"
        wire:click.prevent="$emitTo('components.modal.priority', 'infoPriority', {{ $row['id'] }})" />
@endif
@if (isset($legalNoteIds[$row['noteId']]))
    <x-legal.note-demand-tags :note-id="$row['noteId']" :row-key="'dispatchs-survey-' . $row['id']" />
@endif
