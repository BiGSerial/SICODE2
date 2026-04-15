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
        </style>

        <div class="oexterno-header">
            <div class="d-flex flex-column">
                <h2>Lista para Cancelamento</h2>
                <span class="meta">Solicitações disponíveis para assumir.</span>
            </div>
        </div>

        <div class="oexterno-card p-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                <strong class="me-auto">Consulta em massa</strong>
                <button class="btn btn-outline-primary btn-sm" wire:click="exportToExcel" wire:loading.attr="disabled">
                    <i class="ri-file-excel-2-line align-middle"></i> Exportar
                </button>
                <button class="btn btn-outline-success btn-sm" wire:click="claimSelected">
                    Assumir selecionadas
                </button>
            </div>
            <textarea class="form-control mb-3" rows="2"
                placeholder="Notas/Ordens (separe por vírgula, espaço ou linha)"
                wire:model.debounce.600ms="multiSearch"></textarea>

            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input"
                                    wire:model="selectAll"
                                    wire:change="setSelectAll(@json($requests->pluck('id')->all()))">
                            </th>
                            <th>#</th>
                            <th>Nota</th>
                            <th>Ordens</th>
                            <th>Escopo</th>
                            <th>Categoria</th>
                            <th>Solicitante</th>
                            <th>Solicitado em</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input" wire:model="selected" value="{{ $request->id }}">
                                </td>
                                <td>{{ $request->id }}</td>
                                <td>{{ $request->Note->note ?? '-' }}</td>
                                <td>{{ $request->Orders->pluck('ordem')->implode(', ') }}</td>
                                <td>
                                    <span class="badge {{ $request->scope?->badgeClass() ?? 'bg-secondary' }}">
                                        {{ $request->scope?->label() ?? $request->scope }}
                                    </span>
                                </td>
                                <td>{{ $request->Category->name ?? '-' }}</td>
                                <td>{{ $request->Requester->name ?? '-' }}</td>
                                <td>{{ optional($request->submitted_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-success" wire:click="claim({{ $request->id }})">Assumir</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Nenhuma solicitação disponível.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                <div class="small text-muted">
                    Total: <strong>{{ $requests->total() }}</strong>
                    @if($requests->total() > 0)
                        | Exibindo {{ $requests->firstItem() }}-{{ $requests->lastItem() }}
                    @endif
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0">Por página</label>
                    <select class="form-select form-select-sm w-auto" wire:model="perPage">
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="150">150</option>
                        <option value="200">200</option>
                        <option value="250">250</option>
                    </select>
                </div>
            </div>
            <div class="mt-2">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
