@extends('layouts.app')

@section('title', __('schools.promotion_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.promotion_title') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.academic-years.index', $school) }}">@lang('schools.academic_years')</a></li>
                <li class="breadcrumb-item active">@lang('schools.promotion_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- Year picker --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.schools.promotion.preview', $school) }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">@lang('schools.migrate_source_year')</label>
                    <select name="source" class="form-control" required>
                        <option value="">—</option>
                        @foreach($years as $y)
                            <option value="{{ $y->id }}" @selected($src && $src->id == $y->id)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">@lang('schools.migrate_destination_year')</label>
                    <select name="destination" class="form-control" required>
                        <option value="">—</option>
                        @foreach($years as $y)
                            <option value="{{ $y->id }}" @selected($dst ? $dst->id == $y->id : $y->is_current)>{{ $y->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100"><i class="la la-search"></i> @lang('schools.promotion_preview')</button>
                </div>
            </form>
            <p class="text-muted mt-2 mb-0"><i class="la la-info-circle"></i> @lang('schools.promotion_hint')</p>
        </div>
    </div>

    @if($plan)
        @php $s = $plan['summary']; @endphp

        {{-- Summary tiles --}}
        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2"><div class="card text-center"><div class="card-body py-3">
                <h3 class="mb-0 text-success">{{ $s['promoted'] }}</h3><small class="text-muted">@lang('schools.promotion_promoted')</small>
            </div></div></div>
            <div class="col-md-3 col-6 mb-2"><div class="card text-center"><div class="card-body py-3">
                <h3 class="mb-0 text-primary">{{ $s['graduated'] }}</h3><small class="text-muted">@lang('schools.promotion_graduated')</small>
            </div></div></div>
            <div class="col-md-3 col-6 mb-2"><div class="card text-center"><div class="card-body py-3">
                <h3 class="mb-0 text-warning">{{ $s['overflow_moved'] }}</h3><small class="text-muted">@lang('schools.promotion_overflow')</small>
            </div></div></div>
            <div class="col-md-3 col-6 mb-2"><div class="card text-center"><div class="card-body py-3">
                <h3 class="mb-0 text-danger">{{ $s['not_moved'] }}</h3><small class="text-muted">@lang('schools.promotion_not_moved')</small>
            </div></div></div>
        </div>

        @foreach($plan['blockers'] as $b)
            <div class="alert alert-danger"><i class="la la-ban"></i> {{ $b }}</div>
        @endforeach
        @foreach($plan['warnings'] as $w)
            <div class="alert alert-warning"><i class="la la-exclamation-triangle"></i> {{ $w }}</div>
        @endforeach

        {{-- Plan table --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">@lang('schools.promotion_plan')</h5>
                @if(count($plan['moves']))
                    <button class="btn btn-primary" data-toggle="modal" data-target="#executeModal"
                            @if(!empty($plan['blockers'])) disabled title="@lang('schools.promotion_fix_blockers')" @endif>
                        <i class="la la-graduation-cap"></i> @lang('schools.promotion_execute')
                    </button>
                @endif
            </div>
            <div class="card-body table-responsive">
                @if(count($plan['moves']))
                <table class="table table-sm table-hover">
                    <thead><tr>
                        <th>@lang('schools.student_name')</th>
                        <th>@lang('schools.promotion_from')</th>
                        <th>@lang('schools.promotion_to')</th>
                        <th>@lang('schools.promotion_action')</th>
                    </tr></thead>
                    <tbody>
                    @foreach($plan['moves'] as $m)
                        <tr>
                            <td>{{ $m['student_name'] }}</td>
                            <td>{{ $m['from_section_name'] }} — {{ $m['from_class_name'] ?? '—' }}</td>
                            <td>
                                @if($m['action'] === 'graduated') <span class="text-primary">@lang('schools.promotion_graduated')</span>
                                @elseif($m['action'] === 'not_moved') <span class="text-danger">@lang('schools.promotion_stays')</span>
                                @else {{ $m['to_section_name'] }} — {{ $m['to_class_name'] }} @endif
                            </td>
                            <td>
                                @php $badge = ['promoted'=>'success','graduated'=>'primary','overflow_moved'=>'warning','not_moved'=>'danger'][$m['action']]; @endphp
                                <span class="badge badge-{{ $badge }}">@lang('schools.promotion_'.$m['action'])</span>
                                @if($m['reason']) <small class="text-muted">({{ $m['reason'] }})</small> @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                @else
                    <p class="text-muted mb-0">@lang('schools.promotion_nothing')</p>
                @endif
            </div>
        </div>

        {{-- Execute modal (password-gated) --}}
        <div class="modal fade" id="executeModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('admin.schools.promotion.execute', $school) }}">
                    @csrf
                    <input type="hidden" name="source_year_id" value="{{ $src->id }}">
                    <input type="hidden" name="destination_year_id" value="{{ $dst->id }}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">@lang('schools.promotion_confirm_title')</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p class="text-danger"><i class="la la-exclamation-triangle"></i> @lang('schools.promotion_confirm_body')</p>
                            <div class="form-group">
                                <label class="form-label">@lang('schools.promotion_password')</label>
                                <input type="password" name="password" class="form-control" required autocomplete="current-password">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('common.cancel')</button>
                            <button type="submit" class="btn btn-danger">@lang('schools.promotion_execute_confirm')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <a href="{{ route('admin.schools.promotion.batches', $school) }}" class="btn btn-link">
        <i class="la la-history"></i> @lang('schools.promotion_history')
    </a>
</div>
@endsection
