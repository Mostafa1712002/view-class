# Requirements: Sprint 5 — الخطة الأسبوعية + الدرجات + التقارير + الهوية الجديدة

## Overview
Sprint 5 covers a full rebrand from "ViewClass / فيوكلاس" to **"الأول"** (Al-Awwal — "The First"), a luxury gold/black theme overhaul, and three new modules (Weekly Plan, Grade Management, Reports).

Trello cards under list `sprint prompt`:
- `69f0ab0c83d485ba2a76d8e3` — Sprint 5 (parent epic)
- `69f1bed45a88c2f497031ec5` — الهوية (Identity / Theme)
- `69f1c06896f23a7a04479e21` — الاسم (Rebrand)
- `69f1c3726eacc5142a725169` — ثانياً: إدارة الدرجات
- `69f1c3d8bfd66ac3f4a9558f` — ثالثاً: التقارير

The Sprint 5 parent description also covers **أولاً: الخطة الأسبوعية** (Weekly Plan), but no separate atomic card exists yet.

This sprint is sliced into **two delivery rounds**:
- **Slice 1 (this round)**: الاسم (rebrand) + الهوية (theme) — low-risk, single-day, ships independently.
- **Slice 2 (next round)**: Weekly Plan + Grade Management + Reports — multi-day, depends on slice 1 brand tokens.

## User Stories — Slice 1

### US-501: Rebrand to "الأول"
**As a** product owner
**I want to** rename the platform from "ViewClass / فيوكلاس" to "الأول" / "Al-Awwal" everywhere users see it
**So that** the platform reflects the new brand identity.

**Acceptance Criteria:**
- WHEN any authenticated or guest user visits any page THE SYSTEM SHALL display the new name in the navbar brand text, page title, footer, and login screen.
- WHEN locale is `ar` THE SYSTEM SHALL show "الأول".
- WHEN locale is `en` THE SYSTEM SHALL show "Al-Awwal".
- WHEN the user views the navbar logo THE SYSTEM SHALL show the new RAWANI logo PNG instead of the previous 80×80 placeholder.
- WHEN looking at Sprint 4 question-bank library and subjects-template features THE SYSTEM SHALL refer to "منصة الأول" / "Al-Awwal templates" (not "ViewClass").
- THE SYSTEM SHALL NOT change technical strings (route prefixes, env keys, DB names, internal `@viewclass.local` email fallbacks) — those are internal and unrelated to user-facing brand.

### US-502: Apply gold/black luxury theme
**As a** user of "الأول"
**I want to** see a luxury gold-and-black aesthetic
**So that** the platform looks premium and matches the new brand.

**Acceptance Criteria:**
- THE SYSTEM SHALL expose CSS custom properties matching the brand spec:
  - `--gold-100..500` (#f6d27a → #9c6b1f)
  - `--black-100..300` (#0b0b0b, #121212, #1a1a1a)
  - `--white-100..300` (#ffffff, #f5f5f5, #dcdcdc)
  - `--text-primary` = white, `--text-secondary` = `#a1a1a1`
- WHEN locale is `en` THE SYSTEM SHALL use Playfair Display serif for headings and brand text.
- WHEN locale is `ar` THE SYSTEM SHALL keep Cairo as the default Arabic font.
- THE SYSTEM SHALL provide a `.btn-gold` class with linear gradient (`--gold-200` → `--gold-500`), white text, hover lift + gold-tinted shadow.
- THE SYSTEM SHALL override `.btn-primary` to use the gold-gradient style so existing buttons inherit the new brand without a sweep edit.
- WHEN viewing the navbar THE SYSTEM SHALL use a gold gradient background instead of the previous `bg-info` blue.
- WHEN viewing the active sidebar item THE SYSTEM SHALL use a gold-gradient highlight instead of blue.
- WHEN visiting the login screen THE SYSTEM SHALL use a gold→black gradient backdrop and gold-gradient submit button.
- THE SYSTEM SHALL preserve sidebar section indicator colors (purple/blue/orange/green) since those are functional category markers, not brand color.

## User Stories — Slice 2 (deferred to next round)

### US-503: Weekly Plan view
**As a** school admin
**I want to** see a weekly plan grid filtered by stage/grade/class/teacher with PDF/Excel export
**So that** I can review what teachers are scheduled to cover.

(Acceptance criteria deferred — full breakdown lives in the Sprint 5 parent card description.)

### US-504: Grade Management
**As a** school admin
**I want to** create three kinds of grade reports (Dynamic, Static, Gradesheet)
**So that** the school can publish grades to students and parents.

(Acceptance criteria deferred to slice 2 spec.)

### US-505: Reports module
**As a** school admin
**I want to** view administrative, statistical, and per-user reports
**So that** I can monitor school-wide performance and individual teacher/student activity.

(Acceptance criteria deferred to slice 2 spec.)

## Non-Functional Requirements

### NFR-501: Backwards compatibility
- THE SYSTEM SHALL NOT break any Sprint 1–4 page during the rebrand. All previously-passing flows continue to work.
- Sprint 4 lang keys keep the same key path; only the value changes.

### NFR-502: Performance
- THE SYSTEM SHALL keep the new logo asset under 100KB.
- Theme CSS is added inline in `app.blade.php` `<style>` block (no new asset files) to avoid an extra HTTP request.

### NFR-503: Accessibility
- Gold-on-dark text contrast ≥ AA (4.5:1) on the gold-300/400 hue against black-100.
- Login button text remains white-on-gold (passes contrast).
