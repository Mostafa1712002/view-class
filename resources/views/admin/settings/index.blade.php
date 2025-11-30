@extends('layouts.admin')

@section('title', 'إعدادات المدرسة')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">إعدادات المدرسة</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-building me-2"></i>معلومات المدرسة</h5>
                    </div>
                    <div class="card-body">
                        @foreach($defaults['general'] as $setting)
                            @if($setting['key'] !== 'school_logo')
                            <div class="mb-3">
                                <label class="form-label">{{ $setting['description'] }}</label>
                                <input type="text" name="{{ $setting['key'] }}" class="form-control"
                                    value="{{ $settings['general'][$setting['key']] ?? $setting['value'] }}">
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-graduation-cap me-2"></i>الإعدادات الأكاديمية</h5>
                    </div>
                    <div class="card-body">
                        @foreach($defaults['academic'] as $setting)
                            <div class="mb-3">
                                <label class="form-label">{{ $setting['description'] }}</label>
                                @if($setting['key'] === 'grading_system')
                                    <select name="{{ $setting['key'] }}" class="form-select">
                                        <option value="percentage" {{ ($settings['academic'][$setting['key']] ?? $setting['value']) === 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
                                        <option value="gpa" {{ ($settings['academic'][$setting['key']] ?? $setting['value']) === 'gpa' ? 'selected' : '' }}>معدل تراكمي (GPA)</option>
                                        <option value="letter" {{ ($settings['academic'][$setting['key']] ?? $setting['value']) === 'letter' ? 'selected' : '' }}>حرفي (A, B, C)</option>
                                    </select>
                                @else
                                    <input type="number" name="{{ $setting['key'] }}" class="form-control"
                                        value="{{ $settings['academic'][$setting['key']] ?? $setting['value'] }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-bell me-2"></i>إعدادات الإشعارات</h5>
                    </div>
                    <div class="card-body">
                        @foreach($defaults['notifications'] as $setting)
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch"
                                    name="{{ $setting['key'] }}" id="{{ $setting['key'] }}"
                                    {{ ($settings['notifications'][$setting['key']] ?? $setting['value']) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $setting['key'] }}">{{ $setting['description'] }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-image me-2"></i>شعار المدرسة</h5>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $logo = $settings['general']['school_logo'] ?? null;
                        @endphp
                        <div class="mb-3">
                            @if($logo)
                                <img src="{{ Storage::url($logo) }}" alt="شعار المدرسة" class="img-fluid mb-3" style="max-height: 150px;" id="logo-preview">
                            @else
                                <div class="bg-light p-4 rounded mb-3" id="logo-placeholder">
                                    <i class="la la-image la-3x text-muted"></i>
                                    <p class="text-muted mb-0">لم يتم رفع شعار</p>
                                </div>
                                <img src="" alt="شعار المدرسة" class="img-fluid mb-3 d-none" style="max-height: 150px;" id="logo-preview">
                            @endif
                        </div>
                        <input type="file" name="logo" id="logo-input" class="form-control" accept="image/*">
                        <small class="text-muted">الحد الأقصى للحجم: 2 ميجابايت</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-link me-2"></i>روابط سريعة</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('admin.settings.profile') }}" class="btn btn-outline-primary">
                                <i class="la la-user me-1"></i>الملف الشخصي
                            </a>
                            <a href="{{ route('admin.settings.password') }}" class="btn btn-outline-warning">
                                <i class="la la-lock me-1"></i>تغيير كلمة المرور
                            </a>
                            <a href="{{ route('admin.settings.notifications') }}" class="btn btn-outline-info">
                                <i class="la la-bell me-1"></i>تفضيلات الإشعارات
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="la la-save me-1"></i>حفظ الإعدادات
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.getElementById('logo-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('logo-preview');
            const placeholder = document.getElementById('logo-placeholder');
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            if (placeholder) {
                placeholder.classList.add('d-none');
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
@endsection
