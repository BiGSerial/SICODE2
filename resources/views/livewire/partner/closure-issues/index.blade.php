<div>
    <x-show-loading />
    @include('livewire.partner.partials.company-filter')

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label mb-1">Buscar Nota ou Ordem</label>
                    <input class="form-control border-secondary" wire:model.debounce.500ms="search" placeholder="Nota ou Ordem">
                </div>
                <div>
                    <label class="form-label mb-1">Situação</label>
                    <select class="form-select border-secondary" wire:model="status">
                        <option value="">Todas</option>
                        <option value="REQUESTED">Aguardando resposta</option>
                        <option value="RETURNED">Devolvida para correção</option>
                        <option value="RESPONDED">Respondida</option>
                        <option value="VALIDATING">Em validação</option>
                        <option value="RESOLVED">Resolvida</option>
                    </select>
                </div>
                <div>
                    <label class="form-label mb-1">Categoria</label>
                    <select class="form-select border-secondary" wire:model="categoryId">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}{{ !$category->active ? ' (inativa)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Pendências de Encerramento</strong>
            <span class="text-muted small">Apenas demandas destinadas à sua empresa</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nota / Ordem</th>
                        <th>Classificação</th>
                        <th>Situação</th>
                        <th>Solicitada em</th>
                        <th>Último retorno</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($issues as $issue)
                        <tr wire:key="closure-issue-{{ $issue->id }}">
                            <td>
                                <strong>{{ $issue->Order?->Note?->note ?? '—' }}</strong><br>
                                <span class="text-muted">Ordem {{ $issue->Order?->ordem ?? '—' }}</span>
                            </td>
                            <td>
                                {{ $issue->Category?->name ?? '—' }}<br>
                                <span class="text-muted small">{{ $issue->Subcategory?->name ?? '—' }}</span>
                            </td>
                            <td><span class="badge text-bg-secondary">{{ $issue->status }}</span><br><small>Ciclo {{ $issue->cycle }}</small></td>
                            <td>{{ optional($issue->requested_at ?? $issue->opened_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ optional($issue->Events->last())->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                @if ($issue->Order?->note_id && \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'))
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('partner.closure.work.show', $issue->Order->note_id) }}">Abrir obra</a>
                                @endif
                                @if (in_array($issue->status, ['REQUESTED', 'RETURNED'], true) && \App\Services\PartnerAccess\PartnerAccessGate::allows(auth()->user(), 'closure.issues.respond'))
                                    <button class="btn btn-sm btn-primary" wire:click="startResponse({{ $issue->id }})">Responder</button>
                                @endif
                            </td>
                        </tr>
                        @if ($respondingIssueId === $issue->id)
                            <tr wire:key="closure-issue-response-{{ $issue->id }}">
                                <td colspan="6" class="bg-light">
                                    <label class="form-label">Resposta da parceira</label>
                                    <textarea class="form-control @error('responseMessage') is-invalid @enderror" rows="3" wire:model.defer="responseMessage" maxlength="5000"></textarea>
                                    @error('responseMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <div class="mt-2 d-flex gap-2">
                                        <button class="btn btn-sm btn-success" wire:click="submitResponse">Enviar resposta</button>
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="cancelResponse">Cancelar</button>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">Nenhuma pendência encontrada para os filtros selecionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($issues->hasPages())
            <div class="card-footer">{{ $issues->links() }}</div>
        @endif
    </div>
</div>
