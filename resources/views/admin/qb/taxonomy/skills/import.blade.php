@extends('layouts.app')
@section('title', 'استيراد المهارات')
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">استيراد المهارات من Excel</h2>
    <div class="breadcrumb-wrapper"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.qb.skills.index') }}">المهارات</a></li>
        <li class="breadcrumb-item active">استيراد</li>
    </ol></div>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @error('file')<div class="alert alert-danger">{{ $message }}</div>@enderror

    <div class="card mb-3"><div class="card-body">
        <p class="mb-3">حمّل نموذج Excel أولًا، عبّئ المهارات، ثم ارفع الملف. سيتم فحص الملف وعرض الأخطاء قبل الاستيراد، ولن يتم استيراد إلا الصفوف الصالحة.</p>
        <a href="{{ route('admin.qb.skills.import.template') }}" class="btn btn-outline-success btn-sm mb-3"><x-svg-icon name="download" :size="15" /> تحميل نموذج Excel</a>
        <form method="POST" action="{{ route('admin.qb.skills.import.preview') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6">
                <label class="form-label">ملف Excel</label>
                <input type="file" name="file" accept=".xlsx,.xls" class="form-control" required>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-warning"><x-svg-icon name="search" :size="15" /> فحص ومعاينة</button>
            </div>
        </form>
    </div></div>

    @if($history->isNotEmpty())
        <div class="card"><div class="card-body">
            <h6 class="mb-3">آخر عمليات الاستيراد</h6>
            <div class="table-responsive"><table class="table table-sm mb-0" style="font-size:13px;">
                <thead><tr><th>#</th><th>الملف</th><th>الإجمالي</th><th>الصالح</th><th>الخاطئ</th><th>المستورد</th><th>الحالة</th><th>التاريخ</th></tr></thead>
                <tbody>@foreach($history as $b)<tr>
                    <td>{{ $b->id }}</td><td>{{ $b->original_filename }}</td><td>{{ $b->total_rows }}</td>
                    <td>{{ $b->valid_rows }}</td><td>{{ $b->invalid_rows }}</td><td>{{ $b->imported_rows }}</td>
                    <td><span class="badge bg-secondary">{{ $b->status }}</span></td><td>{{ optional($b->created_at)->format('Y-m-d H:i') }}</td>
                </tr>@endforeach</tbody>
            </table></div>
        </div></div>
    @endif
</div>
@endsection
