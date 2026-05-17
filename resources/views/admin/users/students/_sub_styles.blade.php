@push('styles')
<style>
    body.theme-light .student-avatar-large {
        width: 80px; height: 80px; border-radius: 50%;
        background: linear-gradient(135deg, #fff6dd, #fde2a8);
        color: var(--gold-500); display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.8rem; font-weight: 800; overflow: hidden;
    }
    body.theme-light .student-avatar-large img { width:100%; height:100%; object-fit:cover; }
    body.theme-light .student-subnav .nav-pills .nav-link {
        color: #475569; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
        padding: .45rem .85rem; font-size: .85rem; font-weight: 600; display: inline-flex; align-items: center; gap: .35rem;
    }
    body.theme-light .student-subnav .nav-pills .nav-link:hover { background: #fff6dd; color: var(--gold-500); border-color: #fde2a8; }
    body.theme-light .student-subnav .nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)); color: #fff !important; border-color: transparent;
        box-shadow: 0 4px 12px rgba(207,160,70,.25);
    }
    body.theme-light .grade-chip {
        background: #eef2ff; color: #4338ca; padding: .15rem .55rem;
        border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .class-chip {
        background: #ecfdf5; color: #047857; padding: .15rem .55rem;
        border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .status-pill {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
    }
    body.theme-light .status-pill.on { background: #ecfdf5; color: #047857; }
    body.theme-light .status-pill.off { background: #fef2f2; color: #b91c1c; }
    body.theme-light .add-student-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; padding: .5rem 1rem;
        border-radius: 10px; font-weight: 600; box-shadow: 0 4px 14px rgba(207,160,70,.25);
    }
    body.theme-light .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; padding: .5rem .9rem; font-weight: 500;
    }
    body.theme-light .info-grid { display: grid; gap: .85rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    body.theme-light .info-grid .field { background: #f8fafc; padding: .75rem .85rem; border-radius: 10px; border: 1px solid #f1f5f9; }
    body.theme-light .info-grid .field .label { font-size: .72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .3px; margin-bottom: .3rem; }
    body.theme-light .info-grid .field .value { font-size: .95rem; font-weight: 600; color: #0f172a; }
    body.theme-light .section-title {
        display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem;
        font-size: 1rem; font-weight: 700; color: #0f172a;
    }
    body.theme-light .section-title .icon-wrap {
        width: 32px; height: 32px; border-radius: 8px;
        background: linear-gradient(135deg, #fff6dd, #fde8ad); color: var(--gold-500);
        display: inline-flex; align-items: center; justify-content: center;
    }
    body.theme-light .empty-state { padding: 3rem 1rem; text-align: center; }
    body.theme-light .empty-state .icon-wrap {
        width: 64px; height: 64px; border-radius: 16px; margin: 0 auto 1rem;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 1.6rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    body.theme-light .empty-state h4 { color: #0f172a; font-weight: 700; margin-bottom: .35rem; }
    body.theme-light .empty-state p { color: #64748b; margin-bottom: 0; }
</style>
@endpush
