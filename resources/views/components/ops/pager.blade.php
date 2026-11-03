@props([
    'paginator',
    'perPage' => null,
    'sizes'   => [25, 50, 100, 250, 500],
])

{{-- Rodapé/cabeçalho de paginação da lista: links, "Exibindo X–Y de Z" e quantidade por página (`wire:model="perPage"`). --}}
<div class="ops-pager">
    <div class="ops-pager-links">{{ $paginator->links() }}</div>
    <div class="ops-pager-info">
        @if ($paginator->total())
            Exibindo <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> de <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong>
        @else
            Nenhum registro
        @endif
        @if ($perPage !== null)
            <label class="ops-pager-size">
                <span class="visually-hidden">Registros por página</span>
                <select class="form-select form-select-sm" wire:model="perPage" aria-label="Registros por página">
                    @foreach ($sizes as $size)
                        <option value="{{ $size }}">{{ $size }} / pág.</option>
                    @endforeach
                </select>
            </label>
        @endif
    </div>
</div>
