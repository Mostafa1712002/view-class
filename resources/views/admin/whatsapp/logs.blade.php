@extends('layouts.admin')

@section('title', 'سجل رسائل واتساب')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">سجل رسائل واتساب</h1>
        <a href="{{ route('admin.whatsapp.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear me-1"></i>الإعدادات
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-select">
                        <option value="">— الكل —</option>
                        @foreach(\App\Modules\Whatsapp\Models\WhatsappLog::STATUSES as $val => $label)
                            <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">اسم الطالب</label>
                    <input type="text" name="student_name" class="form-control" value="{{ $filters['student_name'] ?? '' }}" placeholder="بحث...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>الطالب</th>
                                <th>ولي الأمر</th>
                                <th>الرقم</th>
                                <th>النوع</th>
                                <th class="text-center">الحالة</th>
                                <th>الرسالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                            <tr>
                                <td class="text-nowrap">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->student?->name ?? '—' }}</td>
                                <td>{{ $log->parent?->name ?? '—' }}</td>
                                <td dir="ltr">{{ $log->to_number }}</td>
                                <td>
                                    @php
                                        $typeLabels = [
                                            'absence'         => 'غياب',
                                            'late'            => 'تأخر',
                                            'excuse_accepted' => 'قبول عذر',
                                            'excuse_rejected' => 'رفض عذر',
                                        ];
                                    @endphp
                                    {{ $typeLabels[$log->type] ?? $log->type }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $log->status_color }}">{{ $log->status_label }}</span>
                                </td>
                                <td>
                                    <span title="{{ $log->message_text }}">
                                        {{ \Illuminate\Support\Str::limit($log->message_text, 60) }}
                                    </span>
                                    @if($log->failure_reason)
                                        <br><small class="text-danger">{{ $log->failure_reason }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array($log->status, ['failed', 'pending']))
                                        <form method="POST" action="{{ route('admin.whatsapp.resend', $log) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning"
                                                    onclick="return confirm('إعادة إرسال الرسالة؟')">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $logs->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots display-4 text-muted"></i>
                    <p class="text-muted mt-2">لا توجد رسائل</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
