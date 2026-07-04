@extends('layouts.app')

@section('title', $assignment->title)
@section('body_class', 'theme-light')

@push('styles')
<style>
    .as-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:1.25rem 1.4rem; margin-bottom:1rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .as-card h5 { font-size:1rem; font-weight:700; color:#0f172a; margin-bottom:.9rem; display:flex; align-items:center; gap:.5rem; }
    .as-card h5 i { color:var(--gold-400); }
    .as-meta-row { display:flex; flex-wrap:wrap; gap:.5rem 1.5rem; margin-bottom:.75rem; }
    .as-meta-row .item { font-size:.9rem; color:#475569; }
    .as-meta-row .item b { color:#0f172a; }
    .as-desc { font-size:.95rem; color:#334155; line-height:1.8; white-space:pre-line; }
    .status-chip { display:inline-flex; align-items:center; gap:.3rem; padding:.2rem .65rem; border-radius:999px; font-size:.75rem; font-weight:600; border:1px solid transparent; }
    .status-chip.active { background:#dcfce7; color:#15803d; border-color:#bbf7d0; }
    .status-chip.expired { background:#fee2e2; color:#b91c1c; border-color:#fecaca; }
    .status-chip.pending { background:#fffbeb; color:#92400e; border-color:#fde68a; }
    .status-chip.graded  { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .as-file-link { display:inline-flex; align-items:center; gap:.4rem; font-size:.9rem; color:#1d4ed8; }
    .as-feedback { background:#f8fafc; border:1px solid #e5e7eb; border-radius:10px; padding:.85rem 1rem; font-size:.92rem; color:#334155; }
</style>
@endpush

@section('content')

    <div class="content-header row">
        <div class="content-header-left col-md-8 col-12 mb-2">
            <h3 class="content-header-title">{{ $assignment->title }}</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('student.subjects.index') }}">@lang('subjects_content.page_title')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('student.subjects.show', $assignment->subject_id) }}">{{ $assignment->subject?->name ?? '—' }}</a></li>
                        <li class="breadcrumb-item active">{{ $assignment->title }}</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="content-header-right col-md-4 col-12 d-flex align-items-center justify-content-end mb-2">
            <a href="{{ route('student.subjects.show', $assignment->subject_id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="la la-arrow-right"></i> @lang('subjects_content.assignment_back')
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- Details --}}
    <div class="as-card">
        <h5><i class="la la-tasks"></i> @lang('subjects_content.assignment_details')</h5>
        <div class="as-meta-row">
            @if($assignment->due_date)
                <span class="item"><b>@lang('subjects_content.assignment_due'):</b>
                    {{ $assignment->due_date->format('Y-m-d') }}{{ $assignment->due_time ? ' ' . $assignment->due_time->format('H:i') : '' }}
                    @if($assignment->is_overdue)
                        <span class="status-chip expired">@lang('subjects_content.assignment_overdue')</span>
                    @endif
                </span>
            @endif
            @if($assignment->max_score)
                <span class="item"><b>@lang('subjects_content.assignment_max_score'):</b> {{ rtrim(rtrim((string) $assignment->max_score, '0'), '.') }}</span>
            @endif
        </div>
        @if($assignment->description)
            <div class="mb-2"><b>@lang('subjects_content.assignment_description'):</b>
                <div class="as-desc">{{ $assignment->description }}</div>
            </div>
        @endif
        @if($assignment->instructions)
            <div><b>@lang('subjects_content.assignment_instructions'):</b>
                <div class="as-desc">{{ $assignment->instructions }}</div>
            </div>
        @endif
    </div>

    {{-- Existing submission status --}}
    @if($submission && $submission->submitted_at)
        <div class="as-card">
            <h5><i class="la la-check-circle"></i> @lang('subjects_content.assignment_your_submission')</h5>
            <div class="as-meta-row">
                <span class="item"><b>@lang('subjects_content.assignment_status'):</b>
                    <span class="status-chip {{ $submission->status === 'graded' ? 'graded' : 'active' }}">{{ $submission->status_label }}</span>
                    @if($submission->is_late)<span class="status-chip expired">@lang('subjects_content.assignment_late')</span>@endif
                </span>
                @if($submission->submitted_at)
                    <span class="item"><b>@lang('subjects_content.assignment_submitted_at'):</b> {{ $submission->submitted_at->format('Y-m-d H:i') }}</span>
                @endif
                @if($submission->status === 'graded' && $submission->score !== null)
                    <span class="item"><b>@lang('subjects_content.assignment_score'):</b> {{ rtrim(rtrim((string) $submission->score, '0'), '.') }}{{ $assignment->max_score ? ' / ' . rtrim(rtrim((string) $assignment->max_score, '0'), '.') : '' }}</span>
                @endif
            </div>
            @if($submission->content)
                <div class="mb-2"><b>@lang('subjects_content.assignment_answer'):</b>
                    <div class="as-desc">{{ $submission->content }}</div>
                </div>
            @endif
            @if($submission->file_path)
                <div class="mb-2">
                    <a class="as-file-link" href="{{ Storage::disk('public')->url($submission->file_path) }}" target="_blank" rel="noopener">
                        <i class="la la-paperclip"></i> {{ $submission->file_name ?? __('subjects_content.assignment_current_file') }}
                    </a>
                </div>
            @endif
            @if($submission->feedback)
                <div><b>@lang('subjects_content.assignment_feedback'):</b>
                    <div class="as-feedback mt-1">{{ $submission->feedback }}</div>
                </div>
            @endif
        </div>
    @endif

    {{-- Submission form (hidden once graded/returned) --}}
    @php
        $locked = $assignment->status === 'closed'
            || ($assignment->is_overdue && ! $assignment->allow_late_submission)
            || in_array(optional($submission)->status, ['graded', 'returned'], true);
    @endphp
    @if(! $locked)
        <div class="as-card">
            <h5><i class="la la-upload"></i>
                {{ ($submission && $submission->submitted_at) ? __('subjects_content.assignment_resubmit') : __('subjects_content.assignment_submit') }}
            </h5>
            <form method="POST" action="{{ route('student.assignments.submit', $assignment->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>@lang('subjects_content.assignment_answer')</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="@lang('subjects_content.assignment_answer_hint')">{{ old('content', $submission->content ?? '') }}</textarea>
                </div>
                <div class="form-group">
                    <label>@lang('subjects_content.assignment_file')</label>
                    <input type="file" name="file" class="form-control-file">
                    @if($submission && $submission->file_path)
                        <small class="text-muted d-block mt-1">
                            @lang('subjects_content.assignment_current_file'): {{ $submission->file_name }}
                        </small>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="la la-paper-plane"></i> @lang('subjects_content.assignment_submit')
                </button>
            </form>
        </div>
    @elseif($assignment->status === 'closed' || ($assignment->is_overdue && ! $assignment->allow_late_submission))
        <div class="alert alert-warning">@lang('subjects_content.assignment_closed')</div>
    @endif

@endsection
