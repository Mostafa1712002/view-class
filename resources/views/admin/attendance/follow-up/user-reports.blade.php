@extends('layouts.app')
@section('body_class','theme-light')
@section('title', 'تقارير المستخدمين')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">تقارير المستخدمين — إرسال رسائل</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.student-attendance.follow-up') }}">المتابعة</a></li>
            <li class="breadcrumb-item active">تقارير المستخدمين</li>
        </ol>
    </div>
</div>

<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="form-row align-items-end">
            <div class="col-md-4 mb-2"><label>اختر الفصل لعرض الطلاب</label>
                <select name="class_id" class="form-control">
                    <option value="">— اختر الفصل —</option>
                    @foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)$selectedClass===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2"><button class="btn btn-primary"><i class="la la-search"></i> عرض</button></div>
        </form>
    </div></div>

    @if($students->isNotEmpty())
    <form method="POST" action="{{ route('admin.student-attendance.user-reports.send') }}">
        @csrf
        <div class="row">
            <div class="col-md-7">
                <div class="card"><div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th><input type="checkbox" id="checkAll"></th><th>الطالب</th><th>رقم الهوية</th></tr></thead>
                        <tbody>
                            @foreach($students as $s)
                            <tr><td><input type="checkbox" class="rowCheck" name="student_ids[]" value="{{ $s->id }}"></td><td>{{ $s->name }}</td><td>{{ $s->national_id ?? '—' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div></div>
            </div>
            <div class="col-md-5">
                <div class="card"><div class="card-body">
                    <h5 class="mb-3">تكوين الرسالة</h5>
                    <div class="form-group"><label>قناة الإرسال</label>
                        <select name="channel" class="form-control">
                            <option value="in_app">بريد المنصة (إشعار)</option>
                            <option value="sms">SMS</option>
                            <option value="whatsapp">واتساب</option>
                        </select>
                    </div>
                    <div class="form-group"><label>قالب الرسالة (نماذج الرسائل)</label>
                        <select class="form-control" id="tpl" name="template_id">
                            <option value="" data-body="">— مخصص —</option>
                            @foreach(($templates ?? collect()) as $t)
                                <option value="{{ $t->id }}" data-body="{{ e($t->body) }}">{{ $t->title }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">عند اختيار قالب يُعتمد نصه النهائي بعد استبدال المتغيرات.</small>
                    </div>
                    <div class="form-group"><label>نص الرسالة (معاينة)</label><textarea name="message" id="msg" rows="4" class="form-control" required></textarea></div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="la la-paper-plane"></i> إرسال</button>
                </div></div>
            </div>
        </div>
    </form>
    @else
        <div class="card"><div class="card-body text-center text-muted py-5"><i class="la la-users la-3x d-block mb-2"></i> اختر فصلاً لعرض الطلاب.</div></div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ca = document.getElementById('checkAll');
    if (ca) ca.addEventListener('change', function () { document.querySelectorAll('.rowCheck').forEach(function (c){c.checked=ca.checked;}); });
    var tpl = document.getElementById('tpl');
    if (tpl) tpl.addEventListener('change', function () {
        var body = tpl.options[tpl.selectedIndex].getAttribute('data-body') || '';
        if (body) document.getElementById('msg').value = body;
    });
});
</script>
@endpush
@endsection
