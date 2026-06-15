<?php

namespace App\Modules\Communications\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\Communications\Http\Requests\StoreCallRequest;
use App\Modules\Communications\Http\Requests\StoreComplaintRequest;
use App\Modules\Communications\Http\Requests\StoreVisitRequest;
use App\Modules\Communications\Repositories\Contracts\ParentCrmRepository;
use App\Modules\Communications\Repositories\Contracts\ParentsContactRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;

/**
 * Parent CRM write layer (Sprint 10, Trello #269).
 *
 * Adds complaints / school-visits / scheduled-calls onto the existing
 * parents-as-contact detail page. Reads stay in ParentsContactController; this
 * controller only handles the three create flows. Gating: routes carry
 * `permission:parents_contact.manage`, and each store re-checks canDo() via the
 * FormRequest::authorize() (backend enforcement, not UI-only). school_id is
 * resolved fail-closed and denormalized onto every row.
 */
class ParentCrmController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly ParentCrmRepository $crm,
        private readonly ParentsContactRepository $parents,
    ) {
    }

    public function storeComplaint(StoreComplaintRequest $request, int $parent): RedirectResponse
    {
        $schoolId = $this->resolveParentSchool($parent);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('parent-crm/complaints', 'local');
        }

        $model = $this->crm->createComplaint([
            'school_id' => $schoolId,
            'parent_id' => $parent,
            'student_id' => $request->integer('student_id') ?: null,
            'code' => $this->crm->nextComplaintCode(),
            'type' => $request->input('type'),
            'complaint_date' => $request->date('complaint_date'),
            'purpose' => $request->input('purpose'),
            'details' => $request->input('details'),
            'action_required' => $request->input('action_required'),
            'actions_taken' => $request->input('actions_taken'),
            'priority' => $request->input('priority'),
            'assigned_to' => $request->integer('assigned_to') ?: null,
            'status' => $request->input('status'),
            'attachment_path' => $path,
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::logCreate($model, 'إضافة شكوى لولي أمر');

        return back()->with('status', 'تمت إضافة الشكوى بنجاح ('.$model->code.').');
    }

    public function storeVisit(StoreVisitRequest $request, int $parent): RedirectResponse
    {
        $schoolId = $this->resolveParentSchool($parent);

        $model = $this->crm->createVisit([
            'school_id' => $schoolId,
            'parent_id' => $parent,
            'student_id' => $request->integer('student_id') ?: null,
            'visit_date' => $request->date('visit_date'),
            'visit_time' => $request->input('visit_time'),
            'reason' => $request->input('reason'),
            'met_staff_id' => $request->integer('met_staff_id') ?: null,
            'summary' => $request->input('summary'),
            'next_action' => $request->input('next_action'),
            'followup_date' => $request->input('followup_date') ?: null,
            'status' => $request->input('status'),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::logCreate($model, 'تسجيل زيارة مدرسة لولي أمر');

        return back()->with('status', 'تم تسجيل الزيارة بنجاح.');
    }

    public function storeCall(StoreCallRequest $request, int $parent): RedirectResponse
    {
        $schoolId = $this->resolveParentSchool($parent);

        $model = $this->crm->createCall([
            'school_id' => $schoolId,
            'parent_id' => $parent,
            'call_date' => $request->date('call_date'),
            'call_time' => $request->input('call_time'),
            'call_type' => $request->input('call_type'),
            'purpose' => $request->input('purpose'),
            'outcome' => $request->input('outcome'),
            'answered' => $request->boolean('answered'),
            'notes' => $request->input('notes'),
            'followup_at' => $request->input('followup_at') ?: null,
            'assigned_to' => $request->integer('assigned_to') ?: null,
            'status' => $request->input('status'),
            'created_by' => $request->user()->id,
        ]);

        ActivityLog::logCreate($model, 'جدولة اتصال بولي أمر');

        return back()->with('status', 'تم تسجيل الاتصال بنجاح.');
    }

    /**
     * Verify the parent exists within the caller's scope and return the
     * school_id to denormalize onto the new row. Fail-closed: a non-super-admin
     * resolving to a null scope is rejected by scopedSchoolId(); a parent
     * outside scope 404s.
     */
    private function resolveParentSchool(int $parentId): ?int
    {
        $scope = $this->scopedSchoolId();
        $parent = $this->parents->findScoped($parentId, $scope);
        abort_if($parent === null, 404);

        // Bind the row to the parent's own school (super-admin null scope picks
        // up the parent's school rather than leaving it null).
        return $scope ?? $parent->school_id;
    }
}
