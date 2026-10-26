@props([
    'terms' => [],
    'open'  => false,
])

{{-- Busca em massa: botão + modal para colar uma lista de Notas/Ordens (uma por linha, ou separadas por vírgula, ponto e vírgula ou espaço).
     O componente Livewire precisa do trait WithBulkSearch (métodos openBulkSearch, applyBulkSearch, clearBulkSearch, closeBulkSearch). --}}
<button type="button" class="btn btn-outline-secondary btn-sm btn-dash" wire:click="openBulkSearch" title="Colar uma lista de Notas ou Ordens e filtrar de uma vez">
    <i class="ri-list-check-2" aria-hidden="true"></i> Buscar em massa
    @if (count($terms))
        <span class="badge text-bg-success ms-1">{{ count($terms) }}</span>
    @endif
</button>

@if ($open)
    <div class="cpop-overlay" wire:click.self="closeBulkSearch" x-data="{ n: {{ count($terms) }}, count(t) { return new Set(t.split(/[\s,;]+/).filter(Boolean)).size; } }" x-init="$nextTick(() => $refs.bulk.focus())" @keydown.escape.window="$wire.closeBulkSearch()">
        <div class="cpop cpop-md tone-blue" role="dialog" aria-modal="true" aria-label="Buscar em massa">
            <div class="cpop-head">
                <span class="cpop-icon"><i class="ri-list-check-2"></i></span>
                <div>
                    <h3 class="cpop-title">Buscar em massa</h3>
                    <div class="cpop-sub">Cole as Notas ou Ordens que você quer encontrar de uma vez.</div>
                </div>
                <button type="button" class="cpop-close" aria-label="Fechar" wire:click="closeBulkSearch"><i class="ri-close-line"></i></button>
            </div>
            <div class="cpop-body">
                <label for="bulk-search-text" class="cpop-label">Notas e/ou Ordens</label>
                <textarea id="bulk-search-text" x-ref="bulk" class="form-control" rows="10" wire:model.defer="bulkText" @input="n = count($event.target.value)"
                    placeholder="Uma por linha, ou separadas por vírgula, ponto e vírgula ou espaço.&#10;Ex.:&#10;12558185&#10;150000000283, 150000000558"></textarea>
                <div class="d-flex justify-content-between small text-muted mt-1">
                    <span>Correspondência exata com o número da Nota <strong>ou</strong> da Ordem. Repetidos são ignorados.</span>
                    <span><strong x-text="n">0</strong> termo(s) · máx. {{ \App\Services\Closure\ClosureBulkSearch::MAX_TERMS }}</span>
                </div>
            </div>
            <div class="cpop-foot">
                @if (count($terms))
                    <button type="button" class="btn btn-link btn-sm text-danger text-decoration-none me-auto" wire:click="clearBulkSearch"><i class="ri-close-circle-line"></i> Limpar busca</button>
                @endif
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="closeBulkSearch">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm px-3 fw-semibold" wire:click="applyBulkSearch" wire:loading.attr="disabled" wire:target="applyBulkSearch"><i class="ri-search-line"></i> Buscar</button>
            </div>
        </div>
    </div>
@endif
