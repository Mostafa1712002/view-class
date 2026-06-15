@extends('layouts.app')
@section('title', 'المعايير')
@section('body_class', 'theme-light')
@php
    $user = auth()->user();
    $canCreate = $user->canDo('standards.create');
    $canEdit   = $user->canDo('standards.edit');
    $canDelete = $user->canDo('standards.delete');
@endphp
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">المعايير</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item active">المعايير</li>
    </ol></div>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-2"><div class="card-body py-2 d-flex flex-wrap gap-2">
        @if($canCreate)<a href="{{ route('admin.qb.standards.create') }}" class="btn btn-warning btn-sm"><x-svg-icon name="plus-circle-fill" :size="15" /> إضافة معيار</a>@endif
    </div></div>

    <div class="card mb-2"><div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-4"><label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">بحث (الاسم/الكود)</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm"></div>
            <div class="col-md-3"><label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">المادة</label>
                <select name="subject_id" class="form-select form-select-sm"><option value="">الكل</option>
                    @foreach($subjects as $s)<option value="{{ $s->id }}" @selected((string)$filters['subject_id'] === (string)$s->id)>{{ $s->name }}</option>@endforeach
                </select></div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary btn-sm">تطبيق</button>
                <a href="{{ route('admin.qb.standards.index') }}" class="btn btn-outline-secondary btn-sm">تفريغ</a></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        @if($standards->total() === 0)
            <div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="inbox-fill" :size="34" /></div>
                <div class="ds-empty-title">لا توجد معايير</div></div>
        @else
            <div class="table-responsive"><table class="table mb-0" style="font-size:13px;">
                <thead><tr><th>#</th><th>الكود</th><th>المعيار</th><th>المادة</th><th>المجال</th><th>الترتيب</th><th>الحالة</th><th>العمليات</th></tr></thead>
                <tbody>
                    @foreach($standards as $st)
                        <tr>
                            <td>{{ $st->id }}</td><td>{{ $st->code ?? '—' }}</td><td>{{ $st->name }}</td>
                            <td>{{ optional($st->subject)->name ?? '—' }}</td><td>{{ optional($st->domain)->name ?? '—' }}</td>
                            <td>{{ $st->sort_order }}</td>
                            <td>@if($st->status==='active')<span class="badge bg-success">نشط</span>@else<span class="badge bg-secondary">غير نشط</span>@endif</td>
                            <td><div class="d-flex gap-1">
                                @if($canEdit)<a href="{{ route('admin.qb.standards.edit', $st->id) }}" class="btn btn-sm btn-outline-primary"><x-svg-icon name="pencil-fill" :size="14" /></a>@endif
                                @if($canDelete)<form method="POST" action="{{ route('admin.qb.standards.destroy', $st->id) }}" onsubmit="return confirm('حذف المعيار؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><x-svg-icon name="trash-fill" :size="14" /></button></form>@endif
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="p-3">{{ $standards->links() }}</div>
        @endif
    </div></div>
</div>
@endsection
