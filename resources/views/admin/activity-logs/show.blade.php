@extends('layouts.admin')

@section('title', 'تفاصيل النشاط')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="la la-arrow-right me-1"></i>العودة
            </a>
            <h1 class="h3 mb-0">تفاصيل النشاط</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">معلومات النشاط</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 150px;">الإجراء</th>
                            <td>
                                <span class="badge bg-{{ $activityLog->action_color }} fs-6">
                                    <i class="la {{ $activityLog->action_icon }} me-1"></i>
                                    {{ $activityLog->action_label }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>@lang('common.description')</th>
                            <td>{{ $activityLog->description }}</td>
                        </tr>
                        <tr>
                            <th>المستخدم</th>
                            <td>
                                @if($activityLog->user)
                                    <strong>{{ $activityLog->user->name }}</strong>
                                    <br><small class="text-muted">{{ $activityLog->user->email }}</small>
                                @else
                                    <span class="text-muted">غير معروف</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>التاريخ والوقت</th>
                            <td>{{ $activityLog->created_at->format('Y/m/d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>عنوان IP</th>
                            <td><code>{{ $activityLog->ip_address ?? '-' }}</code></td>
                        </tr>
                        <tr>
                            <th>المتصفح</th>
                            <td><small class="text-muted">{{ Str::limit($activityLog->user_agent, 60) ?? '-' }}</small></td>
                        </tr>
                        @if($activityLog->model_type)
                        <tr>
                            <th>نوع العنصر</th>
                            <td><code>{{ class_basename($activityLog->model_type) }}</code></td>
                        </tr>
                        <tr>
                            <th>معرف العنصر</th>
                            <td>{{ $activityLog->model_id }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            @if($activityLog->old_values)
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="la la-history me-1"></i>القيم السابقة</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0 bg-light p-3 rounded" style="max-height: 300px; overflow: auto; direction: ltr; text-align: left;">{{ json_encode($activityLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif

            @if($activityLog->new_values)
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="la la-plus-circle me-1"></i>القيم الجديدة</h5>
                </div>
                <div class="card-body">
                    <pre class="mb-0 bg-light p-3 rounded" style="max-height: 300px; overflow: auto; direction: ltr; text-align: left;">{{ json_encode($activityLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
