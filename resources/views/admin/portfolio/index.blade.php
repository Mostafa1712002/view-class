@extends('layouts.admin')

@section('title', 'إدارة ملف أنجاز')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">إدارة ملف أنجاز</h1>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'teacher' ? 'active' : '' }}" href="{{ route('admin.portfolio.index', ['tab' => 'teacher']) }}">ملف أنجاز معلم</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'student' ? 'active' : '' }}" href="{{ route('admin.portfolio.index', ['tab' => 'student']) }}">ملف أنجاز طالب</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            @if($users->isEmpty())
                <p class="text-muted text-center py-4 mb-0">لا يوجد {{ $tab === 'teacher' ? 'معلمون' : 'طلاب' }}</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->phone ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.portfolio.show', $u) }}" class="btn btn-sm btn-primary">عرض الملف</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
