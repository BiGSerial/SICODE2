<style>
    /* ── Overflow do painel principal ────────────────────────────── */
    .services-dropdown-menu {
        overflow: visible !important;
        max-height: none !important;
    }

    /* ── Tokens de design ────────────────────────────────────────
       navy  #212E3E | green  #28FF52 | cobalt #263CC8
    ──────────────────────────────────────────────────────────── */
    .services-dropdown {
        position: relative;
        min-width: 320px;
        overflow: visible;

        --sd-navy:          #212E3E;
        --sd-green:         #28FF52;
        --sd-cobalt:        #263CC8;
        --sd-cobalt-tint:   #e6eeff;
        --sd-cobalt-hover:  #dce8fd;
        --sd-cobalt-active: #c9d9fb;
        --sd-navy-tint:     #edf0f4;
        --sd-navy-hover:    #dde3ec;
        --sd-bg:            #ffffff;
        --sd-surface:       #f7f9fc;
        --sd-muted:         #64748b;
        --sd-border:        #dde3ec;
        --sd-text:          #212E3E;
        --sd-text-soft:     #3d4f64;
        --sd-font-size:     0.82rem;
        --sd-radius:        0.375rem;

        font-size: var(--sd-font-size);
        line-height: 1.4;
    }

    /* ── Painel do dropdown (<ul>) ───────────────────────────────── */
    .services-dropdown.services-dropdown-menu {
        background: var(--sd-bg) !important;
        border: 1px solid #c8d2e0 !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 12px 36px rgba(33, 46, 62, 0.24) !important;
        padding: 0 !important;
    }

    /* ── Cabeçalho do painel (título do dropdown) ────────────────── */
    .services-dropdown .services-dropdown-header {
        background: var(--sd-navy);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        padding: 0.55rem 0.9rem;
        margin: 0 0 0.25rem;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    /* ════════════════════════════════════════════════════════════════
       TIPO 1 — Identificador setorial  (kind = header)
       Sinaliza "você está nesta categoria" — não é clicável.
       Borda verde à esquerda, fundo cinza, texto muted/uppercase.
    ════════════════════════════════════════════════════════════════ */
    .services-dropdown .sd-section-header {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        list-style: none;
        padding: 0.28rem 0.75rem;
        margin: 0.3rem 0 0.05rem;
        background: var(--sd-surface);
        border-left: 3px solid var(--sd-green);
        font-size: 0.66rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--sd-muted);
        user-select: none;
    }

    .services-dropdown .sd-section-header i {
        font-size: 0.68rem;
        opacity: 0.5;
        flex-shrink: 0;
    }

    /* ════════════════════════════════════════════════════════════════
       TIPO 2 — Link direto  (kind = item)
       Linha de lista simples e leve — navega para uma rota.
       Sem borda, sem background em repouso. Hover suave.
    ════════════════════════════════════════════════════════════════ */
    .services-dropdown .sd-item {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.4rem 0.8rem 0.4rem 0.85rem;
        font-weight: 400;
        font-size: var(--sd-font-size);
        color: var(--sd-text-soft);
        text-decoration: none !important;
        border-left: 2px solid transparent;
        width: 100%;
        box-sizing: border-box;
        white-space: nowrap;
        transition:
            background 0.11s ease,
            border-left-color 0.11s ease,
            padding-left 0.11s ease,
            color 0.11s ease;
    }

    .services-dropdown .sd-item:hover {
        background: #f0f5ff;
        border-left-color: var(--sd-cobalt);
        padding-left: 1.05rem;
        color: var(--sd-navy);
        text-decoration: none !important;
    }

    /* Ícone do item — cobalt, tamanho discreto */
    .services-dropdown .sd-item .sd-icon {
        font-size: 0.9rem;
        color: var(--sd-cobalt);
        width: 1rem;
        flex-shrink: 0;
        text-align: center;
        opacity: 0.75;
        transition: opacity 0.11s ease;
    }

    .services-dropdown .sd-item:hover .sd-icon {
        opacity: 1;
    }

    /* Bolinha quando não há ícone */
    .services-dropdown .sd-item .sd-item-dot {
        display: inline-block;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: #a0aec0;
        flex-shrink: 0;
        margin: 0 0.25rem;
        transition: background 0.11s ease;
    }

    .services-dropdown .sd-item:hover .sd-item-dot {
        background: var(--sd-cobalt);
    }

    .services-dropdown .sd-item .sd-label {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Seta "ir" — aparece apenas no hover, desliza da esquerda */
    .services-dropdown .sd-item .sd-nav-arrow {
        font-size: 0.8rem;
        color: var(--sd-cobalt);
        opacity: 0;
        flex-shrink: 0;
        margin-left: auto;
        padding-left: 0.3rem;
        transition: opacity 0.11s ease, transform 0.11s ease;
        transform: translateX(-5px);
    }

    .services-dropdown .sd-item:hover .sd-nav-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    /* ════════════════════════════════════════════════════════════════
       TIPO 3-A — Sub-dropdown lateral  (kind = group, open = side)
       Parece um botão/card elevado. Claramente diferente de um link.
       ● Sempre tem fundo cobalt tinto (visível em repouso)
       ● Badge colorido à direita com o ícone de submenu
       ● Margem e borda-radius fazem ele "flutuar" como card
    ════════════════════════════════════════════════════════════════ */
    .services-dropdown .sd-group--side {
        margin: 0.22rem 0.5rem;
        border-radius: 0.4rem;
        border: 1px solid #b8cbf5;
        border-left: 4px solid var(--sd-cobalt);
        overflow: visible;
    }

    .services-dropdown .sd-group--side > .sd-group-toggle {
        background: var(--sd-cobalt-tint);
        border-radius: 0.3rem;
        padding: 0.48rem 0.55rem 0.48rem 0.7rem;
    }

    .services-dropdown .sd-group--side > .sd-group-toggle:hover {
        background: var(--sd-cobalt-hover);
    }

    .services-dropdown .sd-group--side > .sd-group-toggle.is-active {
        background: var(--sd-cobalt-active);
        border-radius: 0.3rem 0.3rem 0 0;
    }

    /* Badge à direita do botão side — caixa cobalt com seta */
    .services-dropdown .sd-group--side .sd-group-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--sd-cobalt);
        color: #fff;
        border-radius: 0.25rem;
        width: 1.4rem;
        height: 1.4rem;
        flex-shrink: 0;
        transition: background 0.12s ease, transform 0.12s ease;
    }

    .services-dropdown .sd-group--side .sd-group-badge .sd-group-chevron {
        font-size: 0.9rem;
        color: #fff;
        transition: transform 0.2s ease;
    }

    .services-dropdown .sd-group--side > .sd-group-toggle:hover .sd-group-badge {
        background: #1a2db0;
    }

    .services-dropdown .sd-group--side > .sd-group-toggle.is-active .sd-group-badge {
        background: #1a2db0;
    }

    /* ════════════════════════════════════════════════════════════════
       TIPO 3-B — Sub-dropdown inline  (kind = group, open = down)
       Parece um "acordeão" / separador de seção expandível.
       ● Fundo navy tinto (diferente do cobalt do side)
       ● Chevron rotaciona para baixo ao abrir
    ════════════════════════════════════════════════════════════════ */
    .services-dropdown .sd-group--down {
        margin: 0.22rem 0.5rem;
        border-radius: 0.4rem;
        border: 1px solid #bfc8d6;
        border-left: 4px solid var(--sd-navy);
        overflow: visible;
        margin-bottom: 0.3rem;
    }

    .services-dropdown .sd-group--down > .sd-group-toggle {
        background: var(--sd-navy-tint);
        border-radius: 0.3rem;
        padding: 0.48rem 0.55rem 0.48rem 0.7rem;
    }

    .services-dropdown .sd-group--down > .sd-group-toggle:hover {
        background: var(--sd-navy-hover);
    }

    .services-dropdown .sd-group--down > .sd-group-toggle.is-active {
        background: var(--sd-navy-hover);
        border-radius: 0.3rem 0.3rem 0 0;
    }

    /* Badge à direita do botão down — caixa navy */
    .services-dropdown .sd-group--down .sd-group-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--sd-navy);
        color: #fff;
        border-radius: 0.25rem;
        width: 1.4rem;
        height: 1.4rem;
        flex-shrink: 0;
        transition: background 0.12s ease;
    }

    .services-dropdown .sd-group--down .sd-group-badge .sd-group-chevron {
        font-size: 0.9rem;
        color: #fff;
        transition: transform 0.22s ease;
    }

    .services-dropdown .sd-group--down > .sd-group-toggle.is-active .sd-group-badge .sd-group-chevron {
        transform: rotate(180deg);
    }

    .services-dropdown .sd-group--down > .sd-group-toggle:hover .sd-group-badge {
        background: #2e3f55;
    }

    /* ── Estilos comuns a todos os toggles de grupo ──────────────── */
    .services-dropdown .sd-group-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        width: 100%;
        border: 0;
        font-size: var(--sd-font-size);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--sd-text);
        cursor: pointer;
        text-align: left;
        transition: background 0.12s ease, color 0.12s ease;
    }

    .services-dropdown .sd-group-label {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ── Painéis de submenu ──────────────────────────────────────── */
    .services-dropdown .sd-submenu-panel        { display: none; }
    .services-dropdown .sd-submenu-panel.is-open { display: block; }

    /* Painel inline (down) — expande abaixo do toggle */
    .services-dropdown .sd-submenu-panel--down {
        position: static;
        background: #f3f6fb;
        border-top: 1px solid var(--sd-border);
        padding: 0.2rem 0 0.25rem;
        border-radius: 0 0 0.3rem 0.3rem;
    }

    .services-dropdown .sd-submenu-panel--down > .sd-group {
        margin-left: 0.35rem;
        margin-right: 0.35rem;
    }

    .services-dropdown .sd-submenu-panel--down > .sd-item {
        padding-left: 1.1rem;
    }

    /* Painel lateral (side) — flutua à direita */
    .services-dropdown .sd-submenu-panel--side {
        position: absolute;
        top: 0;
        left: calc(100% + 5px);
        min-width: 250px;
        background: var(--sd-bg);
        border: 1px solid #c8d2e0;
        border-radius: 0.5rem;
        padding: 0.3rem 0;
        z-index: 1050;
        box-shadow: 0 12px 36px rgba(33, 46, 62, 0.24);
    }

    .services-dropdown .sd-submenu-panel--side > .sd-item {
        padding-left: 0.85rem;
    }

    /* ── Scrollbar ───────────────────────────────────────────────── */
    .services-dropdown-menu::-webkit-scrollbar        { width: 5px; }
    .services-dropdown-menu::-webkit-scrollbar-track  { background: #f1f5f9; }
    .services-dropdown-menu::-webkit-scrollbar-thumb  { background: #94a3b8; border-radius: 4px; }
    .services-dropdown-menu::-webkit-scrollbar-thumb:hover { background: #64748b; }

    /* ════════════════════════════════════════════════════════════════
       Layout "panel" legado — mantido para compatibilidade
    ════════════════════════════════════════════════════════════════ */
    .services-dropdown .menu-item {
        padding: 0.45rem 0.75rem;
        margin: 0.15rem 0.5rem;
        border-radius: var(--sd-radius);
        background: var(--sd-navy-tint);
        border-left: 4px solid var(--sd-navy);
        font-weight: 700;
        text-transform: uppercase;
        font-size: var(--sd-font-size);
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }

    .services-dropdown .menu-item:hover       { background: var(--sd-navy-hover); }
    .services-dropdown .menu-item.is-active   { background: var(--sd-navy-hover); color: var(--sd-navy); }
    .services-dropdown .menu-item i           { transition: transform 0.2s ease; }
    .services-dropdown .menu-item.is-active i { transform: rotate(90deg); }

    .services-dropdown .menu-panel          { display: none; padding: 0.25rem 0.5rem 0.5rem; }
    .services-dropdown .menu-panel.is-open  { display: block; }

    .services-dropdown .submenu             { position: relative; margin-bottom: 0.25rem; }

    .services-dropdown .submenu-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 0.38rem 0.7rem;
        border-radius: var(--sd-radius);
        background: var(--sd-cobalt-tint);
        border: 1px solid #b8cbf5;
        border-left: 3px solid var(--sd-cobalt);
        font-size: var(--sd-font-size);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        cursor: pointer;
        margin-bottom: 0.2rem;
    }

    .services-dropdown .submenu-toggle:hover     { background: var(--sd-cobalt-hover); }
    .services-dropdown .submenu-toggle.is-active { background: var(--sd-cobalt-active); }
    .services-dropdown .submenu-toggle i         { transition: transform 0.2s ease; }
    .services-dropdown .submenu-toggle.is-active i { transform: rotate(90deg); }

    .services-dropdown .submenu-panel {
        display: none;
        position: absolute;
        top: 0;
        left: calc(100% + 5px);
        min-width: 250px;
        background: var(--sd-bg);
        border-radius: 0.5rem;
        padding: 0.35rem;
        z-index: 1000;
        box-shadow: 0 12px 36px rgba(33, 46, 62, 0.24);
        border: 1px solid #c8d2e0;
    }

    .services-dropdown .submenu-panel.is-open { display: block; }

    .services-dropdown .submenu-down .submenu-panel--down {
        position: static;
        box-shadow: none;
        border: 0;
        background: transparent;
        padding: 0;
        border-radius: 0;
    }

    .services-dropdown .submenu-side .submenu-panel--side {
        position: absolute;
        top: 0;
        left: calc(100% + 5px);
    }

    .services-dropdown .menu-panel .dropdown-item,
    .services-dropdown .submenu-panel .dropdown-item {
        font-size: var(--sd-font-size);
    }
</style>
