<div>
    {{-- Header --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('legal.queue') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Voltar para Fila
        </a>
        <div class="flex-grow-1">
            <h5 class="mb-0 fw-bold" style="color: var(--legal-primary)">
                {{ $demand->source_case_number ?? $demand->source_process_number ?? 'Sem número' }}
            </h5>
            <div class="d-flex align-items-center gap-2 mt-1">
                <span class="badge bg-secondary">{{ $demand->source_type }}</span>
                <x-legal.due-date-chip :date="$demand->source_due_at" />
                <x-legal.status-badge :status="$demand->internal_status" size="md" />
            </div>
        </div>
    </div>

    {{-- Banner: Controlador diferente --}}
    @if($demand->controller_user_id && $demand->controller_user_id !== auth()->id())
        <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-info-circle-fill"></i>
            Esta demanda está sob responsabilidade de <strong>{{ $demand->controller?->name }}</strong>. Você pode agir mesmo assim.
        </div>
    @endif

    <div class="row g-4">
        {{-- Coluna Esquerda (col-8) --}}
        <div class="col-lg-8">

            {{-- Dados do Processo --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-file-text me-2"></i>Dados do Processo
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <dt class="small text-muted">Nº Processo</dt>
                            <dd>{{ $demand->source_process_number ?? '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Nº Caso</dt>
                            <dd>{{ $demand->source_case_number ?? '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Empresa</dt>
                            <dd>{{ $demand->legalCase?->company_name ?? '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Gestor do Processo</dt>
                            <dd>{{ $demand->process_manager ?? '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Parte Adversa</dt>
                            <dd>{{ $demand->opposing_party ?? '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Área de Origem</dt>
                            <dd>{{ $demand->origin_area_name ?? '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Área Responsável</dt>
                            <dd>{{ $demand->responsible_area_name ?? '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Prazo Judicial</dt>
                            <dd><x-legal.due-date-chip :date="$demand->source_due_at" /></dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Data de Início</dt>
                            <dd>{{ $demand->source_started_at ? \Carbon\Carbon::parse($demand->source_started_at)->format('d/m/Y') : '—' }}</dd>
                        </div>
                        <div class="col-md-4">
                            <dt class="small text-muted">Cidade / Regional</dt>
                            <dd>{{ $demand->city ?? '—' }} / {{ $demand->regional ?? '—' }}</dd>
                        </div>
                        <div class="col-12">
                            <dt class="small text-muted">Assunto</dt>
                            <dd class="fw-semibold">{{ $demand->subject ?? '—' }}</dd>
                        </div>
                        @if($demand->description)
                            <div class="col-12">
                                <dt class="small text-muted">Descrição</dt>
                                <dd x-data="{ expanded: false }">
                                    <span x-show="!expanded">{{ Str::limit($demand->description, 300) }}</span>
                                    <span x-show="expanded">{{ $demand->description }}</span>
                                    @if(strlen($demand->description) > 300)
                                        <button class="btn btn-link btn-sm p-0 ms-1" x-on:click="expanded = !expanded"
                                                x-text="expanded ? 'ver menos' : 'ver mais'"></button>
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Atribuição Atual --}}
            @if($currentAssignment)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-person-check me-2"></i>Atribuição Atual
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    <i class="bi bi-person-fill me-1"></i>
                                    {{ $currentAssignment->toUser?->name ?? 'Usuário não definido' }}
                                </div>
                                <div class="small text-muted">
                                    Enviado por {{ $currentAssignment->sentBy?->name ?? '—' }}
                                    em {{ \Carbon\Carbon::parse($currentAssignment->sent_at ?? $currentAssignment->created_at)->format('d/m/Y H:i') }}
                                </div>
                                <div class="mt-1">
                                    <x-legal.status-badge :status="$currentAssignment->status" />
                                </div>
                                @if($currentAssignment->message)
                                    <blockquote class="blockquote small mt-2 border-start border-2 ps-3">
                                        "{{ $currentAssignment->message }}"
                                    </blockquote>
                                @endif
                            </div>
                        </div>

                        @if($currentAssignment->response_summary)
                            <hr>
                            <div>
                                <div class="fw-semibold small text-muted mb-1">Resposta do Executante</div>
                                <p>{{ $currentAssignment->response_summary }}</p>
                                @if($currentAssignment->answered_at)
                                    <div class="small text-muted">
                                        Respondido em {{ \Carbon\Carbon::parse($currentAssignment->answered_at)->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Timeline de Eventos --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-clock-history me-2"></i>Histórico de Eventos
                </div>
                <div class="card-body">
                    <x-legal.demand-timeline :events="$demand->events" />
                </div>
            </div>

            {{-- Comentários --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-chat me-2"></i>Comentários
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <textarea class="form-control mb-2" rows="3" wire:model="newComment"
                                  placeholder="Adicionar comentário..."></textarea>
                        <div class="d-flex align-items-center gap-2">
                            <select class="form-select form-select-sm" style="width:200px" wire:model="commentVisibility">
                                <option value="controller">🔒 Interno (só controlador)</option>
                                <option value="shared">👁 Compartilhado (executante vê)</option>
                            </select>
                            <button class="btn btn-sm btn-primary" wire:click="addComment">Comentar</button>
                        </div>
                    </div>

                    @forelse($demand->comments->sortByDesc('created_at') as $comment)
                        <div class="d-flex gap-2 mb-3">
                            <div class="flex-grow-1 bg-light rounded p-2">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong class="small">{{ $comment->user?->name ?? '—' }}</strong>
                                    <span class="text-muted small">{{ \Carbon\Carbon::parse($comment->created_at)->format('d/m/Y H:i') }}</span>
                                    @if(($comment->visibility ?? 'controller') === 'controller')
                                        <span class="badge bg-secondary small">🔒 Interno</span>
                                    @else
                                        <span class="badge bg-info text-white small">👁 Compartilhado</span>
                                    @endif
                                </div>
                                <p class="mb-0 small">{{ $comment->comment }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">Nenhum comentário.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Coluna Direita (col-4) --}}
        <div class="col-lg-4">

            {{-- Painel de Ação --}}
            <div class="card border-0 shadow-sm mb-4 legal-action-panel">
                <div class="card-header fw-semibold" style="background: var(--legal-primary); color: white;">
                    <i class="bi bi-lightning-charge me-2"></i>Painel de Ação
                </div>
                <div class="card-body">
                    @switch($statusValue)
                        @case('new_imported')
                            <p class="text-muted small">Esta demanda ainda não entrou em triagem.</p>
                            <button class="btn btn-warning w-100" wire:click="startTriage">
                                <i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem
                            </button>
                            @break

                        @case('triage')
                        @case('waiting_controller_action')
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" wire:click="$toggle('showAssignForm')">
                                    <i class="bi bi-send me-1"></i>Enviar para Campo
                                </button>
                                <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')">
                                    <i class="bi bi-lock me-1"></i>Fechar Internamente
                                </button>
                            </div>
                            @break

                        @case('sent_to_field')
                        @case('field_received')
                        @case('waiting_field_response')
                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-hourglass me-1"></i>
                                Aguardando retorno do executante
                                {{ $currentAssignment?->toUser ? "({$currentAssignment->toUser->name})" : '' }}.
                            </div>
                            @break

                        @case('returned_by_field')
                        @case('under_controller_review')
                            <div class="d-grid gap-2">
                                <button class="btn btn-success" wire:click="approveReturn">
                                    <i class="bi bi-check2-all me-1"></i>Aprovar Retorno
                                </button>
                                <button class="btn btn-outline-warning" wire:click="$toggle('showReturnForm')">
                                    <i class="bi bi-arrow-return-left me-1"></i>Devolver para Correção
                                </button>
                                <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')">
                                    <i class="bi bi-lock me-1"></i>Fechar Internamente
                                </button>
                            </div>
                            @break

                        @case('ready_to_close_external')
                            <button class="btn btn-success w-100" wire:click="$toggle('showCloseForm')">
                                <i class="bi bi-check-circle me-1"></i>Confirmar Fechamento Externo
                            </button>
                            @break

                        @case('cancelled')
                        @case('ignored')
                            <button class="btn btn-outline-secondary w-100" wire:click="reopen">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reabrir Demanda
                            </button>
                            @break

                        @default
                            <div class="alert alert-secondary small mb-0">Demanda encerrada.</div>
                    @endswitch

                    {{-- Formulário: Enviar para Campo --}}
                    @if($showAssignForm)
                        <hr>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Executante *</label>
                            <select class="form-select form-select-sm" wire:model="assignToUserId">
                                <option value="">Selecionar...</option>
                                @foreach($fieldUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Mensagem</label>
                            <textarea class="form-control form-control-sm" rows="3" wire:model="assignMessage"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Prazo interno</label>
                            <input type="date" class="form-control form-control-sm" wire:model="assignDueAt" />
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showAssignForm', false)">Cancelar</button>
                            <button class="btn btn-sm btn-primary flex-fill" wire:click="sendToField">Enviar</button>
                        </div>
                    @endif

                    {{-- Formulário: Fechar Demanda --}}
                    @if($showCloseForm)
                        <hr>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Tipo de fechamento</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" wire:model="closureType" value="internal" id="closeInt" />
                                    <label class="form-check-label small" for="closeInt">Interno (SICODE)</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" wire:model="closureType" value="external" id="closeExt" />
                                    <label class="form-check-label small" for="closeExt">Externo (sistema jurídico)</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Motivo *</label>
                            <textarea class="form-control form-control-sm" rows="3" wire:model="closureReason"
                                      placeholder="Descreva o motivo do fechamento (mín. 10 caracteres)"></textarea>
                        </div>
                        @if($closureType === 'external')
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Protocolo externo *</label>
                                <input type="text" class="form-control form-control-sm" wire:model="externalProtocol" />
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Data de fechamento externo</label>
                                <input type="date" class="form-control form-control-sm" wire:model="externalClosedAt" />
                            </div>
                        @endif
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showCloseForm', false)">Cancelar</button>
                            <button class="btn btn-sm btn-dark flex-fill" wire:click="closeDemand">Confirmar Fechamento</button>
                        </div>
                    @endif

                    {{-- Formulário: Devolver para Correção --}}
                    @if($showReturnForm)
                        <hr>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Motivo da devolução *</label>
                            <textarea class="form-control form-control-sm" rows="3" wire:model="returnReason"
                                      placeholder="Descreva o que precisa ser corrigido (mín. 10 caracteres)"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showReturnForm', false)">Cancelar</button>
                            <button class="btn btn-sm btn-warning flex-fill" wire:click="returnForCorrection">Devolver</button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Resumo do Caso Legal --}}
            @if($demand->legalCase)
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-building me-2"></i>Caso Legal
                    </div>
                    <div class="card-body">
                        <dl class="small mb-0">
                            <dt class="text-muted">Empresa</dt>
                            <dd>{{ $demand->legalCase->company_name ?? '—' }}</dd>
                            <dt class="text-muted">Responsável Jurídico</dt>
                            <dd>{{ $demand->legalCase->legal_responsible_name ?? '—' }}</dd>
                            <dt class="text-muted">Escritório</dt>
                            <dd>{{ $demand->legalCase->law_firm_name ?? '—' }}</dd>
                            <dt class="text-muted">Primeira aparição</dt>
                            <dd>{{ $demand->legalCase->first_seen_at ? \Carbon\Carbon::parse($demand->legalCase->first_seen_at)->format('d/m/Y') : '—' }}</dd>
                        </dl>
                    </div>
                </div>
            @endif

            {{-- Arquivos --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-paperclip me-2"></i>Arquivos
                </div>
                <div class="card-body">
                    @php
                        $controllerFiles = $demand->files->where('removed_at', null)->where('visibility', 'controller');
                        $sharedFiles     = $demand->files->where('removed_at', null)->where('visibility', 'shared');
                    @endphp

                    @if($controllerFiles->isNotEmpty())
                        <div class="small fw-semibold text-muted mb-2">Arquivos internos</div>
                        @foreach($controllerFiles as $file)
                            <div class="d-flex align-items-center gap-2 mb-1 small">
                                <i class="bi bi-file-earmark"></i>
                                <span class="flex-grow-1 text-truncate">{{ $file->original_name ?? basename($file->file_path) }}</span>
                                <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-link btn-sm p-0">
                                    <i class="bi bi-download"></i>
                                </a>
                                @if($file->uploaded_by_id === auth()->id())
                                    <button class="btn btn-link btn-sm p-0 text-danger" wire:click="removeFile({{ $file->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    @if($sharedFiles->isNotEmpty())
                        <div class="small fw-semibold text-muted mb-2 mt-3">Arquivos compartilhados</div>
                        @foreach($sharedFiles as $file)
                            <div class="d-flex align-items-center gap-2 mb-1 small">
                                <i class="bi bi-file-earmark-check"></i>
                                <span class="flex-grow-1 text-truncate">{{ $file->original_name ?? basename($file->file_path) }}</span>
                                <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-link btn-sm p-0">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        @endforeach
                    @endif

                    @if($demand->files->where('removed_at', null)->isEmpty())
                        <p class="text-muted small">Nenhum arquivo.</p>
                    @endif

                    <hr>
                    <div class="mb-2">
                        <input type="file" class="form-control form-control-sm" wire:model="uploadFile" />
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <select class="form-select form-select-sm" wire:model="fileVisibility">
                            <option value="controller">🔒 Interno</option>
                            <option value="shared">👁 Compartilhado</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary" wire:click="uploadFile" wire:loading.attr="disabled">
                            <i class="bi bi-upload"></i>
                        </button>
                    </div>
                    <div class="small text-muted">PDF, JPG, PNG, DOCX, XLSX — máx. 10MB</div>
                </div>
            </div>
        </div>
    </div>

    <x-show-loading />
</div>
