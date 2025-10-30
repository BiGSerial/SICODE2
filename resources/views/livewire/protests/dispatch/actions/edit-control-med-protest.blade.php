@push('css')
    <style>
        .modal-header-modern {
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
            color: #fff;
            border-radius: 18px 18px 0 0;
            padding: 1.8rem 2rem 1.2rem 2rem;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border: none;
        }

        .modal-header-content {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            min-width: 0;
        }

        .modal-header-icon {
            font-size: 2.7rem;
            width: 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.14);
            border-radius: 14px;
            flex-shrink: 0;
        }

        .modal-header-texts {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.18rem;
        }

        .modal-header-title {
            font-size: 1.30rem;
            font-weight: 700;
            line-height: 1.2;
            white-space: break-spaces;
            word-break: break-word;
            letter-spacing: 0.5px;
        }

        .modal-header-desc {
            font-size: .98rem;
            color: rgba(255, 255, 255, 0.88);
            font-weight: 400;
            white-space: break-spaces;
            word-break: break-word;
            margin-top: 2px;
        }

        .btn-close-modern {
            filter: invert(1) grayscale(.3);
            opacity: .85;
            width: 38px;
            height: 38px;
            margin: -0.8rem -0.8rem 0 1.1rem;
            background: transparent;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .18s;
        }

        .btn-close-modern:hover,
        .btn-close-modern:focus {
            background: rgba(255, 255, 255, 0.14);
            opacity: 1;
        }

        .modern-card {
            border: 0;
            border-radius: 16px;
            background-color: #fff;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, .5);
        }

        .modern-card-body {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
        }

        .modern-card-title {
            font-weight: 600;
            font-size: .95rem;
            letter-spacing: .02em;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .badge-status {
            font-size: .7rem;
            line-height: 1rem;
            font-weight: 600;
            padding: .4rem .6rem;
            border-radius: .5rem;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }

        .comments-container {
            max-height: 220px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        @media (max-width: 600px) {
            .modal-header-modern {
                padding: 1.2rem 0.9rem 1rem 1rem;
            }

            .modal-header-title {
                font-size: 1.03rem;
            }

            .modal-header-icon {
                font-size: 1.4rem;
                width: 33px;
                height: 33px;
            }
        }
    </style>
@endpush

<div wire:ignore.self class="modal fade" id="editProtestJobModal" tabindex="-1" aria-labelledby="editProtestJobModalLabel"
    aria-hidden="true">

    <x-show-loading />

    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">

            {{-- HEADER --}}
            <div class="modal-header-modern">
                <div class="modal-header-content">
                    <div class="modal-header-icon">
                        <i class="ri-tools-fill"></i>
                    </div>
                    <div class="modal-header-texts">
                        <span class="modal-header-title">
                            Editar Atividade
                            @if ($job)
                                <strong>#{{ $job->id }}</strong>
                            @endif
                        </span>

                        <span class="modal-header-desc">
                            Ajuste responsável, prioridade, SLA, flags, status e feche/reabra a atividade.
                        </span>
                    </div>
                </div>

                <button type="button" class="btn-close btn-close-modern" aria-label="Fechar" data-bs-dismiss="modal"
                    wire:click="closeEditor"></button>
            </div>

            {{-- BODY --}}
            <div class="modal-body p-4">

                @if ($job)
                    {{-- STATUS RESUMO --}}
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="modern-card">
                                <div class="modern-card-body">
                                    <div class="modern-card-title">
                                        <i class="ri-information-line me-2"></i>Resumo da Atividade
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Status Atual:</span>
                                                <span class="fw-bold">
                                                    <span
                                                        class="{{ $job->status_badge_class ?? 'badge bg-secondary' }}">
                                                        {{ $job->status_label }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Prioridade:</span>
                                                <span class="fw-bold">
                                                    <span
                                                        class="{{ $job->priority_badge_class ?? 'badge bg-secondary' }}">
                                                        {{ $job->priority_label }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Responsável:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->owner?->name ?? '—' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Criado por:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->creator?->name ?? '—' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">SLA:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->sla_due_at?->format('d/m/Y H:i') ?? '—' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex flex-column">
                                                <span class="text-muted small">Medida:</span>
                                                <span class="fw-bold text-dark">
                                                    {{ $job->medProtest?->protest?->nota }}#{{ $job->medProtest?->id }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <span class="text-muted small d-block">Descrição / Instrução atual:</span>
                                            <span class="fw-medium small text-dark">
                                                {{ $job->notes ?: '—' }}
                                            </span>
                                        </div>
                                    </div> {{-- /row g-3 --}}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FORM DE EDIÇÃO --}}
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="modern-card">
                                <div class="modern-card-body">

                                    <div class="modern-card-title">
                                        <i class="ri-edit-box-line me-2"></i>Ajustes da Atividade
                                    </div>

                                    <div class="row g-3">

                                        {{-- Responsável --}}
                                        <div class="col-md-6">
                                            <div class="form-floating position-relative">
                                                <select class="form-select" id="editOwner" wire:model="owner_id">
                                                    <option value="">Selecione o responsável</option>
                                                    @foreach ($userList as $u)
                                                        <option value="{{ $u->id }}">
                                                            {{ mb_strtoupper($u->name) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label for="editOwner">Responsável</label>
                                            </div>
                                            @error('owner_id')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror

                                            {{-- campo busca rápida --}}
                                            <div class="mt-2">
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ri-search-line"></i>
                                                    </span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Buscar usuário..."
                                                        wire:model.debounce.300ms="userSearch">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Prioridade --}}
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="editPriority" wire:model="priority">
                                                    @foreach ($priorityOptions as $opt)
                                                        <option value="{{ $opt->value }}">
                                                            {{ $opt->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label for="editPriority">Prioridade</label>
                                            </div>
                                            @error('priority')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        {{-- Flags --}}
                                        <div class="col-md-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox" id="editAdvanceToggle"
                                                    wire:model="is_advance">
                                                <label class="form-check-label fw-medium text-info"
                                                    for="editAdvanceToggle">
                                                    <i class="ri-road-map-line me-1"></i>Avanço Parceiro
                                                </label>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-check form-switch mt-4">
                                                <input class="form-check-input" type="checkbox"
                                                    id="editEvidenceToggle" wire:model="need_evidence">
                                                <label class="form-check-label fw-medium text-warning"
                                                    for="editEvidenceToggle">
                                                    <i class="ri-camera-line me-1"></i>Evidência obrigatória
                                                </label>
                                            </div>
                                        </div>

                                        {{-- SLA --}}
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="datetime-local" class="form-control" id="editSlaDue"
                                                    wire:model="sla_due_at">
                                                <label for="editSlaDue">Retorno até (SLA)</label>
                                            </div>
                                            @error('sla_due_at')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                        {{-- Notas / instrução --}}
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <textarea class="form-control" style="height: 90px" id="editNotes" wire:model="notes"
                                                    placeholder="Atualize instruções ao responsável"></textarea>
                                                <label for="editNotes">Instruções / Observações</label>
                                            </div>
                                            @error('notes')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
                                        </div>

                                    </div>{{-- /row g-3 --}}

                                    {{-- AÇÕES GERAIS DO JOB: salvar config / mudar status --}}
                                    <div class="row g-3 mt-4">
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-success w-100 py-3"
                                                wire:click="saveJob">
                                                <i class="ri-save-3-fill me-2"></i>
                                                Salvar Alterações
                                            </button>
                                        </div>

                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-warning w-100 py-3 text-dark"
                                                wire:click="reopenJob"
                                                @if (!$job || !in_array($job->status->value, ['done', 'canceled'])) disabled @endif>
                                                <i class="ri-restart-line me-2"></i>
                                                Reabrir
                                            </button>
                                        </div>

                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger w-100 py-3"
                                                wire:click="finishJob"
                                                @if (!$job || $job->status->value === 'done') disabled @endif>
                                                <i class="ri-check-double-line me-2"></i>
                                                Concluir
                                            </button>
                                        </div>

                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-outline-danger w-100 py-3"
                                                wire:click="cancelJob"
                                                @if (!$job || $job->status->value === 'canceled') disabled @endif>
                                                <i class="ri-close-circle-line me-2"></i>
                                                Cancelar Atividade
                                            </button>
                                        </div>
                                    </div>{{-- /row g-3 mt-4 --}}

                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        Nenhuma atividade carregada.
                    </div>
                @endif

                {{-- FECHAR MODAL --}}
                <div class="row g-3">
                    <div class="col-12">
                        <div class="modern-card">
                            <div class="modern-card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-outline-secondary w-100 py-3"
                                            data-bs-dismiss="modal" wire:click="closeEditor">
                                            <i class="ri-arrow-go-back-line me-2"></i>
                                            Fechar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>{{-- /row g-3 --}}

            </div>{{-- /modal-body --}}
        </div>
    </div>
</div>
