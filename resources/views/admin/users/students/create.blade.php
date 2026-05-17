@extends('layouts.app')

@section('title', __('users.add_student'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .form-section { border: 1px solid #e5e7eb; border-radius: 14px; }
    body.theme-light .form-section .card-header {
        background: linear-gradient(135deg, #fff8e6, #fff);
        border-bottom: 1px solid #f1f5f9; padding: .85rem 1rem;
    }
    body.theme-light .form-section h5 { font-size: 1rem; color: #0f172a; font-weight: 700; }
    body.theme-light .form-section .section-icon {
        width: 32px; height: 32px; border-radius: 8px;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1rem; margin-inline-end: .5rem;
    }
    body.theme-light .form-section .form-control,
    body.theme-light .form-section .form-control:focus {
        border-radius: 10px; border: 1px solid #e5e7eb;
    }
    body.theme-light .form-section .form-control:focus {
        border-color: var(--gold-300, #fde2a8);
        box-shadow: 0 0 0 3px rgba(207,160,70,.12);
    }
    body.theme-light .form-section .form-label { font-weight: 600; color: #475569; font-size: .82rem; }
    body.theme-light .add-student-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; padding: .55rem 1.1rem;
        border-radius: 10px; font-weight: 600; box-shadow: 0 4px 14px rgba(207,160,70,.25);
    }
    body.theme-light .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; padding: .55rem 1rem; font-weight: 500;
    }
    body.theme-light .student-avatar-large {
        width: 96px; height: 96px; border-radius: 50%;
        background: linear-gradient(135deg, #fff6dd, #fde2a8);
        color: var(--gold-500); display: inline-flex; align-items: center; justify-content: center;
        font-size: 2rem; overflow: hidden;
    }
    body.theme-light .student-avatar-large img { width:100%; height:100%; object-fit:cover; }
</style>
@endpush

@section('content')
@php
    $student = $student ?? new \App\Models\User();
    $profile = $profile ?? new \App\Models\StudentProfile();
@endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.add_student')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item active">@lang('users.add_student')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <form action="{{ route('admin.users.students.store') }}" method="POST" enctype="multipart/form-data">
        @include('admin.users.students._form')
    </form>
</div>
@endsection
