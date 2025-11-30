@extends('layouts.admin')

@section('title', 'صيانة النظام')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">صيانة النظام</h1>
        <a href="{{ route('admin.maintenance.system-info') }}" class="btn btn-info">
            <i class="la la-info-circle me-1"></i>معلومات النظام
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="la la-database la-3x text-primary mb-3"></i>
                    <h5>حجم قاعدة البيانات</h5>
                    <h3 class="text-primary">{{ $dbSize }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="la la-hdd la-3x text-success mb-3"></i>
                    <h5>الملفات المخزنة</h5>
                    <h3 class="text-success">{{ $storageUsed }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="la la-bolt la-3x text-warning mb-3"></i>
                    <h5>ذاكرة التخزين المؤقت</h5>
                    <h3 class="text-warning">{{ $cacheSize }}</h3>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="la la-file-alt la-3x text-danger mb-3"></i>
                    <h5>ملفات السجلات</h5>
                    <h3 class="text-danger">{{ $logsSize }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="la la-broom me-1"></i>مسح الذاكرة المؤقتة</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">مسح جميع ملفات الذاكرة المؤقتة للنظام وملفات العرض والإعدادات المخزنة مؤقتاً.</p>
                    <ul class="list-unstyled text-muted small">
                        <li><i class="la la-check text-success me-1"></i>تحرير مساحة التخزين</li>
                        <li><i class="la la-check text-success me-1"></i>تحديث الإعدادات المخزنة</li>
                        <li><i class="la la-check text-success me-1"></i>إعادة تحميل القوالب</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <form action="{{ route('admin.maintenance.clear-cache') }}" method="POST" onsubmit="return confirm('هل أنت متأكد؟')">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="la la-eraser me-1"></i>مسح الذاكرة المؤقتة
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="la la-trash me-1"></i>حذف السجلات</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">حذف جميع ملفات سجلات النظام لتحرير مساحة التخزين.</p>
                    <ul class="list-unstyled text-muted small">
                        <li><i class="la la-exclamation-triangle text-warning me-1"></i>سيتم حذف جميع السجلات</li>
                        <li><i class="la la-info-circle text-info me-1"></i>لن يمكن استرجاع السجلات</li>
                        <li><i class="la la-check text-success me-1"></i>سيتم إنشاء سجلات جديدة تلقائياً</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <form action="{{ route('admin.maintenance.clear-logs') }}" method="POST" onsubmit="return confirm('هل أنت متأكد؟ لن يمكن استرجاع السجلات.')">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="la la-trash me-1"></i>حذف السجلات
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="la la-rocket me-1"></i>تحسين النظام</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">تحسين أداء النظام من خلال تخزين الإعدادات والمسارات مؤقتاً.</p>
                    <ul class="list-unstyled text-muted small">
                        <li><i class="la la-check text-success me-1"></i>تسريع تحميل الصفحات</li>
                        <li><i class="la la-check text-success me-1"></i>تقليل استهلاك الموارد</li>
                        <li><i class="la la-check text-success me-1"></i>تحسين أداء قاعدة البيانات</li>
                    </ul>
                </div>
                <div class="card-footer">
                    <form action="{{ route('admin.maintenance.optimize') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="la la-rocket me-1"></i>تحسين النظام
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
