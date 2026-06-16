@extends('layouts.app')
@section('body_class','theme-light')
@section('title', 'متابعة التأخير والغياب')
@section('content')
@php
    $statusLabels = ['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','excused'=>'مستأذن'];
    $statusColors = ['present'=>'success','absent'=>'danger','late'=>'warning','excused'=>'info'];
@endphp
<div class="content-header row">
    <div class="content-header-left col-md-7 col-12 mb-2">
        <h2 class="content-header-title mb-0">متابعة التأخير والغياب</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item active">متابعة التأخير والغياب</li>
        </ol>
    </div>
    <div class="content-header-right col-md-5 col-12 text-md-right">
        <a href="{{ route('admin.student-attendance.user-reports') }}" class="btn btn-primary btn-sm"><x-svg-icon name="send" /> تقارير المستخدمين</a>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row mb-3">
        @foreach([['حضور','present','success'],['غياب','absent','danger'],['تأخير','late','warning'],['استئذان','excused','info']] as [$lbl,$key,$col])
        <div class="col-md-3 col-6 mb-2"><div class="card border-{{ $col }}"><div class="card-body py-2">
            <div class="text-muted small">إجمالي ال{{ $lbl }}</div><h3 class="mb-0 text-{{ $col }}">{{ $counts[$key] }}</h3>
        </div></div></div>
        @endforeach
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-2 mb-2"><label>التاريخ</label><input type="date" name="date" value="{{ request('date') }}" class="form-control"></div>
            <div class="col-md-2 mb-2"><label>الفصل</label>
                <select name="class_id" class="form-control"><option value="">— الكل —</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2"><label>الحالة</label>
                <select name="status" class="form-control"><option value="">— الكل —</option>
                    @foreach($statusLabels as $k=>$v)<option value="{{ $k }}" {{ request('status')===$k?'selected':'' }}>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2"><label>نوع الحالة</label>
                <select name="type" class="form-control">
                    <option value="">— الكل —</option>
                    <option value="absent_daily" {{ request('type')==='absent_daily'?'selected':'' }}>غياب يومي</option>
                    <option value="absent_period" {{ request('type')==='absent_period'?'selected':'' }}>غياب حصة</option>
                    <option value="late_daily" {{ request('type')==='late_daily'?'selected':'' }}>تأخير يومي</option>
                    <option value="late_period" {{ request('type')==='late_period'?'selected':'' }}>تأخير حصة</option>
                    <option value="excuse" {{ request('type')==='excuse'?'selected':'' }}>استئذان</option>
                </select>
            </div>
            <div class="col-md-2 mb-2"><label>اسم الطالب</label><input type="text" name="name" value="{{ request('name') }}" class="form-control"></div>
            <div class="col-md-2 mb-2"><button class="btn btn-primary"><x-svg-icon name="search" /> بحث</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body table-responsive">
        @if($rows->isEmpty())
            <div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="clipboard-data" :size="32" /></div><div class="ds-empty-title">لا توجد سجلات متابعة</div><div class="ds-empty-desc">اختر تاريخ أو فلتر لعرض سجلات المتابعة.</div></div>
        @else
        <table class="table table-hover align-middle">
            <thead><tr>
                <th>الطالب</th><th>الحالة</th><th>الحصة</th><th>الفصل</th><th>المادة</th><th>جوال ولي الأمر</th><th>تم التواصل</th><th>الملاحظات</th><th>التحكم</th>
            </tr></thead>
            <tbody>
                @foreach($rows as $r)
                @php $parent = optional($r->student)->parents->first(); @endphp
                <tr>
                    <td>{{ optional($r->student)->name ?? '—' }}</td>
                    <td><span class="badge badge-{{ $statusColors[$r->status] ?? 'secondary' }}">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
                    <td>{{ $r->period ?? 'يومي' }}</td>
                    <td>{{ optional($r->classRoom)->name ?? '—' }}</td>
                    <td>{{ optional($r->subject)->name ?? '—' }}</td>
                    <td>{{ $parent->phone ?? '—' }}</td>
                    <td>@if($r->notified_parent)<span class="badge badge-success">نعم</span>@else<span class="badge badge-light">لا</span>@endif</td>
                    <td class="small text-muted">{{ \Illuminate\Support\Str::limit($r->notes, 25) ?: '—' }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary js-notify" data-id="{{ $r->id }}" data-name="{{ optional($r->student)->name }}"><x-svg-icon name="send" /> رسالة</button>
                        <a href="{{ route('admin.users.students.attendance', optional($r->student)->id) }}" class="btn btn-sm btn-link"><x-svg-icon name="clock-history" /></a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $rows->links() }}
        @endif
    </div></div>
</div>

{{-- Notify modal --}}
<div class="modal fade" id="notifyModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" id="notifyForm">
        @csrf
        <div class="modal-header"><h5 class="modal-title">إرسال رسالة لولي الأمر — <span id="notifyName"></span></h5></div>
        <div class="modal-body">
            <div class="form-group"><label>القناة</label>
                <select name="channel" class="form-control">
                    <option value="in_app">بريد المنصة (إشعار)</option>
                    <option value="sms">SMS</option>
                    <option value="whatsapp">واتساب</option>
                </select>
            </div>
            <div class="form-group"><label>نص الرسالة</label><textarea name="message" rows="3" class="form-control" required>نود إعلامكم بحالة الحضور لابنكم/ابنتكم.</textarea></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-primary">إرسال</button>
        </div>
    </form>
</div></div></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.js-notify').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('notifyName').textContent = b.dataset.name || '';
            document.getElementById('notifyForm').action = '{{ url('admin/attendance/follow-up') }}/' + b.dataset.id + '/notify';
            if (window.jQuery) jQuery('#notifyModal').modal('show'); else document.getElementById('notifyModal').style.display='block';
        });
    });
});
</script>
@endpush
@endsection
