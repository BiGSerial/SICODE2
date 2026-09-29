@push('css')
    <style>
        .ppr-page { --ppr-ink:#1e293b; --ppr-muted:#64748b; --ppr-border:#e2e8f0; --ppr-surface:#fff;
            background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%), radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%), #f6f7fb;
            padding: 1.5rem 0; }
        .ppr-header { background: linear-gradient(120deg,#0f172a,#0f766e 70%); color:#f8fafc; border-radius:1rem; padding:1.4rem 2rem;
            box-shadow:0 16px 40px rgba(15,23,42,.2); margin-bottom:1.25rem; }
        .ppr-card { background:var(--ppr-surface); border:1px solid var(--ppr-border); border-radius:.9rem; box-shadow:0 10px 24px rgba(15,23,42,.05); }
        .ppr-kpi { padding:.85rem 1.1rem; height:100%; border-left:4px solid var(--ppr-accent, #94a3b8); }
        .ppr-kpi .label { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--ppr-muted); }
        .ppr-kpi .value { font-size:1.6rem; font-weight:700; line-height:1.15; color:var(--ppr-ink); }
        .ppr-kpi .hint { font-size:.75rem; color:var(--ppr-muted); }
        .ppr-chip { border:1px solid var(--ppr-border); background:#fff; border-radius:999px; padding:.3rem .85rem; font-size:.82rem; color:var(--ppr-ink); }
        .ppr-chip.active { background:#0f766e; border-color:#0f766e; color:#fff; }
        .ppr-chip .n { opacity:.75; margin-left:.3rem; font-weight:600; }
        .ppr-table { table-layout:fixed; margin:0; }
        .ppr-table thead th { background:#0f172a; color:#f8fafc; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; vertical-align:middle; border:0; padding:.7rem .6rem; }
        .ppr-table thead th small { display:block; font-weight:500; opacity:.7; text-transform:none; letter-spacing:0; }
        .ppr-table td { vertical-align:middle; font-size:.85rem; padding:.6rem; border-color:var(--ppr-border); }
        .ppr-row-late { box-shadow: inset 4px 0 0 #dc2626; }
        .ppr-row-on_time { box-shadow: inset 4px 0 0 #16a34a; }
        .ppr-row-open { box-shadow: inset 4px 0 0 #3b82f6; }
        .ppr-type { display:inline-block; font-size:.68rem; font-weight:700; letter-spacing:.05em; padding:.2rem .55rem; border-radius:.4rem; text-transform:uppercase; }
        .ppr-type-final { background:#dbeafe; color:#1e40af; }
        .ppr-type-partial { background:#ede9fe; color:#5b21b6; }
        .ppr-type-d5 { background:#ccfbf1; color:#115e59; }
        .ppr-id { font-weight:700; color:var(--ppr-ink); font-size:.95rem; margin-left:.35rem; }
        .ppr-orders { font-size:.72rem; color:var(--ppr-muted); margin-top:.15rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ppr-stage { text-align:center; }
        .ppr-stage .days { font-weight:700; font-size:.95rem; }
        .ppr-stage .lim { font-size:.72rem; color:var(--ppr-muted); }
        .ppr-bar { height:5px; border-radius:99px; background:#e2e8f0; overflow:hidden; margin-top:.3rem; }
        .ppr-bar > span { display:block; height:100%; border-radius:99px; }
        .ppr-on_time .days { color:#166534; } .ppr-on_time .ppr-bar > span { background:#22c55e; }
        .ppr-late .days { color:#b91c1c; } .ppr-late .ppr-bar > span { background:#ef4444; }
        .ppr-open_late .days { color:#b45309; } .ppr-open_late .ppr-bar > span { background:#f59e0b; }
        .ppr-open .days { color:#1d4ed8; } .ppr-open .ppr-bar > span { background:#60a5fa; }
        .ppr-pending .days { color:#94a3b8; }
        .ppr-late-tag { font-size:.68rem; font-weight:700; color:#b91c1c; }
        .ppr-user { line-height:1.25; } .ppr-user small { display:block; color:var(--ppr-muted); font-size:.72rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .ppr-detail { background:#f8fafc; }
        .ppr-detail h6 { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--ppr-muted); margin-bottom:.5rem; }
        .ppr-detail dl { margin:0; display:grid; grid-template-columns:auto 1fr; gap:.15rem .9rem; font-size:.82rem; }
        .ppr-detail dt { color:var(--ppr-muted); font-weight:500; } .ppr-detail dd { margin:0; color:var(--ppr-ink); }
        .ppr-legend span { display:inline-flex; align-items:center; gap:.35rem; margin-right:1rem; font-size:.78rem; color:var(--ppr-muted); }
        .ppr-legend i { width:.7rem; height:.7rem; border-radius:99px; display:inline-block; }
        .ppr-tab { border:0; background:transparent; padding:.55rem 1rem; font-weight:600; font-size:.88rem; color:var(--ppr-muted); border-bottom:3px solid transparent; }
        .ppr-tab.active { color:#0f766e; border-bottom-color:#0f766e; }
        .ppr-tabs { border-bottom:1px solid var(--ppr-border); }
        /* Matriz */
        .ppr-matrix { border-collapse:separate; border-spacing:3px; width:100%; }
        .ppr-matrix th { font-size:.72rem; color:var(--ppr-muted); text-align:center; font-weight:600; padding:.25rem; }
        .ppr-matrix th.stage { text-align:left; white-space:nowrap; color:var(--ppr-ink); font-size:.82rem; padding-right:1rem; }
        .ppr-matrix th.day-limit { color:#b45309; }
        .ppr-matrix td { text-align:center; border-radius:.4rem; height:2.4rem; font-weight:700; font-size:.88rem; min-width:2.6rem; }
        .ppr-matrix td.empty { background:#f1f5f9; color:transparent; }
        .ppr-matrix td.cell { cursor:pointer; transition:transform .08s; border:2px solid transparent; }
        .ppr-matrix td.cell:hover { transform:scale(1.08); }
        .ppr-matrix td.cell.selected { border-color:#0f172a; }
        .ppr-matrix td.ok { background:#bbf7d0; color:#14532d; }
        .ppr-matrix td.limit { background:#fde68a; color:#78350f; }
        .ppr-matrix td.late { background:#fca5a5; color:#7f1d1d; }
        .ppr-matrix td.total, .ppr-matrix th.total { background:#e2e8f0; color:var(--ppr-ink); font-weight:700; }
        .ppr-matrix tr.grand td { background:#0f172a; color:#fff; }
        /* Gantt */
        .ppr-gantt-row { display:grid; grid-template-columns:230px 1fr; gap:1rem; align-items:center; padding:.55rem 1rem; border-bottom:1px solid var(--ppr-border); }
        .ppr-gantt-axis { position:relative; height:1.4rem; margin-left:0; }
        .ppr-gantt-track { position:relative; height:1.5rem; background-image:linear-gradient(to right, #e2e8f0 1px, transparent 1px); background-size:var(--ppr-col) 100%; background-color:#f8fafc; border-radius:.35rem; }
        .ppr-gantt-seg { position:absolute; top:.2rem; bottom:.2rem; border-radius:.3rem; overflow:hidden; }
        .ppr-seg-fiscal_dispatch { background:#94a3b8; }
        .ppr-seg-fiscalization { background:#14b8a6; }
        .ppr-seg-measurement_dispatch { background:repeating-linear-gradient(45deg,#cbd5e1,#cbd5e1 4px,#e2e8f0 4px,#e2e8f0 8px); }
        .ppr-seg-measurement { background:#3b82f6; }
        .ppr-gantt-seg .over { position:absolute; top:0; bottom:0; right:0; background:#ef4444; }
        .ppr-gantt-seg.open { box-shadow:0 0 0 2px rgba(245,158,11,.7); }
        .ppr-gantt-limit { position:absolute; top:-.2rem; bottom:-.2rem; width:0; border-left:2px dashed #dc2626; }
        .ppr-gantt-tick { position:absolute; top:0; font-size:.68rem; color:var(--ppr-muted); transform:translateX(-50%); }
    </style>
@endpush

@php
    $fmt = fn ($d) => $d ? $d->format('d/m/Y H:i') : '—';
    $stageDefs = collect($stageLabels)->map(fn ($label, $key) => [$label, $limits[$key]])->all();
    $typeChips = ['all' => 'Todos', 'final' => 'Informe Final', 'partial' => 'Informe Parcial', 'd5' => 'Informe D5'];
    $lateRate = $summary['total'] ? round($summary['late'] / $summary['total'] * 100) : 0;
@endphp

<div class="ppr-page">
    <x-show-loading />

    <div class="container-fluid">
        <div class="ppr-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div>
                <h2 class="mb-1">PROCESSO DE MEDIÇÃO – PÓS OBRA</h2>
                <div class="text-light opacity-75">
                    Prazos por etapa em dias úteis · {{ collect($stageDefs)->map(fn ($d) => $d[0] . ' ' . $d[1])->implode(' · ') }}
                </div>
            </div>
            <button class="btn btn-light btn-sm text-dark" wire:click="exportReport" wire:loading.attr="disabled" wire:target="exportReport">
                <span wire:loading.remove wire:target="exportReport"><i class="ri-file-excel-2-line me-1"></i> Exportar Excel</span>
                <span wire:loading wire:target="exportReport">Gerando...</span>
            </button>
        </div>

        {{-- Filtros --}}
        <div class="ppr-card p-3 mb-3">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach ($typeChips as $value => $label)
                    <button type="button" wire:click="$set('type', '{{ $value }}')" class="ppr-chip {{ $type === $value ? 'active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Data do informe (de)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="from">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Data do informe (até)</label>
                    <input type="date" class="form-control form-control-sm" wire:model.lazy="to">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Empresa do informe</label>
                    <select class="form-select form-select-sm" wire:model="company_id">
                        <option value="">Todas</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Nota</label>
                    <input type="text" class="form-control form-control-sm" placeholder="Número da nota" wire:model.debounce.500ms="search">
                </div>
                <div class="col-12 col-md-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="ppr_only_late" wire:model="only_late">
                        <label class="form-check-label small" for="ppr_only_late">Somente estourados</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Indicadores --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="ppr-card ppr-kpi" style="--ppr-accent:#0f172a">
                    <div class="label">Informes no período</div>
                    <div class="value">{{ $summary['total'] }}</div>
                    <div class="hint">
                        Final {{ $summary['by_type']['final'] ?? 0 }} · Parcial {{ $summary['by_type']['partial'] ?? 0 }} · D5 {{ $summary['by_type']['d5'] ?? 0 }}
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="ppr-card ppr-kpi" style="--ppr-accent:#16a34a">
                    <div class="label">Dentro do prazo</div>
                    <div class="value text-success">{{ $summary['on_time'] }}</div>
                    <div class="hint">nenhuma etapa estourada</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="ppr-card ppr-kpi" style="--ppr-accent:#dc2626">
                    <div class="label">Com prazo estourado</div>
                    <div class="value text-danger">{{ $summary['late'] }}</div>
                    <div class="hint">ao menos uma etapa fora do prazo</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="ppr-card ppr-kpi" style="--ppr-accent:#f59e0b">
                    <div class="label">Taxa de estouro</div>
                    <div class="value">{{ $lateRate }}%</div>
                    <div class="hint">estourados ÷ total</div>
                </div>
            </div>
        </div>

        {{-- Abas de visualização --}}
        <ul class="nav ppr-tabs mb-3">
            @foreach (['table' => ['ri-table-line', 'Tabela'], 'gantt' => ['ri-bar-chart-horizontal-line', 'Gantt por informe'], 'matrix' => ['ri-grid-line', 'Matriz de etapas']] as $tab => [$icon, $label])
                <li class="nav-item">
                    <button type="button" wire:click="$set('view', '{{ $tab }}')" class="ppr-tab {{ $view === $tab ? 'active' : '' }}">
                        <i class="{{ $icon }} me-1"></i>{{ $label }}
                    </button>
                </li>
            @endforeach
        </ul>

        @if ($view === 'matrix')
            @include('livewire.reports.post-work-process.matrix')
        @endif

        @if ($view === 'gantt')
            @include('livewire.reports.post-work-process.gantt')
        @endif

        @if ($view === 'table' || ($view === 'matrix' && $cell_stage))
            @include('livewire.reports.post-work-process.table')
        @endif

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="ppr-legend">
                <span><i style="background:#22c55e"></i>No prazo</span>
                <span><i style="background:#ef4444"></i>Concluída fora do prazo</span>
                <span><i style="background:#f59e0b"></i>Em aberto e estourada</span>
                <span><i style="background:#60a5fa"></i>Em aberto no prazo</span>
            </div>
            <div>{{ $rows->links() }}</div>
        </div>
    </div>
</div>
