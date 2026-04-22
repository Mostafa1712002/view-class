@extends('layouts.app')

@section('title', __('common.schools'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('common.schools')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item active">@lang('common.schools')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('admin.schools.create') }}" class="btn btn-primary">
            <i data-feather="plus"></i> @lang('common.create')
        </a>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>@lang('common.school')</th>
                            <th>@lang('common.id')</th>
                            <th>@lang('common.email')</th>
                            <th>@lang('common.phone')</th>
                            <th>@lang('common.sections')</th>
                            <th>@lang('common.users')</th>
                            <th>@lang('common.status')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ app()->getLocale() === 'en' ? ($school->name_en ?: $school->name_ar ?: $school->name) : ($school->name_ar ?: $school->name) }}</td>
                            <td>{{ $school->code }}</td>
                            <td>{{ $school->email ?? '-' }}</td>
                            <td>{{ $school->phone ?? '-' }}</td>
                            <td>{{ $school->sections_count }}</td>
                            <td>{{ $school->users_count }}</td>
                            <td>
                                @if($school->is_active)
                                    <span class="badge bg-success">@lang('common.active')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('common.inactive')</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-sm btn-info" title="@lang('common.view')">
                                        <i data-feather="eye"></i>
                                    </a>
                                    <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-sm btn-warning" title="@lang('common.edit')">
                                        <i data-feather="edit"></i>
                                    </a>
                                    <form action="{{ route('admin.schools.destroy', $school) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="@lang('common.delete')">
                                            <i data-feather="trash-2"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">@lang('common.no_data')</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $schools->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
