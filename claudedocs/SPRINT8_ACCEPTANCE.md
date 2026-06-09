# Sprint 8 — Acceptance (شروط قبول) — evidence map

Date: 2026-06-09 · HEAD `ae01e79` · **deployed to live** (`viewclass.newaves-systems.com`). Verified on local `viewclass.test` (identical code) with the seeded end-to-end scenario + DB evidence; engine pages also smoke-tested on live.

| # | شرط القبول | Status | Evidence |
|---|---|---|---|
| 1 | إنشاء نموذج تقييم كامل | ✅ | forms in draft/published/archived; create→edit verified live (form created then deleted) |
| 2 | اختيار نوع النموذج | ✅ | rubric / rating_scale / checklist forms all exist |
| 3 | إضافة مستويات | ✅ | 9 evaluation_levels; rubric %s auto 33/66/100 |
| 4 | إضافة عناصر | ✅ | 7 evaluation_items; weight=100 gate enforced |
| 5 | إضافة مؤشرات | ✅ | 9 evaluation_indicators; rubric level-binding |
| 6 | تحديد مستهدفين | ✅ | 6 evaluation_targets; dedup + summary |
| 7 | تحديد مقيّمين | ✅ | 4 evaluation_assignments(+targets); self-eval blocked |
| 8 | نشر النموذج | ✅ | 4 snapshots frozen (items_frozen=2 each); status→published |
| 9 | يصل النموذج للمقيّمين | ✅ | evaluation_published notifications (6) + my-evaluations queue |
| 10 | المقيّم ينفّذ تقييم | ✅ | evaluations executed (ids 1,3,4,5,…); execution screen per type |
| 11 | حفظ كمسودة | ✅ | draft evaluations (ids 2,10) |
| 12 | منع تسليم ناقص | ✅ | submit blocked on missing required item/evidence (agent-verified) |
| 13 | رفع شواهد لكل بند | ✅ | evaluation_evidences row (file bound to item) |
| 14 | احتساب النتيجة تلقائيًا | ✅ | scores 86.67 / 83.33 / 66.67 match scoring engine; score_breakdown persisted |
| 15 | اعتماد/رفض التقييم | ✅ | approved(2)/rejected(1)/reopened(1)/review(1) statuses + notifications |
| 16 | جدولة زيارة صفية | ✅ | class-visits schedule + dup-guard + secret/notify (agent-verified) |
| 17 | تنفيذ تقييم أثناء الزيارة | ✅ | evaluation.visit.execute audit (2); visit→evaluation link |
| 18 | تظهر تقارير المشرفين | ✅ | supervisor summary renders live (KPIs) |
| 19 | تقرير المشرفين المفصل | ✅ | detailed report (row per evaluation) renders |
| 20 | شاشة المدير العام | ✅ | GM screen live: avg 80.84 / high 86.67 / low 78.89 |
| 21 | الفلاتر تعمل | ✅ | forms/reports/visit filters; score_from / status filters verified |
| 22 | الصلاحيات تعمل | ✅ | authoring admin-only; evaluator/subject routes teacher-inclusive; per-user ownership enforced |
| 23 | الإشعارات تعمل | ✅ | 13/13 triggers; 7 types present in DB; now deep-linked (action_url) |
| 24 | التصدير يعمل | ✅ | reports CSV export (UTF-8 BOM Arabic) |
| 25 | تسجيل العمليات الحساسة | ✅ | activity_logs `evaluation.*` (create/publish/assign/approve/submit/evidence/…); audit screen |
| 26 | عدم تأثر التقييمات القديمة عند تعديل النموذج | ✅ | evaluations bound to snapshot_id; scoring runs against frozen payload; published structure locked |
| 27 | الربط بتقييم الأداء الوظيفي | ✅ | job_perf_settings (aggregation/count_on/weight) writable; job-performance results view |

## Remaining (not engine-build)
- **5 QA test cards** (شروط قبول card group) — QA to execute on live.
- **Optional enhancements from the notification review** (user decision): add evaluation types to `Notification::TYPES` labels; render-time i18n (currently frozen at creation in actor locale — fine for all-Arabic); SMS/email channel for reminders; a teacher-facing class-visit page so visit notifications deep-link.
- Minor: `evaluation.evaluation.*` audit rows are pre-fix historical (new rows are single-prefix).
