@extends('layouts.app')
@section('title','إعدادات التسجيل')
@section('body_class','theme-light')

@section('content')
<div class="content-header">
    <h2>إعدادات التسجيل</h2>
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admissions.index') }}">القبول والتسجيل</a></li>
        <li class="breadcrumb-item active">إعدادات التسجيل</li>
    </ol>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="POST" action="{{ route('admissions.settings.form.save') }}">
        @csrf
        <div class="card mb-3"><div class="card-body">
            <label>عنوان الاستمارة</label>
            <input type="text" name="form_title" value="{{ old('form_title', $setting->form_title) }}" class="form-control mb-3">

            <input type="text" id="fieldSearch" class="form-control mb-3" placeholder="البحث في الحقول...">

            <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr>
                    <th>حقل التسجيل</th>
                    <th width="120" class="text-center">عرض في الطلب</th>
                    <th width="120" class="text-center">مطلوب إجباري</th>
                    <th width="120">الترتيب</th>
                </tr></thead>
                <tbody>
                @foreach($fields as $i => $field)
                    <tr class="field-row">
                        <td class="field-label">
                            {{ $field->label }}
                            <input type="hidden" name="fields[{{ $i }}][id]" value="{{ $field->id }}">
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="fields[{{ $i }}][is_visible]" value="1" {{ $field->is_visible ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="fields[{{ $i }}][is_required]" value="1" {{ $field->is_required ? 'checked' : '' }}>
                        </td>
                        <td>
                            <input type="number" name="fields[{{ $i }}][sort_order]" value="{{ $field->sort_order }}" class="form-control form-control-sm" style="width:80px">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary"><x-svg-icon name="check2" :size="16" /> حفظ</button>
            <a href="{{ route('admissions.index') }}" class="btn btn-outline-secondary">إغلاق</a>
        </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('fieldSearch')?.addEventListener('input', function () {
    var t = this.value.trim().toLowerCase();
    document.querySelectorAll('.field-row').forEach(function (row) {
        var n = row.querySelector('.field-label').textContent.toLowerCase();
        row.hidden = t && n.indexOf(t) === -1;
    });
});
</script>
@endpush
@endsection
