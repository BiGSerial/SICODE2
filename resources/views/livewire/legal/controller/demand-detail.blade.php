<div class="ld-page" x-data="{
    tab: localStorage.getItem('ldt_{{ $demand->id }}') || 'process',
    setTab(t) { this.tab = t; localStorage.setItem('ldt_{{ $demand->id }}', t); }
}" x-cloak>
    <x-show-loading />

    <style>
        [x-cloak] { display: none !important; }
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --bg: #eef2f7;
            --surface: #ffffff;
            --surface-2: #f8fafc;
            --border: #e2e8f0;
            --border-2: #cbd5e1;
            --text: #0f172a;
            --text-2: #475569;
            --text-3: #64748b;
            --primary: #1e3a8a;
            --primary-light: #dbeafe;
            --primary-border: #93c5fd;
            --green: #0f766e; --green-bg: #e6fffa; --green-border: #99f6e4;
            --amber: #a16207; --amber-bg: #fef9c3; --amber-border: #fde68a;
            --red: #b91c1c; --red-bg: #fee2e2; --red-border: #fecaca;
            --blue: #1e3a8a; --blue-bg: #dbeafe; --blue-border: #93c5fd;
        }

        .ld-page { background: transparent; padding: 0 0 32px; color: var(--text); }
        .ld-wrap { width: 100%; margin: 0; }

        /* ── Header ── */
        .ld-header {
            background: linear-gradient(120deg, #0f2d5f, #1a3f6f 70%);
            border-radius: 14px; padding: 20px 24px; color: #eff6ff;
            margin-bottom: 0; position: relative; overflow: hidden;
            border-bottom-left-radius: 0; border-bottom-right-radius: 0;
        }
        .ld-header::after {
            content: '#' attr(data-case);
            position: absolute; right: 20px; bottom: -18px;
            font-size: 72px; font-weight: 800; opacity: .12; line-height: 1;
        }
        .ld-header-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .ld-header-left { flex: 1; min-width: 0; }
        .ld-header-right { display: flex; flex-direction: column; align-items: flex-end; gap: 8px; flex-shrink: 0; }
        .ld-back { font-size: 11px; opacity: .7; text-decoration: none; color: inherit; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 6px; }
        .ld-back:hover { opacity: 1; color: inherit; }
        .ld-company { font-size: 11px; opacity: .75; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
        .ld-case { font-size: 26px; font-weight: 700; line-height: 1.1; margin: 0 0 4px; }
        .ld-subject { font-size: 14px; opacity: .88; margin-bottom: 10px; }
        .ld-badges { display: flex; gap: 6px; flex-wrap: wrap; }
        .ld-badge {
            font-size: 10px; font-weight: 700; padding: 3px 9px; border-radius: 16px;
            display: inline-flex; align-items: center; gap: 5px; border: 1px solid transparent;
        }
        .ld-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .b-green { color: var(--green); background: var(--green-bg); border-color: var(--green-border); }
        .b-amber { color: var(--amber); background: var(--amber-bg); border-color: var(--amber-border); }
        .b-red   { color: var(--red);   background: var(--red-bg);   border-color: var(--red-border); }
        .b-blue  { color: var(--blue);  background: var(--blue-bg);  border-color: var(--blue-border); }
        .b-gray  { color: var(--text-2); background: var(--surface-2); border-color: var(--border); }
        .ld-header-action .btn { font-size: 12px; white-space: nowrap; }

        /* ── Tabs ── */
        .ld-tabs {
            display: flex; background: #e8edf5;
            border: 1px solid #d1d9e6; border-top: none;
            padding: 8px 10px; gap: 4px;
            overflow-x: auto; scrollbar-width: none; flex-wrap: wrap;
        }
        .ld-tabs::-webkit-scrollbar { display: none; }
        .ld-tab {
            padding: 9px 18px; font-size: 13px; font-weight: 600;
            color: #475569; border: 1px solid transparent; background: transparent;
            cursor: pointer; white-space: nowrap; border-radius: 8px;
            display: inline-flex; align-items: center; gap: 7px;
            transition: all .15s; line-height: 1;
        }
        .ld-tab:hover { color: #1e3a8a; background: #dbeafe; border-color: #bfdbfe; }
        .ld-tab.active {
            color: #fff; background: #1e3a8a; border-color: #1e3a8a;
            box-shadow: 0 2px 8px rgba(30,58,138,.35);
        }
        .ld-tab-badge {
            font-size: 10px; font-weight: 700; background: #dc2626; color: #fff;
            border-radius: 999px; padding: 1px 6px; line-height: 1.4;
        }
        .ld-tab.active .ld-tab-badge { background: rgba(255,255,255,.3); color: #fff; }
        .ld-tab-badge.ok { background: rgba(30,58,138,.35); color: #fff; }
        .ld-tab.active .ld-tab-badge.ok { background: rgba(255,255,255,.25); }

        /* ── Tab Content ── */
        .ld-tab-content { background: var(--surface-2); border-radius: 0 0 14px 14px; padding: 20px 0 0; }

        /* ── Sections & panels ── */
        .ld-section, .ld-panel {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 12px; overflow: hidden; margin-bottom: 14px;
        }
        .ld-section-header, .ld-panel-header {
            background: #0f172a; color: #e2e8f0; padding: 10px 16px;
            font-size: 10px; letter-spacing: .1em; text-transform: uppercase; font-weight: 700;
        }
        .ld-section-body, .ld-panel-body { padding: 14px 16px; }

        .ld-main { display: grid; grid-template-columns: 1fr 320px; gap: 16px; align-items: start; }

        /* ── Fields ── */
        .field-grid { display: grid; gap: 8px; }
        .fg-2 { grid-template-columns: 1fr 1fr; }
        .fg-3 { grid-template-columns: 1fr 1fr 1fr; }
        .field { background: var(--surface-2); border: 1px solid var(--border); border-radius: 8px; padding: 9px 11px; }
        .fl { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
        .fv { font-size: 13px; color: var(--text); line-height: 1.4; }
        .fv.mono { font-family: monospace; font-size: 12px; }

        /* ── Timeline events ── */
        .event-timeline { display: grid; gap: 8px; }
        .event-item { display: grid; grid-template-columns: 20px 1fr; gap: 8px; align-items: start; }
        .event-marker-wrap { position: relative; min-height: 100%; }
        .event-marker-wrap::after {
            content: ''; position: absolute; left: 9px; top: 13px; bottom: -12px;
            width: 2px; background: linear-gradient(180deg, #93c5fd 0%, #dbeafe 100%);
        }
        .event-item:last-child .event-marker-wrap::after { display: none; }
        .event-marker {
            width: 12px; height: 12px; border-radius: 999px; background: #1d4ed8;
            border: 2px solid #dbeafe; box-shadow: 0 0 0 3px rgba(59,130,246,.12); margin-top: 1px;
        }
        .event-content { background: #f8fbff; border: 1px solid #dbeafe; border-radius: 8px; padding: 8px 10px; }
        .event-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 4px; }
        .event-badge {
            font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
            color: #1e3a8a; background: #dbeafe; border: 1px solid #93c5fd; border-radius: 999px; padding: 2px 8px;
        }
        .event-time { font-size: 11px; color: var(--text-3); white-space: nowrap; }
        .event-title { font-size: 13px; color: var(--text); font-weight: 600; }
        .event-actor { font-size: 12px; color: var(--text-2); margin-top: 3px; }

        /* ── KV list ── */
        .kv { border-bottom: 1px solid var(--border); padding: 7px 0; }
        .kv:last-child { border-bottom: 0; }
        .kl { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: .05em; }
        .kv-val { font-size: 13px; color: var(--text); margin-top: 1px; }

        /* ── Subdemand cards ── */
        .sub-deck { display: grid; gap: 8px; }
        .sub-card {
            background: #fff; border: 1px solid var(--border); border-radius: 12px;
            overflow: hidden; border-left: 5px solid var(--border-2);
            box-shadow: 0 1px 4px rgba(15,23,42,.05); transition: box-shadow .15s;
        }
        .sub-card:hover { box-shadow: 0 3px 12px rgba(15,23,42,.09); }
        .sub-card.s-closed {
            background: #f8fafc; border-color: #e2e8f0; border-left-color: #cbd5e1; box-shadow: none;
        }
        .sub-card.s-closed .sub-name { color: var(--text-3); }
        .sub-card.s-closed .sub-area { opacity: .6; }
        .sub-card.s-aberta             { border-left-color: #6366f1; }
        .sub-card.s-pendente           { border-left-color: #6366f1; }
        .sub-card.s-em_andamento       { border-left-color: #f59e0b; }
        .sub-card.s-aguardando_retorno { border-left-color: #3b82f6; }
        .sub-card.s-concluida          { border-left-color: #10b981; }
        .sub-card.overdue:not(.s-closed) { border-left-color: #dc2626; }

        .sub-card-header {
            display: flex; align-items: center; gap: 10px; padding: 11px 16px;
            cursor: pointer; user-select: none; transition: background .12s;
        }
        .sub-card.s-closed .sub-card-header:hover { background: #f1f5f9; }
        .sub-card:not(.s-closed) .sub-card-header:hover { background: #f8fbff; }
        .sub-id { font-size: 10px; font-weight: 700; color: #94a3b8; min-width: 28px; flex-shrink: 0; }
        .sub-person { flex: 1; min-width: 0; }
        .sub-name { font-size: 13px; font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sub-area { font-size: 11px; color: var(--text-3); margin-top: 1px; }
        .sub-status-pill {
            font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 999px;
            white-space: nowrap; border: 1px solid transparent; flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .sub-status-pill::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
        .sp-aberta             { background:#ede9fe; color:#4c1d95; border-color:#c4b5fd; }
        .sp-pendente           { background:#ede9fe; color:#4c1d95; border-color:#c4b5fd; }
        .sp-em_andamento       { background:#fef3c7; color:#78350f; border-color:#fcd34d; }
        .sp-aguardando_retorno { background:#dbeafe; color:#1e40af; border-color:#93c5fd; }
        .sp-concluida          { background:#d1fae5; color:#064e3b; border-color:#6ee7b7; }
        .sp-encerrada_controlador { background:#f1f5f9; color:#64748b; border-color:#cbd5e1; }
        .sp-encerrada_controlador::before { opacity:.4; }
        .sub-dl-pill {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px;
            white-space: nowrap; flex-shrink: 0; border: 1px solid transparent;
        }
        .dl-ok      { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
        .dl-warn    { background:#fffbeb; color:#b45309; border-color:#fde68a; }
        .dl-urgent  { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
        .dl-overdue { background:#fef2f2; color:#dc2626; border-color:#fecaca; font-weight:700; }
        .dl-none    { background:#f8fafc; color:#94a3b8; border-color:#e2e8f0; }
        .dl-closed  { background:#f8fafc; color:#94a3b8; border-color:#e2e8f0; font-style:italic; font-size:10px; }
        .sub-chevron { font-size: 10px; color: #94a3b8; margin-left: 2px; transition: transform .18s; flex-shrink: 0; }
        .sub-card-body { border-top: 1px solid var(--border); background: #fafcff; }
        .sub-card.s-closed .sub-card-body { background: #f8fafc; }
        .sub-body-inner { padding: 12px 16px 14px; }
        .sub-actions-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
        .sub-status-control { display: flex; align-items: center; gap: 5px; margin-left: auto; }
        .sub-desc { font-size: 13px; color: var(--text-2); margin-bottom: 12px; padding: 8px 12px; background: #f1f5f9; border-radius: 8px; border-left: 3px solid #cbd5e1; }
        .sub-section-label { font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--text-3); margin-bottom: 7px; display: flex; align-items: center; gap: 5px; }
        .comment-thread { display: flex; flex-direction: column; gap: 7px; max-height: 220px; overflow-y: auto; margin-bottom: 8px; padding-right: 2px; }
        .comment-bubble { padding: 7px 10px; border-radius: 8px; font-size: 12px; line-height: 1.45; background: #fff; border: 1px solid var(--border); max-width: 88%; }
        .comment-bubble.mine { align-self: flex-end; background: #eff6ff; border-color: #bfdbfe; border-right: 3px solid #3b82f6; text-align: right; }
        .comment-bubble.theirs { align-self: flex-start; background: #f0fdf4; border-color: #bbf7d0; border-left: 3px solid #10b981; text-align: left; }
        .comment-bubble.internal { border-left-color: #1e3a8a; border-right-color: #1e3a8a; }
        .comment-meta { font-size: 10px; color: var(--text-3); margin-top: 3px; display: flex; gap: 8px; flex-wrap: wrap; }
        .comment-bubble.mine .comment-meta { justify-content: flex-end; }
        .comment-author { font-weight: 600; color: var(--text-2); }
        .sub-file-chip {
            display: inline-flex; align-items: center; gap: 5px;
            background: #f1f5f9; border: 1px solid var(--border); border-radius: 6px;
            padding: 4px 8px; font-size: 11px; color: var(--text-2); text-decoration: none;
        }
        .sub-file-chip:hover { background: #e2e8f0; color: var(--text); }
        .ext-link-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 10px 12px; font-size: 12px; }
        .ext-link-box .label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #92400e; margin-bottom: 4px; }

        /* ── Notes sub-tabs ── */
        .note-subtabs { display: flex; gap: 3px; background: #f1f5f9; border-radius: 8px; padding: 4px; margin-bottom: 14px; }
        .note-subtab { padding: 6px 14px; font-size: 12px; font-weight: 600; border: none; border-radius: 6px; background: transparent; color: var(--text-3); cursor: pointer; transition: all .12s; display: inline-flex; align-items: center; gap: 5px; }
        .note-subtab:hover { color: var(--text); background: #e2e8f0; }
        .note-subtab.active { background: #fff; color: #1e3a8a; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
        .note-subtab .cnt { font-size: 10px; background: #1e3a8a; color: #fff; border-radius: 999px; padding: 0 6px; line-height: 1.6; }
        .note-card { background: #fff; border: 1px solid var(--border); border-radius: 10px; padding: 12px 16px; margin-bottom: 8px; }
        .note-number { font-size: 16px; font-weight: 800; color: #1e3a8a; }
        .note-status-pill { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #f1f5f9; color: var(--text-2); border: 1px solid var(--border); white-space: nowrap; }
        .note-status-pill.late { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .note-meta { display: flex; flex-wrap: wrap; gap: 10px; font-size: 11px; color: var(--text-3); margin-top: 6px; }
        .note-meta-item strong { color: var(--text-2); }
        .prod-row { display: flex; gap: 10px; align-items: center; padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 6px; background: #fff; font-size: 12px; }
        .ps-1  { background:#ede9fe;color:#5b21b6;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap; }
        .ps-2  { background:#dbeafe;color:#1e40af;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap; }
        .ps-3  { background:#fef3c7;color:#78350f;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap; }
        .ps-4  { background:#fff7ed;color:#c2410c;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap; }
        .ps-5  { background:#d1fae5;color:#065f46;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap; }
        .ps-30,.ps-31{background:#fee2e2;color:#b91c1c;font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;white-space:nowrap;}

        /* ── Sub dispatch form ── */
        .sub-create-panel {
            background: #f0fdf4; border: 2px solid #bbf7d0; border-radius: 12px;
            padding: 16px; margin-bottom: 14px;
        }
        .sub-create-panel .panel-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: #065f46; margin-bottom: 12px;
        }
        .sub-edit-panel {
            background: #eff6ff; border: 2px solid #bfdbfe; border-radius: 12px;
            padding: 16px; margin-bottom: 14px;
        }
        .sub-edit-panel .panel-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            color: #1e40af; margin-bottom: 12px;
        }

        /* ── Files tab ── */
        .files-filter-bar { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
        .files-filter-btn {
            font-size: 11px; font-weight: 700; padding: 5px 13px; border-radius: 999px;
            border: 1px solid var(--border); background: #fff; color: var(--text-3);
            cursor: pointer; transition: all .12s;
        }
        .files-filter-btn.active { background: #1e3a8a; color: #fff; border-color: #1e3a8a; }

        .file-list-item {
            display: flex; align-items: center; gap: 10px; padding: 9px 12px;
            border: 1px solid var(--border); border-radius: 8px; background: #fff;
            margin-bottom: 7px;
        }
        .file-icon { font-size: 20px; flex-shrink: 0; }
        .file-info { flex: 1; min-width: 0; }
        .file-name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .file-meta { font-size: 11px; color: var(--text-3); }
        .vis-badge {
            font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 999px;
            border: 1px solid transparent; white-space: nowrap; flex-shrink: 0;
        }
        .vis-internal { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        .vis-shared   { background: #dcfce7; color: #15803d; border-color: #86efac; }

        .upload-zone {
            border: 2px dashed #93c5fd; background: linear-gradient(180deg,#f8fbff,#eef6ff);
            border-radius: 12px; padding: 16px; margin-top: 16px;
        }
        .upload-zone-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .upload-kpis { display: flex; gap: 6px; flex-wrap: wrap; }
        .upload-chip {
            font-size: 11px; font-weight: 700; color: #1e3a8a; background: #dbeafe;
            border: 1px solid #93c5fd; border-radius: 999px; padding: 2px 9px;
        }
        .queue-list { display: grid; gap: 7px; margin-top: 10px; }
        .queue-item {
            display: flex; align-items: center; gap: 8px; border: 1px solid var(--border);
            border-left: 4px solid #3b82f6; background: #fff; border-radius: 9px; padding: 8px 10px;
            box-shadow: 0 1px 4px rgba(15,23,42,.04);
        }
        .queue-meta { font-size: 11px; color: var(--text-3); margin-top: 3px; }
        .queue-empty { border: 1px dashed var(--border-2); background: #fff; border-radius: 9px; padding: 10px; font-size: 12px; color: var(--text-3); }

        /* ── Communication tab (chat) ── */
        .chat-compose { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .chat-thread { display: flex; flex-direction: column; gap: 10px; }
        .chat-bubble {
            display: flex; gap: 10px; align-items: flex-start;
        }
        .chat-avatar {
            width: 32px; height: 32px; border-radius: 50%; background: #1e3a8a;
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 700; flex-shrink: 0;
        }
        .chat-content { background: #fff; border: 1px solid var(--border); border-radius: 0 10px 10px 10px; padding: 8px 12px; flex: 1; }
        .chat-content.internal { border-left: 3px solid #1e3a8a; background: #f0f7ff; }
        .chat-content.shared { border-left: 3px solid #059669; }
        .chat-header { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 12px; }
        .chat-name { font-weight: 700; }
        .chat-time { color: var(--text-3); font-size: 11px; }
        .chat-text { font-size: 13px; line-height: 1.45; }
        .chat-visibility-control { margin-left: auto; min-width: 165px; }
        .chat-visibility-control .form-select { font-size: 11px; padding-top: 2px; padding-bottom: 2px; }

        /* ── Stats bar ── */
        .stats-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px; }
        .stat-chip {
            display: flex; align-items: center; gap: 6px;
            background: #fff; border: 1px solid var(--border); border-radius: 8px;
            padding: 7px 12px; font-size: 12px;
        }
        .stat-chip .num { font-size: 18px; font-weight: 700; color: var(--text); }
        .stat-chip .lbl { font-size: 11px; color: var(--text-3); }
        .stat-chip.danger { border-color: var(--red-border); background: var(--red-bg); }
        .stat-chip.danger .num { color: var(--red); }
        .stat-chip.success { border-color: var(--green-border); background: var(--green-bg); }
        .stat-chip.success .num { color: var(--green); }

        /* ── Tab two-line ── */
        .ld-tab { flex-direction: column; align-items: flex-start; gap: 2px; padding: 9px 16px; white-space: normal; min-width: 110px; }
        .ld-tab-main { display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; line-height: 1; }
        .ld-tab-sub { font-size: 10px; font-weight: 500; opacity: .7; line-height: 1; padding-left: 1px; }
        .ld-tab.active .ld-tab-sub { opacity: .8; }
        .ld-tab-badge { margin-left: 2px; }

        /* ── Alert banners ── */
        .alert-overdue {
            background: linear-gradient(135deg, #7f1d1d, #991b1b);
            color: #fff; border-radius: 10px; padding: 14px 18px;
            display: flex; align-items: center; gap: 14px; margin-bottom: 14px;
            box-shadow: 0 4px 16px rgba(185,28,28,.35);
        }
        .alert-overdue-icon { font-size: 28px; flex-shrink: 0; }
        .alert-overdue-title { font-size: 15px; font-weight: 700; }
        .alert-overdue-body { font-size: 13px; opacity: .88; margin-top: 2px; }
        .alert-overdue-days {
            margin-left: auto; flex-shrink: 0; text-align: center;
            background: rgba(255,255,255,.18); border-radius: 10px; padding: 8px 14px;
        }
        .alert-overdue-days .num { font-size: 28px; font-weight: 800; line-height: 1; }
        .alert-overdue-days .lbl { font-size: 10px; opacity: .8; text-transform: uppercase; letter-spacing: .04em; }

        .alert-inactivity {
            background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b;
            border-radius: 8px; padding: 10px 14px; margin-bottom: 14px;
            display: flex; align-items: center; gap: 10px; font-size: 13px; color: #78350f;
        }

        /* ── Situação Atual (status cards) ── */
        .situacao-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px;
        }
        .situacao-card {
            background: #fff; border: 1px solid var(--border); border-radius: 10px;
            padding: 12px 14px; border-top: 4px solid var(--border-2); position: relative; overflow: hidden;
        }
        .situacao-card.sc-ok     { border-top-color: #10b981; }
        .situacao-card.sc-open   { border-top-color: #3b82f6; }
        .situacao-card.sc-warn   { border-top-color: #f59e0b; }
        .situacao-card.sc-danger { border-top-color: #dc2626; background: #fff5f5; }
        .situacao-card.sc-info   { border-top-color: #6366f1; }
        .sc-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--text-3); margin-bottom: 6px; }
        .sc-value { font-size: 15px; font-weight: 700; color: var(--text); line-height: 1.25; }
        .sc-value.danger { color: #dc2626; }
        .sc-value.warn   { color: #d97706; }
        .sc-sub { font-size: 11px; color: var(--text-3); margin-top: 4px; line-height: 1.3; }
        .sc-sub.danger { color: #dc2626; }

        /* ── Redirect context block ── */
        .redirect-block {
            background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6;
            border-radius: 8px; padding: 10px 14px; margin-bottom: 10px;
        }
        .redirect-block .rb-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #1d4ed8; margin-bottom: 4px; }
        .redirect-block .rb-area  { font-size: 14px; font-weight: 700; color: #1e3a8a; }
        .redirect-block .rb-meta  { font-size: 11px; color: #3b82f6; margin-top: 3px; }

        /* ── Field highlights ── */
        .field.f-critical { background: #fff1f2; border-color: #fecaca; }
        .field.f-warn     { background: #fffbeb; border-color: #fde68a; }
        .field.f-info     { background: #eff6ff; border-color: #bfdbfe; }
        .field.f-person   { background: #f5f3ff; border-color: #ddd6fe; }
        .fv.critical { color: #dc2626; font-weight: 700; }
        .fv.warn     { color: #b45309; font-weight: 600; }
        .fv.person   { color: #5b21b6; font-weight: 600; }

        /* ── Responsive ── */
        @media (max-width: 1100px) { .situacao-grid { grid-template-columns: repeat(2,1fr); } }
        @media (max-width: 900px) {
            .ld-main { grid-template-columns: 1fr; }
            .fg-2, .fg-3 { grid-template-columns: 1fr; }
            .ld-case { font-size: 22px; }
            .situacao-grid { grid-template-columns: repeat(2,1fr); }
        }
        @media (max-width: 600px) { .situacao-grid { grid-template-columns: 1fr 1fr; } }
    </style>

    @php
        $srcType  = $demand->source_type instanceof \BackedEnum ? $demand->source_type->value : (string) $demand->source_type;
        $tipoLbl  = match($srcType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => $srcType ?: '—' };

        $specificPayload = is_array($demand->source_specific_payload) ? $demand->source_specific_payload : [];
        $rawPayload      = is_array($demand->raw_payload) ? $demand->raw_payload : [];

        $sourceContext = [
            ['label' => 'Status processo (externo)', 'value' => $demand->external_status],
            ['label' => 'Status fluxo (origem)',     'value' => $demand->source_status],
            ['label' => 'Status fluxo normalizado',  'value' => $demand->source_status_group],
            ['label' => 'Área solicitante',          'value' => $demand->requesting_area_name],
            ['label' => 'Responsável solicitante',   'value' => $demand->requesting_responsible_name],
            ['label' => 'Área responsável',          'value' => $demand->responsible_area_name],
            ['label' => 'Responsável da demanda',    'value' => $demand->delegated_responsible_name],
            ['label' => 'Delegado por',              'value' => $demand->delegated_by_name],
            ['label' => 'Delegado em',               'value' => $demand->delegated_at],
            ['label' => 'Cidade',    'value' => data_get($specificPayload, 'city')    ?? data_get($rawPayload, 'city')],
            ['label' => 'Região',    'value' => data_get($specificPayload, 'region')  ?? data_get($rawPayload, 'region')],
            ['label' => 'Regional',  'value' => data_get($specificPayload, 'regional') ?? data_get($rawPayload, 'regional')],
            ['label' => 'Obs. origem', 'value' => data_get($specificPayload, 'observation') ?? data_get($rawPayload, 'observation')],
        ];

        $caseFiles = $demand->legalCase?->files ?? collect();
        $activeFiles = $caseFiles->where('removed_at', null)->sortByDesc('created_at')->values();
        $filesByType = [
            'injunction' => $activeFiles->filter(fn ($file) => ($file->legalDemand?->source_type instanceof \BackedEnum ? $file->legalDemand->source_type->value : (string) ($file->legalDemand?->source_type ?? '')) === 'injunction')->count(),
            'subsidy' => $activeFiles->filter(fn ($file) => ($file->legalDemand?->source_type instanceof \BackedEnum ? $file->legalDemand->source_type->value : (string) ($file->legalDemand?->source_type ?? '')) === 'subsidy')->count(),
            'sentence' => $activeFiles->filter(fn ($file) => ($file->legalDemand?->source_type instanceof \BackedEnum ? $file->legalDemand->source_type->value : (string) ($file->legalDemand?->source_type ?? '')) === 'sentence')->count(),
        ];
        $imageExts   = ['jpg','jpeg','png','gif','bmp','svg','tiff','webp'];
        $imageFiles  = $activeFiles->filter(function ($file) use ($imageExts) {
            $name = (string) ($file->original_name ?? $file->file_name ?? $file->path ?? '');
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $mime = strtolower((string) ($file->mime_type ?? ''));
            return in_array($ext, $imageExts, true) || str_starts_with($mime, 'image/');
        })->values();
        $otherFiles  = $activeFiles->reject(fn ($f) => $imageFiles->contains('id', $f->id))->values();

        $visibleSubdemands = $demand->subdemands->reject(fn ($s) => (bool) data_get($s->metadata ?? [], 'removed_by_controller', false));
        $overdueCount  = $visibleSubdemands->filter(fn ($s) => !in_array(
            $s->status instanceof \BackedEnum ? $s->status->value : (string) $s->status,
            ['concluida','encerrada_controlador']
        ) && $s->deadline_at && $s->deadline_at->isPast())->count();

        $fileIcon = fn (string $name) => match(strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'pdf'                         => 'bi-filetype-pdf text-danger',
            'jpg','jpeg','png','gif','webp','bmp' => 'bi-file-image text-info',
            'xls','xlsx','csv'            => 'bi-file-earmark-spreadsheet text-success',
            'doc','docx','odt'            => 'bi-file-earmark-word text-primary',
            'zip','rar','7z'              => 'bi-file-earmark-zip text-warning',
            default                       => 'bi-file-earmark text-secondary',
        };

        $queuedCount = is_array($uploadFiles ?? null) ? count($uploadFiles) : 0;

        // ── Urgência e prazo ──
        $dueDate     = $demand->source_due_at ? \Carbon\Carbon::parse($demand->source_due_at) : null;
        $isOverdue   = $dueDate && $dueDate->isPast();
        $overdueDays = $isOverdue ? (int) now()->diffInDays($dueDate) : 0;
        $daysUntilDue = (!$isOverdue && $dueDate) ? (int) now()->diffInDays($dueDate) : null;

        // ── Campos enriquecidos do raw_payload ──
        $processManager  = data_get($rawPayload, 'process_manager') ?? data_get($rawPayload, 'process_responsible') ?? null;
        $responsibleArea = $demand->responsible_area_name ?? data_get($rawPayload, 'responsible_area') ?? null;
        $requestingArea  = $demand->requesting_area_name  ?? data_get($rawPayload, 'requesting_area')  ?? null;

        // Data em que o status da fonte mudou (raw_payload tem mais precisão)
        $rawStatusAt = data_get($rawPayload, 'subsidy_status_at')
            ?? data_get($rawPayload, 'status_at')
            ?? data_get($rawPayload, 'analysis_at')
            ?? $demand->source_status_at ?? null;
        $statusChangedAt = $rawStatusAt ? \Carbon\Carbon::parse((string)$rawStatusAt) : null;

        // Redirecionamento
        $isRedirected = str_contains(strtolower((string)($demand->source_status_group ?? '')), 'redirect')
            || str_contains(strtolower((string)($demand->source_status ?? '')), 'redireciona');

        // Inatividade: último evento registrado
        $lastEventAt = $demand->events->max('occurred_at') ?? $demand->events->max('created_at');
        $inactivityDays = $lastEventAt ? (int) now()->diffInDays(\Carbon\Carbon::parse($lastEventAt)) : null;
        $isInactive = $inactivityDays !== null && $inactivityDays >= 7;

        // Contagens de subdemandas
        $subActiveCount = $visibleSubdemands->filter(fn($s) => !in_array(
            $s->status instanceof \BackedEnum ? $s->status->value : (string)$s->status,
            ['concluida','encerrada_controlador']
        ))->count();
        $subClosedCount = $visibleSubdemands->count() - $subActiveCount;

        // Comentários na demanda principal (sem subdemanda)
        $demandCommentCount = $demand->comments->where('legal_demand_subdemand_id', null)->count();

        // Cor semântica do status da fonte
        $srcStatusClass = match(true) {
            str_contains(strtolower((string)($demand->source_status_group ?? '')), 'closed') => 'sc-ok',
            $isRedirected => 'sc-warn',
            str_contains(strtolower((string)($demand->source_status_group ?? '')), 'open')   => 'sc-open',
            default => 'sc-info',
        };

        // Internal status label legível — usa $statusValue (string resolvida pelo render())
        $intStatusLabel = match($statusValue) {
            'new_imported'              => 'Importada',
            'triage'                    => 'Em triagem',
            'waiting_controller_action' => 'Aguarda ação do controlador',
            'sent_to_field'             => 'Enviada ao campo',
            'field_received'            => 'Campo recebeu',
            'waiting_field_response'    => 'Aguarda retorno do campo',
            'returned_by_field'         => 'Retornada pelo campo',
            'returned_for_correction'   => 'Aguarda correção do campo',
            'under_controller_review'   => 'Em revisão pelo controlador',
            'ready_to_close_external'   => 'Pronta para fechar',
            'closed_internal'           => 'Encerrada internamente',
            'closed_external'           => 'Encerrada externamente',
            'reopened'                  => 'Reaberta',
            'cancelled'                 => 'Cancelada',
            'ignored'                   => 'Ignorada',
            default                     => \Illuminate\Support\Str::headline(str_replace('_', ' ', (string)($statusValue ?? 'Indefinido'))),
        };
    @endphp

    <div class="ld-wrap">

        {{-- ═══════════════════════════════════════════════════════════ HEADER --}}
        <div class="ld-header" data-case="{{ $demand->source_case_number ?? $demand->id }}">
            <div class="ld-header-top">
                <div class="ld-header-left">
                    <a href="{{ route('legal.queue') }}" class="ld-back">← Voltar para Fila</a>
                    <div class="ld-company">{{ $demand->legalCase->company_name ?? '—' }}</div>
                    <h1 class="ld-case">Demanda #{{ $demand->source_case_number ?? $demand->id }}</h1>
                    <div class="ld-subject">{{ $demand->source_subject ?? 'Sem assunto' }}</div>
                    <div class="ld-badges">
                        <span class="ld-badge b-blue">{{ $tipoLbl }}</span>
                        <span class="ld-badge b-gray">{{ $demand->external_status ?? 'Sem status externo' }}</span>
                        <span class="ld-badge {{ str_contains((string)($demand->source_status_group ?? ''), 'closed') ? 'b-green' : 'b-amber' }}">
                            {{ $demand->source_status ?? 'Sem status' }}
                        </span>
                        <span class="ld-badge b-gray">{{ $demand->legalCase->law_firm_name ?? 'Sem escritório' }}</span>
                        @if($demand->source_due_at)
                            @php $due = \Carbon\Carbon::parse($demand->source_due_at); @endphp
                            <span class="ld-badge {{ $due->isPast() ? 'b-red' : ($due->diffInDays(now()) <= 3 ? 'b-amber' : 'b-gray') }}">
                                Prazo: {{ $due->format('d/m/Y') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="ld-header-right">
                    @if($subdemandsFeatureEnabled && !$isExternallyClosed)
                        @switch($statusValue)
                            @case('new_imported')
                            @case('reopened')
                                <button class="btn btn-warning btn-sm" wire:click="startTriage"><i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem</button>
                                @break
                            @case('returned_by_field')
                            @case('under_controller_review')
                                <button class="btn btn-success btn-sm" wire:click="approveReturn"><i class="bi bi-check2-all me-1"></i>Aprovar Retorno</button>
                                @break
                            @case('returned_for_correction')
                                <span class="ld-badge b-amber" style="font-size:11px">Aguardando correção do campo</span>
                                @break
                            @case('ready_to_close_external')
                                <button class="btn btn-success btn-sm" wire:click="$toggle('showCloseForm')"><i class="bi bi-check-circle me-1"></i>Confirmar Fechamento</button>
                                @break
                        @endswitch
                    @endif
                    @if($isExternallyClosed)
                        <span class="ld-badge b-gray" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3)">
                            Encerrado externamente
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ TABS --}}
        <div class="ld-tabs">
            {{-- Processo --}}
            <button class="ld-tab" :class="{ active: tab === 'process' }" @click="setTab('process')">
                <div class="ld-tab-main">
                    <i class="bi bi-file-earmark-text"></i> Processo
                    @if($isOverdue) <span class="ld-tab-badge">!</span> @endif
                </div>
                <div class="ld-tab-sub">{{ $tipoLbl }} · {{ $demand->legalCase->process_nature ?? '' }}</div>
            </button>

            {{-- Subdemandas --}}
            <button class="ld-tab" :class="{ active: tab === 'subdemands' }" @click="setTab('subdemands')">
                <div class="ld-tab-main">
                    <i class="bi bi-diagram-3"></i> Subdemandas
                    @if($overdueCount > 0)
                        <span class="ld-tab-badge">{{ $overdueCount }} ⚠</span>
                    @elseif($subActiveCount > 0)
                        <span class="ld-tab-badge ok">{{ $subActiveCount }}</span>
                    @endif
                </div>
                <div class="ld-tab-sub">
                    {{ $subActiveCount }} ativa{{ $subActiveCount != 1 ? 's' : '' }}
                    @if($subClosedCount > 0) · {{ $subClosedCount }} encerrada{{ $subClosedCount != 1 ? 's' : '' }} @endif
                </div>
            </button>

            {{-- Arquivos --}}
            <button class="ld-tab" :class="{ active: tab === 'files' }" @click="setTab('files')">
                <div class="ld-tab-main">
                    <i class="bi bi-paperclip"></i> Arquivos
                    @if($activeFiles->count() > 0)
                        <span class="ld-tab-badge ok">{{ $activeFiles->count() }}</span>
                    @endif
                </div>
                <div class="ld-tab-sub">
                    {{ $activeFiles->count() }} arquivo{{ $activeFiles->count() != 1 ? 's' : '' }}
                    @if($activeFiles->where('visibility','shared')->count() > 0)
                        · {{ $activeFiles->where('visibility','shared')->count() }} compartilhado{{ $activeFiles->where('visibility','shared')->count() != 1 ? 's' : '' }}
                    @endif
                </div>
            </button>

            {{-- Comunicação --}}
            <button class="ld-tab" :class="{ active: tab === 'communication' }" @click="setTab('communication')">
                <div class="ld-tab-main">
                    <i class="bi bi-chat-dots"></i> Comunicação
                    @if($demandCommentCount > 0)
                        <span class="ld-tab-badge ok">{{ $demandCommentCount }}</span>
                    @endif
                </div>
                <div class="ld-tab-sub">
                    {{ $demandCommentCount }} comentário{{ $demandCommentCount != 1 ? 's' : '' }} na demanda
                </div>
            </button>

            {{-- Notes --}}
            <button class="ld-tab" :class="{ active: tab === 'notes' }" @click="setTab('notes')">
                <div class="ld-tab-main">
                    <i class="bi bi-journal-text"></i> Notes
                    @if($linkedNotes->count() > 0)
                        <span class="ld-tab-badge ok">{{ $linkedNotes->count() }}</span>
                    @endif
                </div>
                <div class="ld-tab-sub">
                    {{ $linkedNotes->count() }} vinculada{{ $linkedNotes->count() != 1 ? 's' : '' }} ao processo
                </div>
            </button>
        </div>

        {{-- ═══════════════════════════════════════════════════════════ TAB CONTENT --}}
        <div class="ld-tab-content" style="padding: 16px;">

            {{-- ─────────────────────────────────────── ABA: PROCESSO --}}
            <div x-show="tab === 'process'">

                {{-- ── Banner: prazo vencido ── --}}
                @if($isOverdue)
                    <div class="alert-overdue">
                        <div class="alert-overdue-icon">⚠</div>
                        <div>
                            <div class="alert-overdue-title">Prazo de devolução vencido</div>
                            <div class="alert-overdue-body">
                                A devolução deveria ter ocorrido em <strong>{{ $dueDate->format('d/m/Y') }}</strong>.
                                @if($statusChangedAt)
                                    O status "<strong>{{ $demand->source_status }}</strong>" foi registrado em {{ $statusChangedAt->format('d/m/Y') }} — sem resolução desde então.
                                @endif
                            </div>
                        </div>
                        <div class="alert-overdue-days">
                            <div class="num">{{ $overdueDays }}</div>
                            <div class="lbl">dias<br>vencido</div>
                        </div>
                    </div>
                @endif

                {{-- ── Banner: inatividade ── --}}
                @if($isInactive)
                    <div class="alert-inactivity">
                        <i class="bi bi-clock-history" style="font-size:18px;flex-shrink:0"></i>
                        <span>
                            <strong>Demanda sem atividade há {{ $inactivityDays }} dia{{ $inactivityDays != 1 ? 's' : '' }}</strong>
                            — nenhum evento, comentário ou arquivo registrado recentemente nesta demanda no SICODE.
                        </span>
                    </div>
                @endif

                {{-- ── Cards de Situação Atual ── --}}
                <div class="situacao-grid">
                    {{-- Status externo --}}
                    <div class="situacao-card sc-open">
                        <div class="sc-label">Status externo (processo)</div>
                        <div class="sc-value">{{ $demand->external_status ?? '—' }}</div>
                        <div class="sc-sub">{{ $demand->legalCase->process_nature ?? '' }} · {{ $demand->legalCase->district ?? '' }}</div>
                    </div>

                    {{-- Status da fonte / subsídio --}}
                    <div class="situacao-card {{ $srcStatusClass }}">
                        <div class="sc-label">Status {{ $tipoLbl }} (fonte)</div>
                        <div class="sc-value {{ $isRedirected ? 'warn' : '' }}">{{ $demand->source_status ?? '—' }}</div>
                        <div class="sc-sub {{ $isRedirected ? '' : '' }}">
                            @if($statusChangedAt) Desde {{ $statusChangedAt->format('d/m/Y') }} @endif
                            @if($responsibleArea && $isRedirected) · para: {{ $responsibleArea }} @endif
                        </div>
                    </div>

                    {{-- Status interno SICODE --}}
                    @php
                        $intStatClass = match($statusValue) {
                            'closed_internal','closed_external' => 'sc-ok',
                            'returned_by_field','under_controller_review' => 'sc-warn',
                            default => 'sc-info',
                        };
                    @endphp
                    <div class="situacao-card {{ $intStatClass }}">
                        <div class="sc-label">Status interno (SICODE)</div>
                        <div class="sc-value">{{ $intStatusLabel }}</div>
                        <div class="sc-sub">
                            @if($demand->controller_user_id) Controlador atribuído @else Sem controlador @endif
                        </div>
                    </div>

                    {{-- Prazo --}}
                    <div class="situacao-card {{ $isOverdue ? 'sc-danger' : ($daysUntilDue !== null && $daysUntilDue <= 5 ? 'sc-warn' : 'sc-info') }}">
                        <div class="sc-label">Prazo de devolução</div>
                        @if($dueDate)
                            <div class="sc-value {{ $isOverdue ? 'danger' : ($daysUntilDue !== null && $daysUntilDue <= 5 ? 'warn' : '') }}">
                                {{ $dueDate->format('d/m/Y') }}
                            </div>
                            <div class="sc-sub {{ $isOverdue ? 'danger' : '' }}">
                                @if($isOverdue) Vencido há {{ $overdueDays }} dia{{ $overdueDays != 1 ? 's' : '' }}
                                @elseif($daysUntilDue === 0) Vence hoje
                                @elseif($daysUntilDue !== null) {{ $daysUntilDue }} dia{{ $daysUntilDue != 1 ? 's' : '' }} restantes
                                @endif
                            </div>
                        @else
                            <div class="sc-value" style="color:var(--text-3)">Sem prazo</div>
                            <div class="sc-sub">Não definido na fonte</div>
                        @endif
                    </div>
                </div>

                {{-- ── Bloco de redirecionamento (quando aplicável) ── --}}
                @if($isRedirected && $responsibleArea)
                    <div class="redirect-block">
                        <div class="rb-label"><i class="bi bi-arrow-right-circle me-1"></i>Redirecionado para área responsável</div>
                        <div class="rb-area">{{ $responsibleArea }}</div>
                        @if($statusChangedAt)
                            <div class="rb-meta">Redirecionado em {{ $statusChangedAt->format('d/m/Y \à\s H:i') }} — acompanhe se a área está ciente e agindo.</div>
                        @endif
                    </div>
                @endif

                <div class="ld-main">
                    {{-- Coluna principal: dados do processo --}}
                    <div>

                        {{-- ── Identificação ── --}}
                        <div class="ld-section">
                            <div class="ld-section-header">Identificação do processo</div>
                            <div class="ld-section-body">
                                <div class="field-grid fg-3" style="margin-bottom:8px">
                                    <div class="field"><div class="fl">Número do caso</div><div class="fv">{{ $demand->source_case_number ?? '—' }}</div></div>
                                    <div class="field"><div class="fl">Natureza</div><div class="fv">{{ $demand->legalCase->process_nature ?? '—' }}</div></div>
                                    <div class="field"><div class="fl">Status externo</div><div class="fv">{{ $demand->external_status ?? '—' }}</div></div>
                                </div>
                                <div class="field-grid fg-2" style="margin-bottom:8px">
                                    <div class="field"><div class="fl">Número CNJ</div><div class="fv mono">{{ $demand->source_process_number ?? '—' }}</div></div>
                                    <div class="field"><div class="fl">Comarca / Distrito</div><div class="fv">{{ $demand->legalCase->district ?? '—' }}</div></div>
                                </div>
                                <div class="field-grid fg-2" style="margin-bottom:8px">
                                    <div class="field"><div class="fl">Empresa</div><div class="fv">{{ $demand->legalCase->company_name ?? '—' }}</div></div>
                                    <div class="field"><div class="fl">Escritório / Advogados</div><div class="fv">{{ $demand->legalCase->law_firm_name ?? '—' }}</div></div>
                                </div>
                                @if($processManager)
                                    <div class="field-grid" style="margin-bottom:8px">
                                        <div class="field f-person">
                                            <div class="fl"><i class="bi bi-person-badge me-1"></i>Gestor do processo (escritório)</div>
                                            <div class="fv person">{{ $processManager }}</div>
                                        </div>
                                    </div>
                                @endif
                                @if($demand->legalCase->legal_responsible_name ?? null)
                                    <div class="field-grid">
                                        <div class="field f-person">
                                            <div class="fl"><i class="bi bi-person-check me-1"></i>Responsável jurídico interno</div>
                                            <div class="fv person">{{ $demand->legalCase->legal_responsible_name }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ── Tipo específico ── --}}
                        @if($srcType === 'subsidy')
                            <div class="ld-section">
                                <div class="ld-section-header">Detalhes do Subsídio</div>
                                <div class="ld-section-body">
                                    <div class="field-grid fg-3" style="margin-bottom:8px">
                                        <div class="field"><div class="fl">Assunto</div><div class="fv">{{ $demand->source_subject ?? '—' }}</div></div>
                                        <div class="field {{ $isRedirected ? 'f-warn' : '' }}">
                                            <div class="fl">Status do subsídio</div>
                                            <div class="fv {{ $isRedirected ? 'warn' : '' }}">{{ $demand->source_status ?? '—' }}</div>
                                        </div>
                                        <div class="field">
                                            <div class="fl">Status registrado em</div>
                                            <div class="fv">{{ $statusChangedAt ? $statusChangedAt->format('d/m/Y H:i') : '—' }}</div>
                                        </div>
                                    </div>
                                    <div class="field-grid fg-3">
                                        <div class="field {{ $requestingArea ? '' : 'f-info' }}">
                                            <div class="fl">Área solicitante</div>
                                            <div class="fv">{{ $requestingArea ?? '—' }}</div>
                                        </div>
                                        <div class="field {{ $responsibleArea ? 'f-info' : '' }}">
                                            <div class="fl">Área responsável</div>
                                            <div class="fv">{{ $responsibleArea ?? '—' }}</div>
                                        </div>
                                        <div class="field {{ $isOverdue ? 'f-critical' : ($daysUntilDue !== null && $daysUntilDue <= 5 ? 'f-warn' : '') }}">
                                            <div class="fl">Prazo de devolução</div>
                                            <div class="fv {{ $isOverdue ? 'critical' : ($daysUntilDue !== null && $daysUntilDue <= 5 ? 'warn' : '') }}">
                                                {{ $dueDate ? $dueDate->format('d/m/Y') : '—' }}
                                                @if($isOverdue) <small>({{ $overdueDays }}d vencido)</small> @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($srcType === 'sentence')
                            <div class="ld-section">
                                <div class="ld-section-header">Detalhes da Sentença</div>
                                <div class="ld-section-body">
                                    <div class="field-grid fg-3" style="margin-bottom:8px">
                                        <div class="field"><div class="fl">Status da sentença</div><div class="fv">{{ $demand->source_status ?? '—' }}</div></div>
                                        <div class="field"><div class="fl">Status em</div><div class="fv">{{ $statusChangedAt ? $statusChangedAt->format('d/m/Y H:i') : '—' }}</div></div>
                                        <div class="field"><div class="fl">Serviço / tipo</div><div class="fv">{{ data_get($specificPayload,'service_type') ?? data_get($rawPayload,'agreement_or_conviction') ?? '—' }}</div></div>
                                    </div>
                                    <div class="field-grid fg-2">
                                        <div class="field"><div class="fl">Data da decisão</div><div class="fv">{{ $demand->source_decision_at ? \Carbon\Carbon::parse($demand->source_decision_at)->format('d/m/Y') : '—' }}</div></div>
                                        <div class="field {{ $isOverdue ? 'f-critical' : '' }}">
                                            <div class="fl">Data de cumprimento</div>
                                            <div class="fv {{ $isOverdue ? 'critical' : '' }}">{{ ($demand->source_end_at ?? $demand->source_executed_at) ? \Carbon\Carbon::parse($demand->source_end_at ?? $demand->source_executed_at)->format('d/m/Y') : '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @elseif($srcType === 'injunction')
                            <div class="ld-section">
                                <div class="ld-section-header">Detalhes da Liminar</div>
                                <div class="ld-section-body">
                                    <div class="field-grid fg-3" style="margin-bottom:8px">
                                        <div class="field"><div class="fl">Status da liminar</div><div class="fv">{{ $demand->source_status ?? '—' }}</div></div>
                                        <div class="field"><div class="fl">Status em</div><div class="fv">{{ $statusChangedAt ? $statusChangedAt->format('d/m/Y H:i') : '—' }}</div></div>
                                        <div class="field"><div class="fl">Área requerida</div><div class="fv">{{ data_get($specificPayload,'required_area') ?? data_get($rawPayload,'required_area') ?? '—' }}</div></div>
                                    </div>
                                    <div class="field-grid fg-2">
                                        <div class="field">
                                            <div class="fl">Cidade / Região</div>
                                            <div class="fv">
                                                {{ data_get($specificPayload,'city') ?? data_get($rawPayload,'city') ?? '—' }}
                                                @if(data_get($specificPayload,'region') || data_get($rawPayload,'region'))
                                                    — {{ data_get($specificPayload,'region') ?? data_get($rawPayload,'region') }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="field {{ $isOverdue ? 'f-critical' : '' }}">
                                            <div class="fl">Prazo judicial</div>
                                            <div class="fv {{ $isOverdue ? 'critical' : '' }}">{{ $dueDate ? $dueDate->format('d/m/Y') : '—' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── Delegação (só exibe se tiver dado) ── --}}
                        @if($demand->delegated_responsible_name || $demand->delegated_at || $demand->delegated_by_name)
                            <div class="ld-section">
                                <div class="ld-section-header">Delegação</div>
                                <div class="ld-section-body">
                                    <div class="field-grid fg-3">
                                        <div class="field"><div class="fl">Delegado para</div><div class="fv">{{ $demand->delegated_responsible_name ?? '—' }}</div></div>
                                        <div class="field"><div class="fl">Delegado por</div><div class="fv">{{ $demand->delegated_by_name ?? '—' }}</div></div>
                                        <div class="field"><div class="fl">Delegado em</div><div class="fv">{{ $demand->delegated_at ? \Carbon\Carbon::parse($demand->delegated_at)->format('d/m/Y H:i') : '—' }}</div></div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- ── Contexto da fonte (apenas campos preenchidos) ── --}}
                        @php
                            $filledContext = collect($sourceContext)->filter(function($ctx) {
                                $v = $ctx['value'];
                                return !empty($v) && $v !== '—';
                            })->values();
                        @endphp
                        @if($filledContext->isNotEmpty())
                            <div class="ld-section">
                                <div class="ld-section-header">Contexto da fonte</div>
                                <div class="ld-section-body">
                                    <div class="field-grid fg-2">
                                        @foreach($filledContext as $ctx)
                                            @php
                                                $cv = $ctx['value'];
                                                if ($cv instanceof \Carbon\CarbonInterface) { $cv = $cv->format('d/m/Y H:i'); }
                                                elseif (is_string($cv) && preg_match('/^\d{4}-\d{2}-\d{2}/', $cv)) { try { $cv = \Carbon\Carbon::parse($cv)->format('d/m/Y H:i'); } catch (\Throwable) {} }
                                            @endphp
                                            <div class="field">
                                                <div class="fl">{{ $ctx['label'] }}</div>
                                                <div class="fv">{{ $cv }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>

                    {{-- Coluna lateral: ação + caso + histórico --}}
                    <div>
                        @if($subdemandsFeatureEnabled)
                        <div class="ld-panel">
                            <div class="ld-panel-header">Painel de ação</div>
                            <div class="ld-panel-body">
                                @if($isExternallyClosed)
                                    <div class="alert alert-dark small mb-0">Encerrado no sistema externo: {{ $demand->external_flow_status ?? $demand->external_status ?? '—' }}</div>
                                @else
                                    @if($currentAssignment && data_get($currentAssignment->metadata ?? [], 'external_dispatch'))
                                        <div class="alert alert-warning small mb-2">
                                            <div class="fw-bold mb-1">DEMANDA EXTERNA</div>
                                            Despachada para: <strong>{{ data_get($currentAssignment->metadata ?? [], 'external_contact_name', 'Contato externo') }}</strong>
                                            @if($currentAssignment && data_get($currentAssignment->metadata ?? [], 'external_contact_email'))
                                                <div class="small">{{ data_get($currentAssignment->metadata ?? [], 'external_contact_email') }}</div>
                                            @endif
                                            @if($currentAssignmentExternalExpiresAt)
                                                <div class="small mt-1">Link expira: <strong>{{ $currentAssignmentExternalExpiresAt->format('d/m/Y H:i') }}</strong></div>
                                            @endif
                                            @if($currentAssignmentExternalLink)
                                                <div class="input-group input-group-sm mt-2">
                                                    <input type="text" class="form-control" readonly value="{{ $currentAssignmentExternalLink }}" id="extLinkMain">
                                                    <button class="btn btn-dark" type="button" onclick="navigator.clipboard?.writeText(document.getElementById('extLinkMain').value);this.innerText='Copiado';setTimeout(()=>this.innerText='Copiar',1500)">Copiar</button>
                                                </div>
                                            @else
                                                <div class="small text-danger mt-1">Link expirado. Refaça o despacho.</div>
                                            @endif
                                        </div>
                                    @endif

                                    @switch($statusValue)
                                        @case('new_imported')
                                            <p class="text-muted small">Esta demanda ainda não entrou em triagem.</p>
                                            <button class="btn btn-warning w-100" wire:click="startTriage"><i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem</button>
                                            @break
                                        @case('triage')
                                        @case('waiting_controller_action')
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')"><i class="bi bi-lock me-1"></i>Fechar Internamente</button>
                                            </div>
                                            @break
                                        @case('sent_to_field')
                                        @case('field_received')
                                        @case('waiting_field_response')
                                            <div class="alert alert-info small mb-2">
                                                Aguardando retorno de <strong>{{ data_get($currentAssignment?->metadata ?? [], 'external_dispatch') ? (data_get($currentAssignment?->metadata ?? [], 'external_contact_name') ?? 'contato externo') : ($currentAssignment?->toUser?->name ?? 'executante') }}</strong>.
                                            </div>
                                            @break
                                        @case('returned_for_correction')
                                            <div class="alert alert-warning small mb-2">
                                                <i class="bi bi-arrow-return-left me-1"></i>
                                                <strong>Aguardando correção do campo.</strong><br>
                                                A resposta foi devolvida para <strong>{{ $currentAssignment?->toUser?->name ?? 'o executante' }}</strong> corrigir e reenviar.
                                            </div>
                                            <button class="btn btn-outline-secondary w-100 btn-sm" wire:click="$toggle('showCloseForm')"><i class="bi bi-lock me-1"></i>Fechar Internamente</button>
                                            @break
                                        @case('returned_by_field')
                                        @case('under_controller_review')
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-success" wire:click="approveReturn"><i class="bi bi-check2-all me-1"></i>Aprovar Retorno</button>
                                                <button class="btn btn-outline-warning" wire:click="$toggle('showReturnForm')"><i class="bi bi-arrow-return-left me-1"></i>Devolver para Correção</button>
                                                <button class="btn btn-outline-secondary" wire:click="$toggle('showCloseForm')"><i class="bi bi-lock me-1"></i>Fechar Internamente</button>
                                            </div>
                                            @break
                                        @case('ready_to_close_external')
                                            <button class="btn btn-success w-100" wire:click="$toggle('showCloseForm')"><i class="bi bi-check-circle me-1"></i>Confirmar Fechamento Externo</button>
                                            @break
                                        @case('closed_internal')
                                        @case('closed_external')
                                            <div class="alert alert-success small mb-2">Demanda encerrada.</div>
                                            @can('legal.demands.review')
                                                <button class="btn btn-outline-secondary w-100 btn-sm" wire:click="reopen"><i class="bi bi-arrow-clockwise me-1"></i>Reabrir</button>
                                            @endcan
                                            @break
                                        @case('reopened')
                                            <div class="alert alert-info small mb-2">Demanda reaberta. Inicie nova triagem ou reatribua.</div>
                                            <button class="btn btn-warning w-100" wire:click="startTriage"><i class="bi bi-clipboard-check me-1"></i>Iniciar Triagem</button>
                                            @break
                                        @case('cancelled')
                                        @case('ignored')
                                            <div class="alert alert-secondary small mb-2">Demanda {{ $statusValue === 'cancelled' ? 'cancelada' : 'ignorada' }}.</div>
                                            @can('legal.demands.review')
                                                <button class="btn btn-outline-secondary w-100 btn-sm" wire:click="reopen"><i class="bi bi-arrow-clockwise me-1"></i>Reabrir</button>
                                            @endcan
                                            @break
                                        @default
                                            <div class="alert alert-secondary small mb-0">
                                                Status: <strong>{{ $intStatusLabel }}</strong> — sem ação mapeada para este estado.
                                            </div>
                                    @endswitch

                                    @if(!empty($availableInternalActions))
                                        <hr class="my-2">
                                        <label class="form-label small fw-semibold mb-1">Alterar status interno</label>
                                        <div class="d-flex gap-2">
                                            <select class="form-select form-select-sm" wire:model.defer="internalAction">
                                                <option value="">Selecionar ação...</option>
                                                @foreach($availableInternalActions as $action)
                                                    <option value="{{ $action['value'] }}">{{ $action['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-primary" wire:click="applyInternalAction">OK</button>
                                        </div>
                                    @endif

                                    @if($showCloseForm)
                                        <hr class="my-2">
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Tipo de fechamento</label>
                                            <div>
                                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" wire:model="closureType" value="internal" id="ci"><label class="form-check-label small" for="ci">Interno (SICODE)</label></div>
                                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" wire:model="closureType" value="external" id="ce"><label class="form-check-label small" for="ce">Externo</label></div>
                                            </div>
                                        </div>
                                        <div class="mb-2"><label class="form-label small fw-semibold">Motivo *</label><textarea class="form-control form-control-sm" rows="3" wire:model.defer="closureReason"></textarea></div>
                                        @if($closureType === 'external')
                                            <div class="mb-2"><label class="form-label small fw-semibold">Protocolo externo *</label><input type="text" class="form-control form-control-sm" wire:model.defer="externalProtocol"></div>
                                            <div class="mb-2"><label class="form-label small fw-semibold">Data de fechamento externo</label><input type="date" class="form-control form-control-sm" wire:model.defer="externalClosedAt"></div>
                                        @endif
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showCloseForm', false)">Cancelar</button>
                                            <button class="btn btn-sm btn-dark flex-fill" wire:click="closeDemand">Confirmar Fechamento</button>
                                        </div>
                                    @endif

                                    @if($showReturnForm)
                                        <hr class="my-2">
                                        <div class="mb-2"><label class="form-label small fw-semibold">Motivo da devolução *</label><textarea class="form-control form-control-sm" rows="3" wire:model.defer="returnReason"></textarea></div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-secondary flex-fill" wire:click="$set('showReturnForm', false)">Cancelar</button>
                                            <button class="btn btn-sm btn-warning flex-fill" wire:click="returnForCorrection">Devolver</button>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        @endif

                        <div class="ld-panel">
                            <div class="ld-panel-header">Caso legal</div>
                            <div class="ld-panel-body">
                                <div class="kv"><div class="kl">Empresa</div><div class="kv-val">{{ $demand->legalCase->company_name ?? '—' }}</div></div>
                                <div class="kv"><div class="kl">Escritório / Advogados</div><div class="kv-val">{{ $demand->legalCase->law_firm_name ?? '—' }}</div></div>
                                @if($processManager)
                                    <div class="kv" style="background:#f5f3ff;border-radius:6px;padding:7px 8px;margin:3px 0">
                                        <div class="kl" style="color:#5b21b6"><i class="bi bi-person-badge me-1"></i>Gestor do processo</div>
                                        <div class="kv-val" style="font-weight:700;color:#4c1d95">{{ $processManager }}</div>
                                    </div>
                                @endif
                                @if($demand->legalCase->legal_responsible_name ?? null)
                                    <div class="kv"><div class="kl">Responsável jurídico interno</div><div class="kv-val">{{ $demand->legalCase->legal_responsible_name }}</div></div>
                                @endif
                                <div class="kv"><div class="kl">Natureza</div><div class="kv-val">{{ $demand->legalCase->process_nature ?? '—' }}</div></div>
                                <div class="kv"><div class="kl">Comarca / Distrito</div><div class="kv-val">{{ $demand->legalCase->district ?? '—' }}</div></div>
                                <div class="kv"><div class="kl">Processo externo (CNJ)</div><div class="kv-val" style="font-family:monospace;font-size:12px">{{ $demand->source_process_number ?? '—' }}</div></div>
                                <div class="kv {{ $isOverdue ? 'bg-danger bg-opacity-10 rounded px-2' : '' }}">
                                    <div class="kl {{ $isOverdue ? 'text-danger' : '' }}">Prazo de devolução</div>
                                    <div class="kv-val {{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                        {{ $dueDate ? $dueDate->format('d/m/Y') : '—' }}
                                        @if($isOverdue) <small class="ms-1">({{ $overdueDays }}d vencido)</small> @endif
                                    </div>
                                </div>
                                <div class="kv"><div class="kl">Primeiro registro no SICODE</div><div class="kv-val">{{ $demand->legalCase?->first_seen_at ? \Carbon\Carbon::parse($demand->legalCase->first_seen_at)->format('d/m/Y') : '—' }}</div></div>
                            </div>
                        </div>

                        <div class="ld-panel">
                            <div class="ld-panel-header">Histórico de eventos</div>
                            <div class="ld-panel-body">
                                <div class="event-timeline">
                                    @forelse($demand->events->sortByDesc('occurred_at') as $event)
                                        @php
                                            $when = $event->occurred_at ?? $event->created_at;
                                            $evLabel = \Illuminate\Support\Str::headline(str_replace('_', ' ', (string)($event->event_type ?? 'evento')));
                                        @endphp
                                        <div class="event-item">
                                            <div class="event-marker-wrap"><div class="event-marker"></div></div>
                                            <div class="event-content">
                                                <div class="event-top">
                                                    <span class="event-badge">{{ $evLabel }}</span>
                                                    <span class="event-time">{{ $when ? \Carbon\Carbon::parse($when)->format('d/m/Y H:i') : '—' }}</span>
                                                </div>
                                                <div class="event-title">{{ $event->description ?: $evLabel }}</div>
                                                <div class="event-actor"><i class="bi bi-person-circle me-1"></i>{{ $event->actor?->name ?: 'Sistema' }}</div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted small mb-0">Sem eventos registrados.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─────────────────────────────────────── ABA: SUBDEMANDAS --}}
            <div x-show="tab === 'subdemands'">

                {{-- Stats bar --}}
                @php
                    $subTotal      = $visibleSubdemands->count();
                    $subActive     = $visibleSubdemands->filter(fn ($s) => !in_array($s->status instanceof \BackedEnum ? $s->status->value : (string) $s->status, ['concluida','encerrada_controlador']))->count();
                    $subClosed     = $subTotal - $subActive;
                    $awaitingCount = $demand->assignments()
                        ->whereJsonContains('metadata->source', 'subdemand')
                        ->where('status', 'answered')
                        ->count();
                @endphp
                <div class="stats-bar">
                    <div class="stat-chip"><span class="num">{{ $subTotal }}</span><span class="lbl">Total</span></div>
                    <div class="stat-chip"><span class="num">{{ $subActive }}</span><span class="lbl">Ativas</span></div>
                    @if($awaitingCount > 0)
                        <div class="stat-chip" style="border-color:#fcd34d;background:#fefce8;cursor:default" title="Subdemandas com resposta aguardando sua revisão">
                            <span class="num" style="color:#78350f">{{ $awaitingCount }}</span>
                            <span class="lbl" style="color:#92400e"><i class="bi bi-bell-fill me-1"></i>Aguardam revisão</span>
                        </div>
                    @endif
                    @if($overdueCount > 0)
                        <div class="stat-chip danger"><span class="num">{{ $overdueCount }}</span><span class="lbl">Vencidas</span></div>
                    @endif
                    <div class="stat-chip success"><span class="num">{{ $subClosed }}</span><span class="lbl">Encerradas</span></div>
                    <div class="ms-auto">
                        @unless($canManageSubdemands)
                            <span class="text-muted small">Assuma a demanda para criar subdemandas</span>
                        @endunless
                        <button class="btn btn-sm btn-primary ms-2" wire:click="$toggle('showSubdemandForm')" @disabled(!$canManageSubdemands)>
                            <i class="bi bi-plus-lg me-1"></i>Nova Subdemanda
                        </button>
                    </div>
                </div>

                {{-- Painel de edição (quando aberto) --}}
                @if($showSubdemandActionForm)
                    <div class="sub-edit-panel">
                        <div class="panel-title"><i class="bi bi-pencil-square me-1"></i>Editando Subdemanda #{{ $subdemandActionId }}</div>
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Executante</label>
                                <select class="form-select form-select-sm" wire:model.defer="subdemandActionAssignedToUserId">
                                    <option value="">Manter atual</option>
                                    @foreach($fieldUsers as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}{{ $u->Company?->name ? ' · '.$u->Company->name : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Status destino</label>
                                <select class="form-select form-select-sm" wire:model.defer="subdemandActionToStatus">
                                    @foreach($subdemandStatuses as $status)
                                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold mb-1">Prazo</label>
                                <input type="datetime-local" class="form-control form-control-sm" wire:model.defer="subdemandActionDeadlineAt">
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Motivo</label>
                                <textarea class="form-control form-control-sm" rows="2" wire:model.defer="subdemandActionReason"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Descrição do evento</label>
                                <textarea class="form-control form-control-sm" rows="2" wire:model.defer="subdemandActionDescription"></textarea>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-secondary" wire:click="$set('showSubdemandActionForm', false)">Cancelar</button>
                            <button class="btn btn-sm btn-primary" wire:click="applySubdemandAction"><i class="bi bi-check2 me-1"></i>Salvar Status</button>
                            <button class="btn btn-sm btn-outline-primary" wire:click="applySubdemandReassignment"><i class="bi bi-person-arrows me-1"></i>Reatribuir</button>
                            <button class="btn btn-sm btn-outline-warning" wire:click="applySubdemandDeadline"><i class="bi bi-calendar2-event me-1"></i>Atualizar Prazo</button>
                        </div>
                    </div>
                @endif

                {{-- Painel de criação (quando aberto) --}}
                @if($showSubdemandForm)
                    <div class="sub-create-panel">
                        <div class="panel-title"><i class="bi bi-diagram-3 me-1"></i>Despachar Nova Subdemanda</div>
                        <div class="row g-2 mb-2">
                            @if(!$subdemandAssignAsExternal)
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Empresa</label>
                                    <select class="form-select form-select-sm" wire:model.lazy="subdemandCompanyFilter">
                                        <option value="">Todas</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Buscar usuário</label>
                                    <input type="text" class="form-control form-control-sm" wire:model.lazy="subdemandUserSearch" placeholder="Nome ou email">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Executante *</label>
                                    <select class="form-select form-select-sm" wire:model.defer="subdemandAssignedToUserId">
                                        <option value="">Selecionar...</option>
                                        @foreach($fieldUsers as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->Company?->name ? ' · '.$u->Company->name : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="extDisp" wire:model="subdemandAssignAsExternal">
                            <label class="form-check-label small fw-semibold" for="extDisp">Despachar para usuário externo</label>
                        </div>
                        @if($subdemandAssignAsExternal)
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold mb-1">Contato externo cadastrado</label>
                                    <select class="form-select form-select-sm" wire:model="subdemandExternalContactId">
                                        <option value="">Novo contato</option>
                                        @foreach($externalContacts as $contact)
                                            <option value="{{ $contact->id }}">{{ $contact->name }} — {{ $contact->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @if(!$subdemandExternalContactId)
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Nome externo *</label>
                                        <input type="text" class="form-control form-control-sm" wire:model.defer="subdemandExternalContactName">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Email externo *</label>
                                        <input type="email" class="form-control form-control-sm" wire:model.defer="subdemandExternalContactEmail">
                                    </div>
                                @endif
                            </div>
                        @endif
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Prazo</label>
                                <input type="datetime-local" class="form-control form-control-sm" wire:model.defer="subdemandDeadlineAt">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold mb-1">Instrução / Descrição</label>
                                <textarea class="form-control form-control-sm" rows="2" wire:model.defer="subdemandDescription" placeholder="Descreva o que deve ser feito..."></textarea>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="subEvidReq" wire:model="subdemandRequiresEvidence">
                                <label class="form-check-label small fw-semibold" for="subEvidReq">
                                    <i class="bi bi-paperclip me-1 text-warning"></i>Exigir evidência para enviar resposta
                                </label>
                            </div>
                            <div class="d-flex gap-2 ms-auto">
                                <button class="btn btn-sm btn-secondary" wire:click="$set('showSubdemandForm', false)">Cancelar</button>
                                <button class="btn btn-sm btn-success px-4" wire:click="createSubdemand"><i class="bi bi-send me-1"></i>Criar e Despachar</button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Cards de subdemanda --}}
                <div class="sub-deck">
                    @forelse($visibleSubdemands->sortByDesc('created_at') as $sub)
                        @php
                            $sv = $sub->status instanceof \BackedEnum ? $sub->status->value : (string) $sub->status;
                            $sl = $sub->status instanceof \App\Enum\LegalDemandSubdemandStatus
                                ? $sub->status->label()
                                : \Illuminate\Support\Str::headline(str_replace('_', ' ', $sv));
                            $isOpen  = !in_array($sv, ['concluida','encerrada_controlador']);
                            $isOverd = $isOpen && $sub->deadline_at && $sub->deadline_at->isPast();
                            $isToday = $isOpen && $sub->deadline_at && !$isOverd && $sub->deadline_at->isToday();
                            $isExt   = (bool) data_get($sub->metadata ?? [], 'external_dispatch');
                            $extLink = $subdemandExternalLinks[$sub->id] ?? null;
                            $manualClose = $sub->events->where('event_type','status_changed')->first(fn($e) => (string)$e->to_status === 'encerrada_controlador');
                            $lastEv  = $sub->events->sortByDesc('occurred_at')->first();

                            // Verifica se o executante já enviou resposta e aguarda ação do controlador
                            $answeredAssignment = $demand->assignments()
                                ->whereJsonContains('metadata->subdemand_id', $sub->id)
                                ->where('status', 'answered')
                                ->latest()
                                ->first();
                            $awaitingReview = $isOpen && $answeredAssignment !== null;

                            $subComments = $demand->comments
                                ->where('legal_demand_subdemand_id', $sub->id)
                                ->sortBy(fn ($comment) => $comment->created_at?->timestamp ?? 0)
                                ->values();
                            $subFiles    = $demand->files->where('removed_at',null)->where('legal_demand_subdemand_id',$sub->id)->sortByDesc('created_at')->take(10);
                        @endphp
                        <div class="sub-card s-{{ $sv }} {{ !$isOpen ? 's-closed' : '' }} {{ $isOverd ? 'overdue' : '' }}"
                             x-data="{ open: {{ ($isOpen || ($showSubdemandActionForm && $subdemandActionId == $sub->id)) ? 'true' : 'false' }} }">

                            {{-- ── Card header ── --}}
                            <div class="sub-card-header" @click="open = !open">
                                <div class="sub-id">#{{ $sub->id }}</div>

                                <div class="sub-person">
                                    <div class="sub-name">
                                        @if($isExt)
                                            <i class="bi bi-globe2 me-1" style="color:#f59e0b"></i>{{ data_get($sub->metadata ?? [], 'external_contact_name', 'Externo') }}
                                        @else
                                            {{ $sub->assignedTo?->name ?? 'Sem executante' }}
                                        @endif
                                    </div>
                                    <div class="sub-area">
                                        @if($isExt)
                                            {{ data_get($sub->metadata ?? [], 'external_contact_email', '') }}
                                        @else
                                            {{ $sub->assignedTo?->Company?->name ?? '' }}
                                        @endif
                                    </div>
                                </div>

                                {{-- Deadline pill com cor --}}
                                @php
                                    $dlClass = 'dl-none';
                                    $dlLabel = 'Sem prazo';
                                    if (!$isOpen) {
                                        $dlClass = 'dl-closed';
                                        $dlLabel = $sub->deadline_at ? $sub->deadline_at->format('d/m/Y') : '—';
                                    } elseif ($sub->deadline_at) {
                                        $dlLabel = $sub->deadline_at->format('d/m/Y');
                                        $daysLeft = now()->diffInDays($sub->deadline_at, false);
                                        $dlClass = $daysLeft < 0 ? 'dl-overdue' : ($daysLeft === 0 ? 'dl-urgent' : ($daysLeft <= 3 ? 'dl-warn' : 'dl-ok'));
                                    }
                                @endphp
                                <div class="sub-dl-pill {{ $dlClass }}">
                                    <i class="bi bi-calendar3"></i>
                                    {{ $dlLabel }}
                                    @if($isOpen && $sub->deadline_at)
                                        @php $dLeft = now()->diffInDays($sub->deadline_at, false); @endphp
                                        @if($dLeft < 0) <span style="font-size:10px">({{ abs((int)$dLeft) }}d vencido)</span>
                                        @elseif($dLeft === 0) <span style="font-size:10px">hoje</span>
                                        @elseif($dLeft <= 5) <span style="font-size:10px">({{ (int)$dLeft }}d)</span>
                                        @endif
                                    @endif
                                </div>

                                {{-- Badge "Aguarda revisão" — executante respondeu --}}
                                @if($awaitingReview)
                                    <span style="font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;background:#fef9c3;color:#78350f;border:1px solid #fcd34d;flex-shrink:0;display:inline-flex;align-items:center;gap:5px">
                                        <i class="bi bi-bell-fill"></i> Aguarda sua revisão
                                    </span>
                                @endif

                                {{-- Status pill proeminente --}}
                                <span class="sub-status-pill sp-{{ $sv }}">{{ $sl }}</span>

                                {{-- Badge externo --}}
                                @if($isExt)
                                    @if($sub->external_access_revoked_at)
                                        <span class="badge bg-danger" style="font-size:10px;flex-shrink:0">Link revogado</span>
                                    @elseif($sub->external_access_expires_at && $sub->external_access_expires_at->isPast())
                                        <span class="badge bg-warning text-dark" style="font-size:10px;flex-shrink:0">Link expirado</span>
                                    @else
                                        <span class="badge bg-success" style="font-size:10px;flex-shrink:0">Link ativo</span>
                                    @endif
                                @endif

                                <div class="sub-chevron" :class="{ 'rotate-180': open }">
                                    <i class="bi bi-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''"></i>
                                </div>
                            </div>

                            {{-- ── Card body (expansível) ── --}}
                            <div class="sub-card-body" x-show="open" x-transition>
                                <div class="sub-body-inner">

                                {{-- Descrição --}}
                                @if($sub->description)
                                    <div class="sub-desc"><i class="bi bi-card-text me-1 text-muted"></i>{{ $sub->description }}</div>
                                @endif

                                {{-- Ações: Abrir + gerenciar à esquerda, alterar status à direita --}}
                                <div class="sub-actions-bar">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('legal.subdemand.detail', $sub->uuid) }}">
                                        <i class="bi bi-arrow-up-right-square me-1"></i>Abrir
                                    </a>
                                    @if($canManageSubdemands)
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="openSubdemandAction({{ $sub->id }}, '{{ $sv }}')">
                                            <i class="bi bi-sliders me-1"></i>Editar
                                        </button>
                                        @if($isOpen)
                                            <button class="btn btn-sm btn-outline-danger" wire:click="removeSubdemand({{ $sub->id }})">
                                                <i class="bi bi-trash me-1"></i>Remover
                                            </button>
                                        @endif
                                    @endif
                                </div>

                                {{-- Externo: link --}}
                                @if($isExt)
                                    <div class="ext-link-box mb-3">
                                        <div class="label">Acesso externo</div>
                                        <div class="d-flex gap-2 flex-wrap mb-1">
                                            <button class="btn btn-sm btn-outline-primary" wire:click="regenerateSubdemandExternalAccess({{ $sub->id }})">
                                                <i class="bi bi-arrow-repeat me-1"></i>Gerar novo link
                                            </button>
                                            @if(!$sub->external_access_revoked_at)
                                                <button class="btn btn-sm btn-outline-danger" wire:click="revokeSubdemandExternalAccess({{ $sub->id }})">
                                                    <i class="bi bi-slash-circle me-1"></i>Revogar
                                                </button>
                                            @endif
                                        </div>
                                        @if($extLink)
                                            <div class="input-group input-group-sm">
                                                <input id="sub-link-{{ $sub->id }}" type="text" class="form-control" readonly value="{{ $extLink }}">
                                                <button class="btn btn-outline-secondary" type="button"
                                                        onclick="navigator.clipboard.writeText(document.getElementById('sub-link-{{ $sub->id }}').value);this.innerHTML='<i class=\'bi bi-check\'></i>';setTimeout(()=>this.innerHTML='Copiar',1500)">Copiar</button>
                                            </div>
                                        @else
                                            <div class="small text-muted">Clique em "Gerar novo link" para obter o link de acesso.</div>
                                        @endif
                                    </div>
                                @endif

                                {{-- ── Resposta do executante aguardando revisão ── --}}
                                @if($awaitingReview)
                                    <div style="background:#fefce8;border:2px solid #fcd34d;border-radius:10px;padding:14px;margin-bottom:14px">
                                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#78350f;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                                            <i class="bi bi-bell-fill text-warning"></i> Executante respondeu — aguardando sua ação
                                        </div>
                                        @if($answeredAssignment->answer)
                                            <div style="background:#fff;border-left:3px solid #f59e0b;border-radius:6px;padding:8px 12px;font-size:13px;margin-bottom:10px">
                                                {{ $answeredAssignment->answer }}
                                            </div>
                                        @endif
                                        <div style="font-size:11px;color:#92400e;margin-bottom:10px">
                                            Respondida por <strong>{{ $answeredAssignment->toUser?->name ?? 'Executante' }}</strong>
                                            em {{ $answeredAssignment->answered_at ? \Carbon\Carbon::parse($answeredAssignment->answered_at)->format('d/m/Y H:i') : '—' }}
                                            @if($answeredAssignment->answered_at)
                                                ({{ \Carbon\Carbon::parse($answeredAssignment->answered_at)->diffForHumans() }})
                                            @endif
                                        </div>
                                        @if($canManageSubdemands)
                                            <div class="d-flex gap-2">
                                                <a href="{{ route('legal.subdemand.detail', $sub->uuid) }}" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye me-1"></i>Ver resposta completa e decidir
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Encerramento manual --}}
                                @if($manualClose)
                                    <div class="alert alert-secondary small py-2 mb-3">
                                        Encerrada por <strong>{{ $manualClose->actor?->name ?: 'Sistema' }}</strong>
                                        @if($manualClose->reason) · {{ $manualClose->reason }} @endif
                                    </div>
                                @elseif($lastEv?->reason && !$isOpen)
                                    <div class="text-muted small mb-3">Último motivo: {{ $lastEv->reason }}</div>
                                @endif

                                {{-- ── Área de comunicação + arquivos (duas colunas com fundo distinto) ── --}}
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;border:1px solid var(--border);border-radius:10px;overflow:hidden">

                                    {{-- Comunicação --}}
                                    <div style="background:#fff;border-right:1px solid var(--border);padding:12px 14px">
                                        <div style="font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#1e40af;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                                            <i class="bi bi-chat-dots-fill"></i> Comunicação com executante
                                        </div>
                                        <div class="comment-thread auto-scroll-chat" style="max-height:180px">
                                            @forelse($subComments as $comment)
                                                @php $isMine = $comment->user_id === auth()->id(); @endphp
                                                <div class="comment-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                                    <div>{{ $comment->comment }}</div>
                                                    <div class="comment-meta">
                                                        <span class="comment-author">{{ $comment->user?->name ?: 'Externo' }}</span>
                                                        <span>{{ $comment->created_at?->format('d/m/Y H:i') }}</span>
                                                        @if(($comment->visibility ?? 'shared') === 'controller')
                                                            <span style="color:#1e3a8a;font-style:italic">interno</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-muted small mb-0">Nenhuma mensagem ainda.</p>
                                            @endforelse
                                        </div>
                                        <div class="input-group input-group-sm mt-2">
                                            <input type="text" class="form-control"
                                                   style="background:#fff"
                                                   wire:model.defer="subdemandControllerCommentInput.{{ $sub->id }}"
                                                   placeholder="Escrever para executante…"
                                                   wire:keydown.enter="addControllerSubdemandComment({{ $sub->id }})">
                                            <button class="btn btn-primary" wire:click="addControllerSubdemandComment({{ $sub->id }})">
                                                <i class="bi bi-send"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Arquivos --}}
                                    <div style="background:#f8fafc;padding:12px 14px">
                                        <div style="font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#475569;margin-bottom:8px;display:flex;align-items:center;gap:6px">
                                            <i class="bi bi-paperclip"></i> Arquivos ({{ $subFiles->count() }})
                                        </div>
                                        @if($subFiles->isNotEmpty())
                                            <div style="display:flex;flex-direction:column;gap:5px">
                                                @foreach($subFiles as $f)
                                                    @php
                                                        $fp = $f->path ?? $f->file_path ?? null;
                                                        $fu = $fp ? \Illuminate\Support\Facades\Storage::url($fp) : null;
                                                        $fn = $f->original_name ?? ($fp ? basename($fp) : 'Arquivo');
                                                    @endphp
                                                    @if($fu)
                                                        <a href="{{ $fu }}" target="_blank" class="sub-file-chip" title="{{ $fn }}" style="width:100%">
                                                            <i class="bi {{ $fileIcon($fn) }}"></i>
                                                            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px">{{ $fn }}</span>
                                                            <span style="font-size:10px;color:#94a3b8;flex-shrink:0">{{ strtoupper(pathinfo($fn, PATHINFO_EXTENSION)) }}</span>
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted small mb-0">Sem arquivos nesta subdemanda.</p>
                                        @endif
                                    </div>

                                </div>{{-- /grid comunicação+arquivos --}}
                                </div>{{-- /sub-body-inner --}}
                            </div>{{-- /sub-card-body --}}
                        </div>{{-- /sub-card --}}
                    @empty
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-diagram-3" style="font-size:36px;opacity:.3"></i>
                            <div class="mt-2">Nenhuma subdemanda vinculada.</div>
                            <div class="small">Crie subdemandas para delegar partes desta demanda a executantes.</div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- ─────────────────────────────────────── ABA: ARQUIVOS --}}
            <div x-show="tab === 'files'" x-data="{ filter: 'all' }">

                <div class="files-filter-bar">
                    <button class="files-filter-btn" :class="{ active: filter==='all' }" @click="filter='all'">
                        Todos ({{ $activeFiles->count() }})
                    </button>
                    <button class="files-filter-btn" :class="{ active: filter==='internal' }" @click="filter='internal'">
                        <i class="bi bi-lock me-1"></i>Internos ({{ $activeFiles->where('visibility','controller')->count() }})
                    </button>
                    <button class="files-filter-btn" :class="{ active: filter==='shared' }" @click="filter='shared'">
                        <i class="bi bi-eye me-1"></i>Compartilhados ({{ $activeFiles->where('visibility','shared')->count() }})
                    </button>
                    @if(($filesByType['injunction'] ?? 0) > 0)
                        <button class="files-filter-btn" :class="{ active: filter==='type-injunction' }" @click="filter='type-injunction'">
                            Liminar ({{ $filesByType['injunction'] }})
                        </button>
                    @endif
                    @if(($filesByType['subsidy'] ?? 0) > 0)
                        <button class="files-filter-btn" :class="{ active: filter==='type-subsidy' }" @click="filter='type-subsidy'">
                            Subsídio ({{ $filesByType['subsidy'] }})
                        </button>
                    @endif
                    @if(($filesByType['sentence'] ?? 0) > 0)
                        <button class="files-filter-btn" :class="{ active: filter==='type-sentence' }" @click="filter='type-sentence'">
                            Sentença ({{ $filesByType['sentence'] }})
                        </button>
                    @endif
                    @if($imageFiles->isNotEmpty())
                        <button class="files-filter-btn" :class="{ active: filter==='images' }" @click="filter='images'">
                            <i class="bi bi-images me-1"></i>Imagens ({{ $imageFiles->count() }})
                        </button>
                    @endif
                </div>
                <div class="small text-muted mb-3">
                    Exibindo anexos do caso legal completo. O tipo indica a demanda onde o arquivo foi anexado originalmente.
                </div>

                {{-- Galeria de imagens --}}
                @if($imageFiles->isNotEmpty())
                    <div x-show="filter === 'all' || filter === 'images' || filter === 'internal' || filter === 'shared'">
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin-bottom:16px">
                            @foreach($imageFiles as $index => $file)
                                @php
                                    $fp  = $file->path ?? $file->file_path ?? null;
                                    $fu  = $fp ? \Illuminate\Support\Facades\Storage::url($fp) : null;
                                    $fn  = $file->original_name ?? ($fp ? basename($fp) : 'Imagem');
                                    $vis = $file->visibility ?? 'controller';
                                    $fileType = $file->legalDemand?->source_type instanceof \BackedEnum ? $file->legalDemand->source_type->value : (string) ($file->legalDemand?->source_type ?? '');
                                    $fileTypeLabel = match($fileType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => 'Demanda' };
                                @endphp
                                @if($fu)
                                    <div
                                        x-show="filter === 'all' || filter === 'images' || (filter === 'internal' && '{{ $vis }}' === 'controller') || (filter === 'shared' && '{{ $vis }}' === 'shared') || filter === 'type-{{ $fileType }}'"
                                        style="border:1px solid #e2e8f0;border-radius:10px;background:#fff;padding:6px;text-align:center">
                                        <img src="{{ $fu }}" style="width:100%;height:110px;object-fit:cover;border-radius:7px;cursor:pointer;border:1px solid #e2e8f0" alt="{{ $fn }}" data-bs-toggle="modal" data-bs-target="#filesModal" data-carousel-slide="{{ $index }}">
                                        <div style="font-size:11px;color:#64748b;margin-top:5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $fn }}">{{ $fn }}</div>
                                        <div class="d-flex gap-1 justify-content-center mt-1">
                                            <span class="vis-badge vis-shared">{{ $fileTypeLabel }}</span>
                                            <span class="vis-badge {{ $vis === 'controller' ? 'vis-internal' : 'vis-shared' }}">
                                                {{ $vis === 'controller' ? '🔒 Interno' : '👁 Compartilhado' }}
                                            </span>
                                        </div>
                                        <a href="{{ $fu }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1" style="font-size:11px;padding:2px 8px">
                                            <i class="bi bi-download me-1"></i>Baixar
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Lista de arquivos --}}
                @if($otherFiles->isNotEmpty())
                    <div x-show="filter !== 'images'">
                        @foreach($otherFiles as $file)
                            @php
                                $fp  = $file->path ?? $file->file_path ?? null;
                                $fu  = $fp ? \Illuminate\Support\Facades\Storage::url($fp) : null;
                                $fn  = $file->original_name ?? ($fp ? basename($fp) : 'Arquivo');
                                $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                                $vis = $file->visibility ?? 'controller';
                                $fileType = $file->legalDemand?->source_type instanceof \BackedEnum ? $file->legalDemand->source_type->value : (string) ($file->legalDemand?->source_type ?? '');
                                $fileTypeLabel = match($fileType) { 'injunction' => 'Liminar', 'sentence' => 'Sentença', 'subsidy' => 'Subsídio', default => 'Demanda' };
                            @endphp
                            @if($fu)
                                <div class="file-list-item"
                                     x-show="filter === 'all' || (filter === 'internal' && '{{ $vis }}' === 'controller') || (filter === 'shared' && '{{ $vis }}' === 'shared') || filter === 'type-{{ $fileType }}'">
                                    <div class="file-icon"><i class="bi {{ $fileIcon($fn) }}"></i></div>
                                    <div class="file-info">
                                        <div class="file-name" title="{{ $fn }}">{{ $fn }}</div>
                                        <div class="file-meta">{{ strtoupper($ext ?: '?') }} · {{ $fileTypeLabel }} · {{ $file->uploadedBy?->name ?? 'Desconhecido' }} · {{ $file->created_at?->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <span class="vis-badge vis-shared">{{ $fileTypeLabel }}</span>
                                    <span class="vis-badge {{ $vis === 'controller' ? 'vis-internal' : 'vis-shared' }}">
                                        {{ $vis === 'controller' ? '🔒 Interno' : '👁 Compartilhado' }}
                                    </span>
                                    <div class="d-flex gap-2">
                                        <a href="{{ $fu }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Baixar</a>
                                        @if(($file->uploaded_by ?? null) === auth()->id())
                                            <button class="btn btn-sm btn-outline-danger" wire:click="removeFile({{ $file->id }})"><i class="bi bi-trash"></i></button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if($activeFiles->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-paperclip" style="font-size:32px;opacity:.3"></i>
                        <div class="mt-2 small">Nenhum arquivo anexado ainda.</div>
                    </div>
                @endif

                {{-- Upload zone --}}
                <div class="upload-zone">
                    <div class="upload-zone-head">
                        <div class="fw-semibold" style="font-size:13px">Enviar novos arquivos</div>
                        <div class="upload-kpis">
                            <span class="upload-chip">{{ $queuedCount }} na fila</span>
                            <span class="upload-chip">{{ $fileVisibility === 'controller' ? '🔒 Interno' : '👁 Compartilhado' }}</span>
                        </div>
                    </div>
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col">
                            <input type="file" class="form-control form-control-sm" wire:model="uploadFiles" multiple>
                            <div class="small text-muted mt-1">PDF, JPG, PNG, DOCX, XLSX — máx. 10 MB por arquivo</div>
                        </div>
                        <div class="col-auto">
                            <label class="form-label small fw-semibold mb-1">Visibilidade</label>
                            <select class="form-select form-select-sm" wire:model.defer="fileVisibility">
                                <option value="controller">🔒 Interno (só controlador)</option>
                                <option value="shared">👁 Compartilhado (executante vê)</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-primary" wire:click="saveQueuedFiles" wire:loading.attr="disabled" wire:target="uploadFiles,saveQueuedFiles">
                                <i class="bi bi-cloud-upload me-1"></i>Salvar arquivos
                            </button>
                        </div>
                    </div>

                    @if(!empty($uploadFiles))
                        <div class="small fw-semibold text-muted mb-2">Fila de envio — edite os nomes antes de salvar:</div>
                        <div class="queue-list">
                            @foreach($uploadFiles as $index => $qf)
                                @php
                                    $qn  = $uploadNames[$index] ?? $qf->getClientOriginalName();
                                    $qe  = strtolower(pathinfo((string)$qn, PATHINFO_EXTENSION));
                                    $qsz = method_exists($qf, 'getSize') ? (int)$qf->getSize() : 0;
                                @endphp
                                <div class="queue-item">
                                    <i class="bi {{ $fileIcon($qn) }}"></i>
                                    <div class="flex-grow-1">
                                        <input type="text" class="form-control form-control-sm" wire:model.defer="uploadNames.{{ $index }}" placeholder="Nome do arquivo">
                                        <div class="queue-meta">{{ strtoupper($qe ?: '-') }} · {{ number_format($qsz / 1024, 1, ',', '.') }} KB</div>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger" wire:click="removeQueuedFile({{ $index }})" title="Remover da fila"><i class="bi bi-x-lg"></i></button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="queue-empty">Nenhum arquivo na fila. Selecione arquivos acima para começar.</div>
                    @endif
                </div>
            </div>

            {{-- ─────────────────────────────────────── ABA: COMUNICAÇÃO --}}
            <div x-show="tab === 'communication'">
                <div class="chat-compose">
                    <div class="small fw-semibold mb-2">Novo comentário na demanda</div>
                    <textarea class="form-control mb-2" rows="3" wire:model.defer="newComment" placeholder="Escreva um comentário interno ou para compartilhar com o executante..."></textarea>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" style="width:260px" wire:model.defer="commentVisibility">
                            <option value="controller">🔒 Interno — só controlador vê</option>
                            <option value="shared">👁 Compartilhado — executante vê</option>
                        </select>
                        <button class="btn btn-sm btn-primary" wire:click="addComment"><i class="bi bi-send me-1"></i>Comentar</button>
                    </div>
                </div>

                @if($demand->comments->where('legal_demand_subdemand_id', null)->count() > 0)
                    <div class="chat-thread">
                        @foreach($demand->comments->where('legal_demand_subdemand_id', null)->sortByDesc('created_at') as $comment)
                            @php $vis = $comment->visibility ?? 'controller'; @endphp
                            <div class="chat-bubble">
                                <div class="chat-avatar">{{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}</div>
                                <div class="chat-content {{ $vis }}">
                                    <div class="chat-header">
                                        <span class="chat-name">{{ $comment->user?->name ?? '—' }}</span>
                                        <span class="chat-time">{{ \Carbon\Carbon::parse($comment->created_at)->format('d/m/Y H:i') }}</span>
                                        @if($vis === 'controller')
                                            <span class="badge bg-secondary" style="font-size:10px">🔒 Interno</span>
                                        @else
                                            <span class="badge bg-success" style="font-size:10px">👁 Compartilhado</span>
                                        @endif
                                        @if($canManageSubdemands)
                                            <div class="chat-visibility-control">
                                                <select class="form-select form-select-sm"
                                                        wire:change="updateCommentVisibility({{ $comment->id }}, $event.target.value)">
                                                    <option value="controller" @selected($vis === 'controller')>🔒 Interno</option>
                                                    <option value="shared" @selected($vis === 'shared')>👁 Compartilhado</option>
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="chat-text">{{ $comment->comment }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-dots" style="font-size:32px;opacity:.3"></i>
                        <div class="mt-2 small">Nenhum comentário na demanda principal ainda.</div>
                    </div>
                @endif
            </div>

            {{-- ─────────────────────────────────────── ABA: NOTES --}}
            <div x-show="tab === 'notes'" x-data="{
                noteTab: localStorage.getItem('ldt_nt_{{ $demand->id }}') || 'list',
                setNoteTab(t) { this.noteTab = t; localStorage.setItem('ldt_nt_{{ $demand->id }}', t); }
            }">
                {{-- Buscar e associar --}}
                <div class="ld-section">
                    <div class="ld-section-header">Associar Notes ao Processo</div>
                    <div class="ld-section-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Buscar / informar notes (ID, número ou cliente)</label>
                                <input type="text" class="form-control form-control-sm" wire:model.lazy="noteInput" placeholder="Ex.: 12345 67890 ou 500123;500124">
                                <div class="small text-muted mt-1">Separe múltiplos por espaço, vírgula ou ;</div>
                                @error('noteInput') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Motivo / contexto do vínculo</label>
                                <input type="text" class="form-control form-control-sm" wire:model.defer="noteLinkContext" placeholder="Ex.: subsidio solicitado pelo controlador para resposta do processo">
                                <div class="small text-muted mt-1">Este texto será exibido como motivo/observação do SLA.</div>
                                @error('noteLinkContext') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-sm btn-primary w-100" wire:click="linkNotesToCase"><i class="bi bi-plus-circle me-1"></i>Associar</button>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <div class="rounded-3 p-3 h-100" style="background:#fff7ed;border:1px solid #fed7aa;">
                                    <label class="form-label small fw-semibold mb-1" style="color:#9a3412;">Prazo máximo de SLA *</label>
                                    <input type="datetime-local" class="form-control form-control-sm" wire:model.defer="noteOperatorSlaDueAt">
                                    <div class="small mt-2" style="color:#9a3412;">Define o prazo que será destacado no modal das listas e usado como limite operacional.</div>
                                    @error('noteOperatorSlaDueAt') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="rounded-3 p-3 h-100" style="background:#eff6ff;border:1px solid #bfdbfe;">
                                    <label class="form-label small fw-semibold mb-1" style="color:#1d4ed8;">Instruções para execução da note *</label>
                                    <textarea class="form-control form-control-sm" rows="3" wire:model.defer="noteExecutionInstruction" placeholder="Explique objetivamente o que o executante precisa fazer, quais evidências observar e qual retorno é esperado."></textarea>
                                    <div class="small mt-2" style="color:#1d4ed8;">A instrução será vinculada à note e exibida no modal como orientação do controlador.</div>
                                    @error('noteExecutionInstruction') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        @if($searchedNotes->isNotEmpty())
                            <div class="table-responsive mb-0">
                                <table class="table table-sm align-middle mb-0">
                                    <thead><tr><th>ID</th><th>Note</th><th>Cliente</th><th>Status</th><th class="text-end">Ação</th></tr></thead>
                                    <tbody>
                                        @foreach($searchedNotes as $sn)
                                            <tr>
                                                <td>{{ $sn->id }}</td>
                                                <td>{{ $sn->note ?? '—' }}</td>
                                                <td>{{ $sn->client ?? '—' }}</td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">{{ $sn->nstats ?? $sn->status ?? '—' }}</span>
                                                    @if($sn->dt_status)<div class="text-muted" style="font-size:11px">{{ \Carbon\Carbon::parse($sn->dt_status)->format('d/m/Y H:i') }}</div>@endif
                                                </td>
                                                <td class="text-end"><button class="btn btn-sm btn-outline-primary" wire:click="attachSingleNote({{ $sn->id }})">ADD</button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                @if($linkedNotes->isNotEmpty())
                    {{-- Sub-tabs --}}
                    <div class="note-subtabs">
                        <button class="note-subtab" :class="{active:noteTab==='list'}" @click="setNoteTab('list')">
                            <i class="bi bi-journal-text"></i> Notes
                            <span class="cnt">{{ $linkedNotes->count() }}</span>
                        </button>
                        @if($notesProductions->isNotEmpty())
                            <button class="note-subtab" :class="{active:noteTab==='productions'}" @click="setNoteTab('productions')">
                                <i class="bi bi-lightning"></i> Productions
                                <span class="cnt">{{ $notesProductions->count() }}</span>
                            </button>
                        @endif
                        @if($notesOrders->isNotEmpty())
                            <button class="note-subtab" :class="{active:noteTab==='orders'}" @click="setNoteTab('orders')">
                                <i class="bi bi-box-seam"></i> Orders
                                <span class="cnt">{{ $notesOrders->count() }}</span>
                            </button>
                        @endif
                    </div>

                    {{-- Sub-tab: Notes list --}}
                    <div x-show="noteTab === 'list'">
                        @foreach($linkedNotes as $note)
                            @php
                                $isLate = ($note->pze_parecer ?? '') === 'Vencido';
                                $daysLeft = $note->days_left ?? null;
                                $noteOperationalStatus = ((int) ($note->type_note ?? 0) === 1)
                                    ? ($note->centerjob ?: 'CentroTrab nao informado')
                                    : ($note->nstats ?: 'Status nao informado');
                                $hasNoteEditError = $errors->has("noteEditSlaDueAt.$note->id")
                                    || $errors->has("noteEditContext.$note->id")
                                    || $errors->has("noteEditInstruction.$note->id");
                            @endphp
                            <div class="note-card" x-data="{ editing: {{ $hasNoteEditError ? 'true' : 'false' }} }">
                                <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;justify-content:space-between">
                                    <div>
                                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px">
                                            <span class="note-number">{{ $note->note ?? $note->id }}</span>
                                            <span class="note-status-pill {{ $isLate ? 'late' : '' }}">
                                                {{ $noteOperationalStatus }} — {{ $note->status ?? '—' }}
                                            </span>
                                            @if($isLate)
                                                <span class="note-status-pill late">
                                                    <i class="bi bi-clock-history me-1"></i>Prazo vencido
                                                    @if($daysLeft !== null) ({{ abs($daysLeft) }}d) @endif
                                                </span>
                                            @elseif($daysLeft !== null && $daysLeft > 0)
                                                <span class="note-status-pill" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0">
                                                    {{ $daysLeft }}d restantes
                                                </span>
                                            @endif
                                            @if($note->canceled ?? false)
                                                <span class="note-status-pill" style="background:#fef2f2;color:#dc2626;border-color:#fecaca">Cancelada</span>
                                            @endif
                                        </div>
                                        <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:6px">{{ $note->client ?? '—' }}</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="editing = !editing">
                                            <i class="bi bi-pencil-square me-1"></i>
                                            <span x-show="!editing">Editar associação</span>
                                            <span x-show="editing">Ocultar edição</span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" wire:click="unlinkNoteFromCase({{ $note->id }})">
                                            <i class="bi bi-x-circle me-1"></i>Desvincular
                                        </button>
                                    </div>
                                </div>

                                <div class="note-meta">
                                    @if($note->rubrica)
                                        <span class="note-meta-item"><strong>Rubrica:</strong> {{ $note->rubrica }}</span>
                                    @endif
                                    @if($note->type_note == 1 && $note->centerjob)
                                        <span class="note-meta-item"><strong>Centerjob:</strong>
                                            <span style="font-family:monospace;background:#f1f5f9;padding:1px 6px;border-radius:4px">{{ $note->centerjob }}</span>
                                        </span>
                                    @endif
                                    @if($note->material)
                                        <span class="note-meta-item"><strong>Material:</strong> {{ $note->material }}</span>
                                    @endif
                                    @if($note->lexp)
                                        <span class="note-meta-item"><strong>Localidade:</strong> {{ $note->lexp }}</span>
                                    @endif
                                    @if($note->group1)
                                        <span class="note-meta-item"><strong>Grupo:</strong> {{ $note->group1 }}</span>
                                    @endif
                                    @if($note->dt_status)
                                        <span class="note-meta-item"><strong>Status em:</strong> {{ \Carbon\Carbon::parse($note->dt_status)->format('d/m/Y H:i') }}</span>
                                    @endif
                                    @if($note->pivot_linked_at)
                                        <span class="note-meta-item"><strong>Vinculada em:</strong> {{ \Carbon\Carbon::parse($note->pivot_linked_at)->format('d/m/Y H:i') }}</span>
                                    @endif
                                    @if($note->pivot_context)
                                        <span class="note-meta-item"><strong>Contexto:</strong> {{ $note->pivot_context }}</span>
                                    @endif
                                </div>
                                <div class="mt-3 rounded-3 p-3" x-show="editing" x-transition style="display:none;background:#f8fafc;border:1px solid #dbeafe;">
                                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                                        <div>
                                            <div class="fw-semibold" style="color:#1e3a8a;">Associação operacional da note</div>
                                            <div class="small text-muted">Edite o prazo máximo, motivo e instrução exibidos nas listas e no modal da note.</div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-primary" wire:click="updateLinkedNoteExecutionContext({{ $note->id }})">
                                            <i class="bi bi-save me-1"></i>Salvar associação
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold mb-1">Prazo máximo de SLA</label>
                                            <input type="datetime-local" class="form-control form-control-sm" wire:model.defer="noteEditSlaDueAt.{{ $note->id }}">
                                            @error('noteEditSlaDueAt.'.$note->id) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold mb-1">Motivo / contexto</label>
                                            <input type="text" class="form-control form-control-sm" wire:model.defer="noteEditContext.{{ $note->id }}" placeholder="Motivo do vínculo ou observação do SLA">
                                            @error('noteEditContext.'.$note->id) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold mb-1">Instruções para execução</label>
                                            <textarea class="form-control form-control-sm" rows="2" wire:model.defer="noteEditInstruction.{{ $note->id }}" placeholder="Instrução objetiva para execução desta note"></textarea>
                                            @error('noteEditInstruction.'.$note->id) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Sub-tab: Productions --}}
                    @if($notesProductions->isNotEmpty())
                        <div x-show="noteTab === 'productions'">
                            <div class="ld-section">
                                <div class="ld-section-header">Productions vinculadas às notes ({{ $notesProductions->count() }})</div>
                                <div class="ld-section-body" style="padding:0">
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Note</th>
                                                    <th>Responsável</th>
                                                    <th>Empresa</th>
                                                    <th>Serviço</th>
                                                    <th>Status</th>
                                                    <th>CentroTrab / Status note</th>
                                                    <th>Despachada em</th>
                                                    <th>Atribuída em</th>
                                                    <th>Concluída em</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($notesProductions as $prod)
                                                    @php
                                                        $pStat = (int)($prod->status ?? 0);
                                                        $statusMeta = \App\Custom\Notestatus::status($pStat);
                                                        $prodNote = $prod->Note;
                                                        $noteOperationalValue = ((int) ($prodNote?->type_note ?? 0) === 1)
                                                            ? ($prod->centroTrab ?: $prodNote?->centerjob)
                                                            : ($prod->status_note ?: $prodNote?->nstats);
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $prod->id }}</td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $prodNote?->note ?? $prod->note_id }}</div>
                                                            <div class="text-muted" style="font-size:11px;">#{{ $prod->note_id }}</div>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $prod->User?->name ?? '—' }}</div>
                                                            @if($prod->Dispatcher?->name)
                                                                <div class="text-muted" style="font-size:11px;">Despacho: {{ $prod->Dispatcher->name }}</div>
                                                            @endif
                                                        </td>
                                                        <td>{{ $prod->Company?->name ?? '—' }}</td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $prod->Service?->service ?? '—' }}</div>
                                                            @if($prod->service_id)
                                                                <div class="text-muted" style="font-size:11px;">{{ $prod->service_id }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge {{ $statusMeta->colorbg ?? 'text-bg-secondary' }}">
                                                                {{ $statusMeta->status ?? "Status $pStat" }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-light text-dark border">{{ $noteOperationalValue ?: '—' }}</span>
                                                            <div class="text-muted" style="font-size:11px;">
                                                                {{ ((int) ($prodNote?->type_note ?? 0) === 1) ? 'CentroTrab' : 'Status note' }}
                                                            </div>
                                                        </td>
                                                        <td>{{ $prod->dispatch_at ? \Carbon\Carbon::parse($prod->dispatch_at)->format('d/m/Y H:i') : '—' }}</td>
                                                        <td>{{ $prod->att_at ? \Carbon\Carbon::parse($prod->att_at)->format('d/m/Y H:i') : '—' }}</td>
                                                        <td>
                                                            @if($prod->completed_at)
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                                    {{ \Carbon\Carbon::parse($prod->completed_at)->format('d/m/Y H:i') }}
                                                                </span>
                                                            @else
                                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pendente</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Sub-tab: Orders --}}
                    @if($notesOrders->isNotEmpty())
                        <div x-show="noteTab === 'orders'">
                            <div class="ld-section">
                                <div class="ld-section-header">Orders vinculadas às notes ({{ $notesOrders->count() }})</div>
                                <div class="ld-section-body" style="padding:0">
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead><tr><th>ID</th><th>Note</th><th>Ordem</th><th>Descrição</th><th>Status</th><th>Entrada</th></tr></thead>
                                            <tbody>
                                                @foreach($notesOrders as $ord)
                                                    <tr>
                                                        <td>{{ $ord->id }}</td>
                                                        <td>{{ $ord->note_id }}</td>
                                                        <td><span style="font-family:monospace">{{ $ord->ordem ?? '—' }}</span></td>
                                                        <td>{{ $ord->descricao ?? '—' }}</td>
                                                        <td>
                                                            @if($ord->canceled ?? false)
                                                                <span class="badge bg-danger">Cancelada</span>
                                                            @else
                                                                <span class="badge bg-light text-dark border">{{ $ord->statusSist ?? $ord->statusUser ?? '—' }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $ord->dtEntrada ? \Carbon\Carbon::parse($ord->dtEntrada)->format('d/m/Y') : '—' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-journal-text" style="font-size:32px;opacity:.3"></i>
                        <div class="mt-2 small">Nenhuma note associada a este processo.</div>
                    </div>
                @endif
            </div>

        </div>{{-- /ld-tab-content --}}
    </div>{{-- /ld-wrap --}}

    {{-- Modal de imagens --}}
    @if($imageFiles->isNotEmpty())
        <div class="modal fade" id="filesModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Visualização de Imagens</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="filesCarousel" class="carousel slide" data-bs-ride="false">
                            <div class="carousel-inner">
                                @foreach($imageFiles as $index => $file)
                                    @php $fp = $file->path ?? $file->file_path ?? null; $fu = $fp ? \Illuminate\Support\Facades\Storage::url($fp) : null; @endphp
                                    @if($fu)
                                        <div class="carousel-item @if($index === 0) active @endif">
                                            <div class="text-center">
                                                <img src="{{ $fu }}" class="img-fluid rounded border" alt="{{ $file->original_name ?? basename($fp) }}" style="max-height:70vh;object-fit:contain">
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <div class="small text-muted">{{ $file->original_name ?? basename($fp) }}</div>
                                                <a href="{{ $fu }}" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i>Baixar</a>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if($imageFiles->count() > 1)
                                <button class="carousel-control-prev" type="button" data-bs-target="#filesCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
                                <button class="carousel-control-next" type="button" data-bs-target="#filesCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        function scrollLegalChatsToBottom() {
            document.querySelectorAll('.auto-scroll-chat').forEach((el) => {
                el.scrollTop = el.scrollHeight;
            });
        }

        document.addEventListener('DOMContentLoaded', scrollLegalChatsToBottom);
        document.addEventListener('livewire:load', function () {
            scrollLegalChatsToBottom();
            if (window.Livewire) {
                Livewire.hook('message.processed', scrollLegalChatsToBottom);
            }
        });

        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-carousel-slide]');
            if (!trigger) return;
            const index = parseInt(trigger.getAttribute('data-carousel-slide') || '0', 10);
            const el = document.querySelector('#filesCarousel');
            if (!el || typeof bootstrap === 'undefined') return;
            bootstrap.Carousel.getOrCreateInstance(el, { interval: false }).to(index);
        });
    </script>
</div>
