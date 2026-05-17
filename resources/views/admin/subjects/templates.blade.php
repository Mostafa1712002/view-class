@extends('layouts.app')

@section('title', __('sprint4.subjects.add_template_title'))
@section('body_class','theme-light')

@push('styles')
<style>
    body.theme-light .templates-hero {
        background: linear-gradient(135deg, #fff6dd 0%, #ffffff 65%);
        border: 1px solid #f1e4b8 !important;
    }
    body.theme-light .grade-card { margin-bottom: 1rem; }
    body.theme-light .grade-card .grade-title {
        font-weight: 700; color: #0f172a; font-size: 1.05rem;
    }
    body.theme-light .grade-card .section-meta { color: #94a3b8; font-size: .82rem; }
    body.theme-light .tmpl-row {
        background: #f8fafc; border-radius: 10px; padding: .55rem .85rem;
        margin-bottom: .35rem; display: flex; align-items: center; gap: .6rem;
        border: 1px solid #f1f5f9; cursor: pointer;
        transition: all .15s ease;
    }
    body.theme-light .tmpl-row:hover { background: #fff6dd33; border-color: #fde8ad; }
    body.theme-light .tmpl-row.is-added {
        opacity: .55; cursor: not-allowed; background: #f1f5f9;
    }
    body.theme-light .tmpl-row input[type=checkbox] {
        width: 18px; height: 18px; accent-color: var(--gold-400);
    }
    body.theme-light .tmpl-row .name { font-weight: 600; color: #0f172a; }
    body.theme-light .tmpl-row .name-en { color: #94a3b8; font-size: .8rem; }
    body.theme-light .tmpl-row .badge-added {
        margin-inline-start: auto;
        background: #ecfdf5; color: #047857; font-size: .7rem;
        padding: .15rem .55rem; border-radius: 999px; font-weight: 600;
    }
    body.theme-light .select-all-label {
        display: inline-flex; align-items: center; gap: .35rem;
        background: #fff; border: 1px solid #e5e7eb; padding: .35rem .75rem;
        border-radius: 999px; font-size: .85rem; color: #475569; cursor: pointer;
    }
    body.theme-light .select-all-label:hover { border-color: var(--gold-300); color: var(--gold-500); }
    body.theme-light .add-subject-btn {
        background: linear-gradient(135deg, var(--gold-200), var(--gold-500)) !important;
        color: #fff !important; border: none; padding: .55rem 1.25rem;
        border-radius: 10px; font-weight: 600; box-shadow: 0 4px 14px rgba(207,160,70,.25);
    }
    body.theme-light .add-subject-btn:hover { transform: translateY(-1px); }
    body.theme-light .btn-soft {
        background: #fff; border: 1px solid #e5e7eb; color: #475569;
        border-radius: 10px; padding: .55rem 1.25rem; font-weight: 500;
    }
    body.theme-light .empty-state { padding: 4rem 1rem; text-align: center; }
    body.theme-light .empty-state .icon-wrap {
        width: 72px; height: 72px; border-radius: 18px; margin: 0 auto 1.25rem;
        background: linear-gradient(135deg, #fff6dd, #fde8ad);
        color: var(--gold-500); font-size: 2rem;
        display: inline-flex; align-items: center; justify-content: center;
    }
    body.theme-light .sticky-footer {
        position: sticky; bottom: 0; background: #fff; padding: .9rem;
        border-top: 1px solid #e5e7eb; border-radius: 0 0 14px 14px;
        margin: 0 -1rem -1rem;
    }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.subjects.add_template_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.subjects.add_template_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    {{-- Hero --}}
    <div class="card templates-hero mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="mb-1"><i class="la la-cloud-download-alt" style="color: var(--gold-400);"></i> @lang('sprint4.subjects.add_template_title')</h4>
                <p class="mb-0 text-muted">@lang('sprint4.subjects.add_template_help')</p>
            </div>
            @if($total > 0)
                <span class="grade-chip" style="background:#f1f5f9;color:#475569;padding:.3rem .75rem;border-radius:999px;font-weight:600;">
                    {{ $total }} مادة جاهزة
                </span>
            @endif
        </div>
    </div>

    @if($total === 0)
        {{-- Empty state — no platform templates yet --}}
        <div class="card">
            <div class="card-body empty-state">
                <span class="icon-wrap"><i class="la la-cloud-download-alt"></i></span>
                <h4 class="mb-2">@lang('sprint4.subjects.add_template_empty_title')</h4>
                <p class="text-muted mb-3" style="max-width:520px;margin:0 auto;">
                    @lang('sprint4.subjects.add_template_empty_help')
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('admin.subjects.create') }}" class="btn add-subject-btn">
                        <i class="la la-pen"></i> @lang('sprint4.subjects.add_manual')
                    </a>
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-soft">
                        <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
                        @lang('sprint4.subjects.add_template_back')
                    </a>
                </div>
            </div>
        </div>
    @else
        <form action="{{ route('admin.subjects.templates.attach') }}" method="POST">
            @csrf

            @foreach($byGrade as $level => $templates)
                <div class="card grade-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="grade-title">
                                @lang('sprint4.subjects.add_template_grade_label')
                                @if($level === 0)
                                    عام
                                @else
                                    الصف {{ $level }}
                                @endif
                            </span>
                            <div class="section-meta">@lang('sprint4.subjects.add_template_section_label')</div>
                        </div>
                        <label class="select-all-label">
                            <input type="checkbox" class="js-select-all" data-grade="{{ $level }}">
                            <span>@lang('sprint4.subjects.add_template_select_all')</span>
                        </label>
                    </div>
                    <div class="card-body">
                        @foreach($templates as $tmpl)
                            @php $isAdded = in_array($tmpl->id, $alreadyAdded, true); @endphp
                            <label class="tmpl-row {{ $isAdded ? 'is-added' : '' }}">
                                <input type="checkbox"
                                       name="template_ids[]"
                                       value="{{ $tmpl->id }}"
                                       class="js-tmpl-row"
                                       data-grade="{{ $level }}"
                                       {{ $isAdded ? 'disabled' : '' }}>
                                <span>
                                    <span class="name">{{ $tmpl->name }}</span>
                                    @if($tmpl->name_en)<span class="name-en d-block">{{ $tmpl->name_en }}</span>@endif
                                </span>
                                @if($isAdded)
                                    <span class="badge-added"><i class="la la-check"></i> @lang('sprint4.subjects.add_template_already_added')</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="card">
                <div class="card-body d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.subjects.index') }}" class="btn btn-soft">
                        @lang('sprint4.subjects.add_template_back')
                    </a>
                    <button type="submit" class="btn add-subject-btn">
                        <i class="la la-save"></i> @lang('sprint4.subjects.add_template_save')
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-select-all').forEach(function (master) {
        master.addEventListener('change', function () {
            var grade = master.dataset.grade;
            document.querySelectorAll('.js-tmpl-row[data-grade="' + grade + '"]:not(:disabled)').forEach(function (cb) {
                cb.checked = master.checked;
            });
        });
    });
});
</script>
@endsection
