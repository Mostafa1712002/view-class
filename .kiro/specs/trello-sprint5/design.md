# Design: Sprint 5 Slice 1 — Rebrand + Theme

## Strategy

The platform already has a single source of truth for the user-facing brand text:
the lang key `auth.app_name`. Every brand string in views (`navbar`, `app.blade.php`
title/meta, `login.blade.php`, footer) calls `@lang('auth.app_name')`.

Therefore the rebrand is **2 lang-file edits** + **3 string edits in `sprint4.php`**
+ **logo file replacement** + **APP_NAME env update**. No view sweep needed.

The theme overhaul is contained in the existing `<style>` blocks of:
- `resources/views/layouts/app.blade.php` (shell)
- `resources/views/auth/login.blade.php` (login)

We add CSS custom properties at `:root`, then override the few brand-driven
selectors (`.bg-info` on the navbar, `.btn-primary`, `.main-menu .navigation > li.active > a`,
the login background gradient, `.btn-primary` on login).

## Brand Tokens

```css
:root {
  /* Primary — gold */
  --gold-100: #f6d27a;
  --gold-200: #e3b85c;
  --gold-300: #cfa046;
  --gold-400: #b7842e;
  --gold-500: #9c6b1f;

  /* Neutral */
  --black-100: #0b0b0b;
  --black-200: #121212;
  --black-300: #1a1a1a;

  --white-100: #ffffff;
  --white-200: #f5f5f5;
  --white-300: #dcdcdc;

  /* Accent (logo also uses dark green for the "ا" letter) */
  --brand-green: #1f6f4a;

  --text-primary: var(--white-100);
  --text-secondary: #a1a1a1;
}
```

## Affected Files

| File | Change |
|------|--------|
| `lang/ar/auth.php:15` | `'app_name' => 'الأول'` |
| `lang/en/auth.php:15` | `'app_name' => 'Al-Awwal'` |
| `lang/ar/sprint4.php` | 3 strings: `add_template`, `viewclass`, `library_title` use "الأول" |
| `lang/en/sprint4.php` | 3 strings: same — use "Al-Awwal" |
| `.env` + `.env.example` | `APP_NAME=al-awwal` (slug for things like log file names) |
| `public/app-assets/images/logo/logo.png` | Replace with RAWANI logo (resized so PNG fits 80×80 box; we ship full-res, CSS scales to 32px in navbar) |
| `public/app-assets/images/logo/logo-light.png`, `logo-dark.png`, etc. | Replace with same RAWANI logo (single asset for all variants this round) |
| `resources/views/layouts/app.blade.php` | Add `:root` CSS vars, override `.bg-info` (navbar), `.btn-primary`, active-sidebar |
| `resources/views/auth/login.blade.php` | Replace purple gradient + button with gold/black |

## Logo Sizing

The RAWANI source PNG is 77KB at native resolution. We:
1. Save the original to `public/img/brand/al-awwal-logo.png` (full-res, for printable headers/PDF in slice 2).
2. Generate an 80×80 cropped/scaled-down version overwriting `public/app-assets/images/logo/logo.png` so existing `<img>` tags continue to point at the same path. Use `convert` (ImageMagick) — or, if not available, copy the full PNG and let CSS scale (height: 32px in navbar already does this).

## Risk Register

| Risk | Mitigation |
|------|------------|
| Logo aspect ratio doesn't match 80×80 box | Existing CSS uses `height: 32px` only; aspect-fit handled by `<img>` natural ratio. No mitigation needed. |
| Sprint 4 lang sweep breaks existing test data | Only changing values, not keys — no break possible. |
| `.btn-primary` override clashes with feedback/error buttons elsewhere | We override only the green/blue gradient currently used; `.btn-success`, `.btn-danger`, `.btn-warning` untouched. |
| Section header colors (purple/blue/orange/green) clash with new gold | They're functional indicators, not brand. We keep them. |
| Login `linear-gradient(135deg, #667eea, #764ba2)` is hardcoded | Single edit. |

## Out of Scope (slice 1)

- Translating "viewclass" out of route prefixes (`/admin`, `/api/...`) — those are URLs, not brand
- Renaming `database/migrations/*viewclass*` — none exist
- Replacing `@viewclass.local` email fallback — internal placeholder, not user-facing
- Changing favicon (would need designer SVG; defer)
- Print/PDF header rebranding (defer to slice 2 weekly-plan PDF work)
