@extends('layouts.app')

@section('title', 'إدارة الأقسام')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">الأقسام</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">الأقسام</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.sections.create') }}" class="btn btn-primary">
            <i data-feather="plus"></i> إضافة قسم
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
                            <th>اسم القسم</th>
                            @if(Auth::user()->isSuperAdmin())
                            <th>المدرسة</th>
                            @endif
                            <th>الجنس</th>
                            <th>المرحلة</th>
                            <th>الفصول</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $section->name }}</td>
                            @if(Auth::user()->isSuperAdmin())
                            <td>{{ $section->school->name ?? '-' }}</td>
                            @endif
                            <td>{{ $section->gender_label }}</td>
                            <td>{{ $section->level_label }}</td>
                            <td>{{ $section->classes_count }}</td>
                            <td>
                                @if($section->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">معطل</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('manage.sections.show', $section) }}" class="btn btn-sm btn-info">
                                        <i data-feather="eye"></i>
                                    </a>
                                    <a href="{{ route('manage.sections.edit', $section) }}" class="btn btn-sm btn-warning">
                                        <i data-feather="edit"></i>
                                    </a>
                                    <form action="{{ route('manage.sections.destroy', $section) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
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
                            <td colspan="{{ Auth::user()->isSuperAdmin() ? 8 : 7 }}" class="text-center">لا توجد أقسام</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $sections->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
