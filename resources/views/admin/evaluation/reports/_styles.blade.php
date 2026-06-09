{{-- Shared gold-theme KPI + filter styling for evaluation reports (Sprint 8 Tasks 19-20). --}}
<style>
    body.theme-light .ev-kpis .card { padding: .85rem 1rem; }
    body.theme-light .ev-kpis .label { color:#64748b; font-weight:600; font-size:.72rem; letter-spacing:.3px; text-transform:uppercase; margin-bottom:.3rem; }
    body.theme-light .ev-kpis .value { font-size:1.4rem; font-weight:800; color:var(--gold-400); line-height:1.05; }
    body.theme-light .ev-kpis .value.small { font-size:1rem; }
    body.theme-light .ev-kpis .icon { width:38px; height:38px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.1rem; }
    body.theme-light .ev-add-btn { background:linear-gradient(135deg,var(--gold-200),var(--gold-500))!important; color:#fff!important; border:none; padding:.5rem .95rem; border-radius:10px; font-weight:600; box-shadow:0 4px 14px rgba(207,160,70,.25); }
    body.theme-light .ev-add-btn:hover { transform:translateY(-1px); }
    body.theme-light .filters-card .form-label { font-size:.74rem; color:#64748b; font-weight:600; margin-bottom:.2rem; }
    body.theme-light .filters-card .form-control, body.theme-light .filters-card select { border-radius:10px; border:1px solid #e5e7eb; font-size:.85rem; padding:.4rem .65rem; }
    body.theme-light .ev-pill { display:inline-flex; align-items:center; gap:.3rem; padding:.15rem .55rem; border-radius:999px; font-size:.7rem; font-weight:600; }
    body.theme-light .ev-pill.draft, body.theme-light .ev-pill.needs_review { background:#f1f5f9; color:#475569; }
    body.theme-light .ev-pill.completed { background:#ecfdf5; color:#047857; }
    body.theme-light .ev-pill.approved, body.theme-light .ev-pill.locked { background:#e0f2fe; color:#0369a1; }
    body.theme-light .ev-pill.pending_approval { background:#fffbeb; color:#b45309; }
    body.theme-light .ev-pill.rejected { background:#fef2f2; color:#b91c1c; }
    body.theme-light .ev-empty { padding:3.5rem 1rem; text-align:center; }
    body.theme-light .ev-empty .icon-wrap { width:72px; height:72px; border-radius:18px; margin:0 auto 1rem; background:linear-gradient(135deg,#fff6dd,#fde8ad); color:var(--gold-500); font-size:1.8rem; display:inline-flex; align-items:center; justify-content:center; }
    body.theme-light .report-table td, body.theme-light .report-table th { white-space:nowrap; font-size:.85rem; }
    body.theme-light .bool-yes { color:#047857; font-weight:700; }
    body.theme-light .bool-no { color:#94a3b8; }
    @media print {
        .no-print, .filters-card, .breadcrumb-wrapper, .content-header-right, .pagination { display:none !important; }
        body.theme-light .ev-kpis .card, .card { box-shadow:none !important; border:1px solid #e5e7eb !important; }
        a[href]:after { content:""; }
    }
</style>
