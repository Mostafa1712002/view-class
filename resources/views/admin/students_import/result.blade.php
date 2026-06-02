@extends('layouts.app')
@section('title', __('student_import.result.title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('student_import.result.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.students.import.form') }}">@lang('student_import.breadcrumb')</a></li>
            <li class="breadcrumb-item active">@lang('student_import.result.title')</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if ($result->failed > 0)
        <div class="alert alert-warning"><i class="la la-exclamation-triangle"></i> @lang('student_import.result.partial')</div>
    @else
        <div class="alert alert-success"><i class="la la-check-circle"></i> @lang('student_import.result.success')</div>
    @endif

    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.result.total')</div>
                    <h2 class="fw-bold mb-0">{{ $result->total }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#ecfdf5;">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.result.created')</div>
                    <h2 class="fw-bold mb-0" style="color:#16a34a;">{{ $result->created }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#eff6ff;">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.result.updated')</div>
                    <h2 class="fw-bold mb-0" style="color:#1d4ed8;">{{ $result->updated }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#fef2f2;">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.result.failed')</div>
                    <h2 class="fw-bold mb-0" style="color:#dc2626;">{{ $result->failed }}</h2>
                </div>
            </div>
        </div>
    </div>

    @if (($result->parentCreated ?? 0) > 0)
        <div class="alert alert-info">
            <i class="la la-user-friends"></i> @lang('student_import.result.parents'): {{ $result->parentCreated }}
        </div>
    @endif

    @if (! empty($result->errors))
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">@lang('student_import.result.reason')</h5>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>@lang('student_import.result.row')</th>
                            <th>@lang('student_import.result.reason')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($result->errors as $err)
                            <tr>
                                <td>{{ $err['row'] }}</td>
                                <td>{{ $err['reason'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="text-center">
        @if (! empty($result->errors))
            <a href="{{ route('admin.users.students.import.errors', $log_id) }}" class="btn btn-outline-danger">
                <i class="la la-download"></i> @lang('student_import.result.download_errors')
            </a>
        @endif
        <a href="{{ route('admin.users.students.import.form') }}" class="btn btn-outline-primary">
            <i class="la la-redo"></i> @lang('student_import.result.new_import')
        </a>
        <a href="{{ route('admin.users.students.index') }}" class="btn btn-primary">
            <i class="la la-users"></i> @lang('student_import.result.back_to_list')
        </a>
    </div>
</div>
@endsection
