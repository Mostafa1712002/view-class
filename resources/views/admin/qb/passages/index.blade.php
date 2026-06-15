@extends('layouts.app')

@section('title', 'أسئلة القطعة')
@section('body_class', 'theme-light')

@php
    $user = auth()->user();
    $canCreate = $user->canDo('question_banks.create');
    $canEdit   = $user->canDo('question_banks.edit');
    $canDelete = $user->canDo('question_banks.delete');
    $stClass = [
        'draft' => 'qb-st-draft', 'pending_review' => 'qb-st-pending_review',
        'approved' => 'qb-st-approved', 'rejected' => 'qb-st-rejected', 'archived' => 'qb-st-archived',
    ];
@endphp

@push('styles')
<style>
    .qb-st-draft{background:#e2e8f0;color:#475569}.qb-st-pending_review{background:#fef3c7;color:#92400e}
    .qb-st-approved{background:#dcfce7;color:#166534}.qb-st-rejected{background:#fee2e2;color:#991b1b}
    .qb-st-archived{background:#fde68a;color:#78350f}
    .qb-status{padding:4px 9px;border-radius:999px;font-size:11px;white-space:nowrap}
    .qb-empty{padding:56px 16px;text-align:center;color:#64748b}.qb-empty .ic{font-size:46px;color:#cbd5e1}
    .pg-text{max-width:360px;color:#1e293b;line-height:1.5}
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">أسئلة القطعة (النصوص القرائية)</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.qb.questions.index') }}">قائمة الأسئلة</a></li>
                <li class="breadcrumb-item active">أسئلة القطعة</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif

    <div class="card mb-2"><div class="card-body py-2 d-flex flex-wrap gap-2">
        @if($canCreate)
            <a href="{{ route('admin.qb.passages.create') }}" class="btn btn-warning btn-sm">
                <x-svg-icon name="plus-circle-fill" :size="15" /> إضافة قطعة
            </a>
        @endif
        <a href="{{ route('admin.qb.questions.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="card-text" :size="15" /> قائمة الأسئلة
        </a>
    </div></div>

    <div class="card mb-2"><div class="card-body py-3">
        <form method="GET" action="{{ route('admin.qb.passages.index') }}" class="row g-2">
            <div class="col-md-4">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">بحث في نص القطعة</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">كود القطعة</label>
                <input type="text" name="code" value="{{ $filters['code'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">المادة</label>
                <select name="subject_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach($subjects as $s)<option value="{{ $s->id }}" @selected((string)$filters['subject_id'] === (string)$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">الحالة</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach($statuses as $k => $label)<option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                <a href="{{ route('admin.qb.passages.index') }}" class="btn btn-outline-secondary btn-sm">تفريغ</a>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        @if($passages->total() === 0)
            <div class="qb-empty">
                <div class="ic"><x-svg-icon name="inbox-fill" :size="46" /></div>
                <h5 class="mt-2">لا توجد قطع</h5>
                <p class="mb-3">ابدأ بإضافة قطعة قرائية ثم أضف أسئلتها التابعة.</p>
                @if($canCreate)<a href="{{ route('admin.qb.passages.create') }}" class="btn btn-warning btn-sm">إضافة قطعة</a>@endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table mb-0" style="font-size:13px;">
                    <thead><tr>
                        <th>#</th><th>القطعة</th><th>المادة</th><th>عدد الأسئلة</th>
                        <th>الصعوبة</th><th>الحالة</th><th>التاريخ</th><th>الإجراءات</th>
                    </tr></thead>
                    <tbody>
                        @foreach($passages as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td><div class="pg-text">{{ \Illuminate\Support\Str::limit(strip_tags($p->passage_text ?? ''), 100) ?: '—' }}</div>
                                    @if($p->passage_code)<small class="text-muted">كود: {{ $p->passage_code }}</small>@endif
                                </td>
                                <td>{{ optional($p->subject)->name ?? '—' }}</td>
                                <td><span class="badge bg-info">{{ $p->questions_count }}</span></td>
                                <td>{{ [1=>'سهل',2=>'متوسط',3=>'صعب'][$p->difficulty_level] ?? '—' }}</td>
                                <td><span class="qb-status {{ $stClass[$p->status] ?? '' }}">{{ $statuses[$p->status] ?? $p->status }}</span></td>
                                <td>{{ optional($p->created_at)->format('Y-m-d') }}</td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('admin.qb.passages.show', $p->id) }}" class="btn btn-sm btn-outline-info" title="عرض"><x-svg-icon name="eye-fill" :size="14" /></a>
                                        @if($canEdit)<a href="{{ route('admin.qb.passages.edit', $p->id) }}" class="btn btn-sm btn-outline-primary" title="تعديل"><x-svg-icon name="pencil-fill" :size="14" /></a>@endif
                                        @if($canDelete)
                                            <form method="POST" action="{{ route('admin.qb.passages.destroy', $p->id) }}" class="d-inline" onsubmit="return confirm('حذف القطعة؟ سيتم أرشفة أسئلتها التابعة.')">
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
            <div class="p-3">{{ $passages->links() }}</div>
        @endif
    </div></div>
</div>
@endsection
