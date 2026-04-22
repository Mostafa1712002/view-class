@extends('layouts.admin')

@section('title', 'سجل النشاطات')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">سجل النشاطات</h1>
        @if(auth()->user()->isSuperAdmin())
        <form action="{{ route('admin.activity-logs.clear') }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد؟')">
            @csrf
            <div class="input-group">
                <input type="number" name="days" value="30" min="1" class="form-control form-control-sm" style="width: 80px;">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="la la-trash me-1"></i>حذف الأقدم من (يوم)
                </button>
            </div>
        </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <form action="{{ route('admin.activity-logs.index') }}" method="GET" class="row g-3">
                <div class="col-md-2">
                    <select name="action" class="form-select form-select-sm">
                        <option value="">كل الإجراءات</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ match($action) {
                                    'create' => 'إنشاء',
                                    'update' => 'تعديل',
                                    'delete' => 'حذف',
                                    'login' => 'دخول',
                                    'logout' => 'خروج',
                                    default => $action
                                } }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="من تاريخ">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="إلى تاريخ">
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="بحث في الوصف...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="la la-search me-1"></i>بحث
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;"></th>
                            <th>المستخدم</th>
                            <th>الإجراء</th>
                            <th>@lang('common.description')</th>
                            <th>IP</th>
                            <th>@lang('common.created_at')</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $log->action_color }}">
                                    <i class="la {{ $log->action_icon }}"></i>
                                </span>
                            </td>
                            <td>
                                @if($log->user)
                                    <strong>{{ $log->user->name }}</strong>
                                    <br><small class="text-muted">{{ $log->user->email }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $log->action_color }}">{{ $log->action_label }}</span>
                            </td>
                            <td>{{ Str::limit($log->description, 50) }}</td>
                            <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                            <td>
                                <small>{{ $log->created_at->format('Y/m/d') }}</small>
                                <br><small class="text-muted">{{ $log->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.activity-logs.show', $log) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="la la-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="la la-clipboard-list la-3x mb-2 d-block"></i>
                                لا توجد سجلات
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer">
            {{ $logs->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
