# Tasks: Sprint 5

## Slice 1 — الاسم + الهوية (this round)

### Task 1.1: Lang file rebrand
- [ ] Update `lang/ar/auth.php:15` → `'app_name' => 'الأول'`
- [ ] Update `lang/en/auth.php:15` → `'app_name' => 'Al-Awwal'`
- [ ] Update `lang/ar/sprint4.php` — 3 strings to use "الأول" / "منصة الأول"
- [ ] Update `lang/en/sprint4.php` — 3 strings to use "Al-Awwal"

**Outcome:** Every `@lang('auth.app_name')` call now returns the new brand name.

---

### Task 1.2: Logo assets
- [ ] Save full RAWANI logo to `public/img/brand/al-awwal-logo.png` (for future PDF/print)
- [ ] Replace `public/app-assets/images/logo/logo.png` (and dark/light variants) with RAWANI logo

**Outcome:** Navbar and any other `<img src="...logo.png">` tags show the new logo.

---

### Task 1.3: APP_NAME env
- [ ] Update `.env` `APP_NAME=al-awwal` (slug, used in log file naming)
- [ ] Update `.env.example` to match

**Outcome:** Backend log/cache files prefixed with new app slug.

---

### Task 1.4: Brand tokens + theme overrides
- [ ] Add `:root` CSS variables (gold-100..500, black-100..300, white-100..300, --brand-green) inside `resources/views/layouts/app.blade.php` `<style>` block
- [ ] Override `.bg-info` (navbar background) → linear-gradient gold-300 → gold-500
- [ ] Override `.btn-primary` (and `.btn-primary:hover`) → gold gradient
- [ ] Provide new `.btn-gold` utility class
- [ ] Override active sidebar item → gold gradient highlight (replace existing blue gradient)
- [ ] Add Playfair Display import for `en` locale; keep Cairo for `ar`
- [ ] Apply Playfair Display to `.brand-text`, `h1`-`h6` for `en` locale

**Outcome:** Shell appears in gold/black; sidebar active state is gold; sidebar section headers (purple/blue/orange/green) stay as functional category markers.

---

### Task 1.5: Login screen rebrand
- [ ] Replace purple gradient `#667eea → #764ba2` with gold-300 → black-200 gradient
- [ ] Replace `.brand-logo h2` purple with gold (`--gold-400`)
- [ ] Replace `.btn-primary` overrides with gold gradient
- [ ] Replace `.form-control:focus` purple shadow with gold

**Outcome:** Login screen matches the new brand.

---

### Task 1.6: Live verify (local)
- [ ] Boot `viewclass.test` if not running
- [ ] Playwright: open `/login`, screenshot — verify gold gradient + new logo
- [ ] Playwright: log in as developer@midade.com, screenshot dashboard — verify gold navbar + logo + brand text "الأول"
- [ ] Playwright: open Question Bank library — verify "مكتبة بنوك الأسئلة (الأول)" not "(فيوكلاس)"

---

### Task 1.7: Commit + deploy
- [ ] `git add` + commit conventional msg: `feat(sprint5): rebrand to الأول + gold/black theme (slice 1)`
- [ ] Push to origin/main
- [ ] SSH to prod (if applicable), `git pull`, `view:cache`, smoke-test live URL

---

### Task 1.8: Trello close-out
- [ ] Move الاسم (69f1c06896f23a7a04479e21) to testing prompt + Arabic comment + assign creator (mahmoud yasser)
- [ ] Move الهوية (69f1bed45a88c2f497031ec5) to testing prompt + Arabic comment + assign creator
- [ ] Remove self from both
- [ ] Sprint 5 parent + grades + reports stay in sprint prompt for slice 2

---

## Slice 2 — Weekly Plan + Grades + Reports (next round, deferred)

### Task 2.1: الخطة الأسبوعية (Weekly Plan)
- [ ] Spec out (no card yet — derived from Sprint 5 parent description)

### Task 2.2: ثانياً: إدارة الدرجات
- [ ] Three report types: Dynamic / Static / Gradesheet

### Task 2.3: ثالثاً: التقارير
- [ ] Three categories: Admin / Statistical / User

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| Slice 1: Rebrand + Theme | 8 | 0 | Not Started |
| Slice 2: Modules | 3 | 0 | Deferred |
| **Total Slice 1** | **8** | **0** | **0%** |
