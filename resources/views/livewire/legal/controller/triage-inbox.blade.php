<div>
    {{-- Cabeçalho --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--legal-primary)">
                <i class="bi bi-inbox-fill me-2"></i>Inbox de Triagem
            </h4>
            <div class="d-flex gap-2">
                <span class="badge bg-danger">{{ $newCount }} novas</span>
                <span class="badge bg-warning text-dark">{{ $triageCount }} em triagem</span>
            </div>
        </div>
    </div>

    {{-- Último Batch --}}
    @if($lastBatch)
        @php
            $batchAge = \Carbon\Carbon::parse($lastBatch->created_at)->diffInHours(now());
        @endphp
        <div class="card border-0 shadow-sm mb-4 {{ $batchAge > 24 ? 'border-warning border' : '' }}">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-cloud-download fs-4 text-muted"></i>
                    <div>
                        <div class="fw-semibold small">
                            Batch #{{ $lastBatch->id }} — {{ \Carbon\Carbon::parse($lastBatch->created_at)->format('d/m/Y H:i') }}
                        </div>
                        <div class="small text-muted">
                            Novas: {{ $lastBatch->new_count ?? '—' }}
                            &bull; Atualizadas: {{ $lastBatch->updated_count ?? '—' }}
                            &bull; Desaparecidas: {{ $lastBatch->removed_count ?? '—' }}
                        </div>
                    </div>
                    @if($batchAge > 24)
                        <span class="badge bg-warning text-dark ms-auto">
                            <i class="bi bi-exclamation-triangle me-1"></i>Importação há {{ $batchAge }}h — verifique o agendador
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="row g-2 mb-4">
        <div class="col-12 col-lg-5">
            <input type="text" class="form-control" placeholder="Buscar processo, empresa, assunto, parte adversa..."
                   wire:model.debounce.400ms="search" />
        </div>
        <div class="col-6 col-lg-2">
            <select class="form-select" wire:model="sourceTypeFilter">
                <option value="">Tipo</option>
                <option value="injunction">Liminar</option>
                <option value="sentence">Sentença</option>
                <option value="subsidy">Subsídio</option>
            </select>
        </div>
        <div class="col-6 col-lg-3">
            <input type="text" class="form-control" placeholder="Área de origem..." wire:model.debounce.400ms="originAreaFilter" />
        </div>
    </div>

    {{-- Lista de Cards --}}
    @forelse($demands as $demand)
        @php
            $isOverdue  = $demand->source_due_at && \Carbon\Carbon::parse($demand->source_due_at)->isPast();
            $isReturned = false;
            $cardClass  = $isOverdue ? 'legal-card--overdue' : '';
        @endphp
        <div class="legal-card {{ $cardClass }}">
            {{-- Badge identidade pendente --}}
            @if($demand->needs_identity_review)
                <div class="alert alert-warning py-1 px-2 small mb-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Revisão de identidade necessária — o sistema não conseguiu vincular com certeza a um caso existente.
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex gap-2">
                    <span class="badge bg-secondary">{{ $demand->source_type }}</span>
                    <x-legal.due-date-chip :date="$demand->source_due_at" />
                </div>
                <x-legal.status-badge :status="$demand->internal_status" />
            </div>

            <div class="fw-semibold mb-1">
                Nº {{ $demand->source_case_number ?? $demand->source_process_number ?? 'S/N' }}
            </div>
            <div class="small text-muted mb-1">
                Empresa: {{ $demand->legalCase?->company_name ?? '—' }}
            </div>
            @if($demand->subject)
                <div class="small mb-1">
                    {{ Str::limit($demand->subject, 80) }}
                </div>
            @endif
            @if($demand->opposing_party)
                <div class="small text-muted mb-1">
                    Parte Adversa: {{ $demand->opposing_party }}
                </div>
            @endif
            <div class="small text-muted">
                Área destino: {{ $demand->origin_area_name ?? '—' }}
                @if($demand->process_manager)
                    &bull; Gestor: {{ $demand->process_manager }}
                @endif
            </div>
            @if($demand->first_seen_at)
                <div class="small text-muted">
                    Primeira vez: {{ \Carbon\Carbon::parse($demand->first_seen_at)->format('d/m/Y') }}
                    @if($demand->lastSeenImportBatch)
                        (batch #{{ $demand->lastSeenImportBatch->id }})
                    @endif
                </div>
            @endif

            <hr class="my-2">

            {{-- Confirmação de ignorar inline --}}
            @if($confirmIgnoreId === $demand->id)
                <div class="p-2 border rounded bg-light">
                    <div class="small fw-semibold mb-1">Confirmar Ignorar?</div>
                    <div class="small text-muted mb-2">Esta demanda será marcada como IGNORADA e removida da triagem.</div>
                    <textarea class="form-control form-control-sm mb-2" rows="2" wire:model="confirmIgnoreReason"
                              placeholder="Motivo (obrigatório)..."></textarea>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-secondary flex-fill" wire:click="cancelIgnore">Cancelar</button>
                        <button class="btn btn-sm btn-danger flex-fill" wire:click="confirmIgnore">Confirmar Ignorar</button>
                    </div>
                </div>
            @else
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('legal.demand.detail', $demand->uuid) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>Ver Detalhes
                    </a>
                    <button class="btn btn-sm btn-warning" wire:click="startTriage({{ $demand->id }})">
                        <i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem
                    </button>
                    <button class="btn btn-sm btn-outline-danger" wire:click="showIgnoreConfirm({{ $demand->id }})">
                        <i class="bi bi-x me-1"></i>Ignorar
                    </button>
                </div>
            @endif
        </div>
    @empty
        <x-legal.empty-state icon="bi-check-circle" message="Nenhuma demanda aguardando triagem."
                             subtext="Todas as demandas foram processadas." />
    @endforelse

    {{-- Carregar mais --}}
    @if($loadedCount < $totalCount)
        <div class="text-center mt-3">
            <button class="btn btn-outline-secondary" wire:click="loadMore">
                <i class="bi bi-arrow-down me-1"></i>Carregar mais {{ min(25, $totalCount - $loadedCount) }}
                <span class="text-muted small">({{ $totalCount - $loadedCount }} restantes)</span>
            </button>
        </div>
    @endif

    <x-show-loading />
</div>
