@php
    $statusLabels = [
        'OPEN' => ['Aberta', '#f1f5f9', '#475569'],
        'REQUESTED' => ['Aguardando resposta', '#fef3c7', '#b45309'],
        'RETURNED' => ['Devolvida p/ correção', '#fee2e2', '#b91c1c'],
        'RESPONDED' => ['Respondida', '#dbeafe', '#1d4ed8'],
        'VALIDATING' => ['Em validação', '#ede9fe', '#6d28d9'],
        'RESOLVED' => ['Resolvida', '#dcfce7', '#15803d'],
    ];
    $kpis = [
        ['Aguardando resposta', (int) ($counts['REQUESTED'] ?? 0), 'ri-time-line', '#b45309', 'dependem de você'],
        ['Devolvidas p/ correção', (int) ($counts['RETURNED'] ?? 0), 'ri-arrow-go-back-line', '#c0392b', 'revisar e reenviar'],
        ['Respondidas / em validação', (int) ($counts['RESPONDED'] ?? 0) + (int) ($counts['VALIDATING'] ?? 0), 'ri-hourglass-line', '#263cc8', 'com a equipe de Encerramento'],
        ['Depósitos bloqueados', $signals->count(), 'ri-lock-line', '#155f67', $signals->count() ? $signals->sum('affected_count') . ' Ordem(ns) afetadas' : 'nenhum ativo'],
    ];
    $chips = [];
    if (trim($search) !== '') { $chips[] = ['search', 'Busca: ' . trim($search)]; }
    if ($status !== '') { $chips[] = ['status', 'Situação: ' . ($statusLabels[$status][0] ?? $status)]; }
    if ($categoryId !== '') { $chips[] = ['category', 'Categoria: ' . ($categories->firstWhere('id', (int) $categoryId)?->name ?? '—')]; }
    if ($partnerCompanyFilter !== '') { $chips[] = ['company', 'Empresa: ' . ($this->partnerCompanyFilterOptions()->firstWhere('id', $partnerCompanyFilter)?->name ?? '—')]; }
@endphp
<div class="closure-dash">
    @include('livewire.closure._styles')
    <x-show-loading />

    <div class="dash-hero mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 class="dash-title">Pendências de Encerramento</h2>
                <div class="dash-subtitle">Demandas destinadas à sua empresa: responda as pendências e acompanhe os depósitos bloqueados que afetam as suas obras.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('partner.closure.history') }}" class="btn btn-light btn-sm btn-dash"><i class="ri-history-line"></i> Meu histórico</a>
                <button type="button" class="btn btn-light btn-sm btn-dash" wire:click="exportToExcel" wire:loading.attr="disabled" wire:target="exportToExcel"><i class="ri-file-excel-2-line"></i> Exportar Excel</button>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="alert alert-success d-flex align-items-center gap-2"><i class="ri-checkbox-circle-line"></i> {{ session('success') }}</div>
    @endif

    <div class="row g-2 mb-3">
        @foreach ($kpis as [$label, $value, $icon, $color, $note])
            <div class="col-6 col-xl-3" wire:key="pkpi-{{ $loop->index }}">
                <div class="kpi" style="--k: {{ $color }};">
                    <div class="kpi-top"><span>{{ $label }}</span><i class="{{ $icon }}"></i></div>
                    <div class="kpi-value">{{ number_format($value, 0, ',', '.') }}</div>
                    <div class="kpi-note">{{ $note }}</div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($signals->isNotEmpty())
        <div class="table-card mb-3" style="border-left: 4px solid #c0392b;">
            <div class="od-section-head">
                <h3><i class="ri-lock-line text-danger"></i> Depósitos bloqueados ativos <span class="od-count">{{ $signals->count() }}</span></h3>
            </div>
            <div class="px-3 pb-3 d-flex flex-column gap-2">
                @foreach ($signals as $signal)
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 rounded" style="background:#fff5f5;border:1px solid #f5c2c2;" wire:key="signal-{{ $signal->id }}">
                        <div style="min-width:0;">
                            <div class="fw-bold">Depósito {{ $signal->deposit_code }} <span class="fw-normal text-muted">· {{ $signal->deposit_label }}</span> <span class="st-chip" style="background:#fee2e2;color:#b91c1c;">{{ $signal->affected_count }} Ordem(ns) bloqueada(s)</span></div>
                            <div class="small text-muted">Centro(s) de trabalho: @foreach ($signal->work_centers ?? [] as $code)<span class="co-code me-1">{{ $code }}</span>@endforeach · {{ $signal->origin === 'CALENDAR' ? 'inventário da gestão de ativos' : 'sinalização manual' }}</div>
                            <div class="small text-muted">Desde {{ $signal->created_at?->format('d/m/Y H:i') }} · confirmado por {{ $signal->Author?->name ?? '—' }}</div>
                            <div class="small">{{ $signal->reason }}</div>
                        </div>
                        @if ($canRelease)
                            <button type="button" class="btn btn-sm btn-success fw-semibold" wire:click="startRelease({{ $signal->id }})"><i class="ri-lock-unlock-line"></i> Liberar depósito</button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="table-card p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2 fw-bold"><i class="ri-filter-3-line"></i> Filtros @if (count($chips))<span class="badge bg-light text-dark border">{{ count($chips) }} ativo(s)</span>@endif</div>
            @if (count($chips))
                <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-danger" wire:click="clearFilters"><i class="ri-close-circle-line"></i> Limpar filtros</button>
            @endif
        </div>
        <div class="row g-3">
            <div class="col-12 col-md-6 col-xl-3">
                <label class="ctl-label">Busca</label>
                <div class="input-group"><span class="input-group-text bg-white"><i class="ri-search-line"></i></span><input type="search" class="form-control" wire:model.debounce.500ms="search" placeholder="Nota ou Ordem"></div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="ctl-label">Empresa</label>
                <select class="form-select" wire:model="partnerCompanyFilter">
                    <option value="">Todas as empresas permitidas</option>
                    @foreach ($this->partnerCompanyFilterOptions() as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="ctl-label">Situação</label>
                <select class="form-select" wire:model="status">
                    <option value="">Todas</option>
                    @foreach (['REQUESTED', 'RETURNED', 'RESPONDED', 'VALIDATING', 'RESOLVED'] as $key)
                        <option value="{{ $key }}">{{ $statusLabels[$key][0] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="ctl-label">Categoria</label>
                <select class="form-select" wire:model="categoryId">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}{{ !$category->active ? ' (inativa)' : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if (count($chips))
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <span class="small text-muted">Filtros aplicados:</span>
                @foreach ($chips as [$kind, $label])
                    <span class="ctl-chip" wire:key="pchip-{{ $kind }}">{{ $label }}<button type="button" wire:click="removeFilter('{{ $kind }}')" aria-label="Remover filtro"><i class="ri-close-line"></i></button></span>
                @endforeach
            </div>
        @endif
    </div>

    <div class="table-card mb-3">
        <div class="od-section-head">
            <div><h3><i class="ri-error-warning-line text-muted"></i> Pendências de Encerramento <span class="od-count">{{ $issues->total() }} registros</span></h3><div class="small text-muted mt-1">Apenas demandas destinadas à sua empresa.</div></div>
        </div>
        <div class="table-responsive dash-scroll">
            <table class="table table-sm align-middle mb-0 dash-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th class="ps-3">Nota / Ordem</th>
                        <th>Classificação</th>
                        <th>Situação</th>
                        <th>Solicitada em</th>
                        <th>Último retorno</th>
                        <th class="pe-3 text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($issues as $issue)
                        @php $st = $statusLabels[$issue->status] ?? [$issue->status, '#f1f5f9', '#475569']; @endphp
                        <tr wire:key="closure-issue-{{ $issue->id }}">
                            <td class="ps-3">
                                <div class="ord-main">{{ $issue->Order?->ordem ?? '—' }}</div>
                                <div class="ord-sub">Nota {{ $issue->Order?->Note?->note ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold small">{{ $issue->Category?->name ?? '—' }}</div>
                                <div class="ord-sub">{{ $issue->Subcategory?->name ?? '—' }}</div>
                            </td>
                            <td><span class="st-chip" style="background: {{ $st[1] }}; color: {{ $st[2] }};">{{ $st[0] }}</span><div class="ord-sub mt-1">Ciclo {{ $issue->cycle }}</div></td>
                            <td class="small">{{ optional($issue->requested_at ?? $issue->opened_at)->format('d/m/Y H:i') }}
                                @if (in_array($issue->status, ['REQUESTED', 'RETURNED'], true))<div class="mt-1"><x-closure.age-chip :since="$issue->requested_at ?? $issue->opened_at" /></div>@endif
                            </td>
                            <td class="small">{{ optional($issue->Events->last())->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="pe-3 text-end cell-visible">
                                @php $canReply = in_array($issue->status, ['REQUESTED', 'RETURNED'], true) && $canRespond; @endphp
                                <a class="btn btn-sm {{ $canReply ? 'btn-success' : 'btn-outline-secondary' }} btn-dash" href="{{ route('partner.closure.issue.show', $issue->id) }}">
                                    <i class="{{ $canReply ? 'ri-reply-line' : 'ri-eye-line' }}"></i> {{ $canReply ? 'Ver pedido e responder' : 'Ver pedido' }}
                                </a>
                                @if ($issue->Order?->note_id && $canRespond)
                                    <a class="btn btn-sm btn-outline-secondary btn-dash" href="{{ route('partner.closure.work.show', $issue->Order->note_id) }}"><i class="ri-building-line"></i> Abrir obra</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="ri-checkbox-circle-line d-block text-success" style="font-size:2rem;"></i>Nenhuma pendência encontrada para os filtros selecionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($issues->hasPages())
            <div class="p-3 border-top">{{ $issues->links() }}</div>
        @endif
    </div>

    {{-- Liberar depósito --}}
    @if ($releasingSignalId && ($releasing = $signals->firstWhere('id', $releasingSignalId)))
        <div class="cpop-overlay" wire:click.self="cancelRelease" x-data @keydown.escape.window="$wire.cancelRelease()">
            <div class="cpop" style="max-width: 500px; --tone: #0f8a77;" role="dialog" aria-modal="true" aria-label="Liberar depósito">
                <div class="cpop-head">
                    <span class="cpop-icon"><i class="ri-lock-unlock-line"></i></span>
                    <div>
                        <h3 class="cpop-title">Liberar depósito</h3>
                        <div class="cpop-sub">Depósito {{ $releasing->deposit_code }}@if ($releasing->deposit_label) · {{ $releasing->deposit_label }}@endif</div>
                    </div>
                    <button type="button" class="cpop-close" aria-label="Fechar" wire:click="cancelRelease"><i class="ri-close-line"></i></button>
                </div>
                <div class="cpop-body">
                    <div class="cpop-hint">Confirme que o depósito foi desbloqueado. As <strong>{{ $releasing->affected_count }}</strong> Ordem(ns) bloqueadas voltam, de uma vez, ao status que tinham antes do bloqueio.</div>
                    <label for="release-note" class="cpop-label">Observação (opcional)</label>
                    <textarea id="release-note" class="form-control" rows="3" wire:model.defer="releaseNote" maxlength="1000" placeholder="Ex.: depósito regularizado em 21/10."></textarea>
                </div>
                <div class="cpop-foot">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="cancelRelease">Cancelar</button>
                    <button type="button" class="btn btn-sm px-3 text-white fw-semibold" style="background:#0f8a77;" wire:click="submitRelease" wire:loading.attr="disabled" wire:target="submitRelease"><i class="ri-lock-unlock-line"></i> Liberar todas</button>
                </div>
            </div>
        </div>
    @endif
</div>
