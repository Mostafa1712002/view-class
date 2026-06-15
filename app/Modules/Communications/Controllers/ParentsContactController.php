<?php

namespace App\Modules\Communications\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communications\Repositories\Contracts\ParentCrmRepository;
use App\Modules\Communications\Repositories\Contracts\ParentsContactRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Parents-as-contact (Sprint 9, Trello #242).
 *
 * A communications-side view of parents: lists each parent with their contact
 * data and recorded interaction counts (internal mail, WhatsApp, in-app
 * notifications), and exposes a per-parent communication log + their linked
 * children. Distinct from the CRUD under app/Modules/Users (parents-as-users).
 *
 * Scope: school_id is resolved via HasSchoolScope::activeSchoolId() which
 * returns null for a super-admin with no active school selected — the
 * repository skips the school filter when null (all schools, no 500).
 *
 * Gating: the routes are guarded by the `permission:parents_contact.view`
 * middleware; mutating/contact actions are gated on `parents_contact.manage`
 * and re-checked in the backend (canDo) per CLAUDE.md.
 */
class ParentsContactController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly ParentsContactRepository $parents,
        private readonly ParentCrmRepository $crm,
    ) {
    }

    public function index(Request $request): View
    {
        $search = $request->string('q')->toString() ?: null;

        $parents = $this->parents->paginate(
            $this->scopedSchoolId(),
            $search,
            $request->integer('per_page') ?: 25,
        );

        return view('admin.communications.parents_contact.index', [
            'parents' => $parents,
            'q' => $search,
            'canManage' => (bool) auth()->user()?->canDo('parents_contact.manage'),
        ]);
    }

    public function show(int $id): View
    {
        $schoolId = $this->scopedSchoolId();
        $parent = $this->parents->findScoped($id, $schoolId);
        abort_if($parent === null, 404);

        $logs = $this->parents->interactionLogs($parent);
        // CRM rows belong to the parent regardless of the viewer's active
        // school; pass null when super-admin sees-all, otherwise the active
        // school (the parent already passed the scoped lookup above).
        $crmScope = $schoolId;

        return view('admin.communications.parents_contact.show', [
            'parent' => $parent,
            'children' => $parent->children,
            'logs' => $logs,
            'complaints' => $this->crm->complaints($id, $crmScope),
            'visits' => $this->crm->visits($id, $crmScope),
            'calls' => $this->crm->calls($id, $crmScope),
            'timeline' => $this->crm->timeline($id, $crmScope, $logs),
            'canManage' => (bool) auth()->user()?->canDo('parents_contact.manage'),
        ]);
    }

    /**
     * Export the current (school-scoped, filtered) parents-as-contact list to
     * CSV (UTF-8 BOM so Excel reads Arabic). Read-only; gated by .view.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = $request->string('q')->toString() ?: null;
        $parents = $this->parents->paginate($this->scopedSchoolId(), $search, 100000);

        $rows = [[
            'اسم ولي الأمر', 'الجنسية', 'رقم الجوال',
            'عدد الأبناء', 'عدد الشكاوى', 'عدد الزيارات', 'عدد الاتصالات',
            'عدد رسائل البريد', 'عدد رسائل واتساب', 'عدد الإشعارات',
        ]];
        foreach ($parents as $p) {
            $rows[] = [
                $p->name, $p->nationality, $p->phone,
                $p->children_count ?? 0,
                $p->complaint_count ?? 0, $p->visit_count ?? 0, $p->call_count ?? 0,
                $p->mail_count ?? 0,
                $p->whatsapp_count ?? 0, $p->notification_count ?? 0,
            ];
        }

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'parents-contact-'.date('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
