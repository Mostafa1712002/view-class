@extends('layouts.app')

@section('title', __('evaluation.execute.subjects.page_title'))
@section('body_class','theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('evaluation.execute.subjects.page_title') — {{ $form->title }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.my-evaluations.index') }}">@lang('evaluation.my.page_title')</a></li>
                <li class="breadcrumb-item active">{{ $form->title }}</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.my-evaluations.index') }}" class="btn btn-outline-secondary"><i class="la la-arrow-right"></i> @lang('evaluation.execute.back')</a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <p class="text-muted">@lang('evaluation.execute.subjects.subtitle')</p>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">@lang('evaluation.execute.subjects.filters.school')</label>
                    <select name="school" class="form-control">
                        <option value="">@lang('evaluation.execute.subjects.filters.all')</option>
                        @foreach ($schools as $s)<option value="{{ $s->id }}" @selected($filters['school']===$s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">@lang('evaluation.execute.subjects.filters.subject')</label>
                    <select name="subject" class="form-control">
                        <option value="">@lang('evaluation.execute.subjects.filters.all')</option>
                        @foreach ($subjects as $s)<option value="{{ $s->id }}" @selected($filters['subject']===$s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">@lang('evaluation.execute.subjects.filters.status')</label>
                    <select name="status" class="form-control">
                        <option value="">@lang('evaluation.execute.subjects.filters.all')</option>
                        <option value="not_started" @selected($filters['status']==='not_started')>@lang('evaluation.execute.subjects.status.not_started')</option>
                        @foreach (['draft','completed','pending_approval','approved','rejected','needs_review','locked'] as $st)
                            <option value="{{ $st }}" @selected($filters['status']===$st)>@lang('evaluation.eval_status.'.$st)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary"><i class="la la-filter"></i> @lang('evaluation.execute.subjects.filters.show')</button>
                    <a href="{{ route('admin.evaluations.subjects', $form->id) }}" class="btn btn-outline-secondary">@lang('evaluation.execute.subjects.filters.reset')</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        @if (count($rows))
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th>@lang('evaluation.execute.subjects.columns.name')</th>
                        <th>@lang('evaluation.execute.subjects.columns.status')</th>
                        <th class="text-center">@lang('evaluation.execute.subjects.columns.percentage')</th>
                        <th class="text-end" style="width:160px;">@lang('evaluation.execute.subjects.columns.actions')</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="fw-bold">{{ $row['name'] }}
                                @unless($row['active'])<span class="badge bg-warning text-dark">@lang('evaluation.execute.subjects.inactive')</span>@endunless
                            </td>
                            <td>
                                @if ($row['status'] === 'not_started')
                                    <span class="badge bg-light text-dark">@lang('evaluation.execute.subjects.status.not_started')</span>
                                @else
                                    <span class="badge bg-info">@lang('evaluation.eval_status.'.$row['status'])</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $row['percentage'] !== null ? $row['percentage'].'%' : '—' }}</td>
                            <td class="text-end">
                                @if ($row['status'] === 'not_started')
                                    <a href="{{ route('admin.evaluations.execute.start', [$form->id, $row['subject_id']]) }}" class="btn btn-sm btn-primary"><i class="la la-play"></i> @lang('evaluation.execute.subjects.start')</a>
                                @elseif (in_array($row['status'], ['completed','approved','locked','pending_approval'], true))
                                    <a href="{{ route('admin.evaluations.execute.show', $row['evaluation_id']) }}" class="btn btn-sm btn-outline-primary"><i class="la la-eye"></i> @lang('evaluation.execute.subjects.view')</a>
                                @else
                                    <a href="{{ route('admin.evaluations.execute.show', $row['evaluation_id']) }}" class="btn btn-sm btn-outline-secondary"><i class="la la-edit"></i> @lang('evaluation.execute.subjects.continue')</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="card-body text-center text-muted py-5">@lang('evaluation.execute.subjects.none')</div>
        @endif
    </div>
</div>
@endsection
