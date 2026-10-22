@push('styles')
<style>
    /* ===== Admins index — light + gold accent (card 55) ============= */
    .ad-header { margin-bottom: 1.25rem; }
    .ad-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .ad-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .ad-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    /* KPI strip */
    .ad-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr));
        gap: .75rem; margin-bottom: 1.25rem; }
    .ad-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .ad-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05); }
    .ad-kpi .ico {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500);
    }
    .ad-kpi .ico.ico-blue   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .ad-kpi .ico.ico-violet { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
    .ad-kpi .ico.ico-green  { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .ad-kpi .num   { font-size: 1.35rem; font-weight: 800; color: var(--gold-400); line-height: 1.1; letter-spacing: -.5px; }
    .ad-kpi .num.muted { color: #0f172a; }
    .ad-kpi .lbl   { font-size: .8rem; color: #64748b; }

    /* Search engine card */
    .ad-search-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1rem 1.1rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .ad-search-card .se-title {
        display: flex; align-items: center; gap: .55rem;
        font-size: .92rem; font-weight: 700; color: #0f172a; margin-bottom: .6rem;
    }
    .ad-search-card .se-title i { color: var(--gold-400); font-size: 1.1rem; }
    .ad-search-card .se-hint { color: #64748b; font-size: .8rem; font-weight: 400; margin-{{ $isRtl ? 'right' : 'left' }}: auto; }
    .ad-search-row { display: flex; gap: .55rem; flex-wrap: wrap; }
    .ad-search-row .form-control {
        flex: 1 1 240px; min-width: 0;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .85rem; font-size: .93rem; color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ad-search-row .form-control:focus {
        border-color: var(--gold-300);
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
    .ad-search-row select.form-control { flex: 0 1 220px; }
    .ad-search-row .btn-gold, .ad-search-row .btn-reset { flex: 0 0 auto; }
    .btn-reset {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-weight: 600; padding: .55rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem;
        transition: all .15s ease;
    }
    .btn-reset:hover { background: #f8fafc; color: #0f172a; }

    /* Toolbar */
    .ad-toolbar {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px 14px 0 0;
        padding: .9rem 1.1rem; display: flex; flex-wrap: wrap; gap: .55rem;
        align-items: center; justify-content: space-between;
        border-bottom: 0;
    }
    .ad-toolbar .left { display: flex; flex-wrap: wrap; gap: .45rem; align-items: center; }
    .ad-toolbar .count-pill {
        background: #f8fafc; border: 1px solid #e5e7eb;
        color: #475569; font-size: .78rem; font-weight: 600;
        padding: .25rem .65rem; border-radius: 999px;
    }

    /* Gold CTA */
    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-gold:hover {
        background: linear-gradient(135deg, var(--gold-400), var(--gold-500));
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(207,160,70,.22);
    }
    .btn-gold:active { transform: translateY(0); }

    .btn-ghost {
        background: #fff; border: 1px solid #e2e8f0; color: #334155;
        font-weight: 600; padding: .55rem 1rem; border-radius: 10px;
        display: inline-flex; align-items: center; gap: .35rem;
        transition: all .15s ease;
    }
    .btn-ghost:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1; }
    .btn-ghost i { color: var(--gold-400); }

    /* Surface */
    .ad-surface {
        background: #fff; border: 1px solid #e5e7eb; border-top: 0;
        border-radius: 0 0 14px 14px; overflow: hidden;
    }

    /* Table */
    .ad-table { margin: 0; }
    .ad-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem;
        white-space: nowrap;
    }
    .ad-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; }
    .ad-table tbody tr { transition: background .15s ease; }
    .ad-table tbody tr:hover { background: #fafbfc; }
    .ad-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }

    /* User cell */
    .ad-user { display: flex; align-items: center; gap: .65rem; }
    .ad-avatar {
        width: 38px; height: 38px; border-radius: 10px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: var(--gold-500); font-weight: 700; font-size: .88rem;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .ad-name { font-weight: 600; color: #0f172a; line-height: 1.25; }
    .ad-secondary { color: #64748b; font-size: .82rem; }

    /* Pills */
    .ad-pill {
        display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent;
    }
    .ad-pill .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .ad-pill.active   { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .ad-pill.active .dot { background: #10b981; }
    .ad-pill.inactive { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
    .ad-pill.inactive .dot { background: #9ca3af; }
    .ad-pill.role     { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .ad-pill.role-super { background: #ede9fe; color: #6d28d9; border-color: #ddd6fe; }
    .ad-pill.role-muted { background: #f1f5f9; color: #64748b; border-color: #e2e8f0; }

    /* Action buttons */
    .ad-actions { display: inline-flex; align-items: center; gap: .35rem; }
    .ad-action-btn {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #e2e8f0; background: #fff;
        color: #475569; transition: all .15s ease;
    }
    .ad-action-btn:hover { transform: translateY(-1px); }
    .ad-action-btn.edit:hover { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .ad-action-btn.del { background: #fff5f5; border-color: #fecaca; color: #b91c1c; }
    .ad-action-btn.del:hover { background: #fee2e2; border-color: #fca5a5; }
    .ad-action-btn.warn:hover { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
    .ad-action-btn.info:hover { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
    .ad-action-btn.more {
        background: #fff; border-color: #e2e8f0; color: #64748b;
    }
    .ad-action-btn.more:hover { background: #f8fafc; color: #0f172a; }

    /* Let row action dropdowns float above the table instead of being clipped. */
    body.theme-light .ad-table-wrap, body.theme-light .table-responsive { overflow: visible; }
    body.theme-light .dropdown-menu.is-floating {
        position: fixed; z-index: 1080;
        box-shadow: 0 8px 24px rgba(15,23,42,.12), 0 2px 6px rgba(15,23,42,.06);
    }

    /* Dropdown menu polish */
    .ad-actions .dropdown-menu {
        border: 1px solid #e5e7eb; border-radius: 12px; padding: .35rem;
        box-shadow: 0 10px 30px rgba(15,23,42,.08), 0 2px 8px rgba(15,23,42,.04);
        min-width: 240px;
    }
    .ad-actions .dropdown-item {
        border-radius: 8px; padding: .55rem .75rem; font-size: .88rem;
        display: flex; align-items: center; gap: .55rem; color: #334155;
    }
    .ad-actions .dropdown-item:hover { background: #f8fafc; color: #0f172a; }
    .ad-actions .dropdown-item i { color: var(--gold-400); width: 18px; text-align: center; }
    .ad-actions .dropdown-item.danger { color: #b91c1c; }
    .ad-actions .dropdown-item.danger:hover { background: #fee2e2; color: #991b1b; }
    .ad-actions .dropdown-item.danger i { color: #b91c1c; }
    .ad-actions .dropdown-item button {
        background: transparent; border: 0; padding: 0; width: 100%;
        text-align: {{ $isRtl ? 'right' : 'left' }}; color: inherit; font: inherit;
        display: flex; align-items: center; gap: .55rem;
    }

    /* Add menu — pretty picker */
    .ad-add-menu { min-width: 320px; max-height: 70vh; overflow-y: auto; padding: .5rem !important; }
    .ad-add-menu .dropdown-header {
        font-size: .78rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px;
        padding: .55rem .55rem .35rem; font-weight: 700;
    }
    .ad-add-menu .ad-job-item {
        display: flex; align-items: center; gap: .65rem;
        padding: .55rem .65rem; border-radius: 8px;
        color: #0f172a; text-decoration: none; font-size: .9rem;
        transition: background .15s ease;
    }
    .ad-add-menu .ad-job-item:hover { background: #fffbeb; color: #92400e; }
    .ad-add-menu .ad-job-item .jt-ico {
        width: 30px; height: 30px; border-radius: 8px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: var(--gold-500); font-size: .9rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .ad-add-menu .ad-job-item .jt-meta { display: flex; flex-direction: column; line-height: 1.2; }
    .ad-add-menu .ad-job-item .jt-slug { font-size: .72rem; color: #94a3b8; }
    .ad-add-menu .dropdown-divider { margin: .35rem 0; }
    .ad-add-menu .ad-blank {
        display: flex; align-items: center; gap: .55rem;
        padding: .55rem .65rem; border-radius: 8px;
        color: #334155; font-size: .88rem;
    }
    .ad-add-menu .ad-blank:hover { background: #f8fafc; }

    /* Empty state */
    .ad-empty { padding: 2.75rem 1rem; text-align: center; color: #94a3b8; }
    .ad-empty i { font-size: 2.5rem; opacity: .55; display: block; margin-bottom: .5rem; color: #cbd5e1; }
    .ad-empty .lbl { font-size: .95rem; color: #64748b; }
    .ad-empty .sub { font-size: .8rem; color: #94a3b8; margin-top: .25rem; }

    /* Alerts */
    .ad-alert {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: center;
        gap: .55rem; font-size: .9rem; margin-bottom: 1rem;
    }
    .ad-alert i { color: #10b981; font-size: 1.1rem; }
    .ad-alert.err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }

    /* Footer */
    .ad-footer {
        padding: .85rem 1rem; background: #fff; border-top: 1px solid #f1f5f9;
        display: flex; justify-content: space-between; align-items: center;
    }
    .ad-footer .pagination { margin: 0; }

    /* Responsive */
    @media (max-width: 991.98px) {
        .ad-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .ad-search-row select.form-control { flex: 1 1 100%; }
    }
    @media (max-width: 575.98px) {
        .ad-kpis { gap: .55rem; }
        .ad-kpi { padding: .7rem .8rem; }
        .ad-kpi .ico { width: 32px; height: 32px; font-size: .95rem; }
        .ad-kpi .num { font-size: 1.15rem; }
        .ad-kpi .lbl { font-size: .72rem; }
        .ad-toolbar { padding: .75rem .85rem; gap: .4rem; }
        .ad-table thead { display: none; }
        .ad-table, .ad-table tbody, .ad-table tr, .ad-table td { display: block; width: 100%; }
        .ad-table tbody tr {
            border: 1px solid #f1f5f9; border-radius: 12px;
            margin: .5rem .65rem; padding: .65rem .8rem; background: #fff;
        }
        .ad-table tbody tr + tr td { border-top: 0; }
        .ad-table tbody td {
            padding: .35rem 0; border: 0; display: flex; align-items: center;
            justify-content: space-between; gap: .75rem; font-size: .88rem;
        }
        .ad-table tbody td::before {
            content: attr(data-label);
            font-size: .68rem; color: #64748b; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .ad-table tbody td.actions-cell { justify-content: flex-end; }
        .ad-table tbody td.actions-cell::before { display: none; }
        .ad-user { gap: .55rem; }
        .ad-avatar { width: 34px; height: 34px; font-size: .8rem; }
    }
</style>
@endpush
