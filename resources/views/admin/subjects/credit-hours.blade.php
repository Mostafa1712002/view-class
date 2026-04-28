@extends('layouts.app')

@section('title', __('sprint4.subjects.credit_hours_page.title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.subjects.credit_hours_page.title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.subjects.index') }}">@lang('sprint4.subjects.plural')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.subjects.credit_hours_page.title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p class="text-muted">@lang('sprint4.subjects.credit_hours_page.help')</p>

    <form action="{{ route('admin.subjects.credit-hours.save') }}" method="POST">
        @csrf @method('PATCH')
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>@lang('sprint4.subjects.columns.name')</th>
                            <th>@lang('sprint4.subjects.columns.section')</th>
                            <th style="width: 200px">@lang('sprint4.subjects.columns.credit_hours')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td>
                                    <strong>{{ $subject->name }}</strong>
                                    @if($subject->name_en)<small class="text-muted d-block">{{ $subject->name_en }}</small>@endif
                                </td>
                                <td>{{ $subject->section ?? '—' }}</td>
                                <td>
                                    <input type="number" name="credit_hours[{{ $subject->id }}]" value="{{ $subject->credit_hours }}" min="0" max="20" class="form-control form-control-sm">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">@lang('common.no_results')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary"><i class="la la-save"></i> @lang('sprint4.subjects.credit_hours_page.save')</button>
            </div>
        </div>
    </form>
</div>
@endsection
