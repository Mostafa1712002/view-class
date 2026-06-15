@extends('layouts.app')
@section('title','المدارس المخصصة بالتسجيل')
@section('body_class','theme-light')

@section('content')
<div class="content-header">
    <h2>المدارس المخصصة بالتسجيل</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">القبول والتسجيل</a></li>
        <li class="breadcrumb-item active">إعدادات المدرسة</li>
    </ol>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card"><div class="card-body">
        <p class="text-muted">حدّد المدارس المتاحة في التسجيل. المدارس غير المحددة تُخفى من رابط التسجيل.</p>
        <input type="text" id="schoolSearch" class="form-control mb-3" placeholder="بحث في المدارس...">

        <form method="POST" action="{{ route('admissions.settings.schools.save') }}">
            @csrf
            <table class="table table-hover">
                <thead><tr>
                    <th width="60"><input type="checkbox" id="checkAll"></th>
                    <th>المدرسة</th>
                </tr></thead>
                <tbody>
                @forelse($schools as $school)
                    <tr class="school-row">
                        <td>
                            <input type="checkbox" name="schools[]" value="{{ $school->id }}" class="school-cb"
                                {{ ($enabled[$school->id] ?? true) ? 'checked' : '' }}>
                        </td>
                        <td class="school-name">{{ $school->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">لا توجد مدارس.</td></tr>
                @endforelse
                </tbody>
            </table>
            <button class="btn btn-primary"><x-svg-icon name="check2" :size="16" /> حفظ الإعدادات</button>
        </form>
    </div></div>
</div>

@push('scripts')
<script>
document.getElementById('checkAll')?.addEventListener('change', function () {
    document.querySelectorAll('.school-row:not([hidden]) .school-cb').forEach(cb => cb.checked = this.checked);
});
document.getElementById('schoolSearch')?.addEventListener('input', function () {
    var t = this.value.trim().toLowerCase();
    document.querySelectorAll('.school-row').forEach(function (row) {
        var n = row.querySelector('.school-name').textContent.toLowerCase();
        row.hidden = t && n.indexOf(t) === -1;
    });
});
</script>
@endpush
@endsection
