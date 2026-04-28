@extends('layouts.app')

@section('title', __('sprint4.question_banks.questions.page_title') . ' — ' . $bank->name_ar)

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $bank->name_ar }} — @lang('sprint4.question_banks.questions.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.question-banks.index') }}">@lang('sprint4.question_banks.index_title')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.question_banks.questions.page_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <a class="btn btn-primary btn-sm" href="{{ route('admin.question-banks.questions.create', $bank->id) }}">
                <i class="la la-plus"></i> @lang('sprint4.question_banks.questions.add')
            </a>
            <div class="text-muted small">{{ $questions->total() }}</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('sprint4.question_banks.questions.columns.body')</th>
                        <th>@lang('sprint4.question_banks.questions.columns.type')</th>
                        <th>@lang('sprint4.question_banks.questions.columns.difficulty')</th>
                        <th>@lang('sprint4.question_banks.questions.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questions as $q)
                        <tr>
                            <td>{!! nl2br(e(\Illuminate\Support\Str::limit($q->body_ar, 200))) !!}</td>
                            <td><span class="badge bg-info">@lang('sprint4.question_banks.questions.types.' . $q->type)</span></td>
                            <td>{{ $q->difficulty ?? '—' }}</td>
                            <td>
                                <form action="{{ route('admin.question-banks.questions.destroy', [$bank->id, $q->id]) }}" method="POST" onsubmit="return confirm('?')">
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
        <div class="card-footer">{{ $questions->links() }}</div>
    </div>
</div>
@endsection
