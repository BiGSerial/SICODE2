{{-- Reaproveita o CSS `.closure-dash` do módulo Encerramento (padrão visual de dashboards do SICODE). --}}
@include('livewire.closure._styles')
<style>
    .closure-dash .quality-step { display: flex; align-items: center; gap: .6rem; }
    .closure-dash .quality-step + .quality-step::before { content: ''; flex: 0 0 28px; height: 2px; background: var(--dash-line); margin-right: .2rem; }
    .closure-dash .quality-step.is-done + .quality-step::before { background: var(--dash-green); }
    .closure-dash .quality-dot { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; font-weight: 700; font-size: .85rem; background: #e2e8f0; color: var(--dash-muted); flex: 0 0 34px; }
    .closure-dash .is-done .quality-dot { background: var(--dash-green); color: #fff; }
    .closure-dash .is-active .quality-dot { background: var(--dash-blue); color: #fff; box-shadow: 0 0 0 4px rgba(38, 60, 200, .18); }
    .closure-dash .quality-step small { display: block; color: var(--dash-muted); line-height: 1.1; }
    .closure-dash .quality-step strong { font-size: .85rem; line-height: 1.2; }
    .closure-dash .quality-timeline { list-style: none; margin: 0; padding: 0; }
    .closure-dash .quality-timeline li { position: relative; padding: 0 0 1rem 1.6rem; border-left: 2px solid var(--dash-line); margin-left: .5rem; }
    .closure-dash .quality-timeline li::before { content: ''; position: absolute; left: -7px; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--dash-teal); }
    .closure-dash .quality-timeline li.is-danger::before { background: var(--dash-red); }
    .closure-dash .quality-timeline li.is-success::before { background: var(--dash-green); }
</style>
<style>
    /* Espaço de trabalho por etapa (N1/N2) */
    .closure-dash .wk-stages { display: flex; gap: .5rem; overflow-x: auto; padding-bottom: .25rem; }
    .closure-dash .wk-stage { position: relative; flex: 1 1 0; min-width: 168px; display: block; text-decoration: none; color: var(--dash-ink); background: #fff; border: 1px solid var(--dash-line); border-radius: 10px; padding: .75rem .9rem; transition: box-shadow .15s, border-color .15s, transform .15s; }
    .closure-dash .wk-stage:hover { box-shadow: 0 8px 20px rgba(16, 32, 51, .1); transform: translateY(-1px); color: var(--dash-ink); }
    .closure-dash .wk-stage.is-active { border-color: var(--dash-blue); box-shadow: 0 0 0 2px rgba(38, 60, 200, .15); }
    .closure-dash .wk-stage.is-active::after { content: ''; position: absolute; left: 14px; right: 14px; bottom: -1px; height: 3px; border-radius: 3px; background: var(--dash-blue); }
    .closure-dash .wk-stage .wk-step { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 50%; background: #e2e8f0; color: var(--dash-muted); font-size: .72rem; font-weight: 800; margin-right: .4rem; }
    .closure-dash .wk-stage.is-active .wk-step { background: var(--dash-blue); color: #fff; }
    .closure-dash .wk-stage .wk-name { font-size: .82rem; font-weight: 700; }
    .closure-dash .wk-stage .wk-count { font-size: 1.7rem; font-weight: 850; line-height: 1.1; margin-top: .25rem; }
    .closure-dash .wk-stage .wk-meta { font-size: .75rem; color: var(--dash-muted); min-height: 1.3rem; }
    .closure-dash .wk-dot { position: absolute; top: 10px; right: 10px; width: 10px; height: 10px; border-radius: 50%; background: var(--dash-amber); box-shadow: 0 0 0 3px rgba(247, 210, 0, .25); }
    .closure-dash .wk-help { border-left: 4px solid var(--dash-blue); background: #f4f7ff; border-radius: 6px; padding: .6rem .9rem; font-size: .88rem; color: var(--dash-ink); }
    .closure-dash .wk-toolbar .form-control, .closure-dash .wk-toolbar .form-select { font-size: .85rem; }
    .closure-dash .wk-table tbody tr { cursor: pointer; }
    .closure-dash .wk-table tbody tr:hover { background: #f8fafc; }
    .closure-dash .wk-table td, .closure-dash .wk-table th { vertical-align: middle; }
    .closure-dash .wk-sub { font-size: .75rem; color: var(--dash-muted); }
    .closure-dash .wk-bulkbar { position: sticky; bottom: 0; z-index: 5; background: #102033; color: #fff; border-radius: 10px 10px 0 0; padding: .75rem 1rem; box-shadow: 0 -8px 24px rgba(16, 32, 51, .25); display: none; }
    .closure-dash .wk-bulkbar.is-visible { display: block; }
    .closure-dash .wk-bulkbar .form-select, .closure-dash .wk-bulkbar .form-control { font-size: .85rem; }
    .closure-dash .wk-empty { text-align: center; padding: 3rem 1rem; color: var(--dash-muted); }
    .closure-dash .wk-empty i { font-size: 2.6rem; color: #cbd5e1; display: block; margin-bottom: .5rem; }
</style>
<style>
    /* Processo: "de quem é a vez" */
    .closure-dash .turn { display: flex; align-items: center; gap: 1rem; border-radius: 14px; padding: 1.1rem 1.4rem; flex-wrap: wrap; }
    .closure-dash .turn-icon { flex: 0 0 56px; height: 56px; border-radius: 50%; display: grid; place-items: center; font-size: 1.7rem; }
    .closure-dash .turn-main { flex: 1 1 320px; }
    .closure-dash .turn-kicker { font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .closure-dash .turn-title { font-size: 1.35rem; font-weight: 800; line-height: 1.2; }
    .closure-dash .turn-sub { font-size: .9rem; opacity: .92; margin-top: .15rem; }
    .closure-dash .turn-mine { background: linear-gradient(120deg, #b45309, #f59e0b); color: #fff; box-shadow: 0 14px 30px rgba(180, 83, 9, .28); }
    .closure-dash .turn-mine .turn-icon { background: rgba(255, 255, 255, .22); animation: turn-pulse 2s infinite; }
    .closure-dash .turn-wait { background: linear-gradient(120deg, #eef3ff, #f7faff); border: 1px solid #c7d4f5; color: var(--dash-ink); }
    .closure-dash .turn-wait .turn-icon { background: #dbe5ff; color: var(--dash-blue); }
    .closure-dash .turn-done { background: linear-gradient(120deg, #0f766e, #10b981); color: #fff; }
    .closure-dash .turn-done .turn-icon { background: rgba(255, 255, 255, .22); }
    .closure-dash .turn-cta { white-space: nowrap; }
    @keyframes turn-pulse { 0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, .5); } 70% { box-shadow: 0 0 0 14px rgba(255, 255, 255, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); } }

    /* Processo: jornada */
    .closure-dash .jr { display: flex; gap: .75rem; align-items: stretch; flex-wrap: wrap; }
    .closure-dash .jr-phase { flex: 1 1 360px; border: 1px solid var(--dash-line); border-radius: 12px; padding: .8rem .9rem; background: #fbfcfe; }
    .closure-dash .jr-phase.is-now { border-color: #a9b8f5; background: #f4f7ff; }
    .closure-dash .jr-phase.is-done { background: #f3fbf7; border-color: #bfe8d2; }
    .closure-dash .jr-phase-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: .7rem; gap: .5rem; }
    .closure-dash .jr-phase-title { font-weight: 800; font-size: .92rem; }
    .closure-dash .jr-phase-service { color: var(--dash-muted); font-weight: 600; font-size: .85rem; }
    .closure-dash .jr-steps { display: flex; gap: .25rem; }
    .closure-dash .jr-step { position: relative; flex: 1 1 0; text-align: center; padding: 0 .15rem; min-width: 0; }
    .closure-dash .jr-step + .jr-step::before { content: ''; position: absolute; top: 21px; left: -50%; width: 100%; height: 3px; background: #dbe2ea; z-index: 0; }
    .closure-dash .jr-step.is-done + .jr-step::before, .closure-dash .jr-step.is-now::before { background: #34d399; }
    .closure-dash .jr-node { position: relative; z-index: 1; width: 44px; height: 44px; margin: 0 auto .4rem; border-radius: 50%; display: grid; place-items: center; font-size: 1.25rem; background: #e5e9f0; color: #7b8794; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(16, 32, 51, .12); }
    .closure-dash .jr-step.is-done .jr-node { background: #10b981; color: #fff; }
    .closure-dash .jr-step.is-now .jr-node { width: 52px; height: 52px; font-size: 1.45rem; color: #fff; background: var(--dash-blue); box-shadow: 0 0 0 5px rgba(38, 60, 200, .18); margin-top: -4px; }
    .closure-dash .jr-step.tone-danger .jr-node { background: var(--dash-red); box-shadow: 0 0 0 5px rgba(227, 44, 44, .18); }
    .closure-dash .jr-step.tone-info .jr-node { background: #0891b2; box-shadow: 0 0 0 5px rgba(8, 145, 178, .18); }
    .closure-dash .jr-label { font-weight: 800; font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }
    .closure-dash .jr-step.is-todo .jr-label { color: #9aa5b1; }
    .closure-dash .jr-person { font-size: .8rem; min-height: 1.4rem; display: flex; align-items: center; justify-content: center; gap: .3rem; flex-wrap: wrap; }
    .closure-dash .jr-avatar { display: inline-grid; place-items: center; width: 20px; height: 20px; border-radius: 50%; background: #dbe5ff; color: var(--dash-blue); font-size: .62rem; font-weight: 800; }
    .closure-dash .jr-when { font-size: .74rem; color: var(--dash-muted); min-height: 1.3rem; }
    .closure-dash .jr-loop { display: inline-block; margin-left: .25rem; padding: 0 .35rem; border-radius: 8px; background: #fff3cd; color: #8a6d00; font-weight: 800; }
    .closure-dash .jr-flag { display: inline-block; font-size: .68rem; font-weight: 800; text-transform: uppercase; color: #fff; background: var(--dash-red); border-radius: 6px; padding: 0 .4rem; }
    .closure-dash .jr-end { flex: 0 0 96px; text-align: center; align-self: center; }
    .closure-dash .jr-end .jr-node { background: #e5e9f0; }
    .closure-dash .jr-end.is-done .jr-node { background: #10b981; color: #fff; }
    .closure-dash .jr-end .jr-label { color: #9aa5b1; }
    .closure-dash .jr-end.is-done .jr-label { color: var(--dash-ink); }

    /* Processo: indicadores */
    .closure-dash .kpi { display: flex; align-items: center; gap: .7rem; background: #fff; border: 1px solid var(--dash-line); border-radius: 12px; padding: .7rem .9rem; height: 100%; }
    .closure-dash .kpi-ico { flex: 0 0 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; background: #eef3ff; color: var(--dash-blue); font-size: 1.15rem; }
    .closure-dash .kpi-label { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--dash-muted); }
    .closure-dash .kpi-value { font-weight: 800; font-size: .95rem; line-height: 1.2; }
    .closure-dash .kpi-sub { font-size: .75rem; color: var(--dash-muted); }
    .closure-dash .action-panel { position: sticky; top: 76px; }
</style>
<style>
    /* Cartão de obra (lista rica) */
    .closure-dash .ob-list { display: flex; flex-direction: column; gap: .6rem; padding: .75rem; }
    .closure-dash .ob { position: relative; display: flex; align-items: center; gap: 1rem; background: #fff; border: 1px solid var(--dash-line); border-radius: 12px; padding: .8rem 1rem .8rem 1.15rem; transition: box-shadow .15s, transform .15s, border-color .15s; cursor: pointer; flex-wrap: wrap; }
    .closure-dash .ob:hover { box-shadow: 0 10px 24px rgba(16, 32, 51, .10); transform: translateY(-1px); border-color: #c5d0df; }
    .closure-dash .ob::before { content: ''; position: absolute; left: 0; top: 10px; bottom: 10px; width: 5px; border-radius: 0 5px 5px 0; background: #34d399; }
    .closure-dash .ob.is-warn::before { background: #f59e0b; }
    .closure-dash .ob.is-late::before { background: #ef4444; }
    .closure-dash .ob.is-selected { border-color: var(--dash-blue); background: #f4f7ff; }
    .closure-dash .ob-check { flex: 0 0 auto; }
    .closure-dash .ob-main { flex: 1 1 280px; min-width: 0; }
    .closure-dash .ob-note { font-size: 1.15rem; font-weight: 850; color: var(--dash-ink); letter-spacing: .01em; }
    .closure-dash .ob-material { font-size: .8rem; color: var(--dash-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 460px; }
    .closure-dash .ob-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .3rem; }
    .closure-dash .chip { display: inline-flex; align-items: center; gap: .25rem; font-size: .7rem; font-weight: 700; padding: .1rem .5rem; border-radius: 999px; background: #eef2f7; color: #475569; }
    .closure-dash .chip.chip-cycle { background: #e0e7ff; color: #3730a3; }
    .closure-dash .chip.chip-loop { background: #fff3cd; color: #8a6d00; }
    .closure-dash .chip.chip-reason { background: #fee2e2; color: #991b1b; }
    .closure-dash .chip.chip-where { background: #ecfeff; color: #155e75; }
    .closure-dash .ob-people { display: flex; align-items: center; gap: .6rem; flex: 0 1 240px; min-width: 170px; }
    .closure-dash .ob-avatar { flex: 0 0 34px; height: 34px; border-radius: 50%; display: grid; place-items: center; background: #dbe5ff; color: var(--dash-blue); font-weight: 800; font-size: .78rem; }
    .closure-dash .ob-avatar.is-empty { background: #eef2f7; color: #94a3b8; }
    .closure-dash .ob-person { font-size: .86rem; font-weight: 700; line-height: 1.15; }
    .closure-dash .ob-role { font-size: .7rem; color: var(--dash-muted); text-transform: uppercase; letter-spacing: .04em; }
    .closure-dash .ob-flow { flex: 0 0 auto; }
    .closure-dash .flow-dots { display: flex; align-items: center; gap: 3px; }
    .closure-dash .flow-dot { width: 13px; height: 13px; border-radius: 50%; background: #e2e8f0; position: relative; }
    .closure-dash .flow-dot.is-done { background: #34d399; }
    .closure-dash .flow-dot.is-now { background: var(--dash-blue); box-shadow: 0 0 0 4px rgba(38, 60, 200, .18); }
    .closure-dash .flow-dot.is-now.is-late { background: #ef4444; box-shadow: 0 0 0 4px rgba(239, 68, 68, .2); }
    .closure-dash .flow-sep { width: 8px; height: 2px; background: #cbd5e1; margin: 0 2px; }
    .closure-dash .flow-cap { font-size: .66rem; color: var(--dash-muted); margin-top: .25rem; text-align: center; text-transform: uppercase; letter-spacing: .04em; }
    .closure-dash .ob-side { flex: 0 0 auto; display: flex; align-items: center; gap: .8rem; margin-left: auto; }
    .closure-dash .ob-age { text-align: right; }
    .closure-dash .ob-age small { display: block; font-size: .68rem; color: var(--dash-muted); }
    .closure-dash .ob-empty { text-align: center; padding: 3rem 1rem; color: var(--dash-muted); }
    .closure-dash .ob-empty i { font-size: 2.6rem; color: #cbd5e1; display: block; margin-bottom: .5rem; }
    @media (max-width: 992px) { .closure-dash .ob-side { margin-left: 0; width: 100%; justify-content: space-between; } }

    /* Cartões de usuário (N1) e pipeline (Gestão) */
    .closure-dash .usr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: .8rem; padding: .75rem; }
    .closure-dash .usr-card { background: #fff; border: 1px solid var(--dash-line); border-radius: 14px; padding: 1rem; }
    .closure-dash .usr-head { display: flex; align-items: center; gap: .7rem; }
    .closure-dash .usr-head .ob-avatar { flex-basis: 46px; height: 46px; font-size: .95rem; }
    .closure-dash .load-bar { height: 8px; border-radius: 8px; background: #e2e8f0; overflow: hidden; }
    .closure-dash .load-bar > span { display: block; height: 100%; border-radius: 8px; background: linear-gradient(90deg, #34d399, #10b981); }
    .closure-dash .load-bar.is-warn > span { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
    .closure-dash .load-bar.is-late > span { background: linear-gradient(90deg, #f87171, #ef4444); }
    .closure-dash .usr-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; margin-top: .8rem; text-align: center; }
    .closure-dash .usr-stat b { display: block; font-size: 1.15rem; font-weight: 850; }
    .closure-dash .usr-stat span { font-size: .68rem; color: var(--dash-muted); text-transform: uppercase; letter-spacing: .04em; }
    .closure-dash .usr-act { display: flex; align-items: center; gap: .5rem; padding: .35rem .5rem; border-radius: 8px; background: #f8fafc; font-size: .82rem; margin-top: .3rem; }
    .closure-dash .pipe { display: flex; gap: 4px; height: 64px; border-radius: 12px; overflow: hidden; }
    .closure-dash .pipe-seg { flex: 1 1 0; min-width: 92px; display: flex; flex-direction: column; justify-content: center; padding: .4rem .8rem; color: #fff; text-decoration: none; transition: filter .15s; }
    .closure-dash .pipe-seg:hover { filter: brightness(1.08); color: #fff; }
    .closure-dash .pipe-seg b { font-size: 1.4rem; line-height: 1; }
    .closure-dash .pipe-seg span { font-size: .7rem; text-transform: uppercase; letter-spacing: .05em; opacity: .92; }
</style>
<script>
    document.addEventListener('click', function (e) {
        const card = e.target.closest('.ob[data-href]');
        if (card && !e.target.closest('a, button, input, label, select, textarea')) { window.location = card.dataset.href; }
    });
</script>
<style>
    .closure-dash .decision-btn { width: 100%; text-align: center; border: 2px solid var(--dash-line); background: #fff; border-radius: 12px; padding: .75rem .5rem; display: flex; flex-direction: column; align-items: center; gap: .1rem; transition: all .15s; cursor: pointer; }
    .closure-dash .decision-btn i { font-size: 1.6rem; }
    .closure-dash .decision-btn b { font-size: 1rem; }
    .closure-dash .decision-btn small { font-size: .7rem; color: var(--dash-muted); line-height: 1.15; }
    .closure-dash .decision-approve { color: #047857; }
    .closure-dash .decision-reject { color: #b91c1c; }
    .closure-dash .decision-approve:hover, .closure-dash .decision-approve.is-active { border-color: #10b981; background: #ecfdf5; }
    .closure-dash .decision-reject:hover, .closure-dash .decision-reject.is-active { border-color: #ef4444; background: #fef2f2; }
    .closure-dash .decision-btn.is-active { box-shadow: 0 6px 16px rgba(16, 32, 51, .12); transform: translateY(-1px); }
</style>
<style>
    /* Linha do tempo */
    .closure-dash .tl-day { display: flex; align-items: center; gap: .6rem; margin: 1rem 0 .6rem; color: var(--dash-muted); font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
    .closure-dash .tl-day::before, .closure-dash .tl-day::after { content: ''; flex: 1; height: 1px; background: var(--dash-line); }
    .closure-dash .tl-item { display: flex; gap: .7rem; margin-bottom: .55rem; }
    .closure-dash .tl-avatar { position: relative; flex: 0 0 38px; height: 38px; border-radius: 50%; color: #fff; font-weight: 800; font-size: .78rem; display: grid; place-items: center; }
    .closure-dash .tl-ico { position: absolute; right: -5px; bottom: -4px; width: 18px; height: 18px; border-radius: 50%; color: #fff; display: grid; place-items: center; font-size: .65rem; border: 2px solid #fff; }
    .closure-dash .tl-card { flex: 1; min-width: 0; background: #fff; border: 1px solid var(--dash-line); border-left-width: 4px; border-radius: 10px; padding: .55rem .8rem; }
    .closure-dash .tl-title { font-weight: 800; font-size: .92rem; }
    .closure-dash .tl-target { font-weight: 600; color: var(--dash-muted); font-size: .82rem; }
    .closure-dash .tl-time { font-size: .75rem; color: var(--dash-muted); }
    .closure-dash .tl-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .8rem; margin-top: .1rem; }
    .closure-dash .tl-change { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-top: .35rem; font-size: .72rem; }
    .closure-dash .tl-change span { background: #f1f5f9; border-radius: 6px; padding: .05rem .45rem; color: #475569; }
    .closure-dash .tl-change span.to { background: #e0e7ff; color: #3730a3; font-weight: 700; }
    .closure-dash .tl-quote { margin: .4rem 0 0; padding: .35rem .7rem; border-left: 3px solid #cbd5e1; background: #f8fafc; border-radius: 0 6px 6px 0; font-size: .85rem; color: #334155; }

    /* Discussão */
    .closure-dash .chat-box { max-height: 460px; overflow-y: auto; padding: .6rem; background: linear-gradient(#f8fafc, #f1f5f9); border: 1px solid var(--dash-line); border-radius: 12px; }
    .closure-dash .chat-row { display: flex; align-items: flex-end; gap: .5rem; margin-bottom: .55rem; }
    .closure-dash .chat-row.is-mine { justify-content: flex-end; }
    .closure-dash .chat-avatar { flex: 0 0 34px; height: 34px; border-radius: 50%; color: #fff; font-weight: 800; font-size: .72rem; display: grid; place-items: center; }
    .closure-dash .chat-bubble { max-width: min(78%, 560px); background: #fff; border: 1px solid var(--dash-line); border-radius: 14px 14px 14px 4px; padding: .5rem .75rem; box-shadow: 0 2px 6px rgba(16, 32, 51, .05); }
    .closure-dash .chat-row.is-mine .chat-bubble { background: #e8efff; border-color: #c7d6fb; border-radius: 14px 14px 4px 14px; }
    .closure-dash .chat-head { font-size: .78rem; display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; }
    .closure-dash .chat-text { font-size: .9rem; margin-top: .15rem; word-break: break-word; }
    .closure-dash .chat-time { font-size: .68rem; color: var(--dash-muted); text-align: right; margin-top: .15rem; }
    .closure-dash .chat-composer { margin-top: .8rem; padding: .7rem; border: 1px solid var(--dash-line); border-radius: 12px; background: #fff; }

    /* Galeria de arquivos */
    .closure-dash .gal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: .8rem; }
    .closure-dash .gal-card { background: #fff; border: 1px solid var(--dash-line); border-radius: 12px; overflow: hidden; transition: box-shadow .15s, transform .15s; }
    .closure-dash .gal-card:hover { box-shadow: 0 10px 22px rgba(16, 32, 51, .1); transform: translateY(-2px); }
    .closure-dash .gal-thumb { position: relative; height: 130px; background: #f1f5f9; display: grid; place-items: center; overflow: hidden; }
    .closure-dash .gal-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .closure-dash .gal-icon { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; }
    .closure-dash .gal-icon i { font-size: 2.6rem; }
    .closure-dash .gal-icon b { font-size: .75rem; letter-spacing: .08em; }
    .closure-dash .gal-tag { position: absolute; left: 6px; top: 6px; background: rgba(16, 32, 51, .8); color: #fff; font-size: .62rem; font-weight: 700; padding: .1rem .45rem; border-radius: 999px; }
    .closure-dash .gal-body { padding: .6rem .7rem .7rem; }
    .closure-dash .gal-name { font-weight: 700; font-size: .82rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .closure-dash .gal-meta { font-size: .7rem; color: var(--dash-muted); }

    /* Abas do processo */
    .closure-dash .q-tabs { display: flex; gap: .25rem; padding: .6rem .75rem 0; border-bottom: 1px solid var(--dash-line); overflow-x: auto; }
    .closure-dash .q-tabs .nav-link { border: 0; border-bottom: 3px solid transparent; color: var(--dash-muted); font-weight: 700; font-size: .88rem; padding: .55rem .9rem; border-radius: 8px 8px 0 0; white-space: nowrap; }
    .closure-dash .q-tabs .nav-link:hover { color: var(--dash-ink); background: #f8fafc; }
    .closure-dash .q-tabs .nav-link.active { color: var(--dash-blue); border-bottom-color: var(--dash-blue); background: #f4f7ff; }
    .closure-dash .q-tabs .badge { font-size: .68rem; }
    .closure-dash .round-card { background: #fff; border: 1px solid var(--dash-line); border-radius: 12px; padding: .8rem 1rem; margin-bottom: .7rem; }
    .closure-dash .round-steps { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .5rem; }
    .closure-dash .round-step { display: inline-flex; align-items: center; gap: .35rem; font-size: .76rem; padding: .2rem .55rem; border-radius: 8px; background: #f1f5f9; }
    .closure-dash .round-step.is-ok { background: #ecfdf5; color: #047857; }
    .closure-dash .round-step.is-bad { background: #fef2f2; color: #b91c1c; }
    .closure-dash .rej-card { background: #fff; border: 1px solid #fecaca; border-left: 4px solid #ef4444; border-radius: 10px; padding: .75rem 1rem; margin-bottom: .7rem; }
</style>
<div class="modal fade" id="quality-lightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header border-0"><h6 class="modal-title" id="quality-lightbox-title"></h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
            <div class="modal-body text-center p-2" id="quality-lightbox-body"></div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('click', function (e) {
        const thumb = e.target.closest('[data-lightbox]');
        if (!thumb) return;
        const body = document.getElementById('quality-lightbox-body');
        document.getElementById('quality-lightbox-title').textContent = thumb.dataset.title || '';
        body.innerHTML = thumb.dataset.kind === 'pdf'
            ? '<iframe src="' + thumb.dataset.lightbox + '" style="width:100%;height:78vh;border:0;background:#fff"></iframe>'
            : '<img src="' + thumb.dataset.lightbox + '" style="max-width:100%;max-height:78vh" alt="">';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('quality-lightbox')).show();
    });
    document.getElementById('quality-lightbox')?.addEventListener('hidden.bs.modal', () => { document.getElementById('quality-lightbox-body').innerHTML = ''; });
</script>
