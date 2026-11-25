<?php

namespace App\Modules\Portfolio\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\PortfolioEntry;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Modules\Portfolio\Controllers\Concerns\ManagesPortfolioEntries;
use App\Modules\Portfolio\Repositories\Contracts\PortfolioEntryRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin "إدارة ملف أنجاز" — two tabs (teacher/student) listing school-scoped
 * users, each opening into an achievement-portfolio page mixing auto-derived
 * sections (CV, certificates, grades, class visits) with editable entries
 * stored in portfolio_entries. See .kiro/specs/achievement-portfolio.
 */
class AdminPortfolioController extends Controller
{
    use HasSchoolScope;
    use ManagesPortfolioEntries;

    public function __construct(private PortfolioEntryRepository $entries) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'student' ? 'student' : 'teacher';
        $schoolId = $this->listSchoolId();

        $q         = trim((string) $request->query('q', ''));
        $subjectId = (int) $request->query('subject_id');
        $sectionId = (int) $request->query('section_id');
        $classId   = (int) $request->query('class_id');

        $users = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', $tab))
            ->when($schoolId, fn ($r) => $r->where('school_id', $schoolId))
            ->when($q !== '', fn ($r) => $r->where('name', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->when($tab === 'teacher' && $subjectId, fn ($r) => $r->whereHas('subjects', fn ($s) => $s->where('subjects.id', $subjectId)))
            ->when($tab === 'student' && $classId, fn ($r) => $r->where(fn ($w) => $w->where('class_room_id', $classId)
                ->orWhereHas('enrolledClasses', fn ($c) => $c->where('classes.id', $classId))))
            ->when($tab === 'student' && $sectionId, fn ($r) => $r->where(fn ($w) => $w->where('section_id', $sectionId)
                ->orWhereHas('classRoom', fn ($c) => $c->where('section_id', $sectionId))
                ->orWhereHas('enrolledClasses', fn ($c) => $c->where('classes.section_id', $sectionId))))
            ->with($tab === 'teacher' ? ['subjects:subjects.id,name'] : ['classRoom.section', 'section', 'enrolledClasses.section'])
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $subjects = $tab === 'teacher'
            ? Subject::query()->when($schoolId, fn ($r) => $r->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name'])
            : collect();
        $sections = $tab === 'student'
            ? Section::query()->when($schoolId, fn ($r) => $r->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name'])
            : collect();
        $classes = $tab === 'student'
            ? ClassRoom::whereIn('section_id', $sections->pluck('id'))->orderBy('name')->get(['id', 'name', 'division', 'section_id'])
            : collect();

        return view('admin.portfolio.index', compact('tab', 'users', 'subjects', 'sections', 'classes'));
    }

    /**
     * Every action that reads or mutates a user's portfolio must confirm the
     * target is a teacher/student AND lives in the acting admin's tenant, so a
     * school-admin cannot reach a user in another school (cross-tenant IDOR).
     */
    private function assertUserInScope(User $user): void
    {
        abort_unless($user->isTeacher() || $user->isStudent(), 404);

        $schoolId = $this->listSchoolId();
        abort_if($schoolId && $user->school_id !== $schoolId, 403);
    }

    public function showUser(User $user): View
    {
        $this->assertUserInScope($user);

        $isTeacher = $user->isTeacher();
        $sections  = $isTeacher ? PortfolioEntry::SECTIONS_TEACHER : PortfolioEntry::SECTIONS_STUDENT;
        $entryKeys = $isTeacher ? PortfolioEntry::SECTIONS_TEACHER_ENTRY : PortfolioEntry::SECTIONS_STUDENT_ENTRY;
        $entries   = $this->entries->groupedForUser($user->id);
        $auto      = $isTeacher ? $this->teacherAutoData($user) : $this->studentAutoData($user);

        return view('admin.portfolio.show', compact('user', 'sections', 'entryKeys', 'entries', 'auto'));
    }

    public function storeEntry(Request $request, User $user): RedirectResponse
    {
        $this->assertUserInScope($user);
        $this->saveEntry($request, $user);

        return back()->with('success', 'تم إضافة العنصر بنجاح');
    }

    public function updateEntry(Request $request, User $user, PortfolioEntry $entry): RedirectResponse
    {
        $this->assertUserInScope($user);
        abort_unless($entry->user_id === $user->id, 404);
        $this->saveEntry($request, $user, $entry);

        return back()->with('success', 'تم تحديث العنصر بنجاح');
    }

    public function destroyEntry(User $user, PortfolioEntry $entry): RedirectResponse
    {
        $this->assertUserInScope($user);
        abort_unless($entry->user_id === $user->id, 404);
        $this->deleteEntry($entry);

        return back()->with('success', 'تم حذف العنصر بنجاح');
    }
}
