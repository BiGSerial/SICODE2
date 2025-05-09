<div>
    <div class="row">
        <div class="col-12 col-md-9">
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
                                <div class="accordion-item mb-2 shadow">
                                    <h2 class="accordion-header" id="heading{{ $external->id }}">
                                        <button class="accordion-button collapsed" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapse{{ $external->id }}"
                                            aria-expanded="false" aria-controls="collapse{{ $external->id }}">
                                            <div class="row w-100">
                                                <div class="col-3">
                                                    <p class="fs-6 fw-bold py-0 my-0">Entidade:</p>
                                                    <p class="fs-6 py-0 my-0 text-primary">
                                                        {{ $external->entidade }}</p>
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
                                                        {{ $external->status }}</p>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $external->id }}" class="accordion-collapse collapse shadow"
                                        aria-labelledby="heading{{ $external->id }}"
                                        data-bs-parent="#protocolAccordion">
                                        <div class="accordion-body">
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <h6 class="mb-2">Histórico de Protocolos</h6>
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Protocolo</th>
                                                                <th>Tipo</th>
                                                                <th>Data</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>{{ $external->protocol }}</td>
                                                                <td>{{ $external->type }}</td>
                                                                <td>{{ $external->date }}</td>
                                                                <td>{{ $external->status }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-12">
                                                    <h6 class="mb-2">Documentos Necessários</h6>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled
                                                            {{ $external->doc1 ? 'checked' : '' }}>
                                                        <label class="form-check-label">Documento 1</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled
                                                            {{ $external->doc2 ? 'checked' : '' }}>
                                                        <label class="form-check-label">Documento 2</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled
                                                            {{ $external->doc3 ? 'checked' : '' }}>
                                                        <label class="form-check-label">Documento 3</label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled
                                                            {{ $external->doc4 ? 'checked' : '' }}>
                                                        <label class="form-check-label">Documento 4</label>
                                                    </div>
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
        <div class="col-12 col-md-3 ">
            <div class="card">
                <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                    Ações
                </h5>
                <div class="card-body d-grid gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity', 'openEntity')">
                        Cadastrar Entidade
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        wire:click="$emitTo('components.entity.add-entity-type', 'openEntityType')">
                        Cadastrar Tipos de Entidades
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary"
                        wire:click="$emit('cancelNote', {title: 'Atenção', msg: 'Deseja realmente cancelar a nota?', icon: 'warning', btnOktxt: 'Sim', btnCanceltxt: 'Não', action: 'cancelNote', cancel_titulo: 'Cancelado!', cancel_msg: 'Nota cancelada com sucesso!'})">
                        Cancelar Nota
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="goBack">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Livewire Components --}}
    @livewire('components.entity.add-entity-type', key('add-entity-type'))
    @livewire('components.entity.add-entity', key('add-entity'))
    @livewire('services.oexterno.actions.add-entity-protocol', ['note' => $note], key('add-entity-protocol'))
</div>
