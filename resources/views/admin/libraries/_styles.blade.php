@push('styles')
<style>
    /* ============================================================
       Libraries module — gold/light theme card & form polish
       Bootstrap-4 theme: polyfill BS5 utilities + creative cards
       ============================================================ */

    /* --- BS5 utility polyfills (theme ships BS4) --------------- */
    .lib-scope .form-select {
        display: block;
        width: 100%;
        padding: .4rem 2rem .4rem .75rem;
        font-size: .95rem;
        line-height: 1.5;
        color: #4b4b4b;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23999' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: left .75rem center;
        background-size: 14px 11px;
        border: 1px solid #d9d9e3;
        border-radius: 8px;
        transition: border-color .2s, box-shadow .2s;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    html[dir="rtl"] .lib-scope .form-select {
        padding: .4rem .75rem .4rem 2rem;
        background-position: left .75rem center;
    }
    .lib-scope .form-select[multiple] {
        background-image: none;
        padding: .35rem;
    }
    .lib-scope .form-select-sm { padding-top: .25rem; padding-bottom: .25rem; font-size: .85rem; }
    .lib-scope .form-select:focus {
        border-color: var(--gold-300);
        box-shadow: 0 0 0 .15rem rgba(207,160,70,.18);
        outline: 0;
    }
    .lib-scope .gap-1 { gap: .35rem; }
    .lib-scope .gap-2 { gap: .6rem; }
    .lib-scope .ms-auto { margin-inline-start: auto !important; }
    .lib-scope .ms-3 { margin-inline-start: 1rem !important; }
    .lib-scope .text-md-end { text-align: inherit; }
    @media (min-width: 768px) {
        html[dir="rtl"] .lib-scope .text-md-end { text-align: left !important; }
        html:not([dir="rtl"]) .lib-scope .text-md-end { text-align: right !important; }
    }

    /* --- Form grid spacing ------------------------------------- */
    .lib-scope .form-label {
        font-weight: 600;
        font-size: .9rem;
        color: #333;
        margin-bottom: .35rem;
    }
    .lib-scope .lib-field { margin-bottom: 1.1rem; }
    .lib-scope .form-control { border-radius: 8px; border-color: #d9d9e3; }

    /* --- Tabs styled in gold ----------------------------------- */
    .lib-scope .nav-tabs {
        border-bottom: 2px solid #ece6d8;
        gap: .25rem;
    }
    .lib-scope .nav-tabs .nav-link {
        border: none;
        border-radius: 10px 10px 0 0;
        color: #7a6f55;
        font-weight: 600;
        padding: .6rem 1.15rem;
        transition: all .2s;
    }
    .lib-scope .nav-tabs .nav-link:hover { color: var(--gold-400); background: #faf6ec; }
    .lib-scope .nav-tabs .nav-link.active {
        color: #fff;
        background: linear-gradient(135deg, var(--gold-200), var(--gold-400));
        box-shadow: 0 4px 12px rgba(207,160,70,.28);
    }

    /* --- Search/filter card ------------------------------------ */
    .lib-scope .lib-filter-card .card-header {
        background: linear-gradient(135deg, #fdfaf2, #f7efdc);
        border-bottom: 1px solid #efe6cf;
    }
    .lib-scope .lib-filter-card .card-header h5 { color: var(--gold-500); }

    /* --- Content cards (grid) ---------------------------------- */
    .lib-scope .lib-card {
        border: 1px solid #ece6d8;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 10px rgba(30,25,10,.05);
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s;
    }
    .lib-scope .lib-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 34px rgba(207,160,70,.20);
        border-color: var(--gold-200);
    }
    .lib-scope .lib-card-media {
        height: 150px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f7efdc 0%, #efe2c1 100%);
        overflow: hidden;
    }
    .lib-scope .lib-card-media img {
        width: 100%; height: 100%; object-fit: cover;
    }
    .lib-scope .lib-card-media .lib-icon {
        font-size: 3.2rem;
        color: var(--gold-400);
        opacity: .85;
    }
    .lib-scope .lib-type-chip {
        position: absolute;
        top: 10px;
        inset-inline-start: 10px;
        background: rgba(255,255,255,.92);
        color: var(--gold-500);
        font-size: .72rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 999px;
        box-shadow: 0 2px 6px rgba(0,0,0,.08);
    }
    .lib-scope .lib-card-body { padding: .95rem 1rem .6rem; }
    .lib-scope .lib-card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #2b2b2b;
        margin-bottom: .35rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .lib-scope .lib-card-meta { font-size: .78rem; color: #9a8f78; }
    .lib-scope .lib-card-desc {
        font-size: .82rem; color: #7d7d7d; margin-top: .45rem;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .lib-scope .lib-card-footer {
        display: flex;
        align-items: center;
        gap: .35rem;
        padding: .55rem 1rem .95rem;
    }
    .lib-scope .lib-card-footer .btn { border-radius: 8px; }

    /* --- Labs categories sidebar (gold, not bright blue) ------- */
    .lib-scope .lib-cat-card { border: 1px solid #ece6d8; border-radius: 14px; overflow: hidden; }
    .lib-scope .lib-cat-card .card-header {
        background: linear-gradient(135deg, #fdfaf2, #f7efdc);
        border-bottom: 1px solid #efe6cf;
        color: var(--gold-500);
        font-weight: 700;
    }
    .lib-scope .lib-cat-list .list-group-item { border: none; border-bottom: 1px solid #f2ecdd; padding: .55rem .9rem; }
    .lib-scope .lib-cat-list .list-group-item:last-child { border-bottom: none; }
    .lib-scope .lib-cat-list .list-group-item a { color: #5d5443; text-decoration: none; }
    .lib-scope .lib-cat-list .list-group-item.active {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-400));
        border-color: transparent;
    }
    .lib-scope .lib-cat-list .list-group-item.active a,
    .lib-scope .lib-cat-list .list-group-item.active strong { color: #fff !important; }
    .lib-scope .lib-cat-list .lib-subcat a { color: #9a8f78; font-size: .82rem; }
    .lib-scope .lib-cat-list .lib-subcat a:hover { color: var(--gold-400); }

    /* --- Tables ------------------------------------------------ */
    .lib-scope .table thead th {
        background: #faf6ec;
        color: #6f6450;
        border-bottom: 2px solid #efe6cf;
        font-size: .85rem;
        font-weight: 700;
    }

    /* --- Section heading inside forms -------------------------- */
    .lib-scope .lib-section-title {
        font-weight: 700;
        color: var(--gold-500);
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .3rem;
    }
    .lib-scope .lib-divider { border-top: 1px dashed #e3d8bd; margin: 1.4rem 0; }

    /* --- Empty state ------------------------------------------- */
    .lib-scope .lib-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: #a99e85;
    }
    .lib-scope .lib-empty i { font-size: 3rem; color: #d9cbab; display: block; margin-bottom: .6rem; }
</style>
@endpush
