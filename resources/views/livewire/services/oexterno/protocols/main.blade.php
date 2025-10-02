@php
    use App\Helpers\FileIcon;
    use App\Custom\Notestatus;
@endphp

{{-- Helpers de UI: clamp, sticky, sombras em listas roláveis --}}
<style>
    .clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden
    }

    .clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden
    }

    .clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden
    }

    .break-anywhere {
        overflow-wrap: anywhere;
        word-break: break-word
    }

    .sticky-col {
        position: sticky;
        top: 1rem
    }

    .scroll-area {
        max-height: 260px;
        overflow: auto
    }

    .table-sticky thead th {
        position: sticky;
        top: 0;
        z-index: 1
    }
</style>

<div>
    <div class="row g-3">
        {{-- COLUNA PRINCIPAL --}}
        <div class="col-12 col-lg-9">

            {{-- DADOS DA NOTA/OV --}}
            <div class="card">
                <div
                    class="card-header d-flex align-items-center justify-content-between py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="my-0">Dados da Nota/OV</h5>
                    <span class="badge text-bg-light text-uppercase">{{ $note->note }}</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped-columns align-middle mb-0">
                        <tbody>
                            <tr>
                                <td class="text-end fw-semibold col-3">Cliente</td>
                                <td class="break-anywhere">{{ $note->client }}</td>
                            </tr>
                            <tr>
                                <td class="text-end fw-semibold">Rubrica</td>
                                <td class="break-anywhere">{{ $note->rubrica }}</td>
                            </tr>
                            <tr>
                                <td class="text-end fw-semibold">Município</td>
                                <td class="break-anywhere">{{ $note->lexp }}</td>
                            </tr>
                            <tr>
                                <td class="text-end fw-semibold">Descrição</td>
                                <td class="break-anywhere clamp-3" title="{{ $note->material }}">{{ $note->material }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-end fw-semibold">Status</td>
                                <td class="break-anywhere">{{ $note->nstats }}</td>
                            </tr>
                            <tr>
                                <td class="text-end fw-semibold">Centro de Trabalho</td>
                                <td class="break-anywhere">{{ $note->centerjob }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ARQUIVOS ANEXADOS --}}
            <div class="card mt-2">
                <div class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="my-0">Arquivos Anexados</h5>
                </div>
                <div class="card-body py-2 px-3">
                    @livewire('components.files.show-files-pool', ['files' => $note->Files], key('filesView-' . $note->id))
                </div>
            </div>

            {{-- PROTOCOLOS --}}
            <div class="card mt-2">
                <div
                    class="card-header d-flex justify-content-between align-items-center py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="my-0">Protocolos</h5>
                    <button type="button" class="btn btn-sm btn-success"
                        wire:click="$emitTo('services.oexterno.actions.add-entity-protocol', 'openEntityProtocol')">
                        <i class="ri-add-box-line align-middle fs-5"></i>
                        Nova Entidade Protocolar
                    </button>
                </div>

                @if ($note->externals->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-secondary mb-0 text-center">Nenhum protocolo encontrado.</div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="accordion" id="protocolAccordion">
                            @foreach ($note->externals as $external)
                                @php
                                    $isOpen = $openExternalId === $external->id;
                                    $headerClasses = $external->completed ? 'text-bg-success' : '';
                                    $lastProtocol = $external->protocols?->last();
                                    $statusTitle = $external->Comments?->last()?->title;
                                @endphp

                                <div class="accordion-item mb-2 shadow-sm" wire:key="external-{{ $external->id }}">
                                    <h2 class="accordion-header" id="heading{{ $external->id }}">
                                        <button
                                            class="accordion-button {{ $isOpen ? '' : 'collapsed' }} {{ $headerClasses }}"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ $external->id }}"
                                            aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                            aria-controls="collapse{{ $external->id }}">
                                            <div class="container-fluid px-0">
                                                <div class="row w-100 g-2">
                                                    <div class="col-12 col-md-4">
                                                        <p class="fw-bold mb-0 small">Entidade</p>
                                                        <p
                                                            class="mb-0 clamp-1 break-anywhere {{ $external->completed ? 'text-white' : 'text-primary' }}">
                                                            {{ $external->entity ? $external->entity->name : $external->entidade }}
                                                        </p>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <p class="fw-bold mb-0 small">Último Protocolo</p>
                                                        <p
                                                            class="mb-0 clamp-1 break-anywhere {{ $external->completed ? 'text-white' : 'text-primary' }}">
                                                            {{ $lastProtocol?->protocol ?? '—' }}
                                                        </p>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <p class="fw-bold mb-0 small">Abertura</p>
                                                        <p
                                                            class="mb-0 {{ $external->completed ? 'text-white' : 'text-primary' }}">
                                                            {{ $external->created_at->format('d/m/Y') }}
                                                        </p>
                                                    </div>
                                                    <div class="col-12 col-md-2">
                                                        <p class="fw-bold mb-0 small">Status</p>
                                                        <p
                                                            class="mb-0 clamp-1 break-anywhere {{ $external->completed ? 'text-white' : 'text-primary' }}">
                                                            {{ $statusTitle ?? '—' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>

                                    <div id="collapse{{ $external->id }}"
                                        class="accordion-collapse collapse {{ $isOpen ? 'show' : '' }}"
                                        aria-labelledby="heading{{ $external->id }}"
                                        data-bs-parent="#protocolAccordion" x-data x-init="$el.addEventListener('shown.bs.collapse', () => Livewire.emit('setOpenExternal', {{ $external->id }}));">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                {{-- COLUNA ESQUERDA (CONTEÚDO) --}}
                                                <div class="col-12 col-xl-9">

                                                    {{-- HISTÓRICO DE PROTOCOLOS --}}
                                                    <section class="mb-3">
                                                        <h6 class="fw-bold mb-2">Histórico de Protocolos</h6>

                                                        @if ($external->protocols->isEmpty())
                                                            <div class="alert alert-secondary mb-0 text-center">Nenhum
                                                                protocolo encontrado.</div>
                                                        @else
                                                            <div class="border rounded shadow-sm scroll-area">
                                                                <div class="table-responsive">
                                                                    <table
                                                                        class="table table-sm table-condensed table-striped table-sticky mb-0">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th class="text-nowrap">Protocolo</th>
                                                                                <th>Motivo</th>
                                                                                <th class="text-nowrap">Data</th>
                                                                                <th class="text-end"></th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($external->protocols->sortByDesc('created_at') as $protocol)
                                                                                <tr>
                                                                                    <td
                                                                                        class="fw-semibold break-anywhere">
                                                                                        {{ $protocol->protocol }}</td>
                                                                                    <td class="break-anywhere clamp-2"
                                                                                        title="{{ $protocol->description }}">
                                                                                        {{ $protocol->description }}
                                                                                    </td>
                                                                                    <td class="text-nowrap">
                                                                                        {{ $protocol->created_at->format('d/m/Y H:i:s') }}
                                                                                    </td>
                                                                                    <td class="text-end">
                                                                                        <button type="button"
                                                                                            class="btn btn-sm btn-outline-danger"
                                                                                            @disabled($external->completed)
                                                                                            wire:click="deleteProtocol({{ $protocol->id }})">
                                                                                            Deletar
                                                                                        </button>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </section>

                                                    {{-- INFORMAÇÕES DA ENTIDADE --}}
                                                    <section class="mb-3">
                                                        <h6 class="fw-bold mb-2">Informações da Entidade</h6>

                                                        @if ($external->entity)
                                                            <div class="border rounded shadow-sm p-2 mb-2">
                                                                <div class="row g-2">
                                                                    <div class="col-12 col-md-6">
                                                                        <div class="d-flex">
                                                                            <span class="fw-semibold me-2">Nome:</span>
                                                                            <span class="break-anywhere clamp-2"
                                                                                title="{{ $external->entity->name }}">{{ $external->entity->name }}</span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-12 col-md-6">
                                                                        <div class="d-flex">
                                                                            <span
                                                                                class="fw-semibold me-2">Apelido:</span>
                                                                            <span class="break-anywhere clamp-1"
                                                                                title="{{ $external->entidade }}">{{ $external->entidade }}</span>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-6 col-md-3"><span
                                                                            class="fw-semibold">Aprovação:</span>
                                                                        {{ $external->entity->approve ? 'SIM' : 'NÃO' }}
                                                                    </div>
                                                                    <div class="col-6 col-md-3"><span
                                                                            class="fw-semibold">EO:</span>
                                                                        {{ $external->entity->eon ? 'SIM' : 'NÃO' }}
                                                                    </div>
                                                                    <div class="col-6 col-md-3"><span
                                                                            class="fw-semibold">AutoCAD:</span>
                                                                        {{ $external->entity->cad ? 'SIM' : 'NÃO' }}
                                                                    </div>
                                                                    <div class="col-6 col-md-3"><span
                                                                            class="fw-semibold">Mapa:</span>
                                                                        {{ $external->entity->map ? 'SIM' : 'NÃO' }}
                                                                    </div>

                                                                    @if ($external->entity->observations)
                                                                        <div class="col-12">
                                                                            <span
                                                                                class="fw-semibold">Observações:</span>
                                                                            <div class="break-anywhere clamp-3"
                                                                                title="{{ $external->entity->observations }}">
                                                                                {{ $external->entity->observations }}
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div class="row g-3">
                                                                {{-- DOCUMENTOS NECESSÁRIOS --}}
                                                                <div class="col-12 col-xl-4">
                                                                    <h6 class="fw-bold mb-2">Documentos Necessários
                                                                    </h6>
                                                                    <div class="border rounded shadow-sm">
                                                                        @if ($external->entity->docs)
                                                                            <ul class="list-group list-group-flush">
                                                                                @foreach ($external->entity->docs as $i => $document)
                                                                                    <li
                                                                                        class="list-group-item py-1 break-anywhere">
                                                                                        #{{ $i + 1 }} —
                                                                                        {{ $document }}</li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @else
                                                                            <div class="p-2 text-muted">Nenhum
                                                                                documento listado.</div>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                {{-- CONTATOS --}}
                                                                <div class="col-12 col-xl-8">
                                                                    <h6 class="fw-bold mb-2">Contatos da Entidade</h6>
                                                                    <div class="border rounded shadow-sm p-2">
                                                                        @if ($external->entity->contacts->isNotEmpty())
                                                                            <div class="accordion accordion-flush"
                                                                                id="contactsAccordion-{{ $external->id }}">
                                                                                @foreach ($external->entity->contacts as $contact)
                                                                                    @php
                                                                                        $cid =
                                                                                            $external->id .
                                                                                            '-' .
                                                                                            $contact->id;
                                                                                    @endphp
                                                                                    <div class="accordion-item"
                                                                                        wire:key="external-contacts-{{ $contact->id }}">
                                                                                        <h2 class="accordion-header"
                                                                                            id="heading-{{ $cid }}">
                                                                                            <button
                                                                                                class="accordion-button small py-1 collapsed"
                                                                                                type="button"
                                                                                                data-bs-toggle="collapse"
                                                                                                data-bs-target="#collapse-{{ $cid }}"
                                                                                                aria-expanded="false"
                                                                                                aria-controls="collapse-{{ $cid }}">
                                                                                                <i
                                                                                                    class="bi {{ isset($contact->name) ? 'bi-person-fill' : 'bi-globe' }} me-1"></i>
                                                                                                <span
                                                                                                    class="clamp-1 break-anywhere">
                                                                                                    {{ $contact->name ?? $contact->url }}
                                                                                                </span>
                                                                                            </button>
                                                                                        </h2>
                                                                                        <div id="collapse-{{ $cid }}"
                                                                                            class="accordion-collapse collapse"
                                                                                            aria-labelledby="heading-{{ $cid }}"
                                                                                            data-bs-parent="#contactsAccordion-{{ $external->id }}">
                                                                                            <div
                                                                                                class="accordion-body small py-2">
                                                                                                @isset($contact->email)
                                                                                                    <div class="mb-1">
                                                                                                        <strong>Email:</strong>
                                                                                                        <a class="link-secondary break-anywhere"
                                                                                                            href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                                                                                                    </div>
                                                                                                @endisset
                                                                                                @isset($contact->url)
                                                                                                    <div class="mb-1">
                                                                                                        <strong>URL:</strong>
                                                                                                        <a class="link-secondary break-anywhere"
                                                                                                            href="{{ $contact->url }}"
                                                                                                            target="_blank"
                                                                                                            rel="noopener">{{ $contact->url }}</a>
                                                                                                    </div>
                                                                                                @endisset
                                                                                                @isset($contact->user)
                                                                                                    <div
                                                                                                        class="mb-1 break-anywhere">
                                                                                                        <strong>Usuário:</strong>
                                                                                                        {{ $contact->user }}
                                                                                                    </div>
                                                                                                @endisset
                                                                                                @isset($contact->password)
                                                                                                    <div
                                                                                                        class="mb-1 break-anywhere">
                                                                                                        <strong>Senha:</strong>
                                                                                                        {{ $contact->password }}
                                                                                                    </div>
                                                                                                @endisset
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @else
                                                                            <div class="text-muted">Nenhum contato
                                                                                cadastrado.</div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="alert alert-secondary mb-0">Nenhuma entidade
                                                                vinculada.</div>
                                                        @endif
                                                    </section>

                                                    {{-- COMENTÁRIOS --}}
                                                    <section>
                                                        <h6 class="fw-bold mb-2">Comentários</h6>
                                                        @if (!$external->comments->isNotEmpty())
                                                            <div class="alert alert-info mb-0">Nenhum comentário
                                                                encontrado.</div>
                                                        @else
                                                            <div class="border rounded shadow-sm scroll-area">
                                                                <div class="table-responsive">
                                                                    <table
                                                                        class="table table-sm table-striped table-sticky mb-0">
                                                                        <thead class="table-light">
                                                                            <tr>
                                                                                <th class="text-nowrap">Data</th>
                                                                                <th class="text-nowrap">Usuário</th>
                                                                                <th class="text-nowrap">Título</th>
                                                                                <th>Comentário</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach ($external->Comments->sortByDesc('created_at') as $comment)
                                                                                <tr>
                                                                                    <td class="text-nowrap">
                                                                                        {{ $comment->created_at->format('d/m/Y H:i:s') }}
                                                                                    </td>
                                                                                    <td class="clamp-1 break-anywhere">
                                                                                        {{ $comment->user?->name }}
                                                                                    </td>
                                                                                    <td class="clamp-1 break-anywhere"
                                                                                        title="{{ $comment->title }}">
                                                                                        {{ $comment->title }}</td>
                                                                                    <td class="break-anywhere clamp-2"
                                                                                        title="{{ $comment->comment }}">
                                                                                        {{ $comment->comment }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </section>
                                                </div>

                                                {{-- COLUNA DIREITA (AÇÕES E RESUMOS) --}}
                                                <div class="col-12 col-xl-3">
                                                    <div class="sticky-col">
                                                        @if (!$external->completed)
                                                            <div class="d-grid gap-2 mb-3">
                                                                <button type="button" class="btn btn-outline-primary"
                                                                    wire:click="$emitTo('services.oexterno.actions.edit-entity-protocol', 'openEdityEntityProtocol', {{ $external->id }})">
                                                                    Editar
                                                                </button>
                                                                <button type="button" class="btn btn-outline-primary"
                                                                    wire:click="$emitTo('services.oexterno.actions.add-protocol', 'openAddProtocol', {{ $external->id }})">
                                                                    Adicionar Protocolo
                                                                </button>
                                                                <button type="button" class="btn btn-outline-primary"
                                                                    wire:click="$emitTo('services.oexterno.actions.add-comments', 'openAddComment', {{ $external->id }})">
                                                                    Adicionar Comentário
                                                                </button>
                                                                <button type="button" class="btn btn-primary"
                                                                    wire:click="$emitTo('services.oexterno.actions.inter-return', 'openInternReturn', {{ $external->id }})">
                                                                    Retorno Interno
                                                                </button>
                                                                <button type="button" class="btn btn-success"
                                                                    wire:click="toFinishEntity({{ $external->id }})">
                                                                    Encerrar Protocolo
                                                                </button>
                                                                <button type="button" class="btn btn-danger"
                                                                    wire:click="deleteProtocol({{ $external->id }})">
                                                                    Remover Entidade Protocolar
                                                                </button>
                                                            </div>
                                                        @endif

                                                        {{-- ARQUIVOS DA ENTIDADE --}}
                                                        @if ($external->files->isNotEmpty())
                                                            <div class="card mb-3">
                                                                <h6 class="card-header my-0 py-1 text-bg-secondary">
                                                                    Arquivos</h6>
                                                                <div class="scroll-area">
                                                                    <table
                                                                        class="table table-sm table-hover align-middle mb-0">
                                                                        <tbody>
                                                                            @foreach ($external->files as $file)
                                                                                <tr wire:key="file-{{ $file->id }}"
                                                                                    role="button"
                                                                                    title="Baixar {{ $file->file_name }}"
                                                                                    wire:click="downloadFile({{ $file->id }})">
                                                                                    <td class="fs-4 align-middle">
                                                                                        <i
                                                                                            class="{{ FileIcon::getIcon($file->ext)->icon }}"></i>
                                                                                    </td>
                                                                                    <td class="clamp-2 break-anywhere">
                                                                                        {{ $file->file_name }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        {{-- RETORNO INTERNO (RESUMO) --}}
                                                        @if ($external->reclaims->isNotEmpty())
                                                            @php $lastReclaim = $external->reclaims->last(); @endphp
                                                            @if ($lastReclaim)
                                                                <div class="card">
                                                                    <h6
                                                                        class="card-header my-0 py-1 text-bg-secondary">
                                                                        Retorno Interno</h6>
                                                                    <div class="card-body p-2">
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center mb-1">
                                                                            <span
                                                                                class="text-primary fw-bold clamp-1 break-anywhere">{{ $lastReclaim->service->service }}</span>
                                                                            <span
                                                                                class="badge {{ Notestatus::status($lastReclaim->production?->status)->colorbg }}">
                                                                                {{ Notestatus::status($lastReclaim->production?->status)->status }}
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center">
                                                                            <small>
                                                                                <i class="ri-user-line"></i>
                                                                                @if ($lastReclaim->production && $lastReclaim->production->user)
                                                                                    {{ $lastReclaim->production->user->name }}
                                                                                @elseif($lastReclaim->production && !$lastReclaim->production->user)
                                                                                    Usuário Desconhecido
                                                                                @else
                                                                                    Aguardando Atribuição
                                                                                @endif
                                                                            </small>
                                                                            <small class="badge text-bg-danger">
                                                                                @if ($lastReclaim->completed)
                                                                                    {{ $lastReclaim->completed_at->diffInDays($lastReclaim->created_at) }}
                                                                                    dias
                                                                                @else
                                                                                    {{ $lastReclaim->created_at->diffInDays() }}
                                                                                    dias
                                                                                @endif
                                                                            </small>
                                                                        </div>
                                                                        <div class="text-muted small">
                                                                            <i class="ri-calendar-line"></i>
                                                                            {{ $lastReclaim->created_at->format('d/m/Y H:i') }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            </div> {{-- row --}}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div> {{-- accordion --}}
                    </div>
                @endif
            </div>
        </div>

        {{-- AÇÕES (SIDEBAR) --}}
        <div class="col-12 col-lg-3">
            <div class="card sticky-col">
                <div class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                    <h5 class="my-0">Ações</h5>
                </div>
                <div class="card-body d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity', 'openEntity')">
                        Cadastrar Entidade
                    </button>
                    <button type="button" class="btn btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity-type', 'openEntityType')">
                        Cadastrar Tipos de Entidades
                    </button>
                    <button type="button" class="btn btn-primary" onclick="history.back()">
                        <i class="ri-arrow-left-line align-middle"></i> Voltar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Livewire Components (mantidos) --}}
    @livewire('components.entity.add-entity-type', key('add-entity-type'))
    @livewire('components.entity.add-entity', key('add-entity'))
    @livewire('services.oexterno.actions.add-entity-protocol', ['note' => $note], key('add-entity-protocol'))
    @livewire('services.oexterno.actions.edit-entity-protocol', key('edit-entity-protocol'))
    @livewire('services.oexterno.actions.add-protocol', key('add-protocol'))
    @livewire('services.oexterno.actions.add-comments', key('add-comment'))
    @livewire('services.oexterno.actions.inter-return', key('internal_return'))
</div>
