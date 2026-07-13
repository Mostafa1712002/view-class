<?php

use App\Modules\Users\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Anti-lockout baseline for the roles/permissions rollout
 * (.kiro/specs/roles-permissions §5, Phase 2).
 *
 * Seeds permission_role so each role holds permissions matching its CURRENT
 * effective access — the set it reaches today through `role:` route guards.
 * When enforcement is LATER switched from `role:` to `permission:` on the exams
 * and libraries route groups, no currently-working user is locked out.
 *
 * - school-admin → the full catalog (they are the school's admin; they can do
 *   everything today via `role:super-admin,school-admin`).
 * - teacher      → exactly what the `/teacher/*` route groups expose today:
 *   exams/weekly-plans/grades/attendance writes + the reads the teacher UI shows.
 *   Libraries are READ-only for teachers (the write routes sit in an
 *   admin-only group), so only libraries.view is granted — writes stay fail-closed.
 * - student/parent → untouched (never in the exams/libraries admin/teacher groups).
 * - super-admin  → not seeded (canDo() short-circuits via isSuperAdmin()).
 *
 * Idempotent + additive: resolves permission_id by slug and updateOrInsert()s
 * onto the role, so re-running never removes a later manual edit from the UI,
 * and slugs that don't exist yet are simply skipped (never fabricate a grant).
 */
return new class extends Migration
{
    /**
     * Teacher's current effective access — derived from the `/teacher/*` route
     * groups in routes/web.php (writable resources + the reads the UI exposes).
     *
     * @var string[]
     */
    private array $teacherSlugs = [
        // Exams — Route::resource('exams') + publish/activate/complete (write)
        'exams.view', 'exams.create', 'exams.edit', 'exams.delete',
        // Weekly plans — Route::resource('weekly-plans') (write)
        'weekly-plans.view', 'weekly-plans.create', 'weekly-plans.edit', 'weekly-plans.delete',
        // Grades — index/store/publish (write)
        'grades.view', 'grades.create', 'grades.edit',
        // Attendance — index/store/mark-all-present (write)
        'attendance.view', 'attendance.create', 'attendance.edit',
        // Libraries — teacher reaches only the read group (index/show/items) — READ ONLY
        'libraries.view',
        // Reads the teacher UI surfaces
        'classes.view', 'subjects.view', 'schedules.view', 'reports.view',
        'students.view', 'calendar.view',
    ];

    public function up(): void
    {
        // school-admin gets the whole catalog; teacher gets its curated set.
        $this->grant('school-admin', PermissionCatalog::allSlugs());
        $this->grant('teacher', $this->teacherSlugs);
    }

    public function down(): void
    {
        // Non-destructive rollout: the baseline is additive and shared with
        // manual UI edits, so we do NOT strip permission_role on rollback.
        // (Re-running up() is safe and re-establishes the baseline.)
    }

    /**
     * Grant a role every existing permission in $slugs, idempotently.
     *
     * @param string[] $slugs
     */
    private function grant(string $roleSlug, array $slugs): void
    {
        $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
        if ($roleId === null) {
            return;
        }

        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');
        $now = now();

        foreach ($permIds as $permId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permId],
                ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }
};
