<style>
    @keyframes flame {
        0% {
            transform: scaleX(1) scaleY(1);
        }

        25% {
            transform: scaleX(1) scaleY(0.8);
        }

        50% {
            transform: scaleX(-1) scaleY(0.8);
        }

        75% {
            transform: scaleX(-1) scaleY(1);
        }
    }

    .survey-main-page {
        --sp-bg: #f6f7fb;
        --sp-surface: #ffffff;
        --sp-ink: #1f2933;
        --sp-muted: #6b7280;
        --sp-border: #e5e7eb;
        background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
            radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
            var(--sp-bg);
        padding: 1.5rem 0;
    }

    .survey-header {
        background: linear-gradient(120deg, #0f172a, #0f766e 70%);
        color: #f8fafc;
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
        margin-bottom: 1rem;
    }

    .survey-header h2 {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .survey-meta {
        color: rgba(248, 250, 252, 0.8);
        font-size: 0.9rem;
    }

    .survey-main-page .filter-shell,
    .survey-main-page .summary-bar,
    .survey-main-page .table-card {
        background: var(--sp-surface);
        border: 1px solid var(--sp-border);
        border-radius: 0.9rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
    }

    .survey-main-page .summary-bar {
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
    }

    .survey-main-page .summary-item {
        color: var(--sp-muted);
        font-size: 0.92rem;
    }

    .survey-main-page .summary-item strong {
        color: var(--sp-ink);
    }

    .survey-main-page .table-card {
        overflow: hidden;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
    }

    .survey-main-page .table-card .card-header {
        padding: 0.9rem 1.25rem;
    }

    .survey-main-page .table-card .table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        white-space: nowrap;
    }

    .survey-main-page .table-card .main-table {
        border-collapse: separate;
        border-spacing: 0 0.45rem;
        margin: 0;
    }

    .survey-main-page .table-card .main-table thead th {
        border: 0;
        background: #1f2937;
        color: #f8fafc;
        box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08);
    }

    .survey-main-page .table-card .main-table tbody tr {
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .survey-main-page .table-card .main-table tbody tr:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
    }

    .survey-main-page .table-card .main-table tbody td {
        font-size: 0.9rem;
        vertical-align: middle;
        border-top: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
    }

    .survey-main-page .table-card .main-table tbody tr.table-primary td,
    .survey-main-page .table-card .main-table tbody tr.table-warning td,
    .survey-main-page .table-card .main-table tbody tr.table-success td,
    .survey-main-page .table-card .main-table tbody tr.table-danger td,
    .survey-main-page .table-card .main-table tbody tr.table-secondary td,
    .survey-main-page .table-card .main-table tbody td.table-primary,
    .survey-main-page .table-card .main-table tbody td.table-warning,
    .survey-main-page .table-card .main-table tbody td.table-success,
    .survey-main-page .table-card .main-table tbody td.table-danger,
    .survey-main-page .table-card .main-table tbody td.table-secondary {
        border-color: rgba(15, 23, 42, 0.08);
    }

    .survey-main-page .table-card .main-table tbody tr:not(.table-primary):not(.table-warning):not(.table-success):not(.table-danger):not(.table-secondary) td:not(.table-primary):not(.table-warning):not(.table-success):not(.table-danger):not(.table-secondary):not(.text-bg-secondary):not(.text-bg-success):not(.text-bg-warning):not(.text-bg-danger):not(.text-bg-info) {
        background: #f8fafc;
    }

    .survey-main-page .table-card .main-table tbody td:first-child {
        border-left: 1px solid #e2e8f0;
        border-top-left-radius: 0.7rem;
        border-bottom-left-radius: 0.7rem;
    }

    .survey-main-page .table-card .main-table tbody td:last-child {
        border-right: 1px solid #e2e8f0;
        border-top-right-radius: 0.7rem;
        border-bottom-right-radius: 0.7rem;
    }

    .survey-main-page .filter-shell .form-label {
        color: var(--sp-muted);
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .survey-main-page .filter-shell .btn,
    .survey-main-page .filter-shell .form-select,
    .survey-main-page .filter-shell .form-control {
        border-radius: 0.65rem;
    }
</style>
