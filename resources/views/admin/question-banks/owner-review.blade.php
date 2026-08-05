@extends('layouts.app')

@section('title', __('question_banks.owner_review_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('question_banks.owner_review_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('question_banks.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('question_banks.owner_review_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">@lang('question_banks.filter_school')</label>
                    <select name="school_id" class="form-control" onchange="this.form.submit()">
                        <option value="">@lang('question_banks.filter_all_schools')</option>
                        @foreach($schools as $s)
                            <option value="{{ $s->id }}" @selected($schoolFilter == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
            <p class="text-muted mt-2 mb-0"><i class="la la-info-circle"></i> @lang('question_banks.owner_review_hint')</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            @if($banks->isEmpty())
                <p class="text-muted mb-0">@lang('question_banks.owner_review_empty')</p>
            @else
            <table class="table table-hover">
                <thead><tr>
                    <th>@lang('question_banks.col_school')</th>
                    <th>@lang('question_banks.col_name')</th>
                    <th>@lang('question_banks.col_subject')</th>
                    <th>@lang('question_banks.col_questions')</th>
                    <th></th>
                </tr></thead>
                <tbody>
                @foreach($banks as $bank)
                    <tr>
                        <td>{{ optional($bank->school)->name ?? '—' }}</td>
                        <td>{{ $bank->name_ar }}</td>
                        <td>{{ $bank->subjects->pluck('name')->join('، ') ?: '—' }}</td>
                        <td><span class="badge badge-info">{{ $bank->questions_count }}</span></td>
                        <td>
                            <a href="{{ route('admin.question-banks.questions.index', $bank->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="la la-eye"></i> @lang('question_banks.owner_review_view')
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{ $banks->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
