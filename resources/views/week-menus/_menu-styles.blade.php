<style>
    /* ============ CYCLE DE 4 SEMAINES ============ */
    .week-switcher {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 20px 22px;
        box-shadow: var(--neu-shadow);
        margin-bottom: 22px;
    }

    .week-switcher-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
    }

    .week-live {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: var(--text-secondary);
    }

    .week-live-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--success);
        box-shadow: 0 0 0 0 rgba(93, 138, 102, 0.55);
        animation: weekPulse 2.4s ease-out infinite;
        flex-shrink: 0;
    }

    @keyframes weekPulse {
        70% { box-shadow: 0 0 0 9px rgba(93, 138, 102, 0); }
        100% { box-shadow: 0 0 0 0 rgba(93, 138, 102, 0); }
    }

    @media (prefers-reduced-motion: reduce) {
        .week-live-dot { animation: none; }
    }

    .week-live strong {
        color: var(--text-primary);
        font-weight: 700;
    }

    .week-cycle-link {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--primary);
        text-decoration: none;
    }

    .week-cycle-link:hover { color: var(--primary-dark); text-decoration: underline; }

    /* Segmented control des semaines : defile horizontalement si besoin */
    .week-tabs {
        display: flex;
        gap: 8px;
        padding: 7px;
        border-radius: 16px;
        box-shadow: var(--neu-shadow-inset);
        background: var(--bg-base);
        overflow-x: auto;
        scrollbar-width: thin;
    }

    .week-tab {
        flex: 1 0 110px;
        max-width: 220px;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1px;
        min-height: 68px;
        padding: 9px 6px;
        border-radius: 12px;
        text-decoration: none;
        color: var(--text-secondary);
        font-size: 13px;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
        transition: all 0.2s ease;
    }

    .week-tab:hover {
        color: var(--primary);
        background: rgba(139, 35, 50, 0.06);
    }

    .week-tab.is-current {
        background: var(--bg-card);
        color: var(--primary);
        box-shadow: var(--neu-shadow-sm);
    }

    .week-tab-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-muted);
    }

    .week-tab.is-current .week-tab-label { color: var(--primary-light); }

    .week-tab-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.2;
        max-width: 100%;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        overflow-wrap: anywhere;
    }

    .week-tab.is-reserve { opacity: 0.75; }
    .week-tab.is-reserve .week-tab-label { color: var(--text-muted); }

    .week-tab-state {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 2px;
        font-size: 9.5px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 700;
        color: var(--success);
    }

    .week-tab-state::before {
        content: '';
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--success);
    }

    /* Bandeau d'action pour publier la semaine consultee */
    .week-publish {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin-top: 16px;
        padding: 13px 16px;
        border-radius: 14px;
        background: var(--warning-light);
        border: 1px solid rgba(212, 168, 75, 0.35);
    }

    .week-publish-text {
        flex: 1;
        min-width: 200px;
        font-size: 13px;
        color: var(--text-secondary);
    }

    /* ============ PLATEAU DES JOURS ============ */
    .week-form { padding-bottom: 4px; }

    .day-board {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 16px;
    }

    .day-card {
        background: var(--bg-card);
        border-radius: 18px;
        padding: 16px;
        box-shadow: var(--neu-shadow-sm);
        transition: box-shadow 0.25s ease, transform 0.25s ease;
    }

    .day-card:focus-within,
    .day-card:hover {
        box-shadow: var(--neu-shadow);
        transform: translateY(-2px);
    }

    .day-card.is-today {
        box-shadow: var(--neu-shadow), 0 0 0 2px var(--accent);
    }

    .day-card-head {
        display: flex;
        align-items: center;
        gap: 11px;
        margin-bottom: 14px;
    }

    .day-badge {
        width: 44px;
        height: 44px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        flex-shrink: 0;
        box-shadow: var(--neu-shadow-sm);
    }

    .day-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        text-transform: capitalize;
        line-height: 1.2;
    }

    .day-today-tag {
        display: inline-block;
        margin-top: 2px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--accent-dark);
    }

    /* ---- Creneau midi / soir ---- */
    .slot {
        border-radius: 14px;
        padding: 12px;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
    }

    .slot + .slot { margin-top: 11px; }

    .slot-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 8px;
    }

    .slot-label {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-secondary);
    }

    .slot-label .ki-duotone { font-size: 15px; }
    .slot--midi .slot-label { color: var(--accent-dark); }
    .slot--soir .slot-label { color: var(--secondary-dark); }

    .slot-input {
        width: 100%;
        border: none;
        border-radius: 10px;
        padding: 11px 13px;
        font-family: 'Nunito', sans-serif;
        font-size: 14.5px;
        font-weight: 600;
        color: var(--text-primary);
        background: var(--bg-card);
        box-shadow: var(--neu-shadow-sm);
        resize: none;
        overflow: hidden;
        min-height: 44px;
        transition: box-shadow 0.2s ease;
    }

    .slot-input::placeholder { color: var(--text-muted); font-weight: 500; }

    .slot-input:focus {
        outline: none;
        box-shadow: var(--neu-shadow-sm), 0 0 0 2px var(--accent);
    }

    .slot.is-hidden .slot-input {
        color: var(--text-muted);
        text-decoration: line-through;
        text-decoration-thickness: 1px;
    }

    .slot-input.is-invalid { box-shadow: var(--neu-shadow-sm), 0 0 0 2px var(--danger); }

    .slot-error {
        display: none;
        margin-top: 6px;
        font-size: 11.5px;
        color: var(--danger);
    }

    .slot-error.show { display: block; }

    /* Interrupteur visible / masque */
    .slot-visibility {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        cursor: pointer;
        user-select: none;
        margin: 0;
    }

    .slot-visibility input { position: absolute; opacity: 0; pointer-events: none; }

    .slot-switch {
        position: relative;
        width: 38px;
        height: 21px;
        border-radius: 11px;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
        transition: background 0.22s ease;
        flex-shrink: 0;
    }

    .slot-switch::after {
        content: '';
        position: absolute;
        top: 3px;
        left: 3px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: var(--bg-card);
        box-shadow: var(--neu-shadow-sm);
        transition: transform 0.22s ease;
    }

    .slot-visibility input:checked + .slot-switch { background: var(--success); }
    .slot-visibility input:checked + .slot-switch::after { transform: translateX(17px); }
    .slot-visibility input:focus-visible + .slot-switch { box-shadow: var(--neu-shadow-inset), 0 0 0 2px var(--accent); }

    .slot-visibility-text {
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
        min-width: 48px;
    }

    .slot-visibility input:checked ~ .slot-visibility-text { color: var(--success); }

    /* ============ BARRE D'ENREGISTREMENT ============ */
    .save-bar {
        position: sticky;
        bottom: 0;
        z-index: 20;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 20px;
        padding: 14px 18px;
        border-radius: 18px 18px 0 0;
        background: var(--bg-card);
        box-shadow: 0 -6px 18px var(--shadow-dark);
    }

    .save-bar-status {
        display: flex;
        align-items: center;
        gap: 9px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
    }

    .save-bar-status.is-dirty { color: var(--primary); }

    .save-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--text-muted);
        flex-shrink: 0;
    }

    .save-bar-status.is-dirty .save-dot { background: var(--primary); }

    .save-bar .btn { min-height: 46px; padding-inline: 26px; white-space: nowrap; }

    .save-label-short { display: none; }

    @media (max-width: 575px) {
        .week-switcher { padding: 16px; border-radius: 16px; }
        .week-tabs { gap: 5px; padding: 5px; }
        .week-tab { padding: 8px 6px; min-height: 62px; flex-basis: 96px; }
        .week-tab-label { font-size: 8.5px; letter-spacing: 0.3px; }
        .week-tab-name { font-size: 13px; }
        .week-tab-state { font-size: 8.5px; letter-spacing: 0.2px; }
        .week-tab-state::before { width: 4px; height: 4px; }
        .day-board { grid-template-columns: 1fr; gap: 13px; }
        .save-bar { padding: 12px 14px; }
        .save-bar .btn { flex: 1; padding-inline: 14px; }
        .save-bar-status { width: 100%; }
        .save-label-full { display: none; }
        .save-label-short { display: inline; }
    }
</style>
