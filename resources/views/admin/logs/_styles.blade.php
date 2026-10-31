@push('styles')
<style>
    /* Journaux : table sur ordinateur, cartes sur mobile */
    @media (max-width: 768px) {
        .log-table-wrapper .table-responsive {
            display: none;
        }

        .logs-mobile {
            display: block;
        }
    }

    @media (min-width: 769px) {
        .logs-mobile {
            display: none;
        }
    }

    .log-mobile-card {
        background: var(--bg-card);
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: var(--neu-shadow-sm);
    }

    .log-message {
        word-break: break-word;
    }

    .log-filters label {
        font-size: 12px;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .log-pre {
        background: var(--bg-base);
        border-radius: 8px;
        padding: 12px;
        font-size: 12px;
        max-height: 480px;
        overflow: auto;
        white-space: pre-wrap;
        word-break: break-word;
        margin: 0;
    }

    .log-trace summary {
        cursor: pointer;
        font-weight: 600;
        margin-bottom: 8px;
    }
</style>
@endpush
