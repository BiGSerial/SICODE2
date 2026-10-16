<div class="col-12 col-md-3">
    <label class="form-label fw-semibold">Tipo</label>
    <select class="form-select" wire:model="type" aria-label="Filtrar por tipo">
        <option value="all">Todos (P/M/F)</option>
        <option value="P">P — Passivo</option>
        <option value="M">M — Meta atual</option>
        <option value="F">F — Meta futura</option>
    </select>
</div>
<div class="col-12 col-md-3">
    <label class="form-label fw-semibold">Busca</label>
    <input type="search" class="form-control" wire:model.debounce.400ms="search" placeholder="Ordem ou Nota" aria-label="Buscar por Ordem ou Nota">
</div>
