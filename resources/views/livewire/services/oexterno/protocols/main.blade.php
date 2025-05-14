@php
    use App\Helpers\FileIcon;
@endphp
<div>
    <div class="row">
        <div class="col-12 col-md-10">
            <div class="card">
                <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                    Dados da Nota/OV
                </h5>
                <table class="table table-sm table-condensed table-striped-columns">
                    <tbody>
                        <tr>
                            <td class="text-end fw-bold col-3">Note/Ov</td>
                            <td class="text-start">{{ $note->note }}</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold col-3">Cliente</td>
                            <td class="text-start">{{ $note->client }}</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold col-3">Rubrica</td>
                            <td class="text-start">{{ $note->rubrica }}</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold col-3">Municipio</td>
                            <td class="text-start">{{ $note->lexp }}</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold col-3">Descrição</td>
                            <td class="text-start">{{ $note->material }}</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold col-3">Status</td>
                            <td class="text-start">{{ $note->nstats }}</td>
                        </tr>
                        <tr>
                            <td class="text-end fw-bold col-3">Centro de Trabalho</td>
                            <td class="text-start">{{ $note->centerjob }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card mt-2">
                <h5 class="card-header py-1 my-0 edp-bg-sprucegreen-70 text-edp-verde">ARQUIVOS ANEXADOS
                </h5>
                <div class="card-body py-2 px-3">
                    @livewire('components.files.show-files-pool', ['files' => $note->Files], key('filesView-' . $note->id))
                </div>
            </div>
            <div class="card">
                <div
                    class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde d-flex justify-content-between align-items-center">
                    <h5 class="my-0">Protocolos</h5>
                    <button type="button" class="btn btn-sm btn-success"
                        wire:click="$emitTo('services.oexterno.actions.add-entity-protocol', 'openEntityProtocol')">
                        <i class="ri-add-box-line align-middle fs-5"></i> Nova Entidade Protocolar
                    </button>
                </div>
                @if ($note->externals->isEmpty())
                    <div class="card-body">
                        <p class="text-center">Nenhum protocolo encontrado.</p>
                    </div>
                @else
                    <div class="card-body">
                        <div class="accordion" id="protocolAccordion">
                            @foreach ($note->externals as $external)
                                <div class="accordion-item mb-2 shadow" wire:key="external-{{ $external->id }}">
                                    <h2 class="accordion-header" id="heading{{ $external->id }}">
                                        <button
                                            class="accordion-button @if ($openExternalId !== $external->id) collapsed @endif"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#collapse{{ $external->id }}" aria-expanded="false"
                                            aria-controls="collapse{{ $external->id }}">
                                            <div class="row w-100">
                                                <div class="col-3">
                                                    <p class="fs-6 fw-bold py-0 my-0">Entidade:</p>
                                                    <p class="fs-6 py-0 my-0 text-primary">
                                                        {{ $external->entity ? $external->entity->name : $external->entidade }}
                                                    </p>
                                                </div>
                                                <div class="col-3">
                                                    <p class="fs-6 fw-bold py-0 my-0">Ultimo Protocolo:</p>
                                                    <p class="fs-6 py-0 my-0 text-primary">
                                                        {{ $external->protocols?->last()?->protocol }}</p>
                                                </div>

                                                <div class="col-3">
                                                    <p class="fs-6 fw-bold py-0 my-0">Data Abertura:</p>
                                                    <p class="fs-6 py-0 my-0 text-primary">
                                                        {{ $external->created_at->format('d/m/Y') }}</p>
                                                </div>
                                                <div class="col-3">
                                                    <p class="fs-6 fw-bold py-0 my-0">Status:</p>
                                                    <p class="fs-6 py-0 my-0 text-primary">
                                                        {{ $external->Comments?->last()?->title }}</p>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $external->id }}"
                                        class="accordion-collapse collapse shadow @if ($openExternalId === $external->id) show @endif"
                                        aria-labelledby="heading{{ $external->id }}"
                                        data-bs-parent="#protocolAccordion" x-data x-init="$el.addEventListener('shown.bs.collapse', function() {
                                            Livewire.emit('setOpenExternal', {{ $external->id }});
                                        });">
                                        <div class="accordion-body">
                                            <div class="d-flex justify-content-between">
                                                <div class="flex-grow-1 col-9">
                                                    <div class="col-12 mb-3">
                                                        <h6 class="mb-2 fw-bold">Histórico de Protocolos</h6>
                                                        @if ($external->protocols->isEmpty())
                                                            <p class="text-center">Nenhum protocolo encontrado.</p>
                                                        @else
                                                            <div style="max-height: 250px; overflow-y: auto;"
                                                                class="border rounded shadow mb-2">
                                                                <table
                                                                    class="table table-sm table-condensed table-striped">
                                                                    <thead>
                                                                        <tr class="sticky-top">
                                                                            <th>Protocolo</th>
                                                                            <th>Motivo:</th>
                                                                            <th>Data:</th>
                                                                            <th></th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach ($external->protocols->sortByDesc('created_at') as $protocol)
                                                                            <tr>
                                                                                <td class="fw-bold">
                                                                                    {{ $protocol->protocol }}</td>
                                                                                <td>{{ $protocol->description }}</td>
                                                                                <td>{{ $protocol->created_at->format('d/m/Y H:i:s') }}
                                                                                </td>
                                                                                <td>
                                                                                    <div class="btn-group">

                                                                                        <button type="button"
                                                                                            @disabled($external->completed)
                                                                                            class="btn btn-sm btn-outline-danger"
                                                                                            wire:click="deleteProtocol({{ $protocol->id }})">
                                                                                            Deletar
                                                                                        </button>
                                                                                    </div>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="col-12">
                                                        <h6 class="mb-2 fw-bold">Informações da Entidade</h6>
                                                        @if ($external->entity)
                                                            <div class="border rounded shadow mb-2 p-2">
                                                                <table class="table table-sm table-condensed">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td class="fw-bold">Nome:</td>
                                                                            <td>{{ $external->entity->name }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="fw-bold">Apelido:</td>
                                                                            <td>{{ $external->entidade }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="fw-bold">Precisa de Aprovação:
                                                                            </td>
                                                                            <td>{{ $external->entity->approve ? 'SIM' : 'NÃO' }}
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="fw-bold">EO:</td>
                                                                            <td>{{ $external->entity->eon ? 'SIM' : 'NÃO' }}
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="fw-bold">AUTOCAD:</td>
                                                                            <td>{{ $external->entity->cad ? 'SIM' : 'NÃO' }}
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="fw-bold">Mapa de Localização:
                                                                            </td>
                                                                            <td>{{ $external->entity->map ? 'SIM' : 'NÃO' }}
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td class="fw-bold">Observações:</td>
                                                                            <td>{{ $external->entity->observations }}
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <div class="row mb-3">

                                                                <div class="col-4">
                                                                    <h6 class="mb-2 fw-bold">Documentos Necessários
                                                                    </h6>
                                                                    <div class="border rounded shadow mb-2">
                                                                        @if ($external->entity->docs)
                                                                            <ul class="list-group list-group-flush">
                                                                                @foreach ($external->entity->docs as $index => $document)
                                                                                    <li class="list-group-item py-1">
                                                                                        #{{ $index + 1 }} -
                                                                                        {{ $document }}
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="col-8">
                                                                    <h6 class="mb-2 fw-bold">Contatos da Entidade
                                                                    </h6>
                                                                    <div class="border rounded shadow p-2 mb-2">
                                                                        @if ($external->entity->contacts->isNotEmpty())
                                                                            <div class="accordion accordion-flush"
                                                                                id="contactsAccordion">
                                                                                @foreach ($external->entity->contacts as $contact)
                                                                                    <div class="accordion-item"
                                                                                        wire:key="external-contacts-{{ $contact->id }}">
                                                                                        <h2 class="accordion-header"
                                                                                            id="heading{{ $loop->index }}">
                                                                                            <button
                                                                                                class="accordion-button @if ($openExternalContactId !== $contact->id) collapsed @endif small py-1"
                                                                                                type="button"
                                                                                                data-bs-toggle="collapse"
                                                                                                data-bs-target="#collapse{{ $loop->index }}"
                                                                                                aria-expanded="false"
                                                                                                aria-controls="collapse{{ $loop->index }}">
                                                                                                {{-- ícone compacto --}}
                                                                                                <i
                                                                                                    class="bi {{ isset($contact->name) ? 'bi-person-fill' : 'bi-globe' }} me-1"></i>
                                                                                                {{ $contact->name ?? $contact->url }}
                                                                                            </button>
                                                                                        </h2>
                                                                                        <div id="collapse{{ $loop->index }}"
                                                                                            class="accordion-collapse collapse @if ($openExternalContactId === $contact->id) show @endif"
                                                                                            aria-labelledby="heading{{ $loop->index }}"
                                                                                            data-bs-parent="#contactsAccordion"
                                                                                            x-data
                                                                                            x-init="$el.addEventListener('shown.bs.collapse', function() {
                                                                                                Livewire.emit('setOpenExternalContact', {{ $contact->id }});
                                                                                            });">
                                                                                            <div
                                                                                                class="accordion-body small py-1">
                                                                                                @isset($contact->email)
                                                                                                    <div>
                                                                                                        <strong>Email:</strong>
                                                                                                        <a href="mailto:{{ $contact->email }}"
                                                                                                            class="link-secondary">
                                                                                                            {{ $contact->email }}
                                                                                                        </a>
                                                                                                    </div>
                                                                                                @endisset

                                                                                                @isset($contact->url)
                                                                                                    <div>
                                                                                                        <strong>URL:</strong>
                                                                                                        <a href="{{ $contact->url }}"
                                                                                                            target="_blank"
                                                                                                            class="link-secondary">
                                                                                                            {{ $contact->url }}
                                                                                                        </a>
                                                                                                    </div>
                                                                                                @endisset

                                                                                                @isset($contact->user)
                                                                                                    <div>
                                                                                                        <strong>Usuário:</strong>
                                                                                                        {{ $contact->user }}
                                                                                                    </div>
                                                                                                @endisset
                                                                                                @isset($contact->password)
                                                                                                    <div>
                                                                                                        <strong>Senha:</strong>
                                                                                                        {{ $contact->password }}
                                                                                                    </div>
                                                                                                @endisset
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </div>
                                                                        @endif
                                                                    </div>

                                                                </div>

                                                            </div>
                                                            <div class="col-12">
                                                                <h6 class="mb-2 fw-bold">Comentários</h6>
                                                                @if (!$external->comments->isNotEmpty())
                                                                    <div class="card text-bg-info">
                                                                        <div class="card-body">
                                                                            <p class="card-text">Nenhum comentário
                                                                                encontrado.</p>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div style="max-height: 250px; overflow-y: auto;"
                                                                        class="border rounded shadow">
                                                                        <table
                                                                            class="table table-sm table-condensed table-striped">
                                                                            <thead>
                                                                                <tr class='sticky-top'>
                                                                                    <td class="fw-bold">Data:</td>
                                                                                    <td class="fw-bold">Usuário:</td>
                                                                                    <td class="fw-bold">Titulo:</td>
                                                                                    <td class="fw-bold">Comentário:
                                                                                    </td>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach ($external->Comments->sortByDesc('created_at') as $comment)
                                                                                    <tr>
                                                                                        <td>{{ $comment->created_at->format('d/m/Y H:i:s') }}
                                                                                        </td>
                                                                                        <td>{{ $comment->user?->name }}
                                                                                        </td>
                                                                                        <td>{{ $comment->title }}
                                                                                        </td>
                                                                                        <td>{{ $comment->comment }}
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <p class="text-muted">Nenhuma entidade vinculada.</p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="ms-3 col-3 d-flex flex-column">
                                                    @if (!$external->completed)
                                                        <button type="button" class="btn btn-outline-primary mb-2"
                                                            wire:click="$emitTo('services.oexterno.actions.edit-entity-protocol', 'openEdityEntityProtocol', {{ $external->id }})">
                                                            Editar
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary mb-2"
                                                            wire:click="$emitTo('services.oexterno.actions.add-protocol', 'openAddProtocol', {{ $external->id }})">
                                                            Adicionar Protocolo
                                                        </button>
                                                        <button type="button" class="btn btn-outline-primary mb-2"
                                                            wire:click="$emitTo('services.oexterno.actions.add-comments', 'openAddComment', {{ $external->id }})">
                                                            Adicionar Comentário
                                                        </button>
                                                        <button type="button" class="btn btn-primary  mb-2"
                                                            wire:click="$emitTo('services.oexterno.actions.inter-return', 'openInternReturn', {{ $external->id }})">
                                                            Retorno Interno
                                                        </button>
                                                        <button type="button" class="btn btn-success  mb-2">
                                                            Encerrar Protocolo
                                                        </button>
                                                        <button type="button" class="btn btn-danger"
                                                            wire:click="deleteProtocol({{ $external->id }})">
                                                            Remover Entidade Protocolar
                                                        </button>
                                                    @endif
                                                    @if ($external->files->isNotEmpty())
                                                        <div class="card my-3">
                                                            <h5 class="card-header my-0 py-1 text-bg-secondary">
                                                                Arquivos
                                                            </h5>
                                                            <div class="overflow-auto" style="max-height: 200px;">
                                                                <table
                                                                    class="table table-sm table-condensed table-striped table-hover">
                                                                    <tbody>
                                                                        @foreach ($external->files as $file)
                                                                            <tr wire:key="file-{{ $file->id }}"
                                                                                style="cursor: pointer;"
                                                                                wire:click="downloadFile({{ $file->id }})">
                                                                                <td class="fs-4 align-middle"><i
                                                                                        class="{{ FileIcon::getIcon($file->ext)->icon }}"></i>
                                                                                </td>
                                                                                <td class="fs-6 text-break">
                                                                                    {{ $file->file_name }}
                                                                                </td>

                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-12 col-md-2 ">
            <div class="card">
                <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                    Ações
                </h5>
                <div class="card-body d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity', 'openEntity')">
                        Cadastrar Entidade
                    </button>
                    <button type="button" class="btn btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity-type', 'openEntityType')">
                        Cadastrar Tipos de Entidades
                    </button>


                </div>
            </div>
        </div>
    </div>

    {{-- Livewire Components --}}
    @livewire('components.entity.add-entity-type', key('add-entity-type'))
    @livewire('components.entity.add-entity', key('add-entity'))
    @livewire('services.oexterno.actions.add-entity-protocol', ['note' => $note], key('add-entity-protocol'))
    @livewire('services.oexterno.actions.edit-entity-protocol', key('edit-entity-protocol'))
    @livewire('services.oexterno.actions.add-protocol', key('add-protocol'))
    @livewire('services.oexterno.actions.add-comments', key('add-comment'))
    @livewire('services.oexterno.actions.inter-return', key('internal_return'))
