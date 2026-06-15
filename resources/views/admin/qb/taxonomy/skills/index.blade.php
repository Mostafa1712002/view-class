@extends('layouts.app')

@section('title', 'المهارات')
@section('body_class', 'theme-light')

@php
    $user = auth()->user();
    $canCreate = $user->canDo('skills.create');
    $canEdit   = $user->canDo('skills.edit');
    $canDelete = $user->canDo('skills.delete');
    $canImport = $user->canDo('skills.import');
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">المهارات</h2>
        <div class="breadcrumb-wrapper"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
            <li class="breadcrumb-item active">المهارات</li>
        </ol></div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif

    <div class="card mb-2"><div class="card-body py-2 d-flex flex-wrap gap-2">
        @if($canCreate)
            <a href="{{ route('admin.qb.skills.create') }}" class="btn btn-warning btn-sm"><x-svg-icon name="plus-circle-fill" :size="15" /> إضافة مهارة</a>
        @endif
        @if($canImport)
            <a href="{{ route('admin.qb.skills.import.index') }}" class="btn btn-outline-success btn-sm"><x-svg-icon name="file-earmark-excel-fill" :size="15" /> استيراد Excel</a>
        @endif
    </div></div>

    <div class="card mb-2"><div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">بحث باسم المهارة</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">المادة</label>
                <select name="subject_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach($subjects as $s)<option value="{{ $s->id }}" @selected((string)$filters['subject_id'] === (string)$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">الفصل الدراسي</label>
                <select name="semester_id" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach($semesters as $t)<option value="{{ $t->id }}" @selected((string)$filters['semester_id'] === (string)$t->id)>{{ $t->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">النوع</label>
                <select name="skill_type" class="form-select form-select-sm">
                    <option value="">الكل</option>
                    @foreach($types as $k => $label)<option value="{{ $k }}" @selected($filters['skill_type'] === $k)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm">تطبيق</button>
                <a href="{{ route('admin.qb.skills.index') }}" class="btn btn-outline-secondary btn-sm">تفريغ</a>
            </div>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        @if($skills->total() === 0)
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="inbox-fill" :size="34" /></div>
                <div class="ds-empty-title">لا توجد مهارات</div>
                <div class="ds-empty-desc">ابدأ بإضافة مهارة أو استيرادها من Excel.</div>
            </div>
        @else
            <div class="table-responsive">
                <table class="table mb-0" style="font-size:13px;">
                    <thead><tr>
                        <th>#</th><th>المهارة</th><th>المادة</th><th>الفصل الدراسي</th>
                        <th>الأسبوع</th><th>النوع</th><th>قدرات</th><th>تحصيلي</th>
                        <th>الحالة</th><th>التاريخ</th><th>العمليات</th>
                    </tr></thead>
                    <tbody>
                        @foreach($skills as $sk)
                            <tr>
                                <td>{{ $sk->id }}</td>
                                <td>{{ $sk->name }}</td>
                                <td>{{ optional($sk->subject)->name ?? '—' }}</td>
                                <td>{{ optional($sk->semester)->name ?? '—' }}</td>
                                <td>{{ optional($sk->week)->name ?? '—' }}</td>
                                <td>{{ $types[$sk->skill_type] ?? $sk->skill_type }}</td>
                                <td>@if($sk->is_ability)<span class="badge bg-info">نعم</span>@else—@endif</td>
                                <td>@if($sk->is_tahsili)<span class="badge bg-warning text-dark">نعم</span>@else—@endif</td>
                                <td>@if($sk->status==='active')<span class="badge bg-success">نشط</span>@else<span class="badge bg-secondary">غير نشط</span>@endif</td>
                                <td>{{ optional($sk->created_at)->format('Y-m-d') }}</td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        @if($canEdit)<a href="{{ route('admin.qb.skills.edit', $sk->id) }}" class="btn btn-sm btn-outline-primary" title="تعديل"><x-svg-icon name="pencil-fill" :size="14" /></a>@endif
                                        @if($canDelete)
                                            <form method="POST" action="{{ route('admin.qb.skills.destroy', $sk->id) }}" class="d-inline" onsubmit="return confirm('حذف المهارة؟')">
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
            <div class="p-3">{{ $skills->links() }}</div>
        @endif
    </div></div>
</div>
@endsection
