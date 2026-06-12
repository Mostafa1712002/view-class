# Design: Certificates Module

Serves Trello **#192 §9 (الشهادات — teacher view)** and the certificates portion of **#172 (student/parent certificates)**. No prior certificate infrastructure exists.

## Data model

### `certificates` table (new migration)
| column | type | notes |
|--------|------|-------|
| id | bigint PK | |
| school_id | bigint unsigned, nullable, indexed | tenant scope; FK schools, nullOnDelete |
| type | string(32) | `student` \| `teacher` \| `training` \| `appreciation` |
| title | string(255) | |
| recipient_user_id | bigint unsigned, indexed | FK users, cascadeOnDelete — the holder |
| issued_by | bigint unsigned, nullable | FK users, nullOnDelete — issuer |
| issue_date | date | |
| status | string(16), default `draft` | `draft` \| `published` |
| note | text, nullable | |
| file_path | string(255), nullable | optional uploaded PDF/image on `public` disk |
| created_at / updated_at | timestamps | |
| deleted_at | softDeletes | |

## Model — `app/Models/Certificate.php`
- `SoftDeletes`. `$fillable` = type,title,recipient_user_id,issued_by,issue_date,status,note,file_path,school_id.
- `$casts` = ['issue_date'=>'date'].
- Relations: `recipient()` belongsTo User (recipient_user_id), `issuer()` belongsTo User (issued_by).
- Scopes: `scopePublished($q)` => where status=published; `scopeForSchool($q,$schoolId)` => when $schoolId, where school_id=$schoolId.
- Consts: `TYPES = ['student','teacher','training','appreciation']`, `STATUSES = ['draft','published']`.

## Module — `app/Modules/Certificates/`
```
Controllers/AdminCertificateController.php     # CRUD + publish — issue to any school member
Controllers/MyCertificateController.php        # teacher + student + parent read views
Repositories/Contracts/CertificateRepository.php
Repositories/EloquentCertificateRepository.php
Http/Requests/StoreCertificateRequest.php
Http/Requests/UpdateCertificateRequest.php
```
Reuse `App\Modules\Users\Controllers\Concerns\HasSchoolScope` (`activeSchoolId()`).

### AdminCertificateController (role: super-admin, school-admin)
- `index` — list school certificates, optional `?type=` & `?q=` (title/recipient) filters, paginate/limit.
- `create` / `store` — issue. Recipients dropdown = active users in active school (students+teachers). Optional file upload (`pdf,jpg,jpeg,png`, max 4096) → `store('certificates','public')`.
- `edit` / `update` — same fields; replace file if a new one is uploaded (delete old).
- `publish` (POST) — set status=published.
- `destroy` — soft delete; delete file from disk.
- Every read/write scoped by `activeSchoolId()`; route-model binding guarded so a cert from another school 404s.

### MyCertificateController
- `index` — branch on role:
  - teacher → certificates where `recipient_user_id = auth` **OR** `issued_by = auth`, school-scoped (#192 §9: "شهادات المعلم + شهادات الطلاب التي أصدرها أو شارك فيها").
  - student → `published()` where `recipient_user_id = auth`, school-scoped.
  - parent → `published()` where `recipient_user_id IN children ids`, school-scoped.

## Security
- All FK inputs school-scoped via `Rule::exists('users','id')->where(school_id=$schoolId when set)`.
- `type` `Rule::in(Certificate::TYPES)`; `status` `Rule::in(STATUSES)`.
- Issuing/editing/deleting = admin-only middleware. Teachers/students/parents never reach admin routes.
- IDOR: AdminCertificateController loads the model and `abort_unless($cert->school_id === activeSchoolId() || super-admin, 404)`.

## Routes (routes/web.php)
```php
// Admin issue/manage
Route::middleware(['auth','role:super-admin,school-admin'])->prefix('admin/certificates')->name('admin.certificates.')->group(function(){
    Route::get('/', [AdminCertificateController::class,'index'])->name('index');
    Route::get('/create', [...'create'])->name('create');
    Route::post('/', [...'store'])->name('store');
    Route::get('/{certificate}/edit', [...'edit'])->name('edit');
    Route::put('/{certificate}', [...'update'])->name('update');
    Route::post('/{certificate}/publish', [...'publish'])->name('publish');
    Route::delete('/{certificate}', [...'destroy'])->name('destroy');
});
// Teacher / student / parent read
Route::middleware(['auth','role:teacher,student,parent'])->prefix('my/certificates')->name('my.certificates.')->group(function(){
    Route::get('/', [MyCertificateController::class,'index'])->name('index');
});
```

## Views — `resources/views/certificates/`
- `admin/index.blade.php` — content-header row (title + "إصدار شهادة" button), filter bar, table (العنوان/النوع/المستلم/تاريخ الإصدار/الحالة/التحكم), publish + delete actions use global `vcConfirm({title:...}).then(r=>r.isConfirmed && form.submit())`.
- `admin/form.blade.php` — create/edit; type select, title, recipient select2, issue_date, status, note, file. **Do NOT** `@include('components.alerts')` (layout already renders flash + $errors).
- `my/index.blade.php` — read-only table for teacher/student/parent; download link when file_path present; empty state.

## i18n — `lang/{ar,en}/certificates.php`
Keys: title, issue, types.{student,teacher,training,appreciation}, status.{draft,published}, fields.*, actions.{publish,edit,delete,download}, flash.{created,updated,published,deleted}, empty.

## Sidebar — `resources/views/components/sidebar.blade.php`
- Admin (super-admin/school-admin): link to `admin.certificates.index` in the academic/system section.
- Teacher/student/parent: link to `my.certificates.index` ("شهاداتي").

## Provider
Bind `CertificateRepository::class => EloquentCertificateRepository::class` in `App\Providers\RepositoryServiceProvider`.
