<div>

    {{-- Carrega o Loading da página --}}
    <x-show-loading />



    <div class="card mb-0 shadow rounded-bottom-0" style='z-index: 1;'>
        <div
            class="card-header {{ $protest->tipoNota == 'OU' ? 'text-bg-danger' : 'edp-bg-sprucegreen-70 text-edp-verde' }} py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Reclamação #{{ $protest->nota }}</h5>
                <span class="badge bg-light text-primary">{{ $protest->tipoNota }}</span>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-3" style="min-height: 200px;">
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 border-secondary">
                        <h6 class="text-muted mb-2 text-primary">INFORMAÇÕES BÁSICAS</h6>
                        <p class="mb-1"><strong>Nota:</strong> {{ $protest->nota }}</p>
                        <p class="mb-1"><strong>Municipio:</strong> {{ $protest->cidade }}</p>
                        <p class="mb-1"><strong>Grupo:</strong> {{ $protest->txtGrpCodificacao }}</p>
                        <p class="mb-1"><strong>Causa:</strong> {{ $protest->descCausa }}</p>
                        <p class="mb-1"><strong>SubCausa:</strong> {{ $protest->descSubCausa }}</p>
                    </div>
                </div>
                @php
                    if ($protest->dtConclusaoDesej->isPast()) {
                        $status['color'] = 'badge bg-danger';
                        $status['text'] = 'Vencida'; # code...
                    } elseif ($protest->dtConclusaoDesej?->addDays(3) < now()) {
                        $status['color'] = 'badge bg-success';
                        $status['text'] = 'No Prazo';
                    } else {
                        $status['color'] = 'badge bg-warning';
                        $status['text'] = 'Vencendo';
                    }
                @endphp

                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 border-secondary">
                        <h6 class="text-muted mb-2 text-primary">DATAS:</h6>
                        <p class="mb-1"><strong>Abertura:</strong>
                            {{ $protest->dtAberturaNota?->format('d/m/Y') }}</p>
                        <p class="mb-1"><strong>Conclusão Prevista:</strong>
                            {{ $protest->dtConclusaoDesej?->format('d/m/Y') }}</p>
                        <p class="mb-1"><strong>Status:</strong>
                            <span class="badge {{ $status['color'] }}">{{ $status['text'] }}</span>
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 h-100 border-secondary">
                        <h6 class="text-muted mb-2 text-primary">STATUS</h6>
                        <p class="mb-1">
                            <strong>Total Medidas:</strong>
                            <span class="badge bg-secondary">{{ $protest->medProtests?->count() }}</span>
                        </p>
                        <p class="mb-1">
                            <strong>Última Movimentação:</strong>
                            <span class="badge bg-info">{{ $protest->medProtests?->count() }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="border rounded p-3 border-secondary">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="text-muted mb-2 text-primary">OBRAS ASSOCIADAS:</h6>
                            <button class="btn btn-sm btn-primary" title="Associar Nota/OV" data-bs-toggle="tooltip"
                                wire:click.defer="$emitTo('services.oexterno.actions.protest.add-notes-relation', 'openAddNotesRelation', {{ $protest->id }})">
                                <i class="ri-add-box-fill fs-6 align-middle text-center"></i>
                            </button>
                        </div>
                        @if ($protest->Notes->isNotEmpty())
                            <table class="table table-condensed table-striped table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Nota/OV</th>
                                        <th>Cliente</th>
                                        <th>Rubrica</th>
                                        <th>Municipio</th>
                                        <th>Descrição</th>

                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($protest->Notes as $note)
                                        <tr>
                                            <td>{{ $note->note }}</td>
                                            <td>{{ $note->client }}</td>
                                            <td>{{ $note->rubrica }}</td>
                                            <td>{{ $note->lexp }}</td>
                                            <td>{{ $note->material }}</td>

                                            <td>{{ $note->type_note == 2 ? $note->nstats : $note->centerjob }}
                                            </td>
                                            <td>
                                                {{-- <i class="ri-play-circle-fill fs-5 align-middle text-primary"
                                                        style="cursor: pointer;"></i> --}}
                                                <i class="ri-delete-bin-fill fs-5 align-middle text-danger"
                                                    style="cursor: pointer;" title="Remover Associação"
                                                    data-bs-toggle="tooltip"
                                                    wire:click.prevent="removeNoteFromProtest({{ $note->id }})"></i>
                                            </td>
                                        </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        @else
                            <h4 class="align-middle text-center my-2">Nenhuma Nota/OV Associada</h4>
                        @endif
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">

                    <div class="form-floating">
                        <textarea class="form-control border-secondary" placeholder="Leave a comment here" id="floatingTextarea"
                            style="height: 150px" wire:model.defer="comment"></textarea>
                        <label for="floatingTextarea">ADICIONAR COMENTÁRIOS</label>
                    </div>
                    <div class="mt-2 text-end">
                        <button type="submit" class="btn btn-primary" wire:click.prevent="addComment">
                            <i class="ri-send-plane-fill me-1"></i> Enviar
                        </button>
                    </div>

                </div>
                <div class="col-md-8 h-100" style='min-height: 150px;'>
                    {{-- Verifica se há observações para a nota --}}
                    <div class="border rounded p-3 border-secondary h-100" style='min-height: 150px;'>
                        <h6 class="mb-0 text-dark">
                            <i class="bi bi-chat-dots me-2"></i>OBSERVAÇÔES PARA {{ $protest->nota }}:
                            @if ($protest->comments->isNotEmpty())
                                <span
                                    class="badge bg-secondary ms-2 align-middle">{{ $protest->comments->count() }}</span>
                            @endif
                        </h6>
                        <div class="card my-2">
                            <div class="card-body" style="max-height: 300px; overflow-y: auto; scrollbar-width: thin;">
                                {{-- Verifica se há comentários --}}
                                @forelse($protest->comments as $comment)
                                    <div class="comment-container">
                                        <div
                                            class="comment-item py-2 {{ !$loop->last ? 'border-bottom border-secondary' : '' }}">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="d-flex gap-2">
                                                    <div class="comment-avatar">
                                                        <i class="ri-user-line fs-4 text-primary align-middle"></i>
                                                    </div>
                                                    <div class="comment-content">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center w-100">
                                                            <div class="d-flex align-items-center gap-2">
                                                                @if ($comment->user?->email)
                                                                    <i class="bx bxl-microsoft-teams text-primary fs-4 align-middle"
                                                                        style="cursor:pointer"
                                                                        onclick="window.open('msteams://teams.microsoft.com/l/chat/0/0?users={{ $comment->user?->email }}', '_blank')">
                                                                    </i>
                                                                @endif
                                                                <span
                                                                    class="fw-bold {{ $comment->user_id === auth()->user()->id ? 'text-primary' : '' }}">{{ $comment->user->name }}</span>
                                                                <small class="text-muted">
                                                                    <i class="ri-time-line align-middle"></i>
                                                                    {{ $comment->created_at->diffForHumans() }}
                                                                </small>
                                                            </div>
                                                            @if (
                                                                ($comment->created_at->diffInHours() < 1 && $comment->id === $protest->comments->max('id')) ||
                                                                    auth()->user()->admin ||
                                                                    auth()->user()->superadm)
                                                                <i class="ri-delete-bin-fill text-danger"
                                                                    style="cursor: pointer;"
                                                                    wire:click="deleteComment({{ $comment->id }})"
                                                                    title="Excluir comentário"></i>
                                                            @endif
                                                        </div>
                                                        <p class="mb-0 text-secondary mt-1">
                                                            {{ $comment->message }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                                @empty
                                    <div class="alert alert-info text-center mb-0 mt-3" role="alert">
                                        <i class="bi bi-info-circle me-2"></i> Não há observações para exibir.
                                    </div>
                                @endforelse
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
                <h6 class="text-muted mb-2 ms-2 text-primary">MEDIDAS:</h6>
                <table class="table table-condensed table-striped table-sm table-hover ">
                    <thead class="text-center align-middle">
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Descrição</th>
                            <th>Data Criação</th>
                            <th>Data Fim Desejada</th>
                            <th>Data Fim</th>
                            <th>Acompanhado Por</th>
                            <th>Serviço</th>
                            <th>Responsável</th>
                            <th>Situação</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($protest->medProtests?->sortByDesc('dtCriacaoMedida') as $medProtest)
                            <tr class="text-center align-middle">
                                <td class="fw-bold">
                                    {{ $medProtest->med_id }}
                                </td>
                                <td>
                                    @if ($medProtest->statusSist === 'MEDA')
                                        <span class="badge text-bg-success">ABERTO</span>
                                    @else
                                        <span class="badge text-bg-secondary">FECHADO</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $medProtest->txtCodMedida }}
                                </td>
                                <td class="text-bg-secondary">
                                    {{ $medProtest->dtCriacaoMedida?->format('d/m/Y') }}
                                </td>
                                <td>
                                    {{ $medProtest->dtFimMedidaDesej?->format('d/m/Y') }}
                                </td>
                                <td class="text-bg-secondary">
                                    {{-- Use null-safe operator to avoid errors if dtFimMedida is null --}}
                                    {{ $medProtest->dtFimMedida?->format('d/m/Y') }}
                                </td>
                                <td class="">
                                    ----
                                </td>
                                <td class="">
                                    ----
                                </td>
                                <td class="">
                                    ----
                                </td>
                                <td class="">
                                    ----
                                </td>
                                <td>
                                    @if ($medProtest->statusSist === 'MEDA')
                                        <i class="ri-play-circle-fill fs-5 align-middle text-success"
                                            style="cursor: pointer;"
                                            wire:click.prevent="$emitTo('services.oexterno.actions.protest.control-med-protest', 'openModProtestControl', {{ $medProtest->id }})"></i>
                                    @else
                                        <i class="ri-eye-fill fs-5 align-middle text-primary"
                                            style="cursor: pointer;"></i>
                                    @endif

                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    Nenhuma medida registrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>



    </div>

    {{-- Livewire Components --}}
    @livewire('services.oexterno.actions.protest.add-notes-relation', key('add-notes-relation-' . $protest->id))
    @livewire('services.oexterno.actions.protest.control-med-protest', key('control-med-protest-' . $protest->id))

    {{-- Modal de Controle de Medidas --}}
</div>
