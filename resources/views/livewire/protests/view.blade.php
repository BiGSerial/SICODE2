<div>
    <div>

        {{-- Carrega o Loading da página --}}
        <x-show-loading />



        <div class="card mb-0 shadow rounded-bottom-0" style='z-index: 1;'>
            <div
                class="card-header  {{ $medProtest->protest?->tipoNota == 'NA' ? 'text-bg-primary' : 'text-bg-danger' }} py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">RECLAMAÇÃO <span class="fw-bold">{{ $medProtest->protest->nota }}</span> - Medida
                        Número: #<span class="fw-bold">{{ $medProtest->med_id }}</span></h5>
                    <span
                        class="badge bg-light {{ $medProtest->protest?->tipoNota == 'NA' ? 'text-primary' : 'text-danger' }}">{{ $medProtest->protest->tipoNota }}</span>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-3" style="min-height: 200px;">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100 border-secondary">
                            <h6 class="text-muted mb-2 text-primary">INFORMAÇÕES BÁSICAS</h6>
                            <p class="mb-1"><strong>Grupo Codificação:</strong>
                                {{ $medProtest->protest?->txtGrpCodificacao }}
                            </p>
                            <p class="mb-1"><strong>Centro Plan:</strong>
                                {{ $medProtest->protest?->cenPlan }}</p>
                            <p class="mb-1"><strong>Status Usuario:</strong> {{ $medProtest->protest?->statUsuar }}
                            </p>
                            <p class="mb-1"><strong>Causa:</strong> {{ $medProtest->protest?->descCausa }}</p>
                            <p class="mb-1"><strong>Sub Causa:</strong> {{ $medProtest->protest?->descSubCausa }}</p>
                            <p class="mb-1"><strong>Anexar Evidência Obrigatória?:</strong>
                                {{ $medProtest->needsEvidence ? 'SIM' : 'NÃO' }}</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100 border-secondary">
                            <h6 class="text-muted mb-2 text-primary">DATAS RECLAMAÇÃO:</h6>
                            <p class="mb-1"><strong>Data Abertura:</strong>
                                {{ $medProtest->protest?->dtAberturaNota->format('d/m/Y') }}</p>
                            <p class="mb-1"><strong>Data Conclusão Desejada:</strong>
                                {{ $medProtest->protest?->dtConclusaoDesej->format('d/m/Y') }}</p>
                            <p class="mb-1"><strong>Previsão Conclusão Desejado:</strong>
                                {!! $medProtest->protest?->dtConclusaoDesej < now()
                                    ? '<span class="badge text-bg-danger">VENCIDO</span>'
                                    : '<span class="badge text-bg-success">NO PRAZO</span>' !!}
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100 border-secondary">
                            <h6 class="text-muted mb-2 text-primary">DATAS DESTA MEDIDA:</h6>
                            <p class="mb-1"><strong>Data Abertura:</strong>
                                {{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}</p>
                            <p class="mb-1"><strong>Data Conclusão Desejada:</strong>
                                {{ $medProtest->dtFimMedidaDesej?->format('d/m/Y') }}</p>
                            <p class="mb-1"><strong>Previsão Conclusão Desejado:</strong>
                                {!! $medProtest->dtFimMedidaDesej && $medProtest->dtFimMedidaDesej < now()
                                    ? '<span class="badge text-bg-danger">VENCIDO</span>'
                                    : ($medProtest->dtFimMedidaDesej
                                        ? '<span class="badge text-bg-success">NO PRAZO</span>'
                                        : '<span class="badge text-bg-secondary">NÃO ESPECIFICADO</span>') !!}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="border rounded p-3 border-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="text-muted mb-2 text-primary">NOTAS ASSOCIADAS:</h6>
                                <button class="btn btn-sm btn-primary" title="Adicionar Item" data-bs-toggle="tooltip">
                                    <i class="ri-add-box-fill fs-6 align-middle text-center"></i>
                                </button>
                            </div>
                            @if ($medProtest->notes->isNotEmpty())
                                <table class="table table-condensed table-striped table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nota</th>
                                            <th>Rubrica</th>
                                            <th>Municipio</th>
                                            <th>Material</th>
                                            <th>Descrição</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($medProtest->notes as $note)
                                            <tr>
                                                <td>{{ $note->note }}</td>
                                                <td>{{ $note->rubrica }}</td>
                                                <td>{{ $note->lexp }}</td>
                                                <td>{{ $note->material }}</td>
                                                <td>{{ $note->description }}</td>
                                                <td>{{ $note->nstats }}</td>
                                                <td>
                                                    <i class="ri-delete-bin-fill fs-5 align-middle text-danger"
                                                        style="cursor: pointer;" title="Remover Item"
                                                        data-bs-toggle="tooltip"></i>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="card text-bg-info mt-2">
                                    <div class="card-body">
                                        <p class="mb-0 text-center">Nenhuma nota associada a esta medida.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <textarea class="form-control border-secondary @error('comment') is-invalid @enderror" placeholder="Deixe um comentário"
                                id="floatingTextarea" style="height: 150px" wire:model.defer="comment"></textarea>
                            @error('comment')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <label for="floatingTextarea">ADICIONAR COMENTÁRIOS</label>
                        </div>
                        <div class="mt-2 text-end">
                            <button type="submit" class="btn btn-primary" wire:click="addComment">
                                <i class="ri-send-plane-fill me-1"></i> Enviar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-8 h-100" style='min-height: 150px;'>
                        <div class="border rounded p-3 border-secondary h-100" style='min-height: 150px;'>
                            <h6 class="mb-0 text-dark">
                                <i class="bi bi-chat-dots me-2"></i>COMENTÁRIOS:
                                <span
                                    class="badge bg-secondary ms-2 align-middle">{{ $medProtest->comments?->count() }}</span>
                            </h6>
                            <div class="card my-2">
                                <div class="card-body"
                                    style="max-height: 300px; overflow-y: auto; scrollbar-width: thin;">
                                    <div class="comment-container">
                                        @if ($medProtest->comments->isNotEmpty())
                                            @foreach ($medProtest->comments as $comment)
                                                <div class="comment-item py-2 border-bottom border-secondary">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="d-flex gap-2">
                                                            <div class="comment-avatar">
                                                                <i
                                                                    class="ri-user-line fs-4 text-primary align-middle"></i>
                                                            </div>
                                                            <div class="comment-content">
                                                                <div
                                                                    class="d-flex justify-content-between align-items-center w-100">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                                            style="cursor:pointer"></i>
                                                                        <span
                                                                            class="fw-bold text-primary">{{ $comment->user->name }}</span>
                                                                        <small class="text-muted">
                                                                            <i class="ri-time-line align-middle"></i>
                                                                            {{ $comment->created_at->diffForHumans() }}
                                                                        </small>
                                                                    </div>
                                                                    @if ($comment->user_id == auth()->id() && $comment->created_at->diffInMinutes() < 5 && $loop->last)
                                                                        <i class="ri-delete-bin-fill text-danger"
                                                                            style="cursor: pointer;"
                                                                            title="Excluir comentário"
                                                                            wire:click="removeComment({{ $comment->id }})"></i>
                                                                    @endif

                                                                </div>
                                                                <p class="mb-0 text-secondary mt-1">
                                                                    {!! $comment->message !!}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach

                                        @endif
                                        {{-- <div class="comment-item py-2">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="d-flex gap-2">
                                                    <div class="comment-avatar">
                                                        <i class="ri-user-line fs-4 text-primary align-middle"></i>
                                                    </div>
                                                    <div class="comment-content">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center w-100">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                                    style="cursor:pointer"></i>
                                                                <span class="fw-bold">Usuário 2</span>
                                                                <small class="text-muted">
                                                                    <i class="ri-time-line align-middle"></i>
                                                                    há 1 dia
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <p class="mb-0 text-secondary mt-1">
                                                            Outro comentário de exemplo.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> --}}
                                    </div>

                                    <style>
                                        .comment-container::-webkit-scrollbar {
                                            width: 5px;
                                        }

                                        .comment-container::-webkit-scrollbar-track {
                                            background: #f1f1f1;
                                        }

                                        .comment-container::-webkit-scrollbar-thumb {
                                            background: #888;
                                            border-radius: 5px;
                                        }
                                    </style>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-0 mt-0 shadow-sm border-top-0 rounded-top-0 ">
            <div class="card-body">
                <div class="border rounded py-3 border-secondary">
                    <h6 class="text-muted mb-2 ms-2 text-primary">LISTA DE ITENS:</h6>
                    <table class="table table-condensed table-striped table-sm table-hover ">
                        <thead class="text-center align-middle">
                            <tr>
                                <th>#</th>
                                <th></th>
                                <th>Status</th>
                                <th>Descrição</th>
                                <th>Data Criação</th>
                                <th>Data Prazo</th>
                                <th>Data Conclusão</th>
                                <th>Responsável</th>
                                <th>Executor</th>
                                <th>Situação</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-center align-middle table-warning">
                                <td class="fw-bold">001</td>
                                <td class="fw-bold">
                                    <i class="ri-eye-line fs-5 align-middle text-primary" data-bs-toggle="tooltip"
                                        data-bs-placement="top" title="Em acompanhamento"></i>
                                </td>
                                <td>
                                    <span class="badge text-bg-success">ABERTO</span>
                                </td>
                                <td>Descrição do item 1</td>
                                <td class="text-bg-secondary">01/01/2024</td>
                                <td>31/01/2024</td>
                                <td class="text-bg-secondary">-</td>
                                <td>Responsável 1</td>
                                <td>Executor 1</td>
                                <td>Pendente</td>
                                <td>
                                    <i class="ri-play-circle-fill fs-5 align-middle text-success"
                                        style="cursor: pointer;"></i>
                                </td>
                            </tr>
                            <tr class="text-center align-middle">
                                <td class="fw-bold">002</td>
                                <td class="fw-bold"></td>
                                <td>
                                    <span class="badge text-bg-secondary">FECHADO</span>
                                </td>
                                <td>Descrição do item 2</td>
                                <td class="text-bg-secondary">15/01/2024</td>
                                <td>28/01/2024</td>
                                <td class="text-bg-secondary">25/01/2024</td>
                                <td>Responsável 2</td>
                                <td>Executor 2</td>
                                <td>Concluída</td>
                                <td>
                                    <i class="ri-eye-fill fs-5 align-middle text-primary"
                                        style="cursor: pointer;"></i>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Componentes Livewire podem ser adicionados aqui --}}

    </div>

</div>
