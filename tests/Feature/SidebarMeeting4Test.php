<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/** Meeting-4 sidebar clean-up: six sections only, nothing becomes unreachable. */
class SidebarMeeting4Test extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function hrefs(string $role): array
    {
        Role::firstOrCreate(['slug' => $role], ['name' => $role]);
        $school = School::factory()->create();
        $user = User::factory()->create(['school_id' => $school->id]);
        $user->roles()->attach(Role::where('slug', $role)->first());
        $this->actingAs($user);
        $html = Blade::render('<x-sidebar />');
        preg_match_all('/href="([^"#][^"]*)"/', $html, $m);

        return [$html, $m[1]];
    }

    /** @return list<string> */
    private function previousStaffRoutes(): array
    {
        return [
            'manage.schedules.index', 'admin.portfolio.index', 'manage.special-education.index',
            'manage.appointment-schedules.index', 'manage.appointments.index', 'admin.appointment-settings.index',
            'admin.eval-reports.supervisors', 'admin.eval-reports.supervisors-detailed', 'admin.eval-reports.general-manager',
            'admin.job-performance.index', 'admin.eval-audit.index',
            'admin.attendance.reports.status', 'admin.attendance.reports.aggregate', 'admin.attendance.reports.index',
            'admin.attendance.reports.late', 'admin.attendance.reports.behavior', 'admin.attendance.index',
            'admin.student-attendance.daily', 'admin.student-attendance.period', 'admin.student-attendance.follow-up',
            'admin.student-attendance.absence-messages.index', 'admin.attendance.reports.day-absence',
            'admin.attendance.reports.period-absence', 'admin.qr.cards.index', 'admin.qr.scanner', 'admin.qr.log',
            'admin.qr.groups.index', 'admin.qr.link.index', 'admin.qr.devices.index', 'admin.qr.cards.print',
            'admin.teacher-attendance.daily', 'admin.policies.index', 'admin.users.teachers.index',
            'admin.users.admins.index', 'admin.users.cards.index', 'admin.users.job-titles.index',
            'admin.noor.form', 'manage.academic-years.index', 'admin.behavior.groups.index',
            'admin.behavior.behaviors.index', 'admin.behavior.actions.index', 'admin.behavior.records.index',
        ];
    }

    public function test_staff_keep_every_previous_destination_and_old_sections_are_gone(): void
    {
        foreach (['super-admin', 'school-admin'] as $role) {
            [$html, $hrefs] = $this->hrefs($role);
            $this->assertStringNotContainsString('gp-sec-educational', $html);
            $this->assertStringNotContainsString('gp-sec-system"', $html);
            foreach ($this->previousStaffRoutes() as $name) {
                // qr.scan/view_log/link_*/print_card are canDo() write-style perms: school admins never saw them.
                $gated = in_array($name, ['admin.qr.scanner', 'admin.qr.log', 'admin.qr.link.index', 'admin.qr.devices.index', 'admin.qr.cards.print'], true);
                if (! app('router')->has($name) || ($role === 'school-admin' && $gated)) {
                    continue;
                }
                $this->assertContains(route($name, [], false), array_map(fn ($h) => parse_url($h, PHP_URL_PATH), $hrefs), "$role lost $name");
            }
        }
    }

    public function test_schedule_is_first_in_academic_and_reports_hold_absence_and_evaluation(): void
    {
        [$html] = $this->hrefs('school-admin');
        $academic = substr($html, strpos($html, 'id="gp-sec-academic"'));
        $this->assertLessThan(strpos($academic, route('manage.weekly-plans.index')), strpos($academic, route('manage.schedules.index')));
        $reports = substr($html, strpos($html, 'id="gp-sec-support"'));
        $reports = substr($reports, 0, strpos($reports, 'id="gp-sec-admissions"'));
        foreach (['admin.attendance.reports.status', 'admin.teacher-attendance.daily', 'admin.eval-reports.supervisors'] as $name) {
            $this->assertStringContainsString(route($name), $reports);
        }
    }

    public function test_other_roles_still_render(): void
    {
        foreach (['teacher', 'student', 'parent'] as $role) {
            [$html] = $this->hrefs($role);
            $this->assertStringContainsString('menu-title', $html, $role);
        }
    }
}
