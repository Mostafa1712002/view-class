# Spec: بنك الأسئلة — General / Private restructure

## Status: DRAFT — awaiting user confirmation before build
Card: بنك الأسئلة (`/admin/question-banks`). Product rule (user-confirmed 2026-06-09): **general banks are company-wide, admin-curated, all schools in the company can read/use, approved questions only.**

## Key finding — the data model already supports most of this
`question_banks` columns: `school_id`, `visibility` (public|private), `status` (active|inactive|under_review|archived), `source`, `category_type`, `is_ana_qudurat_linkable`, `is_library`, `created_by`, `imported_by`, `metadata`, external_* (ana-qudurat link prep). Plus a `question_bank_schools` pivot (`belongsToMany School`) — **per-school sharing already modelled**; empty pivot on a public bank = platform/company-wide. `BankQuestion` holds questions.

So this is **mostly UI/flow + scoping rules**, NOT a schema rebuild. Minimal/no migrations.

## Required changes
1. **General vs Private surfacing (Task 1 of card).**
   - Index `/admin/question-banks`: two clear sections/tabs/filter — **عام (general)** vs **خاص (private)** — driven by `visibility`. Show owner scope (company / school), status (approved/under-review), question count, category, ana-qudurat-linkable flag.
   - General bank = `visibility=public`; scoped to the **company** (the creating admin's `educational_company_id`); readable by every school in that company. Private = `visibility=private` + a `school_id`.
2. **Company-wide curation + approved-only.**
   - Only super-admin / company-admin can create/edit a **general** bank and approve its questions. A general bank exposes to schools only its **approved** questions (status active + question-level approved flag if present on `bank_questions`; if not, use bank `status=active`). Surface an approve/review action.
   - School admins manage their own **private** banks freely.
3. **Sharing controls.** On a general bank, control which schools see it via `question_bank_schools` (all-in-company by default; optional subset). UI to manage the school list.
4. **Convert private→ (general) by permission** (card: "تحويل بعض الأسئلة منه إلى بنك خاص حسب الصلاحية" + reverse): an action (super-admin) to promote a private bank/questions to a general bank, and to copy general questions into a school's private bank.
5. **Ana-qudurat readiness.** Keep `is_ana_qudurat_linkable` + category_type (qudurat/verbal/quantitative/speed_reading) so a later "أنا والقدرات" link needs no rebuild. No new build now beyond keeping the fields wired.
6. **`content-header row` sweep (card's opening line: "نفذ تصميم class=content-header row في كل صفح الموقع").** Audit admin pages whose header isn't the standard `content-header row` and normalise them (at least the question-banks pages + obvious offenders). This is broad — scope to question-banks pages now + list other offenders for a follow-up, rather than touch every page blindly.

## Multi-tenant
Repository (`QuestionBankRepository`) enforces scope: a school sees its own private banks + general banks of its company (via visibility=public + company match / pivot). Super-admin sees all.

## Tasks (Sonnet build)
- [x] T1 Repository: scoped queries for general(company)+private(school); approved-only exposure to schools.
- [x] T2 Index UI: general/private tabs/filter + columns (scope, status, count, category, linkable) + content-header row.
- [x] T3 Create/edit: general (company, super-admin) vs private (school); status/approve action; ana-qudurat fields.
- [x] T4 Sharing UI: manage `question_bank_schools` for a general bank.
- [x] T5 Convert/copy actions (promote private→general; copy general→private) by permission + audit.
- [x] T6 content-header row on question-banks pages; list other non-standard pages for follow-up.
- [x] Verify locally (Playwright + DB), commit, deploy, hand to QA.

## Completed 2026-06-09

## Open question for build
Does `bank_questions` have a per-question approval/status column, or is approval at the bank level (`status`)? Build agent verifies and uses whichever exists (prefer bank-level if no question-level flag).
