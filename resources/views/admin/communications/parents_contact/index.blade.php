@extends('layouts.app')

@section('title', 'التواصل مع أولياء الأمور')
@section('page-title', 'التواصل مع أولياء الأمور')
@section('body_class', 'theme-light')

@php
    $isRtl = app()->getLocale() === 'ar';
    $total = $parents->total();
    $pageMail = 0; $pageWa = 0; $pageNotif = 0;
    foreach ($parents as $p) {
        $pageMail  += (int) ($p->mail_count ?? 0);
        $pageWa    += (int) ($p->whatsapp_count ?? 0);
        $pageNotif += (int) ($p->notification_count ?? 0);
    }
@endphp

@push('styles')
<style>
    .pc-header { margin-bottom: 1.1rem; }
    .pc-header h2 { font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-bottom: .1rem; }
    .pc-header .breadcrumb { padding: 0; margin: 0; background: transparent; font-size: .85rem; }
    .pc-search-bar {
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
        border-radius: 14px; padding: .85rem 1rem; margin-bottom: 1.1rem;
        box-shadow: 0 6px 18px rgba(37,99,235,.18);
    }
    .pc-search-bar .pc-search-title { color: #fff; font-weight: 700; font-size: .95rem; margin-bottom: .55rem; display:flex; align-items:center; gap:.4rem; }
    .pc-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .75rem; margin-bottom: 1.1rem; }
    .pc-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:.8rem 1rem; display:flex; align-items:center; gap:.7rem; }
    .pc-kpi .ico { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .pc-kpi .ico.b1 { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e; }
    .pc-kpi .ico.b2 { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1d4ed8; }
    .pc-kpi .ico.b3 { background:linear-gradient(135deg,#dcfce7,#bbf7d0); color:#15803d; }
    .pc-kpi .ico.b4 { background:linear-gradient(135deg,#ede9fe,#ddd6fe); color:#6d28d9; }
    .pc-kpi .num { font-size:1.3rem; font-weight:800; color:#0f172a; line-height:1.1; }
    .pc-kpi .lbl { font-size:.78rem; color:#64748b; }
    .pc-count-pill { display:inline-flex; min-width:1.9rem; justify-content:center; padding:.12rem .5rem; border-radius:999px; font-weight:700; font-size:.8rem; }
    .pc-count-pill.muted { background:#f1f5f9; color:#94a3b8; }
    .pc-actions { display:flex; gap:.3rem; flex-wrap:wrap; }
    @media (max-width: 768px){ .pc-kpis{ grid-template-columns: repeat(2,minmax(0,1fr)); } }
</style>
@endpush

@section('content')
<section class="pc-wrap" id="parents-contact" @if($isRtl) dir="rtl" @endif>

    <div class="pc-header" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem">
        <div>
            <h2>أولياء الأمور</h2>
            <nav><ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item active" aria-current="page">أولياء الأمور</li>
            </ol></nav>
        </div>
        <div class="pc-actions">
            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="قريبًا">
                <x-svg-icon name="funnel" :size="15" /> تصفية
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="قريبًا">
                <x-svg-icon name="layout-three-columns" :size="15" /> تخصيص الأعمدة
            </button>
            <a href="{{ route('admin.parents-contact.export', request()->query()) }}" class="btn btn-outline-primary btn-sm">
                <x-svg-icon name="download" :size="15" /> تصدير إلى CSV
            </a>
        </div>
    </div>

    {{-- Blue search bar --}}
    <div class="pc-search-bar">
        <div class="pc-search-title"><x-svg-icon name="search" :size="16" /> البحث</div>
        <form method="GET" action="{{ route('admin.parents-contact.index') }}" role="search">
            <div style="display:flex;gap:.5rem;flex-wrap:wrap">
                <input type="text" name="q" value="{{ $q }}" class="form-control"
                       style="flex:1;min-width:220px" aria-label="ابحث عن ولي أمر"
                       placeholder="ابحث بالاسم أو رقم الجوال أو الجنسية أو الهوية…">
                <button type="submit" class="btn btn-light">بحث</button>
                @if($q)
                    <a href="{{ route('admin.parents-contact.index') }}" class="btn btn-light">إلغاء</a>
                @endif
            </div>
        </form>
    </div>

    {{-- KPI strip --}}
    <div class="pc-kpis">
        <div class="pc-kpi"><div class="ico b1"><x-svg-icon name="people" :size="18" /></div>
            <div><div class="num">{{ number_format($total) }}</div><div class="lbl">إجمالي أولياء الأمور</div></div></div>
        <div class="pc-kpi"><div class="ico b2"><x-svg-icon name="envelope" :size="18" /></div>
            <div><div class="num">{{ number_format($pageMail) }}</div><div class="lbl">رسائل بريد (هذه الصفحة)</div></div></div>
        <div class="pc-kpi"><div class="ico b3"><x-svg-icon name="whatsapp" :size="18" /></div>
            <div><div class="num">{{ number_format($pageWa) }}</div><div class="lbl">رسائل واتساب (هذه الصفحة)</div></div></div>
        <div class="pc-kpi"><div class="ico b4"><x-svg-icon name="bell" :size="18" /></div>
            <div><div class="num">{{ number_format($pageNotif) }}</div><div class="lbl">إشعارات (هذه الصفحة)</div></div></div>
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- Table card --}}
    <div class="ds-card card">
        <div class="ds-card-header card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
            <h5 class="ds-card-title" style="margin:0">أولياء الأمور</h5>
            <span class="text-muted" style="font-size:.82rem">{{ number_format($total) }} ولي أمر</span>
        </div>

        @if($parents->count() === 0)
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="people" :size="30" /></div>
                <div class="ds-empty-title">لا يوجد أولياء أمور</div>
                <div class="ds-empty-desc">
                    @if($q) لا توجد نتائج مطابقة لبحثك. @else لم تتم إضافة أولياء أمور بعد. @endif
                </div>
            </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover ds-table-tight mb-0" aria-label="جدول أولياء الأمور">
                <thead>
                    <tr>
                        <th scope="col">اسم ولي الأمر</th>
                        <th scope="col">الجنسية</th>
                        <th scope="col">رقم الجوال</th>
                        <th scope="col" class="text-center">عدد الأبناء</th>
                        <th scope="col" class="text-center">رسائل بريد</th>
                        <th scope="col" class="text-center">رسائل واتساب</th>
                        <th scope="col" class="text-center">إشعارات</th>
                        <th scope="col" class="text-end">التحكم</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parents as $p)
                        @php
                            $mail = (int) ($p->mail_count ?? 0);
                            $wa   = (int) ($p->whatsapp_count ?? 0);
                            $nt   = (int) ($p->notification_count ?? 0);
                            $kids = (int) ($p->children_count ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.parents-contact.show', $p->id) }}" style="font-weight:700;color:#0f172a;text-decoration:none">
                                    {{ $p->name ?: $p->username }}
                                </a>
                                @if($p->email && !\Illuminate\Support\Str::endsWith($p->email, '@viewclass.local'))
                                    <div class="text-muted" style="font-size:.76rem">{{ $p->email }}</div>
                                @endif
                            </td>
                            <td>{{ $p->nationality ?: '—' }}</td>
                            <td dir="ltr" style="text-align:start">{{ $p->phone ?: '—' }}</td>
                            <td class="text-center"><span class="pc-count-pill {{ $kids ? 'ds-badge-info' : 'muted' }}">{{ $kids }}</span></td>
                            <td class="text-center"><span class="pc-count-pill {{ $mail ? 'ds-badge-warning' : 'muted' }}">{{ $mail }}</span></td>
                            <td class="text-center"><span class="pc-count-pill {{ $wa ? 'ds-badge-success' : 'muted' }}">{{ $wa }}</span></td>
                            <td class="text-center"><span class="pc-count-pill {{ $nt ? 'ds-badge-navy' : 'muted' }}">{{ $nt }}</span></td>
                            <td class="text-end">
                                <div class="pc-actions" style="justify-content:flex-end">
                                    <a href="{{ route('admin.parents-contact.show', $p->id) }}" class="ds-action-btn" title="عرض سجل التواصل" aria-label="عرض سجل التواصل">
                                        <x-svg-icon name="eye" :size="15" />
                                    </a>
                                    @if(Route::has('admin.users.parents.show'))
                                        <a href="{{ route('admin.users.parents.show', $p->id) }}" class="ds-action-btn" title="ملف ولي الأمر" aria-label="ملف ولي الأمر">
                                            <x-svg-icon name="person-badge" :size="15" />
                                        </a>
                                    @endif
                                    @if(Route::has('admin.users.parents.students'))
                                        <a href="{{ route('admin.users.parents.students', $p->id) }}" class="ds-action-btn" title="عرض الأبناء" aria-label="عرض الأبناء">
                                            <x-svg-icon name="people" :size="15" />
                                        </a>
                                    @endif
                                    @if($canManage && Route::has('admin.users.parents.edit'))
                                        <a href="{{ route('admin.users.parents.edit', $p->id) }}" class="ds-action-btn" title="تعديل" aria-label="تعديل">
                                            <x-svg-icon name="pencil" :size="15" />
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer" style="background:#fff;border-top:1px solid #f1f5f9">
            {{ $parents->links() }}
        </div>
        @endif
    </div>

</section>
@endsection
