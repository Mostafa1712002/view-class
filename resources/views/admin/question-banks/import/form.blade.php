@extends('layouts.app')
@section('title', __('question_import.page_title') . ' — ' . $bank->name_ar)
@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('question_import.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('questions.breadcrumb.banks')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.questions.index', $bank->id) }}">{{ $bank->name_ar }}</a></li>
                <li class="breadcrumb-item active">@lang('question_import.breadcrumb')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pr-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header banner --}}
    <div class="card" style="background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="mb-0"><i class="la la-file-excel"></i> @lang('question_import.upload_card_title')</h4>
            <a href="{{ route('admin.question-banks.questions.import.template', $bank->id) }}"
               class="btn btn-light btn-sm">
                <i class="la la-download"></i> @lang('question_import.download_template')
            </a>
        </div>
    </div>

    <div class="row">
        {{-- Upload form --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <p class="text-muted">@lang('question_import.upload_help')</p>
                    <form method="POST"
                          action="{{ route('admin.question-banks.questions.import.preview', $bank->id) }}"
                          enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label" for="file">
                                @lang('question_import.file_label') <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="file" id="file"
                                   class="form-control" accept=".xlsx,.xls,.csv" required>
                            <small class="text-muted d-block mt-1">@lang('question_import.file_hint')</small>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="la la-eye"></i> @lang('question_import.read_file')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Instructions sidebar --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('question_import.columns_title')</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>@lang('question_import.col_name')</th>
                                <th>@lang('question_import.col_required')</th>
                                <th>@lang('question_import.col_notes')</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>question_code</td><td>@lang('common.no')</td><td>@lang('question_import.col_notes_code')</td></tr>
                            <tr><td>question_type</td><td><span class="text-danger">*</span></td><td>mcq | true_false | short | essay | matching | fill_blank</td></tr>
                            <tr><td>question_content_type</td><td>@lang('common.no')</td><td>text | image | mixed (default: text)</td></tr>
                            <tr><td>question_text</td><td><span class="text-danger">*</span></td><td>@lang('question_import.col_notes_text')</td></tr>
                            <tr><td>option_a … option_d</td><td>@lang('common.no')</td><td>@lang('question_import.col_notes_options')</td></tr>
                            <tr><td>correct_answer</td><td>@lang('common.no')</td><td>A|B|C|D أو true|false أو النص</td></tr>
                            <tr><td>difficulty</td><td>@lang('common.no')</td><td>سهل | متوسط | صعب</td></tr>
                            <tr><td>explanation</td><td>@lang('common.no')</td><td>@lang('question_import.col_notes_explanation')</td></tr>
                            <tr><td>grade</td><td>@lang('common.no')</td><td>@lang('question_import.col_notes_grade')</td></tr>
                            <tr><td>semester</td><td>@lang('common.no')</td><td>@lang('question_import.col_notes_semester')</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Import history --}}
    @if ($history->isNotEmpty())
        <div class="card" id="import-history">
            <div class="card-header">
                <h5 class="card-title mb-0">@lang('question_import.history_title')</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('question_import.history_filename')</th>
                                <th>@lang('question_import.history_status')</th>
                                <th>@lang('question_import.history_total')</th>
                                <th>@lang('question_import.history_imported')</th>
                                <th>@lang('question_import.history_failed')</th>
                                <th>@lang('question_import.history_date')</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $h)
                                <tr>
                                    <td>{{ $h->id }}</td>
                                    <td>{{ $h->original_filename }}</td>
                                    <td>
                                        <span class="badge
                                            @if($h->status === 'completed') badge-success
                                            @elseif($h->status === 'previewed') badge-warning
                                            @elseif($h->status === 'failed') badge-danger
                                            @else badge-secondary @endif">
                                            @lang('question_import.status.' . $h->status)
                                        </span>
                                    </td>
                                    <td>{{ $h->total_rows }}</td>
                                    <td class="text-success">{{ $h->imported_rows }}</td>
                                    <td class="text-danger">{{ $h->failed_rows }}</td>
                                    <td>{{ $h->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($h->failed_rows > 0 && $h->status === 'completed')
                                            <a href="{{ route('admin.question-banks.questions.import.errors', [$bank->id, $h->id]) }}"
                                               class="btn btn-outline-danger btn-sm">
                                                <i class="la la-download"></i> @lang('question_import.result.download_errors')
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
