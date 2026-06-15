@extends('layouts.app')

@section('title', 'قائمة الأسئلة')
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $diffBadge = [1 => 'badge-diff-1', 2 => 'badge-diff-2', 3 => 'badge-diff-3'];
    $user = auth()->user();
    $canCreate  = $user->canDo('question_banks.create');
    $canEdit    = $user->canDo('question_banks.edit');
    $canDelete  = $user->canDo('question_banks.delete');
    $canArchive = $user->canDo('question_banks.archive');
    $canApprove = $user->canDo('question_banks.approve');
    $canReject  = $user->canDo('question_banks.reject');
@endphp

@push('styles')
<style>
    .qb-filters .form-label { font-size: 12px; font-weight: 600; color:#475569; margin-bottom:3px; }
    .qb-filters .form-select, .qb-filters .form-control { font-size: 13px; }
    .qb-table td { vertical-align: middle; font-size: 13px; }
    .qb-body { max-width: 320px; color:#1e293b; line-height:1.5; }
    .qb-body small { color:#64748b; }
    .qb-badge-type { background: rgba(212,175,55,.13); color:#7a5d12; font-weight:600; border:1px solid rgba(212,175,55,.35); padding:4px 9px; border-radius:999px; font-size:11px; white-space:nowrap; }
    .qb-badge-cat { background:#eef2ff; color:#3730a3; font-size:11px; padding:3px 8px; border-radius:999px; }
    .badge-diff-1 { background:#dcfce7; color:#166534; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-diff-2 { background:#fef3c7; color:#92400e; padding:4px 9px; border-radius:999px; font-size:11px; }
    .badge-diff-3 { background:#fee2e2; color:#991b1b; padding:4px 9px; border-radius:999px; font-size:11px; }
    .qb-st-draft { background:#e2e8f0; color:#475569; }
    .qb-st-pending_review { background:#fef3c7; color:#92400e; }
    .qb-st-approved { background:#dcfce7; color:#166534; }
    .qb-st-rejected { background:#fee2e2; color:#991b1b; }
    .qb-st-archived { background:#fde68a; color:#78350f; }
    .qb-status { padding:4px 9px; border-radius:999px; font-size:11px; white-space:nowrap; }
    .qb-empty { padding:56px 16px; text-align:center; color:#64748b; }
    .qb-empty .ic { font-size:46px; color:#cbd5e1; }
    .qb-actions .btn { padding:4px 7px; }
    .qb-toolbar { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .qb-thumb { width:42px; height:42px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">قائمة الأسئلة</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item active">قائمة الأسئلة</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif

    @include('admin.qb.partials.info-banner', [
        'key' => 'qb-questions',
        'slot' => '<b>قائمة الأسئلة:</b> تعرض كل أسئلة بنوك مدارسك. استخدم الفلاتر المتقدمة للبحث حسب البنك والمادة والمهارة والنوع والحالة. اضغط على <b>عرض الإجابة</b> أو <b>عرض الصفوف</b> لمعاينة تفاصيل كل سؤال داخل نافذة منبثقة.',
    ])

    {{-- Toolbar --}}
    <div class="card mb-2">
        <div class="card-body py-2">
            <div class="qb-toolbar">
                @if($canCreate)
                    <a href="{{ route('admin.qb.questions.create') }}" class="btn btn-warning btn-sm">
                        <x-svg-icon name="plus-circle-fill" :size="15" /> إضافة سؤال جديد
                    </a>
                    <a href="{{ route('admin.qb.questions.create', ['category' => 'tahsili']) }}" class="btn btn-outline-warning btn-sm">
                        <x-svg-icon name="mortarboard-fill" :size="15" /> إضافة سؤال تحصيلي
                    </a>
                @endif
                @if($user->canDo('question_banks.import'))
                    <a href="{{ route('admin.qb.import.index') }}" class="btn btn-outline-success btn-sm">
                        <x-svg-icon name="upload" :size="15" /> استيراد من Excel
                    </a>
                @endif
                <a href="{{ route('admin.qb.exams.index') }}" class="btn btn-outline-secondary btn-sm">
                    <x-svg-icon name="journal-check" :size="15" /> الاختبارات
                </a>
                <a href="{{ route('admin.qb.passages.index') }}" class="btn btn-outline-secondary btn-sm">
                    <x-svg-icon name="card-text" :size="15" /> أسئلة القطعة
                </a>
                <a href="{{ route('admin.qb.scope.index') }}" class="btn btn-outline-secondary btn-sm">
                    <x-svg-icon name="diagram-3-fill" :size="15" /> اختيار المدارس والصفوف
                </a>
                <a href="{{ route('admin.question-banks.index') }}" class="btn btn-outline-secondary btn-sm">
                    <x-svg-icon name="database-fill" :size="15" /> بنوك الأسئلة
                </a>
            </div>
        </div>
    </div>

    {{-- Filters (collapsible — #257) --}}
    @php $filtersActive = collect($filters)->filter(fn($v) => $v !== '' && $v !== null && $v !== false)->isNotEmpty(); @endphp
    <div class="card mb-2 qb-filters">
        <div class="card-header py-2 d-flex align-items-center justify-content-between" role="button"
             data-bs-toggle="collapse" data-bs-target="#qbFiltersBody" aria-expanded="{{ $filtersActive ? 'true' : 'false' }}" style="cursor:pointer;">
            <span class="d-flex align-items-center gap-2" style="font-weight:600;font-size:13px;color:#0f172a;">
                <x-svg-icon name="funnel-fill" :size="15" /> الفلاتر المتقدمة
                @if($filtersActive)<span class="badge bg-warning text-dark">مفعّلة</span>@endif
            </span>
            <x-svg-icon name="chevron-down" :size="14" />
        </div>
        <div class="collapse {{ $filtersActive ? 'show' : '' }}" id="qbFiltersBody">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.qb.questions.index') }}">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">بحث (السؤال / المهارة)</label>
                        <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm" placeholder="نص السؤال أو المهارة">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">كود السؤال</label>
                        <input type="text" name="code" value="{{ $filters['code'] }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">بنك الأسئلة</label>
                        <select name="bank_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($banks as $b)
                                <option value="{{ $b->id }}" @selected((string)$filters['bank_id'] === (string)$b->id)>{{ $b->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">المادة</label>
                        <select name="subject_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" @selected((string)$filters['subject_id'] === (string)$s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المهارة</label>
                        <select name="skill_id" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($skills as $sk)
                                <option value="{{ $sk->id }}" @selected((string)$filters['skill_id'] === (string)$sk->id)>{{ $sk->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">نوع السؤال</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($types as $k => $label)
                                <option value="{{ $k }}" @selected($filters['type'] === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">نوع التصنيف</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($categories as $k => $label)
                                <option value="{{ $k }}" @selected($filters['category'] === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">مستوى الصعوبة</label>
                        <select name="difficulty" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($difficulties as $k => $label)
                                <option value="{{ $k }}" @selected((string)$filters['difficulty'] === (string)$k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">الكل</option>
                            @foreach($statuses as $k => $label)
                                <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-12 d-flex gap-2 mt-1">
                        <label class="form-label mb-0 d-flex align-items-center gap-1">
                            <input type="checkbox" name="has_image" value="1" @checked($filters['has_image'])> يحتوي على صورة
                        </label>
                        <label class="form-label mb-0 d-flex align-items-center gap-1">
                            <input type="checkbox" name="full_image_only" value="1" @checked($filters['full_image_only'])> سؤال صورة كاملة
                        </label>
                        <div class="ms-auto d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">تطبيق الفلاتر</button>
                            <a href="{{ route('admin.qb.questions.index') }}" class="btn btn-outline-secondary btn-sm">تفريغ</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            @if($questions->total() === 0)
                <div class="ds-empty">
                    <div class="ds-empty-icon"><x-svg-icon name="inbox-fill" :size="34" /></div>
                    <div class="ds-empty-title">لا توجد أسئلة</div>
                    <div class="ds-empty-desc">لم يتم العثور على أسئلة مطابقة. جرّب تعديل الفلاتر أو ابدأ بإضافة سؤال جديد.</div>
                    @if($canCreate)
                        <a href="{{ route('admin.qb.questions.create') }}" class="btn btn-warning btn-sm mt-2">
                            <x-svg-icon name="plus-circle-fill" :size="15" /> إضافة سؤال جديد
                        </a>
                    @endif
                </div>
            @else
                <div class="table-responsive">
                    <table class="table qb-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>كود</th>
                                <th>المادة</th>
                                <th>السؤال</th>
                                <th>صورة</th>
                                <th>المهارة</th>
                                <th>الصعوبة</th>
                                <th>النوع</th>
                                <th>التصنيف</th>
                                <th>الحالة</th>
                                <th>المصدر</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questions as $q)
                                <tr>
                                    <td>{{ $q->id }}</td>
                                    <td>{{ $q->question_code ?? '—' }}</td>
                                    <td>{{ optional($q->bank)->name_ar ? \Illuminate\Support\Str::limit($q->bank->name_ar, 18) : '—' }}</td>
                                    <td>
                                        <div class="qb-body">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($q->body_ar ?? ''), 90) ?: '— (صورة)' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($q->attachment_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($q->attachment_path) }}" class="qb-thumb" alt="img">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($q->skill)->name ?? '—' }}</td>
                                    <td>
                                        <span class="{{ $diffBadge[$q->difficulty] ?? 'badge-diff-1' }}">
                                            {{ $difficulties[$q->difficulty] ?? '—' }}
                                        </span>
                                    </td>
                                    <td><span class="qb-badge-type">{{ $types[$q->type] ?? $q->type }}</span></td>
                                    <td><span class="qb-badge-cat">{{ $categories[$q->question_category] ?? 'عادي' }}</span></td>
                                    <td><span class="qb-status qb-st-{{ $q->status }}">{{ $statuses[$q->status] ?? $q->status }}</span></td>
                                    <td>{{ $q->source ?? '—' }}</td>
                                    <td>{{ optional($q->created_at)->format('Y-m-d') }}</td>
                                    <td class="qb-actions">
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button type="button" class="btn btn-sm btn-outline-info" title="عرض الإجابة" onclick="qbLoadModal('{{ route('admin.qb.questions.answer', $q->id) }}', 'عرض الإجابة')">
                                                <x-svg-icon name="eye-fill" :size="14" />
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" title="عرض الصفوف" onclick="qbLoadModal('{{ route('admin.qb.questions.classes', $q->id) }}', 'عرض الصفوف')">
                                                <x-svg-icon name="mortarboard-fill" :size="14" />
                                            </button>
                                            @if($canEdit)
                                                <a href="{{ route('admin.qb.questions.edit', $q->id) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                                    <x-svg-icon name="pencil-fill" :size="14" />
                                                </a>
                                            @endif
                                            {{-- #256 review transitions --}}
                                            @if($canEdit && in_array($q->status, ['draft','rejected'], true))
                                                <form method="POST" action="{{ route('admin.qb.questions.submit', $q->id) }}" class="d-inline" onsubmit="return confirm('إرسال هذا السؤال للمراجعة؟')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-secondary" title="إرسال للمراجعة"><x-svg-icon name="send-fill" :size="14" /></button>
                                                </form>
                                            @endif
                                            @if($canApprove && $q->status !== 'approved')
                                                <form method="POST" action="{{ route('admin.qb.questions.approve', $q->id) }}" class="d-inline" onsubmit="return confirm('اعتماد هذا السؤال؟')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success" title="اعتماد"><x-svg-icon name="check2-circle" :size="14" /></button>
                                                </form>
                                            @endif
                                            @if($canReject && !in_array($q->status, ['rejected','archived'], true))
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="رفض"
                                                    onclick="qbRejectModal('{{ route('admin.qb.questions.reject', $q->id) }}')">
                                                    <x-svg-icon name="x-circle-fill" :size="14" />
                                                </button>
                                            @endif
                                            @if($canCreate)
                                                <form method="POST" action="{{ route('admin.qb.questions.duplicate', $q->id) }}" class="d-inline">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-secondary" title="نسخ"><x-svg-icon name="files" :size="14" /></button>
                                                </form>
                                            @endif
                                            @if($canArchive && $q->status !== 'archived')
                                                <form method="POST" action="{{ route('admin.qb.questions.archive', $q->id) }}" class="d-inline" onsubmit="return confirm('أرشفة هذا السؤال؟')">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-warning" title="أرشفة"><x-svg-icon name="archive-fill" :size="14" /></button>
                                                </form>
                                            @endif
                                            @if($canDelete)
                                                <form method="POST" action="{{ route('admin.qb.questions.destroy', $q->id) }}" class="d-inline" onsubmit="return confirm('حذف هذا السؤال؟ إذا كان مستخدمًا في اختبار سيتم أرشفته بدلاً من حذفه.')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" title="حذف"><x-svg-icon name="trash-fill" :size="14" /></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3">
                    {{ $questions->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Shared modal for answer / classes fragments --}}
<div class="modal fade" id="qbModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qbModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body" id="qbModalBody">
                <div class="text-center py-4 text-muted">جارٍ التحميل…</div>
            </div>
        </div>
    </div>
</div>
{{-- #256 reject reason modal --}}
<div class="modal fade" id="qbRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" id="qbRejectForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">رفض السؤال</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                <textarea name="rejected_reason" class="form-control" rows="3" required placeholder="اكتب سبب رفض السؤال…"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-danger">رفض السؤال</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function qbRejectModal(url) {
    const form = document.getElementById('qbRejectForm');
    form.setAttribute('action', url);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('qbRejectModal')).show();
}
function qbLoadModal(url, title) {
    const modalEl = document.getElementById('qbModal');
    document.getElementById('qbModalTitle').textContent = title;
    document.getElementById('qbModalBody').innerHTML = '<div class="text-center py-4 text-muted">جارٍ التحميل…</div>';
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.ok ? r.text() : Promise.reject(r.status))
        .then(html => { document.getElementById('qbModalBody').innerHTML = html; })
        .catch(() => { document.getElementById('qbModalBody').innerHTML = '<div class="alert alert-danger mb-0">تعذّر تحميل المحتوى.</div>'; });
}
</script>
@endpush
