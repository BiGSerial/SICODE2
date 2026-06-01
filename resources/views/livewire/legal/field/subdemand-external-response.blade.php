<div class="container py-4">
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-dark text-white fw-semibold">Resposta Externa de Subdemanda</div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <div class="mb-2"><strong>Processo:</strong> {{ $subdemand->demand?->source_process_number ?? '—' }}</div>
            <div class="mb-2"><strong>Demanda:</strong> {{ $subdemand->demand?->source_subject ?? '—' }}</div>
            <div class="mb-2"><strong>Subdemanda:</strong> #{{ $subdemand->id }}</div>

            <div class="mb-2">
                <label class="form-label small fw-semibold">Seu nome *</label>
                <input type="text" class="form-control" wire:model="executorName">
            </div>

            <div class="mb-2">
                <label class="form-label small fw-semibold">Comentário</label>
                <textarea class="form-control" rows="4" wire:model="comment"></textarea>
            </div>

            <div class="mb-2">
                <label class="form-label small fw-semibold">Anexos</label>
                <input type="file" class="form-control" wire:model="uploadFiles" multiple>
            </div>

            <button class="btn btn-primary" wire:click="submit">Enviar retorno</button>
        </div>
    </div>
</div>
