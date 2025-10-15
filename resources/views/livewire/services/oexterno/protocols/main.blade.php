@php
    use App\Helpers\FileIcon;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Illuminate\Support\Collection;

    // Opções de status (razões) – reason(label), value, prefix
    $protocolReasons = collect(SelectOptions::getProtocolReasons());
@endphp

<div class="container-fluid py-4">
    {{-- LOADER GLOBAL --}}
    <x-show-loading />

    {{-- CABEÇALHO DA PÁGINA --}}
    <header class="page-header d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
        <div>
            <h3 class="mb-1 d-flex align-items-center gap-2 text-primary">
                <i class="ri-task-line"></i>
                Detalhes da Nota / OV
            </h3>
            <div class="text-muted small">Gerencie dados, entidades, protocolos, arquivos e retornos internos desta nota.
            </div>
        </div>

        {{-- Botões no topo (mantidos) --}}
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary"
                wire:click="$emitTo('components.entity.add-entity', 'openEntity')" data-bs-toggle="tooltip"
                data-bs-title="Cadastre uma nova entidade no catálogo">
                <i class="ri-building-2-line me-1"></i> Cadastrar Entidade
            </button>
            <button type="button" class="btn btn-outline-secondary"
                wire:click="$emitTo('components.entity.add-entity-type', 'openEntityType')" data-bs-toggle="tooltip"
                data-bs-title="Gerencie os tipos de entidade">
                <i class="ri-price-tag-3-line me-1"></i> Tipos
            </button>
            <button type="button" class="btn btn-primary" onclick="history.back()" data-bs-toggle="tooltip"
                data-bs-title="Voltar para a tela anterior">
                <i class="ri-arrow-left-line align-middle"></i> Voltar
            </button>
        </div>
    </header>

    {{-- CONTEÚDO PRINCIPAL COM ABAS --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white pt-3 pb-0">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link {{ $activeMainTab == 'note-data-pane' ? 'active' : '' }}" id="note-data-tab"
                        data-bs-toggle="tab" data-bs-target="#note-data-pane" type="button" role="tab"
                        aria-controls="note-data-pane"
                        aria-selected="{{ $activeMainTab == 'note-data-pane' ? 'true' : 'false' }}"
                        wire:click="setActiveMainTab('note-data-pane')">
                        <i class="ri-file-text-line me-2"></i>Dados da Nota
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeMainTab == 'files-pane' ? 'active' : '' }}" id="files-tab"
                        data-bs-toggle="tab" data-bs-target="#files-pane" type="button" role="tab"
                        aria-controls="files-pane"
                        aria-selected="{{ $activeMainTab == 'files-pane' ? 'true' : 'false' }}"
                        wire:click="setActiveMainTab('files-pane')">
                        <i class="ri-attachment-2 me-2"></i>Arquivos Anexados
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeMainTab == 'entities-pane' ? 'active' : '' }}" id="entities-tab"
                        data-bs-toggle="tab" data-bs-target="#entities-pane" type="button" role="tab"
                        aria-controls="entities-pane"
                        aria-selected="{{ $activeMainTab == 'entities-pane' ? 'true' : 'false' }}"
                        wire:click="setActiveMainTab('entities-pane')">
                        <i class="ri-team-line me-2"></i>Entidades Relacionadas
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- TAB PANE: DADOS DA NOTA --}}
                <div class="tab-pane fade {{ $activeMainTab == 'note-data-pane' ? 'show active' : '' }}"
                    id="note-data-pane" role="tabpanel" aria-labelledby="note-data-tab" tabindex="0">
                    <div class="surface px-3 py-3">
                        {{-- Grupo 1: Identificação --}}
                        <div class="group-title">
                            <i class="ri-barcode-box-line me-2"></i> Identificação
                        </div>
                        <dl class="spec-grid">
                            <div class="spec-row">
                                <dt>Nota/OV</dt>
                                <dd>{{ $note->note }}</dd>
                            </div>
                            <div class="spec-row">
                                <dt>Cliente</dt>
                                <dd>{{ $note->client }}</dd>
                            </div>
                        </dl>

                        <div class="divider"></div>

                        {{-- Grupo 2: Localidade e Centro --}}
                        <div class="group-title">
                            <i class="ri-map-pin-2-line me-2"></i> Localidade e Centro
                        </div>
                        <dl class="spec-grid">
                            <div class="spec-row">
                                <dt>Rubrica</dt>
                                <dd>{{ $note->rubrica }}</dd>
                            </div>
                            <div class="spec-row">
                                <dt>Município</dt>
                                <dd>{{ $note->lexp }}</dd>
                            </div>
                            <div class="spec-row">
                                <dt>Centro de Trabalho</dt>
                                <dd>{{ $note->centerjob }}</dd>
                            </div>
                        </dl>

                        <div class="divider"></div>

                        {{-- Grupo 3: Descrição e Status --}}
                        <div class="group-title">
                            <i class="ri-information-line me-2"></i> Descrição e Status
                        </div>
                        <dl class="spec-grid">
                            <div class="spec-row spec-row--full">
                                <dt>Descrição</dt>
                                <dd class="text-break">{{ $note->material }}</dd>
                            </div>
                            <div class="spec-row spec-row--full">
                                <dt>Status da Nota</dt>
                                <dd>
                                    <span class="chip chip-primary">{{ $note->nstats }}</span>
                                    <span class="hint">Estado atual informado pelo sistema.</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- TAB PANE: ARQUIVOS ANEXADOS --}}
                <div class="tab-pane fade {{ $activeMainTab == 'files-pane' ? 'show active' : '' }}" id="files-pane"
                    role="tabpanel" aria-labelledby="files-tab" tabindex="0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-2 px-3">
                            @livewire('components.files.show-files-pool', ['files' => $note->Files], key('filesView-' . $note->id))
                        </div>
                    </div>
                </div>

                {{-- TAB PANE: ENTIDADES RELACIONADAS --}}
                <div class="tab-pane fade {{ $activeMainTab == 'entities-pane' ? 'show active' : '' }}"
                    id="entities-pane" role="tabpanel" aria-labelledby="entities-tab" tabindex="0">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white d-flex align-items-center">
                            <i class="ri-team-line me-2 text-success fs-5"></i>
                            <div>
                                <h5 class="mb-0">Entidades Relacionadas</h5>
                                <div class="small text-muted">Acompanhe protocolos, pagamentos, arquivos e interações.
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary ms-auto"
                                wire:click="$emitTo('services.oexterno.actions.add-entity-protocol', 'openEntityProtocol')"
                                title="Nova Entidade Protocolar" data-bs-toggle="tooltip"
                                data-bs-title="Vincular uma entidade a esta nota">
                                <i class="ri-add-line me-1"></i> Adicionar
                            </button>
                        </div>

                        <div class="card-body">
                            @if ($note->externals->isEmpty())
                                <div class="empty-state">
                                    <i class="ri-inbox-line"></i>
                                    <div class="title">Nenhuma entidade vinculada</div>
                                    <div class="subtitle">Clique em <strong>Adicionar</strong> para iniciar um vínculo.
                                    </div>
                                </div>
                            @else
                                <div class="row g-3">
                                    @foreach ($note->externals->sortByDesc('created_at') as $external)
                                        @php
                                            $lastProto = $external->protocols?->last()?->protocol;
                                            $lastStatusLabel =
                                                $protocolReasons->firstWhere('value', $external->status)?->reason ??
                                                null;
                                            $lastUser = $external->comments?->last()?->user?->name;
                                            $lastInteraction = $external->comments
                                                ?->last()
                                                ?->created_at?->format('d/m/Y H:i');
                                        @endphp

                                        <div class="col-12 col-lg-6">
                                            <div class="card h-100 border-0 shadow-xs hover-shadow-sm entity-card"
                                                wire:key="entity-card-{{ $external->id }}">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start justify-content-between">
                                                        <div class="pe-2">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <h6
                                                                    class="mb-0 {{ $external->completed ? 'text-success' : 'text-primary' }}">
                                                                    {{ $external->entity?->name ?? $external->entidade }}
                                                                </h6>
                                                                @if ($external->completed)
                                                                    <span
                                                                        class="badge text-bg-success">Encerrado</span>
                                                                @endif
                                                            </div>
                                                            @if ($external->entity && $external->entidade && $external->entidade !== $external->entity->name)
                                                                <div class="small text-muted">Apelido:
                                                                    {{ $external->entidade }}</div>
                                                            @endif

                                                            <div class="meta zebra mt-2">
                                                                <div class="meta-row">
                                                                    <span class="meta-label"><i
                                                                            class="ri-calendar-line me-1"></i>Abertura</span>
                                                                    <span
                                                                        class="meta-value">{{ $external->created_at->format('d/m/Y') }}</span>
                                                                </div>
                                                                <div class="meta-row">
                                                                    <span class="meta-label"><i
                                                                            class="ri-hashtag me-1"></i>Último
                                                                        Protocolo</span>
                                                                    <span
                                                                        class="meta-value">{{ $lastProto ?? '—' }}</span>
                                                                </div>
                                                                <div class="meta-row">
                                                                    <span class="meta-label"><i
                                                                            class="ri-user-voice-line me-1"></i>Última
                                                                        interação</span>
                                                                    <span class="meta-value">
                                                                        @if ($lastUser && $lastInteraction)
                                                                            {{ $lastUser }} —
                                                                            {{ $lastInteraction }}
                                                                        @else
                                                                            —
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="text-end">
                                                            <div class="small text-muted">Status</div>
                                                            <div>
                                                                <span
                                                                    class="badge {{ $external->completed ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                                    {{ $external->completed ? 'Encerrado' : $lastStatusLabel ?? 'Indefinido' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="card-footer bg-white d-flex flex-wrap justify-content-end gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        wire:click="openEntityModal({{ $external->id }})"
                                                        data-bs-toggle="modal" data-bs-target="#entityModal"
                                                        title="Ver detalhes da entidade" data-bs-toggle="tooltip"
                                                        data-bs-title="Abrir detalhes completos e ações">
                                                        <i class="ri-information-line me-1"></i> Detalhes
                                                    </button>

                                                    @if (!$external->completed)
                                                        <button type="button" class="btn btn-sm btn-outline-success"
                                                            wire:click="toFinishEntity({{ $external->id }})"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-title="Encerrar tratativa dessa entidade">
                                                            <i class="ri-check-double-line me-1"></i> Encerrar
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            wire:click="deleteProtocol({{ $external->id }})"
                                                            data-bs-toggle="tooltip"
                                                            data-bs-title="Remover vínculo da entidade com esta nota">
                                                            <i class="ri-delete-bin-line me-1"></i> Remover
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Legenda explicativa --}}
                                <div class="legend small text-muted mt-3">
                                    <span class="legend-item">
                                        <span class="badge text-bg-success align-middle me-1">&nbsp;</span> Encerrado
                                    </span>
                                    <span class="legend-item">
                                        <span class="badge text-bg-secondary align-middle me-1">&nbsp;</span> Em
                                        andamento/Indefinido
                                    </span>
                                    <span class="legend-item">
                                        <i class="ri-user-voice-line align-middle me-1"></i> Exibe quem e quando foi a
                                        última interação
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: DETALHES DA ENTIDADE (agora com abas internas) --}}
    <div wire:ignore.self class="modal fade" id="entityModal" tabindex="-1" aria-labelledby="entityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable ">
            <div class="modal-content bg-gray">
                <div class="modal-header edp-bg-sprucegreen-70">
                    <div>
                        <h5 class="modal-title  text-edp-verde d-flex align-items-center gap-2" id="entityModalLabel">
                            <i class="ri-building-3-line"></i>
                            <span>
                                <span wire:loading.inline wire:target="openEntityModal">Carregando entidade...</span>
                                <span wire:loading.remove wire:target="openEntityModal">
                                    @if ($currentExternal)
                                        {{ $currentExternal->entity?->name ?? $currentExternal->entidade }}
                                        @if ($currentExternal->completed)
                                            <span class="badge text-bg-success ms-2">Encerrado</span>
                                        @endif
                                    @else
                                        Detalhes da Entidade
                                    @endif
                                </span>
                            </span>
                        </h5>
                        <div class="small text-white">
                            Gerencie status, protocolos, pagamentos, arquivos e retornos internos desta entidade.
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div wire:loading.flex wire:target="openEntityModal"
                        class="py-5 w-100 justify-content-center align-items-center text-center">
                        <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
                        <div class="text-muted">Carregando entidade...</div>
                    </div>

                    <div wire:loading.remove wire:target="openEntityModal"
                        wire:key="entity-modal-{{ $openExternalId ?? 'none' }}">
                        @if (!$currentExternal)
                            <div class="alert alert-info d-flex align-items-start gap-2" role="alert">
                                <i class="ri-information-line fs-5"></i>
                                <div>Selecione uma entidade na lista para visualizar os detalhes.</div>
                            </div>
                        @else
                            {{-- Ações imediatas --}}
                            <div class="row g-3 align-items-start mb-4">
                                {{-- Status da Entidade --}}
                                <div class="col-12 col-lg-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <label class="form-label fw-semibold mb-2 d-flex align-items-center">
                                                <i class="ri-price-tag-3-line me-2 text-primary fs-5"></i>
                                                Status da Entidade
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">
                                                    <i class="ri-flag-line"></i>
                                                </span>
                                                <select class="form-select" wire:model.defer="currentExternal.status"
                                                    aria-label="Status da Entidade">
                                                    <option value="">Selecione uma razão...</option>
                                                    @foreach ($protocolReasons as $opt)
                                                        @php
                                                            $reason = is_array($opt)
                                                                ? $opt['reason'] ?? ''
                                                                : $opt->reason ?? '';
                                                            $value = is_array($opt)
                                                                ? $opt['value'] ?? ''
                                                                : $opt->value ?? '';
                                                        @endphp
                                                        <option value="{{ $value }}">{{ $reason }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-primary" type="button"
                                                    wire:click="updateEntityStatus({{ $currentExternal->id }})"
                                                    title="Salvar status" aria-label="Salvar status"
                                                    @disabled($currentExternal?->completed)>
                                                    <i class="ri-save-3-line"></i>
                                                </button>
                                            </div>
                                            <div class="form-text mt-2">
                                                <i class="ri-information-line me-1"></i>
                                                O tipo de evidência será gerado com o <strong>prefix</strong> da razão
                                                selecionada.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Solicitar Pagamento --}}
                                <div class="col-12 col-lg-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <label class="form-label fw-semibold mb-2 d-flex align-items-center">
                                                <i class="ri-money-dollar-circle-line me-2 text-success fs-5"></i>
                                                Solicitar Pagamento
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light">
                                                    <i class="ri-bank-card-line"></i>
                                                </span>
                                                <input type="text"
                                                    class="form-control {{ $errors->has('paymentPoolId') ? 'is-invalid' : '' }}"
                                                    placeholder="ID do pool de pagamento"
                                                    wire:model.defer="paymentPoolId"
                                                    aria-label="ID do pool de pagamento">
                                                <button class="btn btn-success" type="button"
                                                    wire:click="requestPayment({{ $currentExternal->id }})"
                                                    title="Adicionar pedido de pagamento"
                                                    aria-label="Adicionar pedido de pagamento"
                                                    @disabled($currentExternal?->completed)>
                                                    <i class="ri-add-line me-1"></i> Adicionar
                                                </button>
                                            </div>
                                            @error('paymentPoolId')
                                                <div class="invalid-feedback d-block mt-2">
                                                    <i class="ri-error-warning-line me-1"></i>{{ $message }}
                                                </div>
                                            @enderror
                                            <div class="form-text mt-2">
                                                <i class="ri-information-line me-1"></i>
                                                Informe o ID do pool para vincular o pagamento à entidade.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- ABAS DE DETALHES DA ENTIDADE --}}
                            <ul class="nav nav-pills nav-pills-primary mb-3" id="entityDetailTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link {{ $activeModalTab == 'modal-protocols' ? 'active' : '' }}"
                                        id="modal-protocols-tab" data-bs-toggle="pill"
                                        data-bs-target="#modal-protocols" type="button" role="tab"
                                        aria-controls="modal-protocols"
                                        aria-selected="{{ $activeModalTab == 'modal-protocols' ? 'true' : 'false' }}"
                                        wire:click="setActiveModalTab('modal-protocols')">
                                        <i class="ri-file-list-3-line me-2"></i>Protocolos
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $activeModalTab == 'modal-payments' ? 'active' : '' }}"
                                        id="modal-payments-tab" data-bs-toggle="pill"
                                        data-bs-target="#modal-payments" type="button" role="tab"
                                        aria-controls="modal-payments"
                                        aria-selected="{{ $activeModalTab == 'modal-payments' ? 'true' : 'false' }}"
                                        wire:click="setActiveModalTab('modal-payments')">
                                        <i class="ri-hand-coin-line me-2"></i>Pagamentos
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $activeModalTab == 'modal-comments' ? 'active' : '' }}"
                                        id="modal-comments-tab" data-bs-toggle="pill"
                                        data-bs-target="#modal-comments" type="button" role="tab"
                                        aria-controls="modal-comments"
                                        aria-selected="{{ $activeModalTab == 'modal-comments' ? 'true' : 'false' }}"
                                        wire:click="setActiveModalTab('modal-comments')">
                                        <i class="ri-chat-1-line me-2"></i>Comentários
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link {{ $activeModalTab == 'modal-entity-files' ? 'active' : '' }}"
                                        id="modal-entity-files-tab" data-bs-toggle="pill"
                                        data-bs-target="#modal-entity-files" type="button" role="tab"
                                        aria-controls="modal-entity-files"
                                        aria-selected="{{ $activeModalTab == 'modal-entity-files' ? 'true' : 'false' }}"
                                        wire:click="setActiveModalTab('modal-entity-files')">
                                        <i class="ri-folder-2-line me-2"></i>Arquivos da Entidade
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link {{ $activeModalTab == 'modal-info-contacts' ? 'active' : '' }}"
                                        id="modal-info-contacts-tab" data-bs-toggle="pill"
                                        data-bs-target="#modal-info-contacts" type="button" role="tab"
                                        aria-controls="modal-info-contacts"
                                        aria-selected="{{ $activeModalTab == 'modal-info-contacts' ? 'true' : 'false' }}"
                                        wire:click="setActiveModalTab('modal-info-contacts')">
                                        <i class="ri-information-line me-2"></i>Informações
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link {{ $activeModalTab == 'modal-internal-returns' ? 'active' : '' }}"
                                        id="modal-internal-returns-tab" data-bs-toggle="pill"
                                        data-bs-target="#modal-internal-returns" type="button" role="tab"
                                        aria-controls="modal-internal-returns"
                                        aria-selected="{{ $activeModalTab == 'modal-internal-returns' ? 'true' : 'false' }}"
                                        wire:click="setActiveModalTab('modal-internal-returns')">
                                        <i class="ri-arrow-go-back-line me-2"></i>Retornos Internos
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content" id="entityDetailTabsContent">
                                {{-- TAB PANE: Protocolos --}}
                                <div class="tab-pane fade {{ $activeModalTab == 'modal-protocols' ? 'show active' : '' }}"
                                    id="modal-protocols" role="tabpanel" aria-labelledby="modal-protocols-tab"
                                    tabindex="0">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white d-flex align-items-center">
                                            <h6 class="mb-0">Histórico de Protocolos</h6>
                                            @if (!$currentExternal->completed)
                                                <button type="button" class="btn btn-sm btn-outline-primary ms-auto"
                                                    wire:click="$emitTo('services.oexterno.actions.add-protocol', 'openAddProtocol', {{ $currentExternal->id }})"
                                                    title="Inserir Protocolo" aria-label="Inserir Protocolo">
                                                    <i class="ri-add-line me-1"></i> Inserir
                                                </button>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @if ($currentExternal->protocols->isEmpty())
                                                <div class="empty-state compact">
                                                    <i class="ri-inbox-line"></i>
                                                    <div class="title">Nenhum protocolo</div>
                                                    <div class="subtitle">Use <strong>Inserir</strong> para adicionar
                                                        um.</div>
                                                </div>
                                            @else
                                                <div class="table-responsive"
                                                    style="max-height: 40vh; overflow:auto;">
                                                    <table class="table table-sm table-hover align-middle mb-0">
                                                        <thead class="table-light sticky-top">
                                                            <tr>
                                                                <th>Protocolo</th>
                                                                <th>Data</th>
                                                                <th>Motivo</th>
                                                                <th class="text-end">Ações</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($currentExternal->protocols->sortByDesc('created_at') as $protocol)
                                                                <tr>
                                                                    <td class="fw-semibold">{{ $protocol->protocol }}
                                                                    </td>
                                                                    <td>{{ $protocol->created_at->format('d/m/Y H:i') }}
                                                                    </td>
                                                                    <td class="text-break">
                                                                        {{ $protocol->description }}</td>
                                                                    <td class="text-end">
                                                                        @if (!$currentExternal->completed)
                                                                            <button type="button"
                                                                                class="btn btn-sm btn-outline-danger"
                                                                                wire:click="deleteProtocol({{ $protocol->id }})"
                                                                                title="Excluir protocolo"
                                                                                aria-label="Excluir protocolo">
                                                                                <i class="ri-delete-bin-line"></i>
                                                                            </button>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- TAB PANE: Pagamentos --}}
                                <div class="tab-pane fade {{ $activeModalTab == 'modal-payments' ? 'show active' : '' }}"
                                    id="modal-payments" role="tabpanel" aria-labelledby="modal-payments-tab"
                                    tabindex="0">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white d-flex align-items-center">
                                            <h6 class="mb-0">Solicitações de Pagamento</h6>
                                        </div>
                                        <div class="card-body p-0">
                                            @if ($currentExternal->PoolPayments->isEmpty())
                                                <div class="empty-state compact">
                                                    <i class="ri-inbox-line"></i>
                                                    <div class="title">Nenhum pedido</div>
                                                    <div class="subtitle">Adicione um na seção acima.</div>
                                                </div>
                                            @else
                                                <div class="table-responsive"
                                                    style="max-height: 40vh; overflow:auto;">
                                                    <table class="table table-sm table-hover align-middle mb-0">
                                                        <thead class="table-light sticky-top">
                                                            <tr>
                                                                <th>PoolId</th>
                                                                <th>Data</th>
                                                                <th>Status</th>
                                                                <th class="text-end">Ações</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($currentExternal->PoolPayments->sortByDesc('created_at') as $payment)
                                                                <tr>
                                                                    <td class="fw-semibold">{{ $payment->pool_id }}
                                                                    </td>
                                                                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}
                                                                    </td>
                                                                    <td class="text-break">
                                                                        {{ $payment->status_pedido ?? 'Novo Pedido' }}
                                                                    </td>
                                                                    <td class="text-end">
                                                                        @if (!$currentExternal->completed)
                                                                            <button type="button"
                                                                                class="btn btn-sm btn-outline-danger"
                                                                                wire:click="deletePayment({{ $payment->id }})"
                                                                                title="Excluir Pedido"
                                                                                aria-label="Excluir Pedido">
                                                                                <i class="ri-delete-bin-line"></i>
                                                                            </button>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- TAB PANE: Comentários --}}
                                <div class="tab-pane fade {{ $activeModalTab == 'modal-comments' ? 'show active' : '' }}"
                                    id="modal-comments" role="tabpanel" aria-labelledby="modal-comments-tab"
                                    tabindex="0">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white d-flex align-items-center">
                                            <h6 class="mb-0">Observações e Trocas</h6>
                                            @if (!$currentExternal->completed)
                                                <button type="button" class="btn btn-sm btn-outline-primary ms-auto"
                                                    wire:click="$emitTo('services.oexterno.actions.add-comments', 'openAddComment', {{ $currentExternal->id }})"
                                                    title="Adicionar Comentário" aria-label="Adicionar Comentário">
                                                    <i class="ri-add-line me-1"></i> Adicionar
                                                </button>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @if (!$currentExternal->comments->isNotEmpty())
                                                <div class="empty-state compact">
                                                    <i class="ri-inbox-line"></i>
                                                    <div class="title">Nenhum comentário</div>
                                                    <div class="subtitle">Registre observações relevantes.</div>
                                                </div>
                                            @else
                                                <div class="table-responsive"
                                                    style="max-height: 30vh; overflow:auto;">
                                                    <table class="table table-sm table-hover align-middle mb-0">
                                                        <thead class="table-light sticky-top">
                                                            <tr>
                                                                <th>Data</th>
                                                                <th>Usuário</th>
                                                                <th>Título</th>
                                                                <th>Comentário</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($currentExternal->Comments->sortByDesc('created_at') as $comment)
                                                                <tr>
                                                                    <td>{{ $comment->created_at->format('d/m/Y H:i') }}
                                                                    </td>
                                                                    <td>{{ $comment->user?->name }}</td>
                                                                    <td>{{ $comment->title }}</td>
                                                                    <td class="text-break">{{ $comment->comment }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- TAB PANE: Arquivos da Entidade --}}
                                <div class="tab-pane fade {{ $activeModalTab == 'modal-entity-files' ? 'show active' : '' }}"
                                    id="modal-entity-files" role="tabpanel" aria-labelledby="modal-entity-files-tab"
                                    tabindex="0">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white d-flex align-items-center">
                                            <h6 class="mb-0">Documentos Relacionados</h6>
                                        </div>
                                        @if ($currentExternal->files->isNotEmpty())
                                            <div class="file-list-container"
                                                style="max-height: 40vh; overflow-y: auto;">
                                                <table class="table table-sm table-hover align-middle mb-0">
                                                    <tbody>
                                                        @foreach ($currentExternal->files as $file)
                                                            <tr wire:key="file-{{ $file->id }}" class="file-row"
                                                                wire:click="downloadFile({{ $file->id }})"
                                                                title="Baixar {{ $file->file_name }}"
                                                                aria-label="Baixar arquivo">
                                                                <td class="fs-4 align-middle">
                                                                    <i
                                                                        class="{{ FileIcon::getIcon($file->ext)->icon }}"></i>
                                                                </td>
                                                                <td class="text-break">{{ $file->file_name }}</td>
                                                                <td class="text-break">
                                                                    @php
                                                                        $filePath = storage_path(
                                                                            'app/public/' . $file->path,
                                                                        );
                                                                        $fileSize = file_exists($filePath)
                                                                            ? round(filesize($filePath) / 1024, 2)
                                                                            : 0;
                                                                    @endphp
                                                                    <div class="small text-muted">{{ $fileSize }}
                                                                        KB</div>
                                                                </td>
                                                                <td class="text-end">
                                                                    <button class="btn btn-sm btn-outline-secondary"
                                                                        wire:click.stop="downloadFile({{ $file->id }})"
                                                                        title="Baixar arquivo" aria-label="Baixar">
                                                                        <i class="ri-download-line"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="empty-state compact">
                                                <i class="ri-inbox-line"></i>
                                                <div class="title">Nenhum arquivo</div>
                                                <div class="subtitle">Anexe arquivos na seção principal da nota.</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- TAB PANE: Informações e Contatos --}}
                                <div class="tab-pane fade {{ $activeModalTab == 'modal-info-contacts' ? 'show active' : '' }}"
                                    id="modal-info-contacts" role="tabpanel"
                                    aria-labelledby="modal-info-contacts-tab" tabindex="0">
                                    {{-- CARD — Dados de Referência (layout refinado) --}}
                                    <div class="card border-0 shadow-sm">
                                        {{-- Header --}}
                                        <div
                                            class="card-header bg-white d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1">
                                                <i class="ri-information-line text-primary fs-5"></i>
                                                <div>
                                                    <h6 class="mb-0">Dados de Referência</h6>
                                                    <div class="small text-muted">Informações cadastrais e contatos da
                                                        entidade</div>
                                                </div>
                                            </div>

                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                wire:click="$emitTo('services.oexterno.actions.edit-entity-protocol', 'openEdityEntityProtocol', {{ $currentExternal->id }})"
                                                title="Editar Entidade" data-bs-toggle="tooltip"
                                                data-bs-title="Editar dados cadastrais da entidade">
                                                <i class="ri-edit-line me-1"></i> Editar Entidade
                                            </button>

                                            {{-- Status rápido (opcional) --}}
                                            @if (isset($currentExternal->entity))
                                                <div class="d-none d-md-flex align-items-center gap-2">
                                                    <span class="badge rounded-pill text-bg-light">
                                                        <i class="ri-id-card-line me-1"></i> ID:
                                                        {{ $currentExternal->entity->id ?? '—' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="card-body">
                                            @if ($currentExternal->entity)
                                                {{-- Identificação --}}
                                                <div class="mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="ri-id-card-line me-2 text-secondary"></i>
                                                        <span class="fw-semibold text-secondary">Identificação</span>
                                                    </div>

                                                    <div class="row g-2">
                                                        {{-- Nome (largura completa em mobile, 50% em desktop) --}}
                                                        @isset($currentExternal->entity->name)
                                                            <div class="col-12 col-md-6">
                                                                <div class="spec-item h-100">
                                                                    <dt class="spec-term">Nome</dt>
                                                                    <dd class="spec-value text-truncate"
                                                                        title="{{ $currentExternal->entity->name }}">
                                                                        {{ $currentExternal->entity->name }}
                                                                    </dd>
                                                                </div>
                                                            </div>
                                                        @endisset

                                                        {{-- Documento --}}
                                                        @isset($currentExternal->entity->document)
                                                            <div class="col-6 col-md-3">
                                                                <div class="spec-item h-100">
                                                                    <dt class="spec-term">Documento</dt>
                                                                    <dd class="spec-value">
                                                                        {{ $currentExternal->entity->document }}</dd>
                                                                </div>
                                                            </div>
                                                        @endisset

                                                        {{-- EO --}}
                                                        <div class="col-6 col-md-3">
                                                            <div class="spec-item h-100">
                                                                <dt class="spec-term">EO</dt>
                                                                <dd class="spec-value">
                                                                    @if ($currentExternal->entity->eon)
                                                                        <span
                                                                            class="badge rounded-pill text-bg-success">SIM</span>
                                                                    @else
                                                                        <span
                                                                            class="badge rounded-pill text-bg-secondary">NÃO</span>
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                        </div>

                                                        {{-- AutoCAD --}}
                                                        <div class="col-6 col-md-3">
                                                            <div class="spec-item h-100">
                                                                <dt class="spec-term">AutoCAD</dt>
                                                                <dd class="spec-value">
                                                                    @if ($currentExternal->entity->cad)
                                                                        <span
                                                                            class="badge rounded-pill text-bg-success">SIM</span>
                                                                    @else
                                                                        <span
                                                                            class="badge rounded-pill text-bg-secondary">NÃO</span>
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                        </div>

                                                        {{-- Mapa --}}
                                                        <div class="col-6 col-md-3">
                                                            <div class="spec-item h-100">
                                                                <dt class="spec-term">Mapa</dt>
                                                                <dd class="spec-value">
                                                                    @if ($currentExternal->entity->map)
                                                                        <span
                                                                            class="badge rounded-pill text-bg-success">SIM</span>
                                                                    @else
                                                                        <span
                                                                            class="badge rounded-pill text-bg-secondary">NÃO</span>
                                                                    @endif
                                                                </dd>
                                                            </div>
                                                        </div>

                                                        {{-- Observações em linha completa --}}
                                                        @isset($currentExternal->entity->observations)
                                                            <div class="col-12">
                                                                <div class="spec-item">
                                                                    <dt class="spec-term">Observações</dt>
                                                                    <dd class="spec-value text-break">
                                                                        {{ $currentExternal->entity->observations }}</dd>
                                                                </div>
                                                            </div>
                                                        @endisset
                                                    </div>
                                                </div>

                                                {{-- Documentos necessários --}}
                                                @if (!empty($currentExternal->entity->docs))
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="ri-file-list-3-line me-2 text-secondary"></i>
                                                            <span class="fw-semibold text-secondary">Documentos
                                                                Necessários</span>
                                                        </div>

                                                        <div class="chips-wrap">
                                                            @foreach ($currentExternal->entity->docs as $i => $document)
                                                                <span class="chip">
                                                                    <i class="ri-hashtag"></i>{{ $i + 1 }} —
                                                                    {{ $document }}
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Contatos --}}
                                                <div class="mb-3">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="ri-contacts-book-2-line me-2 text-secondary"></i>
                                                        <span class="fw-semibold text-secondary">Contatos</span>
                                                    </div>

                                                    @if ($currentExternal->entity->contacts->isNotEmpty())
                                                        <div class="row g-2">
                                                            @foreach ($currentExternal->entity->contacts as $contact)
                                                                <div class="col-12 col-md-6"
                                                                    wire:key="contact-{{ $contact->id }}">
                                                                    <div class="contact-card">
                                                                        <div class="d-flex align-items-start gap-2">
                                                                            <div class="avatar-circle">
                                                                                <i
                                                                                    class="bi {{ isset($contact->name) ? 'bi-person-fill' : 'bi-globe' }}"></i>
                                                                            </div>
                                                                            <div class="flex-grow-1">
                                                                                <div class="fw-semibold mb-1">
                                                                                    {{ $contact->name ?? ($contact->url ?? 'Contato') }}
                                                                                </div>

                                                                                <div
                                                                                    class="small text-body-secondary d-flex flex-column gap-1">
                                                                                    @isset($contact->email)
                                                                                        <div>
                                                                                            <span
                                                                                                class="text-muted">Email:</span>
                                                                                            <a href="mailto:{{ $contact->email }}"
                                                                                                class="link-secondary">
                                                                                                {{ $contact->email }}
                                                                                            </a>
                                                                                        </div>
                                                                                    @endisset

                                                                                    @isset($contact->url)
                                                                                        <div class="text-truncate">
                                                                                            <span
                                                                                                class="text-muted">URL:</span>
                                                                                            <a href="{{ $contact->url }}"
                                                                                                target="_blank"
                                                                                                class="link-secondary text-truncate d-inline-block"
                                                                                                style="max-width: 100%;">
                                                                                                {{ $contact->url }}
                                                                                            </a>
                                                                                        </div>
                                                                                    @endisset

                                                                                    @isset($contact->user)
                                                                                        <div>
                                                                                            <span
                                                                                                class="text-muted">Usuário:</span>
                                                                                            {{ $contact->user }}
                                                                                        </div>
                                                                                    @endisset

                                                                                    @isset($contact->password)
                                                                                        <div
                                                                                            class="d-flex align-items-center gap-2">
                                                                                            <span
                                                                                                class="text-muted">Senha:</span>
                                                                                            <span
                                                                                                class="font-monospace text-truncate"
                                                                                                style="max-width: 180px;">••••••••</span>
                                                                                            <button type="button"
                                                                                                class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                                                                onclick="this.previousElementSibling.textContent = (this.previousElementSibling.textContent==='••••••••' ? '{{ $contact->password }}' : '••••••••')">
                                                                                                <i class="ri-eye-line"></i>
                                                                                            </button>
                                                                                        </div>
                                                                                    @endisset
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <div class="text-muted">Nenhum contato cadastrado.</div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="text-muted">Nenhuma entidade detalhada.</div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Estilos leves para melhorar leitura/organização --}}
                                    <style>
                                        .spec-grid {
                                            display: grid;
                                            grid-template-columns: repeat(12, 1fr);
                                            gap: .75rem .75rem;
                                        }

                                        .spec-item {
                                            grid-column: span 4;
                                            background: var(--bs-light-bg-subtle, #f8f9fa);
                                            border: 1px solid rgba(0, 0, 0, .05);
                                            border-radius: .5rem;
                                            padding: .75rem .75rem;
                                        }

                                        .spec-span-2 {
                                            grid-column: span 12;
                                        }

                                        @media (max-width: 992px) {
                                            .spec-item {
                                                grid-column: span 6;
                                            }

                                            .spec-span-2 {
                                                grid-column: span 12;
                                            }
                                        }

                                        @media (max-width: 576px) {
                                            .spec-item {
                                                grid-column: span 12;
                                            }
                                        }

                                        .spec-term {
                                            margin: 0 0 .25rem 0;
                                            font-size: .75rem;
                                            color: var(--bs-secondary-color);
                                            text-transform: uppercase;
                                            letter-spacing: .02em;
                                        }

                                        .spec-value {
                                            margin: 0;
                                        }

                                        .chips-wrap {
                                            display: flex;
                                            flex-wrap: wrap;
                                            gap: .5rem;
                                        }

                                        .chip {
                                            display: inline-flex;
                                            align-items: center;
                                            gap: .35rem;
                                            border: 1px solid rgba(0, 0, 0, .08);
                                            background: #fff;
                                            padding: .25rem .5rem;
                                            border-radius: 999px;
                                            font-size: .85rem;
                                            box-shadow: 0 1px 0 rgba(0, 0, 0, .03);
                                        }

                                        .contacts-grid {
                                            display: grid;
                                            gap: .75rem;
                                            grid-template-columns: repeat(12, 1fr);
                                        }

                                        .contact-card {
                                            grid-column: span 6;
                                            border: 1px solid rgba(0, 0, 0, .06);
                                            border-radius: .75rem;
                                            padding: .75rem .75rem;
                                            background: #fff;
                                        }

                                        @media (max-width: 992px) {
                                            .contact-card {
                                                grid-column: span 12;
                                            }
                                        }

                                        .avatar-circle {
                                            width: 36px;
                                            height: 36px;
                                            border-radius: 50%;
                                            display: grid;
                                            place-items: center;
                                            background: var(--bs-light-bg-subtle, #f8f9fa);
                                            color: var(--bs-secondary-color);
                                            border: 1px solid rgba(0, 0, 0, .06);
                                        }
                                    </style>

                                </div>

                                {{-- TAB PANE: Retornos Internos --}}
                                <div class="tab-pane fade {{ $activeModalTab == 'modal-internal-returns' ? 'show active' : '' }}"
                                    id="modal-internal-returns" role="tabpanel"
                                    aria-labelledby="modal-internal-returns-tab" tabindex="0">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-white d-flex align-items-center">
                                            <h6 class="mb-0">Solicitações de Feedback para Áreas Internas</h6>
                                            @if (!$currentExternal->completed)
                                                <button type="button" class="btn btn-sm btn-outline-primary ms-auto"
                                                    wire:click="$emitTo('services.oexterno.actions.inter-return', 'openInternReturn', {{ $currentExternal->id }})"
                                                    title="Solicitar Retorno Interno"
                                                    aria-label="Solicitar Retorno Interno">
                                                    <i class="ri-add-line me-1"></i> Solicitar
                                                </button>
                                            @endif
                                        </div>

                                        <div class="card-body p-0">
                                            @if (!$currentExternal->Reclaims->isNotEmpty())
                                                <div class="empty-state compact">
                                                    <i class="ri-inbox-line"></i>
                                                    <div class="title">Nenhum retorno interno</div>
                                                    <div class="subtitle">Use <strong>Solicitar</strong> para abrir uma
                                                        demanda.</div>
                                                </div>
                                            @else
                                                <div class="table-responsive"
                                                    style="max-height: 30vh; overflow:auto;">
                                                    <table class="table table-sm table-hover align-middle mb-0">
                                                        <thead class="table-light sticky-top">
                                                            <tr>
                                                                <th>Data</th>
                                                                <th>Usuário</th>
                                                                <th>Título</th>
                                                                <th>Comentário</th>
                                                                <th>Status</th>
                                                                <th>Concluído Em</th>
                                                                <th>Enviado Por</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($currentExternal->Reclaims->sortByDesc('created_at') as $reclaim)
                                                                <tr wire:key="reclaim-{{ $reclaim->id }}">
                                                                    <td>{{ $reclaim->created_at->format('d/m/Y H:i') }}
                                                                    </td>
                                                                    <td>{{ $reclaim->production?->user?->name }}</td>
                                                                    <td>{{ $reclaim->category }}</td>
                                                                    <td class="text-break">
                                                                        {{ $reclaim->comments?->first()->message }}
                                                                    </td>
                                                                    <td>
                                                                        @if ($reclaim->production)
                                                                            <span
                                                                                class="badge {{ Notestatus::status($reclaim->production?->status)->colorbg }}">
                                                                                {{ Notestatus::status($reclaim->production?->status)->status }}
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-secondary">NÃO
                                                                                ATRIBUÍDO</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $reclaim->production?->completed_at ?? '---' }}
                                                                    </td>
                                                                    <td>{{ $reclaim->comments?->first()?->user?->name ?? '---' }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                {{-- /Retornos Internos --}}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    @if ($currentExternal && !$currentExternal->completed)
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-success"
                                wire:click="toFinishEntity({{ $currentExternal->id }})" data-bs-toggle="tooltip"
                                data-bs-title="Marca a entidade como concluída">
                                <i class="ri-check-double-line me-1"></i> Encerrar Entidade
                            </button>
                            <button type="button" class="btn btn-outline-danger"
                                wire:click="deleteProtocol({{ $currentExternal->id }})" data-bs-toggle="tooltip"
                                data-bs-title="Remove o vínculo desta entidade">
                                <i class="ri-delete-bin-line me-1"></i> Remover Entidade
                            </button>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary"
                            wire:click="saveModalChanges({{ $currentExternal->id ?? 'null' }})"
                            @disabled($currentExternal?->completed)>
                            <i class="ri-save-3-line me-1"></i> Salvar Alterações
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Componentes Livewire auxiliares --}}
    @livewire('components.entity.add-entity-type', key('add-entity-type'))
    @livewire('components.entity.add-entity', key('add-entity'))
    @livewire('services.oexterno.actions.add-entity-protocol', ['note' => $note], key('add-entity-protocol'))
    @livewire('services.oexterno.actions.edit-entity-protocol', key('edit-entity-protocol'))
    @livewire('services.oexterno.actions.add-protocol', key('add-protocol'))
    @livewire('services.oexterno.actions.add-comments', key('add-comment'))
    @livewire('services.oexterno.actions.inter-return', key('internal_return'))
</div>

@push('styles')
    <style>
        /* Estilos globais para aprimorar a tipografia e espaçamento */
        body {
            font-family: 'Inter', sans-serif;
            /* Considere usar uma fonte moderna como Inter ou Poppins */
            background-color: var(--bs-light);
            /* Cor de fundo mais suave */
        }

        /* Cores personalizadas para o gradiente do cabeçalho do modal */
        :root {
            --edp-verde: #00786e;
            /* Cor principal da sua paleta, se houver */
            --bg-gradient-spruce-start: rgba(0, 83, 73, 0.95);
            --bg-gradient-spruce-end: rgba(0, 120, 110, 0.9);
        }

        /* ---------- Superfície neutra para reduzir "branco" e dar contraste ---------- */
        .surface {
            background: var(--bs-body-tertiary, #f6f7f8);
            border: 1px solid rgba(0, 0, 0, .08);
            /* Borda mais suave */
            border-radius: .75rem;
            /* Bordas mais arredondadas */
            padding: 1.5rem;
            /* Preenchimento um pouco maior */
        }

        .divider {
            height: 1px;
            background-image: linear-gradient(to right, rgba(0, 0, 0, .05), rgba(0, 0, 0, .12), rgba(0, 0, 0, .05));
            margin: 1.5rem 0;
            /* Espaçamento mais consistente */
        }

        .group-title {
            font-size: .95rem;
            /* Fonte ligeiramente maior */
            font-weight: 700;
            /* Mais destaque */
            color: var(--bs-primary);
            /* Use a cor primária para um visual mais integrado */
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            letter-spacing: .5px;
            /* Espaçamento entre letras */
            text-transform: uppercase;
            /* Mais elegante */
        }

        .group-title i {
            font-size: 1.25rem;
        }

        /* Tamanho do ícone */


        /* ---------- Ficha técnica: rótulo x valor bem distintos ---------- */
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            /* Adaptável e flexível */
            gap: .75rem 1.25rem;
            /* Espaçamento melhorado */
            margin: 0;
        }

        .spec-row {
            display: flex;
            /* Usar flexbox para alinhamento */
            flex-direction: column;
            /* Rótulo acima do valor */
            background: var(--bs-white);
            border: 1px solid var(--bs-border-color);
            /* Borda mais leve */
            border-radius: .6rem;
            /* Bordas arredondadas */
            padding: .8rem 1rem;
            /* Preenchimento confortável */
            box-shadow: 0 2px 4px rgba(0, 0, 0, .03);
            /* Sombra sutil */
        }

        .spec-row--full {
            grid-column: 1 / -1;
        }

        /* Mantém largura total para campos específicos */
        .spec-row dt {
            margin: 0;
            font-size: .75rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--bs-secondary-text-emphasis);
            /* Mais suave */
            line-height: 1.2;
            margin-bottom: .25rem;
            /* Espaço entre dt e dd */
        }

        .spec-row dd {
            margin: 0;
            font-weight: 700;
            /* Mais destaque */
            color: var(--bs-body-color);
            line-height: 1.4;
        }

        /* ---------- Chips/Badges mais legíveis ---------- */
        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .3rem .8rem;
            /* Preenchimento mais generoso */
            border-radius: 999px;
            font-weight: 600;
            font-size: .8rem;
            border: 1px solid transparent;
            vertical-align: middle;
            text-transform: uppercase;
            /* Chips em maiúsculas */
        }

        .chip-primary {
            background: var(--bs-primary-bg-subtle);
            color: var(--bs-primary-text-emphasis);
            border-color: var(--bs-primary-border-subtle);
        }

        .hint {
            display: block;
            font-size: .75rem;
            color: var(--bs-secondary-text-emphasis);
            margin-top: .4rem;
        }

        /* ---------- Cards Entidade: densidade e zebra de meta ---------- */
        .entity-card {
            background: var(--bs-white);
            border: 1px solid var(--bs-border-color);
            border-radius: .75rem;
            transition: all .2s ease-in-out;
        }

        .entity-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--bs-box-shadow-lg);
            /* Sombra mais pronunciada no hover */
        }

        .entity-card .meta {
            border: 1px solid var(--bs-border-color);
            border-radius: .45rem;
            overflow: hidden;
            background: var(--bs-light);
            /* Cor de fundo mais clara */
            margin-top: 1rem;
        }

        .entity-card .meta-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            /* Largura ajustada para label */
            padding: .5rem .8rem;
            align-items: center;
            /* Alinha verticalmente */
        }

        .entity-card .meta-label {
            font-size: .78rem;
            color: var(--bs-secondary-text-emphasis);
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .entity-card .meta-value {
            font-weight: 600;
            color: var(--bs-body-color);
        }

        .entity-card .zebra .meta-row:nth-child(odd) {
            background: var(--bs-body-tertiary);
            /* Zebra mais suave */
        }

        .entity-card .card-footer {
            background-color: var(--bs-body-tertiary) !important;
            /* Destacar o rodapé */
            border-top: 1px solid var(--bs-border-color);
            border-radius: 0 0 .75rem .75rem;
            padding-top: .75rem;
            padding-bottom: .75rem;
        }

        /* ---------- Estados vazios ---------- */
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--bs-secondary-text-emphasis);
            background-color: var(--bs-body-tertiary);
            border-radius: .75rem;
            margin-top: 1rem;
        }

        .empty-state.compact {
            padding: 1.5rem 1rem;
            margin: 0.5rem 0;
        }

        .empty-state i {
            font-size: 2.2rem;
            opacity: .7;
            display: block;
            margin-bottom: .8rem;
            color: var(--bs-primary);
        }

        .empty-state .title {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: .4rem;
        }

        .empty-state .subtitle {
            font-size: .9rem;
            line-height: 1.5;
        }

        /* ---------- Lista de arquivos clicável ---------- */
        .file-row {
            cursor: pointer;
            transition: background-color .15s ease;
        }

        .file-row:hover {
            background: var(--bs-light-bg-subtle);
        }

        .file-list-container {
            border: 1px solid var(--bs-border-color);
            border-radius: .5rem;
            overflow: hidden;
        }

        /* ---------- Legend (explicações) ---------- */
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px dashed var(--bs-border-color-translucent);
            margin-top: 1rem;
        }

        .legend .legend-item {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .85rem;
        }

        /* ---------- Modal e tabelas ---------- */
        .bg-gradient-spruce {
            background: linear-gradient(135deg, var(--bg-gradient-spruce-start), var(--bg-gradient-spruce-end));
        }

        .modal-header .modal-title {
            color: var(--bs-white) !important;
            /* Cor do título do modal */
        }

        .modal-header .text-white-50 {
            color: rgba(255, 255, 255, .7) !important;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .shadow-xs {
            box-shadow: var(--bs-box-shadow-sm) !important;
        }

        .hover-shadow-sm {
            transition: box-shadow .2s ease, transform .08s ease;
        }

        .hover-shadow-sm:hover {
            box-shadow: var(--bs-box-shadow-lg) !important;
        }

        .hover-shadow-sm:active {
            transform: translateY(1px);
        }

        .table-sm> :not(caption)>*>* {
            padding-top: .6rem;
            padding-bottom: .6rem;
        }

        .modal .table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--bs-light);
            box-shadow: 0 1px 0 var(--bs-border-color);
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--bs-secondary-color);
        }

        .modal .table tbody td {
            font-size: .9rem;
        }

        /* Estilo para abas tipo "pills" no modal */
        .nav-pills-primary .nav-link {
            color: var(--bs-primary);
            background-color: transparent;
            border-radius: .5rem;
            padding: .5rem 1rem;
            transition: all .2s ease-in-out;
            font-weight: 600;
            font-size: .9rem;
        }

        .nav-pills-primary .nav-link:hover {
            background-color: var(--bs-primary-bg-subtle);
            color: var(--bs-primary-text-emphasis);
        }

        .nav-pills-primary .nav-link.active {
            color: var(--bs-white);
            background-color: var(--bs-primary);
            box-shadow: var(--bs-box-shadow-sm);
        }

        .nav-pills-primary .nav-link.active:hover {
            background-color: var(--bs-primary-hover);
            color: var(--bs-white);
        }

        .tooltip {
            font-size: .825rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Função para inicializar/re-inicializar tooltips do Bootstrap
        function initializeTooltips() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(el => {
                // Destrói instâncias existentes para prevenir duplicações
                if (bootstrap.Tooltip.getInstance(el)) {
                    bootstrap.Tooltip.getInstance(el).dispose();
                }
                return new bootstrap.Tooltip(el, {
                    delay: {
                        show: 250,
                        hide: 0
                    }
                });
            });
        }

        // Função para ativar uma aba específica do Bootstrap
        function activateBootstrapTab(tabPaneId) {
            const tabButtonId = tabPaneId + '-tab'; // Constrói o ID do botão da aba
            const tabElement = document.getElementById(tabButtonId);
            if (tabElement) {
                const tab = new bootstrap.Tab(tabElement);
                tab.show();
            }
        }

        document.addEventListener('livewire:load', function() {
            initializeTooltips(); // Inicializa tooltips no carregamento inicial da página

            // Hook do Livewire para elementos atualizados
            Livewire.hook('element.updated', (el, component) => {
                initializeTooltips(); // Re-inicializa tooltips para elementos novos ou atualizados

                // Ativa a aba principal correta após uma atualização Livewire
                // Verifica se a div tab-content principal foi atualizada
                const mainTabContent = document.querySelector('.card-body > .tab-content');
                if (mainTabContent && mainTabContent.contains(el)) {
                    activateBootstrapTab(component.activeMainTab);
                }

                // Ativa a aba do modal correta se o conteúdo do modal foi atualizado
                const modalTabContent = document.getElementById('entityDetailTabsContent');
                if (modalTabContent && modalTabContent.contains(el)) {
                    activateBootstrapTab(component.activeModalTab);
                }
            });

            // Event listener para quando o modal de entidade é aberto (disparado pelo Livewire)
            window.addEventListener('open-entity-modal', event => {
                const modalElement = document.getElementById('entityModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show(); // Garante que o modal seja exibido

                    // Pequeno atraso para garantir que o modal esteja visível antes de ativar as abas internas
                    setTimeout(() => {
                        const tabToActivate = event.detail.tab || 'modal-protocols';
                        activateBootstrapTab(tabToActivate);
                        initializeTooltips(); // Re-inicializa tooltips para o conteúdo do modal
                    }, 100); // Ajuste o atraso se necessário
                }
            });

            // Event listener para quando o modal de entidade deve ser fechado (disparado pelo Livewire)
            window.addEventListener('close-entity-modal', event => {
                const modalElement = document.getElementById('entityModal');
                if (modalElement) {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                        Livewire.emit(
                            'resetCurrentExternal'); // Notifica o Livewire para resetar o estado do modal
                    }
                }
            });

            // Event listener para o evento hidden.bs.modal do Bootstrap (quando o modal é fechado)
            const entityModalElement = document.getElementById('entityModal');
            if (entityModalElement) {
                entityModalElement.addEventListener('hidden.bs.modal', function() {
                    Livewire.emit(
                        'resetCurrentExternal'); // Notifica o Livewire para resetar o estado do modal
                });
            }

            // Adiciona um listener para o evento de toast personalizado
            window.addEventListener('show-toast', event => {
                // Adapte esta parte para o seu sistema de toasts (ex: SweetAlert2, Toastr, etc.)
                // Exemplo simples:
                console.log(`Toast (${event.detail.type}): ${event.detail.message}`);
                // alert(event.detail.message); // Para demonstração simples
            });
        });
    </script>
@endpush
