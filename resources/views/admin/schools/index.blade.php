@extends('layouts.app')

@section('title', 'إدارة المدارس')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">المدارس</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item active">المدارس</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('admin.schools.create') }}" class="btn btn-primary">
            <i data-feather="plus"></i> إضافة مدرسة
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
                            <th>اسم المدرسة</th>
                            <th>الرمز</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>الأقسام</th>
                            <th>المستخدمين</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $school->name }}</td>
                            <td>{{ $school->code }}</td>
                            <td>{{ $school->email ?? '-' }}</td>
                            <td>{{ $school->phone ?? '-' }}</td>
                            <td>{{ $school->sections_count }}</td>
                            <td>{{ $school->users_count }}</td>
                            <td>
                                @if($school->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">معطل</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-sm btn-info">
                                        <i data-feather="eye"></i>
                                    </a>
                                    <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-sm btn-warning">
                                        <i data-feather="edit"></i>
                                    </a>
                                    <form action="{{ route('admin.schools.destroy', $school) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟')">
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
                            <td colspan="9" class="text-center">لا توجد مدارس</td>
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
