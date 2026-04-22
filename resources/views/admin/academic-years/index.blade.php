@extends('layouts.app')

@section('title', __('common.academic_years'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} mb-0">@lang('common.academic_years')</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                        <li class="breadcrumb-item active">@lang('common.academic_years')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.academic-years.create') }}" class="btn btn-primary"><i data-feather="plus"></i> @lang('common.create')</a>
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
                            <th>اسم السنة</th>
                            @if(Auth::user()->isSuperAdmin())<th>المدرسة</th>@endif
                            <th>تاريخ البداية</th>
                            <th>تاريخ النهاية</th>
                            <th>الفصول</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($academicYears as $year)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $year->name }} @if($year->is_current)<span class="badge bg-success">الحالية</span>@endif</td>
                            @if(Auth::user()->isSuperAdmin())<td>{{ $year->school->name ?? '-' }}</td>@endif
                            <td>{{ $year->start_date->format('Y-m-d') }}</td>
                            <td>{{ $year->end_date->format('Y-m-d') }}</td>
                            <td>{{ $year->classes_count }}</td>
                            <td>@if($year->is_current)<span class="badge bg-success">حالية</span>@else<span class="badge bg-secondary">سابقة</span>@endif</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('manage.academic-years.show', $year) }}" class="btn btn-sm btn-info"><i data-feather="eye"></i></a>
                                    <a href="{{ route('manage.academic-years.edit', $year) }}" class="btn btn-sm btn-warning"><i data-feather="edit"></i></a>
                                    <form action="{{ route('manage.academic-years.destroy', $year) }}" method="POST" class="d-inline" onsubmit="return confirm(@json(__('common.confirm_delete')))">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i data-feather="trash-2"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ Auth::user()->isSuperAdmin() ? 8 : 7 }}" class="text-center">لا توجد سنوات دراسية</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $academicYears->links() }}</div>
        </div>
    </div>
</div>
@endsection
