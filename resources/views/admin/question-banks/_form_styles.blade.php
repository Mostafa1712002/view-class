<style>
    /* ===== Question Bank form — light + gold (card 62) ============= */
    .qb-header { margin-bottom: 1.25rem; }
    .qb-header h2 {
        font-size: 1.5rem; font-weight: 700; color: #0f172a;
        margin-bottom: .15rem; letter-spacing: -.2px;
    }
    .qb-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .qb-header .breadcrumb-item + .breadcrumb-item::before { color: #cbd5e1; }

    .qb-form-wrap { max-width: 1080px; margin: 0 auto; }

    .qb-form-section {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: 1.25rem 1.35rem; margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
    }
    .qb-form-section__title {
        font-size: .95rem; font-weight: 700; color: #0f172a;
        margin-bottom: 1rem; padding-bottom: .55rem;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
    }
    .qb-form-section__title::before {
        content: ''; position: absolute; bottom: -1px; inset-inline-start: 0;
        width: 36px; height: 2px; background: var(--gold-400, #cfa046); border-radius: 2px;
    }

    .qb-form-section .form-label {
        font-weight: 600; color: #334155; font-size: .85rem; margin-bottom: .35rem;
    }
    .qb-form-section .form-control, .qb-form-section .form-select {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .55rem .85rem; font-size: .9rem; color: #0f172a;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .qb-form-section .form-control:focus, .qb-form-section .form-select:focus {
        border-color: var(--gold-300, #e3c285);
        box-shadow: 0 0 0 .2rem rgba(207,160,70,.16); outline: none;
    }
    .qb-form-section textarea.form-control { resize: vertical; min-height: 80px; }

    .qb-subject-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: .5rem;
    }
    .qb-subject-chip {
        display: flex; align-items: center; gap: .5rem; cursor: pointer;
        padding: .5rem .75rem; border: 1px solid #e2e8f0; border-radius: 10px;
        background: #fff; transition: all .15s ease; font-size: .88rem;
        margin: 0;
    }
    .qb-subject-chip:hover { background: #fffbeb; border-color: #fde68a; }
    .qb-subject-chip input[type=checkbox] { accent-color: var(--gold-500, #c8941f); }
    .qb-subject-chip input[type=checkbox]:checked + span { color: #92400e; font-weight: 600; }

    .qb-teacher-row {
        display: flex; align-items: center; gap: .5rem;
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: .4rem .65rem;
    }
    .qb-teacher-name { flex: 1; font-size: .85rem; color: #0f172a; }
    .qb-teacher-row .form-select { max-width: 160px; }

    .qb-toggle {
        display: inline-flex; align-items: center; gap: .55rem; cursor: pointer;
        padding: .55rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
        background: #f8fafc; margin: 0; font-weight: 500; color: #334155;
    }
    .qb-toggle:hover { border-color: #fde68a; background: #fffbeb; }
    .qb-toggle input[type=checkbox] { accent-color: var(--gold-500, #c8941f); }

    .qb-form-actions {
        display: flex; gap: .55rem; justify-content: flex-end; align-items: center;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        padding: .85rem 1.1rem; margin-top: 1rem;
        box-shadow: 0 1px 2px rgba(15,23,42,.04);
    }

    .btn-gold {
        background: linear-gradient(135deg, var(--gold-300, #e3c285), var(--gold-500, #c8941f));
        border: 1px solid var(--gold-400, #cfa046); color: #fff;
        font-weight: 600; padding: .6rem 1.25rem; border-radius: 10px;
        box-shadow: 0 1px 2px rgba(207,160,70,.18);
        transition: transform .15s ease, box-shadow .2s ease;
        display: inline-flex; align-items: center; gap: .45rem;
    }
    .btn-gold:hover {
        color: #fff; transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(207,160,70,.22);
    }
    .btn-reset {
        background: #fff; border: 1px solid #e2e8f0; color: #475569;
        font-weight: 600; padding: .55rem 1.1rem; border-radius: 10px;
        text-decoration: none; transition: all .15s ease;
    }
    .btn-reset:hover { background: #f8fafc; color: #0f172a; }

    @media (max-width: 575.98px) {
        .qb-form-section { padding: 1rem; }
        .qb-teacher-row { flex-direction: column; align-items: stretch; gap: .35rem; }
        .qb-teacher-row .form-select { max-width: none; }
    }
</style>
