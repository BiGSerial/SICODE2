{{-- Aba "Informes": todos os informes registrados para a obra (somente leitura). --}}
<div class="table-card mb-3">
    <div class="od-section-head">
        <div>
            <h3><i class="ri-file-list-3-line text-muted"></i> Informes registrados da obra</h3>
            <div class="small text-muted mt-1">{{ $informs->count() }} informe(s) · inclui cancelados e ADS tácita</div>
        </div>
    </div>
    <div class="table-responsive dash-scroll">
        <table class="table table-sm align-middle mb-0 dash-table" style="min-width: 900px;">
            <thead>
                <tr>
                    <th class="ps-3">Informe</th>
                    <th>Data</th>
                    <th>Empresa / informante</th>
                    <th>Situação</th>
                    <th>Ordens</th>
                    <th class="text-end">Valor da ADS</th>
                    <th>ADS tácita</th>
                    <th class="pe-3 text-center">Anexos</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($informs as $informe)
                    <tr wire:key="informe-{{ $informe['id'] }}" @class(['text-muted' => $informe['canceled']])>
                        <td class="ps-3"><div class="ord-main">#{{ $informe['id'] }}</div>@if ($informe['description'] || $informe['observation'])<div class="ord-sub text-truncate" style="max-width: 260px;" title="{{ $informe['description'] ?: $informe['observation'] }}">{{ $informe['description'] ?: $informe['observation'] }}</div>@endif</td>
                        <td class="small text-nowrap">{{ $informe['date']?->format('d/m/Y') ?? '—' }}@if ($informe['informed_at'])<div class="ord-sub">informado {{ $informe['informed_at']->format('d/m/Y H:i') }}</div>@endif</td>
                        <td><div class="fw-semibold small">{{ $informe['company'] ?? '—' }}</div><div class="ord-sub">{{ $informe['informer'] ?? '—' }}</div></td>
                        <td><span class="badge {{ $informe['status_class'] }}">{{ $informe['status_label'] }}</span></td>
                        <td class="small">{{ $informe['orders'] ? implode(', ', $informe['orders']) : '—' }}</td>
                        <td class="small text-end text-nowrap">{{ \App\Services\Closure\ClosureInformValue::format($informe['amount']) }}</td>
                        <td class="small">
                            @if ($informe['tacit'])
                                <span class="badge bg-warning text-dark">Tácita</span>
                                @if ($informe['tacit_due_at'])<div class="ord-sub">prazo {{ $informe['tacit_due_at']->format('d/m/Y') }}</div>@endif
                                @if ($informe['tacit_done_at'])<div class="ord-sub">entregue {{ $informe['tacit_done_at']->format('d/m/Y') }}</div>@endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="pe-3 text-center">
                            @if ($informe['files'])<button type="button" class="btn btn-sm btn-outline-secondary" wire:click="setTab('files')" title="Ver os arquivos da obra"><i class="ri-attachment-2"></i> {{ $informe['files'] }}</button>@else<span class="text-muted">0</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-closure.empty-state class="py-4" icon="ri-file-list-3-line" title="Nenhum informe registrado" text="A parceira ainda não informou esta obra." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
