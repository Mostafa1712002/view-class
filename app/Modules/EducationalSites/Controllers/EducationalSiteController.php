<?php

namespace App\Modules\EducationalSites\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\EducationalSites\Http\Requests\EducationalSiteRequest;
use App\Modules\EducationalSites\Models\EducationalSite;
use App\Modules\EducationalSites\Repositories\Contracts\EducationalSiteRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * #270 — Educational websites (NET-NEW).
 *
 * Two surfaces:
 *   - DISPLAY  : a modern card grid of active sites, visible to any authenticated
 *                user (students/parents included). Disabled sites never appear.
 *   - MANAGEMENT: CRUD + toggle + reorder, restricted to super/school admins
 *                 with the educational_sites.* write permissions.
 *
 * Scope (HasSchoolScope::scopedSchoolId, fail-closed): super-admin → null
 * (sees all + creates globals); school-admin → own school.
 */
class EducationalSiteController extends Controller
{
    use HasSchoolScope;

    public function __construct(private EducationalSiteRepository $sites) {}

    /** Public-facing card grid (any authenticated user). */
    public function display(): View
    {
        // Read-only: use activeSchoolId() (never 403s a student). Resolves to the
        // viewer's school (super-admin → navbar-selected school), and the
        // repository always layers in global sites (school_id IS NULL) on top.
        $sites = $this->sites->listVisible($this->activeSchoolId());

        return view('admin.educational-sites.display', compact('sites'));
    }

    /** Management table. */
    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();
        $sites = $this->sites->listForManagement($schoolId, $this->canManageGlobals(), [
            'name'      => $request->get('name'),
            'is_active' => $request->get('status'),
            'category'  => $request->get('category'),
        ]);

        return view('admin.educational-sites.index', compact('sites'));
    }

    public function create(): View
    {
        $this->scopedSchoolId(); // fail-closed guard for non-super-admin null scope.
        $site = new EducationalSite(['is_active' => true, 'opens_new_tab' => true, 'sort_order' => 0]);

        return view('admin.educational-sites.form', compact('site'));
    }

    public function store(EducationalSiteRequest $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $this->payload($request, $schoolId);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('educational-sites/logos', 'public');
        }

        $site = $this->sites->create($data);
        ActivityLog::logCreate($site, "إضافة موقع تعليمي: {$site->display_name}");

        return redirect()->route('admin.educational-sites.index')
            ->with('success', 'تم إضافة الموقع التعليمي بنجاح.');
    }

    public function edit(int $id): View
    {
        $site = $this->mustFind($id);

        return view('admin.educational-sites.form', compact('site'));
    }

    public function update(EducationalSiteRequest $request, int $id): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $site = $this->mustFind($id);
        $old = $site->only(['name_ar', 'name_en', 'url', 'category', 'is_active', 'sort_order']);

        $data = $this->payload($request, $schoolId, $site);

        if ($request->hasFile('logo')) {
            if ($site->logo_path) {
                Storage::disk('public')->delete($site->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('educational-sites/logos', 'public');
        }

        $site = $this->sites->update($site, $data);
        ActivityLog::logUpdate($site, "تعديل موقع تعليمي: {$site->display_name}", $old);

        return redirect()->route('admin.educational-sites.index')
            ->with('success', 'تم تحديث الموقع التعليمي بنجاح.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $site = $this->mustFind($id);
        $name = $site->display_name;
        $this->sites->delete($site);
        ActivityLog::logDelete($site, "حذف موقع تعليمي: {$name}");

        return redirect()->route('admin.educational-sites.index')
            ->with('success', 'تم حذف الموقع التعليمي.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $site = $this->mustFind($id);
        $site = $this->sites->update($site, ['is_active' => ! $site->is_active]);
        $state = $site->is_active ? 'تفعيل' : 'تعطيل';
        ActivityLog::log('educational_sites.toggle_active', "{$state} موقع تعليمي: {$site->display_name}", $site);

        return back()->with('success', "تم {$state} الموقع.");
    }

    public function reorder(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $request->validate([
            'order'   => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'min:0', 'max:65535'],
        ]);

        // [id => sort_order] keyed map; the repository ignores any id out of scope.
        $this->sites->reorder($request->input('order'), $schoolId, $this->canManageGlobals());
        ActivityLog::log('educational_sites.reorder', 'إعادة ترتيب المواقع التعليمية');

        return back()->with('success', 'تم حفظ الترتيب الجديد.');
    }

    /** Resolve a manageable site within the actor's scope or 404/403. */
    private function mustFind(int $id): EducationalSite
    {
        $schoolId = $this->scopedSchoolId();
        $site = $this->sites->findManageable($id, $schoolId, $this->canManageGlobals());
        abort_if($site === null, 404, 'الموقع غير موجود أو خارج نطاق صلاحيتك.');

        return $site;
    }

    /** Only super-admins may view/manage global (school_id NULL) sites. */
    private function canManageGlobals(): bool
    {
        return (bool) (auth()->user()?->isSuperAdmin() ?? false);
    }

    /**
     * Build the persisted attributes. New rows take the actor's school
     * (null = global for super-admins); existing rows keep their school.
     */
    private function payload(EducationalSiteRequest $request, ?int $schoolId, ?EducationalSite $existing = null): array
    {
        return [
            'school_id'      => $existing?->school_id ?? $schoolId,
            'name_ar'        => $request->input('name_ar'),
            'name_en'        => $request->input('name_en'),
            'description_ar' => $request->input('description_ar'),
            'description_en' => $request->input('description_en'),
            'url'            => $request->input('url'),
            'category'       => $request->input('category'),
            'sort_order'     => (int) $request->input('sort_order', 0),
            'opens_new_tab'  => $request->boolean('opens_new_tab'),
            'is_active'      => $request->boolean('is_active'),
        ];
    }
}
