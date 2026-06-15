# Design: Parent CRM (Sprint 10, Trello #269)

## Approach
Extend the existing Communications "parents-as-contact" feature (Sprint 9, #242). Do NOT build a
parallel CRM page. The parents list and per-parent detail already exist and are sidebar-linked;
this card layers complaints/visits/calls + a unified timeline onto them.

- **No new module** — code lives under `app/Modules/Communications`.
- **No new permissions** — read `parents_contact.view`, write `parents_contact.manage`.
- **No edits** to the existing route block, sidebar, or permission seeder. A NEW route group is
  APPENDED to the already-`require`d `app/Modules/Communications/Routes/web.php`.

## Schema (3 additive tables, soft deletes, bigint unsigned to match users.id)

### parent_complaints
| col | type | notes |
|---|---|---|
| id | bigint unsigned PK | |
| school_id | bigint unsigned, idx | denormalized scope |
| parent_id | bigint unsigned, idx | users.id (parent) |
| student_id | bigint unsigned null | linked child (users.id) |
| code | varchar(20) | generated CMP-###### |
| type | varchar(40) | نوع الشكوى |
| complaint_date | date | |
| purpose | varchar(255) | الغرض |
| details | text null | |
| action_required | text null | الإجراء المطلوب |
| actions_taken | text null | الإجراءات التي تمت |
| priority | varchar(10) | low/normal/high/urgent |
| assigned_to | bigint unsigned null | staff users.id |
| status | varchar(30) | new/in_progress/awaiting_parent/resolved/closed |
| attachment_path | varchar(255) null | |
| created_by | bigint unsigned null | |
| timestamps + softDeletes | | |

### parent_school_visits
| col | type | notes |
|---|---|---|
| id, school_id, parent_id | as above | |
| student_id | bigint unsigned null | |
| visit_date | date | |
| visit_time | time null | |
| reason | varchar(255) | سبب الزيارة |
| met_staff_id | bigint unsigned null | الموظف الذي قابله |
| summary | text null | ملخص الزيارة |
| next_action | text null | الإجراء التالي |
| followup_date | date null | تاريخ المتابعة |
| status | varchar(30) | open/done/followup |
| created_by, timestamps, softDeletes | | |

### parent_scheduled_calls
| col | type | notes |
|---|---|---|
| id, school_id, parent_id | as above | |
| call_date | date | |
| call_time | time null | |
| call_type | varchar(20) | incoming/outgoing |
| purpose | varchar(255) | الغرض |
| outcome | text null | نتيجة الاتصال |
| answered | tinyint(1) default 0 | هل تم الرد |
| notes | text null | |
| followup_at | datetime null | موعد متابعة جديد |
| assigned_to | bigint unsigned null | الموظف المسؤول |
| status | varchar(30) | scheduled/done/missed |
| created_by, timestamps, softDeletes | | |

FK constraints omitted (legacy convention; avoids type/charset mismatch on migrate). Scope is
enforced in the repository, not the DB.

## Code layout (Communications module)
```
app/Modules/Communications/
├── Controllers/ParentCrmController.php           # storeComplaint/storeVisit/storeCall (thin)
├── Models/{ParentComplaint,ParentSchoolVisit,ParentScheduledCall}.php
├── Repositories/Contracts/ParentCrmRepository.php
├── Repositories/EloquentParentCrmRepository.php  # all Eloquent + school scope + timeline union
├── Http/Requests/{StoreComplaint,StoreVisit,StoreCall}Request.php
└── Routes/web.php                                # APPEND new group
```
- `EloquentParentsContactRepository::paginate()` gains complaint/visit/call count subselects.
- `EloquentParentsContactRepository::findScoped()` unchanged; controller `show()` pulls CRM data
  via `ParentCrmRepository` (cross-repo call within the module is fine).
- Bind contract→impl in `RepositoryServiceProvider`.

## Routes (appended group, same role + permission gating)
```
admin/parents-contact/{parent}/complaints   POST  permission:parents_contact.manage  -> storeComplaint
admin/parents-contact/{parent}/visits       POST  permission:parents_contact.manage  -> storeVisit
admin/parents-contact/{parent}/calls        POST  permission:parents_contact.manage  -> storeCall
```
Names: `admin.parents-contact.complaints.store` etc. Backend re-checks `canDo('parents_contact.manage')`.

## Timeline
Union of: complaints, visits, calls (new), + mail, whatsapp, notifications (existing repo methods),
each mapped to `{kind, icon, title, meta, at}` and sorted desc. Satisfies the card's "أي تواصل سابق"
(absence/certificate/admission messages already arrive via mail/whatsapp/notifications channels).

## Activity log
`ActivityLog::logCreate($model, 'إضافة شكوى لولي أمر' | 'تسجيل زيارة مدرسة' | 'جدولة اتصال بولي أمر')`.

## Views
- Edit `index.blade.php`: add 3 count columns (complaints/visits/calls) + a 5th KPI optional.
- Rework `show.blade.php` into tabs (Bootstrap nav-tabs already available): شكاوى / زيارات / اتصالات /
  الخط الزمني, each with an "إضافة" modal posting to the new routes. Keep existing identity + children +
  communication-log content inside the timeline / dedicated tab.
