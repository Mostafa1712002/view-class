@php
    $sidebarUser = auth()->user();
    $isStaff = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin());

    // ── Dynamic student subjects (Card #170) ─────────────────────────────────
    // Subjects come from the subjects linked to the student's grade + class
    // (NOT a fixed list), school-scoped, deduped by (name + track). Mirrors
    // StudentSubjectController::studentSubjects().
    $sidebarStudentSubjects = collect();
    if ($sidebarUser && $sidebarUser->isStudent()) {
        try {
            $gradeLevel = optional($sidebarUser->classRoom)->grade_level;
            $q = \App\Models\Subject::where('school_id', $sidebarUser->school_id)
                ->where('is_active', true);
            if ($gradeLevel !== null) {
                $q->where(function ($w) use ($gradeLevel) {
                    $w->whereJsonContains('grade_levels', (string) $gradeLevel)
                        ->orWhereJsonContains('grade_levels', (int) $gradeLevel);
                });
            }
            $sidebarStudentSubjects = $q->orderBy('certificate_order')->orderBy('name')->get()
                // Collapse exact duplicates (same name + same code); keep genuinely
                // distinct subjects (different code) as separate rows.
                ->unique(fn ($s) => mb_strtolower(trim($s->name)).'|'.($s->code ?? ''))
                ->values();
        } catch (\Throwable $e) {
            $sidebarStudentSubjects = collect();
        }
    }
@endphp
@php
    // Module visibility — respects canViewModule() default-allow-when-unconfigured rule.
    // Only hides items when the user's job-title has explicit permissions configured
    // AND the specific .view permission is absent. Non-configured users see everything.
    $canViewStudents      = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('students');
    $canViewParents       = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('parents');
    $canViewTeachers      = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('teachers');
    $canViewSubjects      = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('subjects');
    $canViewQuestionBanks = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('question_banks');
    $canViewExams         = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('exams');
    $canViewAssignments   = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('assignments');
    $canViewBooks         = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('books');
    $canViewLibraries     = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('libraries');
    $canViewReports       = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('reports');
    $canViewNoor          = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('noor');
    $canViewJobTitles     = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('job_titles');

    // عمليات التواصل (Sprint 9) — same default-allow formula: non-staff (student/parent)
    // are never hidden; configured staff see only modules they hold the .view permission for.
    $canViewAnnouncements = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('announcements');
    $canViewCalendar      = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('calendar');
    $canViewVirtualClasses= !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('virtual_classes');
    $canViewDiscussion    = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('discussion');
    $canViewMailbox       = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('mailbox');
    $canViewSms           = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('sms');
    $canViewWhatsapp      = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('whatsapp');
    $canViewParentsContact= !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('parents_contact');

    // غياب الطلاب (Card #275) — same default-allow formula. The whole attendance
    // dropdown is gated by attendance.view; the three siblings below it (teacher
    // absence / certificates / educational sites) each gate by their own module.
    $canViewAttendance        = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('attendance');
    $canViewTeacherAttendance = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('teacher_attendance');
    $canViewCertificates      = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('certificates');
    $canViewEducationalSites  = !$sidebarUser || !$isStaff || $sidebarUser->canViewModule('educational_sites');
@endphp

{{-- ===== GP Sidebar v3 — navy brand surface, gold active, unified section system ===== --}}
<style>
/* ══════════════════════════════════════════════════════════════════
   ViewClass Sidebar v3 — "المنصة الذهبية"
   Deep-navy brand surface + gold accents. Light icons for high
   contrast & clarity (QA #221). Larger readable nav text. One
   coherent section system replaces the prior 4-colour scheme.
   Tokens: --vc-sb-* are local so the picker-driven html font-size
   still scales rem-based text.
   ══════════════════════════════════════════════════════════════════ */
.main-menu {
    --vc-sb-bg-1:   #16263f;   /* navy top */
    --vc-sb-bg-2:   #0f1c30;   /* navy bottom */
    --vc-sb-text:   #e8edf4;   /* primary nav text */
    --vc-sb-dim:    #9fb0c7;   /* secondary / submenu text */
    --vc-sb-hover:  rgba(255,255,255,.07);
    --vc-sb-icon:   #c8d4e4;   /* resting icon — bright on navy */
    --vc-gold:      #d8b24a;   /* accent on dark (lifted for contrast) */
    --vc-gold-soft: #f0d589;
    /* Clear, Arabic-capable font for the whole sidebar (QA #276). The theme's
       components.css forces "Quicksand" on .menu-title — a Latin-only face, so
       Arabic fell back to a serif (Times) and read blurry. Use the same system
       sans stack the page body already uses, which renders Arabic crisply. */
    --vc-sb-font: "Segoe UI", "Cairo", "Tajawal", "Noto Sans Arabic", system-ui,
                  -apple-system, "Helvetica Neue", Arial, sans-serif;
}
/* Apply the clear font across every text surface inside the sidebar, beating
   the theme's Quicksand→serif rule (same specificity, declared later = wins). */
.main-menu,
.main-menu .navigation li.nav-item > a,
.main-menu .navigation li.nav-item > a .menu-title,
.main-menu .navigation li ul.menu-content li a,
.main-menu .navigation li ul.menu-content li a .menu-item,
.main-menu .gp-section-header,
.main-menu .gp-section-header .gp-sec-label,
.main-menu .brand-text,
.main-menu [class*="brand"] {
    font-family: var(--vc-sb-font) !important;
}

/* ── Icon baseline ── */
.vc-ico {
    display: inline-block;
    vertical-align: -0.18em;
    flex-shrink: 0;
    transition: color .18s ease, transform .18s ease;
}

/* ══════════════════════════════════════════
   CORE SURFACE
   ══════════════════════════════════════════ */
.main-menu {
    background: linear-gradient(177deg, var(--vc-sb-bg-1) 0%, var(--vc-sb-bg-2) 100%) !important;
    border-inline-end: 1px solid rgba(216,178,74,.18);
    box-shadow: 2px 0 24px rgba(8,15,28,.28);
    transition: width .28s cubic-bezier(.16,1,.3,1);
    will-change: width;
}
.main-menu .main-menu-content { background: transparent !important; padding-bottom: 18px; }
/* The theme paints .navigation / .navigation-main white; clear it so the
   navy gradient shows for the full (scrollable) menu height, not just the
   first viewport. Without this the lower items sit on a white slab. */
.main-menu .navigation,
.main-menu .navigation-main,
.main-menu ul.navigation-main { background: transparent !important; }

/* scrollbar */
.main-menu-content::-webkit-scrollbar { width: 5px; }
.main-menu-content::-webkit-scrollbar-track { background: transparent; }
.main-menu-content::-webkit-scrollbar-thumb { background: rgba(216,178,74,.45); border-radius: 6px; }
.main-menu-content::-webkit-scrollbar-thumb:hover { background: rgba(216,178,74,.7); }

/* ── Sidebar header (mini toggle row) ── */
#gp-sidebar-header {
    display: flex; align-items: center; justify-content: flex-end;
    padding: 12px 14px 8px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    margin-bottom: 6px;
}
#gp-sidebar-toggle {
    display: flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 9px; cursor: pointer;
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.10);
    color: var(--vc-sb-dim);
    transition: background .18s, color .18s, border-color .18s;
    flex-shrink: 0;
}
#gp-sidebar-toggle:hover { background: rgba(216,178,74,.16); border-color: var(--vc-gold); color: var(--vc-gold-soft); }

/* ══════════════════════════════════════════
   SECTION HEADERS — one unified gold-rule system
   (replaces purple/blue/orange/green clash)
   ══════════════════════════════════════════ */
.gp-section-header {
    display: flex; align-items: center; gap: 9px;
    padding: 7px 18px 7px;
    margin: 16px 12px 4px;
    font-size: .72rem; font-weight: 700; letter-spacing: .6px;
    color: var(--vc-gold);
    text-transform: none;
    cursor: pointer; user-select: none;
    border-radius: 8px;
    position: relative;
    transition: background .15s, color .15s;
}
/* thin gold separator above each section header */
.gp-section-header::before {
    content: ""; position: absolute;
    top: -9px; inset-inline: 18px;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(216,178,74,.32) 18%, rgba(216,178,74,.32) 82%, transparent);
}
.gp-section-header:first-of-type::before { display: none; }
.gp-section-header .gp-sec-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 6px;
    background: rgba(216,178,74,.14);
    color: var(--vc-gold-soft);
}
.gp-section-header .gp-sec-label { flex: 1 1 auto; }
.gp-section-header .gp-sec-chevron {
    margin-inline-start: auto;
    transition: transform .22s cubic-bezier(.16,1,.3,1);
    opacity: .6;
}
.gp-section-header.collapsed .gp-sec-chevron { transform: rotate(90deg); }
.gp-section-header:hover { background: rgba(255,255,255,.05); color: var(--vc-gold-soft); }
/* all sec-* variants now share the unified gold treatment */
.gp-section-header.sec-programs,
.gp-section-header.sec-educational,
.gp-section-header.sec-communication,
.gp-section-header.sec-system,
.gp-section-header.sec-teacher,
.gp-section-header.sec-student,
.gp-section-header.sec-parent { color: var(--vc-gold); }

.gp-section-content {
    overflow: hidden;
    transition: max-height .28s cubic-bezier(.16,1,.3,1), opacity .22s ease;
    max-height: 9999px;
    opacity: 1;
}
.gp-section-content.gp-collapsed { max-height: 0 !important; opacity: 0; }

/* ══════════════════════════════════════════
   NAV ITEMS
   (target li.nav-item so grandchildren inside
   .gp-section-content stay styled — role sidebars)
   ══════════════════════════════════════════ */
.main-menu .navigation li.nav-item > a {
    display: flex; align-items: center; gap: 12px;
    padding: .64rem .9rem; border-radius: 10px;
    margin: 2px 12px;
    font-size: 1.08rem; font-weight: 600;         /* larger, clearer (QA #276) */
    color: var(--vc-sb-text);
    position: relative;
    transition: background .16s, color .16s, box-shadow .16s, transform .16s;
    text-decoration: none;
}
/* Let the label grow so a trailing dropdown chevron parks at the edge and can
   never sit on top of the text (QA #276). */
.main-menu .navigation li.nav-item > a .menu-title { letter-spacing: .1px; flex: 1 1 auto; min-width: 0; }
.main-menu .navigation li.nav-item > a .vc-ico { color: var(--vc-sb-icon); width: 20px; height: 20px; }

.main-menu .navigation li.nav-item > a:hover {
    background: var(--vc-sb-hover);
    color: #ffffff;
}
/* Keyboard focus ring (a11y) — only on keyboard nav, not mouse clicks */
.main-menu .navigation li.nav-item > a:focus-visible,
.main-menu .navigation li ul.menu-content li a:focus-visible {
    outline: 2px solid var(--vc-gold-soft);
    outline-offset: -2px;
}
.main-menu .navigation li.nav-item > a:hover .vc-ico {
    color: var(--vc-gold-soft);
    transform: scale(1.1);
}

/* Active item — solid gold pill + navy text + glow */
.main-menu .navigation li.nav-item.active > a {
    background: linear-gradient(100deg, var(--vc-gold) 0%, #c79a32 100%) !important;
    color: #14233a !important;
    font-weight: 700 !important;
    box-shadow: 0 6px 18px rgba(216,178,74,.34), 0 1px 0 rgba(255,255,255,.25) inset !important;
}
.main-menu .navigation li.nav-item.active > a .vc-ico { color: #14233a !important; transform: none; }

/* has-sub open (parent of an open submenu, not itself active) */
.main-menu .navigation li.nav-item.has-sub.open:not(.active) > a {
    background: rgba(216,178,74,.10);
    color: #ffffff;
}
.main-menu .navigation li.nav-item.has-sub.open:not(.active) > a .vc-ico { color: var(--vc-gold-soft); }

/* ── Submenu (collapsible dropdown — QA #230) ──
   Children are hidden by default; the parent .has-sub item is a real
   click-to-toggle dropdown. JS animates exact heights; these rules give the
   correct first-paint state (collapsed unless server marks the parent .open)
   so there's no flash before the script runs. */
.main-menu .navigation li.has-sub > ul.menu-content {
    overflow: hidden;
    margin: 0 12px;
    padding: 0;
    border-radius: 10px;
    background: rgba(0,0,0,.22);
    max-height: 0;
    opacity: 0;
    transition: max-height .26s cubic-bezier(.16,1,.3,1), opacity .2s ease, margin .22s ease, padding .22s ease;
}
/* Open state — server-rendered (.open) shows immediately; JS keeps it in sync */
.main-menu .navigation li.has-sub.open > ul.menu-content {
    max-height: 1200px;
    opacity: 1;
    margin: 3px 12px 6px;
    padding: 5px 0;
}
.main-menu .navigation li ul.menu-content li a {
    display: flex; align-items: center; gap: 9px;
    padding: .46rem .85rem .46rem 2.35rem;
    font-size: .96rem; border-radius: 8px;
    margin: 1px 8px;
    color: var(--vc-sb-dim);
    position: relative;
    transition: background .14s, color .14s, padding .14s;
}
html[dir="rtl"] .main-menu .navigation li ul.menu-content li a {
    padding: .44rem 2.35rem .44rem .85rem;
}
/* nesting guide dot — makes child rows read as a clear third level */
.main-menu .navigation li ul.menu-content li a::before {
    content: "";
    position: absolute;
    top: 50%; transform: translateY(-50%);
    inset-inline-start: 1.25rem;
    width: 5px; height: 5px; border-radius: 50%;
    background: currentColor; opacity: .35;
    transition: opacity .14s, transform .14s;
}
html[dir="ltr"] .main-menu .navigation li ul.menu-content li a::before {
    inset-inline-start: auto; inset-inline-end: 1.25rem;
}
.main-menu .navigation li ul.menu-content li a:hover::before { opacity: .7; transform: translateY(-50%) scale(1.25); }
.main-menu .navigation li ul.menu-content li.active > a::before { opacity: 1; background: var(--vc-gold-soft); transform: translateY(-50%) scale(1.3); }
.main-menu .navigation li ul.menu-content li a .vc-ico { color: var(--vc-sb-dim); width: 16px; height: 16px; }
.main-menu .navigation li ul.menu-content li a:hover { background: rgba(255,255,255,.06); color: #fff; }
.main-menu .navigation li ul.menu-content li a:hover .vc-ico { color: var(--vc-gold-soft); }
.main-menu .navigation li ul.menu-content li.active > a {
    color: var(--vc-gold-soft); font-weight: 700;
    background: rgba(216,178,74,.16);
}
.main-menu .navigation li ul.menu-content li.active > a .vc-ico { color: var(--vc-gold-soft); }

/* has-sub parent — reads as a group toggle. The bold weight plus a faint
   persistent tint and the trailing chevron make "this expands" obvious at a
   glance (vs plain links). Active/open backgrounds come from the !important
   rules above and win over the resting tint. */
.main-menu .navigation li.nav-item.has-sub > a { font-weight: 600; }
.main-menu .navigation li.nav-item.has-sub:not(.active):not(.open) > a {
    background: rgba(255,255,255,.03);
}

/* Kill the theme's LineAwesome ::after glyph (QA #276) — the theme injects
   a  character via position:absolute on li.has-sub > a::after which
   overlaps the link text. Our SVG .vc-sub-caret replaces it. */
.main-menu .navigation li.has-sub > a::after { content: none !important; }

/* has-sub dropdown chevron (QA #276) — a real SVG icon injected as a flex
   child (.vc-sub-caret) at the trailing edge, NOT an absolute ::after. Because
   it's a sibling of the (flex:1) label, it parks at the edge and can never sit
   on top of the text. Replaces the old bordered ::after caret. */
.main-menu .navigation li.has-sub > a .vc-sub-caret {
    margin-inline-start: auto;          /* push to the trailing edge */
    flex: 0 0 auto;
    display: inline-flex; align-items: center; justify-content: center;
    width: 18px; height: 18px;
    color: var(--vc-gold-soft);
    opacity: .7;
    transition: transform .22s cubic-bezier(.16,1,.3,1), opacity .2s;
}
.main-menu .navigation li.has-sub > a .vc-sub-caret svg { width: 13px; height: 13px; display: block; }
.main-menu .navigation li.has-sub > a:hover .vc-sub-caret { opacity: 1; }
.main-menu .navigation li.has-sub.open > a .vc-sub-caret { transform: rotate(180deg); opacity: 1; }
/* active (gold pill) parent — keep the caret legible on gold */
.main-menu .navigation li.has-sub.active > a .vc-sub-caret { color: #14233a; opacity: .85; }

/* ── menu-title should not truncate ── */
.main-menu .navigation li.nav-item > a .menu-title,
.main-menu .navigation li ul.menu-content li a .menu-item {
    white-space: normal !important; overflow: visible !important; text-overflow: unset !important;
    line-height: 1.35;
}

/* ══════════════════════════════════════════
   COLLAPSED / MINI RAIL
   Two collapse paths share this look:
   • body.sidebar-mini   — our own mini toggle
   • body.menu-collapsed — the theme's app-menu.js desktop collapse
   Both must hide labels/chevrons and centre the icons; otherwise the
   full-size labels overflow the 72px rail and sit cramped on top of the
   icons (QA #276 — "حسن السايدبر"). The icon-only rail width itself comes
   from the theme rule for .menu-collapsed; we only fix the contents here.
   ══════════════════════════════════════════ */
body.sidebar-mini .main-menu { width: 72px !important; overflow: visible !important; }
body.sidebar-mini .main-menu-content,
body.menu-collapsed .main-menu-content { overflow: visible !important; }
body.sidebar-mini .main-menu .navigation li.nav-item > a,
body.menu-collapsed .main-menu .navigation li.nav-item > a { padding: .6rem 0 !important; justify-content: center; margin: 3px 8px; gap: 0; }
body.sidebar-mini .main-menu .navigation li.nav-item > a .menu-title,
body.menu-collapsed .main-menu .navigation li.nav-item > a .menu-title { display: none !important; }
body.sidebar-mini .main-menu .navigation li.has-sub > a .vc-sub-caret,
body.menu-collapsed .main-menu .navigation li.has-sub > a .vc-sub-caret { display: none !important; }
body.sidebar-mini .main-menu .navigation li.nav-item > a .vc-ico,
body.menu-collapsed .main-menu .navigation li.nav-item > a .vc-ico { width: 22px; height: 22px; }
body.sidebar-mini .gp-section-header,
body.menu-collapsed .gp-section-header { padding: 8px 6px; justify-content: center; margin: 10px 12px 2px; }
body.sidebar-mini .gp-section-header::before,
body.menu-collapsed .gp-section-header::before { inset-inline: 12px; top: -5px; }
body.sidebar-mini .gp-section-header .gp-sec-label,
body.sidebar-mini .gp-section-header .gp-sec-chevron,
body.menu-collapsed .gp-section-header .gp-sec-label,
body.menu-collapsed .gp-section-header .gp-sec-chevron { display: none !important; }
body.sidebar-mini .gp-section-header .gp-sec-icon,
body.menu-collapsed .gp-section-header .gp-sec-icon { margin: 0 auto; }
body.sidebar-mini .main-menu .navigation li ul.menu-content,
body.menu-collapsed .main-menu .navigation li ul.menu-content { display: none !important; }
body.sidebar-mini .app-content { margin-inline-start: 72px !important; }

/* Tooltip in mini mode */
body.sidebar-mini .main-menu .navigation li.nav-item { position: relative; }
body.sidebar-mini .main-menu .navigation li.nav-item > a::before {
    content: attr(data-label);
    position: absolute;
    inset-inline-end: calc(100% + 12px);
    inset-inline-start: auto;
    top: 50%; transform: translateY(-50%);
    background: #0b1422; color: #fff;
    padding: 6px 11px; border-radius: 8px;
    font-size: .82rem; font-weight: 600; white-space: nowrap;
    box-shadow: 0 6px 18px rgba(0,0,0,.32);
    border: 1px solid rgba(216,178,74,.3);
    opacity: 0; pointer-events: none;
    transition: opacity .18s;
    z-index: 2000;
}
html[dir="ltr"] body.sidebar-mini .main-menu .navigation li.nav-item > a::before {
    inset-inline-end: auto; inset-inline-start: calc(100% + 12px);
}
body.sidebar-mini .main-menu .navigation li.nav-item:hover > a::before { opacity: 1; }

/* ══════════════════════════════════════════
   MOBILE DRAWER  (body.gp-drawer-open)
   ══════════════════════════════════════════ */
@media (max-width: 767.98px) {
    .main-menu {
        position: fixed !important;
        inset-block: 0;
        inset-inline-start: -284px;
        width: 284px !important;
        z-index: 1050;
        transition: inset-inline-start .27s cubic-bezier(.16,1,.3,1), width .27s;
    }
    body.gp-drawer-open .main-menu {
        inset-inline-start: 0 !important;
        box-shadow: 4px 0 40px rgba(8,15,28,.5);
    }
    .gp-drawer-overlay {
        display: none; position: fixed; inset: 0; background: rgba(8,15,28,.55);
        z-index: 1040; backdrop-filter: blur(2px);
    }
    body.gp-drawer-open .gp-drawer-overlay { display: block; }
    body.gp-drawer-open { overflow: hidden; }
    .app-content { margin-inline-start: 0 !important; }
    body.sidebar-mini .app-content { margin-inline-start: 0 !important; }
}
@media (min-width: 768px) {
    .gp-drawer-overlay { display: none !important; }
}

/* ══════════════════════════════════════════
   Respect reduced-motion (task req)
   ══════════════════════════════════════════ */
@media (prefers-reduced-motion: reduce) {
    .main-menu,
    .gp-section-content,
    .main-menu .navigation li ul.menu-content,
    .main-menu .navigation li.nav-item > a,
    .main-menu .navigation li.nav-item > a .vc-ico,
    .gp-section-header .gp-sec-chevron,
    .main-menu .navigation li.has-sub > a .vc-sub-caret {
        transition: none !important;
    }
    .main-menu .navigation li.nav-item > a:hover .vc-ico { transform: none !important; }
}
</style>

{{-- Mobile overlay (click to close drawer) --}}
<div class="gp-drawer-overlay" id="gp-drawer-overlay"></div>

<div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true">

    {{-- Sidebar header: mini-toggle button --}}
    <div id="gp-sidebar-header">
        <button id="gp-sidebar-toggle" title="تصغير/توسيع القائمة" aria-label="تبديل حجم القائمة">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M4.5 11.5A.5.5 0 0 1 5 11h10a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm-2-4A.5.5 0 0 1 3 7h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm-2-4A.5.5 0 0 1 1 3h10a.5.5 0 0 1 0 1H1a.5.5 0 0 1-.5-.5z"/>
            </svg>
        </button>
    </div>

    <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">

            {{-- Dashboard — role-aware target so the active state matches the page
                 students/parents actually land on (they never see /dashboard; it
                 redirects). Avoids a dead/duplicate dashboard entry per role. --}}
            @php
                if ($sidebarUser && $sidebarUser->isStudent() && Route::has('student.dashboard')) {
                    $dashHref = route('student.dashboard');
                    $dashActive = request()->routeIs('student.dashboard');
                } elseif ($sidebarUser && $sidebarUser->isParent() && Route::has('parent.dashboard')) {
                    $dashHref = route('parent.dashboard');
                    $dashActive = request()->routeIs('parent.dashboard');
                } else {
                    $dashHref = route('dashboard');
                    $dashActive = request()->routeIs('dashboard');
                }
            @endphp
            <li class="nav-item {{ $dashActive ? 'active' : '' }}"
                data-label="@lang('shell.nav_dashboard')">
                <a href="{{ $dashHref }}" data-label="@lang('shell.nav_dashboard')">
                    <x-svg-icon name="house" class="vc-ico" />
                    <span class="menu-title">@lang('shell.nav_dashboard')</span>
                </a>
            </li>

            @if($isStaff)

            {{-- ========== 1. برامج نوعية ========== --}}
            <div class="gp-section-header sec-programs" data-section-toggle="programs">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.section_programs')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-programs">
                <li class="nav-item" data-section="programs" data-label="@lang('shell.nav_ana_wa_qadarat')"><a href="#" data-label="@lang('shell.nav_ana_wa_qadarat')"><x-svg-icon name="lightbulb" class="vc-ico" /><span class="menu-title">@lang('shell.nav_ana_wa_qadarat')</span></a></li>
                <li class="nav-item" data-section="programs" data-label="@lang('shell.nav_alawwal')"><a href="#" data-label="@lang('shell.nav_alawwal')"><x-svg-icon name="flag" class="vc-ico" /><span class="menu-title">@lang('shell.nav_alawwal')</span></a></li>
                <li class="nav-item" data-section="programs" data-label="@lang('shell.nav_speed_reading')"><a href="#" data-label="@lang('shell.nav_speed_reading')"><x-svg-icon name="book" class="vc-ico" /><span class="menu-title">@lang('shell.nav_speed_reading')</span></a></li>
            </div>

            {{-- ========== 2. عمليات تعليمية ========== --}}
            <div class="gp-section-header sec-educational" data-section-toggle="educational">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917l-7.5-3.5z"/><path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466 4.176 9.032z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.section_educational')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-educational">

                {{-- إدارة المواد --}}
                <li class="nav-item has-sub {{ (request()->routeIs('admin.subjects.*') || request()->routeIs('admin.subject-tracks.*') || request()->routeIs('admin.question-banks.*') || request()->routeIs('admin.exams.*') || request()->routeIs('admin.lessons.*') || request()->routeIs('manage.books.*')) ? 'active open' : '' }}" data-section="educational">
                    <a href="#" data-label="@lang('shell.nav_exams_management')"><x-svg-icon name="book" class="vc-ico" /><span class="menu-title">@lang('shell.nav_exams_management')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.subjects.index') || request()->routeIs('admin.subjects.create') || request()->routeIs('admin.subjects.edit') ? 'active' : '' }}"><a href="{{ Route::has('admin.subjects.index') ? route('admin.subjects.index') : '#' }}"><x-svg-icon name="book" class="vc-ico" /><span class="menu-item">@lang('shell.nav_subjects')</span></a></li>
                        <li class="{{ request()->routeIs('admin.subject-tracks.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.subject-tracks.index') ? route('admin.subject-tracks.index') : '#' }}"><x-svg-icon name="layout-text-sidebar" class="vc-ico" /><span class="menu-item">@lang('subject_tracks.page_title')</span></a></li>
                        @if($canViewQuestionBanks)<li class="{{ request()->routeIs('admin.question-banks.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.question-banks.index') ? route('admin.question-banks.index') : '#' }}"><x-svg-icon name="question-circle" class="vc-ico" /><span class="menu-item">@lang('shell.nav_questions_bank')</span></a></li>@endif
                        {{-- #248 طبقة التصنيفات التعليمية — gated by Route::has + canViewModule --}}
                        @if(Route::has('admin.qb.skills.index') && (!$sidebarUser || !$isStaff || $sidebarUser->canViewModule('skills')))<li class="{{ request()->routeIs('admin.qb.skills.*') ? 'active' : '' }}"><a href="{{ route('admin.qb.skills.index') }}"><x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-item">المهارات</span></a></li>@endif
                        @if(Route::has('admin.qb.weeks.index') && (!$sidebarUser || !$isStaff || $sidebarUser->canViewModule('weeks')))<li class="{{ request()->routeIs('admin.qb.weeks.*') ? 'active' : '' }}"><a href="{{ route('admin.qb.weeks.index') }}"><x-svg-icon name="calendar-week" class="vc-ico" /><span class="menu-item">الأسابيع الدراسية</span></a></li>@endif
                        {{-- المعايير + المجمعات بيانات مرجعية عامة (super-admin only route) → أظهرها لمدير النظام فقط --}}
                        @if(Route::has('admin.qb.standards.index') && (!$sidebarUser || $sidebarUser->isSuperAdmin()) && (!$sidebarUser || $sidebarUser->canViewModule('standards')))<li class="{{ request()->routeIs('admin.qb.standards.*') ? 'active' : '' }}"><a href="{{ route('admin.qb.standards.index') }}"><x-svg-icon name="bookmark-star" class="vc-ico" /><span class="menu-item">المعايير</span></a></li>@endif
                        @if(Route::has('admin.qb.compounds.index') && (!$sidebarUser || $sidebarUser->isSuperAdmin()) && (!$sidebarUser || $sidebarUser->canViewModule('compounds')))<li class="{{ request()->routeIs('admin.qb.compounds.*') ? 'active' : '' }}"><a href="{{ route('admin.qb.compounds.index') }}"><x-svg-icon name="diagram-3" class="vc-ico" /><span class="menu-item">المجمعات</span></a></li>@endif
                        @if($canViewExams)<li class="{{ request()->routeIs('admin.exams.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.exams.index') ? route('admin.exams.index') : '#' }}"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-item">@lang('shell.nav_exam_schedule')</span></a></li>@endif
                        <li class="{{ request()->routeIs('admin.lessons.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.lessons.index') ? route('admin.lessons.index') : '#' }}"><x-svg-icon name="clock" class="vc-ico" /><span class="menu-item">@lang('shell.nav_periods')</span></a></li>
                        @if($canViewBooks)<li class="{{ request()->routeIs('manage.books.*') ? 'active' : '' }}"><a href="{{ Route::has('manage.books.index') ? route('manage.books.index') : '#' }}"><x-svg-icon name="book-half" class="vc-ico" /><span class="menu-item">@lang('shell.nav_books')</span></a></li>@endif
                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('manage.weekly-plans.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('manage.weekly-plans.index') ? route('manage.weekly-plans.index') : '#' }}" data-label="@lang('shell.nav_weekly_plan')"><x-svg-icon name="list-ul" class="vc-ico" /><span class="menu-title">@lang('shell.nav_weekly_plan')</span></a>
                </li>

                {{-- الدرجات --}}
                <li class="nav-item has-sub {{ (request()->routeIs('admin.grades.*') || request()->routeIs('admin.grade-reports.*') || request()->routeIs('admin.grades.entry.*')) ? 'active open' : '' }}" data-section="educational">
                    <a href="#" data-label="@lang('shell.nav_grades')"><x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-title">@lang('shell.nav_grades')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.grade-reports.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.grade-reports.index') ? route('admin.grade-reports.index') : '#' }}"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-item">تقارير الدرجات</span></a>
                        </li>
                        <li class="{{ request()->routeIs('admin.grades.entry.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.grades.entry.index') ? route('admin.grades.entry.index') : '#' }}"><x-svg-icon name="grid-3x3-gap" class="vc-ico" /><span class="menu-item">إدخال الدرجات (ديناميكي)</span></a>
                        </li>
                        <li class="{{ (request()->routeIs('admin.grades.index') || request()->routeIs('admin.grades.store') || request()->routeIs('admin.grades.publish')) ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.grades.index') ? route('admin.grades.index') : '#' }}"><x-svg-icon name="pencil" class="vc-ico" /><span class="menu-item">إدخال الدرجات (مبسط)</span></a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('manage.schedules.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('manage.schedules.index') ? route('manage.schedules.index') : '#' }}" data-label="@lang('shell.nav_schedule')"><x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_schedule')</span></a>
                </li>

                {{-- المكتبات --}}
                <li class="nav-item has-sub {{ request()->routeIs('admin.libraries.*') ? 'active open' : '' }}" data-section="educational">
                    <a href="#" data-label="@lang('shell.nav_libraries')"><x-svg-icon name="bookmark" class="vc-ico" /><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.libraries.public.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.public.index') ? route('admin.libraries.public.index') : '#' }}"><x-svg-icon name="globe" class="vc-ico" /><span class="menu-item">@lang('shell.nav_library_public')</span></a></li>
                        <li class="{{ request()->routeIs('admin.libraries.private.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.private.index') ? route('admin.libraries.private.index') : '#' }}"><x-svg-icon name="lock" class="vc-ico" /><span class="menu-item">@lang('shell.nav_library_private')</span></a></li>
                        <li class="{{ request()->routeIs('admin.libraries.labs.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.libraries.labs.index') ? route('admin.libraries.labs.index') : '#' }}"><x-svg-icon name="eyedropper" class="vc-ico" /><span class="menu-item">@lang('shell.nav_labs')</span></a></li>
                    </ul>
                </li>

                <li class="nav-item" data-section="educational" data-label="@lang('shell.nav_counseling')"><a href="#" data-label="@lang('shell.nav_counseling')"><x-svg-icon name="compass" class="vc-ico" /><span class="menu-title">@lang('shell.nav_counseling')</span></a></li>

                <li class="nav-item {{ request()->routeIs('manage.special-education.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('manage.special-education.index') ? route('manage.special-education.index') : '#' }}" data-label="@lang('special_education.title')">
                        <x-svg-icon name="heart-pulse" class="vc-ico" /><span class="menu-title">@lang('special_education.title')</span>
                    </a>
                </li>

                @if($canViewReports)
                <li class="nav-item has-sub {{ request()->routeIs('admin.reports.*') ? 'active open' : '' }}" data-section="educational">
                    <a href="#" data-label="@lang('shell.nav_reports')"><x-svg-icon name="bar-chart" class="vc-ico" /><span class="menu-title">@lang('shell.nav_reports')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.reports.administrative') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.reports.administrative') ? route('admin.reports.administrative') : '#' }}"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-item">@lang('shell.nav_reports_admin')</span></a>
                        </li>
                        <li class="{{ request()->routeIs('admin.reports.statistical') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.reports.statistical') ? route('admin.reports.statistical') : '#' }}"><x-svg-icon name="graph-up" class="vc-ico" /><span class="menu-item">@lang('shell.nav_reports_stats')</span></a>
                        </li>
                        <li class="{{ request()->routeIs('admin.reports.user-reports') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.reports.user-reports') ? route('admin.reports.user-reports') : '#' }}"><x-svg-icon name="people" class="vc-ico" /><span class="menu-item">@lang('shell.nav_reports_users')</span></a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- المواعيد --}}
                <li class="nav-item has-sub {{ (request()->routeIs('manage.appointment-schedules.*') || request()->routeIs('admin.appointment-settings.*') || request()->routeIs('manage.appointments.*')) ? 'active open' : '' }}" data-section="educational">
                    <a href="#" data-label="@lang('shell.nav_appointments')"><x-svg-icon name="calendar" class="vc-ico" /><span class="menu-title">@lang('shell.nav_appointments')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('manage.appointment-schedules.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('manage.appointment-schedules.index') ? route('manage.appointment-schedules.index') : '#' }}">
                                <x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-item">@lang('shell.nav_my_appointments')</span>
                            </a>
                        </li>
                        @if($isStaff)
                        <li class="{{ request()->routeIs('manage.appointments.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('manage.appointments.index') ? route('manage.appointments.index') : '#' }}">
                                <x-svg-icon name="list-ul" class="vc-ico" /><span class="menu-item">@lang('shell.nav_appointments_bookings')</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('admin.appointment-settings.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.appointment-settings.index') ? route('admin.appointment-settings.index') : '#' }}">
                                <x-svg-icon name="gear" class="vc-ico" /><span class="menu-item">@lang('shell.nav_appointments_settings')</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                @if($sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin()))
                <li class="nav-item {{ request()->routeIs('admin.surveys.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('admin.surveys.index') ? route('admin.surveys.index') : '#' }}" data-label="@lang('shell.nav_surveys')">
                        <x-svg-icon name="bar-chart-line" class="vc-ico" /><span class="menu-title">@lang('shell.nav_surveys')</span>
                    </a>
                </li>
                @else
                <li class="nav-item {{ request()->routeIs('my.surveys.*') ? 'active' : '' }}" data-section="educational">
                    <a href="{{ Route::has('my.surveys.index') ? route('my.surveys.index') : '#' }}" data-label="@lang('shell.nav_surveys')">
                        <x-svg-icon name="bar-chart-line" class="vc-ico" /><span class="menu-title">@lang('shell.nav_surveys')</span>
                    </a>
                </li>
                @endif

                <li class="nav-item {{ request()->routeIs('admin.evaluations.*') && !request()->routeIs('admin.evaluations.approvals.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.evaluations.index') ? route('admin.evaluations.index') : '#' }}" data-label="@lang('shell.nav_eval_forms')"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_eval_forms')</span></a></li>
                <li class="nav-item {{ request()->routeIs('admin.my-evaluations.*') || request()->routeIs('admin.evaluations.subjects') || request()->routeIs('admin.evaluations.execute.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.my-evaluations.index') ? route('admin.my-evaluations.index') : '#' }}" data-label="@lang('shell.nav_evaluations')"><x-svg-icon name="star" class="vc-ico" /><span class="menu-title">@lang('shell.nav_evaluations')</span></a></li>
                <li class="nav-item {{ request()->routeIs('admin.class-visits.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.class-visits.index') ? route('admin.class-visits.index') : '#' }}" data-label="@lang('shell.nav_visits')"><x-svg-icon name="geo-alt" class="vc-ico" /><span class="menu-title">@lang('shell.nav_visits')</span></a></li>
                <li class="nav-item {{ request()->routeIs('admin.evaluations.approvals.*') ? 'active' : '' }}" data-section="educational"><a href="{{ Route::has('admin.evaluations.approvals.index') ? route('admin.evaluations.approvals.index') : '#' }}" data-label="@lang('shell.nav_eval_approvals')"><x-svg-icon name="check2-all" class="vc-ico" /><span class="menu-title">@lang('shell.nav_eval_approvals')</span></a></li>

                {{-- تقارير التقييم --}}
                <li class="nav-item has-sub {{ request()->routeIs('admin.eval-reports.*') || request()->routeIs('admin.job-performance.*') ? 'active open' : '' }}" data-section="educational">
                    <a href="#" data-label="@lang('shell.nav_eval_reports')"><x-svg-icon name="bar-chart" class="vc-ico" /><span class="menu-title">@lang('shell.nav_eval_reports')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.eval-reports.supervisors') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.supervisors') ? route('admin.eval-reports.supervisors') : '#' }}"><x-svg-icon name="person-badge" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_rep_supervisors')</span></a></li>
                        <li class="{{ request()->routeIs('admin.eval-reports.supervisors-detailed') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.supervisors-detailed') ? route('admin.eval-reports.supervisors-detailed') : '#' }}"><x-svg-icon name="list" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_rep_detailed')</span></a></li>
                        <li class="{{ request()->routeIs('admin.eval-reports.general-manager') ? 'active' : '' }}"><a href="{{ Route::has('admin.eval-reports.general-manager') ? route('admin.eval-reports.general-manager') : '#' }}"><x-svg-icon name="shield-shaded" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_rep_gm')</span></a></li>
                        <li class="{{ request()->routeIs('admin.job-performance.*') ? 'active' : '' }}"><a href="{{ Route::has('admin.job-performance.index') ? route('admin.job-performance.index') : '#' }}"><x-svg-icon name="briefcase" class="vc-ico" /><span class="menu-item">@lang('shell.nav_job_performance')</span></a></li>
                        @if (Route::has('admin.eval-audit.index'))
                        <li class="{{ request()->routeIs('admin.eval-audit.*') ? 'active' : '' }}"><a href="{{ route('admin.eval-audit.index') }}"><x-svg-icon name="clock-history" class="vc-ico" /><span class="menu-item">@lang('shell.nav_eval_audit')</span></a></li>
                        @endif
                    </ul>
                </li>

                {{-- الحضور والغياب (Card #275) — every child wired to its real Sprint-10
                     route via Route::has guards; dropdown gated by attendance.view --}}
                @if($canViewAttendance)
                <li class="nav-item has-sub {{ (request()->routeIs('admin.attendance.*') || request()->routeIs('admin.student-attendance.*')) ? 'active open' : '' }}" data-section="educational">
                    <a href="#" data-label="@lang('shell.nav_attendance_management')"><x-svg-icon name="person-x" class="vc-ico" /><span class="menu-title">@lang('shell.nav_attendance_management')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.attendance.reports.status') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.reports.status') ? route('admin.attendance.reports.status') : '#' }}"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_report')</span></a></li>
                        <li class="{{ request()->routeIs('admin.attendance.reports.aggregate') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.reports.aggregate') ? route('admin.attendance.reports.aggregate') : '#' }}"><x-svg-icon name="stack" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_aggregate')</span></a></li>
                        <li class="{{ request()->routeIs('admin.attendance.reports.index') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.reports.index') ? route('admin.attendance.reports.index') : '#' }}"><x-svg-icon name="list" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_list')</span></a></li>
                        <li class="{{ request()->routeIs('admin.attendance.reports.late') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.reports.late') ? route('admin.attendance.reports.late') : '#' }}"><x-svg-icon name="hourglass-split" class="vc-ico" /><span class="menu-item">@lang('shell.nav_late_report')</span></a></li>
                        <li class="{{ request()->routeIs('admin.attendance.reports.behavior') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.reports.behavior') ? route('admin.attendance.reports.behavior') : '#' }}"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behavior_report')</span></a></li>
                        <li class="{{ request()->routeIs('admin.attendance.index') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.index') ? route('admin.attendance.index') : '#' }}"><x-svg-icon name="speedometer2" class="vc-ico" /><span class="menu-item">@lang('shell.nav_attendance_dashboard')</span></a></li>
                        <li class="{{ request()->routeIs('admin.student-attendance.daily') ? 'active' : '' }}"><a href="{{ Route::has('admin.student-attendance.daily') ? route('admin.student-attendance.daily') : '#' }}"><x-svg-icon name="check-square" class="vc-ico" /><span class="menu-item">@lang('shell.nav_daily_attendance')</span></a></li>
                        <li class="{{ request()->routeIs('admin.student-attendance.period') ? 'active' : '' }}"><a href="{{ Route::has('admin.student-attendance.period') ? route('admin.student-attendance.period') : '#' }}"><x-svg-icon name="stopwatch" class="vc-ico" /><span class="menu-item">@lang('shell.nav_period_attendance')</span></a></li>
                        <li class="{{ request()->routeIs('admin.student-attendance.follow-up') ? 'active' : '' }}"><a href="{{ Route::has('admin.student-attendance.follow-up') ? route('admin.student-attendance.follow-up') : '#' }}"><x-svg-icon name="binoculars" class="vc-ico" /><span class="menu-item">@lang('shell.nav_follow_late_absence')</span></a></li>
                        <li class="{{ request()->routeIs('admin.attendance.reports.day-absence') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.reports.day-absence') ? route('admin.attendance.reports.day-absence') : '#' }}"><x-svg-icon name="calendar-day" class="vc-ico" /><span class="menu-item">@lang('shell.nav_days_absence_report')</span></a></li>
                        <li class="{{ request()->routeIs('admin.attendance.reports.period-absence') ? 'active' : '' }}"><a href="{{ Route::has('admin.attendance.reports.period-absence') ? route('admin.attendance.reports.period-absence') : '#' }}"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-item">@lang('shell.nav_subjects_absence_summary')</span></a></li>
                    </ul>
                </li>
                @endif

                @if($canViewTeacherAttendance)
                <li class="nav-item {{ request()->routeIs('admin.teacher-attendance.*') ? 'active' : '' }}" data-section="educational" data-label="@lang('shell.nav_teacher_absence')"><a href="{{ Route::has('admin.teacher-attendance.daily') ? route('admin.teacher-attendance.daily') : '#' }}" data-label="@lang('shell.nav_teacher_absence')"><x-svg-icon name="person-badge" class="vc-ico" /><span class="menu-title">@lang('shell.nav_teacher_absence')</span></a></li>
                @endif
                @if($canViewCertificates)
                <li class="nav-item {{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}" data-section="educational" data-label="@lang('shell.nav_certificates')"><a href="{{ Route::has('admin.certificates.index') ? route('admin.certificates.index') : '#' }}" data-label="@lang('shell.nav_certificates')"><x-svg-icon name="award" class="vc-ico" /><span class="menu-title">@lang('shell.nav_certificates')</span></a></li>
                @endif
                @if($canViewEducationalSites)
                <li class="nav-item {{ request()->routeIs('admin.educational-sites.*') ? 'active' : '' }}" data-section="educational" data-label="@lang('shell.nav_edu_sites')"><a href="{{ Route::has('admin.educational-sites.index') ? route('admin.educational-sites.index') : '#' }}" data-label="@lang('shell.nav_edu_sites')"><x-svg-icon name="box-arrow-up-right" class="vc-ico" /><span class="menu-title">@lang('shell.nav_edu_sites')</span></a></li>
                @endif

            </div>{{-- /gp-sec-educational --}}

            {{-- ========== 3. عمليات التواصل ========== --}}
            <div class="gp-section-header sec-communication" data-section-toggle="communication">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4.414a1 1 0 0 0-.707.293L.854 15.146A.5.5 0 0 1 0 14.793V2z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.section_communication')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-communication">

                {{-- الإعلانات — gated; route not built yet (later card C1) so hidden for now --}}
                @if($canViewAnnouncements && Route::has('admin.announcements.index'))
                <li class="nav-item {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" data-section="communication" data-label="@lang('shell.nav_announcements')"><a href="{{ route('admin.announcements.index') }}" data-label="@lang('shell.nav_announcements')"><x-svg-icon name="megaphone" class="vc-ico" /><span class="menu-title">@lang('shell.nav_announcements')</span></a></li>
                @endif
                {{-- الإعلانات المبوبة — route not built yet so hidden --}}
                @if(Route::has('admin.classified-ads.index'))
                <li class="nav-item {{ request()->routeIs('admin.classified-ads.*') ? 'active' : '' }}" data-section="communication" data-label="@lang('shell.nav_classified_ads')"><a href="{{ route('admin.classified-ads.index') }}" data-label="@lang('shell.nav_classified_ads')"><x-svg-icon name="grid" class="vc-ico" /><span class="menu-title">@lang('shell.nav_classified_ads')</span></a></li>
                @endif

                {{-- التقويم المدرسي --}}
                @php
                    $calIsStaff = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin() || $sidebarUser->isTeacher());
                    $calRoute   = $calIsStaff
                        ? (Route::has('manage.school-calendar.index') ? route('manage.school-calendar.index') : null)
                        : (Route::has('my.calendar.index') ? route('my.calendar.index') : null);
                    $calActive  = request()->routeIs('manage.school-calendar.*') || request()->routeIs('my.calendar.*');
                @endphp
                @if($canViewCalendar && $calRoute)
                <li class="nav-item {{ $calActive ? 'active' : '' }}" data-section="communication">
                    <a href="{{ $calRoute }}" data-label="@lang('shell.nav_calendar')"><x-svg-icon name="calendar-week" class="vc-ico" /><span class="menu-title">@lang('shell.nav_calendar')</span></a>
                </li>
                @endif

                @php
                    $vcIsStaff  = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin() || $sidebarUser->isTeacher());
                    $vcRoute    = $vcIsStaff
                        ? (Route::has('manage.virtual-classes.index') ? route('manage.virtual-classes.index') : null)
                        : (Route::has('my.virtual-classes.index') ? route('my.virtual-classes.index') : null);
                    $vcActive   = request()->routeIs('manage.virtual-classes.*') || request()->routeIs('my.virtual-classes.*');
                @endphp
                @if($canViewVirtualClasses && $vcRoute)
                <li class="nav-item {{ $vcActive ? 'active' : '' }}" data-section="communication">
                    <a href="{{ $vcRoute }}" data-label="@lang('shell.nav_virtual_classrooms')"><x-svg-icon name="camera-video" class="vc-ico" /><span class="menu-title">@lang('shell.nav_virtual_classrooms')</span></a>
                </li>
                @endif

                @php
                    $discIsStaff   = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin() || $sidebarUser->isTeacher());
                    $discRoute     = $discIsStaff
                        ? (Route::has('manage.discussion-rooms.index') ? route('manage.discussion-rooms.index') : null)
                        : (Route::has('discussion.index') ? route('discussion.index') : null);
                    $discActive    = request()->routeIs('manage.discussion-rooms.*') || request()->routeIs('discussion.*');
                @endphp
                @if($canViewDiscussion && $discRoute)
                <li class="nav-item {{ $discActive ? 'active' : '' }}" data-section="communication">
                    <a href="{{ $discRoute }}" data-label="@lang('shell.nav_discussion_rooms')"><x-svg-icon name="chat-dots" class="vc-ico" /><span class="menu-title">@lang('shell.nav_discussion_rooms')</span></a>
                </li>
                @endif

                {{-- سجلات السلوك — للمعلمين فقط (المشرفون يصلون عبر قسم النظام) --}}
                @if($sidebarUser && $sidebarUser->isTeacher() && ! $sidebarUser->isSchoolAdmin() && ! $sidebarUser->isSuperAdmin())
                <li class="nav-item {{ request()->routeIs('admin.behavior.records.*') ? 'active' : '' }}" data-section="communication">
                    <a href="{{ route('admin.behavior.records.index') }}" data-label="@lang('behavior.records.title')"><x-svg-icon name="shield-check" class="vc-ico" /><span class="menu-title">@lang('behavior.records.title')</span></a>
                </li>
                @endif

                {{-- صندوق الوارد --}}
                @if($canViewMailbox && Route::has('my.mailbox.index'))
                <li class="nav-item has-sub {{ request()->routeIs('my.mailbox.*') ? 'active open' : '' }}" data-section="communication">
                    <a href="{{ Route::has('my.mailbox.index') ? route('my.mailbox.index') : '#' }}" data-label="@lang('shell.nav_mailbox')"><x-svg-icon name="inbox" class="vc-ico" /><span class="menu-title">@lang('shell.nav_mailbox')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('my.mailbox.create') ? 'active' : '' }}">
                            <a href="{{ Route::has('my.mailbox.create') ? route('my.mailbox.create') : '#' }}"><x-svg-icon name="pencil" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_new')</span></a>
                        </li>
                        <li class="{{ request()->routeIs('my.mailbox.index') || (request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'inbox') ? 'active' : '' }}">
                            <a href="{{ Route::has('my.mailbox.index') ? route('my.mailbox.index') : '#' }}"><x-svg-icon name="inbox" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_inbox')</span></a>
                        </li>
                        <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'sent' ? 'active' : '' }}">
                            <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'sent') : '#' }}"><x-svg-icon name="send" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_sent')</span></a>
                        </li>
                        <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'drafts' ? 'active' : '' }}">
                            <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'drafts') : '#' }}"><x-svg-icon name="file" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_drafts')</span></a>
                        </li>
                        <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'archive' ? 'active' : '' }}">
                            <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'archive') : '#' }}"><x-svg-icon name="archive" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_archive')</span></a>
                        </li>
                        <li class="{{ request()->routeIs('my.mailbox.folder') && request()->route('folder') === 'trash' ? 'active' : '' }}">
                            <a href="{{ Route::has('my.mailbox.folder') ? route('my.mailbox.folder', 'trash') : '#' }}"><x-svg-icon name="trash" class="vc-ico" /><span class="menu-item">@lang('shell.nav_mail_trash')</span></a>
                        </li>
                    </ul>
                </li>
                @endif

                {{-- الرسائل النصية / واتساب — only parameterless routes are linked here.
                     School-scoped sub-pages (messages/senders reports, sender-name, credit) need a
                     {school} param and are wired by later cards (C8/C9/C12). --}}
                @php
                    $smsItems = [
                        ['gate' => $canViewSms,      'route' => 'admin.sms-services.index', 'icon' => 'send',      'label' => __('shell.nav_sms_send'),  'active' => 'admin.sms-services.index'],
                        ['gate' => $canViewWhatsapp, 'route' => 'admin.whatsapp.index',     'icon' => 'chat-dots', 'label' => __('shell.nav_whatsapp'),  'active' => 'admin.whatsapp.*'],
                    ];
                    $smsVisible = collect($smsItems)
                        ->filter(fn($i) => $i['gate'] && Route::has($i['route']))
                        ->unique(fn($i) => $i['route']);
                @endphp
                @if($smsVisible->isNotEmpty())
                <li class="nav-item has-sub {{ request()->routeIs('admin.sms-services.*') || request()->routeIs('admin.whatsapp.*') ? 'active open' : '' }}" data-section="communication">
                    <a href="#" data-label="@lang('shell.nav_sms')"><x-svg-icon name="phone" class="vc-ico" /><span class="menu-title">@lang('shell.nav_sms')</span></a>
                    <ul class="menu-content">
                        @foreach($smsVisible as $i)
                        <li class="{{ request()->routeIs($i['active']) ? 'active' : '' }}"><a href="{{ route($i['route']) }}"><x-svg-icon name="{{ $i['icon'] }}" class="vc-ico" /><span class="menu-item">{{ $i['label'] }}</span></a></li>
                        @endforeach
                    </ul>
                </li>
                @endif

                {{-- أولياء الأمور كجهة تواصل (CRM) — route not built yet (later card C11) so hidden --}}
                @if($canViewParentsContact && Route::has('admin.parents-contact.index'))
                <li class="nav-item {{ request()->routeIs('admin.parents-contact.*') ? 'active' : '' }}" data-section="communication">
                    <a href="{{ route('admin.parents-contact.index') }}" data-label="@lang('shell.nav_parent_contact')"><x-svg-icon name="people" class="vc-ico" /><span class="menu-title">@lang('shell.nav_parent_contact')</span></a>
                </li>
                @endif

            </div>{{-- /gp-sec-communication --}}

            {{-- ========== 4. إعدادات النظام ========== --}}
            <div class="gp-section-header sec-system" data-section-toggle="system">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.892 3.433-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.892-1.64-.901-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.section_system_settings')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-system">

                <li class="nav-item {{ request()->routeIs('admin.schools.*') ? 'active' : '' }}" data-section="system">
                    <a href="{{ Route::has('admin.schools.index') ? route('admin.schools.index') : '#' }}" data-label="@lang('shell.nav_schools')"><x-svg-icon name="building" class="vc-ico" /><span class="menu-title">@lang('shell.nav_schools')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.policies.*') ? 'active' : '' }}" data-section="system">
                    <a href="{{ Route::has('admin.policies.index') ? route('admin.policies.index') : '#' }}" data-label="@lang('shell.nav_policies')"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-title">@lang('shell.nav_policies')</span></a>
                </li>

                {{-- المستخدمون --}}
                <li class="nav-item has-sub {{ request()->routeIs('admin.users.*') ? 'open' : '' }}" data-section="system">
                    <a href="#" data-label="@lang('shell.nav_users')"><x-svg-icon name="people" class="vc-ico" /><span class="menu-title">@lang('shell.nav_users')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.users.students.*') && !request()->routeIs('admin.users.students.global-search') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.users.students.index') ? route('admin.users.students.index') : '#' }}">
                                <x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-item">@lang('users.students')</span>
                            </a>
                        </li>
                        @if(auth()->check() && auth()->user()->isSuperAdmin())
                        <li class="{{ request()->routeIs('admin.users.students.global-search') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.users.students.global-search') ? route('admin.users.students.global-search') : '#' }}">
                                <x-svg-icon name="search" class="vc-ico" /><span class="menu-item">@lang('users.global_search')</span>
                            </a>
                        </li>
                        @endif
                        <li class="{{ request()->routeIs('admin.users.parents.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.users.parents.index') ? route('admin.users.parents.index') : '#' }}">
                                <x-svg-icon name="people" class="vc-ico" /><span class="menu-item">@lang('users.parents')</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('admin.users.teachers.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.users.teachers.index') ? route('admin.users.teachers.index') : '#' }}">
                                <x-svg-icon name="easel" class="vc-ico" /><span class="menu-item">@lang('users.teachers')</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('admin.users.admins.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.users.admins.index') ? route('admin.users.admins.index') : '#' }}">
                                <x-svg-icon name="shield-shaded" class="vc-ico" /><span class="menu-item">@lang('users.admins')</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('admin.users.cards.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.users.cards.index') ? route('admin.users.cards.index') : '#' }}">
                                <x-svg-icon name="person-vcard" class="vc-ico" /><span class="menu-item">@lang('users.cards')</span>
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('admin.users.job-titles.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.users.job-titles.index') ? route('admin.users.job-titles.index') : '#' }}">
                                <x-svg-icon name="tag" class="vc-ico" /><span class="menu-item">@lang('users.job_titles')</span>
                            </a>
                        </li>
                        @if($canViewNoor)
                        <li class="{{ request()->routeIs('admin.noor.*') ? 'active' : '' }}">
                            <a href="{{ Route::has('admin.noor.form') ? route('admin.noor.form') : '#' }}">
                                <x-svg-icon name="file-earmark-arrow-down" class="vc-ico" /><span class="menu-item">@lang('shell.nav_users_import_noor')</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>

                <li class="nav-item {{ request()->routeIs('admin.school-schedule.*') ? 'active' : '' }}" data-section="system">
                    <a href="{{ Route::has('admin.school-schedule.index') ? route('admin.school-schedule.index') : '#' }}" data-label="@lang('shell.nav_school_schedule')">
                        <x-svg-icon name="calendar-week" class="vc-ico" /><span class="menu-title">@lang('shell.nav_school_schedule')</span>
                    </a>
                </li>

                <li class="nav-item {{ request()->routeIs('manage.academic-years.*') ? 'active' : '' }}" data-section="system">
                    <a href="{{ Route::has('manage.academic-years.index') ? route('manage.academic-years.index') : '#' }}" data-label="@lang('shell.nav_academic_years')"><x-svg-icon name="calendar" class="vc-ico" /><span class="menu-title">@lang('shell.nav_academic_years')</span></a>
                </li>

                <li class="nav-item {{ request()->routeIs('admin.canteens.*') ? 'active' : '' }}" data-section="system">
                    <a href="{{ route('admin.canteens.index') }}" data-label="@lang('shell.nav_cafeteria')"><x-svg-icon name="cup-hot" class="vc-ico" /><span class="menu-title">@lang('shell.nav_cafeteria')</span></a>
                </li>

                {{-- السلوك --}}
                <li class="nav-item has-sub {{ request()->routeIs('admin.behavior.*') ? 'active open' : '' }}" data-section="system">
                    <a href="#" data-label="@lang('shell.nav_behavior')"><x-svg-icon name="shield-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_behavior')</span></a>
                    <ul class="menu-content">
                        <li class="{{ request()->routeIs('admin.behavior.groups.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.groups.index') }}"><x-svg-icon name="people" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behavior_groups')</span></a></li>
                        <li class="{{ request()->routeIs('admin.behavior.behaviors.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.behaviors.index') }}"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behaviors')</span></a></li>
                        <li class="{{ request()->routeIs('admin.behavior.actions.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.actions.index') }}"><x-svg-icon name="gear-fill" class="vc-ico" /><span class="menu-item">@lang('shell.nav_behavior_actions')</span></a></li>
                        <li class="{{ request()->routeIs('admin.behavior.records.*') ? 'active' : '' }}"><a href="{{ route('admin.behavior.records.index') }}"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-item">@lang('behavior.records.title')</span></a></li>
                    </ul>
                </li>

                @php
                    $sidebarSupportRoute = ($sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin()))
                        ? (Route::has('admin.support.index') ? route('admin.support.index') : '#')
                        : (Route::has('my.support.index') ? route('my.support.index') : '#');
                    $sidebarSupportActive = request()->routeIs('admin.support.*') || request()->routeIs('my.support.*');
                @endphp
                <li class="nav-item {{ $sidebarSupportActive ? 'active' : '' }}" data-section="system">
                    <a href="{{ $sidebarSupportRoute }}" data-label="@lang('shell.nav_support')"><x-svg-icon name="life-preserver" class="vc-ico" /><span class="menu-title">@lang('shell.nav_support')</span></a>
                </li>
                <li class="nav-item" data-section="system" data-label="@lang('shell.nav_admissions')"><a href="#" data-label="@lang('shell.nav_admissions')"><x-svg-icon name="person-plus" class="vc-ico" /><span class="menu-title">@lang('shell.nav_admissions')</span></a></li>

                @if($sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin()))
                <li class="nav-item {{ request()->routeIs('admin.certificates.*') ? 'active' : '' }}" data-section="system">
                    <a href="{{ Route::has('admin.certificates.index') ? route('admin.certificates.index') : '#' }}" data-label="@lang('certificates.title')">
                        <x-svg-icon name="award" class="vc-ico" />
                        <span class="menu-title">@lang('certificates.title')</span>
                    </a>
                </li>
                @endif

            </div>{{-- /gp-sec-system --}}

            @endif {{-- /isStaff --}}

            {{-- ── Items visible to all authenticated users ── --}}

            {{-- Students get "شهاداتي" inside their own تقارير group; show the
                 standalone item only for teachers/parents to avoid duplication. --}}
            @if($sidebarUser && ($sidebarUser->isTeacher() || $sidebarUser->isParent()))
            <li class="nav-item {{ request()->routeIs('my.certificates.*') ? 'active' : '' }}">
                <a href="{{ Route::has('my.certificates.index') ? route('my.certificates.index') : '#' }}" data-label="@lang('certificates.my_title')">
                    <x-svg-icon name="award" class="vc-ico" />
                    <span class="menu-title">@lang('certificates.my_title')</span>
                </a>
            </li>
            @endif

            {{-- تقويم المدرسة --}}
            @php
                $scCalIsStaff   = $sidebarUser && ($sidebarUser->isSuperAdmin() || $sidebarUser->isSchoolAdmin() || $sidebarUser->isTeacher());
                $scCalRoute     = $scCalIsStaff
                    ? (Route::has('manage.school-calendar.index') ? route('manage.school-calendar.index') : '#')
                    : (Route::has('my.calendar.index') ? route('my.calendar.index') : '#');
                $scCalActive    = request()->routeIs('manage.school-calendar.*') || request()->routeIs('my.calendar.*');
            @endphp
            <li class="nav-item {{ $scCalActive ? 'active' : '' }}">
                <a href="{{ $scCalRoute }}" data-label="@lang('shell.nav_calendar')"><x-svg-icon name="calendar-week" class="vc-ico" /><span class="menu-title">@lang('shell.nav_calendar')</span></a>
            </li>

            <li class="nav-item {{ request()->routeIs('policies.my.*') ? 'active' : '' }}">
                <a href="{{ Route::has('policies.my.index') ? route('policies.my.index') : '#' }}" data-label="@lang('shell.nav_my_policies')"><x-svg-icon name="hammer" class="vc-ico" /><span class="menu-title">@lang('shell.nav_my_policies')</span></a>
            </li>

            @if($sidebarUser && $sidebarUser->hasRole('parent'))
            <li class="nav-item {{ request()->routeIs('my.canteen.*') ? 'active' : '' }}">
                <a href="{{ Route::has('my.canteen.index') ? route('my.canteen.index') : '#' }}" data-label="@lang('canteen.parent.title')"><x-svg-icon name="cup-hot" class="vc-ico" /><span class="menu-title">@lang('canteen.parent.title')</span></a>
            </li>
            @endif

            {{-- ========== بوابة المعلم ========== --}}
            @if($sidebarUser && $sidebarUser->isTeacher())
            <div class="gp-section-header sec-teacher" data-section-toggle="teacher">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v1H0V4z"/><path d="M0 6v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6H0zm5 4h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1zm0-2h3a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.portal_teacher')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-teacher">
                <li class="nav-item {{ request()->routeIs('teacher.schedule') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ route('teacher.schedule') }}" data-label="@lang('shell.portal_my_schedule_link')"><x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_schedule_link')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.weekly-plans.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ route('teacher.weekly-plans.index') }}" data-label="@lang('shell.portal_my_weekly_plans')"><x-svg-icon name="list-ul" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_weekly_plans')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.exams.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ route('teacher.exams.index') }}" data-label="@lang('shell.portal_my_exams')"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_exams')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.grades.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ route('teacher.grades.index') }}" data-label="@lang('shell.portal_enter_grades')"><x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-title">@lang('shell.portal_enter_grades')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.attendance.index') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ route('teacher.attendance.index') }}" data-label="@lang('shell.portal_record_attendance')"><x-svg-icon name="check-square" class="vc-ico" /><span class="menu-title">@lang('shell.portal_record_attendance')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.assignments.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ Route::has('admin.assignments.index') ? route('admin.assignments.index') : '#' }}" data-label="@lang('shell.nav_assignments')"><x-svg-icon name="list-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_assignments')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.my-evaluations.*') || request()->routeIs('admin.evaluations.subjects') || request()->routeIs('admin.evaluations.execute.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ Route::has('admin.my-evaluations.index') ? route('admin.my-evaluations.index') : '#' }}" data-label="@lang('shell.nav_evaluations')"><x-svg-icon name="star" class="vc-ico" /><span class="menu-title">@lang('shell.nav_evaluations')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.question-banks.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ Route::has('admin.question-banks.index') ? route('admin.question-banks.index') : '#' }}" data-label="@lang('shell.nav_questions_bank')"><x-svg-icon name="database" class="vc-ico" /><span class="menu-title">@lang('shell.nav_questions_bank')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('manage.books.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ Route::has('manage.books.index') ? route('manage.books.index') : '#' }}" data-label="@lang('shell.nav_books')"><x-svg-icon name="book" class="vc-ico" /><span class="menu-title">@lang('shell.nav_books')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.libraries.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ Route::has('admin.libraries.public.index') ? route('admin.libraries.public.index') : '#' }}" data-label="@lang('shell.nav_libraries')"><x-svg-icon name="bookmark" class="vc-ico" /><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ Route::has('admin.subjects.index') ? route('admin.subjects.index') : '#' }}" data-label="@lang('shell.nav_subjects')"><x-svg-icon name="book-half" class="vc-ico" /><span class="menu-title">@lang('shell.nav_subjects')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('teacher.students.*') ? 'active' : '' }}" data-section="teacher">
                    <a href="{{ route('teacher.students.index') }}" data-label="@lang('teacher_students.sidebar_link')"><x-svg-icon name="people" class="vc-ico" /><span class="menu-title">@lang('teacher_students.sidebar_link')</span></a>
                </li>
            </div>
            @endif

            {{-- ========== بوابة الطالب ========== --}}
            @if($sidebarUser && $sidebarUser->isStudent())

            {{-- ── مواد الطالب — dynamic from grade + class (Card #170) ── --}}
            <div class="gp-section-header sec-student" data-section-toggle="student-subjects">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.subjects_group')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-student-subjects">
                <li class="nav-item {{ request()->routeIs('student.subjects.index') ? 'active' : '' }}" data-section="student">
                    <a href="{{ Route::has('student.subjects.index') ? route('student.subjects.index') : '#' }}" data-label="@lang('shell.subjects_group')"><x-svg-icon name="grid" class="vc-ico" /><span class="menu-title">@lang('shell.subjects_group')</span></a>
                </li>
                @forelse($sidebarStudentSubjects as $subj)
                    @php($subjActive = request()->routeIs('student.subjects.show') && (int) request()->route('subject') === (int) $subj->id)
                    <li class="nav-item {{ $subjActive ? 'active' : '' }}" data-section="student">
                        <a href="{{ Route::has('student.subjects.show') ? route('student.subjects.show', $subj->id) : '#' }}" data-label="{{ $subj->name }}">
                            <x-svg-icon name="{{ $subj->icon ?: 'book' }}" class="vc-ico" /><span class="menu-title">{{ $subj->name }}</span>
                        </a>
                    </li>
                @empty
                    <li class="nav-item" data-section="student"><a href="#" style="opacity:.6; pointer-events:none;" data-label="—"><x-svg-icon name="dash-circle" class="vc-ico" /><span class="menu-title" style="font-size:.8rem;">—</span></a></li>
                @endforelse
            </div>

            {{-- ── عمليات تعليمية ── --}}
            <div class="gp-section-header sec-student" data-section-toggle="student-educational">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917l-7.5-3.5z"/><path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466 4.176 9.032z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.group_educational')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-student-educational">
                <li class="nav-item {{ request()->routeIs('student.weekly-plans') ? 'active' : '' }}" data-section="student"><a href="{{ Route::has('student.weekly-plans') ? route('student.weekly-plans') : '#' }}" data-label="@lang('shell.nav_weekly_plan')"><x-svg-icon name="list-ul" class="vc-ico" /><span class="menu-title">@lang('shell.nav_weekly_plan')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.books.*') ? 'active' : '' }}" data-section="student"><a href="{{ Route::has('student.books.index') ? route('student.books.index') : '#' }}" data-label="@lang('shell.nav_books')"><x-svg-icon name="book-half" class="vc-ico" /><span class="menu-title">@lang('shell.nav_books')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.schedule') ? 'active' : '' }}" data-section="student"><a href="{{ route('student.schedule') }}" data-label="@lang('shell.nav_schedule')"><x-svg-icon name="calendar-check" class="vc-ico" /><span class="menu-title">@lang('shell.nav_schedule')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.exams') ? 'active' : '' }}" data-section="student"><a href="{{ route('student.exams') }}" data-label="@lang('shell.portal_exams')"><x-svg-icon name="file-text" class="vc-ico" /><span class="menu-title">@lang('shell.portal_exams')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.grades') ? 'active' : '' }}" data-section="student"><a href="{{ route('student.grades') }}" data-label="@lang('shell.portal_my_grades')"><x-svg-icon name="mortarboard" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_grades')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.attendance') ? 'active' : '' }}" data-section="student"><a href="{{ route('student.attendance') }}" data-label="@lang('shell.portal_my_attendance')"><x-svg-icon name="check-square" class="vc-ico" /><span class="menu-title">@lang('shell.portal_my_attendance')</span></a></li>
            </div>

            {{-- ── تقارير ── --}}
            <div class="gp-section-header sec-student" data-section-toggle="student-reports">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M4 11H2v3h2v-3zm5-4H7v7h2V7zm5-5v12h-2V2h2zm-2-1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1h-2zM6 7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7zm-5 4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1v-3z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.group_reports')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-student-reports">
                <li class="nav-item {{ request()->routeIs('student.reports.index') ? 'active' : '' }}" data-section="student"><a href="{{ route('student.reports.index') }}" data-label="تقارير الغياب"><x-svg-icon name="pie-chart" class="vc-ico" /><span class="menu-title">تقارير الغياب</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.reports.exam-schedule') ? 'active' : '' }}" data-section="student"><a href="{{ route('student.reports.exam-schedule') }}" data-label="جدول الاختبارات"><x-svg-icon name="clipboard-check" class="vc-ico" /><span class="menu-title">جدول الاختبارات</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.portfolio') ? 'active' : '' }}" data-section="student"><a href="{{ route('student.portfolio') }}" data-label="ملف الإنجاز"><x-svg-icon name="trophy" class="vc-ico" /><span class="menu-title">ملف الإنجاز</span></a></li>
                <li class="nav-item {{ request()->routeIs('my.certificates.*') ? 'active' : '' }}" data-section="student"><a href="{{ Route::has('my.certificates.index') ? route('my.certificates.index') : '#' }}" data-label="@lang('certificates.my_title')"><x-svg-icon name="award" class="vc-ico" /><span class="menu-title">@lang('certificates.my_title')</span></a></li>
                <li class="nav-item {{ request()->routeIs('student.special-education') ? 'active' : '' }}" data-section="student"><a href="{{ Route::has('student.special-education') ? route('student.special-education') : '#' }}" data-label="@lang('student.special_ed.title')"><x-svg-icon name="heart" class="vc-ico" /><span class="menu-title">@lang('student.special_ed.title')</span></a></li>
            </div>

            {{-- ── مكتبات (#173 — قد تكون صفحة لاحقة) ── --}}
            <div class="gp-section-header sec-student" data-section-toggle="student-libraries">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.74.439L8 13.069l-5.26 2.87A.5.5 0 0 1 2 15.5V2z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.group_libraries')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-student-libraries">
                {{-- card #173 — general/private/my-files tabs --}}
                <li class="nav-item {{ request()->routeIs('student.libraries.*') && request('tab','public')==='public' ? 'active' : '' }}" data-section="student">
                    <a href="{{ route('student.libraries.index', ['tab'=>'public']) }}" data-label="@lang('student.library.tab_public')">
                        <x-svg-icon name="globe" class="vc-ico" /><span class="menu-title">@lang('student.library.tab_public')</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('student.libraries.*') && request('tab')==='private' ? 'active' : '' }}" data-section="student">
                    <a href="{{ route('student.libraries.index', ['tab'=>'private']) }}" data-label="@lang('student.library.tab_private')">
                        <x-svg-icon name="lock" class="vc-ico" /><span class="menu-title">@lang('student.library.tab_private')</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('student.libraries.*') && request('tab')==='files' ? 'active' : '' }}" data-section="student">
                    <a href="{{ route('student.libraries.index', ['tab'=>'files']) }}" data-label="@lang('student.library.tab_files')">
                        <x-svg-icon name="folder" class="vc-ico" /><span class="menu-title">@lang('student.library.tab_files')</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('student.labs.*') ? 'active' : '' }}" data-section="student">
                    <a href="{{ route('student.labs.index') }}" data-label="@lang('student.labs.title')">
                        <x-svg-icon name="eyedropper" class="vc-ico" /><span class="menu-title">@lang('student.labs.title')</span>
                    </a>
                </li>
            </div>

            {{-- ── تواصل (Sprint 9 — قد تكون صفحة لاحقة) ── --}}
            <div class="gp-section-header sec-student" data-section-toggle="student-communication">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4.414a1 1 0 0 0-.707.293L.854 15.146A.5.5 0 0 1 0 14.793V2z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.group_communication')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-student-communication">
                <li class="nav-item {{ request()->routeIs('my.mailbox.*') || request()->routeIs('messages.*') ? 'active' : '' }}" data-section="student">
                    <a href="{{ Route::has('my.mailbox.index') ? route('my.mailbox.index') : (Route::has('messages.index') ? route('messages.index') : '#') }}" data-label="@lang('shell.nav_mailbox')"><x-svg-icon name="inbox" class="vc-ico" /><span class="menu-title">@lang('shell.nav_mailbox')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('discussion.*') ? 'active' : '' }}" data-section="student">
                    <a href="{{ Route::has('discussion.index') ? route('discussion.index') : '#' }}" data-label="@lang('shell.nav_discussion_rooms')">
                        <x-svg-icon name="chat-dots" class="vc-ico" /><span class="menu-title">@lang('shell.nav_discussion_rooms')</span>
                        @unless(Route::has('discussion.index'))<small style="opacity:.6; margin-inline-start:auto; font-size:.65rem;">@lang('shell.coming_soon')</small>@endunless
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('my.virtual-classes.*') ? 'active' : '' }}" data-section="student">
                    <a href="{{ Route::has('my.virtual-classes.index') ? route('my.virtual-classes.index') : '#' }}" data-label="@lang('shell.nav_virtual_classrooms')">
                        <x-svg-icon name="camera-video" class="vc-ico" /><span class="menu-title">@lang('shell.nav_virtual_classrooms')</span>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('my.appointments.*') ? 'active' : '' }}" data-section="student">
                    <a href="{{ Route::has('my.appointments.index') ? route('my.appointments.index') : '#' }}" data-label="@lang('shell.nav_my_appointments_booking')"><x-svg-icon name="calendar-plus" class="vc-ico" /><span class="menu-title">@lang('shell.nav_my_appointments_booking')</span></a>
                </li>
            </div>

            {{-- ── الدعم ── --}}
            <div class="gp-section-header sec-student" data-section-toggle="student-support">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zM4.882 4.882a.5.5 0 0 1 .708 0L8 7.293l2.41-2.411a.5.5 0 1 1 .708.708L8.707 8l2.411 2.41a.5.5 0 0 1-.708.708L8 8.707l-2.41 2.411a.5.5 0 0 1-.708-.708L7.293 8 4.882 5.59a.5.5 0 0 1 0-.708z" opacity="0"/><path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 11a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm.93-3.412c-.06.34-.4.412-.93.412-.55 0-.88-.08-.93-.42 0 0-.07-2.13.93-2.58.4-.18.7-.5.7-.95 0-.4-.3-.7-.7-.7-.4 0-.7.3-.7.7H5.6c0-1.04.86-1.9 1.9-1.9s1.9.86 1.9 1.9c0 .8-.5 1.4-1.17 1.7-.4.18-.4.6-.4 1.13z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.group_support')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-student-support">
                <li class="nav-item {{ request()->routeIs('my.support.*') ? 'active' : '' }}" data-section="student">
                    <a href="{{ Route::has('my.support.index') ? route('my.support.index') : '#' }}" data-label="@lang('shell.nav_support')"><x-svg-icon name="life-preserver" class="vc-ico" /><span class="menu-title">@lang('shell.nav_support')</span></a>
                </li>
            </div>
            @endif

            {{-- ========== بوابة ولي الأمر ========== --}}
            @if($sidebarUser && $sidebarUser->isParent())
            <div class="gp-section-header sec-parent" data-section-toggle="parent">
                <span class="gp-sec-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16"><path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"/><path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"/></svg>
                </span>
                <span class="gp-sec-label">@lang('shell.portal_parent')</span>
                <svg class="gp-sec-chevron" xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
            </div>
            <div class="gp-section-content" id="gp-sec-parent">
                <li class="nav-item {{ request()->routeIs('parent.contact-teacher') ? 'active' : '' }}" data-section="parent"><a href="{{ route('parent.contact-teacher') }}" data-label="@lang('shell.portal_contact_teacher')"><x-svg-icon name="envelope" class="vc-ico" /><span class="menu-title">@lang('shell.portal_contact_teacher')</span></a></li>
                <li class="nav-item {{ request()->routeIs('my.appointments.*') ? 'active' : '' }}" data-section="parent">
                    <a href="{{ Route::has('my.appointments.index') ? route('my.appointments.index') : '#' }}" data-label="@lang('shell.nav_my_appointments_booking')"><x-svg-icon name="calendar-plus" class="vc-ico" /><span class="menu-title">@lang('shell.nav_my_appointments_booking')</span></a>
                </li>
                <li class="nav-item {{ request()->routeIs('my.libraries.*') ? 'active' : '' }}" data-section="parent">
                    <a href="{{ Route::has('my.libraries.index') ? route('my.libraries.index') : '#' }}" data-label="@lang('shell.nav_libraries')"><x-svg-icon name="bookmark" class="vc-ico" /><span class="menu-title">@lang('shell.nav_libraries')</span></a>
                </li>
            </div>
            @endif

        </ul>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════
     GP Sidebar JS — mini mode, section collapse, mobile drawer
     ═══════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    var LS_MINI    = 'gp.sidebar.mini';
    var LS_SECS    = 'gp.sidebar.sections'; // JSON object {name: collapsed}
    var MINI_CLASS = 'sidebar-mini';

    // ── Read / write localStorage safely ──
    function lsGet(k, def) { try { var v = localStorage.getItem(k); return v !== null ? JSON.parse(v) : def; } catch (_) { return def; } }
    function lsSet(k, v)   { try { localStorage.setItem(k, JSON.stringify(v)); } catch (_) {} }

    // ── Mini mode ──
    function applyMini(mini) {
        document.body.classList.toggle(MINI_CLASS, !!mini);
        // Let the theme know so it adjusts the content margin
        document.body.classList.toggle('menu-collapsed', !!mini);
    }

    var toggle = document.getElementById('gp-sidebar-toggle');
    if (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var now = !document.body.classList.contains(MINI_CLASS);
            applyMini(now);
            lsSet(LS_MINI, now);
        });
    }

    // Restore mini state on load
    applyMini(lsGet(LS_MINI, false));

    // ── Section collapse / expand ──
    var secStates = lsGet(LS_SECS, {});

    function getSectionContent(name) {
        return document.getElementById('gp-sec-' + name);
    }

    function applySection(header, content, collapsed, animate) {
        if (!content) return;
        header.classList.toggle('collapsed', !!collapsed);
        if (collapsed) {
            content.style.maxHeight = content.scrollHeight + 'px';
            // Force reflow so the transition fires from the real height
            content.offsetHeight; // eslint-disable-line no-unused-expressions
            content.style.maxHeight = '0';
            content.style.opacity  = '0';
            content.classList.add('gp-collapsed');
        } else {
            content.classList.remove('gp-collapsed');
            content.style.opacity = '1';
            content.style.maxHeight = content.scrollHeight + 'px';
            // After transition, remove the fixed height so it can grow
            if (animate) {
                content.addEventListener('transitionend', function once() {
                    content.style.maxHeight = '9999px';
                    content.removeEventListener('transitionend', once);
                });
            } else {
                content.style.maxHeight = '9999px';
            }
        }
    }

    // Determine if any child is active (auto-expand that section)
    function hasActiveChild(content) {
        if (!content) return false;
        return !!content.querySelector('li.active');
    }

    // Init all section headers
    document.querySelectorAll('.gp-section-header[data-section-toggle]').forEach(function (header) {
        var name    = header.getAttribute('data-section-toggle');
        var content = getSectionContent(name);
        if (!content) return;

        // Determine initial state: saved state, but force-open if it has an active child
        var saved = secStates[name];
        var active = hasActiveChild(content);
        var collapsed = active ? false : (typeof saved === 'boolean' ? saved : false);

        // Apply without animation on page load
        applySection(header, content, collapsed, false);

        header.addEventListener('click', function () {
            var isNowCollapsed = !header.classList.contains('collapsed');
            applySection(header, content, isNowCollapsed, true);
            secStates[name] = isNowCollapsed;
            lsSet(LS_SECS, secStates);
        });
    });

    // ── Has-sub dropdowns (collapsible submenus — QA #230) ──────────────
    // Every parent nav-item that owns a <ul.menu-content> becomes a real
    // click-to-toggle dropdown. Children are collapsed by default; the parent
    // of the active page auto-opens; an accordion keeps only one open per
    // section. This is the authoritative source of truth (the server-rendered
    // `.open` class is only a first-paint hint).
    function setSubOpen(li, open, animate) {
        var uc = li.querySelector(':scope > ul.menu-content');
        if (!uc) return;
        li.classList.toggle('open', !!open);
        var trigger = li.querySelector(':scope > a');
        if (trigger) trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!animate) {
            // Let CSS handle the static state (max-height via .open rule).
            uc.style.maxHeight = '';
            uc.style.opacity   = '';
            return;
        }
        if (open) {
            uc.style.opacity = '1';
            uc.style.maxHeight = uc.scrollHeight + 'px';
            uc.addEventListener('transitionend', function once(ev) {
                if (ev.propertyName !== 'max-height') return;
                if (li.classList.contains('open')) uc.style.maxHeight = '1200px';
                uc.removeEventListener('transitionend', once);
            });
        } else {
            // From auto/large height → fixed px → 0 so the transition fires.
            uc.style.maxHeight = uc.scrollHeight + 'px';
            uc.offsetHeight; // reflow
            uc.style.maxHeight = '0';
            uc.style.opacity   = '0';
        }
    }

    var subParents = document.querySelectorAll('.main-menu .navigation li.nav-item.has-sub');
    subParents.forEach(function (li) {
        // Initial state: open only if this parent (or a child) is active.
        var active = li.classList.contains('active') ||
                     li.classList.contains('open') ||
                     !!li.querySelector(':scope > ul.menu-content li.active');
        setSubOpen(li, active, false);

        var link = li.querySelector(':scope > a');
        if (!link) return;
        // Inject the dropdown chevron icon (QA #276) as a flex child at the
        // trailing edge — replaces the old absolute ::after caret that overlapped
        // the label. One place covers every role's sidebar. .open rotates it 180°.
        if (!link.querySelector('.vc-sub-caret')) {
            var caret = document.createElement('span');
            caret.className = 'vc-sub-caret';
            caret.setAttribute('aria-hidden', 'true');
            caret.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>';
            link.appendChild(caret);
        }
        // Mark the toggle as an expandable control for assistive tech.
        link.setAttribute('role', 'button');
        link.setAttribute('aria-expanded', active ? 'true' : 'false');
        var subUl = li.querySelector(':scope > ul.menu-content');
        if (subUl) {
            if (!subUl.id) subUl.id = 'gp-sub-' + Math.random().toString(36).slice(2, 8);
            link.setAttribute('aria-controls', subUl.id);
        }
        // Capture phase + stopImmediatePropagation so we are the single source
        // of truth and the theme's delegated `click.app.menu` (app-menu.js) does
        // not also toggle `.open` / run its jQuery slide — which otherwise fights
        // us in overlay/mobile mode.
        link.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            var willOpen = !li.classList.contains('open');
            if (willOpen) {
                // Accordion: close sibling dropdowns within the same container.
                var container = li.parentElement;
                container.querySelectorAll(':scope > li.nav-item.has-sub.open').forEach(function (sib) {
                    if (sib !== li) setSubOpen(sib, false, true);
                });
            }
            setSubOpen(li, willOpen, true);
        }, true /* capture */);
        // Space activates a role=button (Enter already fires click on an <a>).
        link.addEventListener('keydown', function (e) {
            if (e.key === ' ' || e.key === 'Spacebar') { e.preventDefault(); link.click(); }
        });
    });

    // ── Mobile drawer — works WITH the theme's overlay mechanism ──
    // The theme uses body.menu-open (+ body.vertical-overlay-menu) to show the sidebar
    // as an overlay on small screens. We piggyback on that rather than fighting it.
    window.gpOpenMobileDrawer = function () {
        document.body.classList.add('gp-drawer-open');
        // Also trigger the theme's open state
        document.body.classList.remove('menu-hide');
        document.body.classList.add('menu-open');
    };
    window.gpCloseMobileDrawer = function () {
        document.body.classList.remove('gp-drawer-open');
        document.body.classList.remove('menu-open');
        document.body.classList.add('menu-hide');
    };

    // Close overlay when clicking the overlay div
    var overlayEl = document.getElementById('gp-drawer-overlay');
    if (overlayEl) {
        overlayEl.addEventListener('click', gpCloseMobileDrawer);
    }

    // Close drawer when a real nav link is clicked on mobile.
    // Skip .has-sub parent toggles — tapping them should expand the submenu
    // (handled above), not dismiss the whole drawer.
    document.querySelectorAll('.main-menu .navigation a').forEach(function (a) {
        a.addEventListener('click', function () {
            if (a.parentElement && a.parentElement.classList.contains('has-sub')) return;
            if (window.innerWidth < 768) gpCloseMobileDrawer();
        });
    });

    // Our mobile hamburger button — use capture phase so we run before the
    // theme's jQuery bubbling handler (which calls stopPropagation).
    var mobileBtn = document.getElementById('gp-mobile-menu-btn');
    if (mobileBtn) {
        mobileBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (document.body.classList.contains('gp-drawer-open') || document.body.classList.contains('menu-open')) {
                gpCloseMobileDrawer();
            } else {
                gpOpenMobileDrawer();
            }
        }, true /* capture */);
    }

    // Theme's own desktop hamburger (.menu-toggle) is left alone — the theme handles it.

})();
</script>
