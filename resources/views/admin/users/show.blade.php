@extends('layouts.app')

@section('title', 'تفاصيل المستخدم')

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-9 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-right mb-0">{{ $user->name }}</h2>
                <div class="breadcrumb-wrapper">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('manage.users.index') }}">المستخدمين</a></li>
                        <li class="breadcrumb-item active">التفاصيل</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="content-header-right text-md-left col-md-3 col-12">
        <a href="{{ route('manage.users.edit', $user) }}" class="btn btn-warning">
            <i data-feather="edit"></i> تعديل
        </a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">معلومات المستخدم</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>الاسم</th>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th>البريد الإلكتروني</th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th>الهاتف</th>
                            <td>{{ $user->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>الجنس</th>
                            <td>{{ $user->gender == 'male' ? 'ذكر' : ($user->gender == 'female' ? 'أنثى' : '-') }}</td>
                        </tr>
                        <tr>
                            <th>المدرسة</th>
                            <td>{{ $user->school->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>القسم</th>
                            <td>{{ $user->section->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>الحالة</th>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">معطل</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">الأدوار والصلاحيات</h4>
                </div>
                <div class="card-body">
                    <h5>الأدوار:</h5>
                    @foreach($user->roles as $role)
                        <span class="badge bg-primary mb-2">{{ $role->name }}</span>
                    @endforeach

                    @if($user->isTeacher() && $user->subjects->count() > 0)
                    <hr>
                    <h5>المواد:</h5>
                    <ul class="list-group">
                        @foreach($user->subjects as $subject)
                            <li class="list-group-item">{{ $subject->name }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
