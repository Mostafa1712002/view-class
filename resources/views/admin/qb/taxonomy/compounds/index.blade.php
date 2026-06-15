@extends('layouts.app')
@section('title', 'المجمعات')
@section('body_class', 'theme-light')
@php
    $user = auth()->user();
    $canCreate = $user->canDo('compounds.create');
    $canEdit   = $user->canDo('compounds.edit');
    $canDelete = $user->canDo('compounds.delete');
@endphp
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">المجمعات</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
        <li class="breadcrumb-item active">المجمعات</li>
    </ol></div>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-2"><div class="card-body py-2 d-flex flex-wrap gap-2">
        @if($canCreate)<a href="{{ route('admin.qb.compounds.create') }}" class="btn btn-warning btn-sm"><x-svg-icon name="plus-circle-fill" :size="15" /> إضافة مجمع</a>@endif
    </div></div>

    <div class="card mb-2"><div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-4"><label class="form-label" style="font-size:12px;font-weight:600;color:#475569;">بحث باسم المجمع</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm"></div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button class="btn btn-primary btn-sm">تطبيق</button>
                <a href="{{ route('admin.qb.compounds.index') }}" class="btn btn-outline-secondary btn-sm">تفريغ</a></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        @if($compounds->total() === 0)
            <div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="inbox-fill" :size="34" /></div>
                <div class="ds-empty-title">لا توجد مجمعات</div></div>
        @else
            <div class="table-responsive"><table class="table mb-0" style="font-size:13px;">
                <thead><tr><th>#</th><th>المجمع</th><th>المدارس</th><th>الترتيب</th><th>الحالة</th><th>العمليات</th></tr></thead>
                <tbody>
                    @foreach($compounds as $c)
                        <tr>
                            <td>{{ $c->id }}</td>
                            <td>{{ $c->name_ar }}@if($c->name_en)<small class="text-muted d-block">{{ $c->name_en }}</small>@endif</td>
                            <td><span class="badge bg-info">{{ $c->schools_count }}</span>
                                @if($c->schools->isNotEmpty())<small class="text-muted d-block">{{ $c->schools->pluck('name')->take(3)->implode('، ') }}{{ $c->schools->count() > 3 ? '…' : '' }}</small>@endif
                            </td>
                            <td>{{ $c->sort_order }}</td>
                            <td>@if($c->status==='active')<span class="badge bg-success">نشط</span>@else<span class="badge bg-secondary">غير نشط</span>@endif</td>
                            <td><div class="d-flex gap-1">
                                @if($canEdit)<a href="{{ route('admin.qb.compounds.edit', $c->id) }}" class="btn btn-sm btn-outline-primary"><x-svg-icon name="pencil-fill" :size="14" /></a>@endif
                                @if($canDelete)<form method="POST" action="{{ route('admin.qb.compounds.destroy', $c->id) }}" onsubmit="return confirm('حذف المجمع؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><x-svg-icon name="trash-fill" :size="14" /></button></form>@endif
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
            <div class="p-3">{{ $compounds->links() }}</div>
        @endif
    </div></div>
</div>
@endsection
