@extends('layouts.app')

@section('title', 'الإعلانات')
@section('page-title', 'الإعلانات')
@section('body_class', 'theme-light')

@section('content')
<section class="vc-user-ann">

    {{-- Page header + breadcrumb --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.45rem;font-weight:800;color:var(--gray-900)">الإعلانات</h2>
            <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item active" aria-current="page">الإعلانات</li>
            </ol></nav>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="ds-card card">
        <div class="ds-card-header card-header">
            <h5 class="ds-card-title" style="margin:0;display:flex;align-items:center;gap:.4rem">
                <x-svg-icon name="megaphone" :size="18" /> الإعلانات
            </h5>
        </div>

        @if($announcements->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><x-svg-icon name="megaphone" :size="30" /></div>
                <div class="ds-empty-title">لا توجد إعلانات</div>
                <div class="ds-empty-desc">لا توجد إعلانات موجهة إليك حالياً.</div>
            </div>
        @else
            <div class="card-body" style="padding:.25rem 0 0">
                @foreach($announcements as $a)
                    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border-subtle)">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem">
                            <h5 style="margin:0;font-weight:700;color:var(--gray-900)">
                                {{ $a->title }}
                                @if($a->type === 'important')
                                    <span class="badge ds-badge-warning" style="margin-inline-start:.35rem">مهم</span>
                                @endif
                            </h5>
                            <small class="text-muted" style="white-space:nowrap">
                                <x-svg-icon name="calendar-event" :size="13" /> {{ $a->published_at?->format('Y-m-d') }}
                            </small>
                        </div>
                        <div class="ann-body" style="margin-top:.45rem;color:var(--text-secondary);font-size:.9rem">
                            {!! \App\Support\HtmlSanitizer::clean($a->body) !!}
                        </div>
                        <a href="{{ route('announcements.show', $a->id) }}" class="btn btn-sm btn-outline-secondary" style="margin-top:.55rem">
                            <x-svg-icon name="eye" :size="14" /> عرض التفاصيل
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</section>
@endsection
