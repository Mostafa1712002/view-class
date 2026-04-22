@extends('layouts.app')

@section('title', __('common.classes'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('common.classes')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item active">@lang('common.classes')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.classes.create') }}" class="btn btn-primary">
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
                            <th>@lang('common.classroom')</th>
                            <th>القسم</th>
                            <th>السنة الدراسية</th>
                            <th>الصف</th>
                            <th>الشعبة</th>
                            <th>السعة</th>
                            <th>@lang('common.status')</th>
                            <th>@lang('common.actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $class)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $class->name }}</td>
                            <td>{{ $class->section->name ?? '-' }}</td>
                            <td>{{ $class->academicYear->name ?? '-' }}</td>
                            <td>{{ $class->grade_level_label }}</td>
                            <td>{{ $class->division }}</td>
                            <td>{{ $class->capacity }}</td>
                            <td>
                                @if($class->is_active)
                                    <span class="badge bg-success">@lang('common.active')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('common.inactive')</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('manage.classes.show', $class) }}" class="btn btn-sm btn-info">
                                        <i data-feather="eye"></i>
                                    </a>
                                    <a href="{{ route('manage.classes.edit', $class) }}" class="btn btn-sm btn-warning">
                                        <i data-feather="edit"></i>
                                    </a>
                                    <form action="{{ route('manage.classes.destroy', $class) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
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
            <div class="mt-3">{{ $classes->links() }}</div>
        </div>
    </div>
</div>
@endsection
