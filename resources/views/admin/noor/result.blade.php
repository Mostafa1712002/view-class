@extends('layouts.app')
@section('title', __('noor.result_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('noor.result_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.noor.form') }}">@lang('noor.breadcrumb')</a></li>
            <li class="breadcrumb-item active">@lang('noor.result_title')</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if ($pending)
        <div class="alert alert-warning">
            <h5 class="mb-2"><i class="la la-clock"></i> @lang('noor.status.pending')</h5>
            <p class="mb-0">{{ $note }}</p>
        </div>
    @elseif ($result)
        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.result_total')</div>
                        <h2 class="fw-bold mb-0">{{ $result->total }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center" style="background:#ecfdf5;">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.result_created')</div>
                        <h2 class="fw-bold mb-0" style="color:#16a34a;">{{ $result->created }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center" style="background:#eff6ff;">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.result_updated')</div>
                        <h2 class="fw-bold mb-0" style="color:#1d4ed8;">{{ $result->updated }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center" style="background:#fef2f2;">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.result_failed')</div>
                        <h2 class="fw-bold mb-0" style="color:#dc2626;">{{ $result->failed }}</h2>
                    </div>
                </div>
            </div>
        </div>

        @if (! empty($result->errors))
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('noor.result_errors_title')</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>@lang('noor.result_row')</th>
                                <th>@lang('noor.result_reason')</th>
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
    @endif

    <div class="text-center">
        <a href="{{ route('admin.noor.form') }}" class="btn btn-outline-primary">
            <i class="la la-arrow-right"></i> @lang('noor.back_to_form')
        </a>
    </div>
</div>
@endsection
