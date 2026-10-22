# Design: Sidebar Restructure

## Files
- **`resources/views/components/sidebar.blade.php`** (1553 lines, hardcoded Blade, no data-driven
  menu array) — the ONLY file for the nav markup. Included at `layouts/app.blade.php:627`.
- **`lang/ar/shell.php`** — new `section_*` / `nav_*` labels for the six sections + new items.
- **`resources/views/programs/coming-soon.blade.php`** — existing placeholder view used by
  `programs.coming-soon` (`routes/web.php:36`). Verify its text; if it doesn't match the card string,
  add a shared parametric placeholder rather than editing the one 3 program links already use.
- **`routes/web.php`** — add placeholder routes for the 4 label-only items (or reuse a parametric one).

## Pattern to follow (existing sidebar idiom)
Section = header div + content div, matched by `data-section-toggle="X"` ↔ `id="gp-sec-X"`, each item
carries `data-section="X"`:
```blade
<div class="gp-section-header sec-XXX" data-section-toggle="XXX">…<span class="gp-sec-label">@lang('shell.section_xxx')</span>…</div>
<div class="gp-section-content" id="gp-sec-XXX">
  <li class="nav-item {{ request()->routeIs('name*') ? 'active' : '' }}" data-section="XXX">
    <a href="{{ Route::has('name') ? route('name') : '#' }}"><x-svg-icon name="…"/><span class="menu-title">@lang('shell.nav_…')</span></a>
  </li>
</div>
```
- has-sub items nest `<ul class="menu-content">` with child `<li>` (see إدارة المواد at :558, المكتبات :608).
- Reuse existing `$canView*` gating flags (sidebar `@php` block :31–82) — do NOT invent new gates;
  for a duplicated item reuse the same flag the original uses so visibility stays consistent.

## Key decisions
1. **Additive placement.** Insert the six new `sec-*` blocks. Section #1 header goes ABOVE the current
   `sec-educational` header (line ~548). #2–#6 follow in order. Existing sections remain untouched below.
   New `data-section` ids: `sec-extras` (#1), `sec-academic` (#2), `sec-assessment` (#3), `sec-support`
   (#4), `sec-admissions` (#5), `sec-comm2` (#6) — avoid colliding with existing ids
   (`educational`, `communication`, `system`, `teacher`, `student`, `parent`).
2. **Duplicate links are intentional.** Same route referenced from old + new section. Accept double
   `active` highlight. No de-dup, no hiding old — the card forbids it.
3. **الأول بلس before أنا والقدرات.** Swap the two standalone program `<li>` at sidebar :543–545.
   This is the single allowed reordering.
4. **Placeholders.** Four labels with no route: فريق العمل, الترحيل, الفصول الدراسية والاسابيع (#1),
   البرامج التعليمية الخاصة (#2 — note: `manage.special-education.index` EXISTS but card wants a
   "coming soon" here; follow the card → placeholder), التعليم عن بعد (#2), الاختبارات (#3 — note real
   exams route exists elsewhere; card wants placeholder text on THIS item), النقاط والمكافأت (#4),
   الإرشاد الطلابي (#4). Point all at one shared placeholder. Prefer a parametric route
   `Route::view('/soon/{slug?}', 'programs.coming-soon')` OR just `href="#"` + the existing
   coming-soon page — decide in Task 1 after reading the existing view.
5. **صفوف وفصول / الترحيل / الفصول والأسابيع** exist only nested under `admin.schools.*`. For #1, link
   صفوف وفصول to the existing classes listing if a top-level one exists; else placeholder. الترحيل +
   الفصول والأسابيع → placeholder (no top-level route).
6. **إدارة المواد / إدارة الدرجات / المكتبات / التقارير / رسائل الجوال** are has-sub groups — replicate
   the existing sub-structure (same child routes) inside the new section.

## Risk / verification
- One 1553-line file, delicate. Edit in additive blocks; never touch existing lines except the #543–545
  swap. After edit: `php artisan view:clear`, load a page locally, expand each new section, click through
  every link, confirm no 500 and existing nav intact. Screenshot before/after.

## Update 2026-10-22 (card #359) — duplicates removed
QA confirmed the six sections and asked to drop every page that now lives in a new section from its
old place. Admin sidebar now lists each page once: the old «عمليات التواصل» section is gone (all its
items moved), «العمليات التعليمية» keeps only pages not re-homed (الجداول، ملف الإنجاز، التربية الخاصة،
المواعيد، تقارير التقييم، الحضور والغياب، QR، غياب المعلمين), «إعدادات النظام» keeps السياسات،
المستخدمون (without الطلاب/أولياء الأمور)، الأعوام الدراسية، السلوك. المسارات / الأسابيع / المعايير /
المجمعات moved into the new «إدارة المواد» group. The standalone التقويم link is hidden for admins.
