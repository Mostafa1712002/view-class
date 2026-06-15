@extends('layouts.app')
@section('body_class','theme-light')
@section('title','ماسح حضور QR')
@section('content')
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">ماسح حضور QR</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('admin.qr.cards.index') }}">بطاقات QR</a></li><li class="breadcrumb-item active">الماسح</li></ol>
</div></div>
<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h5 class="mb-3">الكاميرا</h5>
                <div class="form-group"><label>اسم الجهاز (اختياري)</label><input type="text" id="deviceName" class="form-control" placeholder="جهاز البوابة"></div>
                <div class="form-group form-check"><input type="checkbox" class="form-check-input" id="manualTime"><label class="form-check-label" for="manualTime">استخدام وقت يدوي للمسح</label></div>
                <div class="form-group d-none" id="manualTimeWrap"><input type="datetime-local" id="scanTime" class="form-control"></div>
                <button class="btn btn-primary mb-2" id="startCam"><i class="la la-camera"></i> تشغيل الكاميرا</button>
                <div id="reader" style="width:100%"></div>
                <hr>
                <h6>إدخال الرمز يدوياً</h6>
                <div class="input-group">
                    <input type="text" id="manualToken" class="form-control" placeholder="الصق رمز البطاقة">
                    <div class="input-group-append"><button class="btn btn-success" id="sendManual"><i class="la la-paper-plane"></i> إرسال</button></div>
                </div>
                <div id="scanResult" class="mt-3"></div>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h5 class="mb-3">آخر المسحات</h5>
                <ul class="list-group" id="recentList">
                    @forelse($recent as $r)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ optional($r->student)->name ?? '—' }}</span>
                        <span class="badge badge-{{ $r->result_status==='present'?'success':($r->result_status==='late'?'warning':($r->result_status==='rejected'?'danger':'info')) }}">{{ ['present'=>'حاضر','late'=>'متأخر','absent'=>'غائب','excused'=>'مستأذن','rejected'=>'مرفوض'][$r->result_status]??$r->result_status }}</span>
                    </li>
                    @empty<li class="list-group-item text-muted text-center">لا توجد مسحات بعد.</li>@endforelse
                </ul>
            </div></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrf = '{{ csrf_token() }}';
    document.getElementById('manualTime').addEventListener('change', function () {
        document.getElementById('manualTimeWrap').classList.toggle('d-none', !this.checked);
    });

    function send(token, channel) {
        var body = { token: token, channel: channel, device_name: document.getElementById('deviceName').value };
        if (document.getElementById('manualTime').checked && document.getElementById('scanTime').value) {
            body.scan_time = document.getElementById('scanTime').value;
        }
        fetch('{{ route('admin.qr.scan') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body)
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
            var box = document.getElementById('scanResult');
            box.innerHTML = '<div class="alert alert-' + (res.d.success ? 'success' : 'danger') + '">' + res.d.message + '</div>';
            if (res.d.success && res.d.student) {
                var li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between';
                li.innerHTML = '<span>' + res.d.student.name + '</span><span class="badge badge-success">' + res.d.status + '</span>';
                document.getElementById('recentList').prepend(li);
            }
        }).catch(function () {
            document.getElementById('scanResult').innerHTML = '<div class="alert alert-danger">تعذر الاتصال بالخادم.</div>';
        });
    }

    document.getElementById('sendManual').addEventListener('click', function () {
        var t = document.getElementById('manualToken').value.trim();
        if (!t) return;
        send(t, 'manual');
        document.getElementById('manualToken').value = '';
    });

    document.getElementById('startCam').addEventListener('click', function () {
        if (typeof Html5Qrcode === 'undefined') {
            document.getElementById('scanResult').innerHTML = '<div class="alert alert-warning">مكتبة الكاميرا غير متاحة. استخدم الإدخال اليدوي.</div>';
            return;
        }
        var scanner = new Html5Qrcode('reader');
        scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: 220 }, function (decoded) {
            scanner.pause();
            send(decoded, 'camera');
            setTimeout(function () { try { scanner.resume(); } catch (e) {} }, 1500);
        }).catch(function () {
            document.getElementById('scanResult').innerHTML = '<div class="alert alert-warning">تعذر تشغيل الكاميرا. استخدم الإدخال اليدوي.</div>';
        });
    });
});
</script>
@endpush
@endsection
