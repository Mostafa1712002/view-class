@extends('layouts.app')
@section('title', __('users.permissions_page_title'))
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('users.permissions_page_title')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.teachers.index') }}">@lang('users.teachers')</a></li>
            <li class="breadcrumb-item active">@lang('users.permissions_link')</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-end">
        <a href="{{ route('admin.users.teachers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="la la-arrow-right"></i> @lang('users.teachers')
        </a>
    </div>
</div>

<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <i class="la la-chalkboard-teacher mr-1" style="font-size:1.6rem;color:#cfa046"></i>
                <div>
                    <small class="text-muted d-block">@lang('users.name')</small>
                    <strong style="font-size:1.05rem">{{ $teacher->name }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Add a new assignment --}}
    <div class="card mb-3">
        <div class="card-header bg-white"><strong>@lang('users.assignment_add_title')</strong></div>
        <div class="card-body">
            <form action="{{ route('admin.users.teachers.permissions.store', $teacher->id) }}" method="POST">
                @csrf
                <div class="row align-items-end">
                    <div class="form-group col-md-4">
                        <label>@lang('users.assignment_school')</label>
                        <select name="school_id" class="form-control" required>
                            <option value="">@lang('users.assignment_choose_school')</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>@lang('users.assignment_role')</label>
                        <select name="role_id" class="form-control" required>
                            <option value="">@lang('users.assignment_choose_role')</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>@lang('users.assignment_job_title')</label>
                        <select name="job_title_id" class="form-control">
                            <option value="">@lang('users.assignment_choose_job_title')</option>
                            @foreach($jobTitles as $jt)
                                <option value="{{ $jt->id }}">{{ $jt->name_ar }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button class="btn btn-primary btn-block" type="submit">
                            <i class="la la-plus"></i> @lang('users.add')
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Current assignments --}}
    <div class="card">
        <div class="card-header bg-white"><strong>@lang('users.assignment_current_title')</strong></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>@lang('users.assignment_schools')</th>
                        <th>@lang('users.assignment_groups')</th>
                        <th>@lang('users.assignment_job_title')</th>
                        <th class="text-end">@lang('users.assignment_manage')</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($assignments as $a)
                    <tr>
                        <td>{{ $a->school->name ?? '—' }}</td>
                        <td>{{ $a->role->name ?? '—' }}</td>
                        <td>{{ $a->jobTitle->name_ar ?? '-' }}</td>
                        <td class="text-end">
                            <form action="{{ route('admin.users.teachers.permissions.destroy', [$teacher->id, $a->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.assignment_delete_confirm')');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="@lang('users.delete')"><i class="la la-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">@lang('users.assignment_none')</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
