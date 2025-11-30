@extends('layouts.app')

@section('title', 'عرض الخطة الأسبوعية')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">الخطة الأسبوعية</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.weekly-plans.index') }}">الخطط الأسبوعية</a></li>
                        <li class="breadcrumb-item active">عرض</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        @if($weeklyPlan->canEdit(auth()->user()))
        <a href="{{ route('manage.weekly-plans.edit', $weeklyPlan) }}" class="btn btn-warning"><i data-feather="edit"></i> تعديل</a>
        @endif
        <a href="{{ route('manage.weekly-plans.duplicate', $weeklyPlan) }}" class="btn btn-secondary"><i data-feather="copy"></i> نسخ</a>
        <a href="{{ route('manage.weekly-plans.index') }}" class="btn btn-outline-secondary"><i data-feather="arrow-right"></i> رجوع</a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h4 class="card-title">معلومات الخطة</h4>
                    <span class="badge {{ $weeklyPlan->status_class }}">{{ $weeklyPlan->status }}</span>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>المعلم:</strong> {{ $weeklyPlan->teacher->name }}
                        </div>
                        <div class="col-md-6">
                            <strong>المادة:</strong> {{ $weeklyPlan->subject->name }}
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>الفصل:</strong> {{ $weeklyPlan->classRoom->name }} - {{ $weeklyPlan->classRoom->division }}
                        </div>
                        <div class="col-md-6">
                            <strong>المرحلة:</strong> {{ $weeklyPlan->classRoom->section->name ?? '-' }}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>الفترة:</strong> {{ $weeklyPlan->week_label }}
                        </div>
                        @if($weeklyPlan->is_locked && $weeklyPlan->lockedByUser)
                        <div class="col-md-6">
                            <strong>قفل بواسطة:</strong> {{ $weeklyPlan->lockedByUser->name }} في {{ $weeklyPlan->locked_at->format('Y-m-d H:i') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @if(auth()->user()->isSuperAdmin() || auth()->user()->isSchoolAdmin())
            <div class="card">
                <div class="card-header"><h4 class="card-title">إجراءات المدير</h4></div>
                <div class="card-body">
                    @if($weeklyPlan->is_locked)
                    <form action="{{ route('manage.weekly-plans.unlock', $weeklyPlan) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block w-100 mb-1"><i data-feather="unlock"></i> فتح القفل</button>
                    </form>
                    @else
                    <form action="{{ route('manage.weekly-plans.lock', $weeklyPlan) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-block w-100 mb-1"><i data-feather="lock"></i> قفل الخطة</button>
                    </form>
                    @endif
                    <form action="{{ route('manage.weekly-plans.destroy', $weeklyPlan) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-block w-100"><i data-feather="trash-2"></i> حذف</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4 class="card-title">محتوى الخطة</h4></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="border rounded p-1">
                        <h6 class="text-primary">أهداف الأسبوع</h6>
                        <p class="mb-0">{!! nl2br(e($weeklyPlan->objectives)) ?: '<span class="text-muted">-</span>' !!}</p>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="border rounded p-1">
                        <h6 class="text-primary">المواضيع</h6>
                        <p class="mb-0">{!! nl2br(e($weeklyPlan->topics)) ?: '<span class="text-muted">-</span>' !!}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="border rounded p-1">
                        <h6 class="text-primary">الأنشطة</h6>
                        <p class="mb-0">{!! nl2br(e($weeklyPlan->activities)) ?: '<span class="text-muted">-</span>' !!}</p>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="border rounded p-1">
                        <h6 class="text-primary">الموارد والوسائل</h6>
                        <p class="mb-0">{!! nl2br(e($weeklyPlan->resources)) ?: '<span class="text-muted">-</span>' !!}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="border rounded p-1">
                        <h6 class="text-primary">التقييم</h6>
                        <p class="mb-0">{!! nl2br(e($weeklyPlan->assessment)) ?: '<span class="text-muted">-</span>' !!}</p>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="border rounded p-1">
                        <h6 class="text-primary">الواجبات</h6>
                        <p class="mb-0">{!! nl2br(e($weeklyPlan->homework)) ?: '<span class="text-muted">-</span>' !!}</p>
                    </div>
                </div>
            </div>
            @if($weeklyPlan->notes)
            <div class="row">
                <div class="col-12">
                    <div class="border rounded p-1 bg-light-warning">
                        <h6 class="text-warning">ملاحظات</h6>
                        <p class="mb-0">{!! nl2br(e($weeklyPlan->notes)) !!}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
