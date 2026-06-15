# Tasks: Parent CRM (Sprint 10, Trello #269)

## Phase 1: Schema
### Task 1.1: Additive migrations
- [x] parent_complaints (soft deletes, code, status enum)
- [x] parent_school_visits
- [x] parent_scheduled_calls
- [x] Run `php artisan migrate` locally (applied DONE)

## Phase 2: Domain layer
### Task 2.1: Models
- [x] ParentComplaint / ParentSchoolVisit / ParentScheduledCall (relations + casts)
### Task 2.2: Repository
- [x] ParentCrmRepository contract + Eloquent impl (school-scoped reads, timeline union, code generator)
- [x] Bind in RepositoryServiceProvider
### Task 2.3: Counts on existing list repo
- [x] complaint/visit/call count subselects in EloquentParentsContactRepository::paginate

## Phase 3: HTTP layer
### Task 3.1: Form Requests (canDo authorize)
- [x] StoreComplaintRequest / StoreVisitRequest / StoreCallRequest
### Task 3.2: Controller
- [x] ParentCrmController (storeComplaint/Visit/Call, school denormalize, ActivityLog, scope guard)
- [x] ParentsContactController::show passes complaints/visits/calls/timeline
### Task 3.3: Routes (appended group, no edit to existing block)
- [x] complaints.store / visits.store / calls.store gated by parents_contact.manage

## Phase 4: UI
### Task 4.1: Index columns
- [x] الشكاوى / الزيارات / الاتصالات count columns
### Task 4.2: Detail tabs
- [x] 4 tabs (شكاوى / زيارة مدرسة / اتصال مجدول / الخط الزمني) + add modals + unified timeline
- [x] Restored the original linked-children table (non-regression)
- [x] CRM counts added to CSV export

## Phase 5: Verification (Playwright + DB)
- [x] List loads 200 (super-admin, active school) with new columns
- [x] Detail loads 200 with tabs + KPIs
- [x] Add complaint E2E → DB row CMP-000001 + ActivityLog
- [x] Add visit E2E → DB row + ActivityLog
- [x] Add call E2E → DB row + ActivityLog
- [x] Timeline merges CRM + mail/whatsapp/notifications (7 events, newest-first)
- [x] super-admin null-scope see-all path (no 500, lists all + CRM counts)
- [x] teacher → 403 on list AND detail (role gate)
- [x] school-admin lacks parents_contact.manage by default (write fail-closed)
- [x] non-regression: existing comm logs still render; views compile

## Progress Tracking
| Phase | Tasks | Completed | Status |
|-------|-------|-----------|--------|
| 1. Schema | 1 | 1 | Done |
| 2. Domain | 3 | 3 | Done |
| 3. HTTP | 3 | 3 | Done |
| 4. UI | 2 | 2 | Done |
| 5. Verify | 10 | 10 | Done |
| **Total** | **19** | **19** | **100%** |

## Notes
- No new permissions (parents_contact.view read / parents_contact.manage write).
- No edits to existing route block, sidebar, or permission seeder.
- Routes live in the module's own Routes/web.php (already required) — NO require line added to routes/web.php.
- routes/web.php / EducationalSites / Admissions changes in the working tree are from sibling cards (#268/#270), not this card.
- Not committed/pushed/deployed per instructions.
