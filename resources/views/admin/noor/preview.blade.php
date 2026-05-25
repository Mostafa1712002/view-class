@extends('layouts.app')
@section('title', __('noor.preview.title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('noor.preview.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.noor.form') }}">@lang('noor.breadcrumb')</a></li>
            <li class="breadcrumb-item active">@lang('noor.preview.title')</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if ($pending)
        <div class="alert alert-warning">
            <h5 class="mb-2"><i class="la la-clock"></i> @lang('noor.status.pending')</h5>
            <p class="mb-0">{{ $note }}</p>
        </div>
        <a href="{{ route('admin.noor.form') }}" class="btn btn-outline-primary"><i class="la la-arrow-right"></i> @lang('noor.back_to_form')</a>
    @else
        {{-- Summary counts --}}
        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center" style="background:#ecfdf5;">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.preview.status.new')</div>
                        <h2 class="fw-bold mb-0" style="color:#16a34a;">{{ $counts['new'] ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center" style="background:#eff6ff;">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.preview.status.update')</div>
                        <h2 class="fw-bold mb-0" style="color:#1d4ed8;">{{ $counts['update'] ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center" style="background:#fffbeb;">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.preview.status.duplicate')</div>
                        <h2 class="fw-bold mb-0" style="color:#b45309;">{{ $counts['duplicate'] ?? 0 }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card text-center" style="background:#fef2f2;">
                    <div class="card-body">
                        <div class="text-muted">@lang('noor.preview.status.invalid')</div>
                        <h2 class="fw-bold mb-0" style="color:#dc2626;">{{ $counts['invalid'] ?? 0 }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">@lang('noor.preview.table_title')</h5>
                <div>
                    @if (($counts['invalid'] ?? 0) + ($counts['duplicate'] ?? 0) > 0)
                        <a href="{{ route('admin.noor.errors', $log_id) }}" class="btn btn-sm btn-outline-danger">
                            <i class="la la-download"></i> @lang('noor.result_download_errors')
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>@lang('noor.result_row')</th>
                                <th>@lang('noor.preview.col_status')</th>
                                <th>@lang('noor.preview.col_name')</th>
                                <th>@lang('noor.preview.col_id')</th>
                                <th>@lang('noor.preview.col_grade')</th>
                                <th>@lang('noor.preview.col_class')</th>
                                <th>@lang('noor.preview.col_parent')</th>
                                <th>@lang('noor.preview.col_parent_id')</th>
                                <th>@lang('noor.result_reason')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                @php $st = $r['status'] ?? 'new'; @endphp
                                <tr @if($st==='invalid') style="background:#fef2f2;" @elseif($st==='duplicate') style="background:#fffbeb;" @endif>
                                    <td>{{ $r['rowNumber'] ?? '' }}</td>
                                    <td>
                                        <span class="badge
                                            @if($st==='new') badge-success
                                            @elseif($st==='update') badge-primary
                                            @elseif($st==='duplicate') badge-warning
                                            @else badge-danger @endif">
                                            {{ __('noor.preview.status.' . $st) }}
                                        </span>
                                    </td>
                                    <td>{{ $r['name'] ?? '' }}</td>
                                    <td>{{ $r['nationalId'] ?? '' }}</td>
                                    <td>{{ $r['grade'] ?? '' }}</td>
                                    <td>{{ $r['classRoom'] ?? '' }}</td>
                                    <td>{{ $r['parentName'] ?? '' }}</td>
                                    <td>{{ $r['parentNationalId'] ?? '' }}</td>
                                    <td class="text-danger">{{ $r['reason'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <a href="{{ route('admin.noor.form') }}" class="btn btn-outline-secondary">
                <i class="la la-arrow-right"></i> @lang('noor.back_to_form')
            </a>
            @if (($counts['new'] ?? 0) + ($counts['update'] ?? 0) > 0)
                <form method="POST" action="{{ route('admin.noor.execute', $log_id) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="la la-check"></i> @lang('noor.execute')
                        ({{ ($counts['new'] ?? 0) + ($counts['update'] ?? 0) }})
                    </button>
                </form>
            @else
                <span class="text-muted">@lang('noor.preview.nothing_to_import')</span>
            @endif
        </div>
    @endif
</div>
@endsection
