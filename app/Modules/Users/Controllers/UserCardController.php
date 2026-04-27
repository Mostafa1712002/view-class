<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Section;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class UserCardController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $sections = Section::query()->where('school_id', $schoolId)->orderBy('name')->get();
        $classes = ClassRoom::query()->whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get();
        return view('admin.users.cards.index', [
            'sections' => $sections,
            'classes' => $classes,
            'tab' => $request->string('tab', 'students')->toString(),
        ]);
    }

    public function generate(Request $request): Response
    {
        $tab = $request->input('tab', 'students');
        $schoolId = $this->activeSchoolId();

        $users = match ($tab) {
            'students' => $this->resolveStudentsAndParents($request, $schoolId),
            'staff' => $this->resolveStaff($request, $schoolId),
            default => collect(),
        };

        $platform = config('app.name', 'ViewClass');
        $url = config('app.url');

        $pdf = Pdf::loadView('admin.users.cards.pdf', [
            'users' => $users,
            'platform' => $platform,
            'url' => $url,
            'tab' => $tab,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('user-cards-'.$tab.'.pdf');
    }

    private function resolveStudentsAndParents(Request $request, ?int $schoolId)
    {
        $studentQuery = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'student'))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($cid = $request->integer('class_room_id')) {
            $studentQuery->where('class_room_id', $cid);
        } elseif ($sid = $request->integer('section_id')) {
            $studentQuery->where('section_id', $sid);
        }
        if ($q = $request->string('q')->toString()) {
            $studentQuery->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")->orWhere('username', 'like', "%$q%");
            });
        }
        $students = $studentQuery->with(['classRoom', 'section'])->orderBy('name')->limit(500)->get();

        $parents = collect();
        if ($request->boolean('include_parents')) {
            $parentIds = \DB::table('parent_student')->whereIn('student_id', $students->pluck('id'))->pluck('parent_id')->unique();
            $parents = User::query()->whereIn('id', $parentIds)->orderBy('name')->get();
        }

        return $students->map(fn ($u) => $this->cardFor($u, 'student'))
            ->concat($parents->map(fn ($u) => $this->cardFor($u, 'parent')));
    }

    private function resolveStaff(Request $request, ?int $schoolId)
    {
        $staff = User::query()
            ->whereHas('roles', function ($r) {
                $r->whereIn('slug', ['teacher', 'school-admin']);
            })
            ->whereDoesntHave('roles', fn ($r) => $r->whereIn('slug', ['student', 'parent']))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId));

        if ($q = $request->string('q')->toString()) {
            $staff->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")->orWhere('username', 'like', "%$q%");
            });
        }
        if ($jobId = $request->integer('job_title_id')) {
            $staff->where('job_title_id', $jobId);
        }

        return $staff->with('jobTitle')->orderBy('name')->limit(500)->get()
            ->map(fn ($u) => $this->cardFor($u, $u->isTeacher() ? 'teacher' : 'admin'));
    }

    private function cardFor(User $u, string $kind): array
    {
        $plain = '—';
        if ($u->plain_password_for_card) {
            try {
                $plain = decrypt($u->plain_password_for_card);
            } catch (\Throwable $e) {
                $plain = '—';
            }
        }
        return [
            'name' => $u->name,
            'username' => $u->username,
            'password' => $plain,
            'kind' => $kind,
            'grade' => optional($u->section)->name,
            'class' => optional($u->classRoom)->name,
            'job_title' => optional($u->jobTitle)->localized_name,
        ];
    }
}
