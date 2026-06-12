@extends('layouts.app')

@section('title', __('special_education.title'))
@section('body_class', 'theme-light')

@php $isRtl = app()->getLocale() === 'ar'; @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <h2 class="content-header-title float-{{ $isRtl ? 'right' : 'left' }} mb-0">
            @lang('special_education.title')
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('special_education.breadcrumb_home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.special-education.index') }}">@lang('special_education.title')</a></li>
                <li class="breadcrumb-item active">
                    {{ $isRtl && $seStudent->student?->name_ar ? $seStudent->student->name_ar : $seStudent->student?->name }}
                </li>
            </ol>
        </div>
    </div>
    <div class="content-header-right text-md-{{ $isRtl ? 'left' : 'right' }} col-md-3 col-12 d-flex justify-content-{{ $isRtl ? 'start' : 'end' }} gap-2 flex-wrap">
        <a href="{{ route('manage.special-education.edit', $seStudent->id) }}" class="btn btn-primary">
            <i class="la la-edit"></i> @lang('special_education.btn_edit')
        </a>
        <a href="{{ route('manage.special-education.index') }}" class="btn btn-secondary">
            @lang('special_education.btn_back')
        </a>
    </div>
</div>


{{-- Student Info --}}
<div class="card mb-2">
    <div class="card-header">
        <h4 class="card-title">@lang('special_education.section_info')</h4>
    </div>
    <div class="card-content collapse show">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 col-6 mb-1">
                    <small class="text-muted d-block">@lang('special_education.field_student')</small>
                    <strong>{{ $isRtl && $seStudent->student?->name_ar ? $seStudent->student->name_ar : $seStudent->student?->name }}</strong>
                </div>
                <div class="col-md-3 col-6 mb-1">
                    <small class="text-muted d-block">@lang('special_education.field_category')</small>
                    <span class="badge badge-info">{{ $seStudent->categoryLabel() }}</span>
                </div>
                <div class="col-md-2 col-6 mb-1">
                    <small class="text-muted d-block">@lang('special_education.field_severity')</small>
                    @if($seStudent->severity)
                        <span class="badge badge-{{ $seStudent->severity === 'severe' ? 'danger' : ($seStudent->severity === 'moderate' ? 'warning' : 'success') }}">
                            {{ $seStudent->severityLabel() }}
                        </span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
                <div class="col-md-2 col-6 mb-1">
                    <small class="text-muted d-block">@lang('special_education.field_status')</small>
                    <span class="badge badge-{{ $seStudent->status === 'active' ? 'success' : ($seStudent->status === 'graduated' ? 'primary' : 'secondary') }}">
                        {{ $seStudent->statusLabel() }}
                    </span>
                </div>
                <div class="col-md-2 col-6 mb-1">
                    <small class="text-muted d-block">@lang('special_education.field_assigned_specialist')</small>
                    <span>{{ $seStudent->specialist ? ($isRtl && $seStudent->specialist->name_ar ? $seStudent->specialist->name_ar : $seStudent->specialist->name) : '—' }}</span>
                </div>
                @if($seStudent->diagnosis)
                <div class="col-12 mb-1">
                    <small class="text-muted d-block">@lang('special_education.field_diagnosis')</small>
                    <p class="mb-0">{{ $seStudent->diagnosis }}</p>
                </div>
                @endif
                @if($seStudent->notes)
                <div class="col-12 mb-1">
                    <small class="text-muted d-block">@lang('special_education.field_notes')</small>
                    <p class="mb-0">{{ $seStudent->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- IEP Plans --}}
<div class="card mb-2">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">@lang('special_education.section_plans')</h4>
    </div>
    <div class="card-content collapse show">
        <div class="card-body">
            {{-- Add Plan Form --}}
            <form action="{{ route('manage.special-education.plans.store', $seStudent->id) }}" method="POST" class="mb-2">
                @csrf
                <div class="row">
                    <div class="col-12 col-md-6 form-group mb-1">
                        <input type="text" name="title" class="form-control form-control-sm @error('title') is-invalid @enderror"
                            placeholder="@lang('special_education.field_plan_title')" maxlength="160" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-2 form-group mb-1">
                        <select name="status" class="form-control form-control-sm @error('status') is-invalid @enderror" required>
                            @foreach(['draft','active','completed'] as $ps)
                                <option value="{{ $ps }}" {{ $ps === 'active' ? 'selected' : '' }}>
                                    @lang('special_education.plan_status_' . $ps)
                                </option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-2 form-group mb-1">
                        <input type="date" name="start_date" class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                            placeholder="@lang('special_education.field_start_date')">
                        @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-2 form-group mb-1">
                        <input type="date" name="review_date" class="form-control form-control-sm @error('review_date') is-invalid @enderror"
                            placeholder="@lang('special_education.field_review_date')">
                        @error('review_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 form-group mb-1">
                        <textarea name="goals" rows="2" class="form-control form-control-sm @error('goals') is-invalid @enderror"
                            placeholder="@lang('special_education.field_goals')"></textarea>
                        @error('goals') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 form-group mb-1">
                        <textarea name="accommodations" rows="2" class="form-control form-control-sm @error('accommodations') is-invalid @enderror"
                            placeholder="@lang('special_education.field_accommodations')"></textarea>
                        @error('accommodations') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="la la-plus"></i> @lang('special_education.btn_add_plan')
                        </button>
                    </div>
                </div>
            </form>

            @if($plans->isEmpty())
                <p class="text-muted text-center mb-0">@lang('special_education.no_plans')</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>@lang('special_education.field_plan_title')</th>
                            <th>@lang('special_education.field_plan_status')</th>
                            <th>@lang('special_education.field_start_date')</th>
                            <th>@lang('special_education.field_review_date')</th>
                            <th>@lang('special_education.field_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                        <tr>
                            <td>{{ $plan->title }}</td>
                            <td>
                                <span class="badge badge-{{ $plan->status === 'active' ? 'success' : ($plan->status === 'completed' ? 'primary' : 'secondary') }}">
                                    {{ $plan->statusLabel() }}
                                </span>
                            </td>
                            <td>{{ $plan->start_date ? $plan->start_date->format('Y-m-d') : '—' }}</td>
                            <td>{{ $plan->review_date ? $plan->review_date->format('Y-m-d') : '—' }}</td>
                            <td>
                                <form action="{{ route('manage.special-education.plans.destroy', [$seStudent->id, $plan->id]) }}" method="POST" class="d-inline" id="del-plan-{{ $plan->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-plan" data-id="{{ $plan->id }}" title="@lang('special_education.btn_delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Progress Notes --}}
<div class="card">
    <div class="card-header">
        <h4 class="card-title mb-0">@lang('special_education.section_notes')</h4>
    </div>
    <div class="card-content collapse show">
        <div class="card-body">
            {{-- Add Note Form --}}
            <form action="{{ route('manage.special-education.notes.store', $seStudent->id) }}" method="POST" class="mb-2">
                @csrf
                <div class="row">
                    <div class="col-12 col-md-8 form-group mb-1">
                        <textarea name="body" rows="2" class="form-control form-control-sm @error('body') is-invalid @enderror"
                            placeholder="@lang('special_education.field_body')" required></textarea>
                        @error('body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-2 form-group mb-1">
                        <input type="date" name="note_date" class="form-control form-control-sm @error('note_date') is-invalid @enderror"
                            value="{{ date('Y-m-d') }}">
                        @error('note_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-2 form-group mb-1 d-flex align-items-start">
                        <button type="submit" class="btn btn-sm btn-success w-100">
                            <i class="la la-plus"></i> @lang('special_education.btn_add_note')
                        </button>
                    </div>
                </div>
            </form>

            @if($notes->isEmpty())
                <p class="text-muted text-center mb-0">@lang('special_education.no_notes')</p>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>@lang('special_education.field_note_date')</th>
                            <th>@lang('special_education.field_body')</th>
                            <th>@lang('special_education.field_author')</th>
                            <th>@lang('special_education.field_actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notes as $note)
                        <tr>
                            <td class="text-nowrap">{{ $note->note_date ? $note->note_date->format('Y-m-d') : '—' }}</td>
                            <td>{{ $note->body }}</td>
                            <td>{{ $note->author ? ($isRtl && $note->author->name_ar ? $note->author->name_ar : $note->author->name) : '—' }}</td>
                            <td>
                                <form action="{{ route('manage.special-education.notes.destroy', [$seStudent->id, $note->id]) }}" method="POST" class="d-inline" id="del-note-{{ $note->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-note" data-id="{{ $note->id }}" title="@lang('special_education.btn_delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).on('click', '.btn-delete-plan', function () {
    var id  = $(this).data('id');
    var msg = '@lang('special_education.confirm_plan_delete')';
    window.vcConfirm({ title: msg }).then(function (r) {
        if (r.isConfirmed) {
            document.getElementById('del-plan-' + id).submit();
        }
    });
});

$(document).on('click', '.btn-delete-note', function () {
    var id  = $(this).data('id');
    var msg = '@lang('special_education.confirm_note_delete')';
    window.vcConfirm({ title: msg }).then(function (r) {
        if (r.isConfirmed) {
            document.getElementById('del-note-' + id).submit();
        }
    });
});
</script>
@endpush
