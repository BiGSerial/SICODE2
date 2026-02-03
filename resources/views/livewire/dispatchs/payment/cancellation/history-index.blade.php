<div class="oexterno-page">
    <div class="container-fluid">
        <x-show-loading />
        <style>
            .oexterno-page {
                --oe-bg: #f6f7fb;
                --oe-surface: #ffffff;
                --oe-ink: #1f2933;
                --oe-muted: #6b7280;
                --oe-accent: #0f766e;
                --oe-border: #e5e7eb;
                background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
                    radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
                    var(--oe-bg);
                padding: 1.5rem 0;
            }

            .oexterno-header {
                background: linear-gradient(120deg, #0f172a, #0f766e 70%);
                color: #f8fafc;
                border-radius: 1rem;
                padding: 1.5rem 2rem;
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
                margin-bottom: 1.5rem;
            }

            .oexterno-card {
                background: var(--oe-surface);
                border: 1px solid var(--oe-border);
                border-radius: 0.9rem;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
            }

            .evidence-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 12px;
            }

            .evidence-card {
                border: 1px solid var(--oe-border);
                border-radius: 0.75rem;
                background: #fff;
                padding: 0.5rem;
                text-align: center;
                box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
            }

            .evidence-thumb {
                width: 100%;
                height: 110px;
                object-fit: cover;
                border-radius: 0.6rem;
            }

            .evidence-name {
                display: block;
                max-width: 100%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        </style>

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Histórico de Cancelamentos</h2>
                <span class="meta">Consulta e exportação das solicitações finalizadas.</span>
            </div>
        </div>

        <div class="oexterno-card p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <strong class="me-auto">Filtros</strong>
                <select class="form-select w-auto" wire:model="status">
                    <option value="">Todos status</option>
                    <option value="DONE">DONE</option>
                    <option value="REJECTED">REJECTED</option>
                    <option value="ABORTED">ABORTED</option>
                </select>
                <input type="date" class="form-control w-auto" wire:model="dateFrom" />
                <input type="date" class="form-control w-auto" wire:model="dateTo" />
                <button class="btn btn-outline-primary btn-sm" wire:click="exportToExcel" wire:loading.attr="disabled">
                    <i class="ri-file-excel-2-line align-middle"></i> Exportar
                </button>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Notas ou Ordens (separe por vírgula, espaço ou linha)</label>
                <textarea class="form-control" rows="2" wire:model.debounce.600ms="multiSearch"></textarea>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Ordens</th>
                            <th>Categoria</th>
                            <th>Solicitante</th>
                            <th>Executor</th>
                            <th>Status</th>
                            <th>Solicitado em</th>
                            <th>Encerrado em</th>
                            <th>Tempo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $request)
                            @php
                                $start = $request->submitted_at ?? $request->created_at;
                                $end = $request->closed_at;
                                $minutes = $start && $end ? $start->diffInMinutes($end) : null;
                            @endphp
                            <tr>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>{{ $request->Orders->pluck('ordem')->implode(', ') }}</td>
                                <td>{{ $request->Category->name ?? '-' }}</td>
                                <td>{{ $request->Requester->name ?? '-' }}</td>
                                <td>{{ $request->Closer->name ?? ($request->Assignee->name ?? '-') }}</td>
                                <td>
                                    <span class="badge {{ $request->status?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $request->status?->label() ?? $request->status?->value ?? $request->status }}
                                    </span>
                                </td>
                                <td>{{ optional($request->submitted_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ optional($request->closed_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $minutes !== null ? $minutes . ' min' : '-' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        wire:click="openNoteDetail({{ $request->id }})">
                                        Nota
                                    </button>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('dispatch.cancellation.show', ['service' => $service, 'request' => $request->id]) }}">Ver</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">Nenhum registro encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $history->links() }}
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="modal_note_detail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhe da Nota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="closeNoteDetail"></button>
                </div>
                <div class="modal-body">
                    @if($noteDetail?->Note)
                        @php
                            $imageExts = ['jpg','jpeg','png','gif','bmp','svg','tiff','webp'];
                            $imageFiles = $noteDetail->EvidenceFiles->filter(function ($file) use ($imageExts) {
                                $ext = strtolower((string) $file->extension);
                                return in_array($ext, $imageExts, true) || str_starts_with((string) $file->mime, 'image/');
                            });
                            $otherFiles = $noteDetail->EvidenceFiles->filter(function ($file) use ($imageExts) {
                                $ext = strtolower((string) $file->extension);
                                return !in_array($ext, $imageExts, true) && !str_starts_with((string) $file->mime, 'image/');
                            });
                        @endphp
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="text-muted small">Nota/OV</div>
                                <div class="fw-semibold">{{ $noteDetail->Note->note }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Cliente</div>
                                <div class="fw-semibold">{{ $noteDetail->Note->client ?? '-' }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Status</div>
                                <div class="fw-semibold">{{ $noteDetail->Note->status ?? $noteDetail->Note->nstats ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Material</div>
                                <div class="fw-semibold">{{ $noteDetail->Note->material ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Município</div>
                                <div class="fw-semibold">{{ $noteDetail->Note->lexp ?? '-' }}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Cancelada</div>
                                <span class="badge {{ $noteDetail->Note->canceled ? 'bg-danger' : 'bg-success' }}">
                                    {{ $noteDetail->Note->canceled ? 'Sim' : 'Não' }}
                                </span>
                            </div>
                            <div class="col-md-6">
                                <div class="text-muted small">Cancelada em</div>
                                <div class="fw-semibold">{{ optional($noteDetail->Note->canceled_at)->format('d/m/Y H:i') ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="ri-alert-line me-2"></i>
                            <div>
                                SICODE: cancelado. SAP: confirme pelo status da ordem.
                                Se não refletir em até 24h, o usuário deve conferir.
                            </div>
                        </div>
                        <hr>
                        <h6 class="mb-2">Ordens</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Ordem</th>
                                        <th>Status Sist.</th>
                                        <th>SAP</th>
                                        <th>Cancelada</th>
                                        <th>Cancelada em</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($noteDetail->Orders as $order)
                                        @php
                                            $sapCanceled = \Illuminate\Support\Str::startsWith($order->statusSist ?? '', 'CANCE');
                                        @endphp
                                        <tr>
                                            <td>{{ $order->ordem }}</td>
                                            <td>{{ $order->statusSist ?? '-' }}</td>
                                            <td>
                                                <span class="badge {{ $sapCanceled ? 'bg-success' : 'bg-warning text-dark' }}">
                                                    {{ $sapCanceled ? 'Cancelado SAP' : 'Pendente SAP' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge {{ $order->canceled ? 'bg-danger' : 'bg-success' }}">
                                                    {{ $order->canceled ? 'Sim' : 'Não' }}
                                                </span>
                                            </td>
                                            <td>{{ optional($order->canceled_at)->format('d/m/Y H:i') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Nenhuma ordem vinculada.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <hr>
                        <h6 class="mb-2">Evidências</h6>
                        @if($imageFiles->count())
                            <div class="evidence-grid mb-3">
                                @foreach($imageFiles as $file)
                                    <div class="evidence-card">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk($file->disk)->url($file->path) }}"
                                            class="evidence-thumb" alt="{{ $file->original_name }}">
                                        <small class="evidence-name mt-2">{{ $file->original_name }}</small>
                                        <button class="btn btn-sm btn-outline-primary mt-2"
                                            wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        @if($otherFiles->count())
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Arquivo</th>
                                            <th>Tipo</th>
                                            <th>Origem</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($otherFiles as $file)
                                            <tr>
                                                <td class="evidence-name">{{ $file->original_name }}</td>
                                                <td>{{ strtoupper($file->extension ?? '-') }}</td>
                                                <td>{{ $file->origin ?? '-' }}</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        wire:click="downloadEvidence({{ $file->id }})">Baixar</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @else
                        <div class="text-muted">Nenhuma nota carregada.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
