@extends('layouts.admin')

@section('title', 'موادي')
@section('body_class', 'theme-light')

@push('styles')
<style>
    body.theme-light .subject-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        height: 100%;
        transition: box-shadow .15s ease, transform .15s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    body.theme-light .subject-card:hover {
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
        transform: translateY(-3px);
    }
    body.theme-light .subject-card .subject-icon-wrap {
        background: linear-gradient(135deg, #fff8e6, #fff3d6);
        border-bottom: 1px solid #f1e4b8;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 90px;
        font-size: 2.2rem;
        color: var(--gold-500, #cfa046);
    }
    body.theme-light .subject-card .card-body { padding: 1rem 1.1rem; flex: 1; }
    body.theme-light .subject-card h6 { font-weight: 700; color: #0f172a; font-size: 1rem; margin-bottom: .3rem; }
    body.theme-light .subject-card .grade-chip {
        display: inline-block;
        padding: .2rem .6rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: .75rem;
        font-weight: 600;
    }
    body.theme-light .subject-card .card-footer {
        padding: .75rem 1.1rem;
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
    }
    body.theme-light .btn-open-subject {
        background: linear-gradient(135deg, var(--gold-300, #ddb85c), var(--gold-500, #cfa046));
        color: #fff !important;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: .85rem;
        padding: .4rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    body.theme-light .btn-open-subject:hover { filter: brightness(1.06); }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">موادي</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">@lang('shell.portal_dashboard')</a></li>
                <li class="breadcrumb-item active">موادي</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">

    @if($subjects->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="la la-book-open la-3x d-block mb-2"></i>
                لا توجد مواد مسجّلة لصفّك حتى الآن.
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($subjects as $subject)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="subject-card">
                        <div class="subject-icon-wrap">
                            @if($subject->icon)
                                <i class="la {{ $subject->icon }}"></i>
                            @elseif($subject->image)
                                <img src="{{ asset('storage/' . ltrim($subject->image, '/')) }}"
                                     alt="{{ $subject->name }}"
                                     style="max-height:64px; border-radius:8px; object-fit:cover">
                            @else
                                <i class="la la-book-open"></i>
                            @endif
                        </div>
                        <div class="card-body">
                            <h6>{{ $subject->name }}</h6>
                            @if($subject->code)
                                <small class="text-muted d-block mb-1">{{ $subject->code }}</small>
                            @endif
                            @php
                                $gradeLevel = optional($student->classRoom)->grade_level;
                            @endphp
                            @if($gradeLevel)
                                <span class="grade-chip">الصف {{ $gradeLevel }}</span>
                            @endif
                        </div>
                        <div class="card-footer d-flex justify-content-end">
                            <a href="{{ route('student.subjects.show', $subject->id) }}"
                               class="btn-open-subject">
                                <i class="la la-folder-open"></i>
                                فتح المادة
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
