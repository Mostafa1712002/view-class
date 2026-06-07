<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class UserCardController extends Controller
{
    use HasSchoolScope;

    private const STAFF_ROLES   = ['teacher', 'school-admin', 'super-admin'];
    private const STUDENT_ROLES = ['student', 'parent'];
    private const PER_PAGE      = 100;

    public function index(Request $request): View
    {
        $tab = $request->string('tab', 'students')->toString();
        if (!in_array($tab, ['students', 'staff'], true)) {
            $tab = 'students';
        }

        $schoolId  = $this->resolveSchoolFilter($request);
        $sections  = $this->sectionsForFilter($schoolId);
        $classes   = $this->classesForFilter($schoolId);
        $schools   = auth()->user() && auth()->user()->isSuperAdmin()
            ? School::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $users = $this->buildUserQuery($request, $tab, $schoolId)
            ->orderBy('users.name')
            ->limit(self::PER_PAGE)
            ->get()
            ->map(fn (User $u) => $this->describeUser($u));

        $totalStudents = $this->buildUserQuery($request, 'students', $schoolId, true)->count('users.id');
        $totalStaff    = $this->buildUserQuery($request, 'staff',    $schoolId, true)->count('users.id');

        return view('admin.users.cards.index', [
            'tab'             => $tab,
            'users'           => $users,
            'sections'        => $sections,
            'classes'         => $classes,
            'schools'         => $schools,
            'totalStudents'   => $totalStudents,
            'totalStaff'      => $totalStaff,
            'filters'         => [
                'q'             => $request->string('q')->toString(),
                'school_id'     => $request->integer('school_id') ?: null,
                'section_id'    => $request->integer('section_id') ?: null,
                'class_room_id' => $request->integer('class_room_id') ?: null,
                'role'          => $request->string('role')->toString(),
                'include_parents' => $request->boolean('include_parents'),
            ],
            'showingCount'    => $users->count(),
            'perPage'         => self::PER_PAGE,
            'flashPassword'   => session('user_cards.new_password'),
            'flashUserName'   => session('user_cards.new_password_for'),
        ]);
    }

    public function generate(Request $request): Response
    {
        $tab = $request->input('tab', 'students');
        if (!in_array($tab, ['students', 'staff'], true)) {
            $tab = 'students';
        }
        $schoolId = $this->resolveSchoolFilter($request);

        $selected = $request->input('user_ids', []);
        $selected = is_array($selected) ? array_filter(array_map('intval', $selected)) : [];

        $query = $this->buildUserQuery($request, $tab, $schoolId, true);
        if (!empty($selected)) {
            $query->whereIn('users.id', $selected);
        }
        $users = $query->orderBy('users.name')->limit(2000)->get();

        if ($users->isEmpty()) {
            return redirect()
                ->route('admin.users.cards.index', $request->only(['tab', 'q', 'section_id', 'class_room_id', 'role', 'school_id', 'include_parents']))
                ->with('error', __('user_cards.flash_no_users'));
        }

        $platform = config('app.name', 'الأول');
        $url      = config('app.url', request()->getSchemeAndHttpHost());

        $cards = $users->map(fn (User $u) => $this->cardFor($u))->values();

        $html = view('admin.users.cards.pdf', [
            'cards'    => $cards,
            'platform' => $platform,
            'url'      => $url,
            'tab'      => $tab,
        ])->render();

        // mPDF (not dompdf) so Arabic names/labels are shaped & rendered RTL correctly —
        // dompdf has no Arabic glyph joining or bidi, which garbled the cards (card #162).
        $isRtl = app()->getLocale() === 'ar';
        $tmp   = storage_path('app/mpdf');
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode'             => 'utf-8',
            'format'           => 'A4',
            'orientation'      => 'P',
            'default_font'     => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'tempDir'          => $tmp,
            'margin_top'       => 10,
            'margin_bottom'    => 10,
            'margin_left'      => 10,
            'margin_right'     => 10,
        ]);
        $mpdf->SetDirectionality($isRtl ? 'rtl' : 'ltr');
        $mpdf->WriteHTML($html);

        $filename = 'user-cards-'.$tab.'-'.now()->format('Ymd-His').'.pdf';

        return response(
            $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    public function regenerate(Request $request, int $id): RedirectResponse
    {
        /** @var User|null $auth */
        $auth = auth()->user();
        $user = User::query()->findOrFail($id);

        // Multi-tenant guard — non-super-admin cannot touch users outside their school
        if ($auth && !$auth->isSuperAdmin() && (int) $user->school_id !== (int) $auth->school_id) {
            abort(403);
        }
        // Never reset own password from this page
        if ($auth && (int) $auth->id === (int) $user->id) {
            return back()->with('error', __('user_cards.pwd_regen_self_block'));
        }

        try {
            $plain = $this->generateReadablePassword();
            $user->password = Hash::make($plain);
            $user->plain_password_for_card = encrypt($plain);
            // Bust API tokens / remember tokens so old logins die
            if (\Schema::hasColumn('users', 'remember_token')) {
                $user->remember_token = Str::random(60);
            }
            $user->save();

            Log::info('user_cards.password_regenerated', [
                'admin_id'   => optional($auth)->id,
                'user_id'    => $user->id,
                'username'   => $user->username,
            ]);

            return back()
                ->with('status', __('user_cards.pwd_regen_success', ['password' => $plain]))
                ->with('user_cards.new_password', $plain)
                ->with('user_cards.new_password_for', $user->name);
        } catch (\Throwable $e) {
            Log::warning('user_cards.password_regen_failed', [
                'user_id' => $id,
                'error'   => $e->getMessage(),
            ]);
            return back()->with('error', __('user_cards.pwd_regen_failed'));
        }
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function buildUserQuery(Request $request, string $tab, ?int $schoolId, bool $forCount = false): Builder
    {
        $roleSlugs = $tab === 'staff' ? self::STAFF_ROLES : self::STUDENT_ROLES;

        $role = $request->string('role')->toString();
        if ($role && in_array($role, $roleSlugs, true)) {
            $roleSlugs = [$role];
        }

        $q = User::query()
            ->whereHas('roles', function ($r) use ($roleSlugs) {
                $r->whereIn('slug', $roleSlugs);
            });

        // Exclude super-admin from "staff" listing unless explicitly requested
        if ($tab === 'staff' && $role !== 'super-admin') {
            $q->whereDoesntHave('roles', fn ($r) => $r->where('slug', 'super-admin'));
        }

        if ($schoolId !== null) {
            $q->where('users.school_id', $schoolId);
        }

        if ($search = trim($request->string('q')->toString())) {
            $q->where(function ($w) use ($search) {
                $w->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.username', 'like', "%{$search}%");
                if (Str::contains($search, '@')) {
                    $w->orWhere('users.email', 'like', "%{$search}%");
                }
            });
        }

        if ($tab === 'students') {
            if ($cid = $request->integer('class_room_id')) {
                $q->where('users.class_room_id', $cid);
            } elseif ($sid = $request->integer('section_id')) {
                $q->where('users.section_id', $sid);
            }
        }

        if (!$forCount) {
            $q->with(['roles:id,name,slug', 'classRoom:id,name,section_id', 'section:id,name', 'school:id,name']);
            if (\Schema::hasColumn('users', 'job_title_id')) {
                $q->with('jobTitle:id,name_ar,name_en');
            }
        }

        return $q;
    }

    private function resolveSchoolFilter(Request $request): ?int
    {
        $auth = auth()->user();
        if (!$auth) {
            return null;
        }
        if ($auth->isSuperAdmin()) {
            $sid = $request->integer('school_id');
            if ($sid) {
                return $sid;
            }
            return session('admin.scope.school_id') ?: null;
        }
        return (int) $auth->school_id ?: null;
    }

    private function sectionsForFilter(?int $schoolId)
    {
        $q = Section::query();
        if ($schoolId) {
            $q->where('school_id', $schoolId);
        }
        return $q->orderBy('name')->get(['id', 'name', 'school_id']);
    }

    private function classesForFilter(?int $schoolId)
    {
        $q = ClassRoom::query();
        if ($schoolId) {
            // class -> section -> school
            $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId));
        }
        return $q->orderBy('name')->get(['id', 'name', 'section_id']);
    }

    private function describeUser(User $u): array
    {
        $primaryRole = optional($u->roles->first())->slug ?? 'student';
        $kind = match (true) {
            in_array($primaryRole, ['school-admin', 'super-admin'], true) => 'admin',
            $primaryRole === 'teacher' => 'teacher',
            $primaryRole === 'parent'  => 'parent',
            default => 'student',
        };

        $passwordReady = (bool) $u->plain_password_for_card;
        $jobTitle = null;
        if (\Schema::hasColumn('users', 'job_title_id') && $u->relationLoaded('jobTitle') && $u->jobTitle) {
            $jobTitle = $u->jobTitle->localized_name ?? ($u->jobTitle->name_ar ?? $u->jobTitle->name_en);
        }

        return [
            'id'             => $u->id,
            'name'           => $u->name,
            'username'       => $u->username,
            'kind'           => $kind,
            'school_name'    => optional($u->school)->name,
            'grade'          => optional($u->section)->name,
            'class'          => optional($u->classRoom)->name,
            'job_title'      => $jobTitle,
            'password_ready' => $passwordReady,
        ];
    }

    private function cardFor(User $u): array
    {
        $primaryRole = optional($u->roles->first())->slug ?? 'student';
        $kind = match (true) {
            in_array($primaryRole, ['school-admin', 'super-admin'], true) => 'admin',
            $primaryRole === 'teacher' => 'teacher',
            $primaryRole === 'parent'  => 'parent',
            default => 'student',
        };

        $plain = null;
        if ($u->plain_password_for_card) {
            try {
                $plain = decrypt($u->plain_password_for_card);
            } catch (\Throwable $e) {
                $plain = null;
            }
        }

        $jobTitle = null;
        if (\Schema::hasColumn('users', 'job_title_id') && $u->jobTitle) {
            $jobTitle = $u->jobTitle->localized_name ?? ($u->jobTitle->name_ar ?? $u->jobTitle->name_en);
        }

        return [
            'name'     => $u->name,
            'username' => $u->username,
            'password' => $plain,
            'kind'     => $kind,
            'school'   => optional($u->school)->name,
            'grade'    => optional($u->section)->name,
            'class'    => optional($u->classRoom)->name,
            'job_title'=> $jobTitle,
        ];
    }

    private function generateReadablePassword(int $length = 8): string
    {
        // human-readable password: avoid look-alikes (0/O, 1/l/I)
        $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $out = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
