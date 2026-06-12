@extends('layouts.app')
@section('title', __('question_import.preview.title'))
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('question_import.preview.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('questions.breadcrumb.banks')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.questions.index', $bank->id) }}">{{ $bank->name_ar }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.questions.import.form', $bank->id) }}">@lang('question_import.breadcrumb')</a></li>
                <li class="breadcrumb-item active">@lang('question_import.preview.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    {{-- Summary count cards --}}
    <div class="row mb-3">
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#ecfdf5;">
                <div class="card-body">
                    <div class="text-muted">@lang('question_import.preview.summary_valid')</div>
                    <h2 class="fw-bold mb-0" style="color:#16a34a;">{{ $counts['valid'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#fef2f2;">
                <div class="card-body">
                    <div class="text-muted">@lang('question_import.preview.summary_invalid')</div>
                    <h2 class="fw-bold mb-0" style="color:#dc2626;">{{ $counts['invalid'] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted">@lang('question_import.preview.summary_total')</div>
                    <h2 class="fw-bold mb-0">{{ count($rows) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-center" style="background:#eff6ff;">
                <div class="card-body">
                    <div class="text-muted">@lang('question_import.preview.summary_bank')</div>
                    <h5 class="fw-bold mb-0" style="color:#1d4ed8;">{{ $bank->name_ar }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">@lang('question_import.preview.title')</h5>
            @if (($counts['invalid'] ?? 0) > 0)
                <a href="{{ route('admin.question-banks.questions.import.errors', [$bank->id, $batch->id]) }}"
                   class="btn btn-sm btn-outline-danger">
                    <i class="la la-download"></i> @lang('question_import.result.download_errors')
                </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>@lang('question_import.preview.col_row')</th>
                            <th>@lang('question_import.preview.col_status')</th>
                            <th>@lang('question_import.preview.col_type')</th>
                            <th>@lang('question_import.preview.col_text')</th>
                            <th>@lang('question_import.preview.col_difficulty')</th>
                            <th>@lang('question_import.preview.col_errors')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $r)
                            @php $st = $r['status'] ?? 'valid'; @endphp
                            <tr @if($st === 'invalid') style="background:#fef2f2;" @endif>
                                <td>{{ $r['rowNumber'] ?? '' }}</td>
                                <td>
                                    <span class="badge {{ $st === 'valid' ? 'badge-success' : 'badge-danger' }}">
                                        @lang('question_import.preview.status.' . $st)
                                    </span>
                                </td>
                                <td>{{ $r['question_type'] ?? '' }}</td>
                                <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ Str::limit($r['question_text'] ?? $r['question_code'] ?? '—', 60) }}
                                </td>
                                <td>{{ $r['difficulty_raw'] ?? '' }}</td>
                                <td class="text-danger small">
                                    @if(!empty($r['errors']))
                                        {{ implode(' | ', (array)$r['errors']) }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="{{ route('admin.question-banks.questions.import.form', $bank->id) }}"
           class="btn btn-outline-secondary">
            <i class="la la-arrow-{{ app()->isLocale('ar') ? 'right' : 'left' }}"></i>
            @lang('question_import.preview.back')
        </a>

        @if (($counts['valid'] ?? 0) > 0)
            <form method="POST"
                  action="{{ route('admin.question-banks.questions.import.execute', [$bank->id, $batch->id]) }}">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="la la-check"></i> @lang('question_import.preview.execute')
                    ({{ $counts['valid'] ?? 0 }})
                </button>
            </form>
        @else
            <span class="text-muted">@lang('question_import.preview.nothing_valid')</span>
        @endif
    </div>
</div>
@endsection
