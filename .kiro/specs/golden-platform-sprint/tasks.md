# Tasks: المنصة الذهبية (Golden Platform) Sprint

Source: Trello board فيوكلاس, list "sprint prompt". 10 cards. Local http://viewclass.test
admin@goldenplatform.com / Mostafa@123. Deploy live then QA (per user 2026-06-14).

## Cards

| # | Card | Trello id | Bucket | Status |
|---|------|-----------|--------|--------|
| 1 | تغيير الهوية → المنصة الذهبية | 6a2e143d31f539446e1a0b1d | visual-seq | ✅ DONE (deployed+QA) |
| 2 | تيمبلت موحد / Design System | 6a2e14530e9e312f948c7c01 | visual-seq | ✅ DONE (deployed+QA) |
| 3 | استبدال الأيقونات Bootstrap Icons | 6a2e14933fbe337b56b5ed6b | visual-seq | ✅ DONE (nav SVG deployed+QA; page-content icons kept on themed font per user) |
| 4 | القائمة الجانبية والهيدر | 6a2e14a5ee0e03ba9529b5b5 | visual-seq | ✅ DONE (deployed+QA) |
| 5 | ضبط PDF والتقارير المطبوعة | 6a2e14c98d5e1c9195443c03 | isolated | ✅ DONE (deployed+QA) |
| 6 | جلب بيانات الطلاب من نظام نور | 6a2e14e305422fea575ad7d9 | isolated | ✅ DONE (deployed+QA) |
| 7 | الحضور/الغياب + واتساب ولي الأمر | 6a2e1501de74ece944b51016 | isolated | ✅ DONE (deployed+QA) |
| 8 | صلاحيات Job Titles | 6a2e151ba1d0f13280939ed4 | isolated | ✅ DONE (deployed+QA) |
| 9 | بنك الأسئلة + ربط منصة الأول | 6a2e153c94cac8e47fecb77e | isolated | ✅ DONE (deployed+QA) |
| 10 | الدخول للإطلاع + ربط الطالب/ولي الأمر | 6a2e15593f2466ab5c1aecf8 | visual-seq | ✅ DONE (deployed+QA) |

## Progress
| Bucket | Cards | Done |
|--------|-------|------|
| isolated (worktree-parallel) | 5 | 0 |
| visual (main-tree sequential) | 5 | 0 |
| **Total** | **10** | **0** |

## PENDING SECURITY FIXES for Task 8 (apply after agent completes, BEFORE deploy)
- [ ] User::canDo() fail-open → default-allow ONLY for `*.view`; DENY write/manage (create/edit/delete/archive/approve/reject/import/export/manage_permissions/login_as_user) when job-title has no configured permissions. (CRITICAL)
- [ ] JobTitlePermissionsController index/update/copy → add school scope guard (global title ⇒ super-admin only; else school_id === activeSchoolId); scope `copy_from` source via forSchool. (HIGH)
