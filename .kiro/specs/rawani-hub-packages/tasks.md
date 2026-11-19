# Tasks: Rawani hub and packages

Read `design.md` first. The task ids are what agents use.

Rules for every implementer:
- **Repos.** Use only the repo named on the task.
  - Rawani work happens in this worktree (`feature/rawani-hub-packages`).
  - Sub-platform work branches off that platform's **live** ref (alawal `live-2026-11-19`, anawlqdrat `server-live-2026-07-31`, aafaq `main`), each in its own worktree.
- **Shared files.** Only the single **wiring task** of each repo touches that repo's shared files (route registrars, kernels, providers, sidebar, `.env.example`, `config/*`). Every other task creates new files only.
- **Tests.** Every task ships with its tests. On ViewClass, parallel agents each use their own test DB. Never run the anawlqdrat or aafaq suites against a dev DB that matters.
- **Commits.** Conventional commits with no AI attribution and no `Claude-Session:` trailer. Never commit secrets.
- **Ambiguity.** On an architectural or product ambiguity, stop and report back. Don't decide.
- **ClickUp.** Each phase gets a team-visible ClickUp card (Midade workspace) when work starts, per the user's global rule.
- **Live.** Deploy and SSH steps are done by the orchestrator, not implementer agents. Local first, live last.
- **One agent per sub-platform repo per phase, working sequentially.** Inside one sub-platform repo the tasks share hot files:
  - alawal: `routes/web.php` (0.1, 0.2)
  - aafaq: `api/public/index.php` (0.4, 0.5, 0.6, 2F.*)
  - anawlqdrat: the auth controllers (0.7, 2Q.*)

  So that repo's tasks run in order. Parallelism is across repos.

---

## Phase 0: Security prerequisites in the sub-platforms

The three repos are independent and can run in parallel.

### Task 0.1 (alawal): Close the forgot-password takeover (P0-AL-1)
- [x] Remove the public `forgot-password` → `verifyIdentity` → `reset-password/{token}` routes and their views/links
- [x] Change the login page link to «تواصل مع إدارة المدرسة»
- [x] Test: `POST /forgot-password` returns 404/405, and the admin temp-password flow still works

### Task 0.2 (alawal): Auth on the routes after the auth group, plus a login throttle (P0-AL-2, P0-AL-3)
- [x] Move `promotions/*` and `reports/*` inside `auth:web` and delete the debug routes
- [x] Add `throttle:5,1` on `POST /login`
- [x] Test: unauthenticated hits redirect to login, and the 6th bad login in a minute returns 429

### Task 0.3 (alawal): Enforce `students.is_active` (P0-AL-4)
- [x] Add the check to `LoginController::login`
- [x] Add a middleware on `auth:student` that logs out inactive students
- [x] Test

### Task 0.4 (aafaq): Close the open user endpoints (P0-AF-1, P0-AF-4)
- [x] **Precondition (orchestrator, read-only):** find how `cron/*.php` and `update_payments.php` actually run on live: crontab, an external URL ping with `?key=`, or not at all. If they are triggered by URL, the CLI-only guard must not ship until the trigger is moved to a crontab CLI entry.
- [x] Grep `afaq_reading`, `afaq_abilities_app` and the aafaq web pages for callers of each endpoint, and record the findings in the PR body
- [x] `getAllStudents` and `deleteStudentByEmail` require an admin session or are removed
- [x] `getStudentData` and `updateProfile` require credential material, or return reduced fields where the apps can't send it
- [x] Use prepared statements in `register`, `editedRegister` and `forgetPass`
- [x] Add a CLI-only guard on `update_payments.php` and `cron/*.php` (P0-AF-5)
- [x] Add a PHP CLI test script under `tests/` that drives these endpoints against a local DB copy

### Task 0.5 (aafaq): Webhook site filter (P0-AF-3)
- [x] `moyasar-webhook.php`: if `metadata.site` is set and is not aafaq's, record `ignored` in `payment_events` and return 200 without applying
- [x] Test with a recorded payload of each kind
- [x] **Must be live before Task 3.x goes live**

### Task 0.6 (aafaq) — NOT IMPLEMENTED: no RevenueCat secret; risk R9 stays open (updatePaymentStatus can still unlock any email): Decide and implement the `updatePaymentStatus` check (P0-AF-2)
- [x] If a RevenueCat secret is available, verify the app user's entitlement server-side before setting the flag
- [x] Otherwise document risk R9 and stop

### Task 0.7 (anawlqdrat): Make blocking mean blocked (P0-AQ-1..3)
- [x] **Precondition (done 2026-11-19, live read-only):** 940 users are `status='inactive'`, not blocked, not deleted. Decision: limit the rule to `blocked`. `inactive` means **"pending admin review"** and is NOT refused at login or on the API.
- [x] Login already refused `blocked` (`LoginWithTemporaryPasswordAction`); `inactive` stays allowed
- [x] Revoke tokens on block, admin delete and self-delete (`User::booted()` hooks: `saved` when `blocked` flips on, `deleted`)
- [x] `EnsureApiUserNotBlocked` in the `api` middleware group returns 401 for blocked users (covers tokens issued before the fix and the password-reset token). Soft-deleted users already fail `auth:api` (default scope).
- [x] Optional: unregister the MyFatoorah demo routes. NOT done: the vendor package registers them in `register()`; removing needs `dont-discover` in composer.json plus a composer step on deploy.
- [x] Feature tests (`tests/Feature/Auth/BlockedUserTokenTest.php`)
- Branch `security/phase0-2026-11-19` in `~/www/anawlqdrat-wt-phase0` (not pushed).

**Outcome:** the known takeover, PII and blocking holes are closed live on all three platforms.
**Dependencies:** none. Deploy notes are in design §7.

---


**Deployed live 2026-11-19 (patch-applied on the Tarweig VPS, backups /root/backups/phase0-*):** alawal security/phase0-2026-11-19 @c40ece6, aafaq @5d5be34 (worktree ~/www/aafaq-wt-phase0), anawlqdrat @4740a8e1 (worktree ~/www/anawlqdrat-wt-phase0, dashboard.anawlqdrat.com). Smoke: alawal forgot 404, promotions unauth 302, debug 404, logins 200; aafaq cron/update_payments URL 403, getAllStudents w/o auth refused; anawlqdrat dashboard 302 OK (pre-existing: /api/user uses undefined guard user-api → 500, untouched).

## Phase 1: Rawani hub core (viewclass repo, this worktree)

Tasks 1.1–1.7 can run in parallel, and only 1.8 touches shared files. Until 1.8 lands, test with routes registered inside the test (`Route::middleware('web')->group(base_path('app/Modules/RawaniHub/Routes/web.php'))`).

### Task 1.1: Migrations and models
- [x] Migrations for every table in design §3.1 (`2026_11_2x_*`)
- [x] Models with casts:
  - `encrypted` on `hub_platforms.secret` and `hub_platform_accounts.password`
  - json on `products`, `capabilities`, `required_fields`, `items`, `access` and `payload`
- [x] Relations: `HubSubscription->user/buyer/package`, `HubPlatformAccount->platform/user`
- [x] Test: the migration runs on a fresh DB and the uniques hold, including two NULL `user_id` rows on one platform

### Task 1.2: Repositories
- [x] Contracts plus Eloquent implementations for Package, Subscription, Payment, Platform and PlatformAccount (design §3)
- [x] Bindings go to Task 1.8; list them in the task report

### Task 1.3: `PlatformClient` + `FakePlatform`
- [x] `PlatformClient` covering ping, putAccount, sso and people:
  - HMAC per design §4.1, signing the logical path and the sorted query
  - `Http::timeout(15)`
  - maps HTTP status and error code to a typed result (ok, conflict, invalid, retryable, unsupported, auth)
  - never logs the body
- [x] `tests/Support/FakePlatform.php`: `Http::fake` responders that implement the contract in memory (accounts by role and rawani_id, 409 on a preset natural key, programmable failures)
- [x] Unit test: the shared signature vector reproduces exactly

### Task 1.4: `EntitlementCalculator` + `AccountState` builder
- [x] Windows per design §3.2, plus required-field checks that return the missing list
- [x] Parent rows (platforms with `parents=true`) carry no entitlements
- [x] Unit tests: overlap, stacking, expired, refunded, two products, parent rows

### Task 1.5: Sync job and commands
- [x] `SyncPlatformAccountJob`: unique per account, tries 6, backoff [60,300,900,3600,21600], state transitions per design §3.3, credential reuse per §3.4
- [x] `hub:sync-pending` and `hub:expire` (marks expired subscriptions and dirties their accounts)
- [x] Tests on `FakePlatform` covering every transition, including needs_info with no HTTP call and a disabled platform being skipped

### Task 1.6: Actions: packages, grant, cancel, account admin
- [x] `CreatePackage`, `UpdatePackage`, `GrantPackage` (multi-beneficiary, stacking), `CancelSubscription`, `FindOrCreateBeneficiary` (default school, parent link, national-ID collision rule)
- [x] `ResetAccountPassword`, `SetAccountEnabled`
- [x] Every action writes an ActivityLog entry and dispatches after commit
- [x] Tests

### Task 1.7: Admin screens: platforms, packages, subscriptions/grant, person page
- [x] Controllers, form requests and Blade views under `resources/views/hub/` in the existing admin layout
- [x] Arabic labels per design §3.5
- [x] Platform «اختبار الاتصال» and «توليد السر» (shown once)
- [x] Person page: accounts with status, last sync, error, credentials reveal and the retry, enable/disable and reset actions
- [x] `lang/{ar,en}/hub_admin.php` with flat keys (other tasks own `hub_my`, `hub_payments` and `hub_directory`)
- [x] Access tests (super-admin 200; school-admin, parent and student 403) and form tests

### Task 1.8: Wiring (the only task that edits shared files)
- [x] `config/rawani_hub.php` and `.env.example` (`RAWANI_HUB_ENABLED`, `RAWANI_HUB_SCHOOL_ID`)
- [x] Conditional route `require` in `routes/web.php` (api.php: no hub API routes exist until the webhook task)
- [x] Conditional schedules in `routes/console.php`
- [x] Repository bindings in `RepositoryServiceProvider`
- [x] Sidebar: الباقات، الاشتراكات، المنصات المرتبطة behind `Route::has` (المدفوعات/الدليل/باقاتي follow their routes)
- [x] Test: flag off gives 404 on every hub route and no hub schedule. Flag on gives a working sidebar.

### Task 1.9: Review + local E2E
- [x] Opus or `/code-review` pass on the Phase 1 diff (2026-11-19). Fixed: package toggle/delete through audited Actions; a password reset landing mid-call is no longer cleared; `uniqueFor` 8 h on the sync job; enabled platform without a secret is skipped; audit rows on every `conflict`/`failed` transition; `@viewclass.local` placeholder sent as `email: null`; duplicate input id on the grant page. **Resolved 2026-11-19 (follow-up commits):** (1) 401 now settles `failed` at once, no retry; (2) `IDENTIFIER_TAKEN` is `conflict`, design §3.3 fixed; also disable/reset release the unique lock and dispatch fresh, the conflict form is fully visible and pre-filled, and a new parent needs an explicit gender. Original wording: (1) the code and its test retry a 401 with backoff, but §3.3 and §4.2 say it settles `failed` at once; (2) 409 `IDENTIFIER_TAKEN` goes to `failed` per §3.3, while §4.2 says `conflict` — the spec contradicts itself.
- [x] Locally, with the flag on: create a platform pointing at a local contract stub, create a package, grant it to a new student with a parent, see the accounts go `active`, cancel, and see the entitlement push. Also run: secret shown once, test connection, reset password (pushed once, flag cleared), disable/enable, 409 → «ربط بعد المراجعة» (PUT carries `link_external_id`, no password), `hub:expire` → entitlements `[]`. All 13 stub requests verified HMAC + nonce. No secret or password in the logs, jobs, failed_jobs or activity_logs.

**Outcome:** packages and free grants work end to end against a fake platform. The code is deployable to both instances.
**Dependencies:** none (1.8 after 1.1–1.7 merge).

---

## Phase 2: Contract on each sub-platform (parallel, one agent per repo)

Each repo does its feature tasks (x.1–x.3) in parallel and then one wiring task (x.4). Each repo also receives a copy of design §4 and §5.

### Task 2A.1 (alawal): Migrations and placement
- [x] Nullable unique `rawani_user_id` on `students` and `my__parents`
- [x] `is_active` on `my__parents`
- [x] `rawani_disabled` on both
- [x] `rawani_access_until` on `students`
- [x] `rawani_placements` table and `rawani_sso_tokens` table

### Task 2A.2 (alawal): Signature middleware + endpoints
- [x] `ping`, `PUT accounts/{role}/{id}` (parent upsert first, student placement, restore trashed, 409 rules, link rules), `GET people`
- [x] Use the design §4.2 envelope
- [x] Add the login gate `is_active AND (not managed OR rawani_access_until >= now)` and the parent `is_active` check
- [x] Feature tests per design §9, including the shared vector

### Task 2A.3 (alawal): SSO
- [x] `POST accounts/{role}/{id}/sso` and `GET /rawani/sso/{token}` (single use, 60 s, hashed, re-runs the login gates)
- [x] Tests

### Task 2A.4 (alawal): Wiring
- [x] Register the middleware alias in `Kernel`, routes in `routes/api.php` + `web.php`, `.env.example` `RAWANI_HUB_SECRET(_PREVIOUS)`

### Task 2Q.1 (anawlqdrat): Migrations
- [x] Nullable unique `users.rawani_user_id`
- [x] `users.rawani_disabled`
- [x] Unique `subscribes.rawani_ref`
- [x] `rawani_sso_codes` (or reuse `temporary_passwords` if it fits cleanly)
- [x] Every migration under a dedicated `--path`

### Task 2Q.2 (anawlqdrat): Signature middleware + endpoints
- [x] `ping`, `PUT accounts/student/{id}` (username `rw<id>`, 3-column collision check, verified and active, `subscribes` row tagged with `rawani_ref`, token revoke on disable or a past window), `GET people`
- [x] Routes in a new `app/Application/Api/routes/V1/Modules/Rawani/*.php`
- [x] Feature tests

### Task 2Q.3 (anawlqdrat backend): SSO
- [x] `POST …/sso`, then the public `POST user/auth/sso/exchange` (throttled, re-runs the login checks, logs `login_type='sso'`)
- [x] Tests

### Task 2Q.5 (anawlqdrat-front — built + pushed on branch rawani/sso, NOT copied to live dist yet, branch `deploy-symmetry-fix`): SSO route
- [x] Route `/sso`: exchange → `auth/attempt` → clear exam localStorage → `/`
- [x] Local build check. The dist is copied to live by hand by the orchestrator.

### Task 2Q.4 (anawlqdrat): Wiring
- [x] Require the Rawani route file in `Api/routes/api.php`
- [x] Middleware alias
- [x] `.env.example`

### Task 2F.1 (aafaq): Schema
- [x] Nullable unique `reg_students.rawani_user_id`, `is_active` default 1, `rawani_disabled`
- [x] `rawani_nonces` table
- [x] SQL migration file in the repo's existing style

### Task 2F.2 (aafaq): Bridge endpoints
- [x] Slim route group `/rawani/v1` with signature verification, using the logical path after `/rawani/v1` (works for both `/api/` and `/api/public/index.php/`)
- [x] `ping` and `PUT accounts/student/{id}`:
  - email rule and `passwordForStorage`
  - both `school` columns via `getDefaultSchoolId()`
  - one `subscriptions` row per product with `payment_id='rawani:<id>:<product>'`, then `applySubscriptionToUsers`
  - `[]` deactivates only those rows
- [x] `GET people` with no password columns
- [x] `sso` returns 501
- [x] `is_active` checked in `login`, `editedLogin`, `editedLogin2` and `checkUserPass`
- [x] PHP CLI test script covering design §9

### Task 2F.3 (aafaq): Wiring
- [x] Register the group in `api/public/index.php` (the only edit to that file beyond the login checks in 2F.2: coordinate by doing 2F.2's login edits here instead if they would conflict)
- [x] Document `rawani_hub_secret` in the gitignored `api/config/config.php` template/README

**Outcome:** all three platforms implement `rawani/v1` and pass the shared test vector.
**Dependencies:** Phase 0 of the same platform must be live before its bridge goes live.

---


**Deployed live 2026-11-19 ~15:20 UTC (patch-applied; backups /root/backups/{alawal,anaw,aafaq}-phase2-*, hub-link-*):** alawal rawani/phase2-api @354271f (migrate --path=database/migrations/rawani, rawani:setup-placement → «مجمع رواني» 6 schools / 36 placements); anawlqdrat backend rawani/phase2-api (migrate --path rawani; front /sso NOT deployed); aafaq rawani/phase2-api (SQL migrations/rawani_bridge.sql applied, reg_students 62,233 rows, all is_active=1). Secrets generated server-side (never printed) into alawal/anawlqdrat .env RAWANI_HUB_SECRET (+RAWANI_SPA_URL) and aafaq api/config/config.php; hub_platforms 1 alawal, 2 anawlqdrat, 3 aafaq enabled; signed ping = ok on all three. Branches pushed: aafaq + anawlqdrat `rawani/phase2-api`, front `rawani/sso`; alawal remote is read-only (code only in ~/www/alawal branch + live tree). aafaq default_school_id=5398 is not under any school/complex/company subscription → safe.

## Phase 3: Online purchase (viewclass repo)

### Task 3.1: `MoyasarGateway`
- [x] createInvoice, fetchInvoice, fetchPayment through `Http::withBasicAuth`
- [x] Verify the invoice field semantics against the Moyasar docs first and note the findings in design §6
- [x] Unit tests with `Http::fake`

### Task 3.2: Checkout + return + `ApplyPaidPayment`
- [x] `StartCheckout` (metadata `{site, rawani_payment_id}` only), the return page and `ApplyPaidPayment` (re-fetch, compare amount, currency and invoice, lock, apply once, stacking, afterCommit dispatch)
- [x] Tests

### Task 3.3: Webhook + reconcile
- [x] `MoyasarWebhookController`: hash_equals, strip the secret, record the event, site filter, match
- [x] `hub:reconcile-payments`
- [x] Tests per design §9

### Task 3.4: «باقاتي» (parent and student) + add child
- [ ] `lang/{ar,en}/hub_my.php`
- [ ] List, buy (child picker or self), add-child form (national ID required when the package includes Al-Awwal), return/status page polling account statuses, credentials view
- [ ] Tests, including another parent's child → 403

### Task 3.5: Payments admin + manual refund
- [ ] `lang/{ar,en}/hub_payments.php`
- [ ] Payments list with flags, «تحقق» (re-fetch) and «تسجيل استرداد»
- [ ] Tests

### Task 3.6: Wiring
- [ ] Webhook route in the hub `api.php` require
- [ ] `hub:reconcile-payments` schedule
- [ ] «باقاتي» route names in the sidebar
- [ ] Mark `.kiro/specs/moyasar-payments/tasks.md` Phase 2 as delivered by this spec

**Outcome:** paid purchases activate and provision without relying on the browser. Refunds are recorded.
**Dependencies:** Phase 1. Task 0.5 must be live on aafaq before live payments.

---

## Phase 4: Master directory (viewclass repo)

### Task 4.1: Mirror
- [x] `MirrorPlatformPeopleJob` + `hub:mirror`: nightly for all pages, plus a weekly pass that marks rows not seen as `missing`
- [x] Upsert by (platform, role, external_id) and fill the mirror columns
- [x] Never overwrite provisioning fields
- [x] Tests against `FakePlatform` paging

### Task 4.2: Directory screen + person page links
- [x] `lang/{ar,en}/hub_directory.php` (4.3 runs after 4.2 and reuses it)
- [x] Two tabs, filters and server pagination (design §3.5)
- [x] The person page shows the parent↔child links on both the Rawani and platform sides

### Task 4.3: Link / create-person / unlink
- [x] Candidates by national ID, then real email, then phone (`PhoneNormalizer`); never by name
- [x] Confirm-link sets `link_confirmed_by` and pushes `link_external_id`
- [x] Create a Rawani person from an external row
- [x] Tests

Notes (4.1–4.3): `hub:mirror {--platform=} {--full} {--sync} {--per=}`; walk state on `hub_platforms.mirror_*` (migration 100400, also `platform_active` + index (platform_id, parent_external_id)). Teacher rows are view-only (contract writes students/parents). Unlink is Rawani-only: rawani/v1 has no detach, so the platform keeps its stored Rawani id and Rawani grants until they end. Phone candidates use `PhoneNormalizer` (digits only), so 05… and +9665… do not meet. Create-person does not attach parent↔child links.

### Task 4.4: Wiring
- [ ] `hub:mirror` schedule
- [ ] Sidebar item

**Outcome:** every platform person is visible in Rawani and linkable without auto-merge.
**Dependencies:** Phase 2 (for live data), Phase 1.

---

## Phase 5: SSO-lite in Rawani (viewclass repo)

### Task 5.1: «افتح المنصة»
- [ ] `IssueSsoLink` + `my/packages/accounts/{a}/open`: ownership check (self, or parent of the beneficiary); linked accounts need `link_confirmed_by`; a 501 shows the credentials fallback with the app links
- [ ] Tests

**Outcome:** one click from Rawani into alawal and anawlqdrat.
**Dependencies:** 2A.3, 2Q.3 and 2Q.5 live. 5.1 adds its strings to `hub_my.php`, so it runs after 3.4.

---

## Phase 6: Live rollout (orchestrator only, design §8)

- [ ] 6.1 Phase 0 deploys, one platform at a time, verified live
- [ ] 6.2 Rawani Phase 1 deployed to viewclass (flag off, 404 verified) and rawaniedu (flag on). Create the «رواني أونلاين» school there and set `RAWANI_HUB_SCHOOL_ID`. Confirm `QUEUE_CONNECTION=database`.
- [ ] 6.3 Each bridge live: set the secret, test the connection, test a grant, log in, cancel, and confirm access closed
- [ ] 6.4 Moyasar: `MOYASAR_SITE=rawani`, register the webhook, run a test mode end to end, then a 1 SAR live payment and refund it
- [ ] 6.5 First mirror run; compare the counts
- [ ] 6.6 SSO live
- [ ] 6.7 Trello card → testing list with an Arabic QA comment

---

## Progress Tracking

| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 0. Security prerequisites | 7 | 0 | Not started |
| 1. Rawani hub core | 9 | 9 | Done (local E2E 2026-11-19) |
| 2. Contract on sub-platforms | 12 | 0 | Not started |
| 3. Online purchase | 6 | 0 | Not started |
| 4. Master directory | 4 | 0 | Not started |
| 5. SSO-lite | 1 | 0 | Not started |
| 6. Live rollout | 7 | 0 | Not started |
| **Total** | **46** | **9** | **20%** |

Waiting on the client (design §11): Q1–Q8.
- Phases 1–2 can start without any answers.
- Phase 0 mostly can too, with three exceptions:
  - 0.1 removes a reset flow that is in use (926 tokens issued), so tell the client before it goes live. The replacement is Q6.
  - 0.6 needs the RevenueCat secret key.
  - 0.7 needs the prod count above.
- Q2 blocks the Al-Awwal live placement.
- Q1, Q7 and Q8 affect Phase 3 copy and rules only.
