<style>
    .closure-dash {
        --dash-ink: #102033;
        --dash-muted: #64748b;
        --dash-line: #dbe4ea;
        --dash-teal: #155f67;
        --dash-green: #0f8a77;
        --dash-blue: #263cc8;
        --dash-red: #e32c2c;
        --dash-amber: #f7d200;
    }

    .closure-dash .dash-hero {
        background: linear-gradient(120deg, #102033 0%, #155f67 58%, #0f8a77 100%);
        border-radius: 8px;
        color: #fff;
        padding: 22px 24px;
        box-shadow: 0 18px 40px rgba(16, 32, 51, .16);
    }

    .closure-dash .dash-title {
        font-size: 1.65rem;
        font-weight: 800;
        margin: 0;
    }

    .closure-dash .dash-subtitle {
        color: rgba(255, 255, 255, .78);
        font-size: .92rem;
        margin-top: 4px;
    }

    .closure-dash .metric-card,
    .closure-dash .chart-card,
    .closure-dash .table-card {
        background: #fff;
        border: 1px solid var(--dash-line);
        border-radius: 8px;
        box-shadow: 0 12px 30px rgba(16, 32, 51, .08);
    }

    .closure-dash .metric-card {
        padding: 16px 18px;
        min-height: 112px;
        border-top: 4px solid var(--accent, var(--dash-teal));
    }

    .closure-dash .metric-label {
        color: var(--dash-muted);
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .closure-dash .metric-value {
        color: var(--dash-ink);
        font-size: 2rem;
        font-weight: 850;
        line-height: 1.1;
        margin-top: 8px;
    }

    .closure-dash .metric-note {
        color: var(--dash-muted);
        font-size: .82rem;
        margin-top: 6px;
    }

    .closure-dash .chart-card {
        padding: 0;
        overflow: hidden;
    }

    .closure-dash .chart-head {
        align-items: center;
        border-bottom: 1px solid var(--dash-line);
        display: flex;
        justify-content: space-between;
        gap: 12px;
        min-height: 58px;
        padding: 14px 18px;
    }

    .closure-dash .chart-title {
        color: var(--dash-ink);
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
    }

    .closure-dash .chart-subtitle {
        color: var(--dash-muted);
        font-size: .8rem;
    }

    .closure-dash .chart-body {
        height: 320px;
        padding: 18px;
    }

    .closure-dash .table-card {
        overflow: hidden;
    }

    .closure-dash .table-card .table {
        margin-bottom: 0;
        table-layout: fixed;
    }

    .closure-dash .table-card thead th {
        background: #102033;
        color: #fff;
        font-size: .78rem;
        letter-spacing: .02em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .closure-dash .table-card td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        vertical-align: middle;
    }

    .closure-dash .group-row td {
        background: var(--dash-soft, #f4f7f9);
        border-top: 2px solid var(--dash-line);
        padding-top: 10px;
        padding-bottom: 10px;
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
    }

    .closure-dash .group-row .chart-title {
        font-size: .92rem;
    }

    .closure-dash .badge-stack {
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 800;
        padding: 5px 9px;
        white-space: nowrap;
        display: inline-block;
    }

    .closure-dash .badge-closed {
        background: rgba(15, 138, 119, .14);
        color: #0a5c4e;
    }

    .closure-dash .badge-open {
        background: rgba(247, 210, 0, .22);
        color: #8a6d00;
    }

    .closure-dash .badge-aging-low {
        background: rgba(21, 95, 103, .12);
        color: #155f67;
    }

    .closure-dash .badge-aging-mid {
        background: rgba(247, 210, 0, .22);
        color: #8a6d00;
    }

    .closure-dash .badge-aging-high {
        background: rgba(227, 44, 44, .12);
        color: #a11d1d;
    }

    .closure-dash .badge-exception {
        background: rgba(38, 60, 200, .1);
        color: var(--dash-blue);
    }

    .closure-dash .badge-type {
        background: rgba(16, 32, 51, .08);
        color: var(--dash-ink);
    }

    .closure-dash .btn-dash {
        border-radius: 6px;
        font-weight: 700;
        font-size: .82rem;
    }

    .closure-dash .row-action {
        align-items: center;
        background: rgba(21, 95, 103, .1);
        border-radius: 6px;
        color: var(--dash-teal);
        display: inline-flex;
        gap: 5px;
        font-size: .76rem;
        font-weight: 700;
        padding: 5px 10px;
        text-decoration: none;
        white-space: nowrap;
    }

    .closure-dash .row-action:hover {
        background: var(--dash-teal);
        color: #fff;
    }

    .closure-dash .progress {
        background: rgba(16, 32, 51, .08);
        height: 6px;
        border-radius: 999px;
    }

    .closure-dash select.form-select-sm {
        border-color: #b8c8d0;
        border-radius: 6px;
    }
</style>
