@extends('layouts.app')
@section('title', __('student_import.preview.title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('student_import.preview.title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.students.import.form') }}">@lang('student_import.breadcrumb')</a></li>
            <li class="breadcrumb-item active">@lang('student_import.preview.title')</li>
        </ol>
    </div>
</div>

<div class="content-body">
    {{-- Summary counts --}}
    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#ecfdf5;">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.preview.summary_new')</div>
                    <h2 class="fw-bold mb-0" style="color:#16a34a;">{{ $counts['new'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#eff6ff;">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.preview.summary_update')</div>
                    <h2 class="fw-bold mb-0" style="color:#1d4ed8;">{{ $counts['update'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#fffbeb;">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.preview.summary_duplicate')</div>
                    <h2 class="fw-bold mb-0" style="color:#b45309;">{{ $counts['duplicate'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#fef2f2;">
                <div class="card-body">
                    <div class="text-muted">@lang('student_import.preview.summary_invalid')</div>
                    <h2 class="fw-bold mb-0" style="color:#dc2626;">{{ $counts['invalid'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">@lang('student_import.preview.title')</h5>
            @if (($counts['invalid'] ?? 0) + ($counts['duplicate'] ?? 0) > 0)
                <a href="{{ route('admin.users.students.import.errors', $log_id) }}" class="btn btn-sm btn-outline-danger">
                    <i class="la la-download"></i> @lang('student_import.result.download_errors')
                </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>@lang('student_import.preview.col_row')</th>
                            <th>@lang('student_import.preview.col_status')</th>
                            <th>@lang('student_import.preview.col_name')</th>
                            <th>@lang('student_import.preview.col_id')</th>
                            <th>@lang('student_import.preview.col_grade')</th>
                            <th>@lang('student_import.preview.col_class')</th>
                            <th>@lang('student_import.preview.col_reason')</th>
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
                                        {{ __('student_import.preview.status.' . $st) }}
                                    </span>
                                </td>
                                <td>{{ $r['name'] ?? '' }}</td>
                                <td>{{ $r['nationalId'] ?? '' }}</td>
                                <td>{{ $r['grade'] ?? '' }}</td>
                                <td>{{ $r['classRoom'] ?? '' }}</td>
                                <td class="text-danger">{{ $r['reason'] ?? '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="{{ route('admin.users.students.import.form') }}" class="btn btn-outline-secondary">
            <i class="la la-arrow-right"></i> @lang('student_import.preview.back')
        </a>
        @if (($counts['new'] ?? 0) + ($counts['update'] ?? 0) > 0)
            <form method="POST" action="{{ route('admin.users.students.import.execute', $log_id) }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="la la-check"></i> @lang('student_import.preview.execute')
                    ({{ ($counts['new'] ?? 0) + ($counts['update'] ?? 0) }})
                </button>
            </form>
        @else
            <span class="text-muted">@lang('student_import.preview.nothing_valid')</span>
        @endif
    </div>
</div>
@endsection
