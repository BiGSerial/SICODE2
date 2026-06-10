<style>
    .user-activity-page {
        --activity-bg: #f6f7fb;
        --activity-surface: #ffffff;
        --activity-ink: #1f2933;
        --activity-muted: #6b7280;
        --activity-border: #e5e7eb;
        background: radial-gradient(circle at 10% 0%, #eef2ff, transparent 40%),
            radial-gradient(circle at 90% 10%, #ecfeff, transparent 35%),
            var(--activity-bg);
        padding: 1.5rem 0;
    }

    .user-activity-hero {
        background: linear-gradient(120deg, #0f172a, var(--activity-accent, #0f766e) 70%);
        color: #f8fafc;
        border-radius: 1rem;
        padding: 1.5rem 2rem;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
        margin-bottom: 1.5rem;
    }

    .user-activity-hero h2 {
        font-weight: 700;
        letter-spacing: 0.02em;
        margin: 0;
    }

    .user-activity-hero .activity-meta {
        color: rgba(248, 250, 252, 0.78);
        font-size: 0.95rem;
    }

    .user-activity-hero .activity-count {
        font-size: 1.6rem;
        font-weight: 700;
        line-height: 1;
    }

    .user-activity-page .activity-filter-card {
        background: var(--activity-surface);
        border: 1px solid var(--activity-border);
        border-radius: 0.9rem;
        padding: 1rem 1.25rem;
        height: 100%;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
    }

    .user-activity-page .activity-filter-title {
        color: var(--activity-muted);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .user-activity-summary {
        background: var(--activity-surface);
        border: 1px solid var(--activity-border);
        border-radius: 0.9rem;
        padding: 0.75rem 1.25rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .user-activity-summary .activity-summary-text {
        color: var(--activity-muted);
        font-size: 0.92rem;
    }

    .user-activity-summary strong {
        color: var(--activity-ink);
    }

    .user-activity-table-card {
        background: var(--activity-surface);
        border: 1px solid var(--activity-border);
        border-radius: 0.9rem;
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .user-activity-table-header {
        color: #ffffff;
        padding: 1rem 1.25rem;
    }

    .user-activity-table-title {
        color: inherit;
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.2;
        margin: 0;
    }

    .user-activity-table-subtitle {
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.82rem;
        margin-top: 0.2rem;
    }

    .user-activity-table-card .table {
        margin-bottom: 0;
    }

    .user-activity-table-card .table thead th {
        font-size: 0.75rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .user-activity-table-card .table tbody td {
        font-size: 0.92rem;
    }

    @media (max-width: 991px) {
        .user-activity-hero {
            padding: 1.25rem;
        }
    }
</style>
