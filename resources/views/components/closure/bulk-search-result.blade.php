@props([
    'report' => null,
])

{{-- Resumo da busca em massa ativa: quantas foram encontradas e quais não casaram (com a lista completa para copiar). Sem busca, não renderiza. --}}
@if ($report)
    @php $missing = $report['missing']; @endphp
    <div {{ $attributes->class(['bulk-result', 'tone-' . (count($missing) === 0 ? 'green' : 'amber')]) }} role="status" wire:key="bulk-result">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <i class="{{ count($missing) === 0 ? 'ri-checkbox-circle-line' : 'ri-error-warning-line' }}" aria-hidden="true"></i>
            <strong>Busca em massa:</strong>
            <span>{{ $report['found'] }} de {{ $report['total'] }} encontrada(s) com os filtros atuais.</span>
            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 ms-auto" wire:click="openBulkSearch"><i class="ri-edit-line"></i> Editar lista</button>
            <button type="button" class="btn btn-link btn-sm text-decoration-none text-danger p-0" wire:click="clearBulkSearch"><i class="ri-close-circle-line"></i> Limpar</button>
        </div>
        @if (count($missing))
            <details class="mt-1">
                <summary class="small fw-semibold">{{ count($missing) }} não encontrada(s)</summary>
                <div class="bulk-missing small mt-1">{{ implode('  ·  ', $missing) }}</div>
                <button type="button" class="btn btn-outline-secondary btn-sm btn-dash mt-1"
                    @click="navigator.clipboard.writeText(@js(implode("\n", $missing))).then(() => $dispatch('closure:toast', { message: 'Lista copiada.', type: 'success' }))">
                    <i class="ri-file-copy-line"></i> Copiar não encontradas
                </button>
            </details>
        @endif
    </div>
@endif
