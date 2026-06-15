# Tasks: Discussion Rooms — Card #235 (audit + gap-fill)

Audit of the existing `app/Modules/Discussion/` module against Trello card #235
"تنفيذ غرف النقاش". Gap-fill only; the base CRUD/topics/comments already existed.

## Phase 1: Gap-fill

### Task 1.1: Room behaviour settings (additive migration + model)
- [x] Migration: discussion_rooms.instructions, allow_topics, allow_comments, requires_approval, comments_count, last_activity_at
- [x] Migration: discussion_topics.comments_closed (per-topic toggle), is_hidden
- [x] Fillable + casts on DiscussionRoom / DiscussionTopic

### Task 1.2: Permission gating (design.md §1)
- [x] Manage routes gated per-action: permission:discussion.{view,create,edit,delete,toggle_comments}
- [x] Member routes: discussion.view on reads; topic/reply posting governed by room flags (NOT discussion.create)

### Task 1.3: Toggle comments (acceptance criterion)
- [x] Room-level toggle (toggleRoomComments) + route + enforcement in commentStore
- [x] Per-topic toggle (toggleTopicComments / comments_closed) + route + enforcement
- [x] Reply form hidden + abort(403) when comments disabled

### Task 1.4: Activate / Stop (تفعيل / إيقاف)
- [x] reopen() room action paired with existing close()

### Task 1.5: Report (acceptance criterion)
- [x] roomReport() repo aggregate (topics, comments, participants, last activity, top topics)
- [x] report.blade view + manage route + index button

### Task 1.6: Form fields per spec
- [x] create/edit blade: instructions, allow_topics, allow_comments, requires_approval switches
- [x] Hide/show + instructions surfaced to members

### Task 1.7: Activity logging (design.md §4)
- [x] logCreate/logUpdate/logDelete on room create/update/delete/close/reopen/toggle, topic/comment delete

## Phase 2: Verify (DONE)
- [x] Playwright: manage list (empty + populated), create room (all fields persisted),
      add topic (room counter bubbled), add comment (topic+room counters bubbled),
      toggle room comments OFF -> reply form hidden + direct POST 403 (backend enforced),
      report page (stats + top topics), close->reopen (status flips both ways),
      403 gating as teacher w/ configured job-title lacking discussion perms
      (member view / manage view / manage create all 403)
- [x] Fixed: findTopic eager-load was missing allow_comments column (would have
      falsely disabled comments in production)

## Deferred (wishlist in card, not acceptance criteria)
- Filters by subject/teacher/grade/class/school + auto/advanced-search checkboxes (only status filter today)
- Card-grid layout for manage list (kept existing table per "do not rebuild")
- Moderation approval queue UI (requires_approval flag stored; queue not built)
- PDF export of report (HTML report only)

## Files changed
- database/migrations/2026_06_15_500000_add_discussion_room_settings.php (NEW)
- app/Models/DiscussionRoom.php, app/Models/DiscussionTopic.php
- app/Modules/Discussion/Routes/web.php
- app/Modules/Discussion/Controllers/{ManageDiscussionController,DiscussionController}.php
- app/Modules/Discussion/Repositories/{Contracts/DiscussionRepository,DiscussionEloquentRepository}.php
- resources/views/discussion/manage/{index,create,edit,report}.blade.php
- resources/views/discussion/member/{room,topic,topic_create}.blade.php
- lang/{ar,en}/discussion.php
