@extends('layouts.app')
@section('body_class','theme-light')
@section('title','حضور وغياب المعلمين')
@section('content')
@php $labels=['present'=>'حاضر','absent'=>'غائب','late'=>'متأخر','excused'=>'مستأذن']; $colors=['present'=>'success','absent'=>'danger','late'=>'warning','excused'=>'info']; @endphp
<div class="content-header row"><div class="content-header-left col-12 mb-2">
    <h2 class="content-header-title mb-0">حضور وغياب المعلمين</h2>
    <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li><li class="breadcrumb-item active">غياب المعلمين</li></ol>
</div></div>
<div class="content-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link {{ $mode==='daily'?'active':'' }}" href="{{ route('admin.teacher-attendance.daily') }}"><x-svg-icon name="calendar-day" /> إدارة حضور وغياب يومي</a></li>
        <li class="nav-item"><a class="nav-link {{ $mode==='period'?'active':'' }}" href="{{ route('admin.teacher-attendance.period') }}"><x-svg-icon name="clock" /> إدارة حضور وغياب حصة</a></li>
    </ul>

    <div class="row mb-3">
        @foreach([['حضور','present','success'],['غياب','absent','danger'],['تأخير','late','warning'],['استئذان','excused','info']] as [$lbl,$k,$col])
        <div class="col-md-3 col-6 mb-2"><div class="card border-{{ $col }}"><div class="card-body py-2"><div class="text-muted small">إجمالي ال{{ $lbl }}</div><h3 class="mb-0 text-{{ $col }}">{{ $counts[$k] }}</h3></div></div></div>
        @endforeach
    </div>

    <div class="card mb-3"><div class="card-body"><form method="GET" action="{{ $mode==='daily'?route('admin.teacher-attendance.daily'):route('admin.teacher-attendance.period') }}" class="form-row align-items-end">
        <div class="col-md-2 mb-2"><label>التاريخ</label><input type="date" name="date" value="{{ $date }}" class="form-control"></div>
        @if($mode==='period')
        <div class="col-md-2 mb-2"><label>الصف</label><select name="class_id" class="form-control"><option value="">— الفصل —</option>@foreach($classes as $c)<option value="{{ $c->id }}" {{ (string)request('class_id')===(string)$c->id?'selected':'' }}>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-2 mb-2"><label>المادة</label><select name="subject_id" class="form-control"><option value="">— المادة —</option>@foreach($subjects as $s)<option value="{{ $s->id }}" {{ (string)request('subject_id')===(string)$s->id?'selected':'' }}>{{ $s->name }}</option>@endforeach</select></div>
        <div class="col-md-1 mb-2"><label>الحصة</label><input type="number" name="period" min="1" max="12" value="{{ $period }}" class="form-control"></div>
        @endif
        <div class="col-md-2 mb-2"><label>اسم المعلم</label><input type="text" name="name" value="{{ request('name') }}" class="form-control"></div>
        <div class="col-md-2 mb-2"><label>رقم الهوية</label><input type="text" name="national_id" value="{{ request('national_id') }}" class="form-control"></div>
        <div class="col-md-1 mb-2"><button class="btn btn-primary"><x-svg-icon name="search" /></button></div>
    </form></div></div>

    @if($rows->isNotEmpty() && auth()->user()?->canDo('pdf_export'))
    @php $tparams = array_merge(request()->except(['page','format']), ['date' => $date] + ($mode==='period' ? ['period' => $period] : [])); @endphp
    <div class="btn-group btn-group-sm mb-2" role="group" aria-label="تصدير حضور المعلمين">
        <a class="btn btn-outline-danger" target="_blank" href="{{ route('admin.teacher-attendance.export', array_merge($tparams, ['format'=>'pdf'])) }}"><x-svg-icon name="file-earmark-pdf" /> PDF</a>
        <a class="btn btn-outline-success" href="{{ route('admin.teacher-attendance.export', array_merge($tparams, ['format'=>'excel'])) }}"><x-svg-icon name="file-earmark-excel" /> Excel</a>
        <a class="btn btn-outline-secondary" href="{{ route('admin.teacher-attendance.export', array_merge($tparams, ['format'=>'csv'])) }}"><x-svg-icon name="file-earmark-text" /> CSV</a>
        <button type="button" class="btn btn-outline-info" onclick="window.print()"><x-svg-icon name="printer" /> طباعة</button>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.teacher-attendance.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        @if($mode==='period')<input type="hidden" name="period" value="{{ $period }}"><input type="hidden" name="class_id" value="{{ request('class_id') }}"><input type="hidden" name="subject_id" value="{{ request('subject_id') }}">@endif
        <div class="card"><div class="card-body table-responsive">
            @if($rows->isEmpty())<div class="ds-empty"><div class="ds-empty-icon"><x-svg-icon name="easel" :size="32" /></div><div class="ds-empty-title">لا يوجد معلمون</div><div class="ds-empty-desc">لا يوجد معلمون مطابقون لمعايير البحث الحالية.</div></div>
            @else
            <table class="table table-hover align-middle"><thead><tr><th>#</th><th>الصورة</th><th>المعلم</th><th>أيام الحضور</th><th>الحالة</th><th>ملاحظات</th><th>التحكم</th></tr></thead>
            <tbody>@foreach($rows as $i=>$row)@php $t=$row['teacher']; @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>@if($t->avatar)<img src="{{ asset('storage/'.$t->avatar) }}" class="rounded-circle" width="36" height="36">@else<span class="badge badge-light rounded-circle p-2"><x-svg-icon name="person" /></span>@endif</td>
                <td>{{ $t->name }}</td>
                <td><span class="badge badge-light">{{ $row['present_days'] }}</span></td>
                <td>
                    <input type="hidden" name="rows[{{ $i }}][teacher_id]" value="{{ $t->id }}">
                    <span class="status-badge badge badge-{{ $colors[$row['status']]??'secondary' }}">{{ $labels[$row['status']]??'غير محدد' }}</span>
                    <input type="hidden" class="status-input" name="rows[{{ $i }}][status]" value="{{ $row['status']??'present' }}">
                    <div class="btn-group btn-group-sm mt-1">
                        @foreach(['present'=>['حاضر','success','check-lg'],'absent'=>['غائب','danger','x-lg'],'late'=>['متأخر','warning','clock'],'excused'=>['مستأذن','info','person-check']] as $k=>[$lbl,$col,$ic])
                        <button type="button" class="btn btn-outline-{{ $col }} js-status" data-status="{{ $k }}" title="{{ $lbl }}"><x-svg-icon :name="$ic" :size="16" /></button>
                        @endforeach
                    </div>
                </td>
                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row['notes'],25)?:'—' }}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary js-msg" data-id="{{ $t->id }}" data-name="{{ $t->name }}"><x-svg-icon name="send" /></button>
                </td>
            </tr>
            @endforeach</tbody></table>
            @endif
        </div>
        @if($rows->isNotEmpty())<div class="card-footer text-left"><button type="submit" class="btn btn-primary"><x-svg-icon name="save" /> حفظ الحضور</button></div>@endif
        </div>
    </form>
</div>

<div class="modal fade" id="msgModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST" id="msgForm">@csrf
    <div class="modal-header"><h5 class="modal-title">رسالة للمعلم — <span id="msgName"></span></h5></div>
    <div class="modal-body"><textarea name="message" rows="3" class="form-control" required>نود إعلامكم بـ ...</textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button><button type="submit" class="btn btn-primary">إرسال</button></div>
</form></div></div></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var labels={present:'حاضر',absent:'غائب',late:'متأخر',excused:'مستأذن'},colors={present:'success',absent:'danger',late:'warning',excused:'info'};
    document.querySelectorAll('.js-status').forEach(function(b){b.addEventListener('click',function(){var tr=b.closest('tr'),s=b.dataset.status;tr.querySelector('.status-input').value=s;var bd=tr.querySelector('.status-badge');bd.textContent=labels[s];bd.className='status-badge badge badge-'+colors[s];});});
    document.querySelectorAll('.js-msg').forEach(function(b){b.addEventListener('click',function(){document.getElementById('msgName').textContent=b.dataset.name;document.getElementById('msgForm').action='{{ url('admin/teacher-attendance/message') }}/'+b.dataset.id;if(window.jQuery)jQuery('#msgModal').modal('show');});});
});
</script>
@endpush
@endsection
