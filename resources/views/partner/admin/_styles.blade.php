<style>
    .partner-admin-shell {
        color: #12243a;
    }

    .partner-admin-header {
        background: #0f1b2a;
        border: 1px solid rgba(15, 23, 42, .14);
        border-radius: 8px;
        box-shadow: 0 18px 38px rgba(15, 23, 42, .18);
        color: #fff;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        min-height: 9.5rem;
        overflow: hidden;
        padding: 1.25rem;
        position: relative;
    }

    .partner-admin-header::before {
        background:
            linear-gradient(135deg, rgba(39, 176, 154, .32) 0 24%, transparent 24% 100%),
            linear-gradient(135deg, transparent 0 58%, rgba(47, 91, 219, .32) 58% 100%),
            repeating-linear-gradient(90deg, rgba(255, 255, 255, .06) 0 1px, transparent 1px 26px);
        content: "";
        inset: 0;
        position: absolute;
    }

    .partner-admin-header > * {
        position: relative;
        z-index: 1;
    }

    .partner-admin-eyebrow {
        color: #6ee7c8;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .partner-admin-title {
        color: #fff;
        font-size: 1.75rem;
        font-weight: 700;
        margin: .2rem 0 .15rem;
    }

    .partner-admin-subtitle {
        color: #cbd5e1;
        font-size: .88rem;
        margin: 0;
    }

    .partner-admin-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        margin-top: 1rem;
    }

    .partner-admin-hero-chip {
        align-items: center;
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 8px;
        display: inline-flex;
        gap: .4rem;
        padding: .45rem .6rem;
    }

    .partner-admin-hero-chip strong {
        color: #fff;
        font-size: .95rem;
    }

    .partner-admin-hero-chip span {
        color: #cbd5e1;
        font-size: .72rem;
        text-transform: uppercase;
    }

    .partner-admin-panel {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 8px;
        box-shadow: 0 16px 36px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .partner-admin-panel-header {
        align-items: center;
        background: #f8fafc;
        border-bottom: 1px solid rgba(15, 23, 42, .08);
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1rem 1.15rem;
    }

    .partner-admin-panel-title {
        font-size: .98rem;
        font-weight: 700;
        margin: 0;
    }

    .partner-admin-panel-body {
        padding: 1.15rem;
    }

    .partner-admin-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        justify-content: flex-end;
    }

    .partner-admin-header .btn-outline-primary {
        background: rgba(255, 255, 255, .08);
        border-color: rgba(255, 255, 255, .55);
        color: #fff;
    }

    .partner-admin-header .btn-primary {
        background: #2f5bdb;
        border-color: #2f5bdb;
    }

    .partner-admin-toolbar {
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 8px;
        margin-bottom: 1rem;
        padding: 1rem;
    }

    .partner-admin-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-bottom: 1rem;
    }

    .partner-admin-tabs a {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #dbe4ec;
        border-radius: 8px;
        color: #1d3654;
        display: inline-flex;
        font-weight: 700;
        gap: .4rem;
        padding: .55rem .75rem;
        text-decoration: none;
    }

    .partner-admin-tabs a.is-active {
        background: #eaf1ff;
        border-color: #2f5bdb;
        color: #183bb7;
    }

    .partner-admin-tabs span {
        background: rgba(15, 23, 42, .08);
        border-radius: 999px;
        color: inherit;
        font-size: .72rem;
        padding: .12rem .45rem;
    }

    .partner-admin-table {
        margin-bottom: 0;
    }

    .partner-admin-table thead th {
        background: #0f1b2a;
        color: #e5edf5;
        font-size: .72rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .partner-admin-table tbody td {
        border-color: #e5eaf0;
        padding: .72rem .6rem;
        vertical-align: middle;
    }

    .partner-admin-section {
        border: 1px solid #dbe4ec;
        border-radius: 8px;
        padding: 1rem;
    }

    .partner-admin-permission-group {
        background: #fff;
        border: 1px solid #dbe4ec;
        border-radius: 8px;
        padding: 1rem;
    }

    .partner-admin-status {
        border-radius: 999px;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 700;
        padding: .25rem .55rem;
    }

    .partner-admin-branch-summary {
        display: inline-flex;
        flex-wrap: wrap;
        gap: .25rem;
        max-width: 18rem;
    }

    .partner-admin-branch-summary small {
        color: #64748b;
        font-weight: 700;
    }

    .partner-admin-status.is-on {
        background: #e8f7ee;
        color: #17633a;
    }

    .partner-admin-status.is-off {
        background: #fff1f2;
        color: #9f1239;
    }

    .partner-admin-code {
        background: #f8fafc;
        border: 1px solid #e5eaf0;
        border-radius: 6px;
        color: #334155;
        display: block;
        max-width: 36rem;
        overflow: auto;
        padding: .45rem .55rem;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .partner-admin-header,
        .partner-admin-panel-header {
            flex-direction: column;
        }

        .partner-admin-actions {
            justify-content: flex-start;
            width: 100%;
        }
    }
</style>
