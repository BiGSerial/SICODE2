<div>
    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('legal.field.queue') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar para Minhas Atribuições
        </a>
        <div class="flex-grow-1">
            <h5 class="mb-0 fw-bold" style="color: var(--legal-primary)">
                {{ $demand->source_case_number ?? $demand->source_process_number ?? 'S/N' }}
                — {{ $demand->legalCase?->company_name ?? '' }}
            </h5>
            <div class="d-flex gap-2 mt-1">
                <span class="badge bg-secondary">{{ $demand->source_type }}</span>
                <x-legal.due-date-chip :date="$demand->source_due_at" />
                <x-legal.status-badge :status="$demand->internal_status" />
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Coluna Esquerda (col-7) --}}
        <div class="col-lg-7">

            {{-- Dados da Demanda --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-file-text me-2"></i>Dados da Demanda
                </div>
                <div class="card-body">
                    <dl class="row g-2 small mb-0">
                        <dt class="col-sm-4 text-muted">Processo</dt>
                        <dd class="col-sm-8">{{ $demand->source_process_number ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Tipo</dt>
                        <dd class="col-sm-8">{{ $demand->source_type }}</dd>
                        <dt class="col-sm-4 text-muted">Empresa</dt>
                        <dd class="col-sm-8">{{ $demand->legalCase?->company_name ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Área Responsável</dt>
                        <dd class="col-sm-8">{{ $demand->responsible_area_name ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Parte Adversa</dt>
                        <dd class="col-sm-8">{{ $demand->opposing_party ?? '—' }}</dd>
                        <dt class="col-sm-4 text-muted">Prazo Judicial</dt>
                        <dd class="col-sm-8"><x-legal.due-date-chip :date="$demand->source_due_at" /></dd>
                    </dl>
                    @if($demand->subject)
                        <hr class="my-2">
                        <div class="fw-semibold small">{{ $demand->subject }}</div>
                    @endif
                    @if($demand->description)
                        <p class="small mt-2 mb-0">{{ $demand->description }}</p>
                    @endif
                </div>
            </div>

            {{-- Solicitação do Controlador --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-megaphone me-2"></i>Solicitação do Controlador
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-1">
                        Enviado por: <strong>{{ $assignment->sentBy?->name ?? '—' }}</strong>
                        em {{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}
                    </div>
                    @if($assignment->due_at)
                        <div class="small mb-2">
                            Prazo para resposta: <x-legal.due-date-chip :date="$assignment->due_at" />
                        </div>
                    @endif
                    @if($assignment->message)
                        <div class="border-start border-primary border-3 ps-3 mt-2">
                            <p class="mb-0">{{ $assignment->message }}</p>
                        </div>
                    @endif

                    {{-- Resposta anterior (caso de devolução) --}}
                    @php
                        $assignStatus = $assignment->status instanceof \BackedEnum ? $assignment->status->value : $assignment->status;
                    @endphp
                    @if($assignStatus === 'returned_for_correction' && $assignment->response_summary)
                        <hr>
                        <div class="alert alert-danger small">
                            <div class="fw-semibold mb-1">Sua resposta anterior:</div>
                            <p class="mb-2">{{ $assignment->response_summary }}</p>
                            @if($assignment->correction_note)
                                <div class="fw-semibold text-danger">Motivo da devolução:</div>
                                <p class="mb-0">"{{ $assignment->correction_note }}"</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Arquivos Compartilhados --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-paperclip me-2"></i>Documentos da Demanda
                </div>
                <div class="card-body">
                    @forelse($sharedFiles as $file)
                        <div class="d-flex align-items-center gap-2 mb-1 small">
                            <i class="bi bi-file-earmark"></i>
                            <span class="flex-grow-1">{{ $file->original_name ?? basename($file->file_path) }}</span>
                            <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-link btn-sm p-0">
                                <i class="bi bi-download"></i> Baixar
                            </a>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Nenhum documento foi compartilhado para esta tarefa.</p>
                    @endforelse
                </div>
            </div>

            {{-- Histórico desta Atribuição --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-clock-history me-2"></i>Histórico desta Atribuição
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="mb-2">
                            <i class="bi bi-check text-success me-2"></i>
                            {{ \Carbon\Carbon::parse($assignment->created_at)->format('d/m/Y H:i') }}
                            — {{ $assignment->sentBy?->name ?? 'Controlador' }} enviou a atribuição
                        </div>
                        @if($assignment->received_at)
                            <div class="mb-2">
                                <i class="bi bi-check text-success me-2"></i>
                                {{ \Carbon\Carbon::parse($assignment->received_at)->format('d/m/Y H:i') }}
                                — Você confirmou o recebimento
                            </div>
                        @endif
                        @if(!in_array($assignStatus, ['answered', 'returned_to_controller']))
                            <div class="text-muted">
                                <i class="bi bi-hourglass me-2"></i>
                                Aguardando sua resposta...
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Coluna Direita (col-5) — Formulário de Resposta --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm legal-action-panel">
                <div class="card-header fw-semibold" style="background: var(--legal-primary); color: white;">
                    <i class="bi bi-pencil-square me-2"></i>Seu Parecer
                </div>
                <div class="card-body">

                    @if(in_array($assignStatus, ['answered', 'returned_to_controller']))
                        <div class="alert alert-success small">
                            <i class="bi bi-check-circle me-1"></i>
                            Resposta já enviada. Aguardando revisão do controlador.
                        </div>
                        @if($assignment->response_summary)
                            <blockquote class="blockquote small">{{ $assignment->response_summary }}</blockquote>
                        @endif
                    @else
                        {{-- Toggle tipo de resposta --}}
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" wire:model="isImpossibility" value="0" id="rNormal" />
                                <label class="form-check-label" for="rNormal">Enviar Parecer / Evidências</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" wire:model="isImpossibility" value="1" id="rImposs" />
                                <label class="form-check-label" for="rImposs">Impossibilidade de Atendimento</label>
                            </div>
                        </div>

                        @if(!$isImpossibility)
                            {{-- Formulário A: Parecer Normal --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Resumo da resposta *</label>
                                <textarea class="form-control" rows="6" wire:model="responseSummary"
                                          placeholder="Descreva sua resposta detalhadamente (mín. 20 caracteres)"></textarea>
                                <div class="form-text small text-muted">
                                    "Seu texto é salvo automaticamente enquanto você digita"
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" wire:model="hasEvidence" id="hasEv" />
                                    <label class="form-check-label small" for="hasEv">Estou enviando arquivos de evidência</label>
                                </div>
                                @if($hasEvidence)
                                    <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple />
                                    @foreach($uploadFiles as $i => $file)
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-file-earmark me-1"></i>{{ $file->getClientOriginalName() }}
                                            ({{ round($file->getSize() / 1024) }} KB)
                                        </div>
                                    @endforeach
                                    <div class="form-text small">Múltiplos arquivos. Máx. 10MB cada.</div>
                                @endif
                            </div>
                        @else
                            {{-- Formulário B: Impossibilidade --}}
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Motivo da impossibilidade *</label>
                                <textarea class="form-control" rows="5" wire:model="impossibilityReason"
                                          placeholder="Descreva por que não é possível atender (mín. 20 caracteres)"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Documentação de suporte (opcional)</label>
                                <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple />
                            </div>
                        @endif

                        {{-- Modal de Confirmação --}}
                        @if($confirmingSend)
                            <div class="alert alert-warning mb-3">
                                <div class="fw-semibold mb-2">Confirmar Envio?</div>
                                <div class="small mb-1">Processo: {{ $demand->source_case_number }}</div>
                                @if(!$isImpossibility)
                                    <div class="small mb-1">Resumo: "{{ Str::limit($responseSummary, 100) }}"</div>
                                    @if(!empty($uploadFiles))
                                        <div class="small mb-1">Arquivos: {{ count($uploadFiles) }} arquivo(s)</div>
                                    @endif
                                @else
                                    <div class="small mb-1">Tipo: Impossibilidade de atendimento</div>
                                @endif
                                <div class="small text-muted mt-2">
                                    Após o envio, você não poderá editar esta resposta.
                                </div>
                                <div class="d-flex gap-2 mt-3">
                                    <button class="btn btn-sm btn-secondary flex-fill" wire:click="cancelConfirm">Cancelar</button>
                                    <button class="btn btn-sm btn-success flex-fill" wire:click="submitResponse">Confirmar e Enviar</button>
                                </div>
                            </div>
                        @else
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary flex-fill btn-sm" wire:click="saveDraft">
                                    Salvar Rascunho
                                </button>
                                <button class="btn btn-primary flex-fill btn-sm" wire:click="startConfirm">
                                    <i class="bi bi-send me-1"></i>Enviar Resposta
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-show-loading />
</div>
