@extends('layouts.app')

@section('title', __('sprint4.question_banks.page_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.question_banks.index_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.question_banks.index_title')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.question-banks.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm me-1" placeholder="@lang('sprint4.subjects.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm" type="submit"><i class="la la-search"></i></button>
        </form>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <a class="btn btn-primary btn-sm" href="{{ route('admin.question-banks.create') }}">
                    <i class="la la-plus"></i> @lang('sprint4.question_banks.add_btn')
                </a>
                <a class="btn btn-outline-secondary btn-sm ms-1" href="{{ route('admin.question-banks.library') }}">
                    <i class="la la-book"></i> @lang('sprint4.question_banks.library')
                </a>
            </div>
            <div class="text-muted small">{{ $banks->total() }} @lang('sprint4.question_banks.index_title')</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('sprint4.question_banks.columns.name')</th>
                        <th>@lang('sprint4.question_banks.columns.subjects')</th>
                        <th>@lang('sprint4.question_banks.columns.questions_count')</th>
                        <th>@lang('sprint4.question_banks.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banks as $bank)
                        <tr>
                            <td>
                                <strong>{{ $bank->name_ar }}</strong>
                                @if($bank->name_en)<small class="text-muted d-block">{{ $bank->name_en }}</small>@endif
                            </td>
                            <td>
                                @foreach($bank->subjects as $s)
                                    <span class="badge bg-light text-dark">{{ $s->name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $bank->questions_count }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.question-banks.questions.index', $bank->id) }}">
                                    <i class="la la-list"></i> @lang('sprint4.question_banks.questions.page_title')
                                </a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.question-banks.edit', $bank->id) }}">
                                    <i class="la la-pen"></i>
                                </a>
                                <form action="{{ route('admin.question-banks.destroy', $bank->id) }}" method="POST" class="d-inline" onsubmit="return confirm('?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">@lang('common.no_results')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $banks->links() }}</div>
    </div>
</div>
@endsection
