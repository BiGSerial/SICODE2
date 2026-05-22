@if ($files?->isNotEmpty())
@php
    $isSuperAdm    = auth()->user()?->superadm ?? false;
    $adsTacitFileIds = \Illuminate\Support\Facades\DB::table('adsforms_files as af')
        ->join('adsforms as a', 'a.id', '=', 'af.adsform_id')
        ->whereIn('af.file_id', $files->pluck('id')->all())
        ->where('a.tacit', true)
        ->whereNotNull('a.work_report_id')
        ->pluck('af.file_id')
        ->flip()
        ->all();

    $firstFile  = $files->first();
    $noteLabel  = $firstFile->note->note ?? '—';
    $noteId     = $firstFile->note_id;
    $totalCount = $files->count();

    $grouped = $files->sortBy(fn ($f) => [
        $f->service_id === null ? 1 : 0,
        $f->service->service ?? '',
        $f->file_name,
    ])->groupBy(fn ($f) => $f->service_id ?? '__others__');
@endphp

<div class="dropdown d-inline fdl-wrap" style="position: inherit;">

    {{-- Trigger --}}
    <button type="button"
        class="fdl-trigger"
        data-bs-toggle="dropdown"
        data-bs-boundary="viewport"
        aria-expanded="false">
        <i class="ri-folder-3-line fdl-trigger-icon"></i>
    </button>

    {{-- Painel --}}
    <div class="dropdown-menu fdl-panel py-0">

        {{-- Header --}}
        <div class="fdl-panel-header">
            <div class="fdl-panel-header-inner">
                <i class="ri-folder-open-line me-1"></i>
                <span class="fdl-panel-note" title="{{ $noteLabel }}">{{ $noteLabel }}</span>
            </div>
            <span class="fdl-panel-total">
                {{ $totalCount }} arquivo{{ $totalCount !== 1 ? 's' : '' }}
            </span>
        </div>

        {{-- Grupos por serviço --}}
        <div class="fdl-accordion" data-accordion-id="fdl-acc-{{ $noteId }}">
            @foreach ($grouped as $serviceId => $serviceFiles)
                @php
                    $serviceName = $serviceFiles->first()->service->service ?? 'Outros';
                    $svcCount    = $serviceFiles->count();
                    $collapseId  = 'fdl-col-' . $noteId . '-' . $loop->index;
                @endphp

                <div class="fdl-group">
                    <button type="button"
                        class="fdl-group-btn"
                        data-fdl-target="#{{ $collapseId }}"
                        onclick="event.stopPropagation(); fdlToggle(this)">
                        <span class="fdl-group-name">{{ mb_strtoupper($serviceName) }}</span>
                        <span class="fdl-group-meta">
                            <span class="fdl-group-count">{{ $svcCount }}</span>
                            <i class="ri-arrow-down-s-line fdl-chevron"></i>
                        </span>
                    </button>

                    <div id="{{ $collapseId }}" class="fdl-collapse">
                        <ul class="fdl-list">
                            @foreach ($serviceFiles as $file)
                                @php
                                    $iconMeta = \App\Helpers\FileIcon::getIcon($file->ext);
                                    $icon     = is_object($iconMeta)
                                        ? ($iconMeta->icon ?? 'ri-file-fill')
                                        : 'ri-file-fill';
                                    $isTacit = isset($adsTacitFileIds[$file->id]);
                                @endphp
                                <li class="fdl-item">
                                    @if ($isTacit && !$isSuperAdm)
                                        <span class="fdl-file fdl-file--blocked"
                                            title="Arquivo de ADS tácita. Download bloqueado.">
                                            <i class="{{ $icon }} fdl-file-icon"></i>
                                            <span class="fdl-file-name">{{ $file->file_name }}</span>
                                            <span class="fdl-file-ext">{{ strtoupper($file->ext) }}</span>
                                            <span class="fdl-badge bg-warning text-dark">TÁCITO</span>
                                            <i class="ri-lock-line text-muted"></i>
                                        </span>
                                    @else
                                        <a href="#"
                                            class="fdl-file"
                                            wire:click.prevent="downloadFile({{ $file->id }})"
                                            onclick="event.stopPropagation();"
                                            title="{{ $file->file_name }}.{{ $file->ext }}">
                                            <i class="{{ $icon }} fdl-file-icon"></i>
                                            <span class="fdl-file-name">{{ $file->file_name }}</span>
                                            <span class="fdl-file-ext">{{ strtoupper($file->ext) }}</span>
                                            @if ($isTacit && $isSuperAdm)
                                                <span class="fdl-badge bg-warning text-dark">TÁCITO</span>
                                            @endif
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@once
<style>
    /* ── Trigger ────────────────────────────────────── */
    .fdl-trigger {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background: none;
        border: none;
        padding: 2px 5px;
        border-radius: 6px;
        cursor: pointer;
        color: #dc3545;
        transition: color .15s, background .15s;
        line-height: 1;
    }

    .fdl-trigger:hover,
    .fdl-trigger[aria-expanded="true"] {
        color: #198754;
        background: rgba(25, 135, 84, .08);
    }

    .fdl-trigger-icon { font-size: 1.2rem; }

    .fdl-trigger-count {
        font-size: .62rem;
        font-weight: 700;
        background: #dc3545;
        color: #fff;
        border-radius: 10px;
        padding: 1px 5px;
        min-width: 18px;
        text-align: center;
        transition: background .15s;
    }

    .fdl-trigger:hover .fdl-trigger-count,
    .fdl-trigger[aria-expanded="true"] .fdl-trigger-count { background: #198754; }

    /* ── Painel ─────────────────────────────────────── */
    .fdl-panel {
        min-width: 360px;
        max-height: 420px;
        overflow-y: auto;
        border: none;
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, .2), 0 2px 8px rgba(0, 0, 0, .1);
        z-index: 9999;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .fdl-panel::-webkit-scrollbar { width: 4px; }
    .fdl-panel::-webkit-scrollbar-track { background: transparent; }
    .fdl-panel::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .fdl-panel::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* ── Header ─────────────────────────────────────── */
    .fdl-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .55rem .85rem;
        background: linear-gradient(120deg, #0f172a 0%, #0f766e 100%);
        color: #f8fafc;
        border-radius: 10px 10px 0 0;
        gap: .5rem;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .fdl-panel-header-inner {
        display: flex;
        align-items: center;
        font-size: .78rem;
        font-weight: 600;
        overflow: hidden;
        gap: .25rem;
    }

    .fdl-panel-note {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 230px;
    }

    .fdl-panel-total {
        font-size: .67rem;
        color: #5eead4;
        white-space: nowrap;
        font-weight: 600;
        flex-shrink: 0;
    }

    /* ── Grupos ─────────────────────────────────────── */
    .fdl-accordion { background: #fff; }

    .fdl-group { border-bottom: 1px solid #e2e8f0; }
    .fdl-group:last-child { border-bottom: none; }

    .fdl-group-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        border: none;
        background: #f8fafc;
        padding: .42rem .85rem;
        font-size: .7rem;
        font-weight: 700;
        color: #475569;
        letter-spacing: .05em;
        cursor: pointer;
        transition: background .15s, color .15s;
    }

    .fdl-group-btn:hover { background: #f1f5f9; color: #334155; }

    .fdl-group-btn--open { background: #ecfdf5; color: #166534; }
    .fdl-group-btn--open:hover { background: #d1fae5; }

    .fdl-group-meta { display: flex; align-items: center; gap: .4rem; }

    .fdl-group-count {
        font-size: .6rem;
        font-weight: 700;
        background: #94a3b8;
        color: #fff;
        border-radius: 10px;
        padding: 1px 6px;
        min-width: 18px;
        text-align: center;
        transition: background .15s;
    }

    .fdl-group-btn--open .fdl-group-count { background: #16a34a; }

    .fdl-chevron {
        font-size: .95rem;
        color: #94a3b8;
        transition: transform .25s;
    }

    .fdl-group-btn--open .fdl-chevron {
        transform: rotate(180deg);
        color: #16a34a;
    }

    /* ── Collapse ───────────────────────────────────── */
    .fdl-collapse {
        overflow: hidden;
        transition: height .22s ease;
        height: 0;
    }

    /* ── Lista de arquivos ──────────────────────────── */
    .fdl-list {
        list-style: none;
        margin: 0;
        padding: .15rem 0;
        background: #fff;
    }

    .fdl-item { border-bottom: 1px solid #f1f5f9; }
    .fdl-item:last-child { border-bottom: none; }

    .fdl-file {
        display: flex;
        align-items: center;
        gap: .45rem;
        padding: .38rem .85rem;
        font-size: .8rem;
        color: #1e293b;
        text-decoration: none;
        transition: background .12s;
        width: 100%;
    }

    .fdl-file:hover {
        background: #f0fdf4;
        color: #166534;
        text-decoration: none;
    }

    .fdl-file--blocked {
        opacity: .55;
        cursor: not-allowed;
        filter: grayscale(1);
    }

    .fdl-file-icon { font-size: .95rem; flex-shrink: 0; }

    .fdl-file-name {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }

    .fdl-file-ext {
        font-size: .58rem;
        font-weight: 700;
        background: #e2e8f0;
        color: #475569;
        border-radius: 4px;
        padding: 1px 5px;
        flex-shrink: 0;
        letter-spacing: .04em;
    }

    .fdl-badge {
        font-size: .58rem;
        flex-shrink: 0;
        padding: 2px 5px;
        border-radius: 4px;
    }
</style>

@push('script')
<script>
    if (!window.__fdlInit) {
        window.__fdlInit = true;

        window.fdlToggle = function (btn) {
            const acc      = btn.closest('[data-accordion-id]');
            const targetId = btn.getAttribute('data-fdl-target').replace('#', '');
            const target   = document.getElementById(targetId);
            const isOpen   = btn.classList.contains('fdl-group-btn--open');

            acc.querySelectorAll('.fdl-collapse').forEach(function (c) {
                c.style.height = '0';
            });
            acc.querySelectorAll('.fdl-group-btn').forEach(function (b) {
                b.classList.remove('fdl-group-btn--open');
            });

            if (!isOpen && target) {
                target.style.height = target.scrollHeight + 'px';
                btn.classList.add('fdl-group-btn--open');
            }
        };

    }
</script>
@endpush
@endonce
