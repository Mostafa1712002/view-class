@extends('layouts.app')
@section('title','القبول والتسجيل')
@section('body_class','theme-light')

@php
    use App\Modules\Admissions\Models\AdmissionApplication;
    $statuses = AdmissionApplication::STATUSES;
@endphp

@section('content')
<div class="content-header">
    <h2>القبول والتسجيل</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item active">القبول والتسجيل</li>
    </ol>
</div>

<div class="content-body">
    @if(session('success'))
        <div class="alert alert-success"><x-svg-icon name="check-circle-fill" :size="18" /> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><x-svg-icon name="exclamation-triangle-fill" :size="18" /> {{ session('error') }}</div>
    @endif

    {{-- status count chips --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach($statuses as $key => $meta)
            <span class="badge badge-pill badge-{{ $meta['color'] }} p-2">
                {{ $meta['label'] }}: {{ $counts[$key] ?? 0 }}
            </span>
        @endforeach
    </div>

    {{-- toolbar: settings + links + export --}}
    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('admissions.settings.schools') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="building-gear" :size="16" /> إعدادات المدرسة
        </a>
        <a href="{{ route('admissions.settings.form') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="ui-checks" :size="16" /> إعدادات التسجيل
        </a>
        <a href="{{ route('admissions.info.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="info-circle" :size="16" /> معلومات التسجيل
        </a>
        @if($companyLink)
        <button type="button" class="btn btn-outline-primary btn-sm js-copy" data-link="{{ $companyLink }}">
            <x-svg-icon name="link-45deg" :size="16" /> نسخ رابط التسجيل للشركة
        </button>
        @endif
        @if($schoolLink)
        <button type="button" class="btn btn-outline-primary btn-sm js-copy" data-link="{{ $schoolLink }}">
            <x-svg-icon name="link-45deg" :size="16" /> نسخ رابط تسجيل المدرسة
        </button>
        @endif
        <a href="{{ route('admissions.export', request()->query()) }}" class="btn btn-outline-success btn-sm ms-auto">
            <x-svg-icon name="download" :size="16" /> تصدير بحسب البحث
        </a>
    </div></div>

    {{-- filters --}}
    <div class="card mb-3"><div class="card-body">
        <form method="GET" action="{{ route('admissions.index') }}" class="form-row align-items-end">
            <div class="col-md-4 mb-2">
                <label>بحث</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="كود الطلب / اسم الطالب / ولي الأمر / الجوال / الهوية">
            </div>
            <div class="col-md-3 mb-2">
                <label>الحالة</label>
                <select name="status" class="form-control">
                    <option value="">— كل الحالات —</option>
                    @foreach($statuses as $key => $meta)
                        <option value="{{ $key }}" {{ $filters['status']===$key?'selected':'' }}>{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label>المدينة</label>
                <input type="text" name="city" value="{{ $filters['city'] }}" class="form-control">
            </div>
            <div class="col-md-2 mb-2">
                <button class="btn btn-primary btn-block"><x-svg-icon name="search" :size="16" /> بحث</button>
            </div>
        </form>
    </div></div>

    {{-- applications table --}}
    <div class="card"><div class="card-body table-responsive">
        @if($applications->isEmpty())
            <div class="text-center text-muted py-5">
                <x-svg-icon name="inbox" :size="48" class="d-block mx-auto mb-2" />
                <div>لا توجد طلبات قبول مطابقة.</div>
            </div>
        @else
        <table class="table table-hover align-middle">
            <thead><tr>
                <th>كود الطلب</th>
                <th>تاريخ الطلب</th>
                <th>اسم الطالب</th>
                <th>ولي الأمر</th>
                <th>الجوال</th>
                <th>الهوية / الهجري</th>
                <th>المدينة</th>
                <th>المرحلة / الصف</th>
                <th>الحالة</th>
                <th>الموعد</th>
                <th>التحكم</th>
            </tr></thead>
            <tbody>
            @foreach($applications as $app)
                <tr>
                    <td><strong>{{ $app->code }}</strong></td>
                    <td>{{ optional($app->created_at)->format('Y-m-d') }}</td>
                    <td>{{ $app->student_name ?: '—' }}</td>
                    <td>{{ $app->guardian_name ?: '—' }}</td>
                    <td dir="ltr">{{ $app->phone ?: '—' }}</td>
                    <td>{{ $app->national_id ?: $app->hijri_code ?: '—' }}</td>
                    <td>{{ $app->city ?: '—' }}</td>
                    <td>{{ trim(($app->stage ?: '').' '.($app->grade ?: '')) ?: '—' }}</td>
                    <td><span class="badge badge-{{ $app->statusColor() }}">{{ $app->statusLabel() }}</span></td>
                    <td>{{ $app->appointment_at ? $app->appointment_at->format('Y-m-d H:i') : '—' }}</td>
                    <td>
                        <a href="{{ route('admissions.show', $app->id) }}" class="btn btn-sm btn-outline-primary" title="عرض">
                            <x-svg-icon name="eye" :size="15" />
                        </a>
                        <a href="{{ route('admissions.edit', $app->id) }}" class="btn btn-sm btn-outline-secondary" title="تعديل">
                            <x-svg-icon name="pencil" :size="15" />
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($applications->hasPages())
        <div class="card-footer">{{ $applications->links() }}</div>
    @endif
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.js-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var link = btn.getAttribute('data-link');
        navigator.clipboard.writeText(link).then(function () {
            var old = btn.innerHTML;
            btn.innerHTML = '✔ تم النسخ';
            setTimeout(function () { btn.innerHTML = old; }, 1500);
        });
    });
});
</script>
@endpush
@endsection
