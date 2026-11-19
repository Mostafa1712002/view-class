# Research: «أنا والقدرات» (anawlqdrat) — integration with the Rawani hub

Date: 2026-11-19. This was read-only research: no code, DB, git or server changes.

## 0. Sources and refs used

| What | Ref | Note |
|---|---|---|
| Backend (Laravel) | `origin/server-live-2026-07-31` @ `9638b6f3` (2026-10-26) in `/home/mostafa/www/anawlqdrat` | **This is what runs in production.** The prod tree `/www/wwwroot/dashboard.anawlqdrat.com` sits on this branch, and every prod deploy commits there and pushes it to the fork. All `file:line` citations below use this ref unless noted otherwise. |
| Backend `origin/main` | `616640da` (2026-09-22) | About a month behind live (`git diff --stat` shows ~2.5k changed lines in User/Api alone). `feature/hybrid-subscription-system` (`7893adcc`) **is merged** into both main and server-live. |
| Remote `production` (GadReda121/anawalqdrat) | `production/main` = `d473b574` (2026-05) | **Stale.** Never check files out from it (that clobbered live files once). |
| Local checkout | branch `fix/trello-notifications-and-exam-reports` | Old feature branch. Not used. |
| SPA | `/home/mostafa/www/anawlqdrat-front` HEAD = branch `deploy-symmetry-fix` @ `37c64076` | The live SPA bundle is built from this branch (per memory). It is not merged into the SPA repo's `master`. |
| Local DB | `anawlqdrat_reports_local` (the same as `anawlqdrat` / `anawlqdrat_gada`: 2,526 users) | **Stale snapshot.** The newest user is from 2025-10-26, and it lacks later columns such as `graduated_at`. Use it for structure only. |
| Memory | `project_anawlqdrat_dev_environment.md`, `project_tarweig_migration_2026.md`, `project_anawlqdrat_admin_account.md`, `project_anawlqdrat_org_scoped_imports.md` | Used for the live layout and approximate prod volumes. |

**"Plans" are not pricing.** The many `anawlqdrat-wt-plans*` and `anawlqdrat-front-wt-plans*` worktrees all deal with **study plans (خطط دراسية: base, complementary and training plans of exam days)**. No priced plans, packages or products exist anywhere. The only commercial object is the date-window `subscribes` row (§5).

---

## 1. What the platform is

| Piece | Detail |
|---|---|
| Backend | Laravel **10.48.19** (composer.lock), PHP ^8.1; production runs PHP **8.2** (`/www/server/php/82`). Code is DDD-ish: `app/Domain/*` (models, actions, repositories) and `app/Application/{Admin,Api}` (controllers, routes, Livewire). |
| Auth packages | `laravel/passport` 11.10.6 (used for the API), `laravel/sanctum` 3.3.3 (installed but unused, 0 rows in `personal_access_tokens`), `laravel/socialite` (social login is commented out). |
| Admin dashboard | Blade + **Livewire 2.12** + spatie/permission 6.9, at `https://dashboard.anawlqdrat.com/{locale}/admin` (`app/Application/Admin/routes/web.php:42-49`, login at `:171`). |
| Public API | `https://dashboard.anawlqdrat.com/api/v1/...` (`app/Application/Api/Providers/ApiApplicationServiceProvider.php:22`, `app/Application/Api/routes/api.php:17-22`). |
| Student/teacher SPA | `https://anawlqdrat.com`: Vue **3.5** + Vite 5 + Vuex 4 + naive-ui. `VITE_BASE_URL=https://dashboard.anawlqdrat.com/api/v1` (`anawlqdrat-front/.env`). |
| Hosting | Tarweig VPS 198.244.189.4 (aaPanel, MariaDB 11.4), the same box as rawaniedu.com and alawal.online. Prod Laravel lives at `/www/wwwroot/dashboard.anawlqdrat.com` (a git tree on `server-live-2026-07-31`). The prod SPA is a static `dist` at `/www/wwwroot/anawlqdrat.com/dist`. The prod DB is `anawlqdrat_dashboard`, reached through a scoped DB user. The php-fpm user is `www`. A scheduler cron runs hourly/daily exam-closing jobs (`app/Console/Kernel.php:15-30`). |
| Dev/staging | `dev.anawlqdrat.com` (SPA) + `dashboard-dev.anawlqdrat.com`, DB `anawlqdrat_dev`, mail sent to the log. |
| Deploy flow (memory) | Commit and push to the fork → on the server, `git fetch` the fork and `git checkout <ref> -- <paths>` → commit on `server-live-2026-07-31`. Migrations are run scoped with `--path`, because the migrations table has drifted. The SPA is built locally and its `dist` is copied over. |
| Legacy | The DB still holds the tables of a former Django app (`users_user`, `users_student`, `users_subscription`, `core_*`). They were imported once through `import:django` (`app/Console/Commands/ImportDjangoData.php:24`) and are otherwise unused. |

---

## 2. Identity model

### 2.1 Account tables

| Kind | Table / model | Guard |
|---|---|---|
| Student | `users`, `type='student'` (`App\Domain\User\Models\User`) | `api` = **Passport** (`config/auth.php:47-48`); session guards `web`/`user` exist but the SPA does not use them |
| Teacher | `users`, `type='teacher'` | same |
| Admin / org manager | **separate** `admins` table (`Domain\Admin\Models\Admin`) + spatie roles (`super_admin`, `manger`, `superviosr` (sic), `company_supervisor`, `complex_supervisor`). Admins carry `educational_company_id/complex_id/school_id/class_id` as their org scope. | `admin` session guard (`config/auth.php:43`, provider `:77`) |
| Parent / ولي أمر | **Does not exist.** No table, type, column or route. A word-boundary search for `parent|guardian|father|mother|ولي` across `app`, `database`, `lang` and the SPA `src` finds only CSS, comments and an unrelated `'parent' => 'القسم الرئيسي'` category label. |

`UserTypeEnum` contains only `student` and `teacher` (`app/Support/Enum/UserTypeEnum.php`). `User::hasActiveSubscription()` has a `type === 'admin'` branch (`User.php:257`), but that value never occurs in `users`.

### 2.2 `users` columns (live DB schema; migrations have drifted)

`id` bigint · `name` · `email` **NOT NULL but often ''** · `username` NOT NULL · `phone` nullable · `blocked` bool · `password` (bcrypt) · `gender` (male/female) · `type` · `school_id` / `educational_complex_id` / `educational_company_id` (FKs, ON DELETE SET NULL; added by `database/migrations/2025_01_28_120000_add_organization_fields_to_users_table.php`) · `status` (active/inactive) · `email_verified_at` · `last_logged_in_at` · `graduated_at` (prod only, `database/migrations/2026_08_23_100000_add_graduated_at_to_users_table.php`) · `deleted_at` (**SoftDeletes**, `User.php:36`) · timestamps. Fillable fields are at `User.php:43-60`.

**Unique identifiers: none are enforced in the database.** `users` has **no UNIQUE index** on email, username or phone. Uniqueness exists only in form requests (`RegisterRequest.php:29-31`, `UserRequest.php:56-60`), and those exclude soft-deleted rows. The importers check only the username.
- Local snapshot: 2,145 of 2,461 students have an **empty email**. There are **32 duplicate-email groups** and **8 duplicate-username groups** (non-deleted), and 0 duplicate phones (almost nobody has a phone).
- Most student usernames (2,104/2,461) are **10-digit numbers**. That looks like a national ID or iqama pattern, but nothing validates or labels it as one, so treat it as an opaque username.
- Login resolves the identifier as `where email = X OR phone = X OR username = X ->first()` (`app/Domain/User/Actions/TemporaryPassword/LoginWithTemporaryPasswordAction.php:29-33`). A collision across those fields means it silently picks the first matching row.

### 2.3 Passwords

- bcrypt `$2y$` everywhere: `bcrypt()` in `RegisterUserAction.php:19`, `Dashboard/User/StoreAction.php:17`, `UpdateAction.php:17-18`, and both importers. Every local row has the `$2y$` prefix. That is the same algorithm as ViewClass/Rawani, so a hash can be copied between the two and `Hash::check` still works.
- **The importers default the password to the username** (`Livewire/User/Modal/Import.php:388`, `TeacherImport.php:124`). There is also `users:update-passwords`, which sets every password to the username (`app/Console/Commands/UpdateUserPasswordsCommand.php:16,49`).
- Temporary passwords: table `temporary_passwords` (polymorphic, single-use, expiring). The login action tries the normal password first and then the active temporary one (`LoginWithTemporaryPasswordAction.php:51-89`). Admins generate them from the user modal (`Livewire/User/Modal/Generatetemppassword.php`).

### 2.4 How accounts are created today

| Path | Where | Result |
|---|---|---|
| Self-registration | `POST /api/v1/user/auth/register` (`app/Application/Api/routes/V1/Modules/User/auth.php:19`, `AuthController.php:97-119`) | Creates the user, defaulting to `type=student, status=inactive`. Sends an email OTP. **No subscription**, so the user cannot log in until an admin adds one. |
| Admin creates one user | `admin:users.store` → `Dashboard/User/StoreAction.php:15-56` | Picks the type and org (or inherits the admin's org scope, `:21-32`), syncs classes and section, then `syncOrgSubscription()` (`:46`). `status=active` ⇒ `email_verified_at=now()` (`:50-52`). |
| Excel import (students) | `Livewire/User/Modal/Import.php:359-420` | Skips existing usernames, `status=active` + verified, password = column or username, then `syncOrgSubscription()`. It can also create company/complex/school/class rows on the fly (`:174-260`). |
| Excel import (teachers) | `Livewire/User/Modal/TeacherImport.php:122-124` | Same pattern. |
| Legacy Django import | `import:django` | One-off. |

**Activation and verification:** API login refuses students and teachers who have no `email_verified_at` (HTTP **203**, `AuthController.php:63-68`). Verification is done with OTP endpoints (`auth.php:21-24`).

**Disable and delete:**
- `blocked=1` is the only flag the login checks (`LoginWithTemporaryPasswordAction.php:43`). **`status='inactive'` is not checked at login.**
- Admin delete is a Livewire soft delete (`Livewire/User/Modal/Delete.php:30`). The user can also delete their own account: `DELETE /api/v1/user/profile/delete` → soft delete (`ProfileController::destroy`).
- None of the paths above revokes Passport tokens. Only verify-OTP (`AuthController.php:152`) and logout do.

### 2.5 Volumes (no personal data)

| Source | Students | Teachers | Admins | Companies / complexes / schools / classes |
|---|---|---|---|---|
| Local snapshot (2025-10, stale) | 2,461 (147 soft-deleted, 44 unverified) | 65 | 3 | 6 / 14 / 24 / 101; `class_user` 2,326, `class_teacher` 175 |
| Prod (approx., from memory notes 2026-09/10) | ~4,200 | ~116 | ~30+ | the main org is the آفاق company; ~770 graduated students (Aug 2026) |

Local `subscribes`: 85 rows, all `User`-morph (69 active, 16 expired), and no org subscriptions in the snapshot. Prod has company/school subscriptions (memory: graduation work in 2026-08/10).

---

## 3. Parent ↔ student linkage and the org model

- **No parent ↔ student link exists.** Rawani must own parents and the parent→child relation completely. Showing a parent their child's anawlqdrat progress would need a new read endpoint (§7).
- **Org hierarchy, single DB (not multi-tenant):** `educational_companies` → `educational_complexes` → `schools` (has `gender`) → `classes`. Students join classes through `class_user`; teachers through `class_teacher` (`User.php:124-135`). A user's own org lives in `users.school_id/complex_id/company_id` (`User.php:145-160`). Section (لفظي/كمي) is stored in `category_user`; a student may have at most one (`UserRequest.php`, the `categories` rule).
- Admin visibility is scoped by the admin's org columns (`User::scopeVisibleToAdmin`, `User.php:164`) plus section supervision (`admin_category`).
- **Graduation (تخريج)** removes `class_user` rows, stamps `users.graduated_at`, and ends the individual subscriptions held at that moment (`User::endSubscriptionsOnGraduation`, `User.php:384-392`). Graduates never inherit an org subscription (`User.php:287`).

---

## 4. APIs and auth

All API routes are under `/api/v1` (CORS `allowed_origins: *` on `api/*`, `config/cors.php`).

| Group | Routes | Auth |
|---|---|---|
| Auth | `POST user/auth/{register,login,otp/verify,otp/resend,password/new/verify,password/new}` (`User/auth.php:17-25`, throttle 35/min) | none |
| Profile | `GET/POST user/profile`, `/update`, `/logout`, `DELETE /delete`, notifications, fcm-tokens, competitions (`User/profile.php:18-31`) | `auth:api` (Passport) |
| Content / exams | categories, skills, lessons, pdf, classes, settings, contact-us (`General/api.php:27-35`); `exams/*`, `tests/*`, `plans/*` (`Exam/student.php:32-79`); `teacher/*` classes, quizzes, exams, reports, simulation-settings (`Exam/teacher.php:23-67`); capsules | `auth:api` set in each controller constructor, plus the `student` / `teacher` type middleware (`app/Http/Middleware/StudentMiddleware.php`, `TeacherMiddleware.php`, which **check type only**) |
| File upload | `POST user/file` | `auth:api` |
| Misc | `GET /api/user` (`routes/api.php:20`) uses guard **`user-api`, which is not defined**. `POST /api/upload-audio` has no auth. | — |

**Things that do not exist:**
- No server-to-server API.
- No endpoint to create, update, list or disable users, and none to grant subscriptions.
- No SSO, impersonation or "login as" link.
- No webhooks out or in.

`app/Application/Api/routes/V1/Modules/Admin/temp-password.php` defines `admin/temp-passwords/*` behind guard `admin-api`. The file is **never required** (only User/Exam/General/Capsule are loaded, `Api/routes/api.php:17-22`) and the guard is undefined, so it is dead code.

**Tokens:**
- Login returns a Passport personal access token (`LoginWithTemporaryPasswordAction.php:108`) with default expiry; no `Passport::tokensExpireIn` is set (`AuthServiceProvider::boot` is empty).
- Passport's own `/oauth/token` routes are registered by default. The DB has password-grant and personal-access clients (`oauth_clients` ids 1-4). There is no client-credentials client and no `CheckClientCredentials` usage yet.
- **The subscription and verification checks run only at login** (`AuthController.php:61-79`; the only callers of `hasActiveSubscription()` are there and the CLI). A token stays valid after the subscription ends.

**How the SPA holds the session:**
- It stores the token in a first-party cookie `jwt` (`secure`, `SameSite=Strict`, 30 days; `src/Store/subscriber.js:5-15`).
- On boot it runs `auth/attempt(Cookies.get('jwt'))` (`src/main.js:71`), which calls `GET /user/profile` to load the user (`src/Store/auth.js:201-219`).
- Login handles HTTP 203 (not verified or not subscribed) specially (`auth.js:80`).
- There is **no route that accepts a token or code from a URL**, and Rawani cannot set that cookie across domains.

---

## 5. Plans, subscriptions and payments

- **Access model:** login requires `hasActiveSubscription()` (`User.php:255-262`). That means **either** a personal `subscribes` row (`status='active' AND end_at >= now()`, `:276-279`) **or** an active row on the user's school, complex or company, reached through the user's own org fields or through their classes (`orgSubscription()`, `:285-329`). This is the "hybrid B2C + B2B" model; teachers are included since `7893adcc`.
- **`subscribes` table:** `start_at`, `end_at`, `status` (active/expired), morph `subscribable_type/id` (`app/Domain/User/Migrations/2025_11_10_213234_create_subscribes_table.php:15-21`). There is **no price, product, package, tier, feature flag, order or payment reference**. Every subscriber gets the same full access.
  - Morph strings are **inconsistent**: users use `App\Domain\User\Models\User`, while orgs use `Domain\User\Models\School|EducationalComplex|EducationalCompany` without the `App\` prefix (`User.php:311-313`, `Livewire/Subscribe/Modal/Create.php:202`).
- **Who creates subscriptions:**
  - Admin user page: `ManageSubscriptions.php:139-158`, with durations from 1 month to 10 years or permanent. An individual row may not outlast the org's row (`orgSubscriptionExceededBy`, `:142`).
  - Admin subscriptions page for a student or company (`Livewire/Subscribe/Modal/Create.php:115-204`).
  - `syncOrgSubscription()` copies the org row onto a user when they join a subscribed org (`User.php:348-377`).
  - CLI `subscription:manage {check|create-user|create-company|list|expire}` (`app/Console/Commands/SubscriptionManagement.php:25-29`).
- **Payments: none are wired.**
  - `moyasar/moyasar` 1.1.1 is installed and `config/payment.php` says `provider => moyasar`, but no app code uses it.
  - `myfatoorah/laravel-package` auto-registers **its demo routes** (`GET /myfatoorah`, `/myfatoorah/callback`, `/myfatoorah/checkout`, `POST /myfatoorah/webhook`; `vendor/myfatoorah/laravel-package/src/MyFatoorahServiceProvider.php:25-39`) pointing at the published sample `app/Http/Controllers/MyFatoorahController.php`, which uses test order data. They are publicly reachable stubs and never create a subscription.
  - No codes, coupons or trials exist. The `offers*` tables are dormant marketing-banner tables with no code on the live ref.
- **Implication for packages:** the whole package-to-access mapping for this platform reduces to "make sure the user has a personal `subscribes` row whose window covers the package period". Payment stays in Rawani (Moyasar).

---

## 6. Local DB counts per user type

See §2.5; aggregates only. All 2,526 password hashes are bcrypt. There are 4 Passport clients, 70 access tokens (7 still live), and 0 Sanctum tokens.

---

## 7. Integration options for Rawani

Core facts that drive every option:
- A provisioned account can log in only when it has `email_verified_at` set **and** an active `subscribes` row (and `blocked=0`).
- No reliable natural key exists.
- No parent concept exists.
- Sessions are long-lived Passport tokens.

### (a) Server-to-server API that Rawani calls — **recommended backbone**

- **Exists:** nothing usable. The domain pieces can be reused: `Dashboard/User/StoreAction`/`UpdateAction` logic, `User::syncOrgSubscription`, `Subscribe` model, Passport.
- **To build in أنا والقدرات** (new `app/Application/Api/routes/V1/Modules/Integration/*.php`, required from `Api/routes/api.php`):
  1. **Machine auth:** a Passport `client_credentials` client + `CheckClientCredentials` middleware (already in Passport 11), or a static bearer secret / HMAC header kept in `.env`. Add an IP allow-list for the box itself, since Rawani is on the same VPS.
  2. **Stable external key:** a migration adding `users.rawani_user_id` (nullable, **UNIQUE**), or a mapping table `external_accounts(provider, external_id, user_id)` (UNIQUE on provider + external_id). All writes are idempotent upserts on that key; never match on email.
  3. `POST integration/users` (upsert by `rawani_user_id`):
     - Set name, gender, type student/teacher, optional org (company/complex/school/class) and section.
     - Generate a unique username (e.g. `rw<rawani_id>`, or the Rawani national ID if Rawani validates it). Check it against email, phone and username together, because login ORs all three.
     - Set the password: either copy Rawani's bcrypt hash or use a random one plus SSO only (see risks).
     - Set `status=active`, `email_verified_at=now()`.
     - Do not send mail. The `Registered` event only queues `SendEmailVerificationNotification`, and that sends nothing because `User` does not implement `MustVerifyEmail`.
  4. `PUT integration/users/{rawani_id}/subscription` `{start_at, end_at}`: create or extend the personal `subscribes` row (User morph), honouring `orgSubscriptionExceededBy` or overriding it deliberately.
  5. `POST integration/users/{rawani_id}/disable|enable`: set `blocked`, expire the subscription and **revoke all Passport tokens** (`$user->tokens()->update(['revoked'=>true])`), because subscription is checked only at login.
  6. `GET integration/users?updated_since=&type=&page=` and `GET integration/orgs`: let Rawani mirror users, classes, schools, graduation (`graduated_at`) and subscription state for its master view. Optionally `GET integration/users/{id}/summary` (plan progress and exam averages) for the parent view.
- **Effort: M** (about 3–5 days with feature tests; reuse the existing actions and keep the API envelope `{code,status,message,data}` from `App\Support\Traits\Api\ApiResponseTrait::handleResponse`; this differs from ViewClass's `{success,data,message}`).
- **Risks:**
  - **Duplicates:** existing anawlqdrat students who buy through Rawani. Needs a one-time claim/link flow (Rawani user proves ownership with an anawlqdrat login, or an admin links them manually). Auto-matching on email is unsafe: emails are often empty, 32 duplicate groups exist, and there is no UNIQUE index.
  - **Password sync:** if users also log in directly on anawlqdrat.com, Rawani must push the hash on every password change. Both apps use bcrypt `$2y$`, so a copied hash verifies. Otherwise make Rawani SSO the only entry point and set a random password.
  - **Email collisions:** blocked by the external key. Store an email only when it is unique across all three login columns.
  - Teacher accounts also need `category_user` (a section is required for teachers, `UserRequest.php` `categories` rule), or they break section-scoped reports.

### (b) Direct DB access (same MariaDB on the same VPS)

- **Exists:** both DBs sit on one MariaDB 11.4 instance. Each app has its own scoped DB user, so a read grant would be a one-line `GRANT SELECT`.
- **To build:** grant Rawani's DB user `SELECT` on `anawlqdrat_dashboard.{users, classes, class_user, class_teacher, schools, educational_*, subscribes, category_user}`, then add Rawani read models or SQL views. Reads only.
- **Effort: S** (about half a day) for reads.
- **Risks:**
  - Tight schema coupling. Live columns drift from the migrations, and prod has `graduated_at` while local copies do not.
  - **Writes are unsafe:** they bypass `syncOrgSubscription`, the org-field inheritance, the inconsistent morph strings (`App\...User` vs `Domain\...School`), the missing UNIQUE constraints and Passport token revocation.
  - A Rawani bug could corrupt anawlqdrat data.
  - Recommendation: use (b) only as an optional read path for heavy reporting, and keep writes on (a).

### (c) SSO / auto-login from Rawani

- **Exists:** nothing. The SPA boots only from its own `jwt` cookie (`subscriber.js:11`, `main.js:71`). `/oauth/token` with the password grant would need the user's plaintext password, which is unsuitable.
- **To build:**
  1. Backend: `POST integration/sso/code` (machine-auth, by `rawani_user_id`) returns a random one-time code, single-use with a TTL of about 60 s, stored hashed. The existing `temporary_passwords` table is a near fit: polymorphic, single-use, `expires_at`, `token`. It could be reused or a sibling table added.
  2. Public `POST user/auth/sso/exchange {code}`: validates the code, **re-runs the same checks as login** (blocked, verified, `hasActiveSubscription`), issues a Passport token, and logs a `user_logins` row with `login_type='sso'`.
  3. SPA: a new route `/sso?code=` that calls exchange, then `dispatch('auth/attempt', token)`, then pushes to `/`. Rawani's "open أنا والقدرات" button redirects the browser to `https://anawlqdrat.com/sso?code=…`.
- **Effort: S–M** (about 1–2 days backend + SPA; the SPA build and copy deploy is manual).
- **Risks:**
  - The code leaks through referrer or logs. Mitigate with a short TTL, single use and `Referrer-Policy`.
  - An expired subscription with an old cookie keeps working until the token expires. Mitigate by revoking tokens on disable (a.5), or add a subscription middleware on `auth:api` routes.
  - Switching users on a shared device: the SPA clears exam localStorage only on logout, so the SSO route should clear it too.

**Recommended combination:**
- **(a) + (c):** Rawani provisions and keeps the subscription window in sync, then hands users over through a one-time SSO code.
- **Optional (b)** read-only access for the master dashboard if API paging proves too slow.
- Rawani keeps parents, the parent↔child link and payments. On this platform, "package includes أنا والقدرات" means **upsert user + subscription window**.

---

## 8. Gotchas

1. **The live code is `origin/server-live-2026-07-31`,** not `origin/main` (about a month behind) and never `production/main` (May 2026, stale). Build the integration on the server-live branch and deploy with the documented path-checkout flow.
2. **No UNIQUE constraints on `users` identifiers,** many empty emails, and existing duplicates. Login ORs email, phone and username with `->first()`. Integration must use its own external key.
3. **Status 203 is used for "not verified" and "not subscribed"** (`AuthController.php:65,74`). Provisioned users need `email_verified_at` set, otherwise the SPA fires an OTP-verify flow (`auth.js:70-75`).
4. **`status='inactive'` does not block login.** Only `blocked` does. Disabling must also revoke tokens, since subscription is checked only at login and Passport tokens are long-lived.
5. **Subscription morph strings are inconsistent** (`App\Domain\User\Models\User` vs `Domain\User\Models\School|EducationalComplex|EducationalCompany`). Raw SQL writers must copy them exactly. Admin role rows had a similar double-backslash corruption once (memory).
6. **Imported users' default password = username.** A security weakness worth fixing before Rawani starts linking accounts by login.
7. **`UserRepository::destroy()` calls relations that do not exist on `User`** (`amountOfStorages`, `quranicReadings`, `competentRequests`, `socialAuth`; `app/Domain/User/Repositories/UserRepository.php:74-86`). The admin UI avoids it by calling `$model->delete()`. Do not reuse it.
8. **Dead or unsafe routes:**
   - The unrouted temp-password API (guard `admin-api` is undefined).
   - `GET /api/user` with an undefined guard `user-api`.
   - Unauthenticated `POST /api/upload-audio`.
   - The MyFatoorah **demo** routes, including `POST /myfatoorah/webhook`, are publicly registered.
   - CORS is `*`.
9. **Graduation silently ends subscriptions and class membership.** Rawani's master view should read `graduated_at` and not treat graduates as "cancelled".
10. **Org subscription capping:** an individual row may not exceed the org's `end_at` in the admin UI (`orgSubscriptionExceededBy`). Decide whether Rawani package periods override this.
11. **Stack mismatch with Rawani:** anawlqdrat runs Laravel 10 on PHP 8.2; ViewClass/Rawani runs Laravel 12 on PHP 8.4. The pools and codebases are separate, so integrate over HTTP (or DB reads), never by shared code.
12. **The migrations table has drifted.** Run new migrations scoped (`migrate --force --path=...`). When cloning a Laravel tree for a staging copy, delete `bootstrap/cache/*.php` first; a cloned cache once pointed dev at the prod DB (memory).
13. **The SPA is deployed by hand from a branch not merged into the SPA repo's `master`** (`deploy-symmetry-fix`). An SSO route must be added there. `index.html` is served `no-cache`, so a new route shows up after one reload.
14. **Legacy Django tables (`users_user`, `users_subscription`, …) are still in the DB.** Ignore them; they are not the live identity source.
