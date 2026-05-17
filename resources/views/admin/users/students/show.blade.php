@extends('layouts.app')

@section('title', $student->name)
@section('body_class','theme-light')

@include('admin.users.students._sub_styles')

@section('content')
@php $active = 'profile'; @endphp
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $student->name }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.students.index') }}">@lang('users.students')</a></li>
                <li class="breadcrumb-item active">{{ $student->name }}</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('admin.users.students._header')
    @include('admin.users.students._subnav')

    @php
        $rows = [
            __('users.student_basic_info') => [
                __('users.student_full_name') => $student->name,
                __('users.username') => $student->username,
                __('users.email') => $student->email,
                __('users.national_id') => $student->national_id,
                __('users.student_first_name') => $profile->first_name,
                __('users.student_father_name') => $profile->father_name,
                __('users.student_grandfather_name') => $profile->grandfather_name,
                __('users.student_last_name') => $profile->last_name,
            ],
            __('users.student_name_en_section') => [
                __('users.student_first_name_en') => $profile->first_name_en,
                __('users.student_father_name_en') => $profile->father_name_en,
                __('users.student_grandfather_name_en') => $profile->grandfather_name_en,
                __('users.student_last_name_en') => $profile->last_name_en,
                __('users.name').' (EN)' => $student->name_en,
            ],
            __('users.student_extra_info') => [
                __('users.student_fingerprint') => $profile->fingerprint_id,
                __('users.student_seat_number') => $profile->seat_number,
                __('users.student_passport') => $profile->passport_number,
                __('users.student_nationality') => $profile->nationality,
                __('users.student_academic_id') => $profile->academic_id,
                __('users.gender') => $student->gender ? __('users.gender_'.$student->gender) : null,
                __('users.date_of_birth') => $student->date_of_birth ? $student->date_of_birth->format('Y-m-d') : null,
                __('users.student_birth_place') => $profile->birth_place,
                __('users.student_admission_year') => $profile->admission_year,
            ],
            __('users.student_school_info') => [
                __('users.grade_level') => optional($student->section)->name,
                __('users.class') => optional($student->classRoom)->name,
                __('users.student_previous_school') => $profile->previous_school,
                __('users.student_enrollment_date') => $profile->enrollment_date ? $profile->enrollment_date->format('Y-m-d') : null,
            ],
            __('users.student_family_info') => [
                __('users.student_father_national_id') => $profile->father_national_id,
                __('users.student_mother_national_id') => $profile->mother_national_id,
                __('users.student_mother_name') => $profile->mother_full_name,
            ],
            __('users.student_contact_info') => [
                __('users.phone') => $student->phone,
                __('users.student_home_phone') => $profile->home_phone,
                __('users.student_address') => $student->address,
            ],
        ];
    @endphp

    @foreach($rows as $title => $fields)
        @php
            $hasAny = collect($fields)->filter(fn ($v) => !blank($v))->isNotEmpty();
        @endphp
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">
                    <span class="icon-wrap"><i class="la la-info-circle"></i></span>
                    {{ $title }}
                </div>
                @if($hasAny)
                    <div class="info-grid">
                        @foreach($fields as $label => $value)
                            @continue(blank($value))
                            <div class="field">
                                <div class="label">{{ $label }}</div>
                                <div class="value">{{ $value }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted m-0">—</p>
                @endif
            </div>
        </div>
    @endforeach

    @if(!empty($profile->notes))
        <div class="card mb-3">
            <div class="card-body">
                <div class="section-title">
                    <span class="icon-wrap"><i class="la la-sticky-note"></i></span>
                    {{ __('users.student_notes') }}
                </div>
                <p class="m-0">{{ $profile->notes }}</p>
            </div>
        </div>
    @endif
</div>
@endsection
