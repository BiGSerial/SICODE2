{{-- resources/views/livewire/fivenote/index.blade.php --}}
<div x-data="fiveListPage()" class="five-list-page d-flex flex-column">
    {{-- === CSS escopado === --}}
    <style>
        .five-list-page .card-soft {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, .06);
        }

        .five-list-page .card-header-slim {
            background: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }

        .five-list-page .c-badge {
            display: inline-block;
            padding: .25rem .5rem;
            font-size: .75rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #3730a3;
        }

        .five-list-page .toolbar .form-control,
        .five-list-page .toolbar .form-select {
            border-radius: 12px;
        }

        .five-list-page .split {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 16px;
        }

        @media (max-width: 1200px) {
            .five-list-page .split {
                grid-template-columns: 1fr;
            }
        }

        /* tabela */
        .five-list-page table thead th {
            background: #0ea5e9;
            color: #fff;
            border: 0;
            font-weight: 600;
            vertical-align: middle;
        }

        .five-list-page table tbody tr {
            cursor: pointer;
            transition: background .15s ease;
        }

        .five-list-page table tbody tr:hover {
            background: rgba(14, 165, 233, .08);
        }

        .five-list-page .cell-tight {
            white-space: nowrap;
        }

        .five-list-page .cell-wrap {
            white-space: normal;
        }

        /* painel lateral / galeria */
        .five-list-page .aside {
            background: linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border-radius: 16px;
            padding: 16px;
        }

        .five-list-page .aside h6 {
            margin: 0 0 10px;
            color: #f9fafb;
            font-weight: 700;
        }

        .five-list-page .thumb-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .five-list-page .thumb {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, .08);
            background: rgba(255, 255, 255, .04);
        }

        .five-list-page .thumb img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }

        .five-list-page .thumb .caption {
            position: absolute;
            left: 6px;
            bottom: 6px;
            background: rgba(0, 0, 0, .45);
            color: #fff;
            font-size: .75rem;
            padding: 2px 6px;
            border-radius: 6px;
        }

        /* modal finalizar */
        .five-list-page .modal.finish .modal-content {
            background: linear-gradient(145deg, #1f2937, #0f172a);
            color: #e5e7eb;
            border: 0;
            border-radius: 16px;
        }

        .five-list-page .modal.finish .modal-header,
        .five-list-page .modal.finish .modal-footer {
            border: 0;
            background: rgba(31, 41, 55, .95);
        }

        .five-list-page .modal.finish .btn-close {
            filter: invert(1);
            opacity: .8;
        }

        .five-list-page .btn-soft {
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, .08);
            background: #374151;
            color: #f3f4f6;
        }

        .five-list-page .btn-soft:hover {
            background: #4b5563;
        }

        /* chips tempo */
        .five-list-page .chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 600;
        }

        .five-list-page .chip.warn {
            background: #fef3c7;
            color: #92400e;
        }

        .five-list-page .chip.ok {
            background: #dcfce7;
            color: #166534;
        }

        .five-list-page .chip.danger {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>

    <x-show-loading />
    {{-- === Barra de filtros === --}}
    <div class="my-2">
        <div class="card card-soft">
            <div class="card-header card-header-slim">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="m-0 d-flex align-items-center gap-2">
                        <i class="ri-file-list-3-line text-primary"></i>
                        D5 • Aguardando Execução
                    </h5>
                    {{-- <div class="text-muted small">Layout-base (placeholders) • substitua pelos binds Livewire</div> --}}
                </div>
            </div>
            <div class="card-body toolbar">
                <div class="row g-2">
                    {{-- Busca livre --}}
                    <div class="col-12 col-md-4 position-relative">
                        <input class="form-control" placeholder="Buscar por nota, PEP, motivo..."
                            wire:model.defer="search" x-model="filters.q" @keydown.enter="$wire.call('toSearch')">
                        {{-- botão abre modal de busca múltipla --}}
                        <button type="button"
                            class="btn btn-outline-secondary position-absolute end-0 top-50 translate-middle-y me-2"
                            data-bs-toggle="modal" data-bs-target="#multiSearchModal" title="Busca múltipla">
                            <i class="ri-checkbox-multiple-blank-line"></i>
                        </button>
                    </div>

                    {{-- Mês --}}
                    <div class="col-6 col-md-2">
                        <input type="month" class="form-control" wire:model.defer="month" x-model="filters.month">
                    </div>

                    {{-- Data início / fim --}}
                    <div class="col-6 col-md-2">
                        <input type="date" class="form-control" wire:model.defer="startDate" x-model="filters.start">
                    </div>
                    <div class="col-6 col-md-2">
                        <input type="date" class="form-control" wire:model.defer="endDate" x-model="filters.end">
                    </div>

                    {{-- Ações --}}
                    <div class="col-6 col-md-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" @click="apply()" wire:click="toSearch()">
                            <i class="ri-search-line me-1"></i> Buscar
                        </button>
                        <button class="btn btn-outline-secondary" wire:click='toClean()'>
                            <i class="ri-eraser-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- === Split: Tabela + Galeria === --}}
    <div class="flex-grow-1 px-1">
        <div class="split">
            {{-- LISTA --}}
            @if ($fives->isNotEmpty())

                <div class="card card-soft">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width:52px;"></th>
                                        <th>Nota D5</th>
                                        <th>Note</th>
                                        <th>Orders</th>
                                        <th>PEP</th>
                                        <th>Motivo</th>
                                        <th>Codificação</th>
                                        {{-- <th>Detalhes</th> --}}
                                        <th class="text-center">Despachado em</th>
                                        <th class="text-center">Dias</th>
                                        <th class="text-center">Empresa</th>
                                        <th class="text-center" style="width:56px;">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @if (!function_exists('getOrder'))
                                        @php
                                            function getOrder($note): string
                                            {
                                                return $note->Orders?->sortBy('ordem')->first()?->ordem;
                                            }
                                        @endphp
                                    @endif

                                    @if ($fives->isNotEmpty())
                                        @foreach ($fives as $index => $five)
                                            <tr @click="selectRow({{ $five->id }})"
                                                :class="rowClass({{ $five->id }})">
                                                <td class="text-center">
                                                    <span class="c-badge">#{{ $index + 1 }}</span>
                                                </td>
                                                <td class="cell-tight">{{ $five->note_d5 }}</td>
                                                <td class="cell-tight">{{ $five->note->note }}</td>
                                                <td class="cell-tight">{{ getOrder($five->note) }}</td>
                                                <td class="cell-tight">{{ $five->pep }}</td>
                                                <td class="cell-tight">{{ $five->reason }}</td>
                                                <td class="cell-tight">{{ $five->codify }}</td>
                                                {{-- <td class="cell-wrap">{!! $five->description !!}</td> --}}
                                                <td class="text-center cell-tight">
                                                    {{ $five->dispatch_at?->format('d/m/Y H:i') }}</td>
                                                <td class="text-center">
                                                    <span class="chip danger"><i class="ri-timer-line"></i>
                                                        {{ \Carbon\Carbon::parse($five->dispatch_at)?->diffInDays() }}</span>
                                                </td>
                                                <td class="text-center">
                                                    {{ $five->company?->name }}
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-success"
                                                        wire:click="$emitTo('partner.five-note.actions.finish-d5', 'getInfoResponse', {{ $five->id }})">
                                                        <i class="ri-play-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif



                                </tbody>
                            </table>
                        </div>

                        {{-- Paginação (placeholder) --}}
                        <div class="d-flex justify-content-between align-items-center px-3 py-3">

                            {{ $fives->links() }}
                            <small class="text-muted">
                                @if ($fives->total() > 0)
                                    Exibindo {{ $fives->firstItem() }} - {{ $fives->lastItem() }} de
                                    {{ $fives->total() }} registros
                                @else
                                    Nenhum registro encontrado
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            @else
                <div class="card card-soft">
                    <div class="card-body p-0">
                        <div class="text-center py-5 text-secondary">
                            <i class="ri-folder-2-line d-block fs-2 mb-2"></i>
                            <div>SEM D5 PARA EXECUÇÃO.</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ASIDE GALERIA --}}
            <aside class="aside" x-show="selected">
                <h6>
                    <i class="ri-image-2-line me-1"></i>
                    Arquivos referenciais {{ $charged?->note_d5 }}

                </h6>

                @isset($charged)
                    @php
                        // Usa optional() para não quebrar se o relacionamento ainda não foi carregado
                        $files = optional($charged)->EvidenceFiles ?? collect();
                    @endphp

                    @if ($files->count())
                        <x-files.attachments :files="$files" :downloadAction="'downloadFile'" :showHeader="false" :card="false"
                            wire:key="files-{{ $charged->id }}" />
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="ri-folder-open-line fs-4 mb-2 d-block"></i>
                            <small>Sem arquivos referenciais.</small>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="ri-folder-open-line fs-4 mb-2 d-block"></i>
                        <small>Selecione um item para ver os arquivos</small>
                    </div>
                @endisset
            </aside>
        </div>
    </div>

    {{-- === Modal Encerrar (play) === --}}
    {{-- <div class="modal fade finish" id="finishFiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="ri-check-double-line me-1"></i> Finalizar atividade</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Confirma o encerramento desta D5?</p>
                    <ul class="mb-0 small text-muted">
                        <li>Será registrada a data/hora de conclusão.</li>
                        <li>Você poderá anexar evidências no registro.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-light btn-soft" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-success"><i class="ri-checkbox-circle-line me-1"></i> Encerrar</button>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- === Modal Busca múltipla (textarea 15 linhas) === --}}
    <div class="modal fade" id="multiSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-file-search-line me-1"></i> Busca Multi-notas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-0">
                    <textarea class="form-control border-0" rows="15" wire:model.defer="multiSearch"
                        placeholder="Cole aqui as notas/OV (uma por linha)"></textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" wire:click="multiSearch"><i class="ri-search-line me-1"></i>
                        Buscar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- LIVEWIRE COMPONENTS --}}
    @livewire('partner.five-note.actions.finish-d5')

    {{-- === Alpine: estado local apenas para protótipo === --}}
    <script>
        function fiveListPage() {
            return {
                filters: {
                    q: '',
                    month: '',
                    start: '',
                    end: ''
                },
                selected: null,
                gallery: {
                    1: [{
                            src: 'https://picsum.photos/seed/a/600/400',
                            name: 'foto_1.jpg'
                        },
                        {
                            src: 'https://picsum.photos/seed/b/600/400',
                            name: 'croqui.png'
                        },
                        {
                            src: 'https://picsum.photos/seed/c/600/400',
                            name: 'frente_posto.jpg'
                        },
                    ],
                    2: [],
                    3: [{
                            src: 'https://picsum.photos/seed/d/600/400',
                            name: 'detalhe_1.jpg'
                        },
                        {
                            src: 'https://picsum.photos/seed/e/600/400',
                            name: 'detalhe_2.jpg'
                        },
                    ],
                },
                selectRow(id) {
                    this.selected = id;
                    this.$wire.chargeFiles(id);
                    this.$wire.emit('refresh_component');
                },
                rowClass(id) {
                    return this.selected === id ? 'table-primary' : '';
                },
                apply() {
                    /* TODO: emitir evento Livewire para filtrar */
                },
                resetFilters() {
                    this.filters = {
                        q: '',
                        month: '',
                        start: '',
                        end: ''
                    }; /* TODO: emitir reset */
                },
            }
        }
    </script>
</div>
