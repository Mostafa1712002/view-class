# Requirements: Rawani hub and packages («باقات رواني»)

## Overview

Rawani (rawaniedu.com, a ViewClass instance) is the parent company. It has four sub-platforms on three backends:

| Product the client sells | Backend | Code |
|---|---|---|
| «أنا والقدرات» | anawlqdrat (Laravel 10 + Vue SPA) | `~/www/anawlqdrat` (live branch `server-live-2026-07-31`), `~/www/anawlqdrat-front` (`deploy-symmetry-fix`) |
| «القراءة السريعة» app | aafaq.xyz (raw PHP 8.2 + Slim 3), one account table for both apps | `~/www/aafaq` (`main`) |
| «القدرات اللفظية» app | aafaq.xyz, same backend | same |
| «منصة الأول» | alawal.online (Laravel 9) | `~/www/alawal` (`live-2026-11-19`) |

Rawani gets:
- **Packages (باقات):** CRUD screens. A package covers one or more products.
- **Paid and free subscriptions:** buyers pay online through Moyasar, or an admin grants the package for free.
- **Automatic accounts:** every subscription opens an account on every platform the package covers.
- **A master directory:** every user and parent of every sub-platform, linked to Rawani people.

Background research for this spec lives in `research-rawani.md`, `research-anawlqdrat.md`, `research-aafaq-apps.md` and `research-alawwal.md`, all in this folder.

### Fixed decisions (client, 2026-11-19, not reopened here)

1. **D1 Integration:** each sub-platform gets a small signed server-to-server API that implements one shared contract (`design.md` §4). Rawani holds:
   - packages and payments
   - the platform registry
   - the person↔account map
   - provisioning jobs with retries
   - the directory
2. **D2 Pricing:** packages are paid online through Moyasar, or granted free by an admin. The Moyasar account is shared with aafaq, so each side's webhook must ignore the other side's payments.
3. **D3 Instances:** the code deploys to both viewclass.newaves-systems.com and rawaniedu.com. The module is switched on only on rawaniedu. All data lives only on rawaniedu: packages, platform secrets and any backfill.

### Terms

| Term | Meaning |
|---|---|
| Platform | One sub-platform backend: `anawlqdrat`, `aafaq` or `alawal`. Platforms are rows in the registry. |
| Product | A sellable unit on a platform: `anawlqdrat.main`, `aafaq.reading`, `aafaq.verbal`, `alawal.main`. Package items point at products. |
| Beneficiary | The Rawani student who receives the access. |
| Buyer | The Rawani user who pays: the student or their parent. |
| Platform account | One account on one platform for one Rawani person, in a role (student or parent). |
| Entitlement | Rawani's own access window for (person, product). It is computed from active subscriptions. |
| Managed account | An account Rawani created. A **linked** account existed before and was linked by an admin. |

---

## User stories

### Phase 0: sub-platform security prerequisites

#### US-001: No account takeover on «منصة الأول»
**As** the client, **I want** the sub-platforms closed against known takeovers before paid accounts are linked to them, **so that** a paid account cannot be stolen.

- WHEN anyone submits a national ID to Al-Awwal's forgot-password form THE SYSTEM SHALL NOT let the requester set a new password without a second factor. (The replacement flow is open question Q6.)
- WHEN an unauthenticated request hits `promotions/*`, `reports/*` or the debug routes after the auth group THE SYSTEM SHALL redirect it to login.
- WHEN a client sends more than 5 failed `POST /login` attempts a minute THE SYSTEM SHALL throttle them.
- WHEN a student whose `is_active=0` logs in THE SYSTEM SHALL refuse the login. WHEN a deactivated student is already logged in THE SYSTEM SHALL end the session on the next request.

#### US-002: aafaq's open endpoints are closed
- WHEN `getAllStudents`, `getStudentData`, `deleteStudentByEmail` or `updateProfile` is called without proof of the account's credential THE SYSTEM SHALL refuse it, unless the call comes from a caller found in the two app repos. Each such caller is handled as described in `design.md` §7.2.
- WHEN `updatePaymentStatus` is called THE SYSTEM SHALL unlock only after the purchase is confirmed server-side with RevenueCat. Until a server check exists, the endpoint stays open (the published iOS app depends on it) and the gap is listed as a risk.
- WHEN aafaq's Moyasar webhook receives a payment whose `metadata.site` is set and is not aafaq's THE SYSTEM SHALL record it as ignored and SHALL NOT unlock anything.
- WHEN `register` / `editedRegister` insert a user THE SYSTEM SHALL use prepared statements.

#### US-003: anawlqdrat blocking means blocked
- WHEN a user is blocked or soft-deleted THE SYSTEM SHALL revoke all of that user's Passport tokens.
- WHEN a blocked user calls any `auth:api` route with an old token THE SYSTEM SHALL return 401.
- THE SYSTEM SHALL use one documented meaning for `status='inactive'`. Recommendation: refuse login, the same as `blocked`.

### Phase 1: Rawani hub core

#### US-010: Hub only where enabled
- WHILE `RAWANI_HUB_ENABLED` is false THE SYSTEM SHALL register no hub routes (web, `my/*` or webhook), schedule no hub commands and show no hub sidebar items. Migrations still create empty tables.
- WHERE the hub is enabled THE SYSTEM SHALL show the hub screens to super-admins only.

#### US-011: Platform registry
**As a** super-admin **I want** to register each sub-platform's URL and secret **so that** Rawani can call it.

- WHEN a super-admin saves a platform THE SYSTEM SHALL store the secret encrypted and never show it again (the screen shows "مُعدّ" / "غير مُعدّ").
- WHEN the admin clicks «اختبار الاتصال» THE SYSTEM SHALL call the platform's signed `ping` and show the result and the round-trip time.
- WHEN a platform is disabled THE SYSTEM SHALL stop sending it calls and SHALL keep its pending work queued.
- Platform rows and secrets are entered through this screen on rawaniedu. They never come from migrations or seeders.

#### US-012: Package CRUD
**As a** super-admin **I want** to create, edit, deactivate and delete packages **so that** I can sell bundles of products.

- THE SYSTEM SHALL store for each package:
  - name (ar, en) and description (ar)
  - price in SAR (0 allowed only when the package is not purchasable online)
  - duration in days
  - the products it covers (at least one)
  - «متاحة للشراء» (purchasable online) and «مفعّلة» (active) flags
  - sort order
- WHEN a package that has subscriptions is deleted THE SYSTEM SHALL soft-delete it and keep the existing subscriptions unchanged.
- WHEN a package's products change THE SYSTEM SHALL NOT change existing subscriptions, because each subscription keeps a snapshot of its products.

#### US-013: Free grant by an admin
**As a** super-admin **I want** to grant a package to a student, or to a parent for one or more of their children, without payment.

- WHEN an admin grants a package THE SYSTEM SHALL create one active subscription per beneficiary with `source=grant`, record who granted it and the note, and queue provisioning.
- WHEN the beneficiary already has an active subscription to the same package THE SYSTEM SHALL start the new one when the current one ends (renewal stacking).
- WHEN the beneficiary does not exist yet THE SYSTEM SHALL let the admin create them in the same flow: a student, optionally with a parent, placed in the hub's default school.

#### US-014: Automatic accounts on the included platforms
- WHEN a subscription becomes active THE SYSTEM SHALL ensure that a platform account exists for the beneficiary on every platform in the snapshot, and on Al-Awwal for their parent too. Al-Awwal requires a parent, and the parent role is the only parent concept any sub-platform has.
- WHEN a buyer starts checkout and data a platform requires is missing THE SYSTEM SHALL ask for it before creating the invoice.
- WHEN required identity data is missing (for example Al-Awwal needs the national ID of the student and the parent) THE SYSTEM SHALL mark the account `needs_info`, show what is missing, and SHALL NOT call the platform.
- WHEN the platform reports that an account with the same key already exists and is not linked to Rawani THE SYSTEM SHALL mark the account `conflict` and SHALL NOT take it over. Only an admin can confirm a link.
- WHEN a call fails with a timeout, a 5xx or a 429 THE SYSTEM SHALL retry with backoff. After the last retry THE SYSTEM SHALL mark the account `failed` with the error.
- THE SYSTEM SHALL push the absolute state (profile, enabled, entitlement windows per product), so that repeating a call is always safe.

#### US-015: Expiry, renewal, cancel
- WHEN a subscription's `ends_at` passes THE SYSTEM SHALL mark it `expired` within a day and push the shortened entitlement.
- WHEN an entitlement ends THE SYSTEM SHALL remove only Rawani's access. It SHALL NOT disable a linked account or remove access that came from another source (a school, an org subscription, a code).
- WHEN an admin cancels a subscription THE SYSTEM SHALL end it now and push the change.

#### US-016: Credentials
- WHEN Rawani creates a managed account THE SYSTEM SHALL generate one random password per person, reuse it on every platform for that person, and store it encrypted.
- THE SYSTEM SHALL show the login name and password for each account to super-admins, to the beneficiary and to their parent in Rawani, with a print and copy option.
- WHILE Rawani mail is the `log` driver THE SYSTEM SHALL NOT claim to have emailed credentials.
- WHEN an admin clicks «إعادة تعيين كلمة المرور» on a managed account THE SYSTEM SHALL generate a new password and push it.
- THE SYSTEM SHALL never send a password for a linked (pre-existing) account.

#### US-017: Audit
- WHEN a package, platform, grant, cancel, refund, link, unlink, disable or password reset changes THE SYSTEM SHALL write an `ActivityLog` entry naming the actor.
- WHEN a provisioning call fails THE SYSTEM SHALL keep the last error and attempt count on the account row.

### Phase 3: Online purchase (Moyasar)

#### US-020: A parent or student buys a package
- WHEN a logged-in student or parent opens «باقاتي» THE SYSTEM SHALL list the active, purchasable packages and their current subscriptions.
- WHEN a parent buys THE SYSTEM SHALL require them to pick one of their children, or add a child (name, gender, stage, grade, national ID when the package includes Al-Awwal).
- WHEN a student buys THE SYSTEM SHALL use the student as the beneficiary.
- WHEN checkout starts THE SYSTEM SHALL create a pending subscription and payment, create a Moyasar invoice with `metadata = {site, rawani_payment_id}` and no `email` or `app` keys, and redirect to the invoice.
- WHEN the buyer returns THE SYSTEM SHALL ignore the query string, fetch the invoice from Moyasar by its stored id, and activate only if Moyasar says it is paid.

#### US-021: The webhook activates
- WHEN a webhook arrives without the right `secret_token` THE SYSTEM SHALL answer 401.
- WHEN a webhook's `metadata.site` is not Rawani's THE SYSTEM SHALL record it as ignored and answer 200.
- WHEN a `payment_paid` webhook matches a pending payment THE SYSTEM SHALL fetch the payment from Moyasar and check status, amount and currency. Only then SHALL it mark the payment paid and activate the subscription once, then queue provisioning after commit.
- WHEN the same payment is delivered again THE SYSTEM SHALL NOT activate it twice.
- WHEN a payment is still pending 10 minutes after checkout THE SYSTEM SHALL ask Moyasar for its status (reconcile) until it is 48 hours old.
- THE SYSTEM SHALL record every webhook event, matched or not, with the secret removed.

#### US-022: Manual refund
- WHEN an admin records a refund on a paid payment THE SYSTEM SHALL mark the payment and the subscription refunded, end the entitlement and push it. The money is returned in the Moyasar dashboard.

#### US-023: Payments screen
- THE SYSTEM SHALL list payments with their status, the buyer, the beneficiary and the package, and SHALL flag paid payments whose subscription is not active.

### Phase 4: Master directory

#### US-030: Everyone in one place
- THE SYSTEM SHALL mirror the people of each enabled platform (students and parents, plus teachers if the platform returns them) into Rawani nightly, through the contract's `people` endpoint.
- THE SYSTEM SHALL run a weekly full sweep and mark rows that disappeared as `missing`. Al-Awwal hard-deletes parents and teachers.
- THE SYSTEM SHALL show one directory with these filters:
  - platform
  - role
  - status (active, needs_info, failed, conflict, disabled, missing)
  - linked or unlinked
  - text search on name, national ID, email or phone
- THE SYSTEM SHALL show a person page with:
  - the Rawani profile and the parent↔child links
  - subscriptions
  - every platform account with its status, last sync and last error
  - the parent↔child links reported by each platform

#### US-031: Linking without auto-merge
- THE SYSTEM SHALL suggest candidate matches by national ID, then real email (never `@viewclass.local`), then normalised phone. It SHALL never match on name and never auto-merge.
- WHEN an admin confirms a link THE SYSTEM SHALL set the account to linked and resolve any `conflict`.
- WHEN an admin turns an unlinked external person into a Rawani person THE SYSTEM SHALL create the Rawani user in the hub's default school and link the account.

### Phase 5: One-time login link (SSO-lite)

#### US-040: «افتح المنصة»
- WHEN a beneficiary or parent clicks «افتح المنصة» for an account on a platform that has the `sso` capability THE SYSTEM SHALL ask the platform for a single-use link (valid 60 seconds) and redirect to it.
- WHEN the platform does not have the `sso` capability (aafaq, which is mobile apps) THE SYSTEM SHALL show the stored login and password and the app store links instead.
- WHEN the account is linked but not managed THE SYSTEM SHALL issue a link only after an admin confirmed the link.

---

## Non-functional requirements

### NFR-001 Security
- Every server-to-server call is signed with HMAC-SHA256 over method, path, timestamp, nonce and body hash, using a per-platform secret. The timestamp window is ±300 s and nonces are single-use.
- Secrets are stored encrypted in Rawani and kept in `.env` on the sub-platforms. They never appear in git, specs or logs.
- The `people` responses never contain password hashes or credential material.
- A Moyasar payment is applied only after Rawani confirms it with Moyasar server-side. The webhook secret is never stored.

### NFR-002 Reliability
- Every provisioning call is idempotent and carries absolute state.
- A scheduled sweep re-queues anything still marked dirty, so a lost job cannot strand an account.
- A sub-platform outage never blocks a purchase. The purchase succeeds and the accounts wait.

### NFR-003 Isolation between instances
- On viewclass.newaves-systems.com the hub has no routes, schedules or menu. Its tables exist but stay empty.

### NFR-004 Localisation
- Every UI string is in the `lang/{ar,en}/hub_*.php` files (one per screen group, design §3.5). Arabic is primary.

### NFR-005 Performance
- Directory lists are paginated server-side. The mirror runs in pages of at most 500 rows, which matters because aafaq has 100k+ accounts.
