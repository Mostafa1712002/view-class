@extends('layouts.app')

@section('title', 'الإعلانات')
@section('page-title', 'الإعلانات')
@section('body_class', 'theme-light')

@php
    use App\Models\Announcement;
    $user = auth()->user();
    $statusMeta = [
        'draft'     => ['مسودة', 'secondary', 'pencil-square'],
        'scheduled' => ['مجدول', 'info', 'clock-history'],
        'active'    => ['نشط', 'success', 'check-circle-fill'],
        'expired'   => ['منتهي', 'warning', 'hourglass-bottom'],
        'stopped'   => ['متوقف', 'danger', 'pause-circle-fill'],
        'deleted'   => ['محذوف', 'dark', 'trash3-fill'],
    ];
    $targetLabels = [
        'all' => 'كل المستخدمين', 'students' => 'الطلاب', 'teachers' => 'المعلمون',
        'parents' => 'أولياء الأمور', 'admins' => 'الإداريون',
        'specific_users' => 'مستخدمون محددون', 'specific_roles' => 'أدوار محددة',
        'job_titles' => 'مسميات وظيفية محددة',
    ];
    $typeLabels = ['normal' => 'عادي', 'important' => 'مهم', 'popup' => 'منبثق'];

    // Map status to ds-badge class
    $statusBadgeClass = [
        'draft'     => 'ds-badge-warning',
        'scheduled' => 'ds-badge-info',
        'active'    => 'ds-badge-success',
        'expired'   => 'ds-badge-danger',
        'stopped'   => 'ds-badge-danger',
        'deleted'   => 'ds-badge-danger',
    ];
    // Map target to ds-badge class
    $targetBadgeClass = [
        'all'            => 'ds-badge-navy',
        'students'       => 'ds-badge-info',
        'teachers'       => 'ds-badge-gold',
        'parents'        => 'ds-badge-gold',
        'admins'         => 'ds-badge-navy',
        'specific_users' => 'ds-badge-info',
        'specific_roles' => 'ds-badge-warning',
        'job_titles'     => 'ds-badge-gold',
    ];
@endphp

@section('content')
<section class="vc-ann" id="announcements">

    {{-- Page header + breadcrumb --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.45rem;font-weight:800;color:var(--gray-900)">الإعلانات</h2>
            <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item active" aria-current="page">الإعلانات</li>
            </ol></nav>
        </div>
        @if($user->canDo('announcements.create'))
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                <x-svg-icon name="plus-lg" :size="16" /> إضافة إعلان
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filter / search toolbar --}}
    <div class="ds-card card" style="margin-bottom:1rem">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title" style="margin:0;font-size:.9rem;display:flex;align-items:center;gap:.35rem">
                <x-svg-icon name="funnel" :size="15" /> تصفية الإعلانات
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.announcements.index') }}" class="row" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end">
                <div style="flex:2;min-width:200px">
                    <label class="form-label">بحث</label>
                    <input type="text" name="search" class="form-control" placeholder="ابحث في العنوان أو التفاصيل" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div style="flex:1;min-width:150px">
                    <label class="form-label">الحالة</label>
                    <select name="status" class="form-control">
                        <option value="">الكل</option>
                        @foreach($statusMeta as $key => $meta)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $meta[0] }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1;min-width:140px">
                    <label class="form-label">النوع</label>
                    <select name="type" class="form-control">
                        <option value="">الكل</option>
                        @foreach($typeLabels as $key => $lbl)
                            <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="flex:1;min-width:160px">
                    <label class="form-label">الفئة المستهدفة</label>
                    <select name="target_type" class="form-control">
                        <option value="">الكل</option>
                        @foreach($targetLabels as $key => $lbl)
                            <option value="{{ $key }}" @selected(($filters['target_type'] ?? '') === $key)>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:.4rem">
                    <button type="submit" class="btn btn-primary"><x-svg-icon name="funnel-fill" :size="16" /> تصفية</button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary"><x-svg-icon name="arrow-clockwise" :size="16" /></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Secondary toolbar: column-customize / export --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.6rem;flex-wrap:wrap;gap:.5rem">
        <span class="badge ds-badge-navy">{{ number_format($announcements->total()) }} إعلان</span>
        <div style="display:flex;gap:.4rem">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('annColsPanel').classList.toggle('d-none')">
                <x-svg-icon name="layout-three-columns" :size="14" /> تخصيص الأعمدة
            </button>
            <a href="{{ route('admin.announcements.index', array_merge(request()->query(), ['export' => 'csv'])) }}"
               class="btn btn-sm btn-outline-secondary"
               onclick="event.preventDefault(); exportAnnouncementsTable();">
                <x-svg-icon name="download" :size="14" /> تصدير إلى CSV
            </a>
        </div>
    </div>

    <div id="annColsPanel" class="ds-card card d-none" style="margin-bottom:.6rem">
        <div class="card-body" style="display:flex;flex-wrap:wrap;gap:1rem">
            @foreach(['title'=>'العنوان','target'=>'الفئة المستهدفة','school'=>'المدرسة','starts'=>'تاريخ البدء','ends'=>'تاريخ الانتهاء','status'=>'الحالة','creator'=>'منشئ الإعلان','created'=>'تاريخ الإنشاء'] as $col => $lbl)
                <label style="display:flex;gap:.3rem;align-items:center"><input type="checkbox" class="ann-col-toggle" data-col="{{ $col }}" checked> {{ $lbl }}</label>
            @endforeach
        </div>
    </div>

    {{-- Main table card --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header" style="display:flex;align-items:center;gap:.4rem">
            <x-svg-icon name="megaphone" :size="17" />
            <h5 class="ds-card-title" style="margin:0">قائمة الإعلانات</h5>
        </div>

        @if($announcements->count() === 0)
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="megaphone-fill" :size="30" /></div>
                <div class="ds-empty-title">لا توجد إعلانات</div>
                <div class="ds-empty-desc">لم يتم إنشاء أي إعلان بعد. ابدأ بإضافة إعلان جديد.</div>
                @if($user->canDo('announcements.create'))
                    <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary" style="margin-top:.5rem">
                        <x-svg-icon name="plus-lg" :size="16" /> إضافة إعلان
                    </a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="announcementsTable">
                    <thead>
                        <tr>
                            <th style="width:36px"><input type="checkbox" id="annCheckAll"></th>
                            <th data-col="title">العنوان</th>
                            <th data-col="target">الفئة المستهدفة</th>
                            <th data-col="school">المدرسة</th>
                            <th data-col="starts">تاريخ البدء</th>
                            <th data-col="ends">تاريخ الانتهاء</th>
                            <th data-col="status">الحالة</th>
                            <th data-col="creator">منشئ الإعلان</th>
                            <th data-col="created">تاريخ الإنشاء</th>
                            <th style="width:60px">التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($announcements as $a)
                        @php
                            $eff = $a->effectiveStatus();
                            $sm = $statusMeta[$eff] ?? ['—','secondary','dot'];
                            $typeLbl = $typeLabels[$a->type] ?? $a->type;
                            $sBadge = $statusBadgeClass[$eff] ?? 'ds-badge-warning';
                            $tBadge = $targetBadgeClass[$a->target_type] ?? 'ds-badge-navy';
                        @endphp
                        <tr>
                            <td><input type="checkbox" class="ann-row-check" value="{{ $a->id }}"></td>
                            <td data-col="title">
                                <strong>{{ $a->title }}</strong>
                                @if($a->type !== 'normal')
                                    <span class="badge {{ $a->type === 'important' ? 'ds-badge-warning' : 'ds-badge-info' }}" style="margin-inline-start:.3rem">{{ $typeLbl }}</span>
                                @endif
                            </td>
                            <td data-col="target">
                                <span class="badge {{ $tBadge }}">{{ $targetLabels[$a->target_type] ?? $a->target_type }}</span>
                            </td>
                            <td data-col="school">{{ optional($a->school)->name ?? '—' }}</td>
                            <td data-col="starts">{{ $a->starts_at ? $a->starts_at->format('Y-m-d H:i') : '—' }}</td>
                            <td data-col="ends">{{ $a->ends_at ? $a->ends_at->format('Y-m-d H:i') : '—' }}</td>
                            <td data-col="status">
                                <span class="badge {{ $sBadge }}">
                                    <x-svg-icon :name="$sm[2]" :size="12" /> {{ $sm[0] }}
                                </span>
                            </td>
                            <td data-col="creator">{{ optional($a->creator)->name ?? '—' }}</td>
                            <td data-col="created">{{ $a->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="dropdown">
                                    <button type="button" class="ds-action-btn" data-toggle="dropdown" aria-label="خيارات" title="خيارات">
                                        <x-svg-icon name="three-dots-vertical" :size="16" />
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @if($user->canDo('announcements.view'))
                                            <a class="dropdown-item" href="{{ route('admin.announcements.show', $a->id) }}">
                                                <x-svg-icon name="eye-fill" :size="14" /> عرض
                                            </a>
                                        @endif
                                        @if($user->canDo('announcements.edit'))
                                            <a class="dropdown-item" href="{{ route('admin.announcements.edit', $a->id) }}">
                                                <x-svg-icon name="pencil-square" :size="14" /> تعديل
                                            </a>
                                        @endif
                                        @if($user->canDo('announcements.publish') && $a->status !== 'published')
                                            <form method="POST" action="{{ route('admin.announcements.activate', $a->id) }}">@csrf
                                                <button class="dropdown-item" type="submit"><x-svg-icon name="play-circle-fill" :size="14" /> تفعيل</button>
                                            </form>
                                        @endif
                                        @if($user->canDo('announcements.publish') && $a->status === 'published')
                                            <form method="POST" action="{{ route('admin.announcements.stop', $a->id) }}">@csrf
                                                <button class="dropdown-item" type="submit"><x-svg-icon name="pause-circle-fill" :size="14" /> إيقاف</button>
                                            </form>
                                        @endif
                                        @if($user->canDo('announcements.create'))
                                            <form method="POST" action="{{ route('admin.announcements.duplicate', $a->id) }}">@csrf
                                                <button class="dropdown-item" type="submit"><x-svg-icon name="files" :size="14" /> نسخ</button>
                                            </form>
                                        @endif
                                        @if($user->canDo('announcements.read_log'))
                                            <a class="dropdown-item" href="{{ route('admin.announcements.read-log', $a->id) }}">
                                                <x-svg-icon name="list-check" :size="14" /> سجل القراءة
                                            </a>
                                        @endif
                                        @if($user->canDo('announcements.delete'))
                                            <div class="dropdown-divider"></div>
                                            <form method="POST" action="{{ route('admin.announcements.destroy', $a->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الإعلان؟')">@csrf @method('DELETE')
                                                <button class="dropdown-item text-danger" type="submit">
                                                    <x-svg-icon name="trash3-fill" :size="14" /> حذف
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($announcements->hasPages())
                <div class="card-footer">{{ $announcements->links() }}</div>
            @endif
        @endif
    </div>

</section>
@endsection

@push('scripts')
<script>
(function () {
    var all = document.getElementById('annCheckAll');
    if (all) all.addEventListener('change', function () {
        document.querySelectorAll('.ann-row-check').forEach(function (c) { c.checked = all.checked; });
    });
    document.querySelectorAll('.ann-col-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            document.querySelectorAll('[data-col="' + cb.dataset.col + '"]').forEach(function (el) {
                el.style.display = cb.checked ? '' : 'none';
            });
        });
    });
    window.exportAnnouncementsTable = function () {
        var rows = [], table = document.getElementById('announcementsTable');
        if (!table) return;
        table.querySelectorAll('tr').forEach(function (tr) {
            var cells = [];
            tr.querySelectorAll('th[data-col],td[data-col]').forEach(function (c) {
                cells.push('"' + c.innerText.trim().replace(/"/g, '""') + '"');
            });
            if (cells.length) rows.push(cells.join(','));
        });
        var blob = new Blob(["﻿" + rows.join("\n")], { type: 'text/csv;charset=utf-8;' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'announcements.csv';
        a.click();
    };
})();
</script>
@endpush
