# Research: aafaq platform («القراءة السريعة» + «القدرات اللفظية») for the Rawani hub

Read-only research, 2026-11-19. Nothing was changed in any repo or DB, and no server was contacted.
Sources: `/home/mostafa/www/aafaq` (branch `main` = `origin/main` @ `974f5d9`, clean apart from an untracked `moyasar_keys.png`),
`/home/mostafa/www/afaq_reading` and `/home/mostafa/www/afaq_abilities_app` (both `dev` = `origin/dev`), the local MySQL DB `aafaq`, and the memory notes `project_aafaq_*`, `project_tarweig_migration_2026.md`, `project_afaq_subdomain_migration_2026-09.md`.

> Secrets: none are copied here. Live DB credentials sit in `config/config.php` + `api/config/config.php` (gitignored).
> The local `system_settings` table also holds third-party API keys in plaintext (see §8).

---

## 0. TL;DR

- **Confirmed:** «القراءة السريعة» = `afaq_reading` (`com.aafaq.speedreading`). «القدرات اللفظية» = `afaq_abilities_app` (Android `tests.aafaq.com.aaafaqtests`, iOS `com.aafaq.aafaqtests`). Both run on **one** backend, `aafaq.xyz`, with **one** user table, `reg_students`. One account = both apps.
- **Identity:** `reg_students.email` is UNIQUE and is the real login key (login, getStudentData, payment unlock all look up by email). There is **no parent entity**, **no account status / disable flag**, and **no API auth tokens**: the app's "session" is email + userId + password-hash kept in SharedPreferences.
- **Access:** 4 int flags on the row (`reading_paid`, `abilities_paid`, `has_reading_code`, `has_abilities_code`). They are written by app codes, admin subscriptions (company → complex → school → user), Moyasar web payments and iOS RevenueCat. Nightly crons re-apply and expire them.
- **APIs that Rawani can use today:** none are safe. `register`, `login`, `getStudentData` and `updatePaymentStatus` exist, but they are unauthenticated: `updatePaymentStatus` lets anyone unlock any email, and `getAllStudents` dumps every student's PII. Use them as-is and Rawani inherits those holes.
- **Recommendation (phase 1):** read through a **read-only DB user** (same MariaDB on the Tarweig VPS). Write through a **small HMAC-authenticated "bridge" endpoint added to aafaq** (create/link account, grant/revoke a package as a `subscriptions` row, disable), reusing aafaq's own helpers. SSO = **same email + password on both sides** (no app release). Real one-tap SSO needs new releases of both apps on 3 platforms, so it is effort L and goes in phase 2.
- **Moyasar trap:** both products use one Moyasar account, and aafaq's webhook unlocks any payment whose metadata carries `email` + `app` (0/1). Rawani payments must **not** use those metadata keys (§5.4).

---

## 1. Apps, stack, base URLs

| | «القراءة السريعة» | «القدرات اللفظية» |
|---|---|---|
| Repo | `github.com/AhmedElhenidy/afaq_reading` → `/home/mostafa/www/afaq_reading` | `github.com/AhmedElhenidy/afaq_abilities_app` → `/home/mostafa/www/afaq_abilities_app` |
| Display name | `ios/Runner/Info.plist:10`, `android/app/src/main/AndroidManifest.xml:9` | `ios/Runner/Info.plist:10`, `AndroidManifest.xml:8` |
| Android id | `com.aafaq.speedreading` (`android/app/build.gradle*:31`) | `tests.aafaq.com.aaafaqtests` (`build.gradle*:24`) |
| iOS bundle / App Store | `com.aafaq.speedreading` (`project.pbxproj:378`) / `id1285567625` | `com.aafaq.aafaqtests` (`project.pbxproj:395`) / `id1282270291` |
| Windows | direct `.exe` at `aafaq.xyz/apps/aafaq-reading-setup.exe` (former MS Store `9NRCNFR9HBQ9`) | `aafaq.xyz/apps/aafaq-abilities-setup.exe` (former `9NKHQW2BBCDB`) |
| Version | `pubspec.yaml` 5.1.3+516 | `pubspec.yaml` 5.1.1+511 (pubspec name `afaq_app`) |
| Code style | Clean-arch features + bloc + dio + get_it | Flat `lib/screens/*`, `http`, globals |
| What it does | Graded reading lessons (grades 1–6 × term) with audio + word-synced transcript; per-lesson exams; "foundation" letters; teacher portal (assign lessons/exams, results) | Verbal-abilities exams (type 0 verbal, 2 quantitative); grades; teacher exam creation |
| API base | Resolved at runtime from `GET /api/mobileConfig` (`lib/main.dart:41-44`). Seed `https://aafaq.xyz/api` (`lib/core/constants/api_constants.dart:11`). Calls `https://aafaq.xyz/api/<route>` | Hard-coded `https://aafaq.xyz/api/public/index.php/<route>` (e.g. `lib/accounts/login.dart:55`) |
| Payment UI | Web `https://aafaq.xyz/payment.php` (`api_constants.dart:34`). RevenueCat code is commented out (`lib/main.dart:27-31`) | Android/web: `payment.php?app=1&useremail=…` (`lib/screens/payment.dart:21`). iOS: RevenueCat product `afaq_verbal_abilities` (`lib/main.dart:16-19`, `lib/screens/ios_payment.dart:106-116`) → `updatePaymentStatus` |
| Deep links | **None.** No intent-filter beyond MAIN/LAUNCHER, no `CFBundleURLTypes`, no associated domains | **None** |

Also: `/home/mostafa/www/aafaq/mobile/` is an **older nested clone** of `afaq_reading` (v2.9.0, with `purchases_flutter`). Do not confuse it with the real app.

**Backend** (`/home/mostafa/www/aafaq`, GitHub `Mostafa1712002/aafaq`, deployed by `git pull` in `/www/wwwroot/aafaq.xyz`):
- Admin panel = raw PHP pages at the repo root (MySQLi, `dbcon.php`), Bootstrap 3 + jQuery.
- Mobile API = **Slim 3.12.5** in one 4,133-line file, `api/public/index.php` (PDO). `api/composer.json` requires `php ^8.1` and `illuminate/database ^10`.
- **Runtime: PHP 8.2** (php-cgi-82 on the Tarweig VPS since the 2026-08-01 migration) and MariaDB 11.4. The "PHP 7.2" in older notes describes the old Hostinger box and is stale. Deprecation notices are suppressed for Slim 3 (`api/public/index.php:2-5`).
- Routing: nginx maps `^/api/(?!public/)(.*)` and `/api/public/index.php/<route>` (PATH_INFO) onto Slim. `api/.htaccess` is the Apache equivalent.
- The same VPS (198.244.189.4) hosts rawaniedu.com (PHP 8.4), alawal.online (منصة الأول) and anawlqdrat.

---

## 2. Identity model

### 2.1 Tables

| Table | Role | Key columns |
|---|---|---|
| `reg_students` | **Every app account: students AND teachers** | `id` int PK AI · `email` varchar(300) **UNIQUE NOT NULL** · `password` · `name` · `mobile` (NOT NULL, default '', not unique) · `gender` int (1 = بنين, 0 = بنات) · `school` int (**authoritative**) + `school_id` int (mirror; writes must set both, per memory `project_aafaq_school_dual_column`) · `school_txt` (free text) · `class` varchar (→ `classes.id`) · `year` (grade 1–6) · `term` · `platform` int (1 = Android, 2 = iOS, 3 = Windows) · `reading_paid` / `abilities_paid` + `*_payment_date` · `has_reading_code` / `has_abilities_code` · `reading_device_id` / `abilities_device_id` · `role` ('student' / 'teacher') + `isTeacher` · `token` (always '') · `datetime` (created) |
| `admin` | Admin-panel users | `name`, `password` (**compared as-is = plaintext**, `checkadmin.php:3`), `token`. Level is derived from the literal name: `admin2`/`admin4` = high (`checkadmin.php:31-40`) |
| `teachers` | Legacy, 0 rows locally | Teacher accounts are actually written to `reg_students` (`teachers.php:97`, `school_teachers.php:77`) |
| `teacher_applications`, `teacher_application_students` | Public "apply as teacher" queue | Approval creates school + class + teacher + students in one transaction (`includes/teacher_application_helper.php`) |
| `students` (20k rows) | Legacy exam-result log, **not accounts** | — |
| `users`, `sessions`, `jobs`, `cache`… | Empty Laravel leftovers in the local DB | — |

Charset: `reg_students` is `utf8mb3_unicode_ci`, `schools_system.name` is `utf8_general_ci`. Joins on text need an explicit `COLLATE`, otherwise MariaDB raises error 1267 (memory note).

### 2.2 Passwords

- **Credential material** = `strtoupper(sha256(plain))`. The server accepts either the plain password or that hash (`api/public/index.php:2299-2307` `credentialMaterial`).
- **Storage (new):** `password_hash(credentialMaterial, PASSWORD_BCRYPT)` (`:2310` `passwordForStorage`). Legacy rows hold a bare SHA-256, a double SHA-256 or plaintext. All are verified by `verifyPassword` (`:2342-2398`) and upgraded to bcrypt on the next successful login (`:2322` `upgradePasswordStorage`). The local DB snapshot is 100% legacy SHA-256; live is mixed.
- Login **returns** the credential material in the `password` field (`:775`). The app stores it and replays it on `saveGrade`, so a leaked SHA-256 works exactly like the password.
- Rawani compatibility: a Laravel `Hash::make(strtoupper(hash('sha256',$plain)))` gives a `$2y$` bcrypt that aafaq's `password_verify` accepts. Rawani can therefore write a valid aafaq password whenever it holds the plaintext (at sign-up or password change).

### 2.3 How accounts are created today

| Path | Where | Notes |
|---|---|---|
| Self-register (app) | `POST /register` (`:1102`), `POST /editedRegister` (`:1147`), both then auto-login | Required: name, mobile, email, password, school, class, year, platform, gender (+ device_id, app). Empty school → `system_settings.default_school_id` (5398 «شركة رواني» live). **INSERT is built by string interpolation** (`:1129`, `:1175`) |
| Teacher self-register (app) | `POST /registerTeacher` (`:2645`) | Hardcodes `$default_school_id = 2` (`:2670`) |
| Teacher application (web) | `teacher_apply.php` → admin approves in `teacher_applications.php` | Creates a `schools_system` row (`source='teacher_application'`, code `TA-<id>`), a class, the teacher and the students. Student passwords are generated and emailed to the teacher only (`teacher_application_helper.php:294-317, 415`) |
| Admin | `teachers.php:97`, `school_teachers.php:77`, `class_students.php:73,221`, `import_excel.php:62-115` (Excel import) | — |

- **Activation:** none. An account is usable immediately. Email is never verified.
- **Disable:** **none.** There is no status column, and all three login functions only check email + password. The only options today are delete (`includes/student_delete.php`: removes `reg_students` plus every table with a `student_id` column, CSV backup first) or revoking the access flags (login still works; paid content locks).

---

## 3. Hierarchy and parent ↔ student

```
educational_companies (id, name, logo)
  └─ educational_complexes (company_id)
       └─ schools_system (complex_id NULLable; branding: logo, colors, about_*, contact_us; source; code; is_active)
            ├─ classes (school_id, year_id, term_id, teacher_id → reg_students.id where role='teacher')
            └─ reg_students.school  (student.class → classes.id, varchar)
```
- `schools_system` holds about 4.6k rows, mostly mined from free-text school names (`migrate_schools.php`). The default school 5398 «شركة رواني» holds about 45k students live (memory). The legacy table `schools` (6 rows, `complex_id`) is still there.
- The school sets the app branding (colors, about, contact): `resolveAbilitiesSchoolId` / `fetchSchoolThemeRow` / `applySchoolContentFallback` (`api/public/index.php:1295-1420`).
- **Parents: no concept anywhere** (no table, column or screen in backend or apps). `reg_students.mobile` is free text, often a family phone for grade 1–6 kids. It is not unique (41k distinct of 48k locally) and not a reliable link. Every parent ↔ child relation must live in Rawani.
- Teacher ↔ student goes through `classes.teacher_id` + `reg_students.class`, plus the teacher-portal assignment tables (`teacher_exam_assignments`, `teacher_lesson_assignments`; production also has `teacher_students`, `student_teacher_assignment`).
- Rawani/ViewClass already has an `educational-companies-complexes` spec. The company → complex → school tree maps 1:1 conceptually, but the ids are separate.

---

## 4. APIs and their auth

**There is no auth layer.** No token, session or API key. CORS is `*` (`:28-44`). Identity is whatever `email` / `user_id` / `userId` the caller sends (`requestUserId()` `:3997`). Only `saveGrade` (`:354`, via `checkUserPass` `:206`) and `updatePassword` (`:497`) verify a password.

| Purpose | Endpoint | Auth | Usable by Rawani? |
|---|---|---|---|
| Create account | `POST /register`, `/editedRegister` | none | Works, but SQL-injectable and returns the login JSON. Needs real `class`/`year`/`school` ids |
| Log in (token issuance) | `POST /login` (`:753`), `/editedLogin` (`:839`), `/editedLogin2` (`:916`) | email + password | Returns the full row + credential material. **No token is issued** (`token` column is always '') |
| Read a user | `POST /getStudentData` (`:1486`) | **none** (email only) | Returns the row minus password, with flags as strings |
| List users | `POST /getAllStudents` (`:2747`) | **none** | Dumps every student (name, email, mobile, school, class…) in one response. A PII leak, not an integration API |
| Unlock app | `POST /updatePaymentStatus` (`:1453`) | **none** | Sets `*_paid=1` for any email. Used by iOS IAP |
| Redeem code | `POST /checkAppCode` (`:230` → `checkTheCode` `:241`) | none | Marks code used, sets `has_*_code=1` |
| Profile / password | `/updateProfile` (`:526`, **no password check**), `/updatePassword` (checks current), `/resetPass` (`:183`, ignores the old password), `/forgetPass` (`:670`, emails a new random password) | mixed | — |
| Delete | `DELETE /deleteStudentByEmail` (`:157`) | **none** | — |
| Config | `GET /mobileConfig` (`:1211`), `/defaultSchool`, `/abilitiesSchool`, `/schools`, `/classes`, `/yearsAndTerms` | none | Read-only metadata |
| Webhook (in) | `moyasar-webhook.php` | Moyasar `secret_token` vs `config.moyasar.webhook_secret` | aafaq's own payments only |
| Webhooks (out) | none | — | — |

---

## 5. Subscriptions, codes, payments, access gating

### 5.1 The four flags and how the apps decide access
- `applyAccessFlags()` (`:2257-2297`) treats paid and code as equivalent in responses: if either `*_paid` or `has_*_code` is 1, both are reported as 1. `getStudentData` stringifies them because the abilities app compares against `'1'` (`afaq_abilities_app/lib/screens/exams_screen.dart:61-65`).
- **Gating is client-side**, from login/getStudentData. The server withholds paid lesson content only when the request carries a `userId` whose account is locked (`requestReadingUnlocked` `:2442`, `isReadingUnlocked` `:4007`, `withoutPaidContent` `:2458`). A request **without** `userId` still gets the full content.
- Teachers pay like students (owner rule 2026-09-15), but the published reading app still opens everything for `isTeacher` (memory; needs an app release).

### 5.2 Writers of the flags
| Source | Writes | Expiry |
|---|---|---|
| App codes (`app_codes` + `app_codes_groups.app` 0 = reading / 1 = abilities, `num_of_mon`). Admin: `add_entrance_codes.php`, `add_reading_codes.php`, `add_abilities_codes.php`, `*_code_groups.php` | `has_*_code=1`, appends user id to `app_codes_groups.users` (CSV) | `update_payments.php:16-44`: after `created_at + num_of_mon` it zeroes the flags for listed users and **deletes the group** |
| Admin subscriptions: `add_subscription.php`, `ajax_quick_subscribe.php` → `subscriptions(subscription_type ∈ company/complex/school/user, entity_id, app_type 0/1/2, start/end, is_active, payment_id UNIQUE)` | `applySubscriptionToUsers()` (`includes/subscription_helper.php:19`) sets `has_*_code=1` on every student under the entity. Teacher-own schools are refused (`isTeacherOwnSchool`) | `cron/expire_subscriptions.php`: zeroes `has_*_code` for the entity once `end_date < today` and nothing else covers it |
| Nightly resync, `cron/resync_subscriptions.php` (CLI only; header says `0 21 * * *` UTC = midnight Riyadh) | Re-runs `resyncAllSubscriptions()`, so students who joined a subscribed school after creation get unlocked by morning | — |
| Moyasar web payment (40 SAR/year per app, `payment.php:72`, entity «Rawani») → `moyasar-webhook.php` / `complete-payment.php` → `applyPaidPayment()` (`includes/payment_activation.php`) | `*_paid=1` + date. Adds a `user` subscription row (1 year, `payment_id` = Moyasar id) and a `payment_events` row | The subscription row expires via the cron above. `*_paid` itself only via `update_payments.php:5-9` (1 year). Whether that script is scheduled live is **unverified**; memory says "`*_paid` never expires anywhere" |
| iOS RevenueCat (abilities only) → `/updatePaymentStatus` | `abilities_paid=1` | Same as above |
| PayPal `$10` (`payment2.php`) | legacy, browser-confirmed only | — |

There is **no Google Play billing** in either app. Android users pay on the web page.

### 5.3 How a Rawani package maps onto this
Use what already exists: one `subscriptions` row per student per package, `subscription_type='user'`, `app_type` 0 / 1 / 2 (reading / abilities / both), `end_date` = package end, `payment_id` = a Rawani reference such as `rawani:<order_id>` (UNIQUE gives idempotency), followed by `applySubscriptionToUsers()`. Expiry and nightly resync then work with no new code.
Caveat: on expiry the cron zeroes `has_*_code` even if the student also redeemed a code. This is pre-existing behaviour, but packages will hit it more often.

### 5.4 Moyasar collision (important)
Rawani's `config/moyasar.php` says "One Moyasar account serves more than one product" and tags payments with a `site` in metadata. aafaq's `paymentBuyer()` (`includes/payment_activation.php:19-38`) reads **`metadata.email` + `metadata.app`** and ignores `site`. So:
- If a Rawani payment ever carries `metadata.app` = 0/1 plus an email, and Moyasar delivers it to aafaq's webhook, **aafaq unlocks that email for 1 year and records a subscription.**
- Payments without those keys still land in aafaq's `payment_events` as "no buyer on the payment", and `payments.php` flags them as unmatched (admin noise).

Mitigation: Rawani uses distinct metadata keys (no `app`/`email`). Also add a one-line `site` filter to aafaq's webhook, or give each product its own webhook URL and filter there.

---

## 6. Local DB counts (snapshot, not live)

The local `aafaq` DB is an **old, partial snapshot**: newest account 2025-11-02, before the `schools_system` relink. Live is larger (ids above 139,226; school 5398 alone holds about 45k). Re-count on live, read-only, before sizing anything.

| Metric | Local |
|---|---|
| `reg_students` total | 47,957 |
| — role student / teacher (`isTeacher=1`) | 47,935 / 22 |
| — platform Android / iOS / Windows / 0 | 18,742 / 28,406 / 808 / 1 |
| — `has_reading_code=1` / `has_abilities_code=1` | 46,244 / 46,248 (codes/subscriptions were applied broadly) |
| — `reading_paid=1` / `abilities_paid=1` | 799 / 346 |
| — with a reading / abilities device id | 27,138 / 3,669 |
| — password formats | 47,941 SHA-256, 16 other, 0 bcrypt |
| — no mobile / distinct mobiles | 297 / 41,022 |
| — year 0 (no grade) / grades 1–6 | 27,323 / 20,633 |
| `admin` rows | 9 |
| `app_codes` used / unused; groups (reading / abilities) | 1,468 / 13,186; 6 / 6 |
| `schools_system` rows (with complex) | 4,644 (8) |
| `subscriptions` | 1 (complex, both apps) |
| `classes` | 6 |
| `reg_results` | 1,421 results from 623 users |
| Parents | n/a (no entity) |

---

## 7. Integration options for Rawani

Requirements recap: Rawani packages (CRUD, price, Moyasar) pick platforms. Buying or assigning a package auto-creates an account on each included platform. Rawani is the master view of all users and parents across platforms.

### (a) API: Rawani calls aafaq over HTTP
- **Exists:** `/register`, `/getStudentData`, `/updatePaymentStatus`, `/getAllStudents`. All unauthenticated; `register` is SQL-injectable; `updatePaymentStatus` sets the 1-year `*_paid` flags with no end date and no package link; nothing can disable an account.
- **Must build (recommended):** one new file, e.g. `api/public/rawani_bridge.php` or a Slim route group `/rawani/*`, authenticated by an HMAC over body + timestamp with a shared secret in `api/config/config.php`. Requests are server-to-server on the same VPS. Operations:
  1. `upsertAccount` (email, name, mobile, gender, year, term, school optional → default 5398; plaintext or SHA-256 password) → returns `{id, created|linked}`. Uses `passwordForStorage` and sets both `school` + `school_id`.
  2. `grantPackage` (account id, app_type, start, end, ref) → `subscriptions` row + `applySubscriptionToUsers`.
  3. `revokePackage` (ref) → `is_active=0` + reset flags (same logic as the expire cron).
  4. `disable` / `enable` → needs a new `reg_students.is_active` (or `disabled_at`) column **and** a check in `login`, `editedLogin`, `editedLogin2` (+ `checkUserPass`). This is server-only; the app shows the returned `message`.
  5. Optional: `listAccounts?updated_since=` for sync. Reads are easier through (b).
- **Effort:** M for the bridge (about 1–2 days including tests on a local copy) + S for the disable column.
- **Risks:** shared-secret handling; PHP 8.2 + Slim 3 quirks (stray output breaks responses); deploy is `git pull` on a box where root-owned files have broken php-fpm before (memory 2026-10-28).

### (b) Direct DB: same MariaDB on the Tarweig VPS
- **Exists:** same host. aafaq already uses a scoped user (`aafaq_app`), and rawaniedu.com has its own DB on the same server.
- **Reads (recommended):** a new **read-only** MariaDB user on `aafaq`, SELECT on `reg_students` (columns minus `password`), `schools_system`, `educational_companies`, `educational_complexes`, `classes`, `years`, `terms`, `subscriptions`, `reg_results`. Configure a second Laravel connection in Rawani. Either query live (paginated) or sync nightly / on demand into a Rawani `platform_accounts` table (platform, external_id, email, name, school, flags, last_seen). **Effort S.**
- **Writes from Laravel directly: not recommended.** Rawani would have to re-implement the dual `school`/`school_id` rule, the default-school lookup, the password format, the subscription fan-out and the teacher-school guard. Those rules live in aafaq PHP and change (several fixes Aug–Nov 2026), so the two would drift. If chosen anyway: **effort M**, and every aafaq-side change becomes a Rawani change.
- **Risks:** collation mismatch on joins (utf8mb3_unicode_ci vs utf8_general_ci); the size of `reg_students` (100k+ live); a DB user with write grants on a DB with no FKs (orphan rows); backups (`/root/daily-backup.sh` already dumps it).

### (c) SSO / auto-login into the apps
- **Exists:** nothing token-based. No deep-link handlers in either app (§1), and app state lives in SharedPreferences (`userEmail`, `userId`, `password` / `userPassword`). Login accepts the plain password **or** its uppercase SHA-256.
- **Option c1, shared credentials (recommended for phase 1):** at provisioning Rawani sends the user's plaintext (or SHA-256) once, so the aafaq account has the **same email + password** as the Rawani account. The user signs into each app with their Rawani login. Zero app work. **Effort S** (part of (a)). Weakness: passwords diverge when changed on one side. Mitigate by having Rawani's password-change push to the bridge, and decide whether aafaq-side changes (`updatePassword`, `forgetPass`) should be disabled for Rawani-managed accounts or accepted as drift.
- **Option c2, real one-tap SSO:** a Rawani page "open in app" → `rawani-reading://sso?code=<one-time>` / `rawani-abilities://…` (or https App Links / Universal Links on aafaq.xyz). The app exchanges the code at a new `POST /ssoExchange` (one-time, about 60 s, bound to the account) and gets the normal login JSON. Needs intent-filters, `CFBundleURLTypes`/associated domains, `app_links` handling in **both** apps, new Android + iOS store releases (review), and Windows installers that **cannot be rebuilt from Linux** (memory). The two apps are maintained by an external developer (Ahmed Elhenidy). **Effort L**; app-release gated.
- **Option c3, web hand-off only:** Rawani links to `aafaq.xyz/downloads.php` / store pages. Trivial; no login help.

### Recommended shape
1. Phase 1: (b) read-only for the master user list + (a) bridge for create/link/grant/revoke/disable + (c1) shared credentials. Rawani stores the mapping `rawani_user_id ↔ (platform='aafaq', external_id=reg_students.id, email)`.
2. Account linking rule (needs owner decision): if the email already exists in aafaq (very likely for some buyers), **link and grant**; never overwrite the existing password or school.
3. Parents exist only in Rawani. A parent buying for a child creates or links the child's `reg_students` row; the parent's own email is not used for the child (email is UNIQUE).
4. A one-line aafaq webhook `site` filter + distinct Rawani metadata keys (§5.4).
5. Phase 2: c2 if the client wants one-tap.

---

## 8. Gotchas and security notes (reported, not fixed)

1. **Unauthenticated state-changing endpoints:** `updatePaymentStatus` (`:1453`, unlock anything by email), `deleteStudentByEmail` (`:157`), `updateProfile` (`:526`, change any user's email by id: account takeover together with `forgetPass`), `resetPass` (`:183`, ignores the old password), `checkAppCode` for any `user_id`.
2. **PII dump:** `getAllStudents` (`:2747-2777`) returns every student (name, email, mobile) with no auth; `getStudentData` returns anyone's row by email.
3. **SQL injection:** `register` / `editedRegister` INSERTs (`:1129`, `:1175`), `forgetPass` (`:679`); several admin pages build SQL by concatenation.
4. **`forgetPass`** replaces the password immediately with a random one and mails it, so anyone can lock a user out by email.
5. **XSS:** `payment.php:67-68` echoes `$_GET['app']` / `$_GET['useremail']` raw into JS.
6. **Admin auth:** `admin.password` compared in plaintext (`checkadmin.php:3`); level by literal name; many admin pages redirect via JS without `exit` (memory: `curl notifications.php` returns the page). No CSRF.
7. **Web-reachable "cron" scripts:** `update_payments.php` (no auth, mass-updates flags and deletes code groups); `cron/expire_subscriptions.php` accepts a hardcoded `?key=` from the URL; nginx ignores `cron/.htaccess`, so check that `cron/` is denied in the vhost.
8. **Secrets in DB:** the local `system_settings` holds OpenAI and AssemblyAI API keys in plaintext. A read-only Rawani user must **not** get SELECT on `system_settings`.
9. The **credential material is a password equivalent** (SHA-256 returned by login, stored on device), so any bridge must treat it as a secret.
10. **Abilities app iOS payment page uses `app=0`** (`afaq_abilities_app/lib/screens/ios_payment.dart:20`), so a web payment there would unlock *reading* instead of abilities.
11. Reading app still calls the **retired dev host** `https://aafaq.newaves-systems.com/api/checkStudCode` (`afaq_reading/lib/features/exam/presentation/widgets/exam_view_body.dart:708`).
12. `registerTeacher` hardcodes default school `2` (`:2670`) instead of `getDefaultSchoolId()`.
13. No FKs: `reg_students` deletes leave orphans unless `includes/student_delete.php` is used. Ids are reused only by AUTO_INCREMENT, so never hard-code ids across systems.
14. Runtime notes: PHP 8.2 + Slim 3 (any `echo` before `run()` breaks responses); MariaDB `STRICT_TRANS_TABLES` (`platform`/`gender` are ints); never run aafaq PHP as root on prod (root-owned `logs/` dirs broke deletes 2026-10-28); `curl | python` truncates via rtk on that box (memory).
15. The paid flags appear never to expire live (memory). If packages rely on `*_paid`, a "package" becomes lifetime. Use `subscriptions` + `has_*_code` instead (§5.3).

---

## 9. Open questions for the owner / plan
1. Existing aafaq account with the same email: link + grant (recommended) or refuse?
2. Package per student or per family (parent buys N children)? This sets the bridge payload.
3. Should Rawani-managed accounts still be able to change their password inside the apps (drift) or not?
4. Disable semantics: block login entirely (needs the new column + login check) or just lock paid content (exists via flags)?
5. Is the iOS RevenueCat purchase and the 40 SAR web payment kept alongside packages, or replaced by Rawani packages?
6. Confirm on live (read-only): is `update_payments.php` / `expire_subscriptions.php` scheduled, and what are the real live counts?
