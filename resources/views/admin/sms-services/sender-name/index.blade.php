@extends('layouts.app')

@section('title', 'اسم المرسل')
@section('page-title', 'اسم المرسل')
@section('body_class', 'theme-light')

@php $user = auth()->user(); @endphp

@section('content')
<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="card-title mb-0">أسماء المرسلين</h4>
            <div class="d-flex gap-1">
                @if($user->canDo('messages.sender_name'))
                <a href="{{ route('admin.sms.sender-name.create') }}" class="btn btn-sm btn-primary">
                    <x-svg-icon name="plus-lg" :size="14" class="me-1" /> طلب اسم مرسل
                </a>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>اسم المرسل</th>
                            <th>النوع</th>
                            <th>عدد المرفقات</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th class="text-end">التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($senders as $s)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $s->name_ar }}</div>
                                <div class="text-muted small" dir="ltr">{{ $s->name_en }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ $s->kind === 'alerts' ? 'تنبيهات' : ($s->kind === 'advertising' ? 'إعلاني' : $s->kind) }}
                                </span>
                            </td>
                            <td>{{ $s->attachments->count() }}</td>
                            <td>
                                @php
                                    $stColors = [
                                        'draft'        => 'bg-secondary',
                                        'submitted'    => 'bg-warning text-dark',
                                        'under_review' => 'bg-warning text-dark',
                                        'needs_edit'   => 'bg-info text-dark',
                                        'accepted'     => 'bg-success',
                                        'active'       => 'bg-success',
                                        'rejected'     => 'bg-danger',
                                    ];
                                    $stColor = $stColors[$s->status] ?? 'bg-secondary';
                                @endphp
                                <span class="badge {{ $stColor }}">{{ $s->statusLabel() }}</span>
                            </td>
                            <td>{{ $s->created_at?->format('Y-m-d') }}</td>
                            <td class="text-end">
                                @if($user->canDo('messages.sender_name'))
                                @if($user->isSuperAdmin() && in_array($s->status, ['submitted', 'under_review', 'needs_edit']))
                                <form action="{{ route('admin.sms.sender-name.approve', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('الموافقة على اسم المرسل وتفعيله؟');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="موافقة"><x-svg-icon name="check-lg" :size="14" /></button>
                                </form>
                                <form action="{{ route('admin.sms.sender-name.reject', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('رفض الطلب؟');">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="رفض"><x-svg-icon name="x-lg" :size="14" /></button>
                                </form>
                                @endif
                                @if(in_array($s->status, ['draft', 'needs_edit', 'rejected']))
                                <form action="{{ route('admin.sms.sender-name.destroy', $s->id) }}" method="POST"
                                      class="d-inline" onsubmit="return confirm('حذف طلب اسم المرسل؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                        <x-svg-icon name="trash3-fill" :size="14" />
                                    </button>
                                </form>
                                @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="empty-state text-center py-5">
                                <div class="icon-wrap mb-2"><x-svg-icon name="person-badge" :size="48" class="ic-muted" /></div>
                                <h5>لا توجد أسماء مرسلين</h5>
                                <p class="text-muted">أرسل طلب اسم مرسل لبدء إرسال الرسائل باسم مدرستك.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($senders->hasPages())
        <div class="card-footer">{{ $senders->links() }}</div>
        @endif
    </div>
</div>
@endsection
