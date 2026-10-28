@extends('layouts.padrao')
@section('breadcrumb')<ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li><li class="breadcrumb-item">Qualidade</li><li class="breadcrumb-item"><a href="{{ route('quality.process',$process) }}">Nota {{ $process->Note?->note }}</a></li><li class="breadcrumb-item active">Checklist</li></ol>@endsection
@section('menu') @include('quality._menu') @endsection
@section('content')
@include('quality._styles')
@php
    $labels = [
        'PENDING'   => ['A conferir', 'secondary'],
        'OK'        => ['Conforme', 'success'],
        'NA'        => ['Não se aplica', 'dark'],
        'ISSUE'     => ['Apontamento', 'danger'],
        'RESOLVED'  => ['Respondido', 'warning'],
        'VALIDATED' => ['Validado', 'success'],
    ];
    $onlyIssues = $returnOnly || $canResolve;
    $entries    = ($onlyIssues ? $run->Entries->whereIn('status', ['ISSUE', 'RESOLVED', 'VALIDATED']) : $run->Entries)->sortBy('sort_order');
    $counts     = $entries->countBy('status');
    $n1Mode     = $canEdit && !$returnOnly;
    $checked    = $entries->count() - ($counts['PENDING'] ?? 0);
    $percent    = $entries->count() ? round($checked / $entries->count() * 100) : 0;
    $openCount  = ($counts['ISSUE'] ?? 0) + ($counts['RESOLVED'] ?? 0);
    $mode       = $n1Mode ? 'Conferência do N1' : ($canResolve ? 'Apontamentos para você responder' : 'Consulta');
@endphp
<style>
.qc{--qc-line:#e3e8ef;--qc-soft:#f6f8fb;--qc-ink:#1b2a3a;--qc-muted:#66768a;--qc-brand:#0c6570;--qc-danger:#dc3545;--qc-ok:#198754;--qc-warn:#f0a500}
.qc-hero{background:linear-gradient(120deg,#063b44,#0c6570);color:#fff;border-radius:16px;padding:1.25rem 1.5rem;box-shadow:0 6px 20px rgba(6,59,68,.18)}
.qc-hero .kicker{font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;opacity:.75;font-weight:700}
.qc-hero h1{font-size:1.75rem;font-weight:800;margin:.1rem 0 .35rem}
.qc-hero .mode{display:inline-flex;align-items:center;gap:.4rem;background:#ffffff22;border-radius:999px;padding:.2rem .7rem;font-size:.8rem;font-weight:600}
.qc-stat{display:flex;gap:1.25rem;text-align:center}
.qc-stat b{font-size:1.6rem;display:block;line-height:1}
.qc-stat small{opacity:.8;font-size:.72rem;text-transform:uppercase;letter-spacing:.04em}
.qc-progress{height:8px;background:#ffffff40;border-radius:9px;overflow:hidden;margin-top:1rem}
.qc-progress span{height:100%;display:block;background:#f7c843;border-radius:9px;transition:width .25s}
.qc-note{border-radius:12px;padding:.8rem 1rem;border:1px solid;display:flex;gap:.75rem;align-items:flex-start}
.qc-note i{font-size:1.3rem;line-height:1.2}
.qc-note.info{background:#eef4ff;border-color:#cfdcf7;color:#233a63}
.qc-note.warn{background:#fff7e0;border-color:#f3dd9a;color:#5c4400}
.qc-toolbar{position:sticky;top:8px;z-index:5;background:#fff;border:1px solid var(--qc-line);border-radius:14px;padding:.6rem .8rem;box-shadow:0 4px 14px rgba(15,35,55,.08)}
.qc-toolbar .form-control{border-radius:10px}
.qc-pill{border:1px solid var(--qc-line);background:#fff;border-radius:999px;padding:.3rem .8rem;font-size:.82rem;font-weight:600;color:var(--qc-muted);display:inline-flex;gap:.4rem;align-items:center}
.qc-pill b{background:var(--qc-soft);border-radius:999px;padding:0 .45rem;color:var(--qc-ink)}
.qc-pill.active{background:var(--qc-brand);border-color:var(--qc-brand);color:#fff}
.qc-pill.active b{background:#ffffff30;color:#fff}
.qc-section{background:#fff;border:1px solid var(--qc-line);border-radius:16px;overflow:hidden}
.qc-section>summary{list-style:none;cursor:pointer;padding:.9rem 1.1rem;display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;justify-content:space-between;background:var(--qc-soft)}
.qc-section>summary::-webkit-details-marker{display:none}
.qc-section[open]>summary{border-bottom:1px solid var(--qc-line)}
.qc-chevron{transition:.15s;color:var(--qc-muted)}
.qc-section[open] .qc-chevron{transform:rotate(90deg)}
.qc-section .title{font-weight:700;color:var(--qc-ink);font-size:1.02rem}
.qc-count{font-size:.78rem;color:var(--qc-muted)}
.qc{font-size:1rem}
.qc-item{display:grid;grid-template-columns:84px minmax(0,1fr) minmax(190px,auto);gap:1rem;padding:1.15rem 1.3rem;border-top:1px solid var(--qc-line);border-left:5px solid transparent;align-items:start}
.qc-item:first-child{border-top:0}
.qc-item[data-status=ISSUE]{border-left-color:var(--qc-danger);background:#fffafa}
.qc-item[data-status=RESOLVED]{border-left-color:var(--qc-warn);background:#fffdf5}
.qc-item[data-status=VALIDATED],.qc-item[data-status=OK]{border-left-color:var(--qc-ok)}
.qc-code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:700;font-size:.9rem;background:var(--qc-soft);border:1px solid var(--qc-line);border-radius:8px;padding:.3rem .5rem;text-align:center;color:var(--qc-ink)}
.qc-q{font-weight:600;color:var(--qc-ink);font-size:1.08rem;line-height:1.4}
.qc-meta{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-top:.45rem}
.qc-meta .badge{font-size:.78rem;padding:.4em .7em}
.qc-msgs{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:.7rem;margin-top:.9rem}
.qc-msg{border-radius:10px;padding:.7rem .9rem;font-size:.97rem;display:flex;gap:.6rem;align-items:flex-start;border:1px solid}
.qc-msg i{margin-top:.15rem;font-size:1.1rem}
.qc-msg .who{display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;font-weight:700;opacity:.75;margin-bottom:.1rem}
.qc-msg.n1{background:#fff1f2;border-color:#f6c9ce;color:#7a1d27}
.qc-msg.n2{background:#eef4ff;border-color:#cfdcf7;color:#233a63}
.qc-msg.user{background:#effaf3;border-color:#bfe5cc;color:#14532d}
.qc-side{display:flex;flex-direction:column;gap:.5rem;align-items:stretch}
.qc-decide{font-weight:600;padding:.55rem 1rem;white-space:nowrap}
.qc-choice{border:1px solid #d5dce5;background:#fff;border-radius:10px;padding:.5rem .9rem;font-size:.92rem;font-weight:600;color:var(--qc-ink);display:inline-flex;gap:.4rem;align-items:center;justify-content:flex-start}
.qc-choice:hover{background:var(--qc-soft)}
.qc-choice.active[data-value=OK]{background:var(--qc-ok);color:#fff;border-color:var(--qc-ok)}
.qc-choice.active[data-value=ISSUE]{background:var(--qc-danger);color:#fff;border-color:var(--qc-danger)}
.qc-choice.active[data-value=NA]{background:#495057;color:#fff;border-color:#495057}
.qc-bulk-bar{display:flex;gap:.4rem;align-items:center;font-size:.8rem;color:var(--qc-muted)}
.qc-footer{display:flex;justify-content:space-between;gap:.75rem;margin:1.25rem 0 2rem}
.qc-empty{padding:2rem;text-align:center;color:var(--qc-muted)}
@media(max-width:900px){.qc-item{grid-template-columns:1fr}.qc-side{flex-direction:row;flex-wrap:wrap}.qc-hero h1{font-size:1.4rem}}
</style>
<div class="closure-dash qc">
    <div class="qc-hero mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="kicker">Checklist de qualidade · rodada {{ $run->round_number }}</div>
                <h1>Nota {{ $process->Note?->note }}</h1>
                <span class="mode"><i class="ri-user-star-line"></i> {{ $mode }}</span>
            </div>
            <div class="qc-stat">
                @if ($onlyIssues)
                    <div><b>{{ $openCount }}</b><small>a responder</small></div>
                    <div><b>{{ $entries->count() }}</b><small>apontamentos</small></div>
                @else
                    <div><b><span id="qc-checked">{{ $checked }}</span>/{{ $entries->count() }}</b><small>conferidos</small></div>
                    <div><b>{{ $counts['ISSUE'] ?? 0 }}</b><small>apontados</small></div>
                @endif
            </div>
        </div>
        <div class="qc-progress"><span id="qc-progress" style="width:{{ $percent }}%"></span></div>
    </div>

    @include('quality._feedback')

    @if ($returnOnly)
        <div class="qc-note info mb-3 justify-content-between flex-wrap">
            <div class="d-flex gap-2"><i class="ri-information-line"></i><div><b>Retorno para reanálise.</b> Exibindo somente os apontamentos enviados ao usuário.</div></div>
            <a href="{{ route('quality.process.checklist', [$process, 'new' => 1]) }}" class="btn btn-outline-primary btn-sm"><i class="ri-add-line"></i> Adicionar novos apontamentos</a>
        </div>
    @elseif ($canResolve)
        <div class="qc-note info mb-3">
            <i class="ri-list-check-2"></i>
            <div><b>Estes são os apontamentos que competem a você.</b> Leia a observação, responda como foi tratado e marque como respondido. Você também pode fazer isso direto no formulário da atividade.</div>
        </div>
    @endif

    <div class="qc-toolbar mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input id="checklist-search" class="form-control form-control-sm flex-grow-1" style="min-width:220px" placeholder="Buscar por item, código ou assunto">
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="qc-pill checklist-filter active" data-filter="ALL">Todos <b>{{ $entries->count() }}</b></button>
                <button type="button" class="qc-pill checklist-filter" data-filter="ISSUE">Apontados <b>{{ $counts['ISSUE'] ?? 0 }}</b></button>
                @unless ($onlyIssues)
                    <button type="button" class="qc-pill checklist-filter" data-filter="PENDING">A conferir <b>{{ $counts['PENDING'] ?? 0 }}</b></button>
                @endunless
            </div>
            @if ($n1Mode)
                <button type="submit" form="checklist-form" class="btn btn-primary btn-sm"><i class="ri-save-line"></i> Salvar</button>
            @endif
        </div>
    </div>

    @if ($n1Mode)
        <form method="post" id="checklist-form" action="{{ route('quality.process.checklist.save', [$process, $run]) }}">@csrf @method('PUT')
    @endif

    <div class="d-flex flex-column gap-3">
    @forelse ($entries->groupBy('section_name') as $section => $sectionEntries)
        @php $sid = 'section-' . \Illuminate\Support\Str::slug($section); @endphp
        <details class="qc-section" open>
            <summary>
                <span class="d-flex align-items-center gap-2">
                    <i class="ri-arrow-right-s-line qc-chevron fs-5"></i>
                    <span class="title">{{ $section }}</span>
                    <span class="qc-count">{{ $sectionEntries->count() }} itens · <span class="text-danger">{{ $sectionEntries->where('status', 'ISSUE')->count() }} apontados</span></span>
                </span>
                @if ($n1Mode)
                    <span class="qc-bulk-bar" onclick="event.preventDefault();event.stopPropagation();">
                        Aplicar a todos:
                        <button type="button" class="btn btn-outline-success btn-sm qc-bulk" data-section="{{ $sid }}" data-value="OK">Conforme</button>
                        <button type="button" class="btn btn-outline-dark btn-sm qc-bulk" data-section="{{ $sid }}" data-value="NA">Não se aplica</button>
                    </span>
                @endif
            </summary>
            <div id="{{ $sid }}">
                @foreach ($sectionEntries as $entry)
                    @php
                        [$label, $tone] = $labels[$entry->status] ?? $labels['PENDING'];
                        $severity = $showQaSeverity ? $entry->severity_qa : $entry->severity;
                    @endphp
                    <div class="qc-item checklist-entry" data-status="{{ $entry->status }}" data-entry="{{ $entry->id }}" data-search="{{ mb_strtolower($entry->code . ' ' . $entry->question . ' ' . $section) }}">
                        <div class="qc-code">{{ $entry->code }}</div>
                        <div class="qc-main">
                            <div class="qc-q">{{ $entry->question }}</div>
                            <div class="qc-meta">
                                <span class="badge text-bg-{{ $severity === 'Grave' ? 'danger' : 'warning' }}">{{ $severity }}</span>
                                <span class="badge text-bg-{{ $tone }} status-label">{{ $label }}</span>
                            </div>

                            @if (($entry->observation && !$n1Mode) || $entry->resolution)
                                <div class="qc-msgs">
                                    @if ($entry->observation && !$n1Mode)
                                        <div class="qc-msg n1"><i class="ri-error-warning-line"></i><div><span class="who">Apontamento do N1</span>{{ $entry->observation }}</div></div>
                                    @endif
                                    @if ($entry->resolution)
                                        <div class="qc-msg user"><i class="ri-reply-line"></i><div><span class="who">Resposta do usuário{{ $entry->status === 'VALIDATED' ? ' · aceita' : '' }}</span>{{ $entry->resolution }}</div></div>
                                    @endif
                                </div>
                            @endif

                            @if ($n1Mode)
                                <div id="obs-{{ $entry->id }}" class="mt-2 {{ in_array($entry->status, ['ISSUE', 'RESOLVED']) ? '' : 'd-none' }}">
                                    <input class="form-control" name="entries[{{ $entry->id }}][observation]" value="{{ $entry->observation }}" maxlength="3000" placeholder="Descreva objetivamente o problema encontrado">
                                </div>
                            @elseif ($canResolve && $entry->status === 'ISSUE')
                                <form class="d-flex flex-wrap gap-2 mt-2" method="post" action="{{ route('quality.process.checklist.resolve', [$process, $entry]) }}">@csrf
                                    <input required name="resolution" maxlength="3000" class="form-control flex-grow-1" placeholder="Como este apontamento foi tratado?">
                                    <button class="btn btn-success"><i class="ri-checkbox-circle-line"></i> Marcar como respondido</button>
                                </form>
                            @endif
                        </div>

                        <div class="qc-side">
                            @if ($canEdit && $entry->status === 'RESOLVED')
                                <button type="submit" form="validate-{{ $entry->id }}-1" class="btn btn-success qc-decide"><i class="ri-check-line"></i> Aceitar</button>
                                <button type="submit" form="validate-{{ $entry->id }}-0" class="btn btn-outline-danger qc-decide"><i class="ri-close-line"></i> Rejeitar</button>
                            @elseif ($n1Mode)
                                @foreach (['OK' => ['Conforme', 'ri-checkbox-circle-line'], 'ISSUE' => ['Apontar problema', 'ri-error-warning-line'], 'NA' => ['Não se aplica', 'ri-subtract-line']] as $value => [$choice, $icon])
                                    <button type="button" class="qc-choice {{ ($entry->status === $value) ? 'active' : '' }}" data-value="{{ $value }}" data-entry="{{ $entry->id }}"><i class="{{ $icon }}"></i> {{ $choice }}</button>
                                @endforeach
                                <select class="d-none checklist-status" name="entries[{{ $entry->id }}][status]" id="status-{{ $entry->id }}">
                                    <option value="PENDING" @selected($entry->status === 'PENDING')>Pendente</option>
                                    <option value="OK" @selected($entry->status === 'OK')>Conforme</option>
                                    <option value="NA" @selected($entry->status === 'NA')>Não se aplica</option>
                                    <option value="ISSUE" @selected($entry->status === 'ISSUE')>Apontamento</option>
                                </select>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </details>
    @empty
        <div class="qc-section"><div class="qc-empty"><i class="ri-checkbox-circle-line fs-2 d-block"></i>Nenhum apontamento para exibir.</div></div>
    @endforelse
    </div>

    @if ($n1Mode)
        <div class="qc-footer">
            <a href="{{ route('quality.process', $process) }}" class="btn btn-outline-secondary">Voltar</a>
            <button class="btn btn-primary btn-dash"><i class="ri-save-line"></i> Salvar checklist</button>
        </div>
        </form>
    @else
        <div class="qc-footer"><a href="{{ route('quality.process', $process) }}" class="btn btn-outline-secondary">Voltar à Nota</a></div>
    @endif
</div>
@if ($canEdit)
    @foreach ($entries->where('status', 'RESOLVED') as $resolved)
        @foreach ([1, 0] as $accepted)
            <form id="validate-{{ $resolved->id }}-{{ $accepted }}" method="post" action="{{ route('quality.process.checklist.validate', [$process, $resolved]) }}" class="d-none">@csrf<input type="hidden" name="accepted" value="{{ $accepted }}"></form>
        @endforeach
    @endforeach
@endif
@endsection
@push('script')<script>(()=>{
const search=document.getElementById('checklist-search'),rows=[...document.querySelectorAll('.checklist-entry')],
labels={PENDING:['A conferir','secondary'],OK:['Conforme','success'],NA:['Não se aplica','dark'],ISSUE:['Apontamento','danger']};
let filter='ALL';
const refresh=()=>{const term=(search?.value||'').toLowerCase();rows.forEach(row=>{row.classList.toggle('d-none',(filter!=='ALL'&&row.dataset.status!==filter)||!row.dataset.search.includes(term))})};
const set=(id,status)=>{
  const select=document.getElementById('status-'+id);if(!select)return;
  const row=select.closest('.checklist-entry'),obs=document.getElementById('obs-'+id),[text,tone]=labels[status];
  select.value=status;row.dataset.status=status;
  row.querySelectorAll('.qc-choice').forEach(b=>b.classList.toggle('active',b.dataset.value===status));
  const badge=row.querySelector('.status-label');badge.className='badge text-bg-'+tone+' status-label';badge.textContent=text;
  obs?.classList.toggle('d-none',status!=='ISSUE');
  const checked=rows.filter(r=>r.dataset.status!=='PENDING').length;
  const c=document.getElementById('qc-checked'),p=document.getElementById('qc-progress');
  if(c)c.textContent=checked;if(p)p.style.width=Math.round(checked/rows.length*100)+'%';
};
search?.addEventListener('input',refresh);
document.querySelectorAll('.checklist-filter').forEach(b=>b.addEventListener('click',()=>{filter=b.dataset.filter;document.querySelectorAll('.checklist-filter').forEach(x=>x.classList.toggle('active',x===b));refresh()}));
document.querySelectorAll('.qc-choice').forEach(b=>b.addEventListener('click',()=>set(b.dataset.entry,b.dataset.value)));
document.querySelectorAll('.qc-bulk').forEach(b=>b.addEventListener('click',()=>document.querySelectorAll('#'+b.dataset.section+' .checklist-entry').forEach(row=>set(row.dataset.entry,b.dataset.value))));
})();</script>@endpush
