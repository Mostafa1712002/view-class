# Research: «منصة الأول» (alawal.online) as a Rawani sub-platform

Read-only analysis done 2026-11-19 for the Rawani packages hub. Source: `/home/mostafa/www/alawal`, git branch `live-2026-11-19` (commit `648e171`, "Snapshot of live alawal.online code"). Laravel `^9.19`, PHP `^8.0.2`, Livewire 2, Laratrust 7.1, Sanctum 3 (`composer.json`). Local DB: `alawal_local`. Live DB: `alawal_prod`, on the same Tarweig VPS as rawaniedu.com (per the brief; I did not connect to it).

The programs (qiyas, tahsili, nafis), the staff roles and the sidebars are mapped in `viewclass-wt/special-programs-368/.kiro/specs/special-programs-368/alawwal-source-map.md` §2 and §9. They are not repeated here.

---

## 0. TL;DR

- **There are four separate account tables, and none of them has a phone number.** Students and parents also have no email.
  - Students, parents and teachers log in with `identity_number` (the national ID). Staff log in with `email`.
  - The national ID is the only key Rawani can join on. In Rawani it is `users.national_id`.
- **A student cannot exist without a school, class, section and parent.**
  - `students.parent_id` and `gender_id` are NOT NULL.
  - Every exam the student can see is looked up through `section_id`. Self-learning uses `Classroom_id`, and diagnostics and rewards use `Grade_id`.
  - So a student who buys a package needs a placement inside Al-Awwal. The likely answer is a dedicated «رواني» complex with one school per stage and gender, plus its classes and sections. Someone (an admin or teacher) also still has to assign exams to those sections.
- **There is no API, no SSO, and no subscription or payment gating.**
  - `routes/api.php` has two routes. One is the stock Sanctum `/user`. The other is unauthenticated and broken.
  - Everything else uses session guards with HTML forms.
- **"Disable" does not work today.**
  - The `students.is_active` toggle exists, but login never checks it.
  - Parents and teachers have no active flag at all, and deleting them is a hard delete.
  - The only thing that actually blocks a login is a student soft-delete.
- **Critical security hole:** anyone who knows a student's or teacher's national ID can reset that account's password with no other check (`ForgotPasswordController.php:20-47`). Fix this before linking Al-Awwal accounts to paid Rawani packages.
- **Recommendation:** option (a) below.
  - Add a small HMAC-signed server-to-server API inside Al-Awwal: provision parent+student, disable/enable, list, and a one-time auto-login token.
  - Optionally give Rawani a read-only DB user for the master listing.
  - Don't sync passwords, and don't attempt full SSO now.

---

## 1. Identity model

### 1.1 Guards → tables

`config/auth.php:38-55` (guards) and `:74-90` (providers). All guards use the `session` driver with the `eloquent` provider. The default guard is `web` (`:17`).

| guard | provider / model | table | login field | model notes |
|---|---|---|---|---|
| `web` | `App\Models\User` | `users` | `email` | `HasApiTokens` + `LaratrustUserTrait` (`User.php:15-16`), `$guarded=['id']` (`:25`), no SoftDeletes |
| `student` | `App\Models\Student` | `students` | `identity_number` | `SoftDeletes` (`Student.php:16`), `$guarded=[]` (`:21`) |
| `teacher` | `App\Models\Teacher` | `teachers` | `identity_number` | `$guarded=[]` (`Teacher.php:14`), no SoftDeletes |
| `parent` | `App\Models\My_Parent` | `my__parents` (`My_Parent.php:15`) | `identity_number` | `$guarded=[]` (`:16`), no SoftDeletes |

Laratrust roles apply to `web` users only. Students, teachers and parents have no roles (source map §2.1–2.2).

### 1.2 Key columns and unique identifiers (local `SHOW CREATE TABLE`)

| table | identifier columns | unique keys | other required (NOT NULL) |
|---|---|---|---|
| `users` | `email` | `users_email_unique` | `name`, `password` |
| `students` | `identity_number` varchar(255) | `students_identity_number_unique`. **This also covers soft-deleted rows.** | `name` (text), `password`, `gender_id`, `parent_id`, `status` enum(active, graduated) default active, `is_active` default 1 |
| `teachers` | `identity_number`, `email` (nullable), `national_id` int (unused) | **only `email`**. `identity_number` is **not** unique in the DB. | `identity_number`, `password` |
| `my__parents` | `identity_number` | `my__parents_identity_number_unique` | `name`, `password` |

Other columns:
- `students`: nullable `Grade_id`, `Classroom_id`, `section_id`, `academic_year`, `graduated_at`, `avatar`, `points`, `deleted_at`.
- `teachers`: `Name`, `Gender_id`, `Grade_id`, `complex_id`, `subject_id`, `Specialization_id`.
- `my__parents`: has only `id`, `identity_number`, `password`, `name` and timestamps. **No phone, no email, no active flag.**

**`users` code/schema mismatch (not verified on live).** `UserController::store` writes `phone` and `type` (`UserController.php:81,83`), and `User` has `scopeClients/Admins` on `type` (`User.php:48-62`). The local `users` table has neither column. Either the live table differs or creating staff users fails. Check this on live before relying on `users`.

**Password hashing:** bcrypt everywhere (`Hash::make` / `bcrypt`). Locally, 100% of hashes in all four tables are `$2y$`. Hashes are compatible with Laravel 12 in Rawani, which also verifies `$2y$`.

**Temporary passwords:** there is one table per type, `temporary_{passwords,student_passwords,teacher_passwords,parent_passwords}`. They store both a bcrypt hash and **`temp_password_plain` in clear text** (`ParentController.php:266-300`, same pattern for the other types). Login tries the normal password first, then a valid temporary password (`LoginController.php:51-78`, `:95-236`).

### 1.3 Login flow

- Selector page at `/` (`web.php:71`). The form is at `/login/{type}` (`web.php:73`). `POST /login` (`web.php:75`) goes to `LoginController::login` (`LoginController.php:34-90`).
- The login field is `identity_number` for student, teacher and parent, otherwise `email` (`:38-40`; view `auth/login.blade.php:514`). The guard comes from the `type` field (`AuthTrait::chekGuard`).
- **`POST /login` has no throttle.** The Breeze `LoginRequest` with its 5-attempt limiter is unused, because `routes/auth.php` is not loaded (`web.php:500` is commented out). This also means Breeze self-registration (`auth.php:15-18`) is **not** exposed.
- No `is_active`, `status` or graduated check exists at login or in middleware. `/usr/bin/grep` over `app/Http`, `app/Providers` and `routes` finds no student `is_active` read outside `toggleActive`.
- Student idle logout runs after 60 minutes (`Middleware/StudentIdleTimeout.php`, in the `web` group at `Kernel.php:40`).
- Redirect targets: `HOME=/dashboard`, `STUDENT=/student/dashboard`, `TEACHER=/teacher/dashboard`, `PARENT=/parent/dashboard` (`RouteServiceProvider.php:20-23`).

### 1.4 How accounts are created today (admin screens only, all under `auth:web`, `web.php:107-387`)

There is **no self-registration** for any type.

| type | screen / route | code | what it requires |
|---|---|---|---|
| Student | `Route::resource('Students')` (`web.php:371`) | `StudentController::store` (`StudentController.php:282-326`), rules in `StoreStudentsRequest.php:28-37` | `name`, `identity_number` (unique), `password` (min 6), `Grade_id`, `Classroom_id`, `section_id`, plus `parent_identity_number`, `parent_name`, `parent_password`. `gender_id` is **derived from the school's `grade_type_id`** (1 = boys, 2 = girls, `:288-295`). |
| Student (bulk) | `POST /students/import` (`web.php:372`) | `Imports/StudentsImport.php:19-89` | Excel columns: `student_name`, `student_id`, `student_password`, `complex_name`, `school_type`, `school_name`, `grade`, `section`, `guardian_id`, `guardian_name`, `guardian_password`, `gender`. Schools, classes and sections are matched **by exact Arabic name**. On error it calls **`dd()`**, which aborts the whole import (`:87`). |
| Parent | `Route::resource('parents')` (`web.php:130`) | `ParentController::store` (`ParentController.php:181-196`) | `identity_number` (unique), `name`, `password` (min 6) |
| Parent (implicit) | created inside the student store and import | `My_Parent::firstOrCreate(['identity_number'=>…])` (`StudentController.php:297-303`, `StudentsImport.php:66-72`) | If the parent already exists, **the submitted name and password are ignored** |
| Teacher | `Route::resource('Teachers')` (`web.php:173`), import `web.php:181` | `Teachercontroller::store` (`Teacher/Teachercontroller.php:463-…`) | `Name`, `identity_number` (**no unique check**), `Password` (max 15), `complex_id`, `Grade_id`, `classroom_id[]`, `section_id[]`, `subject_id[]`. Creates `teacher_section_subject` rows. Gender comes from the school. |
| Staff | `Route::resource('users')` (`web.php:122`) | `UserController::store` (`UserController.php:78-90`) | `name`, `email` (unique), `password`, `role_id` |

### 1.5 Activation, disable, delete

| type | "disable" | delete |
|---|---|---|
| Student | `POST /students/{student}/toggle-active` (`web.php:382`) flips `is_active` (`StudentController.php:263-280`). **This is cosmetic: login ignores it** (§1.3). `status='graduated'` is set by promotions and is not enforced either. | `Student::destroy` = **soft delete** (`StudentController.php:427-436`). This is the only state that blocks login, because the Eloquent provider applies the SoftDeletes scope. The unique `identity_number` stays reserved. |
| Parent | none | **hard delete** (`ParentController.php:239-250`). There is no FK on `students.parent_id`, so the children become orphans. Locally, 3 students already point at a missing parent. |
| Teacher | none | **hard delete** (`Teachercontroller.php:670-674`) |
| Staff | none | `UserController::destroy` (`:167`), hard delete |

### 1.6 Hierarchy (what a student or parent hangs off)

```
companies (2) ─< complexes (6, is_active, company_id, supervisor_id)
                   ─< grades = "schools" (20; complex_id NOT NULL, school_type_id 1/2/3 = ابتدائية/متوسطة/ثانوية,
                                          grade_type_id 1/2 = بنين/بنات, is_active, supervisor_id/deputy_supervisor_id/head_id)
                        ─< classrooms = school years (72; Grade_id, Name_Class «الصف الأول»…)
                             ─< sections = class groups (243; Grade_id, Class_id, Name_Section, Status)
students: Grade_id, Classroom_id, section_id, parent_id → my__parents
```

Each school is a single stage and a single gender (grades 1–24 cover 3 stages × 2 genders × 3–4 complexes). A primary school has 6 classrooms. Intermediate and secondary schools have 3.

**Minimum payload to create a usable student:**
- parent: `identity_number`, `name`, `password`
- student: `name`, `identity_number`, `password`
- a school (`Grade_id`) with the right stage and gender, plus `Classroom_id` and `section_id` inside it

**Parent:** only `identity_number`, `name`, `password`. No school.

**What the student actually sees depends on the placement:**
- Program exams are filtered by `section_id`. Qiyas: `StudentQiyasExamController.php:29-31`. Nafis and tahsili: `:29` in each. Regular exams: `Students/dashboard/ExamController.php:22-30`, which returns nothing when `section_id` is null.
- Self-learning and parent level tests are filtered by classroom (`StudentSelfLearningController.php:173,490`; `ParentLevelTestController.php:101,176-199`).
- Diagnostics and rewards are filtered by `Grade_id` (`StudentSelfLearningController.php:640-686`).
- Sidebar entries depend on school stage and class name (source map §2.4).

Without a section the student sees **no exams**. With a section, they still see only the exams an admin or teacher assigned to it.

## 2. Parent ↔ student linkage

- The link is one-to-many: `students.parent_id` → `my__parents.id` (`Student::parent()` `Student.php:40-43`; `My_Parent::students()` `My_Parent.php:18-21`).
- One student has exactly one parent. There is no pivot, so a second guardian isn't possible.
- The parent dashboard lists `Student::where('parent_id', auth()->id())` (`routes/parent.php:31-34`). The parent routes are all `auth:parent` (`parent.php:34-110`).
- Parents are deduplicated by national ID through `firstOrCreate` when students are created.
- Locally: 857 parents have 2 or more children, and 774 parents have no live child.
- **485 parents use the same `identity_number` as a student.** This looks like the student's own ID being typed as the guardian's ID. Login doesn't break, because the table is chosen by `type`. But Rawani must key on **(role, national ID)**, not the national ID alone.

## 3. APIs and endpoints

`routes/api.php` is loaded under the `api` group with `throttle:api` (`RouteServiceProvider.php:35-37`; `Kernel.php:44-48`). Sanctum's stateful middleware is commented out.

| route | auth | use |
|---|---|---|
| `GET /api/user` (`api.php:18-20`) | `auth:sanctum` | Stock route. `personal_access_tokens` is empty locally (0 rows) and nothing in `app/` calls `createToken`, so it is unused. Only `User` (staff) has `HasApiTokens`. |
| `GET /api/classrooms/{Grade_id}` (`api.php:28-30`) | **none** | Selects `name`, but the column is `Name_Class`, so it errors. Not usable. |

**Nothing exists to create, update, disable or list users, or to log in, as JSON.**
- The only login primitives are `Auth::guard($g)->login($model)` inside `LoginController` (`:115-231`). There is no `loginUsingId`, signed-URL login or token login anywhere.
- CSRF is enforced on all web POSTs except `livewire/*` (`VerifyCsrfToken.php:14-17`), so server-to-server calls to the existing HTML forms are impractical.
- The `signed` middleware is registered (`Kernel.php:66`), but it validates against **Al-Awwal's own `APP_KEY`**. Rawani has a different key and can't mint URLs Al-Awwal would accept unless a secret is shared.

### 3.1 Security problems relevant to this integration

1. **Account takeover by national ID (critical).**
   - Public routes: `POST /forgot-password` → `verifyIdentity` (`web.php:84`; `ForgotPasswordController.php:20-47`). It looks up the ID, creates a token, and **redirects the requester straight to `/reset-password/{token}`**.
   - No SMS, email or second factor is involved, and none would be possible, because students and teachers have no phone or email.
   - `resetPassword` (`:118-149`) then sets any password.
   - Exposed: **students** (`:57-58`, which also reaches soft-deleted and graduated rows) and **teachers** (`:54-55`, which updates *every* teacher row sharing that ID).
   - Not exposed: **parent** queries a nonexistent table `parents` (`:60-61`), and **admin** queries a nonexistent column `users.identity_number` (`:63-64`). Both just error.
   - Tokens have no expiry check. Locally, 926 tokens have been issued historically, so the feature is in use.
2. **No auth on many staff routes after `web.php:387`.** The auth group closes at `:387`. The source map §9.9 lists the exam, report and teacher-exam groups. It does not list:
   - `promotions/*` (`web.php:392-414`): `run`, `batch`, `revert` and `revert-reverse` move or graduate whole sections of students. **They are unauthenticated.** `PromotionController::run` reads `auth()->user()` but has no middleware. There are also debug routes at `:412-414`.
   - `reports/*` at `:418-429`.
   - `reports.get-students-by-section` (`:425`, `:450`) targets a method that does not exist on `ReportController`, so it errors rather than leaking data.
3. **`POST /login` has no rate limit** (`web.php:75`), and national IDs are guessable formats.
4. **Temporary passwords are stored in plain text** (`temp_password_plain` in all four temp tables).
5. **No authorization below `auth:web`.** Any staff user, whatever their Laratrust role, can reach the student, parent and teacher CRUD inside the group. Permission checks live in Blade and the sidebar only (source map §2).

## 4. Subscriptions, payments, plans

**None gate access.**
- `web.php:19-24` imports `Students\{FeeInvoice,Fees,PaymentStudent,ProcessingFee,ReceiptStudent}Controller`, but no route uses them. No `fee*`, `payment*`, `receipt*`, `subscription*` or `plan*` tables exist (full `SHOW TABLES` checked). These are leftovers from the school-management template.
- No payment SDK is in `composer.json`.
- Rewards and points (`RewardController`, `parent_reward_settings`, `grade_reward_settings`, `student_rewards`, `students.points`; parent routes `parent.php:109-110`; staff routes `web.php:464-478`) are motivational. They don't gate any content.
- Content access is decided **only** by school placement plus teacher/admin exam assignment (§1.6).
- A Rawani package therefore can't "unlock" anything in Al-Awwal except by creating or enabling the account and placing it in a section that has exams.

## 5. Local DB counts (`alawal_local`, 2026-11-19; latest `students.created_at` 2026-11-19 10:17)

| entity | count |
|---|---|
| staff `users` (all with one Laratrust role) | 53 |
| students total / live / soft-deleted | 5,669 / 5,209 / 460 |
| students `status=graduated` (live, all with `section_id` NULL) | 943 |
| students `is_active=0` | 0 (the toggle is effectively unused) |
| students with a missing parent row | 3 |
| teachers / with email / duplicate-`identity_number` groups | 296 / 0 / 5 |
| parents / without a live child / with 2+ children | 4,849 / 774 / 857 |
| parent ID = some student ID; parent ID = some teacher ID; teacher ID = some student ID | 485; 24; 0 |
| `identity_number` matching `^[0-9]{10}$` | students 5,291/5,669, parents 4,357/4,849, teachers 272/296 |
| companies / complexes / schools (`grades`) / classrooms / sections | 2 / 6 / 20 / 72 / 243 |
| `personal_access_tokens` | 0 |

Students per school range from 73 to 675. Grades 25 and 26 are inactive and empty.

## 6. Integration options for Rawani

**Decisions needed before any option:**
- **Placement.** Where does a Rawani-provisioned student live in Al-Awwal?
  - Recommended: a new complex «رواني» with 6 schools (3 stages × 2 genders) and their standard classrooms, plus one section per classroom (e.g. «رواني»). Rawani sends stage, gender and school year, and Al-Awwal maps them to a section.
  - Someone must still create or assign exams to those sections. Self-learning by classroom works without that.
  - The alternative is placing a buyer into an existing real school/section, which Rawani would have to choose.
- **Identifier.** The national ID is mandatory in Al-Awwal, and it is the login username.
  - Rawani must collect `national_id` for the student and the parent before provisioning.
  - Don't invent synthetic IDs: they become the user's login and would collide with real IDs later.
- **Key = (role, national_id).** The same ID can legitimately be a parent and a student (485 cases).

### (a) Server-to-server API inside Al-Awwal (recommended) — effort **M**

Build in Al-Awwal (Laravel 9):
- A middleware that verifies `X-Rawani-Signature = HMAC-SHA256(secret, timestamp + body)` with a ±5-minute window, using a shared secret in both `.env` files and `throttle`. Routes go in `routes/api.php` under `/api/rawani/v1`.
- Endpoints:
  - `POST parents` (upsert by `identity_number`)
  - `POST students` (upsert by `identity_number`, with parent ID, stage, gender and year → placement)
  - `POST {type}/{identity}/disable` and `/enable`
  - `GET users?type=&updated_since=&page=` (no passwords)
  - `POST sso-token` → one-time token (random 64 chars, stored hashed, 60 s TTL, single use) → `GET /rawani/sso/{token}` does `Auth::guard($type)->login()` and redirects to the guard's home.
- Two migrations: a real `is_active` (or `disabled_at`) on `my__parents` and `teachers`, plus a `rawani_ref` external ID on `students` and `my__parents`. Optionally a `rawani_sso_tokens` table.
- A login gate: check `is_active` in `LoginController::login` and in the SSO route. For already-logged-in sessions, a small middleware on `auth:student` and `auth:parent` routes.
- Upsert must `withTrashed()->restore()` soft-deleted students. Their ID is still unique-reserved (460 rows).

Risks:
- **Duplicate IDs.** A student ID may already exist under another parent, or as a real school student. The API must **link, not create**, and report conflicts.
- **Parent name and password.** `firstOrCreate` keeps the old name and password. Decide whether Rawani overwrites them.
- **Required placement fields** (`gender_id`, `Grade_id`, `Classroom_id`, `section_id`) must come from the mapping table.
- **Teacher IDs aren't unique** (5 duplicate groups), so teacher provisioning has to fail on ambiguity.
- **Passwords.** Don't sync them. Set a random password on provision, and let users get in through the SSO link. Users who also log in directly keep their own Al-Awwal password, and the forgot-password hole means it can change anyway.
- **Deploy.** It has to go through git onto the live tree, which is the `live-2026-11-19` snapshot with no prior history. The other apps on the same hosting account are unaffected.

### (b) Direct DB access (same VPS, separate DB) — effort **S** for read, **M** for write

Build:
- A MariaDB user with `SELECT` on `alawal_prod.{students,my__parents,teachers,grades,classrooms,sections,complexes,companies}`, and a second `alawal` connection in Rawani's `config/database.php`. That is enough for the "master" listing of all Al-Awwal users and parents with their links.
- For writes, Rawani would insert rows itself with bcrypt hashes, which are compatible.

Risks:
- Writes bypass Al-Awwal logic (gender from school, parent `firstOrCreate`, `teacher_section_subject`).
- Rawani becomes coupled to legacy column names (`Grade_id`, `Name_Class`, `Class_id`, `my__parents`).
- There is no FK on `parent_id`.
- **Hard deletes are invisible** to an `updated_at`-based sync, so periodic full reconciliation is needed.
- Writes still give no auto-login, so the SSO endpoint from (a) is needed anyway.

Verdict: good for **read-only listing**. Don't use it for provisioning.

### (c) Real SSO (Rawani as IdP, OIDC/OAuth) — effort **L**

Build:
- An OAuth server on Rawani (Laravel 12 + Passport or similar).
- A custom Socialite-style client in Al-Awwal that maps the IdP subject to one of **four guards/tables**.
- Changes to the login and forgot-password flows, and to logout across the two domains (`rawaniedu.com` vs `alawal.online`, so no shared cookie).

Risks:
- It changes the login for roughly 10k existing users, who use national ID + password today.
- Sessions use the `file` driver.
- The staff `web` guard is mixed in with the others.
- It still needs the provisioning API from (a), because SSO doesn't create the school placement.

Verdict: not now. The one-time-token link in (a) gives the same "click from Rawani and you're in" experience for a fraction of the work.

**Recommended path:**
1. Fix the critical issues first: the forgot-password takeover, and auth on the `promotions` routes.
2. Build (a) with an HMAC API, the placement mapping, a real `is_active` and one-time SSO tokens.
3. Optionally use (b) as a read-only connection for the master dashboard, or simply use (a)'s `GET users`.

## 7. Gotchas

1. The live code is a snapshot (`648e171`). `php artisan route:list` crashes (`web.php:131` uses an un-imported `ParentController`; source map §9.1). New routes have to be tested by hitting them, not by listing them.
2. Timezone is `UTC` (`config/app.php:72`). Expiry and token windows must be compared in UTC.
3. Identity numbers aren't clean. About 7% of students and 10% of parents are not 10-digit numeric. Normalize (trim, Arabic→Latin digits) on both sides before matching. Al-Awwal already converts digits on every request through the global `ConvertArabicNumbers` middleware (`Kernel.php:24`), but the stored data still varies.
4. `students.name` is `text` and `teachers.Name` is capitalised. Teacher `Password` is written with a capital P (`Teachercontroller.php:492`); it works only because MySQL column names are case-insensitive.
5. `StudentsImport` calls `dd()` on the first bad row, and its lookups are exact Arabic-name matches. Don't reuse it for automated provisioning.
6. Graduated students (943) have `section_id` NULL and still log in. A package renewal for a graduate needs re-placement.
7. A parent sees all children with their `parent_id`. If Rawani links a buyer to an existing parent row, they see every child already linked there.
8. Deleting a parent or teacher in Al-Awwal's admin UI is a hard delete. Rawani's linked IDs dangle, so the API/listing must tolerate it and reconcile.
9. Different `APP_KEY`s per app mean no Laravel signed URLs or encrypted payloads across apps. Use the explicit shared HMAC secret.
10. `users` code vs local schema mismatch (`phone`/`type`, `UserController.php:81,83`). Check live `SHOW CREATE TABLE users` before touching staff accounts.
11. Students, parents and teachers have no email or phone, so Al-Awwal can't send them anything. Any notification (credentials, links) has to go from Rawani.
