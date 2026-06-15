@extends('layouts.admin')

@section('title', $subject->name)
@section('body_class', 'theme-light')

@push('styles')
<style>
    body.theme-light .subject-hero {
        background: linear-gradient(135deg, #fff8e6, #ffffff);
        border: 1px solid #f1e3bd;
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    body.theme-light .subject-hero .s-icon {
        width: 60px; height: 60px; border-radius: 14px;
        background: linear-gradient(135deg, #fff6dd, #fde2a8);
        color: var(--gold-500, #cfa046);
        font-size: 1.8rem; font-weight: 800;
        display: inline-flex; align-items: center; justify-content: center;
        box-shadow: inset 0 0 0 1px rgba(207,160,70,.2);
    }
    body.theme-light .subject-hero h1 { font-size: 1.5rem; font-weight: 800; color: #0f172a; margin-bottom: .2rem; }

    /* Hub section cards */
    body.theme-light .hub-section {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    body.theme-light .hub-section .sec-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: .8rem 1.2rem;
        display: flex;
        align-items: center;
        gap: .6rem;
    }
    body.theme-light .hub-section .sec-header h5 {
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    body.theme-light .hub-section .sec-header .count-chip {
        background: #f1f5f9;
        color: #475569;
        border-radius: 999px;
        padding: .1rem .5rem;
        font-size: .75rem;
        font-weight: 700;
    }
    body.theme-light .hub-section .sec-body { padding: 1rem 1.2rem; }

    /* Content items */
    body.theme-light .content-item {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .75rem;
        border-radius: 10px;
        margin-bottom: .4rem;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        transition: background .1s;
    }
    body.theme-light .content-item:hover { background: #f1f5f9; }
    body.theme-light .content-item .ci-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    body.theme-light .content-item .ci-icon.video      { background: #eff6ff; color: #1d4ed8; }
    body.theme-light .content-item .ci-icon.attachment { background: #fdf4ff; color: #9333ea; }
    body.theme-light .content-item .ci-icon.link       { background: #f0fdf4; color: #16a34a; }
    body.theme-light .content-item .ci-title { font-weight: 600; color: #0f172a; font-size: .9rem; }
    body.theme-light .content-item .ci-desc  { font-size: .78rem; color: #64748b; }
    body.theme-light .content-item .ci-action { margin-inline-start: auto; flex-shrink: 0; }

    /* Empty state */
    body.theme-light .empty-sec {
        text-align: center;
        padding: 1.5rem 1rem;
        color: #94a3b8;
        font-size: .88rem;
    }

    /* Assignment / exam rows */
    body.theme-light .ae-row {
        padding: .55rem .75rem;
        border-radius: 10px;
        margin-bottom: .35rem;
        background: #f8fafc;
        border: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: .6rem;
    }
    body.theme-light .ae-row .ae-title { font-weight: 600; color: #0f172a; font-size: .9rem; }
    body.theme-light .ae-row .ae-meta  { font-size: .78rem; color: #64748b; }
    body.theme-light .ae-row .ae-action { margin-inline-start: auto; }
    body.theme-light .status-chip {
        padding: .15rem .55rem; border-radius: 999px; font-size: .73rem; font-weight: 700;
    }
    body.theme-light .status-chip.active  { background: #dcfce7; color: #166534; }
    body.theme-light .status-chip.expired { background: #fef2f2; color: #b91c1c; }
    body.theme-light .status-chip.pending { background: #fffbeb; color: #b45309; }
</style>
@endpush

@section('content')

{{-- Back link --}}
<div class="mb-3">
    <a href="{{ route('student.subjects.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="la la-arrow-{{ app()->isLocale('ar') ? 'right' : 'left' }}"></i>
        العودة للمواد
    </a>
</div>

{{-- Subject hero --}}
<div class="subject-hero">
    <div class="s-icon">
        @if($subject->icon)
            <i class="la {{ $subject->icon }}"></i>
        @else
            <x-svg-icon name="book" />
        @endif
    </div>
    <div>
        <h1>{{ $subject->name }}</h1>
        @if($subject->code)
            <small class="text-muted">{{ $subject->code }}</small>
        @endif
    </div>
</div>

{{-- ── Videos ──────────────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-play-circle" style="color:#1d4ed8;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_videos')</h5>
        <span class="count-chip">{{ $videos->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($videos as $v)
            <div class="content-item">
                <div class="ci-icon video"><x-svg-icon name="play-circle" /></div>
                <div>
                    <div class="ci-title">{{ $v->title }}</div>
                    @if($v->description)
                        <div class="ci-desc">{{ Str::limit($v->description, 80) }}</div>
                    @endif
                </div>
                @php $vUrl = preg_match('#^https?://#i', (string) $v->url) ? $v->url : null; @endphp
                @if($vUrl)
                    <div class="ci-action">
                        <a href="{{ $vUrl }}" target="_blank" rel="noopener noreferrer"
                           class="btn btn-sm btn-outline-primary">
                            <x-svg-icon name="box-arrow-up-right" /> مشاهدة
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-sec"><i class="la la-video la-2x d-block mb-1"></i>@lang('subjects_content.empty_videos')</div>
        @endforelse
    </div>
</div>

{{-- ── Attachments ─────────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-paperclip" style="color:#9333ea;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_attachments')</h5>
        <span class="count-chip">{{ $attachments->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($attachments as $a)
            <div class="content-item">
                <div class="ci-icon attachment"><x-svg-icon name="file-earmark-text" /></div>
                <div>
                    <div class="ci-title">{{ $a->title }}</div>
                    @if($a->description)
                        <div class="ci-desc">{{ Str::limit($a->description, 80) }}</div>
                    @endif
                </div>
                @if($a->file_path)
                    <div class="ci-action">
                        <a href="{{ route('manage.subject-contents.download', [$subject->id, $a->id]) }}"
                           class="btn btn-sm btn-outline-secondary">
                            <x-svg-icon name="download" /> @lang('subjects_content.btn_download')
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-sec"><i class="la la-folder-open la-2x d-block mb-1"></i>@lang('subjects_content.empty_attachments')</div>
        @endforelse
    </div>
</div>

{{-- ── Links ───────────────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-link" style="color:#16a34a;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_links')</h5>
        <span class="count-chip">{{ $links->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($links as $lk)
            <div class="content-item">
                <div class="ci-icon link"><x-svg-icon name="link-45deg" /></div>
                <div>
                    <div class="ci-title">{{ $lk->title }}</div>
                    @if($lk->description)
                        <div class="ci-desc">{{ Str::limit($lk->description, 80) }}</div>
                    @endif
                </div>
                @php $lkUrl = preg_match('#^https?://#i', (string) $lk->url) ? $lk->url : null; @endphp
                @if($lkUrl)
                    <div class="ci-action">
                        <a href="{{ $lkUrl }}" target="_blank" rel="noopener noreferrer"
                           class="btn btn-sm btn-outline-success">
                            <x-svg-icon name="box-arrow-up-right" /> فتح الرابط
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-sec"><i class="la la-link la-2x d-block mb-1"></i>@lang('subjects_content.empty_links')</div>
        @endforelse
    </div>
</div>

{{-- ── Assignments ──────────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-tasks" style="color:#d97706;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_assignments')</h5>
        <span class="count-chip">{{ $assignments->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($assignments as $as)
            <div class="ae-row">
                <div>
                    <div class="ae-title">{{ $as->title }}</div>
                    <div class="ae-meta">
                        @if($as->due_date)
                            الموعد النهائي: {{ $as->due_date->format('Y-m-d') }}
                        @endif
                    </div>
                </div>
                @php
                    $isOverdue = $as->is_overdue;
                @endphp
                <div class="ae-action">
                    <span class="status-chip {{ $isOverdue ? 'expired' : 'active' }}">
                        {{ $isOverdue ? 'منتهي' : 'مفتوح' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="empty-sec"><i class="la la-tasks la-2x d-block mb-1"></i>لا توجد واجبات لهذه المادة حتى الآن.</div>
        @endforelse
    </div>
</div>

{{-- ── Exams ────────────────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-file-alt" style="color:#0891b2;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_exams')</h5>
        <span class="count-chip">{{ $exams->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($exams as $ex)
            @php
                $now = now();
                $available = $ex->start_time && $ex->start_time->lte($now)
                    && ($ex->end_time === null || $ex->end_time->gte($now));
            @endphp
            <div class="ae-row">
                <div>
                    <div class="ae-title">{{ $ex->title }}</div>
                    <div class="ae-meta">
                        @if($ex->start_time) بداية: {{ $ex->start_time->format('Y-m-d H:i') }} @endif
                        @if($ex->duration_minutes) — {{ $ex->duration_minutes }} دقيقة @endif
                    </div>
                </div>
                <div class="ae-action d-flex align-items-center gap-2">
                    <span class="status-chip {{ $available ? 'active' : ($ex->start_time && $ex->start_time->isFuture() ? 'pending' : 'expired') }}">
                        @if($available) متاح
                        @elseif($ex->start_time && $ex->start_time->isFuture()) قادم
                        @else منتهي
                        @endif
                    </span>
                    @if($available)
                        <a href="{{ route('student.exams.show', $ex->id) }}"
                           class="btn btn-sm btn-primary">
                            <x-svg-icon name="play" /> دخول
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-sec"><i class="la la-file-alt la-2x d-block mb-1"></i>لا توجد اختبارات لهذه المادة حتى الآن.</div>
        @endforelse
    </div>
</div>

{{-- ── Virtual Classes ─────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-video" style="color:#7c3aed;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_virtual')</h5>
        <span class="count-chip">{{ $virtualClasses->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($virtualClasses as $vc)
            <div class="ae-row">
                <div>
                    <div class="ae-title">{{ $vc->title }}</div>
                    <div class="ae-meta">
                        @if($vc->scheduled_at) {{ $vc->scheduled_at->format('Y-m-d H:i') }} @endif
                        @if($vc->duration_minutes) — {{ $vc->duration_minutes }} دقيقة @endif
                    </div>
                </div>
                <div class="ae-action d-flex align-items-center gap-2">
                    <span class="status-chip {{ $vc->status === 'live' ? 'active' : ($vc->status === 'scheduled' ? 'pending' : 'expired') }}">
                        {{ $vc->statusLabel() }}
                    </span>
                    @php $vcUrl = preg_match('#^https?://#i', (string) $vc->join_url) ? $vc->join_url : null; @endphp
                    @if($vc->isJoinable() && $vcUrl)
                        <a href="{{ $vcUrl }}" target="_blank" rel="noopener noreferrer"
                           class="btn btn-sm btn-success">
                            <x-svg-icon name="box-arrow-in-right" /> انضمام
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty-sec"><i class="la la-video la-2x d-block mb-1"></i>لا توجد حصص افتراضية مجدولة لهذه المادة.</div>
        @endforelse
    </div>
</div>

{{-- ── Discussion Rooms ─────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-comments" style="color:#0f766e;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_discussion')</h5>
        <span class="count-chip">{{ $discussionRooms->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($discussionRooms as $dr)
            <div class="ae-row">
                <div>
                    <div class="ae-title">{{ $dr->title }}</div>
                    @if($dr->description)
                        <div class="ae-meta">{{ Str::limit($dr->description, 80) }}</div>
                    @endif
                </div>
                <div class="ae-action">
                    <span class="status-chip active">نشط</span>
                </div>
            </div>
        @empty
            <div class="empty-sec"><i class="la la-comments la-2x d-block mb-1"></i>لا توجد غرف نقاش لهذه المادة حتى الآن.</div>
        @endforelse
    </div>
</div>

{{-- ── Books ─────────────────────────────────────────────────────────────────── --}}
<div class="hub-section">
    <div class="sec-header">
        <i class="la la-book" style="color:#b45309;font-size:1.2rem"></i>
        <h5>@lang('subjects_content.section_books')</h5>
        <span class="count-chip">{{ $books->count() }}</span>
    </div>
    <div class="sec-body">
        @forelse($books as $bk)
            <div class="ae-row">
                <div>
                    <div class="ae-title">{{ $bk->title }}</div>
                    <div class="ae-meta">
                        @if($bk->is_ministry)
                            <span class="status-chip pending">وزاري</span>
                        @endif
                    </div>
                </div>
                @if($bk->read_url)
                    <div class="ae-action">
                        <a href="{{ route('student.books.read', $bk->id) }}"
                           class="btn btn-sm btn-outline-warning">
                            <x-svg-icon name="book-half" /> قراءة
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="empty-sec"><i class="la la-book la-2x d-block mb-1"></i>لا توجد كتب مرتبطة بهذه المادة حتى الآن.</div>
        @endforelse
    </div>
</div>

@endsection
