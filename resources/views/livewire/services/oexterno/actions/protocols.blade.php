@php
    use App\Helpers\FileIcon;
    use App\Custom\Notestatus;
    use App\Helpers\SelectOptions;
    use Carbon\Carbon;
@endphp



<div>
    <style>
        .scrollable-div {
            overflow-y: auto;
            scrollbar-width: thin;
            /* Firefox */
            scrollbar-color: #888 #f1f1f1;
            /* Firefox */
        }

        .scrollable-div::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-div::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .scrollable-div::-webkit-scrollbar-thumb {
            background-color: #888;
            border-radius: 10px;
            border: 2px solid #f1f1f1;
        }

        .scrollable-div::-webkit-scrollbar-thumb:hover {
            background-color: #555;
        }
    </style>
    <x-show-loading />
    <div wire:ignore.self class="modal fade" id="modal_protocols" tabindex="-1" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content edp-bg-stategrey-50">
                <div class="modal-header edp-bg-sprucegreen-70 text-edp-verde">
                    <h4 class="my-auto fw-bold">
                        PROTOCOLOS
                    </h4>
                </div>
                <div class="modal-body">
                    @if ($note)
                        <div class="row">
                            <div class="col-6">
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

                                <ul class="nav nav-tabs" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="protocolar-tab" data-bs-toggle="tab"
                                            data-bs-target="#protocolar" type="button" role="tab"
                                            aria-controls="protocolar" aria-selected="true">Protocolar</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="retorno-tab" data-bs-toggle="tab"
                                            data-bs-target="#retorno" type="button" role="tab"
                                            aria-controls="retorno" aria-selected="false">Retorno interno</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="myTabContent" wire:ignore.self>
                                    <div class="tab-pane fade show active" id="protocolar" role="tabpanel"
                                        aria-labelledby="protocolar-tab">
                                        <div class="card border-top-0">
                                            <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                                ENTIDADE PROTOCOLAR
                                            </h5>
                                            <table class="table table-sm table-condensed table-striped-columns">
                                                <tbody>
                                                    <tr>
                                                        <td class="text-end fw-bold col-3">Tipo</td>
                                                        <td class="text-start">
                                                            @if (!$note->External)
                                                                <select class="form-select form-select-sm"
                                                                    aria-label="Small select example"
                                                                    style="max-width: 150px" wire:model="selType">
                                                                    <option selected>Selecione</option>
                                                                    @foreach (SelectOptions::getUniqueExternalTypes() as $type)
                                                                        <option value="{{ $type }}">
                                                                            {{ $type }}
                                                                        </option>
                                                                    @endforeach

                                                                </select>
                                                            @else
                                                                {{ SelectOptions::getExternalsByTypeOrNick(null, $note->External->entidade)->type }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end fw-bold col-3">Entidade</td>
                                                        <td class="text-start">
                                                            @if (!$note->External)
                                                                <select class="form-select form-select-sm"
                                                                    aria-label="Small select example"
                                                                    wire:model.defer="selAgency">
                                                                    <option selected>Selecione</option>
                                                                    @foreach (SelectOptions::getExternals($selType) as $agency)
                                                                        <option value="{{ $agency->nick }}">
                                                                            {{ $agency->nick }}
                                                                        </option>
                                                                    @endforeach

                                                                </select>
                                                            @else
                                                                {{ SelectOptions::getExternalsByTypeOrNick(null, $note->External->entidade)->agency }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-end fw-bold col-3">Protocolo</td>
                                                        <td class="text-start fw-bold pe-1">

                                                            {{ $note->External && $note->External->Protocols->count() ? $note->External->Protocols->last()->protocol : ' --- ' }}
                                                        </td>
                                                    </tr>
                                                    @if (!$note->External || ($note->External && !$note->External->completed))
                                                        <tr>
                                                            <td class="text-end fw-bold col-3 align-middle">Novo
                                                                Protocolo</td>
                                                            <td class="text-start">

                                                                <input type="text" class="form-control"
                                                                    aria-label="Sizing example input"
                                                                    aria-describedby="inputGroup-sizing-sm"
                                                                    wire:model.defer="protocol.protocol">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold col-3">Descrição</td>
                                                            <td class="text-start">
                                                                <textarea class="form-control" placeholder="Ex. Protocolo de Entrada de Documentação" id="floatingTextarea2"
                                                                    style="height: 100px; resize: none;" wire:model.defer="protocol.description"></textarea>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold col-3">Motivo</td>
                                                            <td class="text-start">
                                                                <select class="form-select form-select-sm"
                                                                    aria-label="Small select example"
                                                                    wire:model.defer="comment.title">
                                                                    <option selected>Selecione</option>
                                                                    @foreach (SelectOptions::getProtocolReasons() as $reason)
                                                                        <option value="{{ $reason->value }}">
                                                                            {{ $reason->reason }}
                                                                        </option>
                                                                    @endforeach

                                                                </select>

                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold col-3">Comentários</td>
                                                            <td class="text-start">
                                                                <textarea class="form-control" placeholder="Ex. Protocolo de Entrada de Documentação" id="floatingTextarea2"
                                                                    style="height: 100px; resize: none;" wire:model.defer="comment.comment"></textarea>
                                                            </td>
                                                        </tr>
                                                    @endif


                                                </tbody>
                                            </table>
                                            @if (!$note->External || ($note->External && !$note->External->completed))
                                                <div class="card-footer">
                                                    <div class="clear-fix">
                                                        <div class="d-flex justify-content-end me-2">
                                                            <div class="form-check align-middle">
                                                                <input
                                                                    class="form-check-input border border-1 border-secondary"
                                                                    type="checkbox" value="true"
                                                                    id="flexCheckIndeterminate"
                                                                    wire:model.defer="encerrar">
                                                                <label class="form-check-label"
                                                                    for="flexCheckIndeterminate">
                                                                    Encerrar Protocolo
                                                                </label>
                                                            </div>
                                                            <div class="d-flex justify-content-end ms-2">
                                                                <button class="btn btn-primary btn-sm"
                                                                    wire:click="save">SALVAR</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="retorno" role="tabpanel"
                                        aria-labelledby="retorno-tab">
                                        <div class="card border-top-0">
                                            <h5 class="card-header my-0 py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                                RETORNO INTERNO
                                            </h5>
                                            @if (!$note->external)
                                                <div class="card-body">
                                                    <h4 class="text-center">SEM PROTOLOCO A ASSOCIAR</h4>
                                                    <div class="text-center border border-rounded shadow p-2">Para
                                                        possibilitar o retorno desta obra,
                                                        é necessário primeiro criar um protocolo inicial,
                                                        mesmo que ainda não tenha um número de protocolo para associar
                                                        ao serviço.</div>
                                                </div>
                                            @elseif ($note->external?->reclaims?->last()?->completed || $note->external?->reclaims->isEmpty())
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <select class="form-select form-select-sm mb-2"
                                                            aria-label="Selecione o Serviço" wire:model="service_id">
                                                            <option value="" selected>Selecione o Serviço
                                                            </option>
                                                            @if ($services)
                                                                @foreach ($services as $service)
                                                                    <option value="{{ $service->uuid }}">
                                                                        {{ $service->service }}
                                                                    </option>
                                                                @endforeach

                                                            @endif

                                                        </select>

                                                        <select class="form-select form-select-sm mb-2"
                                                            aria-label="Motivo" wire:model="category_id"
                                                            @disabled(!$service_id)>
                                                            <option value="" selected>Motivo</option>
                                                            @if ($categories)
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category->id }}">
                                                                        {{ $category->name }}
                                                                    </option>
                                                                @endforeach

                                                            @endif
                                                        </select>

                                                        <select class="form-select form-select-sm mb-2"
                                                            aria-label="Detalhe" @disabled(!$category_id)
                                                            wire:model.defer="subcategory_id">
                                                            <option value="" selected>Detalhe</option>
                                                            @if ($subcategories)
                                                                @foreach ($subcategories as $subcategory)
                                                                    <option value="{{ $subcategory->id }}">
                                                                        {{ $subcategory->name }}
                                                                    </option>
                                                                @endforeach

                                                            @endif

                                                        </select>

                                                        <textarea class="form-control mb-3" rows="5" placeholder="Observações" wire:model.defer="reason"></textarea>
                                                    </div>

                                                    @if ($service_id)
                                                        @if ($production)
                                                            <div class="card mb-3">
                                                                <div
                                                                    class="card-header edp-bg-sprucegreen-70 text-edp-verde py-1">
                                                                    <h5 class="my-0">Retornar para</h5>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p class="mb-1"><strong>Serviço:</strong>
                                                                        {{ $production->service?->service }}</p>
                                                                    <p class="mb-1"><strong>Usuário:</strong>
                                                                        {{ $production->user?->name }}</p>
                                                                    <p class="mb-1"><strong>Empresa:</strong>
                                                                        {{ $production->company?->name }}</p>
                                                                    <p class="mb-1"><strong>Última
                                                                            interação:</strong>
                                                                        {{ $production->completed_at->format('d/m/Y H:i:s') }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <div class="card mb-3">
                                                                <div
                                                                    class="card-header edp-bg-sprucegreen-70 text-edp-verde py-1">
                                                                    <h5 class="my-0">Retornar para</h5>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p class="mb-1"><strong>Serviço:</strong>
                                                                        {{ $services->where('id', $service_id)->first()?->service }}
                                                                    </p>
                                                                    <p class="mb-1"><strong>Usuário:</strong>
                                                                        Não Encontrado</p>
                                                                    <p class="mb-1"><strong>Empresa:</strong>
                                                                        Não Encontrado</p>
                                                                    <p class="mb-1"><strong>Última
                                                                            interação:</strong>
                                                                        Não Encontrado
                                                                    </p>
                                                                    <div class="card text-bg-primary mt-2">
                                                                        <div class="card-body">
                                                                            <p>Por definição, essa obra será retornado
                                                                                para a pilha de retorno interno do
                                                                                serviço escolhido. <br> Acompanhe o
                                                                                andamento sempre que possível, para
                                                                                verificar se o mesmo ja foi atribuído.
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    @endif



                                                    <div class="card-footer">
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <button class="btn btn-secondary"
                                                                type="button">Cancelar</button>
                                                            <button class="btn btn-primary" type="button"
                                                                wire:click.prevent="returnReclaims">Enviar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="card-body">
                                                    <h4 class="text-center">OBRA AGUARDANDO RETORNO INTERNO</h4>
                                                    <p class="text-center">Obra Enviada em:
                                                        {{ $note->external?->reclaims?->last()->created_at->format('d/m/Y H:i:s') }}
                                                        ({{ $note->external?->reclaims?->last()->created_at->diffInDays() }}
                                                        dias)
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @livewire('files.manager.create-serv-files', ['note' => $note, 'service' => $theService], key('createServFiles-' . $note->id))



                            </div>
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <h4 class="fs-5 my-0 py-0">Arquivos</h4>
                                    </div>
                                    <div class="card-body py-1 my-0">
                                        @livewire('components.files.show-files-pool', ['files' => $note->Files], key('noteFiles-' . $note->id))
                                    </div>
                                </div>

                                <div class="card">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <div class="d-flex justify-content-between align-middle">
                                            <h5 class="fs-5 my-0 py-0 align-middle">Protocolos</h5>
                                        </div>
                                    </div>
                                    @if ($note->External && $note->External->Protocols->count())
                                        <table class="table table-sm table-condensed table-striped-columns">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="text-center">DtHora</th>
                                                    <th scope="col" class="text-center">Protocolo</th>
                                                    <th scope="col" class="text-center">Descrição</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($note->External->Protocols->sortByDesc('created_at') as $protocol)
                                                    <tr>
                                                        <td class="text-center text-wrap">
                                                            {{ date('d/m/Y H:i:s', strToTime($protocol->created_at)) }}
                                                        </td>
                                                        <td class="text-center"> {{ $protocol->protocol }}</td>
                                                        <td class="text-center text-wrap">{{ $protocol->description }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="card-body">
                                            <h5 class="text-center my-4 fw-bold">SEM PROTOCOLO REGISTRADO</h5>
                                        </div>
                                    @endif
                                </div>

                                <div class="card edp-bg-stategrey-50">
                                    <div class="card-header py-1 edp-bg-sprucegreen-70 text-edp-verde">
                                        <div class="d-flex justify-content-between align-middle">
                                            <h5 class="fs-5 my-0 py-0 align-middle">Comentários</h5>
                                        </div>
                                    </div>
                                    @if ($note->External && $note->External->Comments->count())
                                        <div class="scrollable-div rounded" style="max-height: 500px;">
                                            @foreach ($note->External->Comments->sortByDesc('created_at') as $comment)
                                                <table class="table table-sm table-condensed table-striped-columns">
                                                    <tbody>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Motivo
                                                            </td>
                                                            <td class="text-uppercase">{{ $comment->title }}</td>

                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Usuario
                                                            </td>
                                                            <td class="fw-bold text-uppercase">
                                                                {{ $comment->User->name }}
                                                            </td>


                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Comentário
                                                            </td>
                                                            <td class="text-wrap">
                                                                {{ $comment->comment }}
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="text-end fw-bold" style="width: 100px;">
                                                                Data
                                                            </td>
                                                            <td class="">
                                                                {{ date('d/m/Y H:i', strToTime($comment->created_at)) }}
                                                            </td>


                                                        </tr>
                                                    </tbody>
                                                </table>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="card-body">
                                            <h5 class="text-center my-4 fw-bold">SEM COMENTÁRIOS REGISTRADO</h5>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        </div>

                    @endif


                </div>
            </div>
        </div>
    </div>



    <script>
        // Capturando o evento de fechamento do modal
        document.getElementById('modal_protocols').addEventListener('hidden.bs.modal', () => {

            Livewire.emitTo('services.oexterno.actions.protocols', 'cleanAll');
        });
    </script>

    <script>
        function setupTabMemory() {
            const modalEl = document.getElementById('modal_protocols');
            const tabContainer = modalEl?.querySelector('#myTab');

            if (!modalEl || !tabContainer) return;

            // 1. Salva aba ativa sempre que muda
            tabContainer.querySelectorAll('button[data-bs-toggle="tab"]').forEach(button => {
                button.removeEventListener('shown.bs.tab', handleTabShown); // evita múltiplas binds
                button.addEventListener('shown.bs.tab', handleTabShown);
            });

            function handleTabShown(event) {
                const activeTabId = event.target.id;
                sessionStorage.setItem('activeProtocolTab_modalProtocols', activeTabId);
                console.log('[TAB SALVA]', activeTabId);
            }

            // 2. Restaura aba salva
            const savedTab = sessionStorage.getItem('activeProtocolTab_modalProtocols');
            if (savedTab) {
                const triggerEl = document.getElementById(savedTab);
                if (triggerEl) {
                    const tab = new bootstrap.Tab(triggerEl);
                    tab.show();
                    console.log('[TAB RESTAURADA]', savedTab);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            setupTabMemory();

            const modalEl = document.getElementById('modal_protocols');

            if (modalEl) {
                modalEl.addEventListener('shown.bs.modal', () => {
                    setTimeout(setupTabMemory, 10); // garante render completo antes de aplicar
                });

                modalEl.addEventListener('hidden.bs.modal', () => {
                    Livewire.emitTo('services.oexterno.actions.protocols', 'cleanAll');
                    sessionStorage.removeItem('activeProtocolTab_modalProtocols');
                });
            }

            Livewire.hook('message.processed', () => {
                setTimeout(() => {
                    if (document.getElementById('modal_protocols')?.classList.contains('show')) {
                        setupTabMemory();
                    }
                }, 50);
            });
        });
    </script>


</div>
