@php
    use App\Helpers\FileIcon;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions; // <- ajuste o namespace se for diferente

    // entidade selecionada para o MODAL
    $currentExternal = isset($openExternalId) ? $note->externals->firstWhere('id', $openExternalId) : null;

    // Opções de status (razões) – reason(label), value, prefix
    $protocolReasons = SelectOptions::getProtocolReasons(); // retorna array/Collection de objetos/arrays
@endphp

<div>
    <x-show-loading />

    <div class="row g-3">
        {{-- COLUNA PRINCIPAL --}}
        <div class="col-12 col-lg-9">
            {{-- DADOS DA NOTA --}}
            <div class="card shadow-sm">
                <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">Dados da Nota/OV</h5>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Note/OV</div>
                            <div class="fw-semibold">{{ $note->note }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="small text-muted">Cliente</div>
                            <div class="fw-semibold">{{ $note->client }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Rubrica</div>
                            <div class="fw-semibold">{{ $note->rubrica }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Município</div>
                            <div class="fw-semibold">{{ $note->lexp }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="small text-muted">Centro de Trabalho</div>
                            <div class="fw-semibold">{{ $note->centerjob }}</div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">Descrição</div>
                            <div class="fw-semibold text-break">{{ $note->material }}</div>
                        </div>
                        <div class="col-12">
                            <div class="small text-muted">Status da Nota</div>
                            {{-- @php $nst = Notestatus::status($note->nstats ?? null); @endphp --}}
                            {{-- <span class="badge {{ $nst->colorbg ?? 'text-bg-secondary' }}">
                                {{ $nst->status ?? ($note->nstats ?? '—') }}
                            </span> --}}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ARQUIVOS DA NOTA --}}
            <div class="card mt-3 shadow-sm">
                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">Arquivos Anexados</h5>
                <div class="card-body py-2 px-3">
                    @livewire('components.files.show-files-pool', ['files' => $note->Files], key('filesView-' . $note->id))
                </div>
            </div>

            {{-- ENTIDADES DA NOTA (CARDS) --}}
            <div class="card mt-3 shadow-sm">
                <div
                    class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde d-flex align-items-center justify-content-between">
                    <h5 class="my-0">Entidades Relacionadas</h5>
                    <button type="button" class="btn btn-sm btn-light"
                        wire:click="$emitTo('services.oexterno.actions.add-entity-protocol', 'openEntityProtocol')"
                        title="Nova Entidade Protocolar">
                        <i class="ri-add-box-line"></i>
                    </button>
                </div>

                <div class="card-body">
                    @if ($note->externals->isEmpty())
                        <p class="text-center m-0">Nenhuma entidade vinculada a esta nota.</p>
                    @else
                        <div class="row g-3">
                            @foreach ($note->externals->sortByDesc('created_at') as $external)
                                @php
                                    $lastProto = $external->protocols?->last()?->protocol;
                                    $lastStatus = $external->Comments?->last()?->title;
                                @endphp
                                <div class="col-12">
                                    <div class="card h-100 border-0 shadow-sm"
                                        wire:key="entity-card-{{ $external->id }}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="pe-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <h6
                                                            class="mb-0 {{ $external->completed ? 'text-success' : 'text-primary' }}">
                                                            {{ $external->entity?->name ?? $external->entidade }}
                                                        </h6>
                                                        @if ($external->completed)
                                                            <span class="badge text-bg-success">Encerrado</span>
                                                        @endif
                                                    </div>
                                                    @if ($external->entity && $external->entidade && $external->entidade !== $external->entity->name)
                                                        <div class="small text-muted">Apelido:
                                                            {{ $external->entidade }}</div>
                                                    @endif
                                                </div>
                                                <div class="text-end small text-muted">
                                                    <div>Aberto em:
                                                        <strong>{{ $external->created_at->format('d/m/Y') }}</strong>
                                                    </div>
                                                    <div>Último Protocolo: <strong>{{ $lastProto ?? '—' }}</strong>
                                                    </div>
                                                    <div>Status atual: <span
                                                            class="badge {{ $external->completed ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $external->completed ? 'Encerrado' : $lastStatus ?? 'Indefinido' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white d-flex justify-content-end gap-2">
                                            {{-- Abrir MODAL XL de Detalhes --}}
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                wire:click="openEntityModal({{ $external->id }})"
                                                data-bs-toggle="modal" data-bs-target="#entityModal"
                                                title="Detalhes da Entidade">
                                                <i class="ri-information-line me-1"></i> Detalhes
                                            </button>

                                            @if (!$external->completed)
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    wire:click="toFinishEntity({{ $external->id }})"
                                                    title="Encerrar Entidade">
                                                    <i class="ri-check-double-line me-1"></i> Encerrar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="deleteProtocol({{ $external->id }})"
                                                    title="Remover Entidade">
                                                    <i class="ri-delete-bin-line me-1"></i> Remover
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- COLUNA LATERAL (AÇÕES GERAIS) --}}
        <div class="col-12 col-lg-3">
            <div class="card shadow-sm">
                <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">Ações</h5>
                <div class="card-body d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity', 'openEntity')">
                        <i class="ri-building-2-line me-1"></i> Cadastrar Entidade
                    </button>
                    <button type="button" class="btn btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity-type', 'openEntityType')">
                        <i class="ri-price-tag-3-line me-1"></i> Tipos de Entidade
                    </button>
                    <button type="button" class="btn btn-primary" onclick="history.back()">
                        <i class="ri-arrow-left-line align-middle"></i> Voltar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL XL: DETALHES DA ENTIDADE / PROTOCOLOS / ARQUIVOS / COMENTÁRIOS --}}
    <div wire:ignore.self class="modal fade" id="entityModal" tabindex="-1" aria-labelledby="entityModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header edp-bg-sprucegreen-70">
                    <h5 class="modal-title text-edp-verde" id="entityModalLabel">
                        {{-- TÍTULO: mostra "Carregando..." enquanto troca de entidade --}}
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
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
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
                            <p class="text-muted">Selecione uma entidade na lista para visualizar os detalhes.</p>
                        @else
                            {{-- Linha superior: Status + Ações primárias do fluxo --}}
                            <div class="row g-3 align-items-end mb-2">
                                <div class="col-12 col-lg-5">
                                    <label class="form-label small text-muted mb-1">Status da Entidade (Razão)</label>
                                    <div class="input-group">
                                        <select class="form-select" wire:model.defer="modalStatusValue">
                                            <option value="">Selecione...</option>
                                            @foreach ($protocolReasons as $opt)
                                                @php
                                                    // suporta objeto ou array
                                                    $reason = is_array($opt)
                                                        ? $opt['reason'] ?? ''
                                                        : $opt->reason ?? '';
                                                    $value = is_array($opt) ? $opt['value'] ?? '' : $opt->value ?? '';
                                                @endphp
                                                <option value="{{ $value }}">{{ $reason }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-outline-primary" type="button"
                                            wire:click="updateEntityStatus({{ $currentExternal->id }})"
                                            title="Salvar status">
                                            <i class="ri-save-3-line"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        O tipo de evidência será gerado com o <strong>prefix</strong> da razão
                                        selecionada.
                                    </div>
                                </div>

                                <div class="col-12 col-lg-7">
                                    <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                        <button type="button" class="btn btn-outline-primary"
                                            wire:click="$emitTo('services.oexterno.actions.add-protocol', 'openAddProtocol', {{ $currentExternal->id }})">
                                            <i class="ri-file-list-3-line me-1"></i> Inserir Protocolo
                                        </button>

                                        {{-- Pedido de Pagamento (inline) --}}
                                        <div class="input-group" style="max-width: 360px;">
                                            <span class="input-group-text"><i
                                                    class="ri-money-dollar-circle-line"></i></span>
                                            <input type="text"
                                                class="form-control {{ $errors->has('paymentPoolId') ? 'is-invalid' : '' }}"
                                                placeholder="ID do pool de pagamento"
                                                wire:model.defer="paymentPoolId">
                                            <button class="btn btn-outline-primary" type="button"
                                                wire:click="requestPayment({{ $currentExternal->id }})">
                                                Adicionar
                                            </button>
                                        </div>



                                        <button type="button" class="btn btn-primary"
                                            wire:click="$emitTo('services.oexterno.actions.inter-return', 'openInternReturn', {{ $currentExternal->id }})">
                                            <i class="ri-arrow-go-back-line me-1"></i> Solicitar Retorno Interno
                                        </button>
                                        @error('paymentPoolId')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <hr class="my-3">

                                {{-- Conteúdo organizado por seções (2 colunas em desktop) --}}
                                <div class="row g-3">
                                    {{-- COLUNA ESQUERDA --}}
                                    <div class="col-12 col-lg-6">
                                        {{-- Protocolos (foco operacional) --}}
                                        <div class="card shadow-sm mb-3">
                                            <div class="card-header py-2">
                                                <h6 class="mb-0">Protocolos</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                @if ($currentExternal->protocols->isEmpty())
                                                    <div class="p-3 text-muted">Nenhum protocolo cadastrado.</div>
                                                @else
                                                    <div class="table-responsive"
                                                        style="max-height: 40vh; overflow:auto;">
                                                        <table class="table table-sm table-striped mb-0">
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
                                                                        <td class="fw-semibold">
                                                                            {{ $protocol->protocol }}
                                                                        </td>
                                                                        <td>{{ $protocol->created_at->format('d/m/Y H:i') }}
                                                                        </td>
                                                                        <td class="text-break">
                                                                            {{ $protocol->description }}
                                                                        </td>

                                                                        <td class="text-end">
                                                                            @if (!$currentExternal->completed)
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-outline-danger"
                                                                                    wire:click="deleteProtocol({{ $protocol->id }})"
                                                                                    title="Excluir protocolo">
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

                                        {{-- Protocolos (foco operacional) --}}
                                        <div class="card shadow-sm mb-3">
                                            <div class="card-header py-2">
                                                <h6 class="mb-0">Solicitação de Pagamentos</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                @if ($currentExternal->PoolPayments->isEmpty())
                                                    <div class="p-3 text-muted">Nenhum pedido de Pagamento cadastrado.
                                                    </div>
                                                @else
                                                    <div class="table-responsive"
                                                        style="max-height: 40vh; overflow:auto;">
                                                        <table class="table table-sm table-striped mb-0">
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
                                                                        <td class="fw-semibold">
                                                                            {{ $payment->pool_id }}
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
                                                                                    title="Excluir Pedido">
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

                                        {{-- Comentários --}}
                                        <div class="card shadow-sm">
                                            <div
                                                class="card-header py-2 d-flex align-items-center justify-content-between">
                                                <h6 class="mb-0">Comentários</h6>
                                                @if (!$currentExternal->completed)
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                        wire:click="$emitTo('services.oexterno.actions.add-comments', 'openAddComment', {{ $currentExternal->id }})">
                                                        <i class="ri-chat-1-line"></i> Adicionar
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="card-body p-0">
                                                @if (!$currentExternal->comments->isNotEmpty())
                                                    <div class="p-3 text-muted">Nenhum comentário.</div>
                                                @else
                                                    <div class="table-responsive"
                                                        style="max-height: 30vh; overflow:auto;">
                                                        <table class="table table-sm table-striped mb-0">
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

                                    {{-- COLUNA DIREITA --}}
                                    <div class="col-12 col-lg-6">
                                        {{-- Arquivos da Entidade --}}
                                        <div class="card shadow-sm mb-3">
                                            <div class="card-header py-2">
                                                <h6 class="mb-0">Arquivos da Entidade</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <x-files.attachments :files="$currentExternal->files" downloadAction='downloadFile' />

                                                {{-- Componente genérico de arquivos (pool) --}}
                                                {{-- @livewire('components.files.show-files-pool', ['files' => $currentExternal->files], key('filesView-external-' . $currentExternal->id)) --}}

                                                {{-- Listagem simples sem componente --}}
                                                {{-- @if ($currentExternal->files->isNotEmpty())
                                            <div class="table-responsive" style="max-height: 40vh; overflow:auto;">
                                                <table class="table table-sm table-striped table-hover mb-0">
                                                    <tbody>
                                                        @foreach ($currentExternal->files as $file)
                                                            <tr wire:key="file-{{ $file->id }}"
                                                                style="cursor:pointer;"
                                                                wire:click="downloadFile({{ $file->id }})">
                                                                <td class="fs-4 align-middle"><i
                                                                        class="{{ FileIcon::getIcon($file->ext)->icon }}"></i>
                                                                </td>
                                                                <td class="text-break">{{ $file->file_name }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="p-3 text-muted">Nenhum arquivo anexado.</div>
                                        @endif --}}
                                            </div>
                                        </div>

                                        {{-- Informações da Entidade / Contatos --}}
                                        <div class="card shadow-sm">
                                            <div class="card-header py-2">
                                                <h6 class="mb-0">Informações da Entidade</h6>
                                            </div>
                                            <div class="card-body">
                                                @if ($currentExternal->entity)
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-12 col-md-6">
                                                            <div class="small text-muted">Nome</div>
                                                            <div class="fw-semibold">
                                                                {{ $currentExternal->entity->name }}
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-md-6">
                                                            <div class="small text-muted">Apelido</div>
                                                            <div class="fw-semibold">{{ $currentExternal->entidade }}
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="small text-muted">Precisa de Aprovação</div>
                                                            <div class="fw-semibold">
                                                                {{ $currentExternal->entity->approve ? 'SIM' : 'NÃO' }}
                                                            </div>
                                                        </div>
                                                        <div class="col-2">
                                                            <div class="small text-muted">EO</div>
                                                            <div class="fw-semibold">
                                                                {{ $currentExternal->entity->eon ? 'SIM' : 'NÃO' }}
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="small text-muted">AutoCAD</div>
                                                            <div class="fw-semibold">
                                                                {{ $currentExternal->entity->cad ? 'SIM' : 'NÃO' }}
                                                            </div>
                                                        </div>
                                                        <div class="col-3">
                                                            <div class="small text-muted">Mapa</div>
                                                            <div class="fw-semibold">
                                                                {{ $currentExternal->entity->map ? 'SIM' : 'NÃO' }}
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="small text-muted">Observações</div>
                                                            <div class="fw-semibold text-break">
                                                                {{ $currentExternal->entity->observations }}</div>
                                                        </div>
                                                    </div>

                                                    @if ($currentExternal->entity->docs)
                                                        <div class="mb-3">
                                                            <div class="small text-muted mb-1">Documentos Necessários
                                                            </div>
                                                            <ul class="list-group list-group-flush border rounded">
                                                                @foreach ($currentExternal->entity->docs as $i => $document)
                                                                    <li class="list-group-item py-1">
                                                                        #{{ $i + 1 }}
                                                                        -
                                                                        {{ $document }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <div>
                                                        <div class="small text-muted mb-1">Contatos</div>
                                                        @if ($currentExternal->entity->contacts->isNotEmpty())
                                                            <div class="border rounded p-2"
                                                                style="max-height: 22vh; overflow:auto;">
                                                                @foreach ($currentExternal->entity->contacts as $contact)
                                                                    <div class="py-2 border-bottom"
                                                                        wire:key="contact-{{ $contact->id }}">
                                                                        <div class="fw-semibold">
                                                                            <i
                                                                                class="bi {{ isset($contact->name) ? 'bi-person-fill' : 'bi-globe' }} me-1"></i>
                                                                            {{ $contact->name ?? $contact->url }}
                                                                        </div>
                                                                        <div class="small">
                                                                            @isset($contact->email)
                                                                                <div><strong>Email:</strong> <a
                                                                                        href="mailto:{{ $contact->email }}"
                                                                                        class="link-secondary">{{ $contact->email }}</a>
                                                                                </div>
                                                                            @endisset
                                                                            @isset($contact->url)
                                                                                <div><strong>URL:</strong> <a
                                                                                        href="{{ $contact->url }}"
                                                                                        target="_blank"
                                                                                        class="link-secondary">{{ $contact->url }}</a>
                                                                                </div>
                                                                            @endisset
                                                                            @isset($contact->user)
                                                                                <div><strong>Usuário:</strong>
                                                                                    {{ $contact->user }}
                                                                                </div>
                                                                            @endisset
                                                                            @isset($contact->password)
                                                                                <div><strong>Senha:</strong>
                                                                                    {{ $contact->password }}</div>
                                                                            @endisset
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
                                    </div>
                                </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    @if ($currentExternal && !$currentExternal->completed)
                        <div class="d-flex gap-2">
                            {{-- Atalhos de rodapé opcionais --}}
                            <button type="button" class="btn btn-outline-success"
                                wire:click="toFinishEntity({{ $currentExternal->id }})">
                                <i class="ri-check-double-line me-1"></i> Encerrar Entidade
                            </button>
                            <button type="button" class="btn btn-outline-danger"
                                wire:click="deleteProtocol({{ $currentExternal->id }})">
                                <i class="ri-delete-bin-line me-1"></i> Remover Entidade
                            </button>
                        </div>
                    @else
                        <div></div>
                    @endif

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary"
                            wire:click="saveModalChanges({{ $currentExternal->id ?? 'null' }})">
                            <i class="ri-save-3-line me-1"></i> Salvar Alterações
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modais/Components Livewire auxiliares --}}
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
        /* Cabeçalhos fixos nas tabelas das seções roláveis */
        .modal .table thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
    </style>
@endpush
