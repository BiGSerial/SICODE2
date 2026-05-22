@push('css')
    <style>
        .work-rejected-page {
            --surface: #fff;
            --muted: #64748b;
            --border: #dbe2ea;
            --ink: #0f172a;
            --danger-bg: #fff7ed;
            background: #f4f7fb;
            border-radius: 14px;
            padding: 1rem;
        }

        .work-rejected-page .toolbar,
        .work-rejected-page .list-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.07);
        }

        .work-rejected-page .page-title {
            color: var(--ink);
            font-weight: 700;
            margin: 0;
        }

        .work-rejected-page .page-subtitle {
            color: var(--muted);
            font-size: .86rem;
        }

        .work-rejected-page .table thead th {
            color: #334155;
            background: #f8fafc;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .work-rejected-page .table td {
            vertical-align: middle;
            font-size: .86rem;
        }

        .work-rejected-page .row-rejected {
            background: var(--danger-bg);
        }

        .work-rejected-page .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 88px;
            border-radius: 999px;
            padding: .28rem .55rem;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
    </style>
@endpush

<div class="work-rejected-page">
    <x-show-loading />

    <div class="toolbar mb-3">
        <div class="p-3 border-bottom">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 align-items-lg-center">
                <div>
                    <h4 class="page-title">Informes rejeitados</h4>
                    <div class="page-subtitle">Lista de informes devolvidos para correção.</div>
                </div>
                <span class="badge text-bg-danger">{{ $lists->total() }} registros</span>
            </div>
        </div>

        <div class="p-3">
            <div class="row align-items-end g-2">
                <div class="col-12 col-sm-4 col-lg-2">
                    <label for="perPageRejected" class="form-label mb-1">Registros</label>
                    <select name="perPage" id="perPageRejected" class="form-select" wire:model="perPage">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="250">250</option>
                        <option value="500">500</option>
                    </select>
                </div>

                <div class="col-12 col-sm-8 col-lg-8">
                    <label for="searchRejected" class="form-label mb-1">Buscar</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchRejected"
                            placeholder="Nota, OV, ordem, motivo ou usuário" wire:model.debounce.500ms="search">
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                            data-bs-target="#workedRejectedListMultiSearchModal" data-bs-placement="top"
                            data-bs-title="Buscar múltiplos registros">
                            <i class="ri-file-copy-line align-middle"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <button class="btn btn-outline-danger w-100" wire:click.prevent="cleanAll()"
                        data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Limpar busca">
                        <i class="ri-find-replace-line fs-5 align-middle"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if (!$lists->count())
        <div class="list-panel text-center my-4 p-4">
            <h5 class="mb-1">Nenhum informe rejeitado por aqui</h5>
            <div class="text-muted">Ajuste os filtros ou aguarde novos retornos.</div>
        </div>
    @else
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center mb-2">
            <div>{{ $lists->links() }}</div>
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }} registros.
            </div>
        </div>

        <div class="list-panel">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">Status</th>
                            <th class="text-center">Nota/OV</th>
                            <th class="text-center">Ordens</th>
                            <th class="text-center">Rubrica</th>
                            <th class="text-center">Município</th>
                            <th class="text-center">Motivo</th>
                            <th class="text-center">Devolvido por</th>
                            <th class="text-center">Data devolução</th>
                            <th class="text-center">Tempo</th>
                            <th class="text-center">Empreiteira</th>
                            <th class="text-center">Detalhes</th>
                            <th class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lists as $list)
                            @php
                                $latestReturn = $list->LatestReturnwork;
                            @endphp
                            <tr class="row-rejected" wire:key="ret-{{ $list->id }}">
                                <td class="text-center">
                                    <span class="status-badge text-bg-warning">Rejeitado</span>
                                </td>
                                <td class="text-center fw-bold">{{ $list->Note->note }}</td>
                                <td class="text-center">
                                    @forelse ($list->Orders as $order)
                                        <div>{{ $order->ordem }}</div>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td class="text-center">{{ $list->Note->rubrica }}</td>
                                <td class="text-center">{{ $list->Note->lexp }}</td>
                                <td class="text-center text-danger fw-bold">{{ $latestReturn?->category ?: '-' }}</td>
                                <td class="text-center">{{ $latestReturn?->User?->name ?: '-' }}</td>
                                <td class="text-center">
                                    {{ $latestReturn?->created_at ? $latestReturn->created_at->format('d/m/Y H:i:s') : '-' }}
                                </td>
                                <td class="text-center text-primary fw-bold">
                                    {{ $latestReturn?->created_at ? $latestReturn->created_at->diffForHumans(null, true) : '-' }}
                                </td>
                                <td class="text-center">{{ $list->Company?->name ?: '-' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="openRejectDetails({{ $list->id }})" title="Ver detalhes da rejeição">
                                        <i class="ri-information-line align-middle"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                        wire:click="reinform({{ $list->id }})" title="Reinformar">
                                        <i class="ri-play-circle-fill align-middle"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center mt-3">
            <div>{{ $lists->links() }}</div>
            <div class="text-muted small">
                Exibindo {{ $lists->firstItem() }} até {{ $lists->lastItem() }} de {{ $lists->total() }} registros.
            </div>
        </div>
    @endif

    <div class="modal fade" id="workedRejectedListMultiSearchModal" tabindex="-1"
        aria-labelledby="workedRejectedListMultiSearchModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header edp-bg-violeta-100 text-white">
                    <h5 class="modal-title" id="workedRejectedListMultiSearchModalLabel">Buscar múltiplos registros</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label for="multiSearchRejected" class="form-label">Notas, OVs, ordens, motivos ou usuários</label>
                    <textarea id="multiSearchRejected" class="form-control" rows="8"
                        placeholder="Cole um registro por linha, ou separe por vírgula, ponto e vírgula ou espaço"
                        wire:model.defer="multiSearch"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click.prevent="applyMultiSearch"
                        data-bs-dismiss="modal">
                        <i class="ri-search-line align-middle"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectDetailsModal" tabindex="-1" aria-labelledby="rejectDetailsModalLabel"
        aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="rejectDetailsModalLabel">
                        Detalhes da Rejeição
                        @if ($selectedRejectedNote)
                            <span class="text-muted">- Nota/OV {{ $selectedRejectedNote }}</span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    @php
                        $hasSelectedReturnwork = isset($selectedReturnworks[$selectedReturnworkIndex]);
                        $currentReturn = $hasSelectedReturnwork ? $selectedReturnworks[$selectedReturnworkIndex] : null;
                        $totalReturnworks = count($selectedReturnworks);
                        $officialIndex = max(0, $totalReturnworks - 1);
                        $officialReturn = $totalReturnworks > 0 ? $selectedReturnworks[$officialIndex] : null;
                        $isOfficialViewing = $hasSelectedReturnwork && $selectedReturnworkIndex === $officialIndex;
                    @endphp

                    @if ($selectedRejectedCompany)
                        <div class="mb-3">
                            <div class="small text-muted">Empreiteira</div>
                            <div class="fw-bold fs-5 text-dark">{{ $selectedRejectedCompany }}</div>
                        </div>
                    @endif

                    @if ($hasSelectedReturnwork)
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div class="badge text-bg-light text-muted border px-3 py-2">
                                Item {{ $selectedReturnworkIndex + 1 }} de {{ $totalReturnworks }}
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    wire:click="previousRejectDetail" @disabled($selectedReturnworkIndex === 0)>
                                    <i class="ri-arrow-left-s-line"></i> Anterior
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    wire:click="nextRejectDetail" @disabled($selectedReturnworkIndex === $totalReturnworks - 1)>
                                    Próxima <i class="ri-arrow-right-s-line"></i>
                                </button>
                            </div>
                        </div>

                        @if ($totalReturnworks > 1)
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                @foreach ($selectedReturnworks as $index => $returnItem)
                                    <button type="button"
                                        class="btn btn-sm {{ $selectedReturnworkIndex === $index ? ($officialIndex === $index ? 'btn-success' : 'btn-outline-dark') : ($officialIndex === $index ? 'btn-outline-success' : 'btn-outline-secondary') }}"
                                        wire:click="goToRejectDetail({{ $index }})"
                                        title="Ir para rejeição {{ $index + 1 }}{{ $officialIndex === $index ? ' (vigente)' : '' }}">
                                        {{ $index + 1 }}{{ $officialIndex === $index ? ' *' : '' }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="card {{ $isOfficialViewing ? 'border-success' : 'border-light' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-end mb-2">
                                    <span class="badge {{ $isOfficialViewing ? 'text-bg-success' : 'text-bg-light text-muted border' }} px-3 py-2">
                                        {{ $isOfficialViewing ? 'Vigente' : 'Histórico' }}
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Motivo</div>
                                    <div class="fw-semibold text-danger">{{ $currentReturn['category'] ?: 'Não informado' }}</div>
                                </div>

                                <div class="mb-3">
                                    <div class="text-muted small mb-1">Descrição</div>
                                    <div class="bg-light border rounded p-3" style="white-space: pre-wrap;">{{ $currentReturn['text_obs'] ?: 'Sem descrição.' }}</div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">Devolvido por</div>
                                        <div class="fw-semibold">{{ $currentReturn['user_name'] ?: 'Não informado' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-muted small mb-1">Data da devolução</div>
                                        <div class="fw-semibold">{{ $currentReturn['created_at'] ?: '-' }}</div>
                                        @if (!empty($currentReturn['created_human']))
                                            <div class="small text-muted">{{ $currentReturn['created_human'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">
                            Este informe não possui histórico de rejeições detalhado.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:load', function() {
            window.addEventListener('showRejectDetailsModal', function() {
                const modalElement = document.getElementById('rejectDetailsModal');
                if (!modalElement) {
                    return;
                }

                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                modal.show();
            });
        });
    </script>
</div>
