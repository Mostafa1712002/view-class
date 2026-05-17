<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WeeklyPlanNoteTemplate;
use Illuminate\Http\Request;

/**
 * Card 66 — الخطة الأسبوعية / الملاحظات الجاهزة.
 *
 * Tenant scoping: super-admin sees everything; school-admin sees only their
 * own templates (school_id matches their school_id, or null = global).
 */
class WeeklyPlanNoteTemplateController extends Controller
{
    protected function baseQuery()
    {
        $user = auth()->user();
        $q = WeeklyPlanNoteTemplate::query()->with('creator')->latest();

        if (!$user->isSuperAdmin()) {
            $schoolId = $user->school_id;
            $q->where(function ($w) use ($schoolId) {
                $w->whereNull('school_id')->orWhere('school_id', $schoolId);
            });
        }
        return $q;
    }

    public function index(Request $request)
    {
        $query = $this->baseQuery();
        if ($request->filled('q')) {
            $term = trim($request->get('q'));
            $query->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                  ->orWhere('body', 'like', "%{$term}%");
            });
        }
        $templates = $query->paginate(20)->withQueryString();
        return view('admin.weekly-plan-notes.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:200',
            'body' => 'required|string|max:2000',
        ], [
            'body.required' => __('weekly_plan.note_body_required'),
        ]);

        $user = auth()->user();
        WeeklyPlanNoteTemplate::create([
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'school_id' => $user->isSuperAdmin() ? null : $user->school_id,
            'created_by' => $user->id,
        ]);

        return redirect()->route('manage.weekly-plan-notes.index')
            ->with('success', __('weekly_plan.note_created'));
    }

    public function update(Request $request, $id)
    {
        $template = $this->baseQuery()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'nullable|string|max:200',
            'body' => 'required|string|max:2000',
        ], [
            'body.required' => __('weekly_plan.note_body_required'),
        ]);

        $template->update($validated);
        return redirect()->route('manage.weekly-plan-notes.index')
            ->with('success', __('weekly_plan.note_updated'));
    }

    public function destroy($id)
    {
        $template = $this->baseQuery()->findOrFail($id);
        $template->delete();
        return redirect()->route('manage.weekly-plan-notes.index')
            ->with('success', __('weekly_plan.note_deleted'));
    }
}
