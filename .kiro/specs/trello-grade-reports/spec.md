# Spec: تقارير الدرجات — grade-reports lifecycle

## Status: DRAFT — awaiting user confirmation + priority before build
Card: تقارير الدرجات (`/admin/grade-reports`, `/admin/grades`) — "مطلوب تعديلات كتيرة" (vague, broad).

## Key finding — the module already covers much of the lifecycle
`grade_reports` columns: `type`, `is_active`, `is_locked`, `title`, `grade_input_starts_at/ends_at`, `calc_starts_at/ends_at`, `opens_at/closes_at`, `include_behavior`, `show_subject_bilingual`, `visible_to_student/parent/teacher`, `header_settings`, `footer_settings`, school/year/term/class/subject scope. Models: `GradeReport`, `GradeReportColumn`, `GradeReportRating`. Controllers: `GradeReportController` (index/create/store/show/edit/update/updateColumns/destroy) + `GradeEntryController`. Views: index, create-dynamic, edit, _form, show.

So lock, publish/close dates, columns, ratings, student/parent/teacher visibility, header/footer all EXIST. The card is **expand + polish**, not greenfield.

## The card's asks → concrete deltas
1. **تقرير رصد الدرجات (grade-monitoring report)** — a page showing, per subject/class/section/teacher, whether grade entry is complete or still has missing grades. (Explicit "أزرار رئيسية: تقرير رصد الدرجات".) Likely NEW. High value.
2. **Report types** — the card lists 9: ديناميكي، ثابت، كشف الدرجات، إشعار الدرجات، مراجعة الدرجات، رصد الدرجات النهائية، تقرير اكتمال الرصد، السلوك والمواظبة، حجب الدرجات. The `type` column exists; verify which are implemented (likely only dynamic). **Needs priority** — implementing all 9 is large; recommend: dynamic (exists) + كشف الدرجات (transcript) + إشعار الدرجات (notification) + رصد/اكتمال (monitoring) first.
3. **التقديرات (ratings) management** — UI to manage `GradeReportRating` (text grades/bands) shown in reports per school policy. Partially exists (model) — needs a management screen if missing.
4. **Report data page + table + control menu** — the index ("بيانات التقارير") with columns (title, lock, publish date, close date, type, control) and a per-report control menu (settings, columns, header/footer, monitor grades, publish/close, lock, student/parent view, export, delete). Mostly exists — polish + complete the control menu.
5. **Student/parent display** — ensure published reports respect `visible_to_student/parent` and render for them (tie-in with the parent-visibility work just shipped).
6. **Design polish + `content-header row`** consistency across the grade-reports/grades pages.

## Tasks (Sonnet build) — scoped to confirmed priority
- [ ] T1 Grade-monitoring report (رصد الدرجات): per subject/class/teacher completion status (entered vs missing), filters, export.
- [ ] T2 Report types: confirm dynamic works; add the priority types (transcript/notification/monitoring) using the existing `type` + columns; defer the rest.
- [ ] T3 Ratings (التقديرات) management screen (CRUD on GradeReportRating) if not already present.
- [ ] T4 Report control menu completion + index polish (lock/publish/close/columns/header-footer/monitor/export).
- [ ] T5 Student/parent published-report view (respects visibility flags).
- [ ] T6 content-header row + on-brand polish on grade-reports/grades pages.
- [ ] Verify locally, commit, deploy, hand to QA.

## 🔴 SECURITY — MUST FIX BEFORE DEPLOY (automated review flagged in GradeReportPrintController.php)
The build agent's new `app/Modules/GradeReports/Controllers/GradeReportPrintController.php` has 3 issues to fix at integration (orchestrator deploys, so fix before live):
1. **[HIGH] IDOR on class_id** — `ClassRoom::find($classId)` not school-scoped. Scope to current school (e.g. `whereHas('section', fn($q)=>$q->where('school_id',$this->schoolId()))`) + `abort_unless($class,404)`; prefer deriving classId from `$report->class_id` (already scoped) unless an override is required.
2. **[HIGH] IDOR on student_id** — `User::find($selectedStudentId)` not scoped. Restrict to current school AND to the report's class (`where('school_id',$this->schoolId())->whereHas('classes', class_id=$classId)`) + `abort_unless($student,404)`.
3. **[MEDIUM] visibility/publish gate not enforced** — `$isPublished` is computed but not enforced. Add server-side `abort_unless($isPublished,403)` (for non-admin viewers) + `abort_if($viewerIsStudent && !$report->visible_to_student,403)` / parent equivalent before loading grade values.
Verify after fix: cross-tenant class/student IDs return 404; unpublished/hidden report 403 for student/parent; admin still works.

## Open question for user (priority)
The card lists 9 report types but is vague. Confirm the priority subset for THIS pass (recommend: grade-monitoring + transcript كشف + notification إشعار + the dynamic that exists), leaving السلوك/حجب/final-variants for a follow-up — so this ships in a reasonable scope instead of stalling on all 9.
