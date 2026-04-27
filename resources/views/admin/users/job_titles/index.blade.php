@extends('layouts.app')
@section('title', __('users.job_titles'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.job_titles')</h2></div>
<div class="content-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="row">
        <div class="col-md-7">
            <div class="card"><div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light"><tr>
                        <th>@lang('users.jt_slug')</th>
                        <th>@lang('users.name_ar')</th>
                        <th>@lang('users.name')</th>
                        <th>@lang('users.jt_active')</th>
                        <th>@lang('users.jt_sort_order')</th>
                        <th>@lang('users.actions')</th>
                    </tr></thead>
                    <tbody>
                    @forelse($jobTitles as $jt)
                        <tr>
                            <td><code>{{ $jt->slug }}</code></td>
                            <td>{{ $jt->name_ar }}</td>
                            <td>{{ $jt->name_en }}</td>
                            <td>
                                @if($jt->is_active)<span class="badge bg-success">●</span>@else<span class="badge bg-secondary">○</span>@endif
                                @if($jt->school_id === null)<span class="badge bg-info ms-1">@lang('users.jt_global')</span>
                                @else<span class="badge bg-warning ms-1">@lang('users.jt_school')</span>@endif
                            </td>
                            <td>{{ $jt->sort_order }}</td>
                            <td>
                                @if($jt->school_id !== null || auth()->user()->isSuperAdmin())
                                <form action="{{ route('admin.users.job-titles.destroy', $jt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('@lang('users.delete')?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="la la-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">@lang('users.no_results')</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>

        <div class="col-md-5">
            <div class="card"><div class="card-body">
                <h5 class="card-title">@lang('users.jt_create_form')</h5>
                <form action="{{ route('admin.users.job-titles.store') }}" method="POST">
                    @csrf
                    <div class="form-group"><label>@lang('users.jt_slug') *</label>
                        <input type="text" name="slug" class="form-control" required pattern="[a-z0-9_-]+" /></div>
                    <div class="form-group"><label>@lang('users.name_ar') *</label>
                        <input type="text" name="name_ar" class="form-control" required /></div>
                    <div class="form-group"><label>@lang('users.name') (EN) *</label>
                        <input type="text" name="name_en" class="form-control" required /></div>
                    <div class="form-group"><label>@lang('users.jt_sort_order')</label>
                        <input type="number" name="sort_order" class="form-control" min="0" value="0" /></div>
                    <div class="form-check mb-2">
                        <input type="checkbox" id="active" name="is_active" value="1" checked class="form-check-input" />
                        <label for="active" class="form-check-label">@lang('users.jt_active')</label>
                    </div>
                    <button class="btn btn-primary btn-block"><i class="la la-save"></i> @lang('users.save')</button>
                </form>
                @if($errors->any())<div class="alert alert-danger mt-2"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            </div></div>
        </div>
    </div>
</div>
@endsection
