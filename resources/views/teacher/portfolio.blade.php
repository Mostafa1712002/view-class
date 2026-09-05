@extends('layouts.admin')

@section('title', 'ملف الإنجاز')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">ملف الإنجاز</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @foreach($sections as $key => $label)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ $label }}</h5>
            @if(in_array($key, $entryKeys, true))
            <button type="button" class="btn btn-sm btn-primary js-add-entry"
                data-section="{{ $key }}"
                data-store-url="{{ route('teacher.portfolio.entries.store') }}">
                <i class="la la-plus"></i> إضافة
            </button>
            @endif
        </div>
        <div class="card-body">
            @if(in_array($key, $entryKeys, true))
                @php $sectionEntries = $entries->get($key, collect()); @endphp
                @if($sectionEntries->isEmpty())
                    <p class="text-muted mb-0">لا توجد عناصر بعد</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>العنوان</th><th>التاريخ</th><th>مرفق</th><th class="text-end"></th></tr></thead>
                        <tbody>
                        @foreach($sectionEntries as $entry)
                        <tr>
                            <td>{{ $entry->title }}</td>
                            <td>{{ $entry->entry_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                @if($entry->attachment_path)
                                    <a href="{{ Storage::url($entry->attachment_path) }}" target="_blank" rel="noopener">عرض المرفق</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary js-edit-entry"
                                    data-section="{{ $key }}"
                                    data-title="{{ $entry->title }}"
                                    data-body="{{ $entry->body }}"
                                    data-date="{{ $entry->entry_date?->format('Y-m-d') }}"
                                    data-attachment-name="{{ $entry->attachment_path ? basename($entry->attachment_path) : '' }}"
                                    data-update-url="{{ route('teacher.portfolio.entries.update', $entry) }}">تعديل</button>
                                <form action="{{ route('teacher.portfolio.entries.destroy', $entry) }}" method="POST" class="d-inline" onsubmit="return confirm('تأكيد حذف هذا العنصر؟')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            @elseif($key === 'cv')
                @php $tp = $auto['teacher_profile']; @endphp
                <dl class="row mb-0">
                    <dt class="col-sm-3">الاسم</dt><dd class="col-sm-9">{{ $teacher->name }}</dd>
                    <dt class="col-sm-3">البريد الإلكتروني</dt><dd class="col-sm-9">{{ $teacher->email }}</dd>
                    <dt class="col-sm-3">الهاتف</dt><dd class="col-sm-9">{{ $teacher->phone ?? '-' }}</dd>
                    <dt class="col-sm-3">المؤهل</dt><dd class="col-sm-9">{{ $teacher->qualification ?? '-' }}</dd>
                    <dt class="col-sm-3">التخصص</dt><dd class="col-sm-9">{{ $teacher->specialization ?? '-' }}</dd>
                    <dt class="col-sm-3">تاريخ التعيين</dt><dd class="col-sm-9">{{ $teacher->hire_date?->format('Y-m-d') ?? '-' }}</dd>
                    <dt class="col-sm-3">الجنسية</dt><dd class="col-sm-9">{{ $tp?->nationality ?? $teacher->nationality ?? '-' }}</dd>
                    <dt class="col-sm-3">مكان الميلاد</dt><dd class="col-sm-9">{{ $tp?->birth_place ?? $teacher->birth_place ?? '-' }}</dd>
                </dl>
            @elseif($key === 'achievements')
                @php $certs = $auto['achievements']; @endphp
                @if($certs->isEmpty())
                    <p class="text-muted mb-0">لا توجد شهادات منشورة بعد</p>
                @else
                <div class="row">
                    @foreach($certs as $cert)
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card border h-100">
                            <div class="card-body">
                                <h6 class="mb-1">{{ $cert->title }}</h6>
                                @if($cert->issue_date)<p class="text-muted small mb-0">{{ $cert->issue_date->format('Y-m-d') }}</p>@endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            @elseif($key === 'testimonials')
                @php $visits = $auto['testimonials']; @endphp
                @if($visits->isEmpty())
                    <p class="text-muted mb-0">لا توجد زيارات صفية بعد</p>
                @else
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>المشرف</th><th>التاريخ</th><th>الحالة</th><th>الملاحظات</th></tr></thead>
                        <tbody>
                        @foreach($visits as $visit)
                        <tr>
                            <td>{{ $visit->supervisor?->name ?? '-' }}</td>
                            <td>{{ $visit->visit_date?->format('Y-m-d') ?? '-' }}</td>
                            <td>{{ $visit->status?->label() ?? '-' }}</td>
                            <td>{{ $visit->pre_notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            @endif
        </div>
    </div>
    @endforeach
</div>

@include('admin.portfolio._entry_modal')
@endsection
