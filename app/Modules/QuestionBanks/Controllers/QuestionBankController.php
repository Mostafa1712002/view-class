<?php

namespace App\Modules\QuestionBanks\Controllers;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\Subject;
use App\Models\User;
use App\Modules\QuestionBanks\Repositories\Contracts\QuestionBankRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    use HasSchoolScope;

    public function __construct(private QuestionBankRepository $banks) {}

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $filters = $this->extractFilters($request);

        $banks = $this->banks->paginate($schoolId, $filters);
        $stats = $this->banks->stats($schoolId);

        $subjects = $this->subjectsForSchool($schoolId);
        $creators = $this->creatorsForSchool($schoolId);

        $vocab = $this->vocabulary();

        return view('admin.question-banks.index', [
            'banks'        => $banks,
            'stats'        => $stats,
            'filters'      => $filters,
            'subjects'     => $subjects,
            'creators'     => $creators,
            'visibilities' => $vocab['visibilities'],
            'statuses'     => $vocab['statuses'],
            'sources'      => $vocab['sources'],
            'grades'       => $vocab['grades'],
            'categories'   => $vocab['categories'],
        ]);
    }

    public function library(): View
    {
        $banks = $this->banks->library();
        return view('admin.question-banks.library', compact('banks'));
    }

    public function clone(int $id): RedirectResponse
    {
        $template = QuestionBank::query()->where('is_library', true)->findOrFail($id);
        $schoolId = $this->activeSchoolId();
        $copy = $this->banks->clone($template, $schoolId, auth()->id());

        return redirect()
            ->route('admin.question-banks.edit', $copy->id)
            ->with('success', __('sprint4.question_banks.flash.cloned'));
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $bank = new QuestionBank([
            'visibility' => QuestionBank::VISIBILITY_PRIVATE,
            'status' => QuestionBank::STATUS_ACTIVE,
            'source' => QuestionBank::SOURCE_MANUAL,
            'is_ana_qudurat_linkable' => false,
        ]);
        $subjects = $this->subjectsForSchool($schoolId);
        $teachers = $this->teachersForSchool($schoolId);
        $shareSchools = $this->schoolsForSharing();
        $vocab = $this->vocabulary();

        return view('admin.question-banks.create', array_merge([
            'bank'         => $bank,
            'subjects'     => $subjects,
            'teachers'     => $teachers,
            'shareSchools' => $shareSchools,
        ], $vocab));
    }

    public function createBatch(): View
    {
        $schoolId = $this->activeSchoolId();
        $subjects = $this->subjectsForSchool($schoolId);
        $vocab    = $this->vocabulary();

        return view('admin.question-banks.create-batch', array_merge([
            'subjects'     => $subjects,
            'shareSchools' => $this->schoolsForSharing(),
        ], $vocab));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBank($request);

        // Only super-admin or school-admin can create general (public) banks.
        if ($data['visibility'] === 'public' && ! $this->canManageGeneralBanks()) {
            abort(403, __('question_banks.error_general_forbidden'));
        }

        // Auto-derive bank name from the single subject_id when provided.
        $singleSubjectId = $data['subject_id'] ?? null;
        if ($singleSubjectId) {
            $subject = Subject::find($singleSubjectId);
            $derivedNameAr = $subject ? ('بنك أسئلة ' . $subject->name) : ($data['name_ar'] ?? '');
            $derivedNameEn = $subject?->name_en ? ('Question Bank — ' . $subject->name_en) : ($data['name_en'] ?? null);
        } else {
            $derivedNameAr = $data['name_ar'] ?? '';
            $derivedNameEn = $data['name_en'] ?? null;
        }

        $subjectIds  = $singleSubjectId ? [$singleSubjectId] : $request->input('subject_ids', []);
        $memberRoles = $request->input('member_roles', []);
        $schoolIds   = $data['visibility'] === 'public' ? ($data['school_ids'] ?? []) : [];

        $payload = [
            'name_ar'                 => $derivedNameAr,
            'name_en'                 => $derivedNameEn,
            'description'             => $data['description'] ?? null,
            'school_id'               => $this->resolveSchoolForPayload($data),
            'is_library'              => false,
            'visibility'              => $data['visibility'],
            'status'                  => $data['status'],
            'source'                  => $data['source'],
            'grade_level'             => $data['grade_level'] ?? null,
            'category_type'           => $data['category_type'] ?? null,
            'is_ana_qudurat_linkable' => (bool) ($data['is_ana_qudurat_linkable'] ?? false),
            'exportable'              => (bool) ($data['exportable'] ?? true),
            'external_platform'       => $data['source'] === QuestionBank::SOURCE_AL_AWWAL
                                            ? 'al_awwal'
                                            : ($data['external_platform'] ?? null),
            'created_by'              => auth()->id(),
        ];

        $this->banks->create($payload, $subjectIds, $memberRoles, $schoolIds);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('question_banks.flash_created'));
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'visibility'    => ['required', 'in:public,private'],
            'status'        => ['required', 'in:active,inactive,under_review,archived'],
            'source'        => ['required', 'in:manual,al_awwal,library,import'],
            'grade_level'   => ['nullable', 'integer', 'min:1', 'max:12'],
            'term'          => ['nullable', 'string', 'max:100'],
            'subject_ids'   => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'school_ids'    => ['nullable', 'array'],
            'school_ids.*'  => ['integer', 'exists:schools,id'],
            'skip_existing' => ['nullable', 'boolean'],
        ]);

        if ($data['visibility'] === 'public' && ! $this->canManageGeneralBanks()) {
            abort(403, __('question_banks.error_general_forbidden'));
        }

        $schoolId     = $this->resolveSchoolForPayload($data);
        $skipExisting = (bool) ($data['skip_existing'] ?? true);
        $schoolIds    = $data['visibility'] === 'public' ? ($data['school_ids'] ?? []) : [];
        $memberRoles  = [];
        $termValue    = $data['term'] ?? null;

        $created = 0;
        $skipped = 0;

        foreach ($data['subject_ids'] as $subjectId) {
            $subject = Subject::find($subjectId);
            if (! $subject) {
                continue;
            }

            // Duplicate check: same school + subject + grade + term
            $duplicateQuery = QuestionBank::query()
                ->where('school_id', $schoolId)
                ->where('grade_level', $data['grade_level'] ?? null)
                ->whereHas('subjects', fn ($q) => $q->where('subjects.id', $subjectId));

            if ($termValue !== null) {
                $duplicateQuery->whereJsonContains('metadata->term', $termValue);
            }

            if ($duplicateQuery->exists()) {
                if ($skipExisting) {
                    $skipped++;
                    continue;
                }
            }

            $payload = [
                'name_ar'                 => 'بنك أسئلة ' . $subject->name,
                'name_en'                 => $subject->name_en ? 'Question Bank — ' . $subject->name_en : null,
                'school_id'               => $schoolId,
                'is_library'              => false,
                'visibility'              => $data['visibility'],
                'status'                  => $data['status'],
                'source'                  => $data['source'],
                'grade_level'             => $data['grade_level'] ?? null,
                'is_ana_qudurat_linkable' => false,
                'exportable'              => true,
                'external_platform'       => $data['source'] === QuestionBank::SOURCE_AL_AWWAL ? 'al_awwal' : null,
                'metadata'                => $termValue ? ['term' => $termValue] : null,
                'created_by'              => auth()->id(),
            ];

            $this->banks->create($payload, [$subjectId], $memberRoles, $schoolIds);
            $created++;
        }

        $message = $skipped > 0
            ? __('question_banks.batch_created_partial', ['created' => $created, 'skipped' => $skipped])
            : __('question_banks.batch_created_success', ['count' => $created]);

        return redirect()->route('admin.question-banks.index')->with('success', $message);
    }

    public function edit(int $id): View
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);

        $subjects = $this->subjectsForSchool($schoolId);
        $teachers = $this->teachersForSchool($schoolId);
        $shareSchools = $this->schoolsForSharing();
        $vocab = $this->vocabulary();

        return view('admin.question-banks.edit', array_merge([
            'bank' => $bank,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'shareSchools' => $shareSchools,
        ], $vocab));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);

        $data = $this->validateBank($request);

        // Only super-admin or school-admin can update/change a general bank.
        $becomingPublic = $data['visibility'] === 'public';
        if ($becomingPublic && ! $this->canManageGeneralBanks()) {
            abort(403, __('question_banks.error_general_forbidden'));
        }

        $subjectIds = $request->input('subject_ids', []);
        $memberRoles = $request->input('member_roles', []);
        $schoolIds = $becomingPublic ? ($data['school_ids'] ?? []) : [];

        $this->banks->update($bank, [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'] ?? null,
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
            'status' => $data['status'],
            'source' => $data['source'],
            'grade_level' => $data['grade_level'] ?? null,
            'category_type' => $data['category_type'] ?? null,
            'is_ana_qudurat_linkable' => (bool) ($data['is_ana_qudurat_linkable'] ?? false),
            'exportable' => (bool) ($data['exportable'] ?? true),
            'external_platform' => $data['external_platform'] ?? null,
        ], $subjectIds, $memberRoles, $schoolIds);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('question_banks.flash_updated'));
    }

    /**
     * Quick-approve: move a bank from under_review → active.
     */
    public function approve(int $id): RedirectResponse
    {
        abort_unless($this->canManageGeneralBanks(), 403, __('question_banks.error_general_forbidden'));

        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);

        $this->banks->approve($bank);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('question_banks.flash_approved'));
    }

    /**
     * Promote a private bank to general (public / company-wide).
     * Super-admin only.
     */
    public function promote(Request $request, int $id): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);
        abort_if($bank->visibility === QuestionBank::VISIBILITY_PUBLIC, 422);

        $schoolIds = $request->input('school_ids', []);
        $this->banks->promote($bank, $schoolIds);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('question_banks.flash_promoted'));
    }

    /**
     * Copy all approved questions from a general bank into a new private bank
     * for the authenticated user's school.
     */
    public function copyToMySchool(Request $request, int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        abort_if(! $schoolId, 403);

        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);
        abort_if($bank->visibility !== QuestionBank::VISIBILITY_PUBLIC, 422);

        $copy = $this->banks->copyToSchool($bank, $schoolId, auth()->id());

        return redirect()
            ->route('admin.question-banks.edit', $copy->id)
            ->with('success', __('question_banks.flash_copied'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $bank = $this->banks->findScoped($id, $schoolId);
        abort_if(! $bank, 404);

        $this->banks->delete($bank);

        return redirect()
            ->route('admin.question-banks.index')
            ->with('success', __('question_banks.flash_deleted'));
    }

    private function extractFilters(Request $request): array
    {
        $tab = $request->get('tab', 'all');
        // Map tab shortcuts to visibility/status filters (don't override explicit filter values)
        $visibilityFromTab = match ($tab) {
            'general' => 'public',
            'private' => 'private',
            default   => $request->get('visibility'),
        };
        $statusFromTab = ($tab === 'under_review') ? 'under_review' : $request->get('status');

        return [
            'q'          => trim((string) $request->get('q', '')),
            'visibility' => $visibilityFromTab,
            'status'     => $statusFromTab,
            'source'     => $request->get('source'),
            'subject_id' => $request->get('subject_id'),
            'grade_level' => $request->get('grade_level'),
            'creator_id' => $request->get('creator_id'),
        ];
    }

    private function validateBank(Request $request): array
    {
        return $request->validate([
            'name_ar'                 => ['nullable', 'string', 'max:255'],
            'name_en'                 => ['nullable', 'string', 'max:255'],
            'description'             => ['nullable', 'string', 'max:1000'],
            'visibility'              => ['required', 'in:public,private'],
            'status'                  => ['required', 'in:active,inactive,under_review,archived'],
            'source'                  => ['required', 'in:manual,al_awwal,library,import,ana_qudurat'],
            'grade_level'             => ['nullable', 'integer', 'min:1', 'max:12'],
            'category_type'           => ['nullable', 'in:school,qudurat,verbal,quantitative,speed_reading'],
            'is_ana_qudurat_linkable' => ['nullable', 'boolean'],
            'exportable'              => ['nullable', 'boolean'],
            'external_platform'       => ['nullable', 'string', 'max:100'],
            'subject_id'              => ['nullable', 'integer', 'exists:subjects,id'],
            'subject_ids'             => ['nullable', 'array'],
            'subject_ids.*'           => ['integer', 'exists:subjects,id'],
            'member_roles'            => ['nullable', 'array'],
            'member_roles.*'          => ['nullable', 'in:viewer,editor'],
            'school_ids'              => ['nullable', 'array'],
            'school_ids.*'            => ['integer', 'exists:schools,id'],
        ]);
    }

    /**
     * Public banks created from inside a school stay attached to that school
     * (so the school still owns it) but become visible everywhere thanks to
     * the visibility flag. A super-admin without an active school scope can
     * still create a true platform-wide public bank (school_id = null).
     */
    private function resolveSchoolForPayload(array $data): ?int
    {
        return $this->activeSchoolId();
    }

    private function subjectsForSchool(?int $schoolId): \Illuminate\Support\Collection
    {
        $query = Subject::query()->select('id', 'name', 'name_en')->where('is_active', true);
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }
        return $query->orderBy('name')->get();
    }

    private function teachersForSchool(?int $schoolId): \Illuminate\Support\Collection
    {
        $query = User::query()->select('id', 'name', 'username');
        if ($schoolId !== null) {
            $query->where('school_id', $schoolId);
        }
        $query->whereHas('roles', function ($q) {
            $q->whereIn('slug', ['teacher', 'school-admin', 'super-admin']);
        });
        return $query->orderBy('name')->limit(200)->get();
    }

    /**
     * Schools selectable as sharing targets for a general (public) bank.
     * Super-admins see every school; a school-scoped admin sees the schools in
     * the same educational company (so a group of schools can share one bank).
     */
    private function schoolsForSharing(): \Illuminate\Support\Collection
    {
        $query = \App\Models\School::query()
            ->select('id', 'name', 'name_ar', 'name_en')
            ->where('is_active', true);

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $companyId = \App\Models\School::query()
                ->whereKey($user->school_id)
                ->value('educational_company_id');
            if ($companyId !== null) {
                $query->where('educational_company_id', $companyId);
            } elseif ($user->school_id) {
                $query->whereKey($user->school_id);
            }
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Super-admin or school-admin can create/edit/approve general (public) banks.
     */
    private function canManageGeneralBanks(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() || $user->isSchoolAdmin());
    }

    private function creatorsForSchool(?int $schoolId): \Illuminate\Support\Collection
    {
        $bankQuery = QuestionBank::query()
            ->select('created_by')
            ->where('is_library', false)
            ->whereNotNull('created_by');

        if ($schoolId !== null) {
            $bankQuery->where(function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId)->orWhere('visibility', 'public');
            });
        }

        $ids = $bankQuery->distinct()->pluck('created_by');
        if ($ids->isEmpty()) {
            return collect();
        }

        return User::query()
            ->select('id', 'name', 'username')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }

    private function vocabulary(): array
    {
        return [
            'visibilities' => [
                QuestionBank::VISIBILITY_PUBLIC => __('question_banks.visibility_public'),
                QuestionBank::VISIBILITY_PRIVATE => __('question_banks.visibility_private'),
            ],
            'statuses' => [
                QuestionBank::STATUS_ACTIVE => __('question_banks.status_active'),
                QuestionBank::STATUS_INACTIVE => __('question_banks.status_inactive'),
                QuestionBank::STATUS_UNDER_REVIEW => __('question_banks.status_under_review'),
                QuestionBank::STATUS_ARCHIVED => __('question_banks.status_archived'),
            ],
            'sources' => [
                QuestionBank::SOURCE_MANUAL    => __('question_banks.source_manual'),
                QuestionBank::SOURCE_AL_AWWAL  => __('question_banks.source_al_awwal'),
                QuestionBank::SOURCE_LIBRARY   => __('question_banks.source_library'),
                QuestionBank::SOURCE_IMPORT    => __('question_banks.source_import'),
                // Keep for display of legacy records only:
                QuestionBank::SOURCE_ANA_QUDURAT => __('question_banks.source_ana_qudurat'),
            ],
            'grades' => __('question_banks.grades'),
            'categories' => [
                QuestionBank::CATEGORY_SCHOOL => __('question_banks.category_school'),
                QuestionBank::CATEGORY_QUDURAT => __('question_banks.category_qudurat'),
                QuestionBank::CATEGORY_VERBAL => __('question_banks.category_verbal'),
                QuestionBank::CATEGORY_QUANTITATIVE => __('question_banks.category_quantitative'),
                QuestionBank::CATEGORY_SPEED_READING => __('question_banks.category_speed_reading'),
            ],
        ];
    }
}
