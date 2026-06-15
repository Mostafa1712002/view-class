# Tasks: Support / Tickets (Trello #267)

## Phase 1: Schema + models
### Task 1.1: Additive migration
- [x] Add `type`, `department`, `problem_url` to `support_tickets`
- [x] Create `support_ticket_status_logs` (سجل تغيير الحالة)
- [x] Run locally

### Task 1.2: Models
- [x] `SupportTicketStatusLog` model
- [x] Extend `SupportTicket` (fillable, statusLogs relation, TYPES/DEPARTMENTS/STATUSES consts, type/department labels, derivedReplyState, static statusLabelFor)

## Phase 2: Data + business layer
- [x] Repository contract + impl: `?int $schoolId` (super-admin see-all), `adminCounts`, `delete`, status-log on updateStatus, reply_state derived filter
- [x] `SupportNotifier` service → writes to existing `notifications` sink (navbar bell)
- [x] Form requests: type/department/problem_url/attachment

## Phase 3: Controllers
- [x] Admin: scopedSchoolId fail-closed, canDo gates, attachments, close/reopen/delete, ActivityLog, notifications, attachment download
- [x] User: scopedSchoolId, attachments, type/department/problem_url, ActivityLog, notify, own-attachment download

## Phase 4: Routes + gating
- [x] `app/Modules/Support/Routes/web.php` with `permission:support.*` per action
- [x] Inline blocks in routes/web.php replaced with `require` line

## Phase 5: Views (RTL, design-system, x-svg-icon)
- [x] Admin index: 5 stat cards + click-to-filter + extended columns + filters (status/priority/type/department)
- [x] Admin show: attachments, status log, close/reopen/delete, assign, status
- [x] User create: type/department/priority/category/problem_url/attachment (multipart)
- [x] User show: type/department/problem_url/attachment download + reply attachment

## Phase 6: Lang
- [x] ar + en support.php new keys

## Phase 7: Verify (Playwright)
- [x] Admin (school-scoped) loads 200
- [x] Super-admin null-school loads 200, sees ALL schools (no crash)
- [x] Create / reply / status / assign / close / reopen end-to-end
- [x] Stat-card click filter
- [x] Status log rows recorded
- [x] Notifications + ActivityLog rows created
- [x] 403 for user without support grant (write-class denial, real not nominal)
- [x] Normal user sees only own tickets; cross-user ticket → 403

## Progress
| Phase | Status |
|-------|--------|
| 1–7   | Done (local, not committed) |

## Deferred (not in acceptance criteria)
- "تخصيص الأعمدة" (customize columns) — not built (gold-plating).
- "تصدير إلى" (export) — not built (not in شروط القبول; would reuse support.view).
- Multiple attachments per ticket/reply — single file each (uses existing `attachment_path`).
- Legacy ticket category `general` has no lang key (pre-existing data; out-of-vocabulary).
