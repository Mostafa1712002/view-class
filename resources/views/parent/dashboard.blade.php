@extends('layouts.admin')

@section('title', 'لوحة ولي الأمر')
@section('body_class', 'theme-light')

@push('styles')
<style>
    .pd-hero {
        background: linear-gradient(135deg, #fffbeb 0%, #fff 60%);
        border: 1px solid #fde68a; border-radius: 16px;
        padding: 1.1rem 1.25rem; margin-bottom: 1.25rem;
        display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
        box-shadow: 0 1px 2px rgba(207,160,70,.06), 0 6px 18px rgba(207,160,70,.05);
    }
    .pd-hero .logo {
        width: 54px; height: 54px; border-radius: 12px; background: #fff;
        border: 1px solid #fde68a; display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; overflow: hidden;
    }
    .pd-hero .logo img { max-width: 80%; max-height: 80%; object-fit: contain; }
    .pd-hero h1 { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -.3px; }
    .pd-hero .sub { color: #92400e; font-size: .9rem; }
    .pd-hero .yr { margin-inline-start: auto; }
    .pd-hero .yr .pill {
        background: #fff; border: 1px solid #fde68a; color: #92400e;
        padding: .35rem .75rem; border-radius: 999px; font-weight: 600; font-size: .82rem;
    }

    .pd-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; overflow: hidden;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 12px rgba(15,23,42,.04);
        transition: transform .2s ease, box-shadow .2s ease; height: 100%;
    }
    .pd-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,23,42,.07); }
    .pd-card .head {
        background: linear-gradient(135deg, var(--gold-300), var(--gold-500));
        color: #fff; padding: .9rem 1.1rem;
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    }
    .pd-card .head .nm { font-weight: 700; font-size: 1.02rem; }
    .pd-card .head .cls { font-size: .8rem; opacity: .9; }
    .pd-card .head .btn-light {
        background: #fff; color: #92400e; border: 0; border-radius: 8px;
        padding: .3rem .7rem; font-size: .8rem; font-weight: 600; text-decoration: none;
    }
    .pd-card .body { padding: 1.1rem; }

    .pd-stats { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-bottom: 1rem; }
    .pd-stat { text-align: center; padding: .75rem .5rem; border-radius: 12px; border: 1px solid #f1f5f9; background: #fafbfc; }
    .pd-stat .v { font-size: 1.35rem; font-weight: 800; line-height: 1.1; }
    .pd-stat .v.good { color: #15803d; } .pd-stat .v.warn { color: #b45309; } .pd-stat .v.bad { color: #b91c1c; }
    .pd-stat .l { font-size: .76rem; color: #64748b; margin-top: .15rem; }

    .pd-sec-title { font-size: .82rem; font-weight: 700; color: #334155; border-bottom: 1px solid #f1f5f9; padding-bottom: .4rem; margin-bottom: .55rem; display: flex; align-items: center; gap: .4rem; }
    .pd-sec-title i { color: var(--gold-400); }
    .pd-line { display: flex; align-items: center; justify-content: space-between; margin-bottom: .35rem; font-size: .85rem; color: #0f172a; }
    .pd-badge { padding: .15rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 600; }
    .pd-badge.ok { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .pd-badge.no { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .pd-badge.muted { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
    .pd-empty { color: #94a3b8; font-size: .82rem; }

    .pd-foot { display: flex; gap: .45rem; padding: .85rem 1.1rem; border-top: 1px solid #f1f5f9; background: #fff; flex-wrap: wrap; }
    .pd-foot a {
        flex: 1 1 0; text-align: center; text-decoration: none;
        border: 1px solid #e2e8f0; border-radius: 10px; padding: .45rem .3rem;
        font-size: .82rem; color: #475569; font-weight: 600; transition: all .15s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: .3rem;
    }
    .pd-foot a:hover { border-color: var(--gold-300); color: var(--gold-500); background: #fffbeb; }

    .pd-empty-state { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; text-align: center; padding: 3rem 1rem; }
    .pd-empty-state i { font-size: 3rem; color: #cbd5e1; }
    .pd-empty-state h4 { color: #0f172a; margin-top: .75rem; font-weight: 700; }
    .pd-empty-state p { color: #64748b; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="pd-hero">
        <div class="logo">
            <img src="{{ asset('img/brand/al-awwal-logo.png') }}" alt="@lang('auth.app_name')" onerror="this.style.display='none'">
        </div>
        <div>
            <h1>مرحباً {{ $parent->name }}</h1>
            <div class="sub">بوابة ولي الأمر — متابعة أبنائك التعليمية</div>
        </div>
        @if($academicYear)
            <div class="yr"><span class="pill"><i class="la la-calendar"></i> {{ $academicYear->name }}</span></div>
        @endif
    </div>

    @if($childrenData->count() > 0)
        <div class="row">
            @foreach($childrenData as $childData)
                <div class="col-md-6 mb-4">
                    <div class="pd-card">
                        <div class="head">
                            <div>
                                <div class="nm">{{ $childData['student']->name }}</div>
                                <div class="cls">{{ $childData['class']?->name ?? 'غير مسجل في صف' }}</div>
                            </div>
                            <a href="{{ route('parent.child', $childData['student']) }}" class="btn-light">التفاصيل</a>
                        </div>
                        <div class="body">
                            <div class="pd-stats">
                                <div class="pd-stat">
                                    <div class="v {{ $childData['attendance_rate'] >= 80 ? 'good' : 'warn' }}">{{ $childData['attendance_rate'] }}%</div>
                                    <div class="l">نسبة الحضور</div>
                                </div>
                                <div class="pd-stat">
                                    <div class="v {{ ($childData['grade_average'] ?? 0) >= 50 ? 'good' : 'bad' }}">{{ $childData['grade_average'] ?? '-' }}%</div>
                                    <div class="l">المعدل العام</div>
                                </div>
                            </div>

                            <div class="pd-sec-title"><i class="la la-calendar-check"></i> آخر سجلات الحضور</div>
                            <div style="margin-bottom:1rem;">
                                @forelse($childData['recent_attendance']->take(3) as $attendance)
                                    <div class="pd-line">
                                        <span>{{ $attendance->date->format('Y/m/d') }}</span>
                                        <span class="pd-badge {{ in_array($attendance->status, ['present','late']) ? 'ok' : 'no' }}">{{ $attendance->status_label ?? $attendance->status }}</span>
                                    </div>
                                @empty
                                    <div class="pd-empty">لا توجد سجلات</div>
                                @endforelse
                            </div>

                            <div class="pd-sec-title"><i class="la la-star"></i> آخر الدرجات</div>
                            <div>
                                @forelse($childData['recent_grades'] as $grade)
                                    <div class="pd-line">
                                        <span>{{ $grade->subject->name ?? '-' }}</span>
                                        <span class="pd-badge {{ $grade->total >= 50 ? 'ok' : 'no' }}">{{ number_format($grade->total, 1) }}%</span>
                                    </div>
                                @empty
                                    <div class="pd-empty">لا توجد درجات</div>
                                @endforelse
                            </div>
                        </div>
                        <div class="pd-foot">
                            <a href="{{ route('parent.child.grades', $childData['student']) }}"><i class="la la-award"></i> الدرجات</a>
                            <a href="{{ route('parent.child.attendance', $childData['student']) }}"><i class="la la-calendar-check"></i> الحضور</a>
                            <a href="{{ route('parent.child.schedule', $childData['student']) }}"><i class="la la-calendar"></i> الجدول</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="pd-empty-state">
            <i class="la la-user-friends"></i>
            <h4>لا يوجد أبناء مسجلين</h4>
            <p>يرجى التواصل مع إدارة المدرسة لربط حسابك بأبنائك</p>
        </div>
    @endif
</div>
@endsection
