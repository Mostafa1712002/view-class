@extends('layouts.app')

@section('title', __('schools.promotion_history'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">
            @lang('schools.promotion_history') — {{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar) : ($school->name_ar ?: $school->name) }}
        </h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.schools.promotion.preview', $school) }}">@lang('schools.promotion_title')</a></li>
                <li class="breadcrumb-item active">@lang('schools.promotion_history')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body table-responsive">
            @if($batches->isEmpty())
                <p class="text-muted mb-0">@lang('schools.promotion_no_batches')</p>
            @else
            <table class="table table-hover">
                <thead><tr>
                    <th>#</th>
                    <th>@lang('schools.migrate_source_year')</th>
                    <th>@lang('schools.migrate_destination_year')</th>
                    <th>@lang('schools.promotion_summary')</th>
                    <th>@lang('schools.promotion_executed_by')</th>
                    <th>@lang('schools.promotion_status')</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($batches as $b)
                    @php $sum = $b->summary ?? []; @endphp
                    <tr>
                        <td>{{ $b->id }}</td>
                        <td>{{ optional($b->sourceYear)->name }}</td>
                        <td>{{ optional($b->destinationYear)->name }}</td>
                        <td>
                            <span class="badge badge-success">{{ $sum['promoted'] ?? 0 }}</span>
                            <span class="badge badge-primary">{{ $sum['graduated'] ?? 0 }}</span>
                            <span class="badge badge-warning">{{ $sum['overflow_moved'] ?? 0 }}</span>
                            <span class="badge badge-danger">{{ $sum['not_moved'] ?? 0 }}</span>
                        </td>
                        <td>{{ optional($b->executor)->name_ar ?? optional($b->executor)->name }}<br>
                            <small class="text-muted">{{ optional($b->executed_at)->format('Y-m-d H:i') }}</small></td>
                        <td>
                            @if($b->status === 'executed')
                                <span class="badge badge-info">@lang('schools.promotion_status_executed')</span>
                            @else
                                <span class="badge badge-secondary">@lang('schools.promotion_status_rolled_back')</span>
                            @endif
                        </td>
                        <td>
                            @if($b->status === 'executed' && $b->id === $latestExecutedId)
                                <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#rollback{{ $b->id }}">
                                    <i class="la la-undo"></i> @lang('schools.promotion_rollback')
                                </button>
                                <div class="modal fade" id="rollback{{ $b->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <form method="POST" action="{{ route('admin.schools.promotion.batches.rollback', [$school, $b]) }}">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">@lang('schools.promotion_rollback')</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-danger"><i class="la la-exclamation-triangle"></i> @lang('schools.promotion_rollback_body')</p>
                                                    <div class="form-group">
                                                        <label class="form-label">@lang('schools.promotion_password')</label>
                                                        <input type="password" name="password" class="form-control" required autocomplete="current-password">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('common.cancel')</button>
                                                    <button type="submit" class="btn btn-danger">@lang('schools.promotion_rollback_confirm')</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
@endsection
