# Design: Redesign Slice 1

## Approach

Theme is **opt-in via body class** (`body.theme-luxury`). Slice 1 dashboards + login set the class; every other page in the app stays on the existing light theme until later slices restyle them. This keeps blast radius small and lets us ship per-slice without breaking modules we have not visited yet.

## Token surface

Tokens already exist in `layouts/app.blade.php` (added in earlier rebrand pass):

```
--gold-100..500   gradient highlights
--black-100..300  surface tones
--white-100..300  text + subtle separators
--brand-green     accent (used sparingly)
--radius-md: 10px
```

Slice 1 introduces no new tokens — it only consumes them via a new `.theme-luxury` scope.

## Theme rules (scoped to `.theme-luxury`)

| Surface | Treatment |
|---|---|
| `body.theme-luxury` | gradient: black → black-300 + radial gold highlight at 20%/80% |
| `.theme-luxury .card` | `background: rgba(255,255,255,.03)`; `backdrop-filter: blur(10px)`; border `rgba(207,160,70,.18)` |
| `.theme-luxury .card-text`, `p`, `small` | `color: var(--text-secondary)` (#a1a1a1) |
| `.theme-luxury h1..h4` | `color: var(--white-100)` |
| `.theme-luxury .breadcrumb`, `.breadcrumb-item` | light text, gold active |
| `.theme-luxury .table thead th` | `background: rgba(255,255,255,.04)`; light text |
| `.theme-luxury .table tbody tr` | borders in `rgba(255,255,255,.06)` |
| `.theme-luxury .stat-value` | gold tint (`color: var(--gold-200)`) |

KPI cards inside dashboards use a new helper class `.luxury-stat` (just a wrapper that gives the number the gold tint and adds a thin gold underline).

## Login page

Already partially branded. Upgrade from "white card on dark gradient" to "glass card on dark gradient":

- `.auth-card` background → `rgba(20,20,28,.55)` with `backdrop-filter: blur(18px)`.
- Form labels and helper text shift to white / `var(--text-secondary)`.
- Input fields stay light (white background) for legibility — they are the focal interaction surface.
- reCAPTCHA mock keeps its existing look (light pill).

## Body class plumbing

Layout (`resources/views/layouts/app.blade.php`):
- `<body class="... @yield('body_class')">` so each view can opt into `theme-luxury`.

Slice 1 views set:
```blade
@section('body_class', 'theme-luxury')
```

## File-level changes

| File | Change |
|---|---|
| `resources/views/layouts/app.blade.php` | Add `@yield('body_class')` to `<body>`; add `.theme-luxury` style block |
| `resources/views/auth/login.blade.php` | Convert auth-card to glass; recolor labels/headings |
| `resources/views/dashboard.blade.php` | Add `@section('body_class', 'theme-luxury')` |
| `resources/views/student/dashboard.blade.php` | Same |
| `resources/views/parent/dashboard.blade.php` | Same |

No controllers, routes, migrations, JS bundles touched.

## Verification matrix

| Role | URL | Viewport |
|---|---|---|
| Admin | `/dashboard` | 1440, 375 |
| Manager | `/dashboard` | 1440, 375 |
| Teacher | `/dashboard` | 1440 |
| Student | `/student/dashboard` | 1440 |
| Parent | `/parent/dashboard` | 1440 |
| Login | `/login` | 1440, 375 |

Each verification: page loads 200, no JS console errors, glass cards visible, dark page bg, gold accents on numbers.
