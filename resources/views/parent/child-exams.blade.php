@extends('layouts.admin')

@section('title', 'اختبارات ' . $child->name)
@section('body_class', 'theme-light')

@push('styles')
<style>
    .ce-header { margin-bottom: 1.1rem; }
    .ce-back {
        display: inline-flex; align-items: center; gap: .4rem;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 9px;
        padding: .38rem .8rem; font-size: .85rem; color: #475569; text-decoration: none;
        font-weight: 500; transition: all .15s ease; margin-bottom: .75rem;
    }
    .ce-back:hover { border-color: var(--gold-300); color: var(--gold-500); background: #fffbeb; }

    .ce-title { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -.2px; }
    .ce-sub { color: #64748b; font-size: .9rem; }

    .ce-year-bar {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
        padding: .75rem 1rem; margin-bottom: 1.25rem;
        display: flex; align-items: center; gap: .75rem; flex-wrap: wrap;
    }
    .ce-year-bar label { font-size: .85rem; color: #475569; font-weight: 600; white-space: nowrap; }
    .ce-year-bar select {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
        padding: .4rem .7rem; font-size: .88rem; color: #0f172a;
        transition: border-color .15s ease;
    }
    .ce-year-bar select:focus { border-color: var(--gold-300); outline: none; }

    .ce-section-title {
        font-size: .9rem; font-weight: 700; color: #334155;
        display: flex; align-items: center; gap: .45rem;
        margin-bottom: .9rem; padding-bottom: .5rem; border-bottom: 2px solid #f1f5f9;
    }
    .ce-section-title i { color: var(--gold-400); font-size: 1.1rem; }

    .ce-card {
        background: #fff; border: 1px solid #e5e7eb; border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 4px 10px rgba(15,23,42,.03);
        margin-bottom: .75rem; padding: 1rem 1.1rem;
        display: flex; align-items: flex-start; gap: 1rem;
        transition: box-shadow .15s ease, transform .15s ease;
    }
    .ce-card:hover { box-shadow: 0 4px 14px rgba(15,23,42,.07); transform: translateY(-1px); }

    .ce-card .ce-ico {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .ce-ico.upcoming { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1d4ed8; }
    .ce-ico.completed { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #15803d; }

    .ce-card .ce-info { flex: 1; min-width: 0; }
    .ce-card .ce-name { font-weight: 700; color: #0f172a; font-size: .95rem; margin-bottom: .2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ce-card .ce-meta { font-size: .8rem; color: #64748b; display: flex; flex-wrap: wrap; gap: .5rem; }
    .ce-card .ce-meta span { display: inline-flex; align-items: center; gap: .2rem; }

    .ce-pill {
        padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        display: inline-flex; align-items: center; gap: .25rem; white-space: nowrap;
    }
    .ce-pill.upcoming { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .ce-pill.completed { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .ce-pill.marks { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }

    .ce-empty { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 2rem 1rem; text-align: center; }
    .ce-empty i { font-size: 2.25rem; color: #cbd5e1; display: block; margin-bottom: .5rem; }
    .ce-empty p { color: #64748b; font-size: .9rem; margin: 0; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Back + heading --}}
    <div class="ce-header">
        <a href="{{ route('parent.child', $child) }}" class="ce-back">
            <i class="la la-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}"></i>
            رجوع
        </a>
        <div class="ce-title">اختبارات {{ $child->name }}</div>
        <div class="ce-sub">عرض الاختبارات القادمة والمنتهية للعام الدراسي</div>
    </div>

    {{-- Year selector --}}
    <form method="GET" class="ce-year-bar">
        <label for="ce-year"><x-svg-icon name="calendar" /> العام الدراسي</label>
        <select name="academic_year_id" id="ce-year" onchange="this.form.submit()">
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" {{ $academicYear?->id == $year->id ? 'selected' : '' }}>
                    {{ $year->name }}
                </option>
            @endforeach
        </select>
    </form>

    {{-- Upcoming exams --}}
    <div class="ce-section-title">
        <x-svg-icon name="clock" /> الاختبارات القادمة
        <span class="ce-pill upcoming ms-1">{{ $upcomingExams->count() }}</span>
    </div>

    @if($upcomingExams->count() > 0)
        @foreach($upcomingExams as $exam)
            <div class="ce-card">
                <div class="ce-ico upcoming"><x-svg-icon name="file-earmark-text" /></div>
                <div class="ce-info">
                    <div class="ce-name">{{ $exam->title }}</div>
                    <div class="ce-meta">
                        @if($exam->subject)
                            <span><x-svg-icon name="book" /> {{ $exam->subject->name }}</span>
                        @endif
                        <span><x-svg-icon name="calendar" /> {{ $exam->start_time->format('Y/m/d') }}</span>
                        <span><x-svg-icon name="clock" /> {{ $exam->start_time->format('H:i') }}</span>
                        @if($exam->duration_minutes)
                            <span><x-svg-icon name="hourglass-split" /> {{ $exam->duration_minutes }} دقيقة</span>
                        @endif
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <span class="ce-pill upcoming"><x-svg-icon name="clock" /> قادم</span>
                    <div class="mt-1">
                        <span class="ce-pill marks"><x-svg-icon name="star" /> {{ $exam->total_marks }} درجة</span>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="ce-empty mb-4">
            <x-svg-icon name="calendar-x" />
            <p>لا توجد اختبارات قادمة</p>
        </div>
    @endif

    {{-- Completed exams --}}
    <div class="ce-section-title mt-3">
        <x-svg-icon name="check-circle" /> الاختبارات المنتهية
        <span class="ce-pill completed ms-1">{{ $completedExams->count() }}</span>
    </div>

    @if($completedExams->count() > 0)
        @foreach($completedExams as $exam)
            <div class="ce-card">
                <div class="ce-ico completed"><x-svg-icon name="check-circle" /></div>
                <div class="ce-info">
                    <div class="ce-name">{{ $exam->title }}</div>
                    <div class="ce-meta">
                        @if($exam->subject)
                            <span><x-svg-icon name="book" /> {{ $exam->subject->name }}</span>
                        @endif
                        <span><x-svg-icon name="calendar" /> {{ $exam->start_time->format('Y/m/d') }}</span>
                        @if($exam->duration_minutes)
                            <span><x-svg-icon name="hourglass-split" /> {{ $exam->duration_minutes }} دقيقة</span>
                        @endif
                    </div>
                </div>
                <div class="text-end flex-shrink-0">
                    <span class="ce-pill completed"><x-svg-icon name="check" /> منتهي</span>
                    <div class="mt-1">
                        <span class="ce-pill marks"><x-svg-icon name="star" /> {{ $exam->total_marks }} درجة</span>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="ce-empty">
            <x-svg-icon name="file-earmark-text" />
            <p>لا توجد اختبارات منتهية</p>
        </div>
    @endif
</div>
@endsection
