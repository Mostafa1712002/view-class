# Research: the Rawani / ViewClass side of «باقات رواني»

Scope: read-only look at worktree `feature/rawani-hub-packages` at `8e0d28af`, which is the same commit as `origin/main`. All paths are relative to the repo root. Schema facts come from the migrations. Local DB `viewclass` output is used only to back them up. Nothing was run against any server.

---

## 0. Headline: there is no Moyasar payment flow yet

The brief says to use "our existing Moyasar integration". On `origin/main` that integration is **configuration only**:

- There is no invoice, payment or checkout code.
- There is no webhook route.
- There are no `payments`, `invoices` or `payment_events` tables.
- There is no refund code.
- **No feature charges money through Moyasar today.**

The payment half of «باقات» is therefore new work. It is not reuse. The design for it is already written down and the working reference is aafaq (see §1).

---

## 1. Moyasar

### What exists (merged on main)
| Piece | Where | Notes |
|---|---|---|
| Config | `config/moyasar.php:15` mode, `:28` site, `:36` api_url, `:38` currency, `:50-62` test/live key pairs, `:76` webhook_secret | `mode` selects the key pair. `site` (default `viewclass`) is meant to go into `metadata.site` because the Moyasar account is **shared with aafaq.xyz** (entity "Rawani"). |
| Env template | `.env.example:67-74` | `MOYASAR_MODE/SITE/CURRENCY`, four keys, `MOYASAR_WEBHOOK_SECRET` |
| Health check | `app/Console/Commands/MoyasarCheck.php:20-58` | `php artisan moyasar:check [--mode=]`. It runs `Http::withBasicAuth($secret,'')->get(api_url.'/payments',['per'=>1])` (`:41-43`). This is the only HTTP call to Moyasar in the codebase. |
| Runbook | `claudedocs/MOYASAR.md` | Seven hard rules taken from the aafaq outage (31 payers locked out, Feb 2025 to Jul 2026). The rules: a webhook grants access, not a browser redirect; never trust `?status=paid`; put identity in `metadata` because Moyasar drops extra callback params; record every event; apply idempotently; strip the secret before storing; keep logs out of the web root. |
| Spec | `.kiro/specs/moyasar-payments/` | Phase 1 (config) is done. **Phase 2 "Charging" is BLOCKED** because nobody has decided what is being charged for (`tasks.md` "Phase 2"). Missing pieces: invoices via `POST /v1/invoices` with `metadata.site`, `invoices` and `payment_events` tables, a webhook that checks `secret_token` with `hash_equals`, and an admin payments screen. US-002 and US-003 in `requirements.md` are marked NOT BUILT. |

The design decision recorded in `.kiro/specs/moyasar-payments/requirements.md` (2026-08-29) is **invoices: a link that gets sent, not an in-app card form**. There is no SDK on purpose: `moyasar/moyasar-php` is abandoned, so the code uses the `Http::withBasicAuth` facade (`design.md` "Why no package").

### Test and live keys
- `MOYASAR_MODE` picks the pair. Only `test` keys belong in local `.env`, and the live pair is set only in the server `.env` (`claudedocs/MOYASAR.md` "Where the keys live").
- Secret keys must never be committed. Publishable keys are public by design.
- Recommendation: set `MOYASAR_SITE` in rawaniedu's server `.env` to a rawani-specific tag (for example `rawani`). The default `viewclass` would mislabel package payments in the shared dashboard. I could not verify the current server value.

### What is reusable for package purchases
- `config/moyasar.php` and the `Http::withBasicAuth` call pattern.
- The **aafaq reference flow** (`moyasar-webhook.php`, `includes/payment_activation.php::applyPaidPayment()`, `payment_events` table, `payments.php` admin screen), which is described in `claudedocs/MOYASAR.md` "Rules" and the memory note `project_aafaq_moyasar_payments`. Port that shape:
  1. `payments` row, created as pending.
  2. Moyasar invoice or payment with `metadata {site, payment_id, user_id, package_id}`.
  3. Webhook `payment_paid`: verify the token, record the event, then apply once (a unique `moyasar_payment_id` with a status transition).
  4. Activate the subscription and dispatch provisioning jobs.
- **One-time vs recurring:** nothing recurring exists. Moyasar's recurring/tokenised-card support and its refund endpoint **were not verified** (no network in this research). Assume one-time purchases plus an expiry date (`ends_at`), with renewal done as a new purchase. Treat auto-renew as a separate decision.
- **Refunds:** no code exists. The same gateway would handle them with the secret key (refund API not verified). Model it as a status on the payment row and record it in the event log. It needs an explicit decision about whether a refund **de-provisions** the external accounts.

### Shared-account risk (for the aafaq researcher)
According to memory, aafaq's webhook is subscribed to all 16 events on the same Moyasar entity. Moyasar webhooks are registered per account (this is an inference from that memory note and was not checked against Moyasar's docs), so **aafaq's endpoint will most likely also receive Rawani package payments**. If aafaq matches payments by `metadata.email` or `callback_url`, it could open a reading/abilities subscription for a Rawani buyer. Rawani's webhook must likewise **ignore events whose `metadata.site` is not its own** (and aafaq should do the same). Someone needs to confirm how aafaq's `applyPaidPayment()` handles a payment from another site.

---

## 2. Anything like packages, plans, subscriptions, products or coupons

**None exists.** I ran a case-insensitive file search for `subscription|coupon|invoice|payment|discount|price|fee|package` over `app`, `routes`, `config` and `database/migrations`. It only hit the Moyasar config and command, `config/services.php` and the canteen files. An Arabic search of `resources/views` and `lang` for «باقة|باقات|اشتراك|رسوم|خصم|كوبون» only found «خصم» used for deductions (canteen balance, behaviour points, late penalty). Local `SHOW TABLES` has nothing for pay, invoice, subscr, fee, coupon or package. What the searches did turn up:
- `WeeklyPlan` / `SpecialEducationPlan`: lesson planning.
- `CanteenProduct` / `canteen_orders` (`database/migrations/2026_06_04_190000_create_canteen_orders_tables.php:12-36`): an internal school canteen paid from a **prepaid balance ledger**. There is no gateway. This is still the best in-repo precedent for **price snapshots on order items** (`:32-35`) and **atomic balance changes**. `App\Modules\Canteen\Services\CanteenBalanceService::apply()` (`:19`) uses `DB::transaction` (`:23`) with `lockForUpdate` (`:27`).
- SMS credit (`database/migrations/2026_06_15_500004_create_sms_credit_tables.php:20-57`): `sms_credit_ledger` plus `sms_credit_recharge_requests`. It is a **manual bank transfer with an uploaded receipt and admin approval**. No gateway. It is a useful precedent for a ledger with `balance_before/after` and a polymorphic `reference`.
- `PromotionPlanner`: student grade promotion. Not commerce.

No coupons or discounts exist anywhere.

---

## 3. Identity

### `users` table
The base table is `database/migrations/0001_01_01_000000_create_users_table.php`. It was extended by `2025_11_30_163321_add_fields_to_users_table.php`, `2026_04_21_100002_add_multitenant_fields_to_users_table.php` and `2026_05_25_130001_pa_add_parent_profile_fields_to_users_table.php`.
- **Globally unique:** `email` (`0001_01_01_000000…:17`, NOT NULL) and `username` (`2026_04_21_100002…:14`, nullable unique). They are unique **across all schools**, and soft-deleted rows still hold their values.
- **Not unique:** `national_id` and `phone` (`2025_11_30_163321…:18-19`, nullable, no index). `whatsapp` and `phone_secondary` exist as well.
- `school_id` is nullable with an FK to `schools` (`ON DELETE SET NULL`). The table has SoftDeletes (`app/Models/User.php:16`).
- `plain_password_for_card` stores the password **reversibly encrypted** with `encrypt()` and is decrypted for printing login cards (`app/Modules/Users/Controllers/UserCardController.php:324-326`). This is relevant to provisioning: if the business wants it, the Rawani password can be reused on the sub-platforms.
- Legacy NOT NULL-with-default columns (`email`, `gender`, `status`, `language_preference`, `language`, `timezone`) must be **omitted**, not passed as null. Use `withoutNulls()` (`app/Modules/Users/Controllers/Concerns/HasSchoolScope.php:81-84`).
- If a user has no email, the code synthesises `{username}@viewclass.local` (`app/Modules/Users/Controllers/StudentController.php:173`, `ParentController.php:56`, `app/Modules/Admissions/Actions/ConvertToStudentAction.php:57,119`). **These are not real addresses** and must not be used as a cross-platform identity key.

### Roles
- Fixed roles seeded as `super-admin`, `school-admin`, `teacher`, `student`, `parent` (the local DB matches). The pivot is `role_user` (`app/Models/User.php:134-137`). Helpers: `hasRole()` (`:240`), `isSuperAdmin/isSchoolAdmin/isStudent/isParent` (`:339-362`).
- Users cannot create roles. Finer-grained access comes from job titles plus the permission matrix built from `app/Modules/Users/Support/PermissionCatalog.php` (`MODULES` const, `:19`).
- Every catalog slug also needs a `permissions` row seeded by an insert-only migration. Precedent: `database/migrations/2026_09_05_090000_seed_full_permission_catalog.php`. Without the row, a ticked box is silently dropped.

### Parent and student link
- Pivot `parent_student(parent_id, student_id, relationship default 'parent', is_primary, can_receive_notifications)`, unique on (parent_id, student_id), both FKs to `users` with cascade. The local `SHOW CREATE` matches.
- Relations `User::children()` and `User::parents()` are at `app/Models/User.php:216-228`.
- The UI sync is `ParentController::syncStudents` (`app/Modules/Users/Controllers/ParentController.php:147-158`). There is also linking by sheet (`:284-303`).

### Required fields to create a student or parent today
All user creation is **inline in controllers**. No `CreateStudentAction` or `CreateParentAction` exists. The only Action is the admissions converter.
- Student: `StudentController::store` (`StudentController.php:158-200`). Validation at `:670-682`: `name`, `username` (unique) and, for a super-admin, `school_id` are required. `email` (unique), `national_id`, `phone` and `password` are optional. If no password is given, it defaults to `national_id` or a random 8-character string. The method attaches role `student` and inserts into `class_student`.
- Parent: `ParentController::store` (`:47-66`), validation at `:363-385`, with the same required fields. It attaches role `parent` (`:317-321`).
- Admissions: `ConvertToStudentAction::execute` (`app/Modules/Admissions/Actions/ConvertToStudentAction.php:28`) creates the student (`:51-89`) and finds or creates the guardian (`:95-130`). Usernames come from `uniqueUsername()`, which uses national_id or a slug.
- Noor import: `app/Modules/NoorImport/Actions/ImportNoorUsersAction.php` **matches by `national_id`** (`:67,82,175`), upserts and links parents (`:216-220`). This is the in-repo precedent for **"find by national id, otherwise create, never duplicate"**, which the unified directory needs.

**Implication:** the hub should extract a `CreateHubUserAction` (or `FindOrCreatePersonAction`) inside its own module rather than call into controllers. Matching order (used throughout this doc): national_id, then real email (skip `@viewclass.local`), then normalised phone.

### Registration and self-service entry points
- Login is `/login` only (`routes/web.php:25-26`). **There is no public self-registration for parents or users.**
- The only public form is admissions: `app/Modules/Admissions/Routes/web.php:14-24` (`web` middleware with `throttle:20,1`, per school or company). It creates an *application*, not a user.
- Logged-in parent self-service lives under `my.*` (for example `my/canteen`, `routes/web.php:382-386`; `my/appointments` `:1169`). A "parent buys a package" page would sit here as `my/packages`. An anonymous buyer would need either a new public checkout or an account created first by an admin.

### API auth an external system could call
- JWT (new): `routes/api.php:18-22` with `POST /api/auth/login|refresh|logout` and `GET /api/auth/me`, rate-limited by `auth-login` (5/min, `app/Providers/AppServiceProvider.php` RateLimiter). `LoginAction` (`app/Modules/Auth/Actions/LoginAction.php:19-33`) accepts **username or email** (`app/Modules/Auth/Repositories/EloquentUserRepository.php:10-14`). Tokens are HS256 with secret `config('auth.jwt_secret') ?: APP_KEY` (`app/Modules/Auth/Services/JwtService.php:14`). `auth.jwt_secret` is **not defined** in `config/auth.php`, so APP_KEY is in effect. Access TTL is 900s and refresh TTL is 7d (`app/Modules/Auth/Actions/IssueTokenPairAction.php:13-14`). The middleware is `AuthenticateJwt` (`app/Modules/Auth/Http/Middleware/AuthenticateJwt.php`).
- Sanctum (legacy): `routes/api.php:44-55`, `POST /api/login` and student read endpoints.
- **There are no user-creation endpoints and no service-to-service (API key) auth.** Today a sub-platform cannot push users into Rawani, and no SSO token format is shared (the HS256 secret is APP_KEY, which must not be shared).

### Existing outbound HTTP integrations (patterns to copy)
- `app/Modules/Whatsapp/Drivers/HttpProviderDriver.php`: constructor-injected url/token, `Http::timeout(15)->post`, returns a `success/provider_response/failure_reason` array and never throws. Its settings model encrypts the token: `app/Modules/Whatsapp/Models/SchoolWhatsappSetting.php` `'api_token' => 'encrypted'` cast. **This is the pattern to copy for platform credentials.**
- `app/Modules/SmsServices/Drivers/HttpProviderDriver.php`: Bearer header, stays "queued" when there is no endpoint, never fakes success. Its counterpart `SchoolSmsSetting` (`app/Modules/SmsServices/Models/SchoolSmsSetting.php:13-28`) stores `api_key/api_secret` **without encryption**. Do not copy that.
- `app/Services/ZoomService.php:18-21,31,96`: credentials come from `config('zoom.*')` (env), using `Http::asForm` and `withToken`.
- `app/Modules/Whatsapp/Support/PhoneNormalizer.php`: digits only, keeps a leading `+`. Use it to normalise phones for matching.
- There are no webhooks (inbound or outbound) anywhere. There are no `app/Events`, `app/Listeners` or `app/Observers` directories.

### "External platform" and الأول concepts already present
- `QuestionBank::SOURCE_AL_AWWAL = 'al_awwal'`, `SOURCE_ANA_QUDURAT = 'ana_qudurat'`, columns `external_id`/`external_platform` (`app/Models/QuestionBank.php:29-30,74-75`, `app/Models/BankQuestion.php:74`). These are **content-source tags only**: no API and no credentials.
- `App\Modules\Evaluation\Enums\OutcomeSource` (`app/Modules/Evaluation/Enums/OutcomeSource.php:11-19`) has `external_alawwal` and `external_qudrat` marked as "TODO Phase D". These are planned pulls from الأول and أنا والقدرات and were never built.
- `EducationalSites` module (`database/migrations/2026_06_16_720000_create_educational_sites_table.php:21-36`) is a **link bookmark list** (name, url, logo, category, is_active). It is a UI precedent for listing platforms, but it holds no credentials or sync logic. Do not overload it; the platforms registry is a separate thing.

---

## 4. Multi-tenancy, platform level vs school level

- Hierarchy: `educational_companies` → `compounds` (complexes; model `app/Modules/QuestionBankCore/Models/Compound.php`, M2M `compound_school`) → `schools` (`schools.educational_company_id`) → users (`users.school_id`). Multi-school admins use the `admin_school` pivot (`User::managedSchoolIds()` `app/Models/User.php:116-127`).
- Scoping helpers in `app/Modules/Users/Controllers/Concerns/HasSchoolScope.php`:
  - `activeSchoolId()` (`:43-61`): super-admin reads the session `scope.school_id`, and null means all schools.
  - `scopedSchoolId()` (`:69-75`): fail-closed, returns 403 for a non-super-admin with null.
  - `writeSchoolId()` (`:30-37`).
  - `listSchoolId()` (`:93-99`).
  - Company and compound session scope is **UI-only**. Only `school_id` scopes data.
- **Platform-level (super-admin) features** live in the `role:super-admin` group at `routes/web.php:73` (`admin.` prefix). Examples: school branches, SMS services connections, schools, educational companies and complexes. Maintenance is at `:1104`. Global settings use `settings` rows with `school_id = NULL` (`app/Models/Setting.php:44-67`, unique on (school_id, key)).
- **Packages, the platform registry and the unified directory should be platform-level**: no `school_id`, routes in a super-admin group, nav in the sidebar's super-admin "extras" area (`resources/views/components/sidebar.blade.php:561-576`, which uses the `Route::has()` pattern). This fits `.kiro/specs/sidebar-restructure/design.md`, where items carry `data-section="X"` inside the six `sec-*` sections. `canDo()` always allows super-admin (`.kiro/specs/roles-permissions/design.md`), so super-admin-only gating needs no new permission rows.
- An optional `school_id` on a subscription is reasonable if a school buys for its students. A parent buying for a child crosses schools, so the subscription should key on the user.
- **rawaniedu vs viewclass:** same code (`main`), separate DB. The differences are all data or env:
  - Brand comes from `settings.brand_*` with `school_id` NULL (`app/Providers/AppServiceProvider.php` `shareGlobalBrand()`, which also rewrites `auth.app_name`).
  - Mail branding is `App\Support\MailBrand`.
  - `.env` sets mail and Moyasar.
  - No instance-specific code or config flag exists today. Packages must work only on rawani, so they need one (see §6).

---

## 5. Where the new pieces fit

The pattern comes from `CLAUDE.md` "Module Pattern / Repository / Action / ApiResponse". Proposed module `app/Modules/RawaniHub/`, or split into `Packages/` and `LinkedPlatforms/` if preferred:

| Piece | Tables (suggested) | Notes |
|---|---|---|
| **Packages CRUD** | `packages` (name_ar/en, description, price decimal(10,2), currency, duration_days nullable, audience enum student/parent/both, is_active, softDeletes), `package_platform` (package_id, linked_platform_id, platform_plan/product code nullable) | Super-admin CRUD under `admin/packages`. Price is snapshotted onto the subscription or payment, as canteen does (`…canteen_orders_tables.php:32-35`). |
| **Purchases / payments** | `package_subscriptions` (user_id, package_id, price_snapshot, status pending/active/expired/cancelled/refunded, starts_at, ends_at, source purchase/assigned, assigned_by), `payments` (subscription_id, moyasar_id unique, invoice_id, amount, status, metadata json), `payment_events` (raw payload with the secret stripped, matched bool) | This is moyasar-payments Phase 2. The design in `claudedocs/MOYASAR.md` applies as written. "Assigned by admin" skips the payment and goes straight to `active`. |
| **Linked platforms registry** | `linked_platforms` (slug unique e.g. `anawlqdrat`/`alawal`/`aafaq_reading`/`aafaq_verbal`, name_ar/en, base_url, api_key **`encrypted` cast**, extra json encrypted nullable, driver/adapter key, is_enabled, last_health_at) | Copy `SchoolWhatsappSetting`'s `encrypted` cast. Each platform has its own adapter class behind one interface (`provisionUser`, `findUser`, `listUsers`), the same shape as `WhatsappDriverInterface`/`SmsDriverInterface`. Bind it in `App\Providers\RepositoryServiceProvider` (`$bindings`, `:110`). A "test connection" button follows `SmsServicesController::testConnection` (`routes/web.php` sms-services group, `:86-102`). |
| **External accounts mapping** | `external_accounts` (user_id, linked_platform_id, external_id, external_username, status pending/active/failed/disabled, last_error, last_synced_at, unique(user_id, linked_platform_id), unique(linked_platform_id, external_id)) | One row per (user, platform). Provisioning only ever moves a row forward. The DB uniques make retries safe. |
| **Unified users directory** | Rawani `users` plus `external_accounts`, plus optionally `external_people` for sub-platform users with no Rawani account yet (platform_id, external_id, name, email, phone_normalized, national_id, role, parent_external_id, linked user_id nullable) | Precedent: the super-admin-only cross-school search `app/Modules/Users/Controllers/StudentGlobalSearchController.php:20-25` and list repos `BaseUserListRepository.php:27-64`. "Connections between them": use `parent_student` for Rawani-side links and `parent_external_id` for links imported from the sub-platforms. Matching uses the order from §3 (national_id, then real email, then normalised phone), following the Noor precedent. |
| **Provisioning** | the `jobs` table | Write `ProvisionExternalAccountJob(user, platform)` with `$tries`/`$backoff` like `app/Modules/Attendance/Jobs/NotifyAbsenceJob.php:20-26`. Dispatch **after commit** from the activation action. A scheduled `hub:sync-platforms` command pulls sub-platform users into the directory, registered in `routes/console.php`. |
| **Permissions** | Add `packages` / `linked_platforms` groups to `PermissionCatalog::MODULES` and an insert-only seed migration | Or keep them super-admin-only by role middleware, which is simpler since roles are fixed. |
| **Routes** | `app/Modules/RawaniHub/Routes/web.php` required from `routes/web.php` (the existing `require` lines are at `:1187-1304`). Webhook goes in `routes/api.php` (no CSRF, no session) as `POST /api/payments/moyasar/webhook`. | API responses use `App\Support\ApiResponse::ok/fail` (`app/Support/ApiResponse.php:9,18`). |
| **Audit** | `App\Models\ActivityLog::log/logCreate/...` (`app/Models/ActivityLog.php:48-91`) | Log package edits, manual assignments and provisioning failures. |

### Queue and scheduler on rawaniedu
- The code default is `QUEUE_CONNECTION=database` (`config/queue.php:16`, `.env.example:38`). The `jobs` and `failed_jobs` tables exist. `CACHE_STORE=database` and `SESSION_DRIVER=database`.
- `rawaniedu-instance/design.md` records a cron that runs `schedule:run` plus `queue:work --stop-when-empty --max-time=55` every minute, with no supervisor. The server `.env` was **not verified**, so confirm `QUEUE_CONNECTION=database` there (with `sync`, provisioning would run inline during the webhook request).
- With a once-per-minute worker, provisioning latency is up to about 60 seconds. That is acceptable, but the buyer's UI should show "جارٍ إنشاء الحسابات" and read `external_accounts.status`.
- Scheduled jobs live in `routes/console.php`. As `claudedocs/CRON_SETUP.md` warns, adding a schedule turns it on wherever the cron is live, and that includes viewclass.

### Secrets storage
- Gateway keys go in env/config (`config/moyasar.php`), the same way as Zoom.
- Per-platform API keys go in a DB row with an Eloquent `encrypted` cast, as in `SchoolWhatsappSetting`. Rotating APP_KEY would make them unreadable; that is the existing trade-off for WhatsApp tokens too.
- The webhook secret must never be stored with events (`claudedocs/MOYASAR.md` rule 6).

---

## 6. Gaps and risks

1. **The payment flow does not exist.** Moyasar Phase 2 (invoice, webhook, payments tables, admin screen) is still blocked "on what is charged for". Packages answer that question, so this card unblocks it. The cost should count it as new work.
2. **Every code deploy goes to BOTH viewclass.newaves-systems.com and rawaniedu.com** (memory rule, 2026-11-09). The hub must be inert on viewclass. Use an env flag such as `RAWANI_HUB_ENABLED` (read through config): routes only registered when it is true, sidebar entries gated by `Route::has()`, and no scheduled sync when it is off. Migrations will still create empty tables on viewclass, which is harmless. `MOYASAR_SITE` defaults to `viewclass` and must be set on rawani.
3. **Schoolless buyers.** A parent who buys directly may have no school. `users.school_id` is nullable, but the existing create forms require it for super-admin (`StudentController.php:670`, `ParentController.php:366`). Many controllers use `scopedSchoolId()`, which returns 403 for a non-super-admin with null school (`HasSchoolScope.php:69-75`). A schoolless parent can log in, but pages such as `my/canteen` and dashboards may 403 or show nothing. Decision needed: put hub buyers in a dedicated "رواني" default school, or keep `school_id` NULL and only show them the hub pages.
4. **Identity matching across platforms is weak.** Email is often a synthetic `@viewclass.local`. `national_id` and `phone` are not unique and are often empty. Each sub-platform has its own unique keys (the other researchers should report them). Expect duplicates and plan a manual "merge/link" screen. Never auto-merge on name.
5. **Credential delivery has no channel.** Rawani `MAIL_MAILER=log` (`rawaniedu-instance/tasks.md` "Mail sending" is open). SMS uses `PendingDriver` until a provider is configured, and WhatsApp is the same until a provider URL is set. New sub-platform passwords therefore cannot reach the buyer. Options are to show them on a Rawani page or card (as `plain_password_for_card` does) or to use one password shared across platforms. Decide before building.
6. **No inbound API or SSO.** Rawani cannot be called by the sub-platforms today. JWT is signed with APP_KEY, which must not be shared, so SSO would need a separate per-platform signed token (HMAC with the platform's own key) or plain separate logins. For v1, start with separate logins and use deep links from Rawani.
7. **Shared Moyasar entity with aafaq** (§1). Both webhooks must filter on `metadata.site`, otherwise aafaq's activation could react to Rawani payments, or the reverse.
8. **Refund and expiry semantics are undefined.** Does a refund or expiry disable the external accounts? This depends on whether each sub-platform's API supports disable (other researchers to report).
9. **Secrets hygiene.** Do not copy the unencrypted `SchoolSmsSetting` pattern. Keys must not appear in specs or claudedocs: `claudedocs/MOYASAR.md` already prints publishable keys, which is acceptable, but nothing beyond that.
10. **Fresh-install permission quirk.** On rawani, seeders `sync()` role permissions and wipe migration grants (`rawaniedu-instance/design.md`). If the hub adds permission rows, re-grant them after any reseed. Gating by `role:super-admin` avoids this.
11. **Business logic in controllers.** User creation is copy-pasted across `StudentController`, `ParentController`, `ConvertToStudentAction` and the Noor import. The hub needs one Action to create or find a person. Do not refactor the legacy paths in this card.
12. **Unverified items** (no network or server access in this research): Moyasar recurring and refund API behaviour; rawani server `.env` values (`QUEUE_CONNECTION`, `MOYASAR_SITE`, `MOYASAR_MODE`); whether rawani has any schools or users yet (the 2026-11-09 memory says 0 complexes).
