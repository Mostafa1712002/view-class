@extends('layouts.app')

@section('title', __('sprint4.subjects.page_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('sprint4.subjects.index_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('sprint4.subjects.plural')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.subjects.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm me-1" placeholder="@lang('sprint4.subjects.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm" type="submit"><i class="la la-search"></i></button>
        </form>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-1 justify-content-between align-items-center">
            <div class="btn-group btn-group-sm" role="group">
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        <i class="la la-plus"></i> @lang('sprint4.subjects.add') ▼
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('admin.subjects.create') }}"><i class="la la-pen"></i> @lang('sprint4.subjects.add_manual')</a>
                        <a class="dropdown-item disabled" href="#"><i class="la la-file-excel"></i> @lang('sprint4.subjects.add_excel')</a>
                        <a class="dropdown-item disabled" href="#"><i class="la la-cloud-download-alt"></i> @lang('sprint4.subjects.add_template') ({{ $templatesCount }})</a>
                    </div>
                </div>
                <a class="btn btn-outline-secondary ms-1" href="{{ route('admin.subjects.credit-hours') }}">
                    <i class="la la-clock"></i> @lang('sprint4.subjects.set_credit_hours')
                </a>
                <button class="btn btn-outline-secondary ms-1" disabled>
                    <i class="la la-layer-group"></i> @lang('sprint4.subjects.subject_sections')
                </button>
            </div>
            <div class="text-muted small">{{ $subjects->total() }} @lang('sprint4.subjects.plural')</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 30px"><input type="checkbox" id="js-check-all" /></th>
                        <th>@lang('sprint4.subjects.columns.name')</th>
                        <th>@lang('sprint4.subjects.columns.grade')</th>
                        <th>@lang('sprint4.subjects.columns.section')</th>
                        <th>@lang('sprint4.subjects.columns.credit_hours')</th>
                        <th>@lang('sprint4.subjects.columns.certificate_order')</th>
                        <th>@lang('sprint4.subjects.columns.source')</th>
                        <th>@lang('sprint4.subjects.columns.is_active')</th>
                        <th>@lang('sprint4.subjects.columns.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subjects as $subject)
                        <tr>
                            <td><input type="checkbox" class="js-row" value="{{ $subject->id }}" /></td>
                            <td>
                                <strong>{{ $subject->name }}</strong>
                                @if($subject->name_en)<small class="text-muted d-block">{{ $subject->name_en }}</small>@endif
                                @if($subject->code)<span class="badge bg-light text-dark">{{ $subject->code }}</span>@endif
                            </td>
                            <td>
                                @foreach($subject->grade_levels ?? [] as $level)
                                    <span class="badge bg-secondary">{{ $level }}</span>
                                @endforeach
                            </td>
                            <td>{{ $subject->section ?? '—' }}</td>
                            <td>{{ $subject->credit_hours ?? '—' }}</td>
                            <td>{{ $subject->certificate_order }}</td>
                            <td><span class="badge bg-info">@lang('sprint4.subjects.sources.' . $subject->source)</span></td>
                            <td>
                                @if($subject->is_active)
                                    <span class="badge bg-success">@lang('sprint4.subjects.columns.is_active')</span>
                                @else
                                    <span class="badge bg-warning">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">⋯</button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('admin.subjects.edit', $subject->id) }}"><i class="la la-pen"></i> @lang('sprint4.subjects.edit')</a>
                                        <a class="dropdown-item" href="{{ route('admin.subjects.lesson-tree', $subject->id) }}"><i class="la la-tree"></i> @lang('sprint4.subjects.lesson_tree') ({{ $subject->units_count }})</a>
                                        <a class="dropdown-item disabled" href="#"><i class="la la-list"></i> @lang('sprint4.subjects.standards')</a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('admin.subjects.destroy', $subject->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger"><i class="la la-trash"></i> @lang('sprint4.subjects.delete')</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">@lang('common.no_results')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $subjects->links() }}</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var all = document.getElementById('js-check-all');
    if (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('.js-row').forEach(function (cb) { cb.checked = all.checked; });
        });
    }
});
</script>
@endsection
