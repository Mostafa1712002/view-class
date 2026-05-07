# Default Login Credentials

**Status:** Locked. Do not change. Reproducible via `php artisan db:seed`.

These are the canonical local/dev/QA test accounts for ViewClass (الأول / Al-Awwal). They are seeded by `database/seeders/AdminUserSeeder.php` and `database/seeders/SchoolManagerUserSeeder.php`. If the DB is wiped, re-running the seeders restores the same credentials byte-for-byte.

## Accounts

| Role | Login (email or username) | Password | School | Seeder |
|------|---------------------------|----------|--------|--------|
| مدير النظام (Super Admin) | `admin@goldenplatform.com` *or* `admin` | `admin123` | DEMO-001 | `AdminUserSeeder` |
| مدير المدرسة (School Manager) | `manager@alawwal.local` *or* `manager` | `manager123` | DEMO-001 | `SchoolManagerUserSeeder` |

The login form (`/login`) accepts either the email or the username in the same `email` field — `LoginController` auto-detects via `FILTER_VALIDATE_EMAIL`.

## Re-seed (if DB is wiped)

```bash
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=SchoolManagerUserSeeder
# or all of DatabaseSeeder:
php artisan db:seed
```

Both seeders use `updateOrCreate`, so re-running is idempotent.

## Reset password manually

If a future migration alters columns and the seeder breaks, reset directly:

```bash
php artisan tinker --execute='
use App\Models\User; use Illuminate\Support\Facades\Hash;
User::where("email","admin@goldenplatform.com")->update(["password"=>Hash::make("admin123")]);
User::where("email","manager@alawwal.local")->update(["password"=>Hash::make("manager123")]);
'
```

## Known issue (out of scope for credentials)

Logging in as **مدير المدرسة** currently renders the dashboard with HTTP 500 because `app/Http/Controllers/DashboardController.php:103,178` queries `exams.school_id` and `exams.start_date` — neither column exists in the current schema (the `exams` table uses `class_id` for tenancy and `start_time`/`end_time` for scheduling). This is a pre-existing legacy bug from the Sprint 1/4 dashboard widget, **not** caused by the new manager account. File a separate Trello card to align the dashboard query with the actual `exams` schema (rename `start_date` → `start_time` and replace `school_id` filter with a join through `class_rooms`).

The Super Admin (`admin@goldenplatform.com`) lands on the dashboard fine.

## Rules

- **Do not change these passwords.** They are documented here, in the seeders, and in shared QA notes. Rotating them silently breaks every QA workflow and Playwright smoke test.
- **Never commit a different password** to the seeders without updating this file in the same commit.
- **Production deployments must use different credentials** — these are dev/QA only. Production admin is provisioned via the install wizard, not these seeders.
