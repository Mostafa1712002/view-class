@extends('layouts.app')

@section('title', $lesson ? __('lessons_admin.breadcrumb_edit') : __('lessons_admin.breadcrumb_create'))
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $days = trans('lessons_admin.days');
    $isEdit = (bool) $lesson;

    // Pre-fill values for edit, otherwise from old() / sensible defaults.
    $cur = [
        'class_id' => old('class_id', $isEdit ? optional($lesson->schedule)->class_id : null),
        'academic_year_id' => old('academic_year_id', $isEdit ? optional($lesson->schedule)->academic_year_id : null),
        'semester' => old('semester', $isEdit ? optional($lesson->schedule)->semester : 'first'),
        'subject_id' => old('subject_id', $isEdit ? $lesson->subject_id : null),
        'teacher_id' => old('teacher_id', $isEdit ? $lesson->teacher_id : null),
        'substitute_teacher_id' => old('substitute_teacher_id', $isEdit ? $lesson->substitute_teacher_id : null),
        'day_of_week' => old('day_of_week', $isEdit ? $lesson->day_of_week : null),
        'period_number' => old('period_number', $isEdit ? $lesson->period_number : null),
        'start_time' => old('start_time', $isEdit && $lesson->start_time ? $lesson->start_time->format('H:i') : null),
        'end_time' => old('end_time', $isEdit && $lesson->end_time ? $lesson->end_time->format('H:i') : null),
        'room' => old('room', $isEdit ? $lesson->room : null),
    ];
@endphp

@push('styles')
<style>
    .ls-form-header h2 { font-size:1.5rem; font-weight:700; color:#0f172a; margin-bottom:.15rem; letter-spacing:-.2px; }
    .ls-form-header .breadcrumb { padding:0; margin:0; background:transparent; font-size:.85rem; }
    .ls-form-header .breadcrumb-item + .breadcrumb-item::before { color:#cbd5e1; }
    .ls-form-card {
        background:#fff; border:1px solid #e5e7eb; border-radius:14px;
        box-shadow:0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        padding:1.25rem 1.4rem; margin-bottom:1.25rem;
    }
    .ls-form-card h3 { font-size:1.05rem; font-weight:700; color:#0f172a; margin-bottom:.25rem; }
    .ls-form-card .subtitle { color:#64748b; font-size:.88rem; margin-bottom:1rem; }
    .ls-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:1rem; }
    .ls-grid .full { grid-column: 1 / -1; }
    .ls-field label { display:block; font-size:.85rem; font-weight:600; color:#334155; margin-bottom:.35rem; }
    .ls-field label .req { color:#dc2626; margin-{{ $isRtl ? 'right' : 'left' }}:.2rem; }
    .ls-field .form-control, .ls-field select.form-control {
        width:100%; background:#fff; border:1px solid #e2e8f0; border-radius:10px;
        padding:.55rem .85rem; font-size:.93rem; color:#0f172a;
        transition:border-color .15s ease, box-shadow .15s ease;
    }
    .ls-field .form-control:focus, .ls-field select.form-control:focus {
        border-color:var(--gold-300); box-shadow:0 0 0 .2rem rgba(207,160,70,.16); outline:none;
    }
    .ls-field .form-control.is-invalid { border-color:#f87171; }
    .ls-field .err { color:#dc2626; font-size:.78rem; margin-top:.25rem; }

    .ls-actions-bar { display:flex; gap:.55rem; flex-wrap:wrap; justify-content:flex-end; }
    .btn-gold { background:linear-gradient(135deg, var(--gold-300), var(--gold-500)); border:1px solid var(--gold-400); color:#fff; font-weight:600; padding:.6rem 1.3rem; border-radius:10px; box-shadow:0 1px 2px rgba(207,160,70,.18); transition:transform .15s ease, box-shadow .2s ease; display:inline-flex; align-items:center; gap:.45rem; }
    .btn-gold:hover { color:#fff; transform:translateY(-1px); box-shadow:0 6px 16px rgba(207,160,70,.22); }
    .btn-cancel { background:#fff; border:1px solid #e2e8f0; color:#475569; font-weight:600; padding:.6rem 1.2rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; transition:all .15s ease; }
    .btn-cancel:hover { background:#f8fafc; color:#0f172a; }

    @media (max-width:640px) { .ls-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="ls-form-header" style="margin-bottom:1.25rem">
    <h2>{{ $isEdit ? __('lessons_admin.form.edit_title') : __('lessons_admin.form.create_title') }}</h2>
    <nav><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('lessons_admin.breadcrumb_home')</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.lessons.index') }}">@lang('lessons_admin.breadcrumb_index')</a></li>
        <li class="breadcrumb-item active">{{ $isEdit ? __('lessons_admin.breadcrumb_edit') : __('lessons_admin.breadcrumb_create') }}</li>
    </ol></nav>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0; padding-{{ $isRtl ? 'right' : 'left' }}:1.2rem">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ $isEdit ? route('admin.lessons.update', $lesson->id) : route('admin.lessons.store') }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="ls-form-card">
        <h3>{{ $isEdit ? __('lessons_admin.form.edit_title') : __('lessons_admin.form.create_title') }}</h3>
        <p class="subtitle">{{ $isEdit ? __('lessons_admin.form.edit_subtitle') : __('lessons_admin.form.create_subtitle') }}</p>

        <div class="ls-grid">
            <div class="ls-field">
                <label>@lang('lessons_admin.form.class')<span class="req">*</span></label>
                <select name="class_id" class="form-control {{ $errors->has('class_id') ? 'is-invalid' : '' }}" required>
                    <option value="">@lang('lessons_admin.form.choose')</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected((string) $cur['class_id'] === (string) $c->id)>
                            {{ optional($c->section)->name ? $c->section->name . ' — ' : '' }}{{ $c->name }}
                        </option>
                    @endforeach
                </select>
                @error('class_id') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.academic_year')<span class="req">*</span></label>
                <select name="academic_year_id" class="form-control {{ $errors->has('academic_year_id') ? 'is-invalid' : '' }}" required>
                    <option value="">@lang('lessons_admin.form.choose')</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected((string) $cur['academic_year_id'] === (string) $y->id)>{{ $y->name ?? ($y->title ?? ('#' . $y->id)) }}</option>
                    @endforeach
                </select>
                @error('academic_year_id') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.semester')<span class="req">*</span></label>
                <select name="semester" class="form-control {{ $errors->has('semester') ? 'is-invalid' : '' }}" required>
                    <option value="first" @selected($cur['semester'] === 'first')>@lang('lessons_admin.form.semester_first')</option>
                    <option value="second" @selected($cur['semester'] === 'second')>@lang('lessons_admin.form.semester_second')</option>
                </select>
                @error('semester') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.subject')<span class="req">*</span></label>
                <select name="subject_id" class="form-control {{ $errors->has('subject_id') ? 'is-invalid' : '' }}" required>
                    <option value="">@lang('lessons_admin.form.choose')</option>
                    @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}" @selected((string) $cur['subject_id'] === (string) $sub->id)>{{ $sub->name }}</option>
                    @endforeach
                </select>
                @error('subject_id') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.teacher')<span class="req">*</span></label>
                <select name="teacher_id" class="form-control {{ $errors->has('teacher_id') ? 'is-invalid' : '' }}" required>
                    <option value="">@lang('lessons_admin.form.choose')</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" @selected((string) $cur['teacher_id'] === (string) $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
                @error('teacher_id') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.substitute_teacher')</label>
                <select name="substitute_teacher_id" class="form-control {{ $errors->has('substitute_teacher_id') ? 'is-invalid' : '' }}">
                    <option value="">@lang('lessons_admin.substitute.none')</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" @selected((string) $cur['substitute_teacher_id'] === (string) $t->id)>{{ $t->name }}</option>
                    @endforeach
                </select>
                @error('substitute_teacher_id') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.day')<span class="req">*</span></label>
                <select name="day_of_week" class="form-control {{ $errors->has('day_of_week') ? 'is-invalid' : '' }}" required>
                    <option value="">@lang('lessons_admin.form.choose')</option>
                    @foreach($days as $i => $d)
                        <option value="{{ $i }}" @selected((string) $cur['day_of_week'] !== '' && $cur['day_of_week'] !== null && (int) $cur['day_of_week'] === $i)>{{ $d }}</option>
                    @endforeach
                </select>
                @error('day_of_week') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.period_number')<span class="req">*</span></label>
                <input type="number" name="period_number" min="1" max="12" value="{{ $cur['period_number'] }}" class="form-control {{ $errors->has('period_number') ? 'is-invalid' : '' }}" required>
                @error('period_number') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.room')</label>
                <input type="text" name="room" value="{{ $cur['room'] }}" maxlength="100" class="form-control {{ $errors->has('room') ? 'is-invalid' : '' }}">
                @error('room') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.start_time')</label>
                <input type="time" name="start_time" value="{{ $cur['start_time'] }}" class="form-control {{ $errors->has('start_time') ? 'is-invalid' : '' }}">
                @error('start_time') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="ls-field">
                <label>@lang('lessons_admin.form.end_time')</label>
                <input type="time" name="end_time" value="{{ $cur['end_time'] }}" class="form-control {{ $errors->has('end_time') ? 'is-invalid' : '' }}">
                @error('end_time') <div class="err">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="ls-actions-bar">
        <a href="{{ route('admin.lessons.index') }}" class="btn-cancel"><i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>@lang('lessons_admin.actions.cancel')</a>
        <button type="submit" class="btn-gold"><i class="la la-save"></i>@lang('lessons_admin.actions.save')</button>
    </div>
</form>
@endsection
