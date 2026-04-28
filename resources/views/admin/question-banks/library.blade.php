@extends('layouts.app')

@section('title', __('sprint4.question_banks.library_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.question_banks.library_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('sprint4.question_banks.index_title')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.question_banks.library')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
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
                                <form action="{{ route('admin.question-banks.library.clone', $bank->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="la la-copy"></i> @lang('sprint4.question_banks.clone')
                                    </button>
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
