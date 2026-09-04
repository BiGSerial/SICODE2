@php
    use App\Helpers\FileIcon;
@endphp

@once
    <style>
        .reclaim-info-modal .modal-dialog {
            max-width: min(1120px, calc(100vw - 1.5rem));
        }

        .reclaim-info-modal .modal-content {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.26);
            max-height: calc(100vh - 1.5rem);
            overflow: hidden;
        }

        .reclaim-info-modal__header {
            align-items: center;
            background: #123f43;
            color: #ffffff;
            display: flex;
            gap: 1rem;
            justify-content: space-between;
            padding: 1rem 1.25rem;
        }

        .reclaim-info-modal__title {
            color: #28ff52;
            font-size: 1rem;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
        }

        .reclaim-info-modal__body {
            background: #eef4f5;
            overflow: auto;
            padding: 1rem;
            scrollbar-color: #0f766e #dbe6e8;
            scrollbar-width: thin;
        }

        .reclaim-info-modal__body::-webkit-scrollbar,
        .reclaim-info-modal__scroll::-webkit-scrollbar {
            height: 10px;
            width: 10px;
        }

        .reclaim-info-modal__body::-webkit-scrollbar-track,
        .reclaim-info-modal__scroll::-webkit-scrollbar-track {
            background: #dbe6e8;
            border-radius: 999px;
        }

        .reclaim-info-modal__body::-webkit-scrollbar-thumb,
        .reclaim-info-modal__scroll::-webkit-scrollbar-thumb {
            background: #0f766e;
            border: 2px solid #dbe6e8;
            border-radius: 999px;
        }

        .reclaim-info-modal__grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1fr);
        }

        .reclaim-info-modal__panel {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
        }

        .reclaim-info-modal__panel-title {
            align-items: center;
            background: #1f555b;
            color: #28ff52;
            display: flex;
            font-size: 0.9rem;
            font-weight: 800;
            gap: 0.45rem;
            margin: 0;
            padding: 0.55rem 0.75rem;
        }

        .reclaim-info-modal__table {
            margin: 0;
        }

        .reclaim-info-modal__table th {
            background: #f8fafc;
            color: #334155;
            font-size: 0.78rem;
            text-align: right;
            vertical-align: middle;
            white-space: nowrap;
            width: 132px;
        }

        .reclaim-info-modal__table td {
            color: #1f2937;
            font-size: 0.82rem;
            vertical-align: middle;
        }

        .reclaim-info-modal__table thead th {
            text-align: center;
        }

        .reclaim-info-modal__note {
            font-weight: 800;
        }

        .reclaim-info-modal__scroll {
            max-height: min(36vh, 340px);
            overflow: auto;
        }

        .reclaim-info-modal__comment {
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            overflow: hidden;
        }

        .reclaim-info-modal__comment-head {
            background: #1f555b;
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 0.4rem 0.65rem;
        }

        .reclaim-info-modal__comment-head.is-own {
            background: #2563eb;
        }

        .reclaim-info-modal__comment-body {
            color: #1f2937;
            font-size: 0.84rem;
            padding: 0.65rem;
        }

        .reclaim-info-modal__comment-time {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 0.4rem 0.65rem;
        }

        .reclaim-info-modal__footer {
            align-items: center;
            background: #ffffff;
            border-top: 1px solid #dbe3ef;
            display: flex;
            justify-content: flex-end;
            padding: 0.85rem 1rem;
        }

        .reclaim-info-modal__footer .btn {
            border-radius: 6px;
            font-weight: 700;
            min-width: 104px;
        }

        .reclaim-info-modal textarea,
        .reclaim-info-modal .form-control {
            border-color: #cbd5e1;
            border-radius: 6px;
            font-size: 0.86rem;
        }

        @media (min-width: 992px) {
            .reclaim-info-modal__grid {
                grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);
            }
        }
    </style>
@endonce

<div>
    <x-show-loading />

    <div wire:ignore.self class="modal fade reclaim-info-modal" id="responserInfo" tabindex="-1"
        aria-labelledby="reclaimInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                @if ($reclaim)
                    @php
                        if ($reclaim->Viabilities->isNotEmpty()) {
                            $origin = 'VIABILIDADE';
                        } elseif ($reclaim->Waiting) {
                            $origin = 'CONTRATACAO';
                        } elseif ($reclaim->Approvals->isNotEmpty()) {
                            $origin = 'VALIDACAO DE PROJETOS';
                        } elseif ($reclaim->Externals->isNotEmpty()) {
                            $origin = 'ENTIDADE EXTERNA';
                        } else {
                            $origin = 'DESCONHECIDO';
                        }
                    @endphp

                    <div class="reclaim-info-modal__header">
                        <h1 class="reclaim-info-modal__title" id="reclaimInfoModalLabel">
                            Informacao de {{ $reclaim->Note->note }}
                        </h1>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Fechar"></button>
                    </div>

                    <div class="reclaim-info-modal__body">
                        <div class="reclaim-info-modal__grid">
                            <div class="d-grid gap-3">
                                <section class="reclaim-info-modal__panel">
                                    <h2 class="reclaim-info-modal__panel-title">
                                        <i class="ri-file-list-3-line"></i>
                                        Dados da Nota
                                    </h2>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped-columns reclaim-info-modal__table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">Nota/OV</th>
                                                    <td class="reclaim-info-modal__note">{{ $reclaim->Note->note }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Ordens</th>
                                                    <td>
                                                        @if ($reclaim->Note->Viabilities->isNotEmpty())
                                                            @foreach ($reclaim->Note->Viabilities as $viability)
                                                                @foreach ($viability->Orders as $order)
                                                                    <div>{{ $order->ordem }}</div>
                                                                @endforeach
                                                            @endforeach
                                                        @elseif ($reclaim->Note->Orders->isNotEmpty())
                                                            @foreach ($reclaim->Note->Orders as $order)
                                                                <div>{{ $order->ordem }}</div>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">---</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Status</th>
                                                    <td>{{ $reclaim->Note->nstats }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Situacao</th>
                                                    <td>{{ $reclaim->Note->status }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Municipio</th>
                                                    <td>{{ $reclaim->Note->lexp }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Rubrica</th>
                                                    <td>{{ $reclaim->Note->rubrica }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Material</th>
                                                    <td>{{ $reclaim->Note->material }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </section>

                                <section class="reclaim-info-modal__panel">
                                    <h2 class="reclaim-info-modal__panel-title">
                                        <i class="ri-attachment-2"></i>
                                        Arquivos
                                    </h2>
                                    @if ($reclaim->Note->Files->count())
                                        <div class="table-responsive reclaim-info-modal__scroll">
                                            <table class="table table-sm table-hover reclaim-info-modal__table">
                                                <thead>
                                                    <tr>
                                                        <th scope="col" style="width: 48px;"></th>
                                                        <th scope="col">Servico</th>
                                                        <th scope="col">Tipo</th>
                                                        <th scope="col">Arquivo</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($reclaim->Note->Files->sortBy('file_name') as $file)
                                                        <tr>
                                                            <td class="text-center">
                                                                <input class="form-check-input border border-1 border-secondary"
                                                                    type="checkbox" value="{{ $file->id }}"
                                                                    wire:model.defer="selectedFiles">
                                                            </td>
                                                            <td>{{ $file->Service->service ?? '' }}</td>
                                                            <td class="text-center">
                                                                <i class="{{ FileIcon::getIcon($file->ext)->icon }} fs-5 align-middle"></i>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-link btn-sm p-0 text-start"
                                                                    wire:click.prevent="downloadFile({{ $file->id }})">
                                                                    {{ $file->file_name }}
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="p-2 border-top">
                                            <button class="btn btn-sm btn-primary" wire:click.prevent="zipFiles">
                                                <i class="ri-download-cloud-2-line align-middle"></i>
                                                Baixar selecionados
                                            </button>
                                        </div>
                                    @else
                                        <div class="p-3 text-center text-muted fw-bold">Sem arquivos</div>
                                    @endif
                                </section>
                            </div>

                            <div class="d-grid gap-3">
                                @if ($reclaim->Viabilities->count() && $reclaim->Viabilities->last()->Form)
                                    @php($form = $reclaim->Viabilities->last()->Form)
                                    <section class="reclaim-info-modal__panel">
                                        <h2 class="reclaim-info-modal__panel-title">
                                            <i class="ri-git-pull-request-line"></i>
                                            Retorno Viabilidade
                                        </h2>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-striped-columns reclaim-info-modal__table">
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">Motivo</th>
                                                        <td class="fw-bold">{{ $form->reason }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Impacto</th>
                                                        <td>{{ $form->changes * 10 }}%</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Responsavel</th>
                                                        <td class="text-uppercase">{{ $form->responsible }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">Descricao</th>
                                                        <td>{{ $form->description }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </section>
                                @endif

                                <section class="reclaim-info-modal__panel">
                                    <h2 class="reclaim-info-modal__panel-title">
                                        <i class="ri-loop-left-line"></i>
                                        Retorno Interno
                                    </h2>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped-columns reclaim-info-modal__table">
                                            <tbody>
                                                <tr>
                                                    <th scope="row">Origem</th>
                                                    <td class="fw-bold">{{ $origin }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Categoria</th>
                                                    <td>{{ $reclaim->Subcategory?->Category?->name ?? '' }}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Motivo</th>
                                                    <td>{{ $reclaim->Subcategory?->name ?? $reclaim->category }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </section>

                                <section class="reclaim-info-modal__panel">
                                    <h2 class="reclaim-info-modal__panel-title">
                                        <i class="ri-chat-3-line"></i>
                                        Comentarios
                                    </h2>
                                    <div class="p-2">
                                        <div class="reclaim-info-modal__scroll">
                                            @forelse ($reclaim->Comments as $index => $comment)
                                                <article class="reclaim-info-modal__comment">
                                                    <div
                                                        class="reclaim-info-modal__comment-head {{ $comment->User?->id == auth()->id() ? 'is-own' : '' }}">
                                                        #{{ $index + 1 }} -
                                                        {{ $comment->User?->id == auth()->id() ? 'Voce' : $comment->User?->name ?? 'Usuario desconhecido' }}
                                                        @if ($comment->User?->id != auth()->id() && $comment->User?->email)
                                                            <span class="fw-normal">({{ $comment->User->email }})</span>
                                                        @endif
                                                    </div>
                                                    <div class="reclaim-info-modal__comment-body">
                                                        {{ $comment->message }}
                                                    </div>
                                                    <div class="reclaim-info-modal__comment-time">
                                                        <i class="ri-time-line align-middle"></i>
                                                        {{ $comment->created_at->format('d/m/Y - H:i:s') }}
                                                    </div>
                                                </article>
                                            @empty
                                                <div class="alert alert-light border mb-0">Sem comentarios registrados.</div>
                                            @endforelse
                                        </div>

                                        <div class="mt-3">
                                            <textarea class="form-control" wire:model.defer="newComment" rows="2"
                                                placeholder="Digite seu comentario..."></textarea>
                                            <button class="btn btn-primary btn-sm mt-2" wire:click="addComment"
                                                wire:loading.attr="disabled" wire:target="addComment">
                                                <span wire:loading.remove wire:target="addComment">
                                                    <i class="ri-send-plane-2-line align-middle"></i>
                                                    Enviar
                                                </span>
                                                <span wire:loading wire:target="addComment">Enviando...</span>
                                            </button>
                                        </div>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="reclaim-info-modal__footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ri-close-line align-middle"></i>
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        (function() {
            let pendingReclaimInfoModal = null;

            function openReclaimInfoModal() {
                if (!pendingReclaimInfoModal) {
                    return;
                }

                const modalEl = document.getElementById(pendingReclaimInfoModal);

                if (!modalEl || !modalEl.querySelector('.reclaim-info-modal__header')) {
                    return;
                }

                pendingReclaimInfoModal = null;
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            window.addEventListener('reclaimInfoLoaded', function(e) {
                pendingReclaimInfoModal = e.detail.id;

                window.requestAnimationFrame(openReclaimInfoModal);
                window.setTimeout(openReclaimInfoModal, 75);
                window.setTimeout(openReclaimInfoModal, 200);
            });

            function registerLivewireHook() {
                if (!window.Livewire || !window.Livewire.hook) {
                    return;
                }

                window.Livewire.hook('message.processed', openReclaimInfoModal);
            }

            registerLivewireHook();
            document.addEventListener('livewire:load', registerLivewireHook);
        })();
    </script>
@endonce
