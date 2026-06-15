@extends('layouts.app')

@section('title', 'علاقات ولي الأمر — ' . ($parent->name ?: $parent->username))
@section('page-title', 'علاقات العملاء — ملف ولي الأمر')
@section('body_class', 'theme-light')

@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;
    $isRtl = app()->getLocale() === 'ar';

    $complaintStatus = [
        'new' => ['ds-badge-info', 'جديدة'], 'in_progress' => ['ds-badge-warning', 'قيد المعالجة'],
        'awaiting_parent' => ['ds-badge-navy', 'بانتظار رد ولي الأمر'],
        'resolved' => ['ds-badge-success', 'تم الحل'], 'closed' => ['ds-badge-secondary', 'مغلقة'],
    ];
    $priorityLabel = ['low' => 'منخفضة', 'normal' => 'عادية', 'high' => 'مرتفعة', 'urgent' => 'عاجلة'];
    $visitStatus = ['open' => ['ds-badge-info', 'مفتوحة'], 'done' => ['ds-badge-success', 'منتهية'], 'followup' => ['ds-badge-warning', 'تحتاج متابعة']];
    $callStatus = ['scheduled' => ['ds-badge-info', 'مجدول'], 'done' => ['ds-badge-success', 'تم'], 'missed' => ['ds-badge-danger', 'فائت']];

    $timelineMeta = [
        'complaint' => ['تصنيف' => 'شكوى', 'cls' => 'tl-danger'],
        'visit' => ['تصنيف' => 'زيارة', 'cls' => 'tl-navy'],
        'call' => ['تصنيف' => 'اتصال', 'cls' => 'tl-success'],
        'mail' => ['تصنيف' => 'بريد', 'cls' => 'tl-warning'],
        'whatsapp' => ['تصنيف' => 'واتساب', 'cls' => 'tl-success'],
        'notification' => ['تصنيف' => 'إشعار', 'cls' => 'tl-info'],
    ];
    $fmt = fn ($d) => $d ? Carbon::parse($d)->format('Y-m-d H:i') : '—';
    $children = $children ?? collect();
@endphp

@push('styles')
<style>
    .pc-info-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:.75rem; }
    .pc-info-item { background:#f8fafc; border:1px solid #eef2f7; border-radius:10px; padding:.6rem .8rem; }
    .pc-info-item .k { font-size:.74rem; color:#94a3b8; margin-bottom:.15rem; }
    .pc-info-item .v { font-size:.92rem; color:#0f172a; font-weight:600; }
    .crm-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.6rem; margin:.9rem 0; }
    .crm-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:.6rem .8rem; text-align:center; }
    .crm-kpi .num { font-size:1.25rem; font-weight:800; color:#0f172a; }
    .crm-kpi .lbl { font-size:.76rem; color:#64748b; }
    .crm-tabs .nav-link { font-weight:700; color:#475569; }
    .crm-tabs .nav-link.active { color:#9c6b1f; border-color:#c9a04b #c9a04b #fff; }
    .pc-log-empty { color:#94a3b8; font-size:.85rem; padding:1rem; text-align:center; }
    .tl { list-style:none; margin:0; padding:.4rem 0; }
    .tl li { position:relative; padding:.55rem .9rem .55rem 0; border-inline-start:2px solid #e5e7eb; margin-inline-start:.6rem; }
    .tl li .dot { position:absolute; inset-inline-start:-7px; top:.8rem; width:12px; height:12px; border-radius:50%; background:#94a3b8; border:2px solid #fff; }
    .tl li.tl-danger .dot{background:#dc2626} .tl li.tl-navy .dot{background:#1f2a44}
    .tl li.tl-success .dot{background:#16a34a} .tl li.tl-warning .dot{background:#d97706}
    .tl li.tl-info .dot{background:#0ea5e9}
    .tl .tl-head { display:flex; justify-content:space-between; gap:.6rem; flex-wrap:wrap; }
    .tl .tl-title { font-weight:700; color:#0f172a; font-size:.9rem; }
    .tl .tl-at { font-size:.76rem; color:#94a3b8; } .tl .tl-meta { font-size:.8rem; color:#64748b; }
    @media (max-width:768px){ .crm-kpis{ grid-template-columns:repeat(2,minmax(0,1fr)); } }
</style>
@endpush

@section('content')
<section class="pc-show" @if($isRtl) dir="rtl" @endif>

    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.6rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-weight:800;color:#0f172a">{{ $parent->name ?: $parent->username }}</h2>
            <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent;font-size:.85rem">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.parents-contact.index') }}">أولياء الأمور</a></li>
                <li class="breadcrumb-item active" aria-current="page">ملف العلاقات</li>
            </ol></nav>
        </div>
        <a href="{{ route('admin.parents-contact.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="arrow-right" :size="15" /> رجوع
        </a>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul style="margin:0;padding-inline-start:1.1rem">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul></div>
    @endif

    {{-- Parent identity --}}
    <div class="ds-card card" style="margin-bottom:1rem">
        <div class="ds-card-header card-header"><h5 class="ds-card-title" style="margin:0">بيانات ولي الأمر</h5></div>
        <div class="card-body">
            <div class="pc-info-grid">
                <div class="pc-info-item"><div class="k">الاسم</div><div class="v">{{ $parent->name ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">الجنسية</div><div class="v">{{ $parent->nationality ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">رقم الجوال</div><div class="v" dir="ltr" style="text-align:start">{{ $parent->phone ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">واتساب</div><div class="v" dir="ltr" style="text-align:start">{{ $parent->whatsapp ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">رقم الهوية</div><div class="v">{{ $parent->national_id ?: '—' }}</div></div>
                <div class="pc-info-item"><div class="k">الأبناء المرتبطون</div><div class="v">{{ $children->count() }}</div></div>
            </div>

            <div class="crm-kpis">
                <div class="crm-kpi"><div class="num">{{ $complaints->count() }}</div><div class="lbl">الشكاوى</div></div>
                <div class="crm-kpi"><div class="num">{{ $visits->count() }}</div><div class="lbl">الزيارات</div></div>
                <div class="crm-kpi"><div class="num">{{ $calls->count() }}</div><div class="lbl">الاتصالات</div></div>
                <div class="crm-kpi"><div class="num">{{ count($timeline) }}</div><div class="lbl">إجمالي التفاعلات</div></div>
            </div>
        </div>
    </div>

    {{-- Linked children (kept from the original parents-contact page) --}}
    <div class="ds-card card" style="margin-bottom:1rem">
        <div class="ds-card-header card-header"><h5 class="ds-card-title" style="margin:0">الأبناء المرتبطون ({{ $children->count() }})</h5></div>
        @if($children->count() === 0)
            <div class="pc-log-empty">لا يوجد أبناء مرتبطون بهذا الحساب.</div>
        @else
        <div class="table-responsive">
            <table class="table ds-table-tight mb-0">
                <thead><tr><th>اسم الطالب</th><th>الصف / الفصل</th><th>رقم الهوية</th></tr></thead>
                <tbody>
                    @foreach($children as $child)
                        <tr>
                            <td style="font-weight:600">{{ $child->name ?: $child->username }}</td>
                            <td>{{ optional($child->classRoom)->name ?: '—' }}</td>
                            <td>{{ $child->national_id ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- CRM tabs --}}
    <div class="ds-card card">
        <div class="card-body">
            <ul class="nav nav-tabs crm-tabs" id="crmTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-complaints-btn" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-complaints" href="#tab-complaints" type="button" role="tab">
                        <x-svg-icon name="exclamation-triangle" :size="15" /> الشكاوى ({{ $complaints->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-visits-btn" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-visits" href="#tab-visits" type="button" role="tab">
                        <x-svg-icon name="geo-alt" :size="15" /> زيارة مدرسة ({{ $visits->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-calls-btn" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-calls" href="#tab-calls" type="button" role="tab">
                        <x-svg-icon name="telephone" :size="15" /> اتصال مجدول ({{ $calls->count() }})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-timeline-btn" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#tab-timeline" href="#tab-timeline" type="button" role="tab">
                        <x-svg-icon name="clock-history" :size="15" /> الخط الزمني ({{ count($timeline) }})
                    </button>
                </li>
            </ul>

            <div class="tab-content" style="padding-top:1rem">

                {{-- ===== Complaints tab ===== --}}
                <div class="tab-pane fade show active" id="tab-complaints" role="tabpanel">
                    @if($canManage)
                        <div style="text-align:end;margin-bottom:.6rem">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-toggle="modal" data-bs-target="#addComplaint" data-target="#addComplaint">
                                <x-svg-icon name="plus" :size="15" /> إضافة شكوى
                            </button>
                        </div>
                    @endif
                    @if($complaints->isEmpty())
                        <div class="pc-log-empty">لا توجد شكاوى مسجّلة.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table ds-table-tight mb-0">
                            <thead><tr>
                                <th>كود الشكوى</th><th>التاريخ</th><th>الغرض</th><th>الإجراءات التي تمت</th>
                                <th>الأولوية</th><th>الموظف المسؤول</th><th>الحالة</th>
                            </tr></thead>
                            <tbody>
                                @foreach($complaints as $c)
                                    @php $st = $complaintStatus[$c->status] ?? ['ds-badge-secondary', $c->status]; @endphp
                                    <tr>
                                        <td style="font-weight:700">{{ $c->code }}</td>
                                        <td dir="ltr" style="text-align:start">{{ $c->complaint_date?->format('Y-m-d') ?: '—' }}</td>
                                        <td>{{ $c->purpose ?: '—' }}</td>
                                        <td style="max-width:240px">{{ Str::limit($c->actions_taken, 80) ?: '—' }}</td>
                                        <td>{{ $priorityLabel[$c->priority] ?? $c->priority }}</td>
                                        <td>{{ optional($c->assignee)->name ?: '—' }}</td>
                                        <td><span class="{{ $st[0] }}">{{ $st[1] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- ===== Visits tab ===== --}}
                <div class="tab-pane fade" id="tab-visits" role="tabpanel">
                    @if($canManage)
                        <div style="text-align:end;margin-bottom:.6rem">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-toggle="modal" data-bs-target="#addVisit" data-target="#addVisit">
                                <x-svg-icon name="plus" :size="15" /> تسجيل زيارة
                            </button>
                        </div>
                    @endif
                    @if($visits->isEmpty())
                        <div class="pc-log-empty">لا توجد زيارات مسجّلة.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table ds-table-tight mb-0">
                            <thead><tr>
                                <th>التاريخ</th><th>الوقت</th><th>سبب الزيارة</th><th>الطالب</th>
                                <th>الموظف المقابِل</th><th>تاريخ المتابعة</th><th>الحالة</th>
                            </tr></thead>
                            <tbody>
                                @foreach($visits as $v)
                                    @php $st = $visitStatus[$v->status] ?? ['ds-badge-secondary', $v->status]; @endphp
                                    <tr>
                                        <td dir="ltr" style="text-align:start">{{ $v->visit_date?->format('Y-m-d') ?: '—' }}</td>
                                        <td dir="ltr" style="text-align:start">{{ $v->visit_time ?: '—' }}</td>
                                        <td>{{ $v->reason ?: '—' }}</td>
                                        <td>{{ optional($v->student)->name ?: '—' }}</td>
                                        <td>{{ optional($v->metStaff)->name ?: '—' }}</td>
                                        <td dir="ltr" style="text-align:start">{{ $v->followup_date?->format('Y-m-d') ?: '—' }}</td>
                                        <td><span class="{{ $st[0] }}">{{ $st[1] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- ===== Calls tab ===== --}}
                <div class="tab-pane fade" id="tab-calls" role="tabpanel">
                    @if($canManage)
                        <div style="text-align:end;margin-bottom:.6rem">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-toggle="modal" data-bs-target="#addCall" data-target="#addCall">
                                <x-svg-icon name="plus" :size="15" /> تسجيل اتصال
                            </button>
                        </div>
                    @endif
                    @if($calls->isEmpty())
                        <div class="pc-log-empty">لا توجد اتصالات مسجّلة.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table ds-table-tight mb-0">
                            <thead><tr>
                                <th>التاريخ</th><th>الوقت</th><th>النوع</th><th>الغرض</th>
                                <th>تم الرد؟</th><th>موعد المتابعة</th><th>الموظف المسؤول</th><th>الحالة</th>
                            </tr></thead>
                            <tbody>
                                @foreach($calls as $call)
                                    @php $st = $callStatus[$call->status] ?? ['ds-badge-secondary', $call->status]; @endphp
                                    <tr>
                                        <td dir="ltr" style="text-align:start">{{ $call->call_date?->format('Y-m-d') ?: '—' }}</td>
                                        <td dir="ltr" style="text-align:start">{{ $call->call_time ?: '—' }}</td>
                                        <td>{{ $call->call_type === 'incoming' ? 'وارد' : 'صادر' }}</td>
                                        <td>{{ $call->purpose ?: '—' }}</td>
                                        <td>@if($call->answered)<span class="ds-badge-success">نعم</span>@else<span class="ds-badge-warning">لا</span>@endif</td>
                                        <td dir="ltr" style="text-align:start">{{ $call->followup_at ? $call->followup_at->format('Y-m-d H:i') : '—' }}</td>
                                        <td>{{ optional($call->assignee)->name ?: '—' }}</td>
                                        <td><span class="{{ $st[0] }}">{{ $st[1] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>

                {{-- ===== Timeline tab ===== --}}
                <div class="tab-pane fade" id="tab-timeline" role="tabpanel">
                    @if(empty($timeline))
                        <div class="pc-log-empty">لا يوجد سجل تفاعلات بعد.</div>
                    @else
                    <ul class="tl">
                        @foreach($timeline as $ev)
                            @php $tm = $timelineMeta[$ev['kind']] ?? ['تصنيف' => $ev['kind'], 'cls' => '']; @endphp
                            <li class="{{ $tm['cls'] }}">
                                <span class="dot"></span>
                                <div class="tl-head">
                                    <span class="tl-title"><x-svg-icon :name="$ev['icon']" :size="14" /> {{ $ev['title'] }}</span>
                                    <span class="tl-at" dir="ltr">{{ $fmt($ev['at']) }}</span>
                                </div>
                                @if(!empty($ev['meta']))<div class="tl-meta">{{ $ev['meta'] }}</div>@endif
                            </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

            </div>
        </div>
    </div>

    @if($canManage)
    {{-- ===== Add Complaint modal ===== --}}
    <div class="modal fade" id="addComplaint" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.parents-contact.complaints.store', $parent->id) }}" enctype="multipart/form-data" class="modal-content">
          @csrf
          <div class="modal-header"><h5 class="modal-title">إضافة شكوى</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="إغلاق"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">الطالب المرتبط</label>
                <select name="student_id" class="form-select form-control"><option value="">—</option>
                  @foreach($children as $ch)<option value="{{ $ch->id }}">{{ $ch->name ?: $ch->username }}</option>@endforeach
                </select></div>
              <div class="col-md-6"><label class="form-label">نوع الشكوى</label><input name="type" class="form-control" maxlength="40"></div>
              <div class="col-md-6"><label class="form-label">تاريخ الشكوى *</label><input type="date" name="complaint_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
              <div class="col-md-6"><label class="form-label">الأولوية *</label>
                <select name="priority" class="form-select form-control" required>
                  <option value="low">منخفضة</option><option value="normal" selected>عادية</option>
                  <option value="high">مرتفعة</option><option value="urgent">عاجلة</option></select></div>
              <div class="col-12"><label class="form-label">الغرض من الشكوى *</label><input name="purpose" class="form-control" maxlength="255" required></div>
              <div class="col-12"><label class="form-label">التفاصيل</label><textarea name="details" class="form-control" rows="2"></textarea></div>
              <div class="col-md-6"><label class="form-label">الإجراء المطلوب</label><textarea name="action_required" class="form-control" rows="2"></textarea></div>
              <div class="col-md-6"><label class="form-label">الإجراءات التي تمت</label><textarea name="actions_taken" class="form-control" rows="2"></textarea></div>
              <div class="col-md-6"><label class="form-label">الموظف المسؤول (ID)</label><input type="number" name="assigned_to" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">الحالة *</label>
                <select name="status" class="form-select form-control" required>
                  <option value="new" selected>جديدة</option><option value="in_progress">قيد المعالجة</option>
                  <option value="awaiting_parent">بانتظار رد ولي الأمر</option>
                  <option value="resolved">تم الحل</option><option value="closed">مغلقة</option></select></div>
              <div class="col-12"><label class="form-label">مرفقات</label><input type="file" name="attachment" class="form-control"></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-primary">حفظ الشكوى</button>
          </div>
        </form>
      </div>
    </div>

    {{-- ===== Add Visit modal ===== --}}
    <div class="modal fade" id="addVisit" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.parents-contact.visits.store', $parent->id) }}" class="modal-content">
          @csrf
          <div class="modal-header"><h5 class="modal-title">تسجيل زيارة مدرسة</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="إغلاق"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">تاريخ الزيارة *</label><input type="date" name="visit_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
              <div class="col-md-6"><label class="form-label">وقت الزيارة</label><input type="time" name="visit_time" class="form-control"></div>
              <div class="col-12"><label class="form-label">سبب الزيارة *</label><input name="reason" class="form-control" maxlength="255" required></div>
              <div class="col-md-6"><label class="form-label">الطالب المرتبط</label>
                <select name="student_id" class="form-select form-control"><option value="">—</option>
                  @foreach($children as $ch)<option value="{{ $ch->id }}">{{ $ch->name ?: $ch->username }}</option>@endforeach
                </select></div>
              <div class="col-md-6"><label class="form-label">الموظف الذي قابله (ID)</label><input type="number" name="met_staff_id" class="form-control"></div>
              <div class="col-12"><label class="form-label">ملخص الزيارة</label><textarea name="summary" class="form-control" rows="2"></textarea></div>
              <div class="col-md-6"><label class="form-label">الإجراء التالي</label><textarea name="next_action" class="form-control" rows="2"></textarea></div>
              <div class="col-md-3"><label class="form-label">تاريخ المتابعة</label><input type="date" name="followup_date" class="form-control"></div>
              <div class="col-md-3"><label class="form-label">الحالة *</label>
                <select name="status" class="form-select form-control" required>
                  <option value="open" selected>مفتوحة</option><option value="followup">تحتاج متابعة</option><option value="done">منتهية</option></select></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-primary">حفظ الزيارة</button>
          </div>
        </form>
      </div>
    </div>

    {{-- ===== Add Call modal ===== --}}
    <div class="modal fade" id="addCall" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.parents-contact.calls.store', $parent->id) }}" class="modal-content">
          @csrf
          <div class="modal-header"><h5 class="modal-title">تسجيل اتصال مجدول</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="إغلاق"></button></div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-4"><label class="form-label">تاريخ الاتصال *</label><input type="date" name="call_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
              <div class="col-md-4"><label class="form-label">وقت الاتصال</label><input type="time" name="call_time" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">نوع الاتصال *</label>
                <select name="call_type" class="form-select form-control" required>
                  <option value="outgoing" selected>صادر</option><option value="incoming">وارد</option></select></div>
              <div class="col-12"><label class="form-label">الغرض *</label><input name="purpose" class="form-control" maxlength="255" required></div>
              <div class="col-12"><label class="form-label">نتيجة الاتصال</label><textarea name="outcome" class="form-control" rows="2"></textarea></div>
              <div class="col-md-4"><label class="form-label">هل تم الرد؟</label>
                <select name="answered" class="form-select form-control"><option value="0" selected>لا</option><option value="1">نعم</option></select></div>
              <div class="col-md-4"><label class="form-label">موعد متابعة جديد</label><input type="datetime-local" name="followup_at" class="form-control"></div>
              <div class="col-md-4"><label class="form-label">الموظف المسؤول (ID)</label><input type="number" name="assigned_to" class="form-control"></div>
              <div class="col-12"><label class="form-label">ملاحظات</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
              <div class="col-md-4"><label class="form-label">الحالة *</label>
                <select name="status" class="form-select form-control" required>
                  <option value="scheduled" selected>مجدول</option><option value="done">تم</option><option value="missed">فائت</option></select></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-primary">حفظ الاتصال</button>
          </div>
        </form>
      </div>
    </div>
    @endif

</section>
@endsection
