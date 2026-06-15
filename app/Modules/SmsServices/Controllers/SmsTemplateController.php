<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SmsServices\Models\SmsTemplate;
use App\Modules\SmsServices\Support\SmsSegmentCalculator;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Trello #238 — SMS templates CRUD (school-scoped, fail-closed).
 */
class SmsTemplateController extends Controller
{
    use HasSchoolScope;

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('messages.templates'), 403);
    }

    public function index(Request $request): View
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();
        $q = trim((string) $request->query('q', ''));

        $templates = SmsTemplate::query()
            ->when($schoolId, fn ($qq) => $qq->where('school_id', $schoolId))
            ->when($q !== '', fn ($qq) => $qq->where(fn ($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('body', 'like', "%{$q}%")))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.sms-services.templates.index', compact('templates', 'q'));
    }

    public function create(): View
    {
        $this->gate();
        return view('admin.sms-services.templates.form', [
            'template'  => new SmsTemplate(),
            'variables' => SmsTemplateRenderer::VARIABLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();
        $data = $this->validateData($request, $schoolId, null);

        SmsTemplate::create([
            'school_id'  => $schoolId,
            'title'      => $data['title'],
            'body'       => $data['body'],
            'lang'       => SmsSegmentCalculator::detectLang($data['body']),
            'is_active'  => $request->boolean('is_active', true),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.sms.templates.index')
            ->with('success', __('sms_templates.created'));
    }

    public function edit(int $id): View
    {
        $this->gate();
        $template = $this->find($id);
        return view('admin.sms-services.templates.form', [
            'template'  => $template,
            'variables' => SmsTemplateRenderer::VARIABLES,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();
        $template = $this->find($id);
        $data = $this->validateData($request, $schoolId, $template->id);

        $template->update([
            'title'     => $data['title'],
            'body'      => $data['body'],
            'lang'      => SmsSegmentCalculator::detectLang($data['body']),
            'is_active' => $request->boolean('is_active', $template->is_active),
        ]);

        return redirect()->route('admin.sms.templates.index')
            ->with('success', __('sms_templates.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->gate();
        $this->find($id)->delete();
        return back()->with('success', __('sms_templates.deleted'));
    }

    public function copy(int $id): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();
        $template = $this->find($id);

        $title = $template->title . ' - نسخة';
        // keep the unique-per-school constraint happy
        $i = 1;
        while (SmsTemplate::where('school_id', $schoolId)->where('title', $title)->exists()) {
            $title = $template->title . ' - نسخة ' . (++$i);
        }

        SmsTemplate::create([
            'school_id'  => $schoolId,
            'title'      => $title,
            'body'       => $template->body,
            'lang'       => $template->lang,
            'is_active'  => $template->is_active,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', __('sms_templates.copied'));
    }

    public function toggle(int $id): RedirectResponse
    {
        $this->gate();
        $template = $this->find($id);
        $template->update(['is_active' => ! $template->is_active]);
        return back()->with('success', __('common.updated_successfully'));
    }

    /** AJAX: live segment / char counter for the editor. */
    public function analyze(Request $request): JsonResponse
    {
        $this->gate();
        return response()->json([
            'success' => true,
            'data'    => SmsSegmentCalculator::analyze((string) $request->input('body', '')),
        ]);
    }

    /** "تجربة القالب": render with sample data so the user previews output. */
    public function tryTemplate(int $id): JsonResponse
    {
        $this->gate();
        $template = $this->find($id);

        $sample = [
            'first_name' => 'أحمد', 'last_name' => 'العتيبي', 'full_name' => 'أحمد العتيبي',
            'student_name' => 'أحمد العتيبي', 'school_name' => 'مدرستي', 'date' => now()->format('Y-m-d'),
            'class' => '1/أ', 'grade' => 'الأول', 'mobile' => '0500000000',
        ];
        $rendered = SmsTemplateRenderer::render($template->body, $sample, 'dash');

        return response()->json([
            'success' => true,
            'data'    => ['rendered' => $rendered] + SmsSegmentCalculator::analyze($rendered),
        ]);
    }

    private function validateData(Request $request, ?int $schoolId, ?int $ignoreId): array
    {
        return $request->validate([
            'title' => [
                'required', 'string', 'min:3', 'max:150',
                Rule::unique('sms_templates', 'title')
                    ->where(fn ($q) => $q->where('school_id', $schoolId)->whereNull('deleted_at'))
                    ->ignore($ignoreId),
            ],
            'body'      => ['required', 'string', 'max:1600'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function find(int $id): SmsTemplate
    {
        $schoolId = $this->scopedSchoolId();
        return SmsTemplate::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->findOrFail($id);
    }
}
