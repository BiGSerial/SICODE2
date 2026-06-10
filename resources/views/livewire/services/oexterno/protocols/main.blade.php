@php
    use App\Helpers\FileIcon;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Illuminate\Support\Collection;

    // Opções de status (razões) – reason(label), value, prefix
    $protocolReasons = collect(SelectOptions::getProtocolReasons());
    $externalCount = $note->externals->count();
    $openExternalCount = $note->externals->where('completed', false)->count();
    $protocolCount = $note->externals->sum(fn ($external) => $external->protocols->count());
    $fileCount = $note->Files->count();
@endphp

<div class="user-activity-page">
    {{-- LOADER GLOBAL --}}
    <x-show-loading />
    @include('livewire.services.partials.user-activity-list-style')

    <div class="container-fluid">
        <section class="user-activity-hero d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3"
            style="--activity-accent: #0f766e;">
            <div>
                <div class="activity-meta text-uppercase">Órgão externo • Gestão de protocolo</div>
                <h2>NOTA / OV {{ $note->note }}</h2>
                <div class="activity-meta mt-1">
                    {{ $note->client ?: 'Cliente não informado' }} •
                    {{ $note->material ?: 'Descrição não informada' }}
                </div>
            </div>
            <div class="d-flex flex-column align-items-xl-end gap-3">
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-light"
                        wire:click="$emitTo('components.entity.add-entity', 'openEntity')" data-bs-toggle="tooltip"
                        data-bs-title="Cadastre uma nova entidade no catálogo">
                        <i class="ri-building-2-line me-1"></i> Cadastrar entidade
                    </button>
                    <button type="button" class="btn btn-outline-light"
                        wire:click="$emitTo('components.entity.add-entity-type', 'openEntityType')"
                        data-bs-toggle="tooltip" data-bs-title="Gerencie os tipos de entidade">
                        <i class="ri-price-tag-3-line me-1"></i> Tipos
                    </button>
                    <button type="button" class="btn btn-outline-light" onclick="history.back()"
                        data-bs-toggle="tooltip" data-bs-title="Voltar para a tela anterior">
                        <i class="ri-arrow-left-line me-1"></i> Voltar
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-4 text-xl-end">
                    <div>
                        <div class="activity-meta">Entidades</div>
                        <div class="activity-count">{{ $externalCount }}</div>
                    </div>
                    <div>
                        <div class="activity-meta">Em aberto</div>
                        <div class="activity-count">{{ $openExternalCount }}</div>
                    </div>
                    <div>
                        <div class="activity-meta">Protocolos</div>
                        <div class="activity-count">{{ $protocolCount }}</div>
                    </div>
                    <div>
                        <div class="activity-meta">Arquivos</div>
                        <div class="activity-count">{{ $fileCount }}</div>
                    </div>
                </div>
            </div>
        </section>

        {{-- CONTEÚDO PRINCIPAL COM ABAS --}}
        <div class="card user-activity-table-card">
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
                        <span class="badge text-bg-light ms-1">{{ $fileCount }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link {{ $activeMainTab == 'entities-pane' ? 'active' : '' }}" id="entities-tab"
                        data-bs-toggle="tab" data-bs-target="#entities-pane" type="button" role="tab"
                        aria-controls="entities-pane"
                        aria-selected="{{ $activeMainTab == 'entities-pane' ? 'true' : 'false' }}"
                        wire:click="setActiveMainTab('entities-pane')">
                        <i class="ri-team-line me-2"></i>Entidades Relacionadas
                        <span class="badge text-bg-light ms-1">{{ $externalCount }}</span>
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
                                <dd>{{ $note->client ?: 'Não informado' }}</dd>
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
                                <dd>{{ $note->rubrica ?: 'Não informada' }}</dd>
                            </div>
                            <div class="spec-row">
                                <dt>Município</dt>
                                <dd>{{ $note->lexp ?: 'Não informado' }}</dd>
                            </div>
                            <div class="spec-row">
                                <dt>Centro de Trabalho</dt>
                                <dd>{{ $note->centerjob ?: 'Não informado' }}</dd>
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
                                <dd class="text-break">{{ $note->material ?: 'Não informada' }}</dd>
                            </div>
                            <div class="spec-row spec-row--full">
                                <dt>Status da Nota</dt>
                                <dd>
                                    <span class="chip chip-primary">{{ $note->nstats ?: 'N/D' }}</span>
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
                                            $lastProtocol = $external->protocols?->first();
                                            $lastStatusLabel =
                                                $protocolReasons->firstWhere('value', $external->status)?->reason ??
                                                null;
                                            $lastComment = $external->comments?->first();
                                            $lastUser = $lastComment?->user?->name;
                                            $lastInteraction = $lastComment?->created_at?->format('d/m/Y H:i');
                                        @endphp

                                        <div class="col-12 col-lg-6">
                                            <div class="card h-100 entity-card"
                                                wire:key="entity-card-{{ $external->id }}">
                                                <div class="card-body p-0">
                                                    <div class="entity-card__header">
                                                        <div class="entity-card__identity">
                                                            <span
                                                                class="entity-card__avatar {{ $external->completed ? 'entity-card__avatar--completed' : '' }}">
                                                                <i class="ri-government-line"></i>
                                                            </span>
                                                            <div class="min-w-0">
                                                                <div class="entity-card__eyebrow">Entidade externa</div>
                                                                <h6 class="entity-card__name text-truncate mb-0">
                                                                    {{ $external->entity?->name ?? $external->entidade }}
                                                                </h6>
                                                                @if ($external->entity && $external->entidade && $external->entidade !== $external->entity->name)
                                                                    <div class="entity-card__alias text-truncate">
                                                                        {{ $external->entidade }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <span
                                                            class="entity-status {{ $external->completed ? 'entity-status--completed' : 'entity-status--active' }}">
                                                            <span class="entity-status__dot"></span>
                                                            {{ $external->completed ? 'Encerrado' : $lastStatusLabel ?? 'Indefinido' }}
                                                        </span>
                                                    </div>

                                                    <div class="entity-card__content">
                                                        <div class="entity-info-grid">
                                                            <div class="entity-info-item">
                                                                <span class="entity-info-item__icon">
                                                                    <i class="ri-calendar-event-line"></i>
                                                                </span>
                                                                <div>
                                                                    <span class="entity-info-item__label">Abertura</span>
                                                                    <strong>{{ $external->created_at->format('d/m/Y') }}</strong>
                                                                </div>
                                                            </div>

                                                            <div class="entity-info-item">
                                                                <span class="entity-info-item__icon">
                                                                    <i class="ri-hashtag"></i>
                                                                </span>
                                                                <div class="min-w-0">
                                                                    <span class="entity-info-item__label">Último protocolo</span>
                                                                    <strong class="text-truncate d-block"
                                                                        title="{{ $lastProtocol?->protocol ?? 'Sem protocolo' }}">
                                                                        {{ $lastProtocol?->protocol ?? 'Sem protocolo' }}
                                                                    </strong>
                                                                </div>
                                                            </div>

                                                            <div class="entity-info-item entity-info-item--wide">
                                                                <span class="entity-info-item__icon">
                                                                    <i class="ri-user-voice-line"></i>
                                                                </span>
                                                                <div class="min-w-0">
                                                                    <span class="entity-info-item__label">Última interação</span>
                                                                    @if ($lastUser && $lastInteraction)
                                                                        <strong class="text-truncate d-block"
                                                                            title="{{ $lastUser }} — {{ $lastInteraction }}">
                                                                            {{ $lastUser }}
                                                                        </strong>
                                                                        <small>{{ $lastInteraction }}</small>
                                                                    @else
                                                                        <strong>Sem interação registrada</strong>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="card-footer entity-card__footer">
                                                    <div class="entity-card__footer-meta">
                                                        <i class="ri-chat-3-line"></i>
                                                        {{ $external->comments->count() }}
                                                        {{ $external->comments->count() === 1 ? 'interação' : 'interações' }}
                                                    </div>
                                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                            wire:click="openEntityModal({{ $external->id }})"
                                                            data-bs-toggle="modal" data-bs-target="#entityModal"
                                                            title="Abrir detalhes completos e ações">
                                                            <i class="ri-information-line me-1"></i> Detalhes
                                                        </button>

                                                        @if (!$external->completed)
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-success"
                                                                wire:click="toFinishEntity({{ $external->id }})"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-title="Encerrar tratativa dessa entidade">
                                                                <i class="ri-check-double-line me-1"></i> Encerrar
                                                            </button>
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-danger"
                                                                wire:click="deleteProtocol({{ $external->id }})"
                                                                data-bs-toggle="tooltip"
                                                                data-bs-title="Remover vínculo da entidade com esta nota">
                                                                <i class="ri-delete-bin-line me-1"></i> Remover
                                                            </button>
                                                        @endif
                                                    </div>
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content entity-detail-modal">
                <div class="modal-header entity-detail-modal__header">
                    <div class="entity-detail-modal__identity">
                        <span class="entity-detail-modal__avatar">
                            <i class="ri-government-line"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="entity-detail-modal__eyebrow">Detalhes da entidade externa</div>
                            <h5 class="modal-title text-truncate mb-0" id="entityModalLabel">
                                <span wire:loading.inline wire:target="openEntityModal">Carregando entidade...</span>
                                <span wire:loading.remove wire:target="openEntityModal">
                                    @if ($currentExternal)
                                        {{ $currentExternal->entity?->name ?? $currentExternal->entidade }}
                                    @else
                                        Detalhes da Entidade
                                    @endif
                                </span>
                            </h5>
                            <div class="entity-detail-modal__subtitle">
                                Gerencie protocolos, pagamentos, interações, arquivos e retornos internos.
                            </div>
                        </div>
                        @if ($currentExternal)
                            <span
                                class="entity-status {{ $currentExternal->completed ? 'entity-status--completed' : 'entity-status--active' }}">
                                <span class="entity-status__dot"></span>
                                {{ $currentExternal->completed ? 'Encerrado' : $protocolReasons->firstWhere('value', $currentExternal->status)?->reason ?? 'Indefinido' }}
                            </span>
                        @endif
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
                            <div class="entity-modal-summary mb-3">
                                <div class="entity-modal-summary__item">
                                    <i class="ri-calendar-event-line"></i>
                                    <div>
                                        <span>Vinculada em</span>
                                        <strong>{{ $currentExternal->created_at->format('d/m/Y') }}</strong>
                                    </div>
                                </div>
                                <div class="entity-modal-summary__item">
                                    <i class="ri-file-list-3-line"></i>
                                    <div>
                                        <span>Protocolos</span>
                                        <strong>{{ $currentExternal->protocols->count() }}</strong>
                                    </div>
                                </div>
                                <div class="entity-modal-summary__item">
                                    <i class="ri-hand-coin-line"></i>
                                    <div>
                                        <span>Pagamentos</span>
                                        <strong>{{ $currentExternal->PoolPayments->count() }}</strong>
                                    </div>
                                </div>
                                <div class="entity-modal-summary__item">
                                    <i class="ri-chat-3-line"></i>
                                    <div>
                                        <span>Interações</span>
                                        <strong>{{ $currentExternal->comments->count() }}</strong>
                                    </div>
                                </div>
                                <div class="entity-modal-summary__item">
                                    <i class="ri-attachment-2"></i>
                                    <div>
                                        <span>Arquivos</span>
                                        <strong>{{ $currentExternal->files->count() }}</strong>
                                    </div>
                                </div>
                                <div class="entity-modal-summary__item">
                                    <i class="ri-arrow-go-back-line"></i>
                                    <div>
                                        <span>Retornos</span>
                                        <strong>{{ $currentExternal->Reclaims->count() }}</strong>
                                    </div>
                                </div>
                            </div>

                            {{-- Ações imediatas --}}
                            <div class="row g-3 align-items-stretch mb-4">
                                {{-- Status da Entidade --}}
                                <div class="col-12 col-lg-6">
                                    <div class="card entity-quick-action h-100">
                                        <div class="card-body">
                                            <div class="entity-quick-action__heading">
                                                <span class="entity-quick-action__icon">
                                                    <i class="ri-flag-line"></i>
                                                </span>
                                                <div>
                                                    <h6>Status da entidade</h6>
                                                    <p>Atualize a etapa atual da tratativa.</p>
                                                </div>
                                            </div>
                                            <div class="input-group">
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
                                                O tipo de evidência usa o prefixo da razão selecionada.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Solicitar Pagamento --}}
                                <div class="col-12 col-lg-6">
                                    <div class="card entity-quick-action entity-quick-action--success h-100">
                                        <div class="card-body">
                                            <div class="entity-quick-action__heading">
                                                <span class="entity-quick-action__icon">
                                                    <i class="ri-money-dollar-circle-line"></i>
                                                </span>
                                                <div>
                                                    <h6>Vincular pagamento</h6>
                                                    <p>Registre o identificador devolvido pelo portal.</p>
                                                </div>
                                            </div>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control {{ $errors->has('paymentPoolId') ? 'is-invalid' : '' }}"
                                                    placeholder="Ex.: HRC0008140"
                                                    wire:model.defer="paymentPoolId"
                                                    aria-label="Código da solicitação de pagamento">
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
                                                Aceita ID numérico ou código com prefixo, como
                                                <strong>HRC0008140</strong>.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ABAS DE DETALHES DA ENTIDADE --}}
                            <div class="entity-modal-workspace">
                            <ul class="nav nav-pills nav-pills-primary entity-modal-tabs" id="entityDetailTabs"
                                role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button
                                        class="nav-link {{ $activeModalTab == 'modal-protocols' ? 'active' : '' }}"
                                        id="modal-protocols-tab" data-bs-toggle="pill"
                                        data-bs-target="#modal-protocols" type="button" role="tab"
                                        aria-controls="modal-protocols"
                                        aria-selected="{{ $activeModalTab == 'modal-protocols' ? 'true' : 'false' }}"
                                        wire:click="setActiveModalTab('modal-protocols')">
                                        <i class="ri-file-list-3-line me-2"></i>Protocolos
                                        <span class="badge">{{ $currentExternal->protocols->count() }}</span>
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
                                        <span class="badge">{{ $currentExternal->PoolPayments->count() }}</span>
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
                                        <span class="badge">{{ $currentExternal->comments->count() }}</span>
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
                                        <i class="ri-folder-2-line me-2"></i>Arquivos
                                        <span class="badge">{{ $currentExternal->files->count() }}</span>
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
                                        <i class="ri-arrow-go-back-line me-2"></i>Retornos
                                        <span class="badge">{{ $currentExternal->Reclaims->count() }}</span>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content entity-modal-tab-content" id="entityDetailTabsContent">
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
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer entity-detail-modal__footer">
                    @if ($currentExternal && !$currentExternal->completed)
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-danger"
                                wire:click="deleteProtocol({{ $currentExternal->id }})" data-bs-toggle="tooltip"
                                data-bs-title="Remove o vínculo desta entidade">
                                <i class="ri-delete-bin-line me-1"></i> Remover Entidade
                            </button>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <div class="d-flex flex-wrap gap-2 ms-auto">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Fechar
                        </button>
                        @if ($currentExternal && !$currentExternal->completed)
                            <button type="button" class="btn btn-outline-success"
                                wire:click="toFinishEntity({{ $currentExternal->id }})" data-bs-toggle="tooltip"
                                data-bs-title="Marca a entidade como concluída">
                                <i class="ri-check-double-line me-1"></i> Encerrar entidade
                            </button>
                        @endif
                        <button type="button" class="btn btn-primary px-4"
                            wire:click="saveModalChanges({{ $currentExternal->id ?? 'null' }})"
                            @disabled($currentExternal?->completed)>
                            <i class="ri-save-3-line me-1"></i> Salvar Alterações
                        </button>
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
</div>

@push('css')
    <style>
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
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
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
            border-radius: .75rem;
            /* Bordas arredondadas */
            padding: .8rem 1rem;
            /* Preenchimento confortável */
            box-shadow: none;
        }

        .spec-row--highlight {
            background: linear-gradient(135deg, #edf4ff, #f6f9ff);
            border-color: #ccdcf6;
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
            font-size: .96rem;
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
            border-radius: .9rem;
            transition: all .2s ease-in-out;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .07);
        }

        .entity-card:hover {
            border-color: rgba(15, 118, 110, .35);
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(15, 23, 42, .12);
        }

        .min-w-0 {
            min-width: 0;
        }

        .entity-card__header {
            align-items: center;
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            padding: 1rem 1.1rem;
        }

        .entity-card__identity {
            align-items: center;
            display: flex;
            gap: .75rem;
            min-width: 0;
        }

        .entity-card__avatar {
            align-items: center;
            background: var(--bs-primary-bg-subtle);
            border: 1px solid var(--bs-primary-border-subtle);
            border-radius: .8rem;
            color: var(--bs-primary);
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1.25rem;
            height: 2.9rem;
            justify-content: center;
            width: 2.9rem;
        }

        .entity-card__avatar--completed {
            background: var(--bs-success-bg-subtle);
            border-color: var(--bs-success-border-subtle);
            color: var(--bs-success);
        }

        .entity-card__eyebrow {
            color: var(--bs-secondary-color);
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .08em;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .entity-card__name {
            color: var(--bs-primary);
            font-size: .98rem;
            font-weight: 700;
            margin-top: .18rem;
        }

        .entity-card__alias {
            color: var(--bs-secondary-color);
            font-size: .76rem;
            margin-top: .15rem;
        }

        .entity-status {
            align-items: center;
            border: 1px solid transparent;
            border-radius: 999px;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: .7rem;
            font-weight: 700;
            gap: .4rem;
            max-width: 45%;
            padding: .38rem .62rem;
            text-align: left;
        }

        .entity-status__dot {
            background: currentColor;
            border-radius: 999px;
            flex: 0 0 auto;
            height: .42rem;
            width: .42rem;
        }

        .entity-status--active {
            background: var(--bs-info-bg-subtle);
            border-color: var(--bs-info-border-subtle);
            color: var(--bs-info-text-emphasis);
        }

        .entity-status--completed {
            background: var(--bs-success-bg-subtle);
            border-color: var(--bs-success-border-subtle);
            color: var(--bs-success-text-emphasis);
        }

        .entity-card__content {
            padding: 1rem 1.1rem;
        }

        .entity-info-grid {
            display: grid;
            gap: .65rem;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .entity-info-item {
            align-items: center;
            background: var(--bs-tertiary-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: .7rem;
            display: flex;
            gap: .65rem;
            min-width: 0;
            padding: .7rem .75rem;
        }

        .entity-info-item--wide {
            grid-column: 1 / -1;
        }

        .entity-info-item__icon {
            align-items: center;
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color);
            border-radius: .55rem;
            color: var(--bs-primary);
            display: inline-flex;
            flex: 0 0 auto;
            height: 2rem;
            justify-content: center;
            width: 2rem;
        }

        .entity-info-item__label {
            color: var(--bs-secondary-color);
            display: block;
            font-size: .67rem;
            font-weight: 600;
            line-height: 1.2;
            margin-bottom: .15rem;
            text-transform: uppercase;
        }

        .entity-info-item strong {
            color: var(--bs-body-color);
            font-size: .82rem;
            font-weight: 650;
        }

        .entity-info-item small {
            color: var(--bs-secondary-color);
            display: block;
            font-size: .72rem;
            margin-top: .08rem;
        }

        .entity-card__footer {
            align-items: center;
            background: #fff;
            border-top: 1px solid var(--bs-border-color);
            display: flex;
            gap: .75rem;
            justify-content: space-between;
            padding: .75rem 1.1rem;
        }

        .entity-card__footer-meta {
            align-items: center;
            color: var(--bs-secondary-color);
            display: inline-flex;
            font-size: .75rem;
            gap: .35rem;
            white-space: nowrap;
        }

        @media (max-width: 575.98px) {
            .entity-card__header,
            .entity-card__footer {
                align-items: flex-start;
                flex-direction: column;
            }

            .entity-status {
                max-width: 100%;
            }

            .entity-info-grid {
                grid-template-columns: 1fr;
            }

            .entity-info-item--wide {
                grid-column: auto;
            }

            .entity-card__footer>div:last-child,
            .entity-card__footer .btn {
                width: 100%;
            }

            .entity-card__footer .btn {
                justify-content: center;
            }
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

        #entityModal .entity-detail-modal {
            background: #f4f7f9;
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .26);
            overflow: hidden;
        }

        #entityModal .entity-detail-modal__header {
            align-items: center;
            background: linear-gradient(120deg, #0f172a, #0f766e);
            border: 0;
            color: #fff;
            min-height: 5.5rem;
            padding: 1rem 1.25rem;
        }

        #entityModal .entity-detail-modal__identity {
            align-items: center;
            display: flex;
            gap: .85rem;
            min-width: 0;
        }

        #entityModal .entity-detail-modal__avatar {
            align-items: center;
            background: rgba(255, 255, 255, .14);
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: .85rem;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1.35rem;
            height: 3rem;
            justify-content: center;
            width: 3rem;
        }

        #entityModal .entity-detail-modal__eyebrow {
            color: rgba(255, 255, 255, .65);
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        #entityModal .modal-title {
            color: #fff !important;
            font-size: 1.08rem;
            font-weight: 700;
            margin-top: .12rem;
            max-width: 34rem;
        }

        #entityModal .entity-detail-modal__subtitle {
            color: rgba(255, 255, 255, .7);
            font-size: .76rem;
            margin-top: .15rem;
        }

        #entityModal .entity-detail-modal__header .entity-status {
            background: rgba(255, 255, 255, .12);
            border-color: rgba(255, 255, 255, .2);
            color: #fff;
            margin-left: .5rem;
            max-width: 18rem;
        }

        #entityModal .modal-body {
            background: #f4f7f9;
            padding: 1rem 1.15rem 1.25rem;
        }

        #entityModal .entity-modal-summary {
            background: #fff;
            border: 1px solid var(--bs-border-color);
            border-radius: .85rem;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            overflow: hidden;
        }

        #entityModal .entity-modal-summary__item {
            align-items: center;
            border-right: 1px solid var(--bs-border-color);
            display: flex;
            gap: .55rem;
            min-width: 0;
            padding: .7rem .8rem;
        }

        #entityModal .entity-modal-summary__item:last-child {
            border-right: 0;
        }

        #entityModal .entity-modal-summary__item>i {
            color: var(--bs-primary);
            flex: 0 0 auto;
            font-size: 1rem;
        }

        #entityModal .entity-modal-summary__item span,
        #entityModal .entity-modal-summary__item strong {
            display: block;
        }

        #entityModal .entity-modal-summary__item span {
            color: var(--bs-secondary-color);
            font-size: .64rem;
            line-height: 1.2;
            text-transform: uppercase;
        }

        #entityModal .entity-modal-summary__item strong {
            color: var(--bs-body-color);
            font-size: .87rem;
            margin-top: .08rem;
        }

        #entityModal .entity-quick-action {
            border: 1px solid var(--bs-border-color);
            border-radius: .85rem;
            box-shadow: 0 7px 20px rgba(15, 23, 42, .05);
        }

        #entityModal .entity-quick-action__heading {
            align-items: center;
            display: flex;
            gap: .65rem;
            margin-bottom: .8rem;
        }

        #entityModal .entity-quick-action__icon {
            align-items: center;
            background: var(--bs-primary-bg-subtle);
            border-radius: .65rem;
            color: var(--bs-primary);
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1.1rem;
            height: 2.45rem;
            justify-content: center;
            width: 2.45rem;
        }

        #entityModal .entity-quick-action--success .entity-quick-action__icon {
            background: var(--bs-success-bg-subtle);
            color: var(--bs-success);
        }

        #entityModal .entity-quick-action__heading h6 {
            color: var(--bs-body-color);
            font-size: .88rem;
            font-weight: 700;
            margin: 0;
        }

        #entityModal .entity-quick-action__heading p {
            color: var(--bs-secondary-color);
            font-size: .7rem;
            margin: .08rem 0 0;
        }

        #entityModal .entity-modal-workspace {
            background: #fff;
            border: 1px solid var(--bs-border-color);
            border-radius: .9rem;
            box-shadow: 0 9px 24px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        #entityModal .entity-modal-tabs {
            background: #f8fafc;
            border-bottom: 1px solid var(--bs-border-color);
            flex-wrap: nowrap;
            gap: .25rem;
            overflow-x: auto;
            padding: .65rem;
        }

        #entityModal .entity-modal-tabs .nav-link {
            align-items: center;
            border: 1px solid transparent;
            border-radius: .65rem;
            color: var(--bs-secondary-color);
            display: inline-flex;
            font-size: .78rem;
            gap: .15rem;
            padding: .52rem .68rem;
            white-space: nowrap;
        }

        #entityModal .entity-modal-tabs .nav-link:hover {
            background: #fff;
            border-color: var(--bs-border-color);
            color: var(--bs-primary);
        }

        #entityModal .entity-modal-tabs .nav-link.active {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            box-shadow: 0 5px 12px rgba(var(--bs-primary-rgb), .2);
            color: #fff;
        }

        #entityModal .entity-modal-tabs .badge {
            background: var(--bs-secondary-bg);
            color: var(--bs-secondary-color);
            font-size: .62rem;
            margin-left: .25rem;
            min-width: 1.25rem;
        }

        #entityModal .entity-modal-tabs .nav-link.active .badge {
            background: rgba(255, 255, 255, .18);
            color: #fff;
        }

        #entityModal .entity-modal-tab-content {
            padding: .85rem;
        }

        #entityModal .entity-modal-tab-content>.tab-pane>.card {
            border: 1px solid var(--bs-border-color) !important;
            border-radius: .75rem;
            box-shadow: none !important;
            overflow: hidden;
        }

        #entityModal .entity-modal-tab-content .card-header {
            background: #fff !important;
            border-bottom: 1px solid var(--bs-border-color);
            min-height: 3.4rem;
            padding: .75rem .9rem;
        }

        #entityModal .entity-detail-modal__footer {
            background: #fff;
            border-top: 1px solid var(--bs-border-color);
            gap: .75rem;
            padding: .85rem 1.15rem;
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

        @media (max-width: 991.98px) {
            #entityModal .entity-modal-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            #entityModal .entity-modal-summary__item:nth-child(3) {
                border-right: 0;
            }

            #entityModal .entity-modal-summary__item:nth-child(-n+3) {
                border-bottom: 1px solid var(--bs-border-color);
            }
        }

        @media (max-width: 575.98px) {
            #entityModal .modal-dialog {
                margin: .5rem;
            }

            #entityModal .entity-detail-modal__header {
                align-items: flex-start;
            }

            #entityModal .entity-detail-modal__identity {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            #entityModal .entity-detail-modal__header .entity-status {
                margin-left: 3.85rem;
                max-width: calc(100% - 3.85rem);
            }

            #entityModal .entity-modal-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            #entityModal .entity-modal-summary__item {
                border-bottom: 1px solid var(--bs-border-color);
            }

            #entityModal .entity-modal-summary__item:nth-child(2n) {
                border-right: 0;
            }

            #entityModal .entity-modal-summary__item:nth-last-child(-n+2) {
                border-bottom: 0;
            }

            #entityModal .entity-detail-modal__footer {
                align-items: stretch;
                flex-direction: column;
            }

            #entityModal .entity-detail-modal__footer>div,
            #entityModal .entity-detail-modal__footer .btn {
                width: 100%;
            }
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
