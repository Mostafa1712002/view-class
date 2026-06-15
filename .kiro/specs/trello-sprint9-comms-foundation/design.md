# Design: Sprint 9 — Communications Foundation (Trello #231, card oi6gWI7x)

This card builds the **shared scaffolding** every Sprint-9 communication module (announcements,
calendar, virtual classes, discussion rooms, mailbox, SMS, WhatsApp, templates, reports,
parents-as-contact, sender-name, credit) plugs into. The individual pages are SEPARATE later
cards (C1–C14). This card delivers: the permission set, the sidebar group gating, and the
scope + activity-log conventions documented here.

---

## 1. Permission system — how it works (so later cards follow it)

The permission system has **three moving parts** that must stay in sync:

1. **`permissions` table** — one row per `group.action` slug, with an Arabic `name` and a `group`.
   Seeded by **migrations** that loop over a `$modules` array calling
   `DB::table('permissions')->updateOrInsert(['slug' => ...], ['name','group',...])` (idempotent).
   See `database/migrations/2026_06_14_300001_seed_module_permissions.php` for the canonical pattern.

2. **`JobTitlePermissionsController::MODULES`** (const array) — drives the **matrix UI**
   (`resources/views/admin/users/job_titles/permissions.blade.php`). Each entry is
   `'group' => ['label' => 'عنوان عربي', 'actions' => [...action slugs]]`. The matrix only renders
   a module row when at least one of its `group.action` slugs exists in the `permissions` table,
   and only renders an action pill when that specific permission row exists. **So a slug must be
   added to BOTH the seed migration AND this MODULES const** or it won't appear/won't persist.

3. **`job_title_permissions` pivot** (`job_title_id`, `permission_id`, `scope`) — the actual grant,
   written by the matrix `update()` via `sync()`. The pivot's `scope` enum
   (all/company/group/school/stage/class/section/subject/own_students/own_subjects/own) defines the
   data-visibility band for that grant.

### Resolution: `User::canDo($slug)` (app/Models/User.php)
```
1. super-admin            → always allow
2. permission_role match  → allow (legacy RBAC path)
3. otherwise, fail-closed EXCEPT read-only ".view":
   - default-allow applies ONLY to "*.view" so unconfigured users keep seeing menus/read pages
   - no job title                       → allow iff slug ends with .view
   - job title with 0 configured perms  → allow iff slug ends with .view
   - job title WITH configured perms    → allow iff that exact slug is granted (else deny)
```
- `User::canViewModule($module)` = `canDo("$module.view")` — the sidebar gate.
- `CheckPermission` middleware (`permission:slug` route alias in bootstrap/app.php) calls `canDo()`
  and `abort(403)` on failure — so controller routes are protected too.

### Assignable-scope restriction (kept from last session — DO NOT regress)
In `JobTitlePermissionsController::update()`, a **non-super-admin may only assign narrow scopes**
(`school` and below). The wide scopes `all/company/group` are super-admin only; anything else is
clamped to `school`. This prevents a school-admin from escalating a job title beyond their school.

---

## 2. Permission keys added by this card

Registered in BOTH the new seed migration
`database/migrations/2026_06_15_400000_seed_communications_permissions.php`
AND `JobTitlePermissionsController::MODULES`, grouped under the "عمليات التواصل" concern.

| group | actions | Arabic group label |
|-------|---------|--------------------|
| `announcements` | view, create, edit, delete, publish, read_log | الإعلانات |
| `calendar` | view, create_event, edit_event, delete_event, print | التقويم المدرسي |
| `virtual_classes` | view, create, start, join, view_attendance, recalc_attendance, clear_cache | الفصول الافتراضية |
| `discussion` | view, create, edit, delete, toggle_comments | غرف النقاش |
| `mailbox` | view, send, draft, delete, archive | صندوق البريد الداخلي |
| `sms` | view, send | الرسائل القصيرة SMS |
| `whatsapp` (extended) | + send | واتساب |
| `messages` | send_excel, templates, reports, sender_name, credit | خدمات الرسائل |
| `parents_contact` | view, manage | أولياء الأمور كجهة تواصل |

**Reconciliation note (overlap with legacy groups):**
- The matrix already has a legacy `mail` group (view/create/delete/send_notifications) for the
  *internal-mail* feature. Sprint-9 introduces a richer `mailbox` group. Both are kept: `mail` is
  legacy/internal, `mailbox` is the Sprint-9 module. Later mailbox-card code MUST gate on
  `mailbox.*`, not `mail.*`.
- The matrix already has a `whatsapp` group (view/send_whatsapp). This card EXTENDS it with a
  `whatsapp.send` action rather than creating a duplicate group. `parents.send_whatsapp` (legacy)
  stays for the parents module.
  - **WHICH SLUG GATES THE ACTUAL SEND (binding decision for C6/C12):** the Sprint-9 messaging
    module gates the WhatsApp send action on **`whatsapp.send`**. The pre-existing
    `whatsapp.send_whatsapp` is **legacy** (it predates this card) and MUST NOT be used by new
    comms code — it is kept only so existing grants don't break. Later cards check
    `canDo('whatsapp.send')`, never `whatsapp.send_whatsapp`.

Every new action slug also has an Arabic entry in the matrix blade's `$actionLabels` map
(`resources/views/admin/users/job_titles/permissions.blade.php`), so the matrix never shows a raw
English slug.

---

## 3. Data-scope convention (MANDATORY for every later comms card)

Every comms query that touches school-owned data MUST filter by scope. Enforce in the
**repository/action layer**, not scattered in controllers (CLAUDE.md rule). Resolution order:

1. **school_id** — always. `->where('school_id', $user->school_id)` (or the active school for
   multi-school admins). No comms row crosses a school boundary.
2. **role narrowing** on top of school_id:
   - **super-admin** → all schools (no school_id filter); company/group scopes apply if granted.
   - **school-admin** → own school only.
   - **teacher** → own classes / own subjects only (use `$user->subjects`,
     `$user->schedulePeriods`, or class assignment). E.g. virtual classes / discussion rooms the
     teacher owns or teaches.
   - **student** → own records only (own enrolled classes via `enrolledClassIds()`, own mailbox,
     announcements targeted at them).
   - **parent** → own children's records only (via `$user->children`).
3. The granted **pivot `scope`** further narrows within school (stage/class/section/subject/own*).
   Read it from `$jobTitle->permissions` when a card needs scope-aware listing; default to
   `school` when unset.

A shared `app/Modules/Communications/Concerns/CommsScope.php` trait is provided as the place to
centralise the school_id + role narrowing helper so every comms repository reuses one
implementation. Keep it minimal; later cards extend it.

---

## 4. Activity-log convention (MANDATORY for every later comms card)

Use the existing logger — DO NOT invent one. `App\Models\ActivityLog`:

```php
use App\Models\ActivityLog;

// generic
ActivityLog::log(string $action, string $description, ?Model $model = null,
                 ?array $oldValues = null, ?array $newValues = null);

// convenience helpers (auto-capture user_id, school_id, ip, user_agent):
ActivityLog::logCreate($model, 'وصف عربي');
ActivityLog::logUpdate($model, 'وصف عربي', $oldValues);
ActivityLog::logDelete($model, 'وصف عربي');
```

`log()` auto-fills `user_id`, `school_id` (from the actor), `ip_address`, `user_agent`. Comms cards
log every mutating action (publish announcement, send SMS/WhatsApp batch, start/cancel a virtual
class, send mail, delete a discussion room) with a concise Arabic `$description`. Reads are NOT
logged unless a card explicitly needs an audit trail (e.g. `announcements.read_log` is a *feature*
of the announcements module, not the ActivityLog table — that is its own read-receipt store).

Reference existing usage: `app/Modules/Evaluation/Services/AuditTrail.php` wraps `ActivityLog::log`
with a module prefix — a good pattern for a comms-specific audit wrapper if a card needs one.

---

## 5. Sidebar group

The "عمليات التواصل" section already exists in `resources/views/components/sidebar.blade.php`
(section `communication`, ~lines 517–607). This card:
- Gates each comms item with the established formula
  `!$sidebarUser || !$isStaff || $sidebarUser->canViewModule('<module>')` so students/parents
  (non-staff) are NEVER hidden (mirrors the A4 fix), and configured staff see only what they may.
- Replaces `href="#"` placeholders with `Route::has(...)` gates so an item only renders when its
  route exists. Items whose pages aren't built yet (no route) simply don't appear — no broken
  links. Later cards add the route and the item lights up automatically.

Current end-state (routes that exist today): announcements & classified-ads placeholders are hidden
(no routes yet); calendar, virtual-classes, discussion, mailbox, SMS, WhatsApp, sender/credit, and
parents-contact link to their real routes where present.
