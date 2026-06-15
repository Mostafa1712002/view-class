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
- [x] C6 #237 رسائل واتساب حسب الاسكرينات الجديدة  (OYxDiBCV)
- [x] C7 #238 قوالب الرسائل القصيرة  (59FxoN7o)
- [x] C8 #239 الرسائل القصيرة SMS + إرسال من Excel  (fOnH3S5T)
- [x] C9 #240 تقارير الرسائل وسجلات الإرسال  (N7xbkc1S)
- [ ] C10 #241 إعدادات رسائل الطلاب المجمعة ونماذج الرسائل  (TCtNYskB)
- [x] C11 #242 إدارة أولياء الأمور كجهة تواصل  (cuoSJKJP)
- [x] C12 #243 خدمات الرسائل الإضافية (sender name + credit)  (HWOUFFDr)
- [x] C13 #244 تحسين التصميم/UX لكل صفحات التواصل  (ectMOQsi)  — 40 comms views polished (ds-* + gold/navy + x-svg-icon + empty states); SMS template form rebuilt (grouped searchable chips + live preview + counter). See c13-c14.md. NOT committed/deployed.
- [x] C14 #245 اختبارات التواصل النهائية والتكامل  (P2320D7u)  — route grid (admin/null-school/no-perm) PASS; core flows verified via HTTP+DB+activity-log; fixed missing activity-log on sender-name + credit requests. NOT committed/deployed.

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

## Expanded scope (user-approved 2026-06-15)
Execution order after current Sprint 9 finishes:
1. C6 #237 whatsapp + C11 #242 parents-contact — DONE local, awaiting SMS agent to co-deploy (shared RepositoryServiceProvider)
2. SMS subsystem C7 #238 / C8 #239 / C9 #240 / C10 #241 / C12 #243 (provider creds requested from Mahmoud on #239; sends queued until then)
3. C13 #244 comms UX polish + C14 #245 integration tests
4. #221 QA BOUNCE (soc4avfb) — Mahmoud rejected: "design bad, sidebar broken, bigger font, clearer icons site-wide" → creative sidebar/header redesign + FULL icon-clarity sweep (the #220 long-tail). User chose "creative redesign + clear icons".
5. NEW Question-Bank rebuild sprint: #247-259 (13 cards) — agent per card
6. NEW Sprint 10: #260-274 (15 cards: attendance, certificates, support/tickets, admissions, parent CRM, educational websites, exports) — agent per card
Deploy live one-at-a-time, QA Arabic + assign creator (Mahmoud 68905a1af157def85d18a667), each verified live incl. super-admin null-school case. Use scopedSchoolId() fail-closed helper for all school scoping.
