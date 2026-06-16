# Tasks: Sprint-10 Integration (#271 messaging link + #273 export/PDF)

## #271 — Link Sprint-10 to messaging + templates
- [x] SendAttendanceMessageAction — central bridge to SMS/WhatsApp/in-app + templates
- [x] FollowUpController::notify — sms/whatsapp now route through real layer (was in-app only)
- [x] FollowUpController::sendUserReports — same, + template_id support, school-grouped
- [x] userReports screen — real SmsTemplate dropdown (verified 13 options)
- [x] NotifyAbsenceJob — added SMS path (queued) alongside existing WhatsApp + in-app
- [x] Sprint10MessageTemplatesSeeder — seeded 8 student + 3 teacher templates (idempotent)

## #273 — Export / Print / PDF
- [x] ExportsReports trait — mPDF(RTL/XB Riyaz) + xlsx + CSV(BOM) primitives
- [x] AttendanceQueryService::reportQuery — shared scoped+filtered builder (screen == export)
- [x] ReportExportController — status/day-absence/period-absence/late/behavior × pdf/excel/csv
- [x] Export buttons partial added to 5 attendance report screens + print button
- [x] TeacherAttendance::export — pdf/excel/csv + buttons on board
- [x] Gated by pdf_export (fail-closed); certificates + QR PDFs already covered (verified)

## Verify (DONE)
- [x] absence → queued sms_messages row (status=queued) — UI + tinker
- [x] attendance report PDF (%PDF + Arabic shaping via pdftotext) + CSV (Arabic + BOM)
- [x] both admin (school=1) and null_super (school=null) → no 500
- [x] non-regression: 10 screens 200; school-admin pdf_export=false (correct 403)

## Progress
| Card | Status |
|------|--------|
| #271 | Done (vertical slice) |
| #273 | Done (attendance + teacher + verified existing cert/QR) |
