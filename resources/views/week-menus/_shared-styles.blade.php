<style>
    /* Mobile members optimizations */
    @media (max-width: 768px) {
        .table-responsive {
            display: none;
        }

        .members-mobile-list {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .members-mobile-list {
            display: none;
        }
    }

    /* Mobile member cards */
    .member-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .member-mobile-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 10px;
    }

    .member-mobile-header .symbol {
        flex-shrink: 0;
    }

    .member-mobile-header .symbol-label {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }

    .member-mobile-info {
        flex: 1;
        min-width: 0;
    }

    .member-mobile-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .member-mobile-email {
        font-size: 12px;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .member-mobile-room {
        font-size: 11px;
        color: var(--text-muted);
    }

    .member-mobile-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 10px;
    }

    .member-mobile-actions {
        display: flex;
        gap: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(60, 48, 40, 0.08);
    }

    .member-mobile-actions .btn {
        flex: 1;
    }

    .member-mobile-actions form {
        flex: 1;
    }

    .member-mobile-actions form .btn {
        width: 100%;
    }
</style>
