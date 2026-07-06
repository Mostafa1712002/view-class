@extends('layouts.app')

@section('title', 'دليل الاستخدام')
@section('body_class', 'theme-light')

@push('styles')
<style>
    body.theme-light .docs-hero { background:linear-gradient(135deg,#fff8e6,#fff3d6); border:1px solid #f1e4b8; border-radius:16px; padding:1.4rem 1.6rem; margin-bottom:1.5rem; }
    body.theme-light .docs-hero h2 { font-weight:800; color:#0f172a; margin:0 0 .3rem; }
    body.theme-light .docs-hero p { color:#64748b; margin:0; font-size:.95rem; }
    body.theme-light .docs-track { margin-bottom:2rem; }
    body.theme-light .docs-track-head { display:flex; align-items:center; gap:.6rem; margin-bottom:1rem; }
    body.theme-light .docs-track-head .ic { width:40px; height:40px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center; background:#fff; color:var(--gold-500,#cfa046); font-size:1.3rem; box-shadow:inset 0 0 0 1px rgba(207,160,70,.18); }
    body.theme-light .docs-track-head h4 { font-weight:800; color:#0f172a; margin:0; font-size:1.15rem; }
    body.theme-light .docs-track-head .cnt { margin-inline-start:auto; font-size:.78rem; color:#64748b; background:#f1f5f9; padding:.2rem .7rem; border-radius:999px; font-weight:600; }
    body.theme-light .video-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; height:100%; display:flex; flex-direction:column; transition:box-shadow .15s ease, transform .15s ease; }
    body.theme-light .video-card:hover { box-shadow:0 10px 28px rgba(15,23,42,.08); transform:translateY(-3px); }
    body.theme-light .video-thumb { position:relative; aspect-ratio:16/9; background:#0f172a; display:flex; align-items:center; justify-content:center; }
    body.theme-light .video-thumb iframe, body.theme-light .video-thumb video { width:100%; height:100%; border:0; }
    body.theme-light .video-thumb .soon { color:#cbd5e1; text-align:center; font-size:.85rem; }
    body.theme-light .video-thumb .soon i { font-size:2rem; display:block; margin-bottom:.4rem; color:var(--gold-400,#e0c37a); }
    body.theme-light .video-thumb .vid-id { position:absolute; top:.5rem; inset-inline-start:.5rem; background:rgba(0,0,0,.55); color:#fff; font-size:.7rem; font-weight:700; padding:.15rem .5rem; border-radius:6px; }
    body.theme-light .video-body { padding:.9rem 1rem; flex:1; display:flex; flex-direction:column; }
    body.theme-light .video-body h6 { font-weight:700; color:#0f172a; font-size:.98rem; margin:0 0 .35rem; }
    body.theme-light .video-body .desc { color:#475569; font-size:.85rem; line-height:1.7; flex:1; }
    body.theme-light .video-meta { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-top:.7rem; }
    body.theme-light .video-meta .chip { font-size:.72rem; font-weight:600; padding:.2rem .55rem; border-radius:999px; background:#f8fafc; border:1px solid #eef2f7; color:#475569; }
    body.theme-light .video-meta .chip i { color:var(--gold-500,#cfa046); }
</style>
@endpush

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">دليل الاستخدام</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">دليل الاستخدام</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <div class="docs-hero">
        <h2><i class="la la-play-circle"></i> شروحات فيديو النظام</h2>
        <p>{{ $videoCount }} فيديو شرح تغطي شاشات النظام خطوة بخطوة، مرتبة حسب دورك. اختر أي فيديو للمشاهدة.</p>
    </div>

    @foreach($tracks as $track)
        <div class="docs-track">
            <div class="docs-track-head">
                <span class="ic"><i class="la {{ $track['icon'] }}"></i></span>
                <h4>{{ $track['title'] }}</h4>
                <span class="cnt">{{ count($track['videos']) }} فيديو</span>
            </div>
            <div class="row">
                @foreach($track['videos'] as $v)
                    <div class="col-12 col-md-6 col-xl-4 mb-3">
                        <div class="video-card">
                            <div class="video-thumb">
                                <span class="vid-id">{{ $v['id'] }}</span>
                                @if(!empty($v['video_url']))
                                    @if(\Illuminate\Support\Str::endsWith($v['video_url'], ['.webm', '.mp4', '.ogg']))
                                        <video src="{{ $v['video_url'] }}" controls preload="metadata" title="{{ $v['title'] }}"></video>
                                    @else
                                        <iframe src="{{ $v['video_url'] }}" allowfullscreen loading="lazy" title="{{ $v['title'] }}"></iframe>
                                    @endif
                                @else
                                    <div class="soon"><i class="la la-video"></i> قريبًا</div>
                                @endif
                            </div>
                            <div class="video-body">
                                <h6>{{ $v['title'] }}</h6>
                                <div class="desc">{{ $v['desc'] }}</div>
                                <div class="video-meta">
                                    <span class="chip"><i class="la la-clock"></i> {{ $v['duration'] }}</span>
                                    <span class="chip"><i class="la la-map-marker"></i> {{ $v['path'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection
