@extends('layouts.app')

@section('title', $announcement->title)
@section('page-title', 'إعلان')
@section('body_class', 'theme-light')

@php
    $myRead = $announcement->reads->firstWhere('user_id', auth()->id());
    $confirmed = $myRead && $myRead->read_confirmed_at;
@endphp

@section('content')
<section class="vc-user-ann-show">

    {{-- Page header + breadcrumb --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:.75rem;margin-bottom:1rem">
        <div>
            <h2 style="margin:0;font-size:1.45rem;font-weight:800;color:var(--gray-900)">
                {{ $announcement->title }}
            </h2>
            <nav><ol class="breadcrumb" style="margin:0;padding:0;background:transparent">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('announcements.index') }}">الإعلانات</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($announcement->title, 40) }}</li>
            </ol></nav>
        </div>
        <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary btn-sm">
            <x-svg-icon name="arrow-right" :size="15" /> عودة للإعلانات
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="ds-card card">
        <div class="ds-card-header card-header" style="display:flex;align-items:center;gap:.5rem">
            <x-svg-icon name="megaphone-fill" :size="16" class="text-gold" />
            <h5 class="ds-card-title" style="margin:0;flex:1">{{ $announcement->title }}</h5>
            @if($announcement->type === 'important')
                <span class="badge ds-badge-warning">مهم</span>
            @endif
        </div>

        <div class="card-body">
            <small class="text-muted" style="display:flex;align-items:center;gap:.3rem;margin-bottom:.85rem">
                <x-svg-icon name="calendar-event" :size="13" /> {{ $announcement->published_at?->format('Y-m-d H:i') }}
            </small>

            <div class="ann-body" style="line-height:1.8">{!! \App\Support\HtmlSanitizer::clean($announcement->body) !!}</div>

            @if($announcement->require_read_ack)
                <hr>
                @if($confirmed)
                    <div class="alert alert-success" style="margin:0">
                        <x-svg-icon name="check-circle-fill" :size="16" /> لقد أكدت قراءة هذا الإعلان.
                    </div>
                @else
                    <form method="POST" action="{{ route('announcements.confirm', $announcement->id) }}">@csrf
                        <button type="submit" class="btn btn-primary">
                            <x-svg-icon name="check-lg" :size="16" /> تأكيد القراءة
                        </button>
                    </form>
                @endif
            @endif
        </div>

        <div class="card-footer" style="display:flex;justify-content:flex-start">
            <a href="{{ route('announcements.index') }}" class="btn btn-outline-secondary btn-sm">
                <x-svg-icon name="arrow-right" :size="15" /> عودة لكل الإعلانات
            </a>
        </div>
    </div>

</section>
@endsection
