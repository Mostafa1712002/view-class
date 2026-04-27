@extends('layouts.app')

@section('title', __('users.students'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.students')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('users.students')</li>
            </ol>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <form action="{{ route('admin.users.students.index') }}" method="GET" class="d-flex">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm me-1" placeholder="@lang('users.search_placeholder')" />
            <button class="btn btn-outline-primary btn-sm" type="submit"><i class="la la-search"></i></button>
        </form>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-header d-flex flex-wrap gap-1 justify-content-between align-items-center">
            <div class="btn-group btn-group-sm" role="group">
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        <i class="la la-plus"></i> @lang('users.add_student') ▼
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{{ route('admin.users.students.create') }}"><i class="la la-user-plus"></i> @lang('users.add_student')</a>
                        <a class="dropdown-item" href="#" data-action="excel"><i class="la la-file-excel"></i> @lang('users.import_excel')</a>
                        <a class="dropdown-item disabled" href="#"><i class="la la-cloud-download-alt"></i> @lang('users.import_noor')</a>
                        <a class="dropdown-item disabled" href="#"><i class="la la-images"></i> @lang('users.import_photos')</a>
                        <a class="dropdown-item disabled" href="#"><i class="la la-sync"></i> @lang('users.refresh_status')</a>
                    </div>
                </div>
                <div class="dropdown ms-1">
                    <button class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        @lang('users.other_options') ▼
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item disabled" href="#">@lang('users.graduates')</a>
                        <a class="dropdown-item disabled" href="#">@lang('users.delete_graduates')</a>
                        <a class="dropdown-item disabled" href="#">@lang('users.advanced_list')</a>
                        <a class="dropdown-item disabled" href="#">@lang('users.counts')</a>
                        <a class="dropdown-item disabled" href="#">@lang('users.unlinked_to_parents')</a>
                        <a class="dropdown-item disabled" href="#">@lang('users.global_search')</a>
                    </div>
                </div>
                <div class="dropdown ms-1">
                    <button class="btn btn-outline-warning dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                        @lang('users.operations') ▼
                    </button>
                    <div class="dropdown-menu">
                        @foreach(['hide_grades','show_grades','hide_report','show_report','license','unlicense','waiting'] as $op)
                            <button type="button" class="dropdown-item js-bulk" data-op="{{ $op }}">@lang('users.op_'.$op)</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="text-muted small">{{ $students->total() }} @lang('users.students')</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th><input type="checkbox" id="js-check-all" /></th>
                        <th>@lang('users.national_id')</th>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.grade_level')</th>
                        <th>@lang('users.class')</th>
                        <th>@lang('users.gender')</th>
                        <th>@lang('users.last_activity')</th>
                        <th>@lang('users.actions')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($students as $u)
                    <tr>
                        <td><input type="checkbox" class="js-row-check" value="{{ $u->id }}" /></td>
                        <td>{{ $u->national_id ?? '—' }}</td>
                        <td>
                            <strong>{{ $u->name }}</strong><br>
                            <small class="text-muted">{{ $u->username }}</small>
                        </td>
                        <td>{{ optional($u->section)->name ?? '—' }}</td>
                        <td>{{ optional($u->classRoom)->name ?? '—' }}</td>
                        <td>{{ $u->gender ? __('users.gender_'.$u->gender) : '—' }}</td>
                        <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.users.students.edit', $u->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('users.edit')"><i class="la la-edit"></i></a>
                            <form action="{{ route('admin.users.students.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.delete')?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="@lang('users.delete')"><i class="la la-trash"></i></button>
                            </form>
                            <div class="dropdown d-inline">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown">
                                    <i class="la la-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu">
                                    @if(auth()->user()->isSuperAdmin())
                                    <form action="{{ route('admin.users.impersonate.start', $u->id) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item" type="submit"><i class="la la-user-secret"></i> @lang('users.login_as')</button>
                                    </form>
                                    @endif
                                    <a class="dropdown-item disabled" href="#"><i class="la la-user-friends"></i> @lang('users.parents_link')</a>
                                    <a class="dropdown-item disabled" href="#"><i class="la la-calendar"></i> @lang('users.schedule_link')</a>
                                    <a class="dropdown-item disabled" href="#"><i class="la la-chalkboard"></i> @lang('users.classes_link')</a>
                                    <a class="dropdown-item disabled" href="#"><i class="la la-times-circle"></i> @lang('users.absences_link')</a>
                                    <a class="dropdown-item disabled" href="#"><i class="la la-balance-scale"></i> @lang('users.behavior_link')</a>
                                    <a class="dropdown-item disabled" href="#"><i class="la la-notes-medical"></i> @lang('users.medical_link')</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">@lang('users.no_results')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $students->links() }}</div>
    </div>
</div>

<form id="js-bulk-form" action="{{ route('admin.users.students.bulk') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="action" id="js-bulk-action" />
    <input type="hidden" name="ids" id="js-bulk-ids" />
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var checkAll = document.getElementById('js-check-all');
    var rows = document.querySelectorAll('.js-row-check');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rows.forEach(function (r) { r.checked = checkAll.checked; });
        });
    }
    document.querySelectorAll('.js-bulk').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var ids = [];
            rows.forEach(function (r) { if (r.checked) ids.push(r.value); });
            if (ids.length === 0) { alert('@lang('users.no_results')'); return; }
            if (!confirm('@lang('users.operations'): ' + btn.textContent.trim() + ' (' + ids.length + ')')) return;
            var f = document.getElementById('js-bulk-form');
            document.getElementById('js-bulk-action').value = btn.dataset.op;
            // Build hidden inputs for ids[]
            // remove old
            f.querySelectorAll('input[name="ids[]"]').forEach(function (el) { el.remove(); });
            ids.forEach(function (id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                f.appendChild(inp);
            });
            f.submit();
        });
    });
});
</script>
@endsection
