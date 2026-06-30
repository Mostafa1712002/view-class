@extends('layouts.app')

@section('title', 'إعدادات رسائل الطلاب المجمعة')
@section('page-title', 'إعدادات رسائل الطلاب المجمعة')
@section('body_class', 'theme-light')

@php $user = auth()->user(); @endphp

@section('content')
<div class="content-body">
    @include('components.alerts')

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0"><x-svg-icon name="chat-left-text" :size="18" class="me-1" /> أحداث الإرسال التلقائي</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>الحدث</th>
                            <th>الحالة</th>
                            <th>معاينة النص</th>
                            <th class="text-end">التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($events as $type => $event)
                        <tr>
                            <td class="fw-semibold">{{ $event['meta']['label'] ?? $type }}</td>
                            <td>
                                <span class="status-pill {{ $event['enabled'] ? 'on' : 'off' }}">
                                    <x-svg-icon name="circle-fill" :size="8" />
                                    {{ $event['enabled'] ? 'مفعّل' : 'موقوف' }}
                                </span>
                            </td>
                            <td class="text-muted small" style="max-width:300px;">
                                {{ \Illuminate\Support\Str::limit($event['body'] ?? $event['meta']['default'] ?? '', 80) }}
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    @if($user->canDo('messages.templates'))
                                    <a href="{{ route('admin.sms.auto-messages.edit', $type) }}"
                                       class="btn btn-sm btn-outline-secondary" title="نموذج الرسالة">
                                        <x-svg-icon name="pencil-square" :size="14" /> نموذج الرسالة
                                    </a>
                                    <form action="{{ route('admin.sms.auto-messages.toggle', $type) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm {{ $event['enabled'] ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                title="{{ $event['enabled'] ? 'إيقاف' : 'تفعيل' }}">
                                            <x-svg-icon name="power" :size="14" />
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">
                            <div class="empty-state text-center py-5">
                                <div class="icon-wrap mb-2"><x-svg-icon name="chat-left-text" :size="48" class="ic-muted" /></div>
                                <h5>لا توجد أحداث مضبوطة</h5>
                                <p class="text-muted">لم يتم تعريف أي أحداث للإرسال التلقائي.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
