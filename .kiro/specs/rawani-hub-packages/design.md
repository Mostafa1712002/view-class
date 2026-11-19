# Design: Rawani hub and packages

Read with `requirements.md`. File references point to files and symbols rather than line numbers, because main moves.

## 1. Architecture

```
                       rawaniedu.com (ViewClass, RAWANI_HUB_ENABLED=true)
 ┌───────────────────────────────────────────────────────────────────────────────┐
 │ app/Modules/RawaniHub                                                         │
 │  Packages CRUD ─┐                                                             │
 │  Admin grant ───┼─► hub_subscriptions ──► EntitlementCalculator              │
 │  Moyasar ───────┘        ▲                    │ marks hub_platform_accounts  │
 │  checkout/webhook/       │                    ▼ needs_sync=1                 │
 │  reconcile          hub_payments     SyncPlatformAccountJob (queue, retries) │
 │                                               │ PlatformClient (one class,   │
 │  Directory ◄── hub:mirror (nightly) ◄─────────┤ HMAC, shared contract)       │
 └───────────────────────────────────────────────┼───────────────────────────────┘
                                                  │ HTTPS  /…/rawani/v1/*
               ┌──────────────────────────────────┼──────────────────────────┐
               ▼                                  ▼                          ▼
  dashboard.anawlqdrat.com             aafaq.xyz/api/rawani/v1      alawal.online/api/rawani/v1
  /api/v1/rawani/v1/* (Laravel 10)     (Slim 3 route group)          (Laravel 9)
```

The shared contract means **one HTTP client in Rawani and no per-platform adapters**. Each platform's differences are stored as data on its registry row: products, capabilities and required fields. The receiving side of each sub-platform maps the contract onto its own model.

**No direct DB access.** Decision D1 allowed read-only DB access if it clearly shortens phase 1. It does not, for four reasons:
- All three platforms need the API for writes anyway, and adding a `people` list endpoint to it costs about half a day.
- DB reads would couple Rawani to drifting schemas. anawlqdrat's live columns differ from its migrations, and alawal uses legacy names such as `my__parents` and `Grade_id`.
- DB reads would need three more DB users on the VPS.
- They risk exposing tables that hold secrets, such as aafaq `system_settings`.

---

## 2. Switching the hub on per instance

- New file `config/rawani_hub.php`:
  - `enabled` from `env('RAWANI_HUB_ENABLED', false)`
  - `default_school_id` from `env('RAWANI_HUB_SCHOOL_ID')`, the school that hub-created people are placed in, a dedicated «رواني أونلاين» school created through the UI on rawaniedu
  - `sync_backoff`
- `routes/web.php` and `routes/api.php` require the module route files only `if (config('rawani_hub.enabled'))`. `route:cache` is per instance, so this is safe.
- In `routes/console.php`, the hub schedules sit inside the same `if`.
- Sidebar items are wrapped in `Route::has('admin.hub.packages.index')`, so viewclass shows nothing.
- `.env` on rawaniedu: `RAWANI_HUB_ENABLED=true`, `RAWANI_HUB_SCHOOL_ID=<id>`, `MOYASAR_SITE=rawani`, `MOYASAR_MODE` and keys, and `MOYASAR_WEBHOOK_SECRET` (the secret of **Rawani's own** webhook registration).
- viewclass `.env` is unchanged. Migrations run there and create empty tables.

---

## 3. The Rawani module (`app/Modules/RawaniHub/`)

```
RawaniHub/
├── Actions/        CreatePackage, UpdatePackage, GrantPackage, StartCheckout, ApplyPaidPayment,
│                   RecordRefund, CancelSubscription, FindOrCreateBeneficiary, LinkPlatformAccount,
│                   ResetAccountPassword, SetAccountEnabled, IssueSsoLink
├── Controllers/    Admin/{Platforms,Packages,Subscriptions,Payments,Directory,People}Controller,
│                   My/MyPackagesController, MoyasarWebhookController
├── DTOs/           PackageData, GrantData, AccountState
├── Http/Requests/  one per form
├── Jobs/           SyncPlatformAccountJob, MirrorPlatformPeopleJob
├── Console/        hub:sync-pending, hub:expire, hub:reconcile-payments, hub:mirror
├── Models/         HubPlatform, HubPackage, HubPackageItem, HubSubscription, HubPayment,
│                   HubPaymentEvent, HubPlatformAccount, HubProfile
├── Repositories/   Contracts/{Package,Subscription,Payment,Platform,PlatformAccount}Repository + Eloquent*
├── Services/       PlatformClient (HMAC + HTTP), MoyasarGateway (invoice create/fetch, payment fetch),
│                   EntitlementCalculator
├── Routes/         web.php (admin + my), api.php (webhook)
└── views → resources/views/hub/**   lang → lang/{ar,en}/hub.php
```

Migrations go in `database/migrations/2026_11_2x_*_hub_*.php`, following the repo's existing convention (the points module did the same).

### 3.1 Schema

None of these tables has a `school_id`: they are platform-level and super-admin only (see the CLAUDE.md note on tenant scope). New tables do not use soft deletes, except `hub_packages`, because packages are referenced by history.

```sql
hub_platforms (
  id, slug varchar(32) UNIQUE,            -- anawlqdrat | aafaq | alawal
  name_ar, name_en, base_url,             -- e.g. https://alawal.online/api/rawani/v1
  secret text NULL,                       -- Eloquent 'encrypted' cast (as SchoolWhatsappSetting)
  products json,                          -- [{"code":"reading","name_ar":"القراءة السريعة"}, ...]
  capabilities json,                      -- {"sso":true,"parents":true,"roles":["student","parent"]}
  required_fields json,                   -- {"student":["national_id","gender","stage","grade"],"parent":["national_id"]}
  is_enabled bool default 0, last_ok_at NULL, last_error text NULL, timestamps)

hub_packages (
  id, name_ar, name_en NULL, description_ar text NULL,
  price decimal(10,2), currency char(3) default 'SAR', duration_days int unsigned,
  is_purchasable bool default 0, is_active bool default 1, sort int default 0,
  created_by FK users NULL, timestamps, deleted_at)

hub_package_items (
  id, package_id FK cascade, platform_id FK, product_code varchar(32),
  UNIQUE(package_id, platform_id, product_code))

hub_profiles (                              -- data the platforms need that ViewClass users lack
  user_id PK FK users cascade, stage enum('primary','intermediate','secondary') NULL,
  grade tinyint NULL, timestamps)          -- gender and national_id stay on users

hub_subscriptions (
  id, user_id FK users (beneficiary), buyer_id FK users NULL, package_id FK,
  items json,                             -- snapshot [{platform_id, product_code}]
  price decimal(10,2), source enum('purchase','grant'),
  status enum('pending','active','expired','cancelled','refunded'),
  starts_at datetime NULL, ends_at datetime NULL,
  granted_by FK users NULL, note varchar(500) NULL, timestamps,
  INDEX(user_id, status), INDEX(status, ends_at))

hub_payments (
  id, subscription_id FK UNIQUE, buyer_id FK users,
  amount int unsigned,                    -- halalas
  currency char(3), status enum('pending','paid','failed','refunded'),
  moyasar_invoice_id varchar(64) UNIQUE NULL, moyasar_payment_id varchar(64) UNIQUE NULL,
  paid_at NULL, refunded_at NULL, refunded_by FK users NULL, refund_note varchar(500) NULL,
  last_checked_at NULL, timestamps)

hub_payment_events (
  id, event_type varchar(64), moyasar_payment_id varchar(64) NULL, site varchar(32) NULL,
  payload json,                           -- secret_token removed before save
  outcome varchar(32),                    -- applied | duplicate | ignored_site | unmatched | rejected
  created_at)

hub_platform_accounts (                    -- the person↔account map AND the directory mirror
  id, platform_id FK, role enum('student','parent','teacher'),
  user_id FK users NULL,                  -- the Rawani person; NULL = mirrored, unlinked
  external_id varchar(64) NULL,           -- their PK
  login varchar(191) NULL,                -- what the person types to log in there
  password text NULL,                     -- 'encrypted' cast; managed accounts only
  managed bool default 0,                 -- 1 = Rawani created it; 0 = pre-existing, linked
  link_confirmed_by FK users NULL,        -- admin who confirmed a link (or NULL if managed)
  status enum('pending','needs_info','active','conflict','failed','disabled','missing','unlinked'),
  enabled bool default 1,                 -- desired state (admin toggle)
  needs_sync bool default 0, attempts tinyint default 0,
  last_error text NULL, synced_at NULL,
  -- mirror columns (filled by hub:mirror; read-only facts from the platform)
  name, email NULL, phone NULL, national_id NULL, parent_external_id varchar(64) NULL,
  org_label varchar(255) NULL, access json NULL, seen_at NULL, timestamps,
  UNIQUE(platform_id, role, external_id),
  UNIQUE(platform_id, role, user_id),       -- MySQL allows many NULLs
  INDEX(status), INDEX(needs_sync), INDEX(national_id), INDEX(email), INDEX(phone))
```

Two tables collapse into one here: the account map and the directory mirror. A row created by provisioning has `user_id`, and its `external_id` is filled once the platform answers. A row created by the mirror has `external_id` and starts with `user_id NULL` (`unlinked`). Linking sets `user_id`. The unique keys keep this from producing duplicates.

The mirror matches each incoming item in this order:
1. If the item carries a `rawani_id`, by (platform, role, `user_id`). This catches a create that timed out before Rawani stored the `external_id`.
2. Otherwise by (platform, role, `external_id`).

The mirror fills only the mirror columns, never the provisioning ones.

### 3.2 Entitlements (`EntitlementCalculator`)

For person U and product (P, X), the window is computed from U's `active` subscriptions whose `items` snapshot contains (P, X):

```
from = min(starts_at)
until = max(ends_at)
```

Renewals stack (the new `starts_at` is the old `ends_at`), so the windows are contiguous. Expired, cancelled and refunded subscriptions do not count. No active subscription means no entitlement for that product.

`AccountState` is the absolute state Rawani sends for one account:

```json
{ "profile": {...}, "enabled": true, "password": "only on create/reset",
  "link_external_id": "only when an admin confirmed a link",
  "entitlements": [{"product":"reading","from":"2026-11-19T00:00:00Z","until":"2027-11-19T00:00:00Z"}] }
```

Every change (activate, expire, cancel, refund, enable or disable, a profile edit, a link) only sets `needs_sync=1` on the affected accounts and dispatches the job **after commit**. The job builds the state when it runs, so a stale or duplicate job does no harm.

Which accounts a subscription affects:
- the beneficiary's account on each platform in the snapshot
- on platforms with `capabilities.parents=true` (Al-Awwal), also the beneficiary's primary parent from `parent_student`, as a `parent` row

A parent row has no entitlements, so its state is only profile and enabled. **Al-Awwal parent accounts are never disabled automatically**, because they may cover children outside Rawani.

### 3.3 Account state machine

```
                ┌────────── admin fills missing data ──────────┐
                ▼                                              │
(subscription) pending ──job──► active ◄──── admin link ──── conflict
                │   │             │  ▲                          ▲
                │   │   enabled=0 ▼  │ enabled=1                │ 409 ACCOUNT_EXISTS
                │   │          disabled                         │
                │   └── 4xx (422/409 other) / retries exhausted ─► failed ──admin «إعادة المحاولة»──► pending
                └── required field missing ─► needs_info
mirror only:  unlinked ──admin link/create person──► active        any ──full sweep, not seen──► missing
```

- `SyncPlatformAccountJob`:
  - `ShouldBeUnique` per account id.
  - `$tries = 6`, `backoff = [60, 300, 900, 3600, 21600]`.
  - Retries only on timeout, 5xx and 429. Other 4xx responses settle the status at once.
- `hub:sync-pending` runs every 5 minutes. It re-dispatches rows with `needs_sync=1` in `pending`, `active` or `disabled` whose last attempt was more than 10 minutes ago. This recovers lost jobs.
- Before calling, the job checks `required_fields` against the person and their `hub_profiles` row. If something is missing it sets `needs_info` and stores the list of missing fields in `last_error`.
- Platform disabled → skip; the row stays dirty.
- A student account on a platform with `parents=true` waits (stays `pending`, no HTTP call) until its parent's account on that platform is `active`. When the parent turns active, the job sets `needs_sync` on the waiting children.

### 3.4 Credentials

- The password is generated with `Str::password(10, symbols: false)`. Al-Awwal requires at least 6 characters, and the other platforms have no rule.
- One password per person: on creating a second managed account, the job reuses the password stored on the person's first managed account.
- The password is sent only when the account is created, or after «إعادة تعيين كلمة المرور».
- The login is whatever the platform returns: the national ID on Al-Awwal, `rw<id>` on anawlqdrat, the email on aafaq.
- Delivery is on screen only: the admin person page, «باقاتي» for the beneficiary and their parent, and a printable card that reuses the user-card PDF styling. Rawani mail is `MAIL_MAILER=log` (rawaniedu-instance spec), so nothing claims to have sent an email. Risk R6 covers this.

### 3.5 Screens and routes (Arabic labels)

All admin routes sit in `Route::middleware(['auth','role:super-admin'])->prefix('admin/hub')->name('admin.hub.')`. Gating is by **role, not by `canDo()`**:
- `canDo()` default-allows any slug without a permissions row (see the `PointsPolicy` note).
- rawaniedu reseeds wipe migration grants.

So no `PermissionCatalog` rows are added. If the client later wants non-super-admin hub staff, add a `hub` group and a seed migration in the points-371 style (open question Q8).

| Screen (ar) | Route | Notes |
|---|---|---|
| المنصات المرتبطة | `GET platforms`, `GET/PUT platforms/{p}/edit`, `POST platforms/{p}/test`, `POST platforms/{p}/secret` (generate + show once) | The three rows are created by the admin through this screen. The products, capabilities and required-fields JSON is edited with a small form, with defaults from §5. |
| الباقات | `resource packages` (index/create/store/edit/update/destroy), `PATCH packages/{p}/toggle` | Form fields: الاسم، الوصف، السعر (ر.س)، المدة (يوم)، المنتجات (checkbox per platform product)، متاحة للشراء، مفعّلة، الترتيب |
| الاشتراكات | `GET subscriptions` (filters: package, status, source, date), `GET/POST subscriptions/grant`, `POST subscriptions/{s}/cancel` | «منح باقة»: pick a student, or a parent and then children (multi-select), or «طالب جديد» (name, gender, national ID, stage, grade, optional parent: name and national ID). Then pick the package, the start date (default today) and a note. |
| المدفوعات | `GET payments`, `POST payments/{p}/refund`, `POST payments/{p}/check` (re-fetch from Moyasar) | Flags: «مدفوعة بدون اشتراك فعّال», «لم تُطابق» (unmatched events) |
| دليل المستخدمين | `GET directory` | Tabs: «أشخاص رواني» (users with any hub account or subscription) and «حسابات المنصات» (all `hub_platform_accounts`). Filters: المنصة، الدور، الحالة، مرتبط/غير مرتبط، بحث |
| ملف الشخص | `GET people/{user}` | Profile and parent↔children; subscriptions; per-platform accounts with status, login, «إظهار كلمة المرور», last sync, error, and the actions «إعادة المحاولة», «تعطيل/تفعيل», «إعادة تعيين كلمة المرور», «ربط بحساب موجود» |
| ربط حساب | `GET accounts/{a}/link`, `POST accounts/{a}/link`, `POST accounts/{a}/create-person`, `DELETE accounts/{a}/link` | Shows candidates ranked by national ID, then real email, then normalised phone (`PhoneNormalizer`). Never by name, and never automatic. |
| باقاتي (parent/student) | `GET my/packages`, `GET my/packages/{package}/buy`, `POST my/packages/{package}/checkout`, `GET my/packages/return/{payment}`, `GET my/packages/children/create`, `POST my/packages/children`, `GET my/packages/accounts/{a}/open` (SSO, phase 5) | Under `auth` + `role:student,parent`. The parent checkout requires `child_id ∈ auth()->user()->children`. The return page polls account statuses, showing «جارٍ إنشاء الحسابات…». |
| Webhook | `POST /api/hub/moyasar/webhook` (routes/api.php group, no CSRF, `throttle:60,1`) | Registered only when the hub is enabled. |

**Sidebar:** add a block «منصات رواني» inside the super-admin `extras` section (`resources/views/components/sidebar.blade.php`, next to the `isSuperAdmin()` promotion links, using `data-section="extras"`). It holds the items الباقات، الاشتراكات، المدفوعات، دليل المستخدمين، المنصات المرتبطة, each wrapped in `@if(Route::has(...))`. Students and parents get «باقاتي» in their own section, also gated by `Route::has('my.packages.index')`.

**Lang:** one file per screen group, so that parallel tasks never edit the same file:
- `hub_admin.php` (platforms, packages, subscriptions, person page)
- `hub_my.php` («باقاتي» and SSO)
- `hub_payments.php`
- `hub_directory.php`

Each exists in `lang/ar` and `lang/en` and uses flat keys only. The points module learned that dotted nested keys break `__()`.

### 3.6 Who buys

- **A parent for a child:** the main case. One subscription per child; buying for two children means two checkouts. Multi-child carts are skipped until the client asks.
- **A student for themself.** If the package includes Al-Awwal, the student must have a linked parent with a national ID, otherwise checkout shows `needs_info` guidance before payment.
- **An admin grant:** for anyone, free.
- **No public sign-up in this spec.** `/login` is the only entry point today, and buyers are accounts an admin created (or that came from admissions). Public registration plus checkout is open question Q1.

New beneficiaries are created by `FindOrCreateBeneficiary`:
- role `student`
- `school_id = config('rawani_hub.default_school_id')`, so the existing `my/*` pages don't 403 on a NULL school
- an email only if a real one was given, otherwise the repo's usual `{username}@viewclass.local`
- the parent link through `parent_student`

A national ID that already belongs to another Rawani user **blocks** self-service with «يرجى التواصل مع الدعم». An admin resolves it, which prevents hijacking through someone else's ID.

### 3.7 Audit

Use `App\Models\ActivityLog::log(...)`, the existing helper, for every admin mutation and for each `failed` or `conflict` transition. No new audit table.

---

## 4. Shared sub-platform contract `rawani/v1`

All three platforms implement exactly this contract. Base URLs, stored in `hub_platforms.base_url`:
- alawal: `https://alawal.online/api/rawani/v1`
- anawlqdrat: `https://dashboard.anawlqdrat.com/api/v1/rawani/v1`
- aafaq: `https://aafaq.xyz/api/rawani/v1` (nginx already maps `/api/*` to Slim)

### 4.1 Authentication

Request headers:

```
X-Rawani-Timestamp: 1763550000             # unix seconds
X-Rawani-Nonce: 6f1c2a7e-4b1d-4c55-9a3e-2d7f8b9c0a11   # UUID v4, single use
X-Rawani-Signature: <hex HMAC-SHA256(secret, canonical)>
Content-Type: application/json
```

```
canonical = METHOD + "\n" + LOGICAL_PATH + "\n" + timestamp + "\n" + nonce + "\n" + hex(sha256(raw_body))
```

- **METHOD** is uppercase.
- **LOGICAL_PATH** is the path **after** the contract base, starting with `/`, without the query string. Example: `/accounts/student/42`. Each host strips its own prefix, so aafaq's `/api/public/index.php/rawani/v1/...` and `/api/rawani/v1/...` both sign `/accounts/...`.
- **Query string (GET):** sorted by key, RFC 3986 encoded (PHP `http_build_query($q, '', '&', PHP_QUERY_RFC3986)` after `ksort`), and appended to the path as `?a=1&b=2`.
  - If there is no query, there is no `?`.
  - Empty-valued params are **not sent and not signed**.
  - The client builds the query string once and uses the same bytes in the URL and in the signature.
  - The server rebuilds it from the parsed query with the same function.
- **raw_body** is the exact bytes sent. For a GET it is the empty string, whose sha256 is `e3b0c442…b855`.
- **Verification:**
  1. `abs(now - timestamp) <= 300`, else 401 `STALE_TIMESTAMP`.
  2. `hash_equals(expected, header)` against the current secret or the previous one (`RAWANI_HUB_SECRET`, `RAWANI_HUB_SECRET_PREVIOUS`, which allows rotation), else 401 `INVALID_SIGNATURE`.
  3. The nonce has not been seen in the last 600 s, else 401 `REPLAYED_NONCE`.
- **Where nonces are stored:**
  - Laravel (alawal, anawlqdrat): `Cache::add('rawani-nonce:'.$nonce, 1, 600)` returns false when the nonce was already seen. Both apps use a file or DB cache, which is fine on a single box.
  - aafaq: table `rawani_nonces(nonce CHAR(36) PK, created_at)` with `INSERT IGNORE`. Zero affected rows means a replay. Rows older than one day are pruned on each call.
- Also apply `throttle` (120/min) on the group. Logs must never print the secret, the signature or passwords.

**Shared test vector.** Every implementation's test must reproduce this value.

```
secret    = test-secret-0123456789abcdef0123456789abcdef
method    = PUT
path      = /accounts/student/42
timestamp = 1763550000
nonce     = 6f1c2a7e-4b1d-4c55-9a3e-2d7f8b9c0a11
body      = {"enabled":true}
sha256(body) = 26b3426b2593763c96d0890b4a77a0bbf66d13fc512b0c6b138a23c290f30a2a
signature    = 9117d1e5c2e4be4d2455f1971c4b5b1fd16761260eb8e4b05d7486d4563b3672   # recomputed 2026-11-19 with openssl (the earlier value was wrong)

# second vector: GET with an encoded query
method    = GET
query in  = {per: 500, cursor: "a b/c"}
path      = /people?cursor=a%20b%2Fc&per=500
timestamp = 1763550000
nonce     = 0b7e1c4a-9d2f-4e61-8a3b-5c6d7e8f9a01
body      = (empty)  sha256 = e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
signature = 048cf64cf8727ac5b6688acff0a2b9a68d134b65ff99fb5c49f5350ee9985745
```

### 4.2 Envelope

All four endpoints use the same envelope on all three hosts. It is ViewClass's own (`App\Support\ApiResponse`). anawlqdrat uses `{code,status,message,data}` elsewhere and keeps that for its other endpoints.

```json
{ "success": true,  "data": { ... }, "message": "" }
{ "success": false, "error": { "code": "ACCOUNT_EXISTS", "message": "…", "details": { ... } } }
```

| HTTP | code | Rawani reaction |
|---|---|---|
| 401 | `INVALID_SIGNATURE`, `STALE_TIMESTAMP`, `REPLAYED_NONCE` | `failed`; the platform row gets `last_error` (check the secret or the clock) |
| 404 | `NOT_FOUND` | Only for `sso`. Re-sync the account. |
| 409 | `ACCOUNT_EXISTS` (`details.external_id`, `details.login`) | `conflict`; an admin links or resolves it |
| 409 | `IDENTIFIER_TAKEN` (e.g. the same national ID is on a teacher, or a duplicate teacher ID) | `conflict` |
| 422 | `VALIDATION_FAILED` (`details` = field errors), `PLACEMENT_UNAVAILABLE` (alawal has no section for that stage, gender and grade) | `failed` with the message |
| 501 | `NOT_SUPPORTED` | e.g. `sso` on aafaq. The UI uses the fallback. |
| 429, 5xx, timeout | `PLATFORM_ERROR` | Retry with backoff |

### 4.3 Endpoints

**1. `GET /ping`** returns `{"platform":"alawal","contract":"rawani/v1","time":"2026-11-19T10:00:00Z","capabilities":{"sso":true,"parents":true}}`.

**2. `PUT /accounts/{role}/{rawani_id}`** upserts the account and applies the full state. `role` is `student` or `parent`. This is the only write endpoint, and it is idempotent.

```json
{
  "profile": {
    "name": "…", "gender": "male|female",
    "national_id": "1234567890|null", "email": "real@x.com|null", "phone": "+9665…|null",
    "stage": "primary|intermediate|secondary|null", "grade": 1,
    "parent_rawani_id": 77      // student role, platforms with parents only; the parent is its own account (see rule 8)
  },
  "enabled": true,
  "password": "only-on-create-or-reset",          // omitted otherwise; never stored in logs
  "link_external_id": "5531",                     // only after admin confirmation
  "entitlements": [ { "product": "main", "from": "…Z", "until": "…Z" } ]  // full list of Rawani grants; [] = none
}
```

Rules every platform follows:
1. **Find the account.** First by its stored Rawani id (a new column or table on their side). If that finds nothing and `link_external_id` is given, by that id. If neither applies, create the account.
   - Before creating, check the platform's natural key: alawal (role, national ID), aafaq email, anawlqdrat the generated username. If an unlinked account already has that key, return **409 `ACCOUNT_EXISTS`**. Never take over an account silently.
2. **Create:** the platform sets every field its login and gating need (§5), stores the Rawani id, and marks the account **managed**. It sets the password if one was sent. If none was sent on create, it sets a random password, which can only be reached through SSO.
3. **Link (`link_external_id`):** store the Rawani id on the existing account and make **no** changes to its password, school, org or profile. The account stays non-managed.
4. **Profile:** for managed accounts, update the name, gender, stage and grade (placement). For non-managed accounts, ignore the profile.
5. **`enabled`:**
   - `false` blocks login and kills existing sessions or tokens.
   - `true` unblocks, but only if Rawani was the one that blocked it. Each platform stores a `rawani_disabled` flag so that Rawani never re-enables an account the platform's own admin blocked.
6. **`entitlements`:** replace all Rawani-owned grants for this account with the list.
   - Only grants tagged as Rawani's are touched. Access from schools, orgs, codes and other payments is never changed.
   - An empty list removes Rawani's grants and nothing else.
7. **`password` on an existing account:** if the account is managed, set it (this is the reset). If it is linked, ignore it. Never return it.
8. **Parents (platforms with `parents=true`):**
   - A parent is upserted by its own `PUT /accounts/parent/{parent_rawani_id}`, with the same rules, its own 409 and its own password (the parent person's).
   - A student PUT resolves its parent **only** by `parent_rawani_id` on the parent's stored Rawani id. If it is not there, the platform answers 422 `PARENT_NOT_LINKED`.
   - Rawani orders the work: the student account's job does nothing while the parent account is not `active`. It stays `pending` with «بانتظار حساب ولي الأمر». It runs again when the parent turns active.
   - So an existing Al-Awwal parent, which is the common case, goes through `conflict` → admin link once, and from then on all their Rawani children attach to it.
9. **Response:** `200 {"external_id":"5531","login":"1234567890","created":true,"managed":true}`. A 409 carries the `external_id` and `login` of the existing account **for the role in the path**.

**3. `POST /accounts/{role}/{rawani_id}/sso`** (only platforms with the `sso` capability).
- Response: `{"url":"https://alawal.online/rawani/sso/<64-char token>","expires_at":"…"}`.
- The token is random, stored hashed, single use, and valid for 60 s.
- Redeeming it re-runs that platform's normal login checks: blocked, active, subscription.
- If the account has no Rawani link, the endpoint returns 404.

**4. `GET /people?cursor=&per=500`**
- Response: `{"items":[…],"next_cursor":"…|null"}`.
- The cursor is opaque. It is the last `id` seen, so a full walk is ordered by PK. No `updated_since` is offered, because aafaq `reg_students` has no reliable updated column and alawal hard-deletes. Rawani always walks everything: nightly for active rows, and weekly with missing-detection.
- Each item has these fields:

```json
{ "external_id":"5531", "role":"student|parent|teacher", "rawani_id":42|null,
  "name":"…", "email":"…|null", "phone":"…|null", "national_id":"…|null",
  "parent_external_id":"88|null", "org":"مجمع … / مدرسة … / الصف …|null",
  "active":true, "access":[{"product":"reading","until":"…|null","source":"rawani|other"}],
  "created_at":"…" }
```

- Never include password hashes, credential material, device ids or tokens.

Throughput: aafaq has more than 100k accounts. At 500 rows a page that is about 200 calls a night, which is fine.

---

## 5. Per-platform mapping

| | «منصة الأول» alawal | «أنا والقدرات» anawlqdrat | aafaq (reading + verbal) |
|---|---|---|---|
| Products | `main` | `main` | `reading`, `verbal` |
| Capabilities | `sso: true`, `parents: true`, roles student and parent | `sso: true` (needs an SPA route), `parents: false`, role student | `sso: false`, `parents: false`, role student |
| Required fields (Rawani validates) | student: national_id, gender, stage, grade, and a parent with national_id and name. parent: national_id, name | student: name, gender | student: name, gender, grade |
| Where the Rawani id lives | new nullable unique `rawani_user_id` on `students` and `my__parents` | new nullable unique `users.rawani_user_id` | new nullable unique `reg_students.rawani_user_id` |
| Natural key for 409 | (role, `identity_number`), which is unique and also reserved by soft-deleted students | none reliable. The username `rw<rawani_id>` is checked against email, phone and username, because login ORs all three. | `email` (UNIQUE) |
| Login shown to the person | the national ID | `rw<rawani_id>` | the email: the person's real email if it is free in aafaq, otherwise `rw<rawani_id>@rawaniedu.com` (needs Q7: confirm the domain; aafaq never verifies email) |
| Create also sets | Student placement: a new «رواني» complex holding one school per (stage × gender) with standard classrooms and one section «رواني» each. Mapping table `rawani_placements(stage, gender, grade → Grade_id, Classroom_id, section_id)`, filled by Al-Awwal admins on prod (data, not seed). `gender_id` comes from the school. `parent_id` is resolved from `profile.parent_rawani_id` (contract rule 8; 422 `PARENT_NOT_LINKED` when it is missing). A soft-deleted student is restored (`withTrashed()->restore()`). | `type=student`, `status=active`, `email_verified_at=now()`. Email only if it is real and unique across all three login columns, otherwise `''`. No org (the personal subscription is enough). | `password = passwordForStorage(credentialMaterial(plain))`, which is bcrypt of the uppercase SHA-256. `school` and `school_id` = `getDefaultSchoolId()` (5398 «شركة رواني» on live). `year` = grade, `term` = 1, `class` = `''`, `platform` = 0, `mobile` = phone or `''`. |
| Entitlement → access | No subscription concept, so Rawani's window becomes `rawani_access_until` on the student. The login gate for **managed** accounts is `is_active AND rawani_access_until >= now()`. **Linked** real-school students are never gated by it. | One `subscribes` row (User morph, exact string `App\Domain\User\Models\User`) per account, tagged with a new `subscribes.rawani_ref` UNIQUE = `rawani:<rawani_id>`. Start and end are set from the window. `[]` sets `status=expired` on that row only. When a window is shortened into the past, Passport tokens are revoked, because the subscription is only checked at login. The bridge skips the admin-UI `orgSubscriptionExceededBy` cap. `hasActiveSubscription()` ORs the personal row with the org row, so a personal row can stand on its own. | One `subscriptions` row per product: `subscription_type='user'`, `entity_id`, `app_type` 0 for reading and 1 for verbal, `payment_id='rawani:<rawani_id>:<product>'` (UNIQUE gives idempotency), then `applySubscriptionToUsers()`. `[]` sets `is_active=0` on those rows and runs the existing expire logic for that user. Known caveat: the expire cron zeroes `has_*_code` even when the user also redeemed a code (pre-existing, risk R8). |
| Disable | Students: `is_active=0` (existing column, enforced from Phase 0). Parents: new `is_active` column plus a login check. Sets `rawani_disabled=1`. | `blocked=1` plus revoke all tokens. Sets `rawani_disabled=1`. | New `reg_students.is_active` (default 1), checked in `login`, `editedLogin`, `editedLogin2` and `checkUserPass`. Sets `rawani_disabled=1`. |
| SSO | `POST sso` → `GET /rawani/sso/{token}` (web, no auth) → `Auth::guard($role)->login($model)` → the guard's home | `POST sso` returns `https://anawlqdrat.com/sso?code=…`. A public `POST /api/v1/user/auth/sso/exchange {code}` re-runs the login checks and returns a Passport token. A new SPA route `/sso` calls exchange, then `auth/attempt`, clears exam localStorage and goes to `/`. | `501 NOT_SUPPORTED`. Rawani shows the email and password and the store and .exe links. One-tap needs app releases by the external developer (Q5). |
| Parent of a Rawani child | A real `my__parents` account, which sees its children on Al-Awwal | None. The parent sees the child's credentials and the «افتح» link in Rawani. | None. Same as anawlqdrat. |
| `people` source | `students` with withTrashed (soft-deleted ones reported `active:false`), `my__parents`, `teachers`. `parent_external_id` = `students.parent_id`. | `users` (student and teacher, `deleted_at` reported inactive), plus the org label and `graduated_at` | `reg_students` (role student or teacher), with the access flags reported as `source:"other"` unless they come from a `rawani:` subscription row |

**Linking an existing account:**
- For the platform, the only difference is `link_external_id`.
- In Rawani, linking needs an admin's confirmation (`link_confirmed_by`).
- SSO is issued for a linked account only after that confirmation. Without this rule, typing someone else's national ID into a child form would end in a login link to their account.

---

## 6. Moyasar (completes `.kiro/specs/moyasar-payments` Phase 2)

We keep the 2026-08-29 decision: **hosted invoices**, no card form in our pages. One flow serves both self-checkout (redirect to the invoice URL) and, later, admin "send a payment link".

`MoyasarGateway` uses `Http::withBasicAuth(secret,'')` against `config('moyasar.api_url')`. It does not add an SDK (moyasar-payments design, "Why no package").

1. **Checkout** (`StartCheckout`):
   - First, run the job's required-fields check (§3.3) for every account the package will create, including the parent's national ID on Al-Awwal. If anything is missing, show the form to complete it before any invoice exists, so a buyer never pays and then lands in `needs_info`.
   - In one DB transaction, create a `hub_subscriptions` row (pending, items snapshot, price) and a `hub_payments` row (pending, amount = price×100).
   - `POST /v1/invoices` with `{amount, currency:"SAR", description:"باقة <name> — <beneficiary>", callback_url: route('my.packages.return',$payment), metadata:{site: config('moyasar.site'), rawani_payment_id: $payment->id}}`.
   - **Never send metadata keys named `email` or `app`.** aafaq's `paymentBuyer()` would act on them.
   - Store the invoice id and redirect to `invoice.url`.
   - The exact semantics of the invoice fields (`callback_url` vs `success_url`/`back_url`) and how invoice metadata is echoed onto payment webhooks are **unverified**. Check them against Moyasar docs before coding. The design only relies on four things: the stored invoice id, `GET /v1/invoices/{id}`, `GET /v1/payments/{id}`, and the webhook.
2. **Return page:** ignore the query. Call `GET /v1/invoices/{stored id}`. If the status is `paid`, take the paid payment id from it and run `ApplyPaidPayment`. Either way, show the status page.
3. **Webhook** `POST /api/hub/moyasar/webhook`:
   1. `hash_equals(config('moyasar.webhook_secret'), $request->input('secret_token'))`, else 401.
   2. Remove `secret_token` and save `hub_payment_events`.
   3. If `metadata.site !== config('moyasar.site')`, record `ignored_site` and return 200.
   4. Find the `hub_payments` row by `metadata.rawani_payment_id` or the invoice id. If there is none, record `unmatched` and return 200.
   5. For `payment_paid`, run `ApplyPaidPayment($payment, $moyasarPaymentId)`. Other event types are only recorded in phase 1.
4. **`ApplyPaidPayment`** (one path, used by the webhook, the return page, reconcile and the admin «تحقق»):
   1. `GET /v1/payments/{id}` and require `status=paid`, the same `amount`, the same `currency`, and the same invoice id.
   2. `DB::transaction`, then `lockForUpdate` on the payment row.
   3. If the row is already `paid`, record `duplicate` and stop.
   4. Otherwise set the payment to `paid` (`moyasar_payment_id`, `paid_at`) and set the subscription to `active` with `starts_at = max(now, last ends_at of the same user+package)` and `ends_at = starts_at + duration_days`.
   5. Mark the accounts dirty, then dispatch the sync jobs `afterCommit`.
5. **Reconcile:** `hub:reconcile-payments` runs every 10 minutes. It checks pending payments older than 10 minutes and younger than 48 hours through the invoice API, then marks the rest `failed` after 48 hours, and their pending subscriptions `cancelled`. This also covers the case where the Moyasar account cannot hold a second webhook registration (unverified, risk R3).
6. **Refund (manual):**
   1. The admin refunds in the Moyasar dashboard.
   2. In Rawani, «تسجيل استرداد» with a note sets the payment to `refunded` and the subscription to `refunded`.
   3. The entitlement is recomputed and the accounts are synced.
   - Refund webhooks are only recorded. They are not applied automatically in phase 1.
7. **Configuration:**
   - Rawani's own webhook URL is registered in the Moyasar dashboard with its own secret.
   - `MOYASAR_SITE=rawani` is set only on rawaniedu.
   - The test keys are used locally and on the first live dry run.
8. **aafaq side (Phase 0):** its webhook ignores any event whose `metadata.site` is set and is not aafaq's. aafaq's own payments carry `email` and `app` and no `site`, so they keep working. If aafaq's `payment.php` is changed to add `site:'aafaq'`, the filter becomes strict. **This must be live on aafaq before Rawani's first live payment.**

---

## 7. Phase 0: per-platform security fixes

Each fix ships on its own branch, deploys alone, and is verified live before any integration work on that platform goes live.

### 7.1 alawal (`~/www/alawal`, branch `live-2026-11-19`, Laravel 9, Tarweig VPS)
- **P0-AL-1, forgot-password takeover:**
  - Remove the public self-service reset: the `POST /forgot-password` → `verifyIdentity` → `reset-password/{token}` chain.
  - Keep the existing admin «كلمة مرور مؤقتة» flow, which already exists for all four types, as the only reset path.
  - The login page link changes to «تواصل مع إدارة المدرسة».
  - Replacement self-service (for example a reset through the Rawani parent) is open question Q6.
- **P0-AL-2:** move `promotions/*`, `reports/*` and the debug routes after `web.php`'s auth group back inside `auth:web`. Delete the debug routes.
- **P0-AL-3:** apply `throttle:5,1` (by IP and identity number) on `POST /login`.
- **P0-AL-4:**
  - `LoginController::login` refuses students with `is_active=0`.
  - A middleware on `auth:student` routes logs out deactivated students.
  - The same check goes in the future SSO route.
- **Deploy:**
  - The live tree is a snapshot with no prior history. Confirm its remote and deploy path on the box before the first push (read-only check).
  - Test by hitting the routes. `route:list` crashes on this codebase.

### 7.2 aafaq (`~/www/aafaq`, `main`, raw PHP 8.2 + Slim 3, deploy = `git pull` in `/www/wwwroot/aafaq.xyz`, never as root)
Before changing any endpoint, grep both Flutter repos (`~/www/afaq_reading`, `~/www/afaq_abilities_app`) and the aafaq web pages for callers. An endpoint with an app caller can only be tightened in ways the published app already satisfies.
- **P0-AF-1:**
  - With no app caller, `getAllStudents` and `deleteStudentByEmail` are restricted to an admin session or removed.
  - `getStudentData` requires `userId` plus the credential material, the same check as `checkUserPass`, **if** the apps already send it. Otherwise it returns a reduced field set (no mobile and no school) and is listed as a residual risk.
  - `updateProfile` requires the credential material.
- **P0-AF-2:** `updatePaymentStatus` is used by the published iOS abilities app (RevenueCat).
  - Add a server-side check against the RevenueCat REST API for that app user id before setting the flag, if the RevenueCat secret key is available.
  - Otherwise leave the endpoint as is, write it down as risk R9, and track it as a client item.
- **P0-AF-3:** the Moyasar webhook `site` filter (§6.8).
- **P0-AF-4:** prepared statements in `register`, `editedRegister` and `forgetPass`.
- **P0-AF-5:** deny `cron/` and `update_payments.php` in the nginx vhost, or require CLI (`PHP_SAPI === 'cli'`). The nginx change needs the server owner, so prefer the PHP check.

### 7.3 anawlqdrat (`~/www/anawlqdrat`, live branch `server-live-2026-07-31`, Laravel 10)
- **P0-AQ-1:**
  - The login refuses `status='inactive'` the same way as `blocked`, with one message.
  - Document that meaning in the user admin modal label.
- **P0-AQ-2:** revoke tokens with `$user->tokens()->update(['revoked'=>true])` when a user is blocked, set inactive or soft-deleted. That covers the admin modal, the delete action and the self-delete in `ProfileController::destroy`.
- **P0-AQ-3:** a middleware on `auth:api` that returns 401 for blocked, inactive or soft-deleted users. This covers tokens issued before the fix.
- **Optional, same branch:**
  - Unregister the MyFatoorah demo routes (`'register_routes' => false` or override in the provider).
  - Add auth to `POST /api/upload-audio` after checking that the SPA calls it with a token.
- **Deploy:**
  - Commit and push to the fork. On the server, `git fetch` and `git checkout <ref> -- <paths>`, then commit on `server-live-2026-07-31`.
  - Never use `production/main`.
  - No migrations in Phase 0.

---

## 8. Rollout order

| Step | Where | Gate to move on |
|---|---|---|
| 0 | Phase 0 fixes on the 3 platforms (parallel) | Each is verified live with a curl or browser check. aafaq P0-AF-3 is live. |
| 1 | Rawani hub core (schema, flag, registry, packages, grant, sync job, credentials), tested against `FakePlatform` (`Http::fake` + a local stub that implements the contract) | Feature tests pass. Deployed to both instances: the flag is off on viewclass (verify 404 on `/admin/hub/packages`) and on on rawaniedu with no platforms enabled. |
| 2 | Contract on alawal, anawlqdrat and aafaq (parallel, each on a local copy of its DB) | Each passes the shared test vector, the upsert, entitlement, disable and replay tests, and an end-to-end grant from local Rawani to the local platform. |
| 3 | Live connect, one platform at a time: deploy the bridge, set `RAWANI_HUB_SECRET` in that platform's `.env` (the generate button on rawaniedu shows it once), «اختبار الاتصال», grant to one test student, log in on the platform, cancel, confirm access closed | All three platforms are green. The client approves the first real packages. |
| 4 | Moyasar on rawaniedu: test mode end to end (with a webhook through a tunnel locally), then the live keys, the live webhook registration and a 1 SAR live package refunded by hand | Paid → accounts within about 1–2 min (the cron queue worker runs every minute). aafaq ignored the event (check its `payment_events`). |
| 5 | Directory mirror + linking | First nightly mirror completes. Counts match the platforms' own admin counts. |
| 6 | SSO-lite: alawal, then anawlqdrat (backend + SPA build from `deploy-symmetry-fix`) | One-click login works. A replayed or expired token is refused. |

Every code deploy of Rawani goes to **both** viewclass.newaves-systems.com and rawaniedu.com:

```
commit → push → ssh → git pull → migrate --force → view:cache
```

Data goes only to rawaniedu: platform rows, secrets, packages and the hub school.

---

## 9. Tests

**Rawani** (`tests/Feature/Hub*Test.php`):
- Use your own test DB when several agents work in parallel.
- Cases:
  - the flag off gives 404 on admin, my and webhook routes, with no schedule registered
  - non-super-admins get 403
  - package CRUD with validation, including that 0 price is not allowed when `is_purchasable`
  - a grant creates the subscription and the dirty accounts, and stacks renewals
  - `EntitlementCalculator` cases: overlap, expired, refunded, two products
  - `PlatformClient` reproduces the §4.1 signature vector
  - the job's status transitions under `Http::fake`: 200 → active, 409 → conflict, 422 → failed, 503 → retried, missing national ID → needs_info with no HTTP call
  - checkout sends metadata without `email` or `app`
  - webhook: wrong secret → 401; foreign site → 200 and ignored; duplicate → applied once; amount mismatch → not applied; secret not stored
  - reconcile applies a paid invoice
  - refund ends the entitlement
  - a parent cannot check out for someone else's child
  - a national ID collision blocks self-service

**Each sub-platform** (PHPUnit for the Laravel two, a PHP CLI test script for aafaq):
- the signature vector
- stale timestamp and replayed nonce
- upsert twice gives one account
- 409 on an existing unlinked natural key
- link does not touch the password
- entitlements replace only Rawani-tagged grants and leave a code- or org-granted access untouched
- disable blocks login and kills tokens or sessions; enable does not undo a block made by the platform's own admin
- `people` paging contains no secrets
- SSO token is single use and expires (alawal and anawlqdrat)

---

## 10. Risks

| # | Risk | Mitigation |
|---|---|---|
| R1 | Existing accounts (an alawal national ID, an aafaq email) block auto-creation for real buyers | The `conflict` status, an admin link screen and directory candidates. Never auto-merge. |
| R2 | Account takeover through a typed national ID or email | Linked accounts get no SSO until an admin confirms, and their password is never pushed. Self-service blocks IDs that already exist. |
| R3 | The Moyasar account may allow only one webhook URL; aafaq's endpoint may receive Rawani events | Reconcile and return-page fetch make activation independent of the webhook. The metadata never has `email` or `app`. aafaq has the site filter. Verify in the dashboard. |
| R4 | Al-Awwal placement: provisioned students see no exams until someone assigns exams to the «رواني» sections | A client decision (Q2). Self-learning by classroom works without it. |
| R5 | anawlqdrat Passport tokens outlive the subscription | Revoke tokens on disable and on a window in the past. The P0 middleware rejects blocked and inactive users. |
| R6 | No credential delivery channel (Rawani mail is set to log) | Credentials are shown in Rawani and on a printable card. Configure a mailbox later (Q4). |
| R7 | Live code drift: anawlqdrat live is `server-live-*`, alawal is a snapshot with no history | Branch from the live refs only and use the documented deploy flows. |
| R8 | aafaq's expire cron zeroes `has_*_code` for users who also redeemed codes | Pre-existing. Report it to the client and fix it separately if it hurts. |
| R9 | aafaq `updatePaymentStatus` stays open if no RevenueCat server check | Client item (needs the RevenueCat secret). |
| R10 | Credential-material leaks on aafaq (login returns SHA-256) | Don't extend it. The bridge accepts the plaintext only over HTTPS and signed. |
| R11 | APP_KEY rotation on rawaniedu makes stored platform secrets and passwords unreadable | Same trade-off as WhatsApp tokens. Re-enter secrets and reset passwords. |
| R12 | Queue worker is a once-a-minute cron on rawaniedu | Up to about 60 s latency, which is acceptable. The UI shows «جارٍ إنشاء الحسابات». Confirm `QUEUE_CONNECTION=database` there. |
| R13 | One-tap app login for aafaq is not possible without app releases | `sso:false` with an on-screen credential fallback (Q5). |

---

## 11. Open questions for the client (only they can answer)

- **Q1** Online purchase: only for existing Rawani accounts, or also a public «سجّل واشترِ» page? (Default: existing accounts only.)
- **Q2** Al-Awwal: is a separate «رواني» complex with section «رواني» OK, and who assigns exams to it?
- **Q3** A buyer who already has an account on a platform: link it after admin review (default) or create a new one?
- **Q4** Which mailbox should send login details? Until one exists, they are shown inside Rawani only.
- **Q5** aafaq apps: are on-screen email and password enough, or should the app developer add one-tap login (new store releases)?
- **Q6** Al-Awwal forgot-password after we close the open reset: through the school admin only, or through the Rawani parent?
- **Q7** Is `rw<id>@rawaniedu.com` OK as the aafaq login for children with no email?
- **Q8** Are prices VAT-inclusive, and is a tax invoice needed?

Defaults in place, no answer needed unless the client disagrees:
- Expiry closes access and keeps the account.
- Only super-admins manage the hub.
- No teacher packages.
- aafaq's own 40 SAR payment and the iOS purchase stay.

## Decisions 2026-11-19 (user)
- Phase 0 starts now.
- Q1: public «سجّل واشترِ» page on rawaniedu.com (anyone, usually a parent, registers → picks package → pays via Moyasar); admin free grants stay.
- Q3: existing account on a sub-platform (same national id / email) → "conflict", linked only after admin review; no auto-link.
- Q5: آفاق = show login + generated password inside Rawani; no one-tap login / no new app releases now.
- Precondition checks (read-only, live, 2026-11-19): aafaq `cron/resync_subscriptions.php` runs from root crontab via CLI (no URL trigger seen in access logs) → CLI-only guard is safe. anawlqdrat: **940** users `status='inactive'`, not blocked, not deleted → do NOT refuse `inactive` at login; Phase 0 limits enforcement to `blocked` (+ token revoke) and documents `inactive` as "pending admin review". Rawani "disable" uses `blocked`.

## Decisions 2026-11-19 (round 2, user)
- Q2 الأول: one «رواني» complex with 6 schools (3 stages × بنين/بنات); buyers are placed by grade automatically; the complex manager assigns exams to those sections (no auto-assign).
- Q4 Mail: credentials are sent from **no-reply@rawaniedu.com** (mailbox already exists on Hostinger mail for rawaniedu.com; configure SMTP on rawani + verify SPF/DKIM at rollout).
- Q7 آفاق kid login: generated `rw<id>@rawaniedu.com` placeholder email + generated password, shown to the parent.
- Q8 Prices are VAT-inclusive (15%); buyer gets a simple payment receipt (no ZATCA e-invoice for now).
