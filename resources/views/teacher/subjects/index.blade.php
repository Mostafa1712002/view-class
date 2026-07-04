@extends('layouts.app')

@section('title', 'مواد المعلم')
@section('body_class', 'theme-light')

@push('styles')
<style>
    body.theme-light .subject-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        height: 100%;
        transition: box-shadow .15s ease, transform .15s ease;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }
    body.theme-light .subject-card:hover {
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
        transform: translateY(-3px);
    }
    body.theme-light .subject-card .subject-icon-wrap {
        background: linear-gradient(135deg, #fff8e6, #fff3d6);
        border-bottom: 1px solid #f1e4b8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .9rem 1.1rem;
    }
    body.theme-light .subject-card .subject-icon-wrap .ic {
        width: 44px; height: 44px; border-radius: 12px;
        display: inline-flex; align-items: center; justify-content: center;
        background: #fff; color: var(--gold-500, #cfa046); font-size: 1.4rem;
        box-shadow: inset 0 0 0 1px rgba(207,160,70,.18);
    }
    .subject-card-anchor { display:block; height:100%; text-decoration:none; color:inherit; }
    .subject-card-anchor:hover { text-decoration:none; color:inherit; }
    body.theme-light .subject-card .manage-cta { margin-top:.85rem; text-align:center; font-weight:600; font-size:.85rem; color:#fff; background:linear-gradient(135deg,var(--gold-300,#e0c37a),var(--gold-500,#cfa046)); border-radius:9px; padding:.5rem .75rem; display:flex; align-items:center; justify-content:center; gap:.4rem; }
    body.theme-light .subject-card .card-body { padding: 1rem 1.1rem; flex: 1; display:flex; flex-direction:column; }
    body.theme-light .subject-card h6 { font-weight: 700; color: #0f172a; font-size: 1.02rem; margin-bottom: .35rem; }
    body.theme-light .grade-chip {
        display: inline-block;
        padding: .2rem .6rem;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        font-size: .74rem;
        font-weight: 600;
        margin-inline-end: .3rem;
        margin-bottom: .3rem;
    }
    body.theme-light .src-chip {
        display: inline-block;
        padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 600;
        margin-bottom: .3rem;
    }
    body.theme-light .src-chip.src-viewclass { background: #fdf3d8; color: var(--gold-500, #cfa046); }
    body.theme-light .src-chip.src-school { background: #eef2ff; color: #4338ca; }
    body.theme-light .src-chip.src-system { background: #fef2f2; color: #b91c1c; }
    body.theme-light .subject-counts {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: .5rem;
        margin-top: .75rem;
    }
    body.theme-light .subject-counts .count-pill {
        display: flex; align-items: center; gap: .4rem;
        background: #f8fafc; border: 1px solid #eef2f7; border-radius: 10px;
        padding: .4rem .55rem;
    }
    body.theme-light .subject-counts .count-pill .n { font-weight: 800; color: #0f172a; font-size: .92rem; }
    body.theme-light .subject-counts .count-pill .l { color: #64748b; font-size: .7rem; }
    body.theme-light .subject-counts .count-pill .ic { color: var(--gold-500, #cfa046); flex-shrink: 0; }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">مواد المعلم</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">مواد المعلم</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">

    @if($cards->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="la la-book-open la-3x d-block mb-2"></i>
                لا توجد مواد مسندة إليك حالياً. سيظهر هنا كل مادة مرتبطة بجدولك أو بتكليفاتك.
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($cards as $card)
                @php($subject = $card['subject'])
                @php($counts = $card['counts'])
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="{{ route('teacher.materials.index', ['subject' => $subject->id]) }}" class="subject-card-anchor">
                    <div class="subject-card">
                        <div class="subject-icon-wrap">
                            <span class="ic">
                                @if($subject->icon)
                                    <i class="la {{ $subject->icon }}"></i>
                                @else
                                    <x-svg-icon name="journal-bookmark-fill" :size="22" />
                                @endif
                            </span>
                            <span class="src-chip src-{{ $card['source_label'] === 'فيوكلاس' ? 'viewclass' : ($card['source_label'] === 'مدير النظام' ? 'system' : 'school') }}">
                                {{ $card['source_label'] }}
                            </span>
                        </div>
                        <div class="card-body">
                            <h6>{{ $subject->display_name }}</h6>
                            @if($subject->code)
                                <small class="text-muted d-block mb-2">{{ $subject->code }}</small>
                            @endif

                            <div>
                                <span class="grade-chip"><x-svg-icon name="signpost-split" :size="12" /> {{ $card['grade_label'] }}</span>
                                <span class="grade-chip"><x-svg-icon name="bookshelf" :size="12" /> {{ $card['stage_label'] }}</span>
                            </div>

                            <div class="subject-counts">
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="door-open-fill" :size="15" /></span>
                                    <span><span class="n">{{ $counts['classes'] }}</span> <span class="l d-block">الفصول</span></span>
                                </div>
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="people-fill" :size="15" /></span>
                                    <span><span class="n">{{ $counts['students'] }}</span> <span class="l d-block">الطلاب</span></span>
                                </div>
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="clipboard-check-fill" :size="15" /></span>
                                    <span><span class="n">{{ $counts['assignments'] }}</span> <span class="l d-block">الواجبات</span></span>
                                </div>
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="pencil-square" :size="15" /></span>
                                    <span><span class="n">{{ $counts['exams'] }}</span> <span class="l d-block">الاختبارات</span></span>
                                </div>
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="paperclip" :size="15" /></span>
                                    <span><span class="n">{{ $counts['attachments'] }}</span> <span class="l d-block">المرفقات</span></span>
                                </div>
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="camera-video-fill" :size="15" /></span>
                                    <span><span class="n">{{ $counts['videos'] }}</span> <span class="l d-block">الفيديوهات</span></span>
                                </div>
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="chat-square-text-fill" :size="15" /></span>
                                    <span><span class="n">{{ $counts['discussion_rooms'] }}</span> <span class="l d-block">غرف النقاش</span></span>
                                </div>
                                <div class="count-pill">
                                    <span class="ic"><x-svg-icon name="display" :size="15" /></span>
                                    <span><span class="n">{{ $counts['virtual_classes'] }}</span> <span class="l d-block">الفصول الافتراضية</span></span>
                                </div>
                            </div>

                            <div class="manage-cta">
                                <x-svg-icon name="folder2-open" :size="14" /> إدارة محتوى المادة
                            </div>
                        </div>
                    </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
