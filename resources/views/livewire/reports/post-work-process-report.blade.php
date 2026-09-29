<div class="py-3">
    <x-show-loading />

    <div class="container-fluid">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3 gap-2">
            <div>
                <h3 class="mb-0">PROCESSO DE MEDIÇÃO – PÓS OBRA</h3>
                <small class="text-muted">Prazos em dias úteis: Despacho Fiscal 2 · Fiscalização 3 · Medição/Pagamento 3 · Total 8</small>
            </div>
            <button class="btn btn-success btn-sm" wire:click="exportReport" wire:loading.attr="disabled" wire:target="exportReport">
                <i class="ri-file-excel-2-line me-1"></i> Exportar
            </button>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-2">
                <label class="form-label small mb-0">Tipo de informe</label>
                <select class="form-select form-select-sm" wire:model="type">
                    <option value="all">Todos</option>
                    <option value="final">Informe Final</option>
                    <option value="partial">Informe Parcial</option>
                    <option value="d5">Informe D5</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Data do informe (de)</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="from">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Data do informe (até)</label>
                <input type="date" class="form-control form-control-sm" wire:model.lazy="to">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Empresa</label>
                <select class="form-select form-select-sm" wire:model="company_id">
                    <option value="">Todas</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Nota</label>
                <input type="text" class="form-control form-control-sm" wire:model.debounce.500ms="search">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="only_late" wire:model="only_late">
                    <label class="form-check-label small" for="only_late">Só estourados</label>
                </div>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4"><div class="card h-100"><div class="card-body py-2"><small class="text-muted">Total</small><div class="fw-bold fs-5">{{ $summary['total'] }}</div></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body py-2"><small class="text-muted">Com prazo estourado</small><div class="fw-bold fs-5 text-danger">{{ $summary['late'] }}</div></div></div></div>
            <div class="col-md-4"><div class="card h-100"><div class="card-body py-2"><small class="text-muted">Dentro do prazo</small><div class="fw-bold fs-5 text-success">{{ $summary['on_time'] }}</div></div></div></div>
        </div>

        @php
            $badge = fn ($stage) => match ($stage['status']) {
                'on_time'   => 'bg-success',
                'late'      => 'bg-danger',
                'open_late' => 'bg-warning text-dark',
                'open'      => 'bg-info text-dark',
                default     => 'bg-secondary',
            };
        @endphp

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Tipo</th>
                            <th>Nota</th>
                            <th>Empresa</th>
                            <th>Data do informe</th>
                            <th>Despacho Fiscal<br><small>≤ 2</small></th>
                            <th>Fiscalização<br><small>≤ 3</small></th>
                            <th>Medição/Pagamento<br><small>≤ 3</small></th>
                            <th>Total<br><small>≤ 8</small></th>
                            <th>Fiscalização (user / empresa)</th>
                            <th>Pagamento (user / empresa)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['type_label'] }}</td>
                                <td>{{ $row['note'] ?? '---' }}@if (!empty($row['note_d5'])) <small class="text-muted d-block">D5 {{ $row['note_d5'] }}</small>@endif</td>
                                <td>{{ $row['company'] ?? '---' }}</td>
                                <td>{{ $row['start_at']?->format('d/m/Y H:i') ?? '---' }}</td>
                                @foreach (['fiscal_dispatch', 'fiscalization', 'measurement', 'total'] as $key)
                                    @php $stage = $row['stages'][$key]; @endphp
                                    <td>
                                        <span class="badge {{ $badge($stage) }}">{{ $stage['days'] ?? '—' }}{{ $stage['days'] !== null ? 'd' : '' }}</span>
                                        @if ($stage['late_days'] > 0)
                                            <small class="text-danger">+{{ $stage['late_days'] }}</small>
                                        @endif
                                    </td>
                                @endforeach
                                <td>{{ $row['fiscal']['user'] ?? '---' }}<small class="text-muted d-block">{{ $row['fiscal']['company'] ?? '' }}</small></td>
                                <td>{{ $row['payment']['user'] ?? '---' }}<small class="text-muted d-block">{{ $row['payment']['company'] ?? '' }}</small></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-4">Nenhum informe encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-2">{{ $rows->links() }}</div>
    </div>
</div>
