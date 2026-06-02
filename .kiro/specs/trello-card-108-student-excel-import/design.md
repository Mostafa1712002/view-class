# Design: استيراد الطلاب من ملف إكسل (Trello #108)

## Approach
Mirror the proven `App\Modules\NoorImport` pipeline (form → preview → execute → result +
operations log + CSV errors), but with an **Excel-template-specific** parser/DTO/importer.
We cannot reuse the Noor importer because the Excel template has English headers, explicit
Username + Password columns, and father/mother/English-name fields Noor does not carry.

## Module: app/Modules/StudentImport/
```
Controllers/StudentImportController.php   form, template, preview, execute, errorsReport
DTOs/StudentImportRowDto.php              one parsed row (30 fields)
DTOs/StudentImportResult.php              tallies
Actions/ParseStudentExcel.php            xlsx/csv → DTO[], maps the 30 English headers
Actions/ClassifyStudentRows.php          required-fields + dup-in-file + dup-in-system +
                                          grade/class existence + username collision
Actions/ImportStudentsAction.php         create/update users + profile + class link + parents
Http/Requests/StudentImportRequest.php   validation (file mimes, school_id)
```

## Column mapping (template → DB)
| Template header              | Target |
|------------------------------|--------|
| Identity Number *            | users.national_id (required) |
| Acceptance Year              | student_profiles.admission_year |
| First Name *                 | profile.first_name (required) |
| Last Name *                  | profile.last_name (required) |
| Father Name *                | profile.father_name (required) |
| Grand father name            | profile.grandfather_name |
| Username                     | users.username (generated from id if blank; collision → invalid) |
| Password                     | users.password (CREATE only; never on update) |
| Grade                        | sections.name (must exist) → section_id |
| Class                        | classes.name/division in section (must exist) → class_room_id |
| Gender                       | users.gender (ذكر/أنثى/male/female → male/female) |
| Mobile Number                | users.phone |
| Email                        | users.email |
| Birthdate                    | users.date_of_birth |
| Birth Place                  | profile.birth_place |
| Nationality                  | profile.nationality |
| Passport ID                  | profile.passport_number |
| Academic ID                  | profile.academic_id |
| Previous School              | profile.previous_school |
| Fingerprint ID               | profile.fingerprint_id |
| Father Identity Number       | profile.father_national_id (+ father parent acct) |
| Father Mobile number         | father parent acct phone |
| Mother Identity Number       | profile.mother_national_id (+ mother parent acct) |
| Mother Full Name             | profile.mother_full_name |
| mother mobile number         | mother parent acct phone |
| First/Father/Grand/Last (English) | profile.*_en |
| Sit Number                   | profile.seat_number |

`users.name` / `name_ar` = first + father + grandfather + last (Arabic, joined).

## Database
New table (separate archive, no cross-contamination with `noor_imports`):
```sql
CREATE TABLE student_imports (
  id, school_id, user_id (uploader), original_name, stored_path,
  status ENUM(previewed|completed|failed), total_rows,
  created_count, updated_count, failed_count, parent_created_count,
  preview_data JSON, errors JSON, note, timestamps
);
```

## Routes (admin, prefix admin/users/students/import)
| Method | URI | name |
|--------|-----|------|
| GET  | import            | admin.users.students.import.form |
| GET  | import/template   | admin.users.students.import.template |
| POST | import/preview    | admin.users.students.import.preview |
| POST | import/{log}/run  | admin.users.students.import.execute |
| GET  | import/{log}/errors | admin.users.students.import.errors |

## Wiring the fix
`resources/views/admin/users/students/index.blade.php` — the "استيراد من Excel" dropdown
item changes from `route('admin.noor.form', ['type'=>'students'])` to
`route('admin.users.students.import.form')`.

## Safety rules honored
- Password never touched on update; explicit-or-generated only on create.
- Username collision → invalid row (no silent rename, unlike Noor's auto-suffix).
- Grade/Class strict existence (deliberate divergence from Noor's fuzzy `like`).
- All lookups school-scoped.
