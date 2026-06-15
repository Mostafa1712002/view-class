# Tasks: ViewClass Sprint-9 + standalone batch (25 Trello cards)

Run: agent-per-card · deploy live one-at-a-time · QA Arabic + assign creator.
Order minimizes shared-file conflicts (sidebar/header/template serialized).

## Batch A — standalone fixes (bug-shaped, reproduce live first)
- [x] A1 #228 حذف العيادات — remove clinics module everywhere + sidebar  (rfqLmuuk)
- [x] A2 #246 التقييم — evaluations/create Arabic key leak (evaluation.form.shared_*)  (bOWtNImi)
- [x] A3 #229 الخروج من تاب الاختبار — dynamic exit count + reopen locked exam  (LpycSPoN)
- [x] A4 #230 السايد بار — student/parent/teacher sidebar broken + link order  (fpk7TVBz)

## Batch B — design reconcile (shared files: sidebar/header/template/design-system — SERIAL)
- [x] B1 #220 الأيقونات — replace ALL icons → Bootstrap Icons (page-content too, not just nav)  (bkSpK1UQ)
- [x] B2 #221 القائمة الجانبية والهيدر — redesign sidebar+header all roles  (soc4avfb)
- [x] B3 #219 تيمبلت موحد — unified template + visual identity all roles  (VOeHxydN)
- [x] B4 #170 هيكل حساب الطالب — student account shell/header/sidebar/dashboard  (nKHlmgKv)
- [x] B5 #162 بطاقات المستخدمين — user cards fixes/gaps  (xTKbhPVi)
- [x] B6 #173 المكتبة العامة... (student account: libraries, files, virtual labs, appts, SpecialEd, policies)  (DLm2PbYp)

## Batch C — Sprint 9 communications (15 cards; new modules can build parallel, deploy serial)
- [x] C0 #231 Sprint 9 overview / عمليات التواصل  (oi6gWI7x)
- [x] C1 #232 موديول الإعلانات  (gYAL0yss)
- [x] C2 #233 التقويم المدرسي وإدارة الأحداث  (CMbz5vRx)
- [x] C3 #234 الفصول الافتراضية وربطها بالحضور  (3DvY4q7Y)
- [x] C4 #235 غرف النقاش  (J0zRtLHR)
- [x] C5 #236 صندوق البريد الداخلي  (W0iPhE0G)
- [ ] C6 #237 رسائل واتساب حسب الاسكرينات الجديدة  (OYxDiBCV)
- [ ] C7 #238 قوالب الرسائل القصيرة  (59FxoN7o)
- [ ] C8 #239 الرسائل القصيرة SMS + إرسال من Excel  (fOnH3S5T)
- [ ] C9 #240 تقارير الرسائل وسجلات الإرسال  (N7xbkc1S)
- [ ] C10 #241 إعدادات رسائل الطلاب المجمعة ونماذج الرسائل  (TCtNYskB)
- [ ] C11 #242 إدارة أولياء الأمور كجهة تواصل  (cuoSJKJP)
- [ ] C12 #243 خدمات الرسائل الإضافية (sender name + credit)  (HWOUFFDr)
- [ ] C13 #244 تحسين التصميم/UX لكل صفحات التواصل  (ectMOQsi)
- [ ] C14 #245 اختبارات التواصل النهائية والتكامل  (P2320D7u)

## Progress
| Batch | Cards | Done |
|-------|-------|------|
| A | 4 | 0 |
| B | 6 | 0 |
| C | 15 | 0 |
| **Total** | **25** | **0** |

## Hard rules (every card)
- Read card desc + ALL comments + download/READ every attachment (Sprint 9 is screenshot-driven).
- Bug cards: reproduce live before fixing. Build cards: spec→build→local test.
- school_id scope on every school-data query. Never artisan as root (sudo -u www-data / chown after).
- No `git add -A`/`.`. git pull --rebase before push. Deploy: ssh → git pull → migrate --force → caches → chown.
- Independently verify live (real route/Playwright) — do NOT trust agent's self-report.
- QA comment Arabic = what-was-done + numbered test steps; move to testing (69f0774def6235774c7409c5); assign card creator.
