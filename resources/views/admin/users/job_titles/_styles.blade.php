@push('styles')
<style>
    /* ===== Job Titles — light + gold accent ============================ */
    .jt-header { margin-bottom: 1.25rem; }
    .jt-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .jt-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .jt-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    /* KPI strip */
    .jt-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr));
        gap: .75rem; margin-bottom: 1.25rem; }
    .jt-kpi {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1rem; display: flex; align-items: center; gap: .75rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .jt-kpi:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(15,23,42,.06), 0 12px 28px rgba(15,23,42,.05); }
    .jt-kpi .ico {
        width: 38px; height: 38px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
        background: linear-gradient(135deg, #fef3c7, #fde68a); color: var(--gold-500);
    }
    .jt-kpi .ico.ico-blue   { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .jt-kpi .ico.ico-violet { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #6d28d9; }
    .jt-kpi .ico.ico-green  { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }
    .jt-kpi .num   { font-size: 1.35rem; font-weight: 800; color: var(--gold-400); line-height: 1.1; letter-spacing: -.5px; }
    .jt-kpi .num.muted { color: #0f172a; }
    .jt-kpi .lbl   { font-size: .8rem; color: #64748b; }

    /* Surface cards (override only what we need so theme-light still applies) */
    .jt-surface .card-header {
        background: #fff; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between;
        padding: 1rem 1.1rem;
    }
    .jt-surface .card-header h5 {
        margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a;
        display: inline-flex; align-items: center; gap: .55rem;
    }
    .jt-surface .card-header h5 i { color: var(--gold-400); }
    .jt-surface .card-header .count-pill {
        background: #f8fafc; border: 1px solid #e5e7eb;
        color: #475569; font-size: .78rem; font-weight: 600;
        padding: .2rem .55rem; border-radius: 999px;
    }

    /* Table */
    .jt-table { margin: 0; }
    .jt-table thead th {
        background: #f8fafc !important; color: #475569 !important;
        font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .5px;
        border-bottom: 1px solid #e5e7eb; padding: .8rem 1rem;
    }
    .jt-table tbody td { padding: .85rem 1rem; vertical-align: middle; color: #0f172a; }
    .jt-table tbody tr { transition: background .15s ease; }
    .jt-table tbody tr:hover { background: #fafbfc; }
    .jt-table tbody tr + tr td { border-top: 1px solid #f1f5f9; }
    .jt-slug-code {
        background: #f1f5f9; color: #475569; font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: .78rem; padding: .15rem .5rem; border-radius: 6px; border: 1px solid #e2e8f0;
    }
    .jt-name-primary { font-weight: 600; color: #0f172a; }
    .jt-name-secondary { color: #64748b; font-size: .85rem; }
    .jt-sort {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 26px; padding: 0 .45rem;
        background: #f1f5f9; color: #475569; border-radius: 6px;
        font-weight: 600; font-size: .8rem; border: 1px solid #e2e8f0;
    }

    /* Status pills */
    .jt-pill { display: inline-flex; align-items: center; gap: .3rem;
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        line-height: 1.3; border: 1px solid transparent; }
    .jt-pill .dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
    .jt-pill.active   { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .jt-pill.active .dot { background: #10b981; }
    .jt-pill.inactive { background: #f3f4f6; color: #6b7280; border-color: #e5e7eb; }
    .jt-pill.inactive .dot { background: #9ca3af; }
    .jt-pill.scope-global { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .jt-pill.scope-school { background: #fffbeb; color: #92400e; border-color: #fde68a; }

    /* Action btn */
    .jt-action-btn {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; border: 1px solid #fecaca; background: #fff5f5;
        color: #b91c1c; transition: all .15s ease;
    }
    .jt-action-btn:hover { background: #fee2e2; border-color: #fca5a5; transform: translateY(-1px); }
    .jt-action-btn:disabled, .jt-action-btn.is-disabled {
        background: #f8fafc; border-color: #e5e7eb; color: #cbd5e1; cursor: not-allowed;
        transform: none;
    }

    /* Empty state */
    .jt-empty { padding: 2.5rem 1rem; text-align: center; color: #94a3b8; }
    .jt-empty i { font-size: 2.25rem; opacity: .55; display: block; margin-bottom: .35rem; color: #cbd5e1; }
    .jt-empty .lbl { font-size: .92rem; color: #64748b; }

    /* Form card */
    .jt-form-card { position: sticky; top: 1rem; }
    .jt-form-card .form-label {
        font-weight: 600; font-size: .82rem; color: #334155;
        margin-bottom: .35rem; display: flex; align-items: center; gap: .35rem;
    }
    .jt-form-card .form-label .req { color: #dc2626; font-weight: 700; }
    .jt-form-card .form-label .hint { color: #94a3b8; font-weight: 400; font-size: .75rem; margin-{{ $isRtl ? 'right' : 'left' }}: auto; }
    .jt-form-card .form-control {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .75rem; font-size: .92rem; color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .jt-form-card .form-control:focus {
        border-color: var(--gold-300);
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16);
        outline: none;
    }
    .jt-form-card .form-control[name="slug"] {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .88rem;
    }
    .jt-toggle {
        display: flex; align-items: center; justify-content: space-between;
        gap: 1rem; padding: .75rem .85rem; border-radius: 10px;
        background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 1rem;
    }
    .jt-toggle .lbl { font-weight: 600; font-size: .88rem; color: #334155; }
    .jt-toggle .hint { font-size: .76rem; color: #64748b; display: block; margin-top: .15rem; }
    .jt-toggle .form-check { padding: 0; min-height: auto; margin: 0; }
    .jt-toggle .form-check-input {
        width: 38px; height: 22px; margin: 0; cursor: pointer;
        background-color: #cbd5e1; border-color: #cbd5e1;
    }
    .jt-toggle .form-check-input:checked {
        background-color: var(--gold-400); border-color: var(--gold-400);
    }

    /* Gold CTA — match login button */
    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        border: 1px solid var(--gold-400); color: #fff;
        font-weight: 600; padding: .6rem 1.2rem; border-radius: 10px;
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
    .btn-gold:disabled { opacity: .6; cursor: not-allowed; transform: none; }

    .jt-alert {
        background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
        border-radius: 10px; padding: .65rem .85rem; display: flex; align-items: center;
        gap: .55rem; font-size: .9rem; margin-bottom: 1rem;
    }
    .jt-alert i { color: #10b981; font-size: 1.1rem; }
    .jt-alert.err { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .jt-alert.err i { color: #ef4444; }

    /* Responsive: stack columns + drop pill labels */
    @media (max-width: 991.98px) {
        .jt-kpis { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .jt-form-card { position: static; margin-top: 1rem; }
    }
    @media (max-width: 575.98px) {
        .jt-kpis { grid-template-columns: 1fr 1fr; gap: .55rem; }
        .jt-kpi { padding: .7rem .8rem; }
        .jt-kpi .ico { width: 32px; height: 32px; font-size: .95rem; }
        .jt-kpi .num { font-size: 1.15rem; }
        .jt-kpi .lbl { font-size: .72rem; }
        .jt-table thead { display: none; }
        .jt-table, .jt-table tbody, .jt-table tr, .jt-table td { display: block; width: 100%; }
        .jt-table tbody tr {
            border: 1px solid #f1f5f9; border-radius: 12px;
            margin-bottom: .65rem; padding: .65rem .8rem; background: #fff;
        }
        .jt-table tbody tr + tr td { border-top: 0; }
        .jt-table tbody td {
            padding: .35rem 0; border: 0; display: flex; align-items: center;
            justify-content: space-between; gap: .75rem; font-size: .9rem;
        }
        .jt-table tbody td::before {
            content: attr(data-label);
            font-size: .72rem; color: #64748b; font-weight: 600;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .jt-table tbody td.actions-cell { justify-content: flex-end; }
        .jt-table tbody td.actions-cell::before { display: none; }
        .jt-surface .card-header { padding: .85rem 1rem; }
    }
</style>
@endpush
