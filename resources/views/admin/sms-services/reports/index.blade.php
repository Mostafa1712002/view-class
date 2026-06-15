@extends('layouts.app')

@section('title', 'تقارير الرسائل القصيرة')
@section('page-title', 'تقارير الرسائل القصيرة')
@section('body_class', 'theme-light')

@php
    $user = auth()->user();
    $smsBadgeClass = function($status) {
        if (in_array($status, ['sent', 'delivered', 'read'])) return 'bg-success';
        if (in_array($status, ['queued']))                    return 'bg-primary';
        if (in_array($status, ['failed', 'no_credit', 'rejected'])) return 'bg-danger';
        return 'bg-secondary';
    };
@endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">تقارير الرسائل القصيرة</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item active">التقارير</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- filter bar --}}
    <form method="GET" action="{{ route('admin.sms.reports.index') }}" class="card mb-2" style="background:#fdf8ee;border:1px solid #e8d5a3;">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">من</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">إلى</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                @if($schools->isNotEmpty())
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">المدرسة</label>
                    <select name="school_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($schools as $sc)
                            <option value="{{ $sc->id }}" {{ ($filters['school_id'] ?? '') == $sc->id ? 'selected' : '' }}>{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">المرسل</label>
                    <select name="sender_id" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($senders as $s)
                            <option value="{{ $s->id }}" {{ ($filters['sender_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name_ar }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">الحالة</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">رقم/اسم المستلم</label>
                    <input type="text" name="recipient" value="{{ $filters['recipient'] ?? '' }}" class="form-control form-control-sm" placeholder="بحث…">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">المرسِل (المستخدم)</label>
                    <input type="text" name="sent_by" value="{{ $filters['sent_by'] ?? '' }}" class="form-control form-control-sm" placeholder="اسم أو ID…">
                </div>
                <div class="col-12 d-flex gap-1 align-items-center flex-wrap">
                    <button class="btn btn-sm btn-primary"><x-svg-icon name="search" :size="14" class="me-1" /> بحث</button>
                    <a href="{{ route('admin.sms.reports.index') }}" class="btn btn-sm btn-outline-secondary">إلغاء</a>
                    <span class="ms-auto d-flex gap-1">
                        <a href="{{ route('admin.sms.reports.export.excel') . '?' . http_build_query(request()->query()) }}"
                           class="btn btn-sm btn-outline-success">
                            <x-svg-icon name="file-earmark-excel" :size="14" class="me-1" /> Excel
                        </a>
                        <a href="{{ route('admin.sms.reports.export.pdf') . '?' . http_build_query(request()->query()) }}"
                           class="btn btn-sm btn-outline-danger">
                            <x-svg-icon name="download" :size="14" class="me-1" /> PDF
                        </a>
                    </span>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">سجل الرسائل القصيرة</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>القناة</th>
                            <th>اسم المرسل</th>
                            <th>المستلم</th>
                            <th>رقم الجوال</th>
                            <th>الدور</th>
                            @if($isAllSchools)<th>المدرسة</th>@endif
                            <th>النص</th>
                            <th>الحالة</th>
                            <th>سبب الفشل</th>
                            <th>عدد الرسائل</th>
                            <th>الرصيد</th>
                            <th>وقت الإرسال</th>
                            <th>المرسِل</th>
                            <th>Batch</th>
                            @if($user->canDo('messages.reports'))<th class="text-end">التحكم</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($messages as $m)
                        @php $statusClass = $smsBadgeClass($m->status); @endphp
                        <tr>
                            <td><span class="badge bg-light text-dark">{{ strtoupper($m->channel ?? 'sms') }}</span></td>
                            <td>{{ optional($m->sender)->name_ar ?? '—' }}</td>
                            <td>{{ $m->recipient_name ?? '—' }}</td>
                            <td dir="ltr" class="text-start">{{ $m->recipient ?? '—' }}</td>
                            <td class="text-muted small">{{ $m->recipient_role ?? '—' }}</td>
                            @if($isAllSchools)<td>{{ optional($m->school)->name ?? '—' }}</td>@endif
                            <td style="max-width:200px;" class="small text-muted">{{ \Illuminate\Support\Str::limit($m->body ?? '', 50) }}</td>
                            <td><span class="badge {{ $statusClass }}">{{ $m->statusLabel() }}</span></td>
                            <td class="small text-muted">{{ $m->error ?? '—' }}</td>
                            <td>{{ $m->message_count ?? 1 }}</td>
                            <td>{{ $m->credit_charged ?? 0 }}</td>
                            <td class="small text-nowrap">{{ optional($m->sent_at ?? $m->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="small">{{ optional($m->triggeredBy)->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $m->batch_id ?? '—' }}</td>
                            @if($user->canDo('messages.reports'))
                            <td class="text-end">
                                @if(in_array($m->status, ['failed', 'no_credit', 'invalid_number', 'no_number']))
                                <form action="{{ route('admin.sms.reports.resend', $m->id) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('إعادة الإرسال؟')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="إعادة الإرسال">
                                        <x-svg-icon name="arrow-clockwise" :size="14" />
                                    </button>
                                </form>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAllSchools ? 15 : 14 }}">
                            <div class="empty-state text-center py-5">
                                <div class="icon-wrap mb-2"><x-svg-icon name="chat-left-text" :size="48" class="ic-muted" /></div>
                                <h5>لا توجد رسائل</h5>
                                <p class="text-muted">لم يتم إرسال أي رسائل بعد، أو الفلاتر لا تطابق أي نتائج.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($messages->hasPages())
        <div class="card-footer">{{ $messages->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
