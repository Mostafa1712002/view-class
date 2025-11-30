@extends('layouts.admin')

@section('title', 'تفضيلات الإشعارات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة للإعدادات
            </a>
            <h1 class="h3 mb-0">تفضيلات الإشعارات</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.settings.notifications.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-envelope me-2"></i>إشعارات البريد الإلكتروني</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="email_grades" id="email_grades"
                                {{ $preferences['email_grades'] ?? false ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_grades">
                                <strong>إشعارات الدرجات</strong>
                                <p class="text-muted mb-0 small">استلام إشعار عند إضافة درجات جديدة</p>
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="email_attendance" id="email_attendance"
                                {{ $preferences['email_attendance'] ?? false ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_attendance">
                                <strong>إشعارات الحضور والغياب</strong>
                                <p class="text-muted mb-0 small">استلام إشعار عند تسجيل غياب</p>
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="email_announcements" id="email_announcements"
                                {{ $preferences['email_announcements'] ?? false ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_announcements">
                                <strong>الإعلانات والتنبيهات</strong>
                                <p class="text-muted mb-0 small">استلام الإعلانات الهامة من الإدارة</p>
                            </label>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="email_messages" id="email_messages"
                                {{ $preferences['email_messages'] ?? false ? 'checked' : '' }}>
                            <label class="form-check-label" for="email_messages">
                                <strong>الرسائل الخاصة</strong>
                                <p class="text-muted mb-0 small">استلام إشعار عند وصول رسالة جديدة</p>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-bell me-2"></i>إشعارات المتصفح</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch"
                                name="browser_notifications" id="browser_notifications"
                                {{ $preferences['browser_notifications'] ?? false ? 'checked' : '' }}>
                            <label class="form-check-label" for="browser_notifications">
                                <strong>تفعيل إشعارات المتصفح</strong>
                                <p class="text-muted mb-0 small">استلام إشعارات فورية في المتصفح</p>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="la la-globe me-2"></i>التفضيلات الإقليمية</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">اللغة</label>
                            <select name="language" class="form-select">
                                <option value="ar" {{ ($user->language ?? 'ar') === 'ar' ? 'selected' : '' }}>العربية</option>
                                <option value="en" {{ ($user->language ?? 'ar') === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">المنطقة الزمنية</label>
                            <select name="timezone" class="form-select">
                                <option value="Asia/Riyadh" {{ ($user->timezone ?? 'Asia/Riyadh') === 'Asia/Riyadh' ? 'selected' : '' }}>الرياض (GMT+3)</option>
                                <option value="Asia/Dubai" {{ ($user->timezone ?? 'Asia/Riyadh') === 'Asia/Dubai' ? 'selected' : '' }}>دبي (GMT+4)</option>
                                <option value="Asia/Kuwait" {{ ($user->timezone ?? 'Asia/Riyadh') === 'Asia/Kuwait' ? 'selected' : '' }}>الكويت (GMT+3)</option>
                                <option value="Africa/Cairo" {{ ($user->timezone ?? 'Asia/Riyadh') === 'Africa/Cairo' ? 'selected' : '' }}>القاهرة (GMT+2)</option>
                                <option value="Asia/Amman" {{ ($user->timezone ?? 'Asia/Riyadh') === 'Asia/Amman' ? 'selected' : '' }}>عمان (GMT+3)</option>
                                <option value="Asia/Beirut" {{ ($user->timezone ?? 'Asia/Riyadh') === 'Asia/Beirut' ? 'selected' : '' }}>بيروت (GMT+3)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body bg-light">
                        <h6 class="mb-3"><i class="la la-info-circle me-1"></i>ملاحظة</h6>
                        <p class="text-muted small mb-0">
                            سيتم تطبيق التغييرات على الإشعارات الجديدة فقط. الإشعارات السابقة لن تتأثر بهذه الإعدادات.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="la la-save me-1"></i>حفظ التفضيلات
            </button>
        </div>
    </form>
</div>
@endsection
