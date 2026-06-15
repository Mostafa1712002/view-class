<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobTitle;
use App\Models\Permission;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobTitlePermissionsController extends Controller
{
    use HasSchoolScope;

    /**
     * Multi-tenant guard mirroring JobTitleController: a non-super-admin may
     * only manage job titles belonging to their own school; global (school_id
     * null) titles are super-admin only.
     */
    private function assertCanManage(JobTitle $jobTitle): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            return;
        }
        abort_if($jobTitle->school_id === null, 403);
        abort_unless((int) $jobTitle->school_id === (int) $this->activeSchoolId(), 403);
    }

    /**
     * Module definitions for the permission matrix UI.
     * Each entry: 'group_slug' => ['label' => '...', 'actions' => [...action_slugs]]
     */
    private const MODULES = [
        'users'           => ['label' => 'المستخدمون',         'actions' => ['view','create','edit','delete','export','import','print','send_notifications','manage_permissions']],
        'students'        => ['label' => 'الطلاب',             'actions' => ['view','create','edit','delete','archive','export','import','print','view_details','send_notifications']],
        'parents'         => ['label' => 'أولياء الأمور',      'actions' => ['view','create','edit','delete','export','send_notifications','send_whatsapp']],
        'teachers'        => ['label' => 'المعلمون',           'actions' => ['view','create','edit','delete','export','import','print','send_notifications']],
        'schools'         => ['label' => 'المدارس',            'actions' => ['view','create','edit','delete']],
        'subjects'        => ['label' => 'المواد',             'actions' => ['view','create','edit','delete']],
        'question_banks'  => ['label' => 'بنك الأسئلة',       'actions' => ['view','create','edit','delete','import','export']],
        'exams'           => ['label' => 'الاختبارات',         'actions' => ['view','create','edit','delete']],
        'assignments'     => ['label' => 'الواجبات',           'actions' => ['view','create','edit','delete','approve']],
        'books'           => ['label' => 'الكتب',              'actions' => ['view','create','edit','delete','print']],
        'libraries'       => ['label' => 'المكتبات',           'actions' => ['view','create','edit','delete']],
        'evaluations'     => ['label' => 'التقييمات',          'actions' => ['view','create','edit','delete','approve','reject','export']],
        'job_performance' => ['label' => 'تقييم الأداء',       'actions' => ['view','create','edit','delete','export']],
        'attendance'      => ['label' => 'الغياب',             'actions' => ['view','create','edit','delete','export']],
        'behavior'        => ['label' => 'السلوك',             'actions' => ['view','create','edit','delete','export']],
        'appointments'    => ['label' => 'المواعيد',           'actions' => ['view','create','edit','delete','approve']],
        'mail'            => ['label' => 'البريد الداخلي',     'actions' => ['view','create','delete','send_notifications']],
        'support'         => ['label' => 'الدعم الفني',        'actions' => ['view','create','edit','delete']],
        'reports'         => ['label' => 'التقارير',           'actions' => ['view','export','print']],
        'settings'        => ['label' => 'الإعدادات',          'actions' => ['view','edit','manage_permissions']],
        'pdf_export'      => ['label' => 'PDF / التصدير',     'actions' => ['view','print']],
        'noor'            => ['label' => 'نظام نور',           'actions' => ['view','import','export']],
        'whatsapp'        => ['label' => 'واتساب',             'actions' => ['view','send_whatsapp','send']],
        'job_titles'      => ['label' => 'المسميات الوظيفية', 'actions' => ['view','create','edit','delete','manage_permissions']],

        // ── عمليات التواصل (Sprint 9) — see .kiro/specs/trello-sprint9-comms-foundation ──
        'announcements'   => ['label' => 'الإعلانات',              'actions' => ['view','create','edit','delete','publish','read_log']],
        'calendar'        => ['label' => 'التقويم المدرسي',       'actions' => ['view','create_event','edit_event','delete_event','print']],
        'virtual_classes' => ['label' => 'الفصول الافتراضية',     'actions' => ['view','create','start','join','view_attendance','recalc_attendance','clear_cache']],
        'discussion'      => ['label' => 'غرف النقاش',            'actions' => ['view','create','edit','delete','toggle_comments']],
        'mailbox'         => ['label' => 'صندوق البريد الداخلي',  'actions' => ['view','send','draft','delete','archive']],
        'sms'             => ['label' => 'الرسائل القصيرة SMS',   'actions' => ['view','send']],
        'messages'        => ['label' => 'خدمات الرسائل',         'actions' => ['send_excel','templates','reports','sender_name','credit']],
        'parents_contact' => ['label' => 'أولياء الأمور كجهة تواصل', 'actions' => ['view','manage']],
    ];

    private const SCOPE_LABELS = [
        'all'          => 'كل النظام',
        'company'      => 'الشركة',
        'group'        => 'المجمع',
        'school'       => 'المدرسة',
        'stage'        => 'المرحلة',
        'class'        => 'الصف',
        'section'      => 'الفصل',
        'subject'      => 'المادة',
        'own_students' => 'الطلاب المرتبطون فقط',
        'own_subjects' => 'المواد المسندة فقط',
        'own'          => 'بياناته فقط',
    ];

    public function index(JobTitle $jobTitle): View
    {
        $this->assertCanManage($jobTitle);
        $jobTitle->load('permissions');

        // Build a lookup: 'group.action' => permission model (with pivot)
        $configured = $jobTitle->permissions->keyBy(fn ($p) => $p->slug);

        // Load all permission models keyed by slug for fast lookup
        $allSlugs = [];
        foreach (self::MODULES as $group => $def) {
            foreach ($def['actions'] as $action) {
                $allSlugs[] = "{$group}.{$action}";
            }
        }
        $permModels = Permission::whereIn('slug', $allSlugs)->get()->keyBy('slug');

        $otherJobTitles = JobTitle::where('id', '!=', $jobTitle->id)
            ->forSchool($this->activeSchoolId())
            ->orderBy('name_ar')
            ->get();

        return view('admin.users.job_titles.permissions', [
            'jobTitle'       => $jobTitle,
            'modules'        => self::MODULES,
            'scopeLabels'    => self::SCOPE_LABELS,
            'configured'     => $configured,
            'permModels'     => $permModels,
            'otherJobTitles' => $otherJobTitles,
        ]);
    }

    public function update(Request $request, JobTitle $jobTitle): RedirectResponse
    {
        $this->assertCanManage($jobTitle);

        // A non-super-admin may only grant school-level or narrower data scopes;
        // the system-wide scopes (all/company/group) are super-admin only,
        // otherwise a school-admin could escalate a job title beyond their school.
        $wideScopes   = ['all', 'company', 'group'];
        $narrowScopes = ['school', 'stage', 'class', 'section', 'subject', 'own_students', 'own_subjects', 'own'];
        $allowedScopes = auth()->user()?->isSuperAdmin()
            ? array_merge($wideScopes, $narrowScopes)
            : $narrowScopes;

        $request->validate([
            'permissions'   => 'nullable|array',
            'permissions.*' => 'string',
            'scopes'        => 'nullable|array',
            'scopes.*'      => ['string', \Illuminate\Validation\Rule::in($allowedScopes)],
        ]);

        $selectedSlugs = $request->input('permissions', []);
        $scopes        = $request->input('scopes', []);

        // Resolve permission IDs from slugs
        $permMap = Permission::whereIn('slug', $selectedSlugs)->get()->keyBy('slug');

        $syncData = [];
        foreach ($selectedSlugs as $slug) {
            $perm = $permMap->get($slug);
            if ($perm) {
                $scope = $scopes[$slug] ?? 'school';
                if (! in_array($scope, $allowedScopes, true)) {
                    $scope = 'school'; // clamp anything not allowed for this actor
                }
                $syncData[$perm->id] = [
                    'scope'      => $scope,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
        }

        // sync() replaces all existing rows — detaches unselected, attaches new
        $jobTitle->permissions()->sync($syncData);

        return redirect()
            ->route('admin.users.job-titles.permissions.index', $jobTitle)
            ->with('status', 'تم حفظ الصلاحيات بنجاح');
    }

    public function copy(Request $request, JobTitle $jobTitle): RedirectResponse
    {
        $this->assertCanManage($jobTitle);

        $request->validate([
            'copy_from' => 'required|integer|exists:job_titles,id',
        ]);

        // Scope the source title to what this user may read (own school + global);
        // a non-super-admin cannot copy from another school's job title.
        $source = JobTitle::where('id', $request->integer('copy_from'))
            ->forSchool($this->activeSchoolId())
            ->firstOrFail();
        $source->load('permissions');

        $syncData = [];
        foreach ($source->permissions as $perm) {
            $syncData[$perm->id] = [
                'scope'      => $perm->pivot->scope,
                'updated_at' => now(),
                'created_at' => now(),
            ];
        }

        $jobTitle->permissions()->sync($syncData);

        return redirect()
            ->route('admin.users.job-titles.permissions.index', $jobTitle)
            ->with('status', "تم نسخ الصلاحيات من «{$source->name_ar}» بنجاح");
    }
}
