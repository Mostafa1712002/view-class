{{-- Schedule tabs — used by /admin/school-schedule and /admin/exams to switch
     between the regular class schedule and the exam schedule.
     Expects: $active ∈ ['class','exam']. --}}
@php($active = $active ?? 'exam')

@push('styles')
<style>
    body.theme-light .schedule-tabs {
        display: inline-flex; gap: .25rem; padding: .25rem;
        background: #fff; border: 1px solid #e5e7eb; border-radius: 999px;
        box-shadow: 0 4px 12px rgba(15,23,42,.04);
    }
    body.theme-light .schedule-tabs a {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .45rem 1rem; border-radius: 999px;
        color: #475569; font-weight: 600; font-size: .85rem;
        text-decoration: none; transition: all .15s ease;
    }
    body.theme-light .schedule-tabs a:hover { color: #0f172a; background: #f8fafc; }
    body.theme-light .schedule-tabs a.active {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500));
        color: #fff; box-shadow: 0 4px 12px rgba(207,160,70,.28);
    }
</style>
@endpush

<nav class="schedule-tabs mb-3" aria-label="@lang('exams_admin.page_title')">
    <a href="{{ route('admin.school-schedule.index') }}"
       class="{{ $active === 'class' ? 'active' : '' }}"
       aria-current="{{ $active === 'class' ? 'page' : 'false' }}">
        <i class="la la-calendar"></i>
        @lang('exams_admin.tabs.class_schedule')
    </a>
    <a href="{{ route('admin.exams.index') }}"
       class="{{ $active === 'exam' ? 'active' : '' }}"
       aria-current="{{ $active === 'exam' ? 'page' : 'false' }}">
        <i class="la la-file-alt"></i>
        @lang('exams_admin.tabs.exam_schedule')
    </a>
</nav>
