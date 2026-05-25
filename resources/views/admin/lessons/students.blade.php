@extends('layouts.app')

@section('title', __('lessons_admin.students.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@push('styles')
<style>
    .ls-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04); margin-bottom:1.25rem; }
    .ls-card .ls-card-head { padding:1rem 1.1rem; border-bottom:1px solid #f1f5f9; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:.5rem; }
    .ls-card .ls-card-head i { color:var(--gold-400); }
    .ls-meta { display:flex; gap:1.25rem; flex-wrap:wrap; padding:1rem 1.1rem; color:#475569; font-size:.9rem; }
    .ls-meta b { color:#0f172a; }
    .btn-gold { background:linear-gradient(135deg, var(--gold-300), var(--gold-500)); border:1px solid var(--gold-400); color:#fff; font-weight:600; padding:.55rem 1.3rem; border-radius:10px; display:inline-flex; align-items:center; gap:.45rem; }
    .btn-gold:hover { color:#fff; }
    .btn-back { background:#fff; border:1px solid #e2e8f0; color:#475569; font-weight:600; padding:.55rem 1rem; border-radius:10px; display:inline-flex; align-items:center; gap:.35rem; }
    .ls-table { width:100%; margin:0; }
    .ls-table thead th { background:#f8fafc; color:#475569; font-size:.82rem; font-weight:700; padding:.7rem 1rem; border-bottom:1px solid #e5e7eb; }
    .ls-table tbody td { padding:.65rem 1rem; border-bottom:1px solid #f1f5f9; color:#0f172a; font-size:.9rem; vertical-align:middle; }
    .ls-empty { text-align:center; color:#94a3b8; padding:2rem; }
</style>
@endpush

@section('content')
<div style="margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap">
    <div>
        <h2 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin-bottom:.15rem">@lang('lessons_admin.students.title')</h2>
        <nav><ol class="breadcrumb" style="padding:0;margin:0;background:transparent;font-size:.85rem">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('lessons_admin.breadcrumb_home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.lessons.index') }}">@lang('lessons_admin.breadcrumb_index')</a></li>
            <li class="breadcrumb-item active">@lang('lessons_admin.students.title')</li>
        </ol></nav>
    </div>
    <a href="{{ route('admin.lessons.index') }}" class="btn-back"><i class="la la-arrow-{{ $isRtl ? 'right' : 'left' }}"></i>@lang('lessons_admin.actions.back')</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="ls-card">
    <div class="ls-meta">
        <span><b>@lang('lessons_admin.table.subject'):</b> {{ optional($lesson->subject)->name ?? '—' }}</span>
        <span><b>@lang('lessons_admin.table.teacher'):</b> {{ optional($lesson->teacher)->name ?? '—' }}</span>
        <span><b>@lang('lessons_admin.table.class'):</b> {{ optional($classRoom)->name ?? '—' }}</span>
        <span><b>@lang('lessons_admin.table.day'):</b> {{ trans('lessons_admin.days')[$lesson->day_of_week] ?? $lesson->day_of_week }}</span>
        <span><b>@lang('lessons_admin.table.period'):</b> {{ $lesson->period_number }}</span>
    </div>
</div>

<form action="{{ route('admin.lessons.students.update', $lesson->id) }}" method="POST">
    @csrf @method('PUT')
    <div class="ls-card">
        <div class="ls-card-head"><i class="la la-users"></i>@lang('lessons_admin.students.head')</div>
        <div class="table-responsive">
            <table class="ls-table">
                <thead>
                    <tr>
                        <th style="width:48px">
                            <input type="checkbox" id="checkAll">
                        </th>
                        <th>@lang('lessons_admin.students.name')</th>
                        <th>@lang('lessons_admin.students.academic_no')</th>
                        <th>@lang('lessons_admin.table.class')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classStudents as $student)
                        <tr>
                            <td>
                                <input type="checkbox" class="stu-check" name="student_ids[]" value="{{ $student->id }}"
                                    @checked(in_array($student->id, $linkedIds))>
                            </td>
                            <td>{{ $student->name }}</td>
                            <td>{{ $student->academic_number ?? $student->username ?? '—' }}</td>
                            <td>{{ optional($classRoom)->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="ls-empty">@lang('lessons_admin.students.empty')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($classStudents->isNotEmpty())
        <div style="display:flex;justify-content:flex-end;gap:.55rem">
            <button type="submit" class="btn-gold"><i class="la la-save"></i>@lang('lessons_admin.actions.save')</button>
        </div>
    @endif
</form>

<p style="color:#64748b;font-size:.82rem;margin-top:.75rem">@lang('lessons_admin.students.hint')</p>

@push('scripts')
<script>
    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('.stu-check').forEach(c => c.checked = this.checked);
    });
</script>
@endpush
@endsection
