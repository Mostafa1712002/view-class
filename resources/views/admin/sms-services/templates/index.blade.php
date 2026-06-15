@extends('layouts.app')

@section('title', 'قوالب الرسائل القصيرة')
@section('page-title', 'قوالب الرسائل القصيرة')
@section('body_class', 'theme-light')

@php $user = auth()->user(); @endphp

@section('content')
<div class="content-header row">
    <div class="content-header-left col-12 mb-2">
        <h2 class="content-header-title mb-0">قوالب الرسائل القصيرة</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item">الرسائل القصيرة</li>
                <li class="breadcrumb-item active">قوالب الرسائل القصيرة</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @include('components.alerts')

    {{-- blue search bar --}}
    <form method="GET" class="card mb-2" style="background:#eaf2fb;border:1px solid #cfe0f5;">
        <div class="card-body d-flex flex-wrap align-items-center gap-2 py-2">
            <span class="fw-bold" style="color:#1d4e89;">بحث في النتائج</span>
            <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:320px;" placeholder="العنوان أو نص القالب">
            <button class="btn btn-sm btn-primary"><x-svg-icon name="search" :size="14" class="me-1" /> بحث</button>
            @if($q !== '')<a href="{{ route('admin.sms.templates.index') }}" class="btn btn-sm btn-outline-secondary">إلغاء</a>@endif
        </div>
    </form>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <h4 class="card-title mb-0">قوالب الرسائل القصيرة</h4>
            <div class="d-flex gap-1">
                @if($user->canDo('messages.templates'))
                <a href="{{ route('admin.sms.templates.create') }}" class="btn btn-sm btn-primary">
                    <x-svg-icon name="plus-lg" :size="14" class="me-1" /> إضافة قالب
                </a>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>نص القالب</th>
                            <th>اللغة</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th class="text-end">التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($templates as $t)
                        <tr>
                            <td class="fw-semibold">{{ $t->title }}</td>
                            <td class="text-muted" style="max-width:320px;">{{ \Illuminate\Support\Str::limit($t->body, 70) }}</td>
                            <td><span class="badge bg-light text-dark">{{ ['ar'=>'عربي','en'=>'إنجليزي','mixed'=>'مختلط'][$t->lang] ?? $t->lang }}</span></td>
                            <td>
                                <span class="status-pill {{ $t->is_active ? 'on' : 'off' }}">
                                    <x-svg-icon name="circle-fill" :size="8" /> {{ $t->is_active ? 'مفعّل' : 'موقوف' }}
                                </span>
                            </td>
                            <td>{{ $t->created_at?->format('Y-m-d') }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button class="btn btn-sm btn-outline-info js-try" data-id="{{ $t->id }}" title="تجربة القالب"><x-svg-icon name="play-fill" :size="14" /></button>
                                    @if($user->canDo('messages.templates'))
                                    <a href="{{ route('admin.sms.templates.edit', $t->id) }}" class="btn btn-sm btn-outline-secondary" title="تعديل"><x-svg-icon name="pencil-square" :size="14" /></a>
                                    <form action="{{ route('admin.sms.templates.copy', $t->id) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary" title="نسخ"><x-svg-icon name="files" :size="14" /></button></form>
                                    <form action="{{ route('admin.sms.templates.toggle', $t->id) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-warning" title="تفعيل/إيقاف"><x-svg-icon name="power" :size="14" /></button></form>
                                    <form action="{{ route('admin.sms.templates.destroy', $t->id) }}" method="POST" class="d-inline" onsubmit="return confirm('حذف القالب؟');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="حذف"><x-svg-icon name="trash3-fill" :size="14" /></button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="empty-state text-center py-5">
                                <div class="icon-wrap mb-2"><x-svg-icon name="chat-left-text" :size="48" class="ic-muted" /></div>
                                <h5>لا توجد قوالب</h5>
                                <p class="text-muted">أضف أول قالب لاستخدامه في إرسال الرسائل.</p>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($templates->hasPages())
        <div class="card-footer">{{ $templates->links() }}</div>
        @endif
    </div>
</div>

{{-- try modal --}}
<div class="modal fade" id="tryModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">تجربة القالب</h5><button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal"></button></div>
    <div class="modal-body">
        <p class="text-muted small">المعاينة باستخدام بيانات تجريبية:</p>
        <div id="tryRendered" class="p-2 border rounded bg-light" dir="rtl" style="white-space:pre-wrap;"></div>
        <div class="mt-2 small text-muted" id="tryStats"></div>
    </div>
</div></div></div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.js-try').forEach(function(btn){
    btn.addEventListener('click', function(){
        var id = this.dataset.id;
        fetch('{{ url('admin/sms/templates') }}/'+id+'/try', {
            method:'POST',
            headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}
        }).then(r=>r.json()).then(function(j){
            if(!j.success) return;
            document.getElementById('tryRendered').textContent = j.data.rendered;
            document.getElementById('tryStats').textContent = 'الأحرف: '+j.data.length+' | الرسائل: '+j.data.segments+' | '+({ar:'عربي',en:'إنجليزي',mixed:'مختلط'}[j.data.lang]||j.data.lang);
            var m = document.getElementById('tryModal');
            if (window.bootstrap) { new bootstrap.Modal(m).show(); }
            else if (window.jQuery) { jQuery(m).modal('show'); }
        });
    });
});
</script>
@endpush
