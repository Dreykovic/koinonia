<style>
    /* ============ MENU PUBLIC ============ */
    .menu-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px 16px;
        margin-bottom: 18px;
    }

    .menu-head-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 22px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.2;
    }

    .menu-head-sub {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
        font-size: 13px;
        color: var(--text-secondary);
    }

    /* ---- Bloc "Aujourd'hui" ---- */
    .today-card {
        position: relative;
        margin-bottom: 22px;
        padding: 22px 24px;
        border-radius: 22px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: #fff;
        box-shadow: 0 14px 30px rgba(139, 35, 50, 0.28);
        overflow: hidden;
    }

    .today-card::after {
        content: '';
        position: absolute;
        right: -60px;
        top: -70px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.07);
        pointer-events: none;
    }

    .today-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px 14px; margin-bottom: 16px; }

    .today-kicker {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--accent);
    }

    .today-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 22px;
        font-weight: 700;
        margin: 0;
        color: #fff;
        line-height: 1.2;
    }

    .today-slots { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; position: relative; }

    .today-slot {
        padding: 16px 18px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.10);
        border: 1px solid rgba(255, 255, 255, 0.14);
        min-height: 110px;
    }

    .today-slot.is-now {
        background: var(--bg-card);
        border-color: transparent;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
    }

    .today-slot-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }

    .today-slot-period {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: rgba(255, 255, 255, 0.85);
    }

    .today-slot-period .ki-duotone { font-size: 16px; }
    .today-slot.is-now .today-slot-period { color: var(--accent-dark); }

    .today-slot-now {
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        background: var(--accent);
        color: var(--primary-dark);
    }

    .today-slot-dish {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 21px;
        font-weight: 700;
        line-height: 1.25;
        color: #fff;
        overflow-wrap: anywhere;
    }

    .today-slot.is-now .today-slot-dish { color: var(--text-primary); }
    .today-slot.is-empty .today-slot-dish { font-size: 16px; font-style: italic; font-weight: 600; opacity: 0.75; }

    .today-slot-desc { margin-top: 6px; font-size: 12.5px; color: rgba(255, 255, 255, 0.8); }
    .today-slot.is-now .today-slot-desc { color: var(--text-secondary); }

    .menu-section-title {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 17px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 4px 10px;
    }

    .menu-section-title--cards { display: none; }

    .menu-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
        color: var(--text-secondary);
    }

    /* ---- Tableau (ecrans larges) ---- */
    .menu-table-wrap {
        background: var(--bg-card);
        border-radius: 20px;
        padding: 10px;
        box-shadow: var(--neu-shadow);
        overflow-x: auto;
        scrollbar-width: thin;
    }

    .menu-table {
        width: 100%;
        min-width: 720px;
        border-collapse: separate;
        border-spacing: 6px;
        table-layout: fixed;
    }

    .menu-table th, .menu-table td { vertical-align: top; }

    .menu-table th.period-col, .menu-table td.period-col { width: 72px; }

    .menu-table thead th {
        padding: 10px 6px 6px;
        text-align: center;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-muted);
    }

    .menu-table thead th .day-pill {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .menu-table thead th .today-tag {
        display: block;
        margin-top: 4px;
        font-size: 9.5px;
        letter-spacing: 0.4px;
        color: var(--accent-dark);
    }

    .menu-table td.period-col {
        padding: 12px 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-secondary);
        white-space: nowrap;
    }

    .menu-table td.period-col .ki-duotone { font-size: 15px; margin-right: 4px; }
    .menu-table tr.period--midi td.period-col { color: var(--accent-dark); }
    .menu-table tr.period--soir td.period-col { color: var(--secondary-dark); }

    .menu-cell {
        min-height: 72px;
        padding: 12px 12px;
        border-radius: 14px;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
        font-size: 14px;
        font-weight: 700;
        line-height: 1.35;
        color: var(--text-primary);
        overflow-wrap: anywhere;
    }

    .menu-cell.is-empty {
        font-weight: 600;
        font-style: italic;
        color: var(--text-muted);
        background: transparent;
        box-shadow: inset 0 0 0 1px rgba(60, 48, 40, 0.08);
    }

    .menu-cell-desc {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
    }

    .menu-cell-price {
        display: inline-block;
        margin-top: 6px;
        font-size: 11.5px;
        font-weight: 700;
        color: var(--accent-dark);
    }

    /* ---- Cartes par jour (tablette et mobile) ---- */
    .menu-days { display: none; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }

    .menu-day {
        background: var(--bg-card);
        border-radius: 18px;
        padding: 15px;
        box-shadow: var(--neu-shadow-sm);
    }

    .menu-day-head { display: flex; align-items: center; gap: 11px; margin-bottom: 12px; }

    .menu-day-badge {
        width: 42px;
        height: 42px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12.5px;
        font-weight: 700;
        letter-spacing: 0.5px;
        flex-shrink: 0;
        box-shadow: var(--neu-shadow-sm);
    }

    .menu-day-name {
        font-family: 'Playfair Display', Georgia, serif;
        font-size: 17px;
        font-weight: 700;
        color: var(--text-primary);
        text-transform: capitalize;
        line-height: 1.2;
    }

    .menu-day-today {
        display: block;
        margin-top: 2px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--accent-dark);
    }

    .menu-slot {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 12px;
        background: var(--bg-base);
        box-shadow: var(--neu-shadow-inset);
    }

    .menu-slot + .menu-slot { margin-top: 8px; }

    .menu-slot-period {
        min-width: 46px;
        padding-top: 2px;
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
    }

    .menu-slot--midi .menu-slot-period { color: var(--accent-dark); }
    .menu-slot--soir .menu-slot-period { color: var(--secondary-dark); }

    .menu-slot-dish { font-size: 14.5px; font-weight: 700; color: var(--text-primary); line-height: 1.35; overflow-wrap: anywhere; }
    .menu-slot.is-empty .menu-slot-dish { font-weight: 600; font-style: italic; color: var(--text-muted); }

    /* ---- Etat vide ---- */
    .menu-empty {
        padding: 44px 20px;
        text-align: center;
        border-radius: 20px;
        background: var(--bg-card);
        box-shadow: var(--neu-shadow-sm);
        color: var(--text-secondary);
    }

    .menu-empty .ki-duotone { font-size: 40px; color: var(--text-muted); margin-bottom: 10px; }
    .menu-empty h3 { font-family: 'Playfair Display', Georgia, serif; font-size: 19px; color: var(--text-primary); margin: 0 0 6px; }

    @media (max-width: 991px) {
        .menu-table-wrap { display: none; }
        .menu-section-title { display: none; }
        .menu-section-title--cards { display: block; }
        .menu-days { display: grid; }
    }

    @media (max-width: 575px) {
        .menu-head-title { font-size: 20px; }
        .today-card { padding: 18px 16px; border-radius: 18px; }
        .today-title { font-size: 19px; }
        .today-slots { grid-template-columns: 1fr; gap: 10px; }
        .today-slot { min-height: 0; padding: 14px 15px; }
        .today-slot-dish { font-size: 19px; }
        .menu-days { grid-template-columns: 1fr; gap: 12px; }
        .menu-day { padding: 13px; border-radius: 16px; }
    }
</style>
