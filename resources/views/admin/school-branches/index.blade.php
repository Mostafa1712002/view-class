@extends('layouts.app')

@section('title', __('school_branches.title'))

@section('body_class', 'theme-light')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('school_branches.title')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.schools.index') }}">@lang('schools.title')</a></li>
                        <li class="breadcrumb-item active">@lang('school_branches.breadcrumb')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right col-md-4 col-12">
        <div class="d-flex justify-content-md-end">
            <a href="{{ route('admin.school-branches.create') }}" class="btn btn-primary btn-sm">
                <i class="la la-plus"></i> @lang('school_branches.add_branch')
            </a>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('school_branches.name_ar')</th>
                            <th>@lang('school_branches.name_en')</th>
                            <th>@lang('school_branches.sort_order')</th>
                            <th>@lang('school_branches.schools_count')</th>
                            <th>@lang('school_branches.is_active')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $branch->name_ar }}</strong></td>
                            <td>{{ $branch->name_en }}</td>
                            <td>{{ $branch->sort_order ?? '-' }}</td>
                            <td>{{ $branch->schools_count ?? 0 }}</td>
                            <td>
                                @if($branch->is_active)
                                    <span class="badge bg-success">@lang('common.active')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('common.inactive')</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.school-branches.edit', $branch->id) }}" class="btn btn-sm btn-outline-primary" title="@lang('common.edit')">
                                    <i class="la la-pen"></i>
                                </a>
                                <form action="{{ route('admin.school-branches.destroy', $branch->id) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="@lang('common.delete')">
                                        <i class="la la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">@lang('school_branches.no_branches')</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $branches->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
