# Tasks: Sprint 5

## Slice 1 — الاسم + الهوية (this round) ✅

### Task 1.1: Lang file rebrand
- [x] Update `lang/ar/auth.php:15` → `'app_name' => 'الأول'` + tagline → "منصة تعليمية متكاملة"
- [x] Update `lang/en/auth.php:15` → `'app_name' => 'Al-Awwal'` + tagline → "Integrated Educational Platform"
- [x] Update `lang/ar/sprint4.php` — 3 strings to use "الأول" / "منصة الأول"
- [x] Update `lang/en/sprint4.php` — 3 strings to use "Al-Awwal"

**Outcome:** ✅ Every `@lang('auth.app_name')` call now returns the new brand name.

---

### Task 1.2: Logo assets
- [x] Save full RAWANI logo to `public/img/brand/al-awwal-logo.png`
- [x] Regenerate `public/app-assets/images/logo/logo.png` + variants from RAWANI source
- [x] Replace favicon

**Outcome:** ✅ Navbar logo, login logo, browser tab favicon all use new RAWANI image.

---

### Task 1.3: APP_NAME env
- [x] `.env` `APP_NAME="Al-Awwal"`
- [x] `.env.example` matches

**Outcome:** ✅

---

### Task 1.4: Brand tokens + theme overrides
- [x] `:root` CSS variables (gold-100..500, black-100..300, white-100..300, --brand-green)
- [x] Override `.bg-info` → black→gold linear-gradient
- [x] Override `.btn-primary` (idle + hover) → gold gradient
- [x] New `.btn-gold` utility
- [x] Active sidebar → gold gradient
- [x] Playfair Display imported for `en` locale, applied to headings + brand text

**Outcome:** ✅ Shell is gold/black. Section indicator colours preserved.

Verified live computed values:
- `--gold-500` = `#9c6b1f`
- navbar bg = `linear-gradient(135deg, rgb(18,18,18) 0%, rgb(26,26,26) 60%, rgb(156,107,31) 130%)`
- active sidebar bg = gold gradient `rgb(207,160,70)→rgb(227,184,92)`
- `.brand-text` color = `rgb(227,184,92)` (gold-200)

---

### Task 1.5: Login screen rebrand
- [x] Backdrop: gold/green radial accents over black-100→black-300 gradient
- [x] Brand SVG → `<img>` of RAWANI logo
- [x] `.btn-primary` → gold gradient
- [x] Focus glow → gold
- [x] Subtitle now reads `auth.tagline` ("منصة تعليمية متكاملة")

**Outcome:** ✅

---

### Task 1.6: Live verify
- [x] Login page title "تسجيل الدخول — الأول" ✓
- [x] Dashboard after login title "لوحة التحكم — الأول" ✓
- [x] Question Bank library title "مكتبة بنوك الأسئلة (منصة الأول) — الأول" ✓
- [x] Computed CSS variables + navbar gradient + active-sidebar gradient verified via JS eval
- Note: Playwright screenshot timed out repeatedly during font-load (Playfair+Cairo from CDN); used accessibility snapshot + computed-style readback for evidence instead.

---

### Task 1.7: Commit + push
- [x] Commit `d9fdfeb feat(sprint5): rebrand viewclass → الأول (Al-Awwal) + gold/black theme`
- [x] Pushed to origin/main
- Production server pull: deferred — repo has no prod-server SSH config recorded; QA team can pull when reviewing.

---

### Task 1.8: Trello close-out
- [x] الاسم (69f1c06896f23a7a04479e21) → testing prompt + Arabic comment + reassigned to mahmoud yasser
- [x] الهوية (69f1bed45a88c2f497031ec5) → testing prompt + Arabic comment + reassigned to mahmoud yasser
- [x] Removed self from both
- [x] Sprint 5 parent + grades + reports remain in sprint prompt for slice 2

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
| Slice 1: Rebrand + Theme | 8 | 8 | ✅ Shipped + on Trello QA |
| Slice 2: Modules | 3 | 0 | Deferred (next round) |
| **Total Slice 1** | **8** | **8** | **100%** |
