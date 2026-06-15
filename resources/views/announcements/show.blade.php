@extends('layouts.app')

@section('title', $announcement->title)
@section('page-title', 'إعلان')

@php
    $myRead = $announcement->reads->firstWhere('user_id', auth()->id());
    $confirmed = $myRead && $myRead->read_confirmed_at;
@endphp

@section('content')
<section class="vc-user-ann-show">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card"><div class="card-body">
        <h3>{{ $announcement->title }}
            @if($announcement->type === 'important')<span class="badge badge-warning">مهم</span>@endif
        </h3>
        <small class="text-muted">{{ $announcement->published_at?->format('Y-m-d H:i') }}</small>
        <hr>
        <div class="ann-body">{!! \App\Support\HtmlSanitizer::clean($announcement->body) !!}</div>

        @if($announcement->require_read_ack)
            <hr>
            @if($confirmed)
                <div class="alert alert-success" style="margin:0"><x-svg-icon name="check-circle-fill" :size="16" /> لقد أكدت قراءة هذا الإعلان.</div>
            @else
                <form method="POST" action="{{ route('announcements.confirm', $announcement->id) }}">@csrf
                    <button type="submit" class="btn btn-primary"><x-svg-icon name="check-lg" :size="16" /> تأكيد القراءة</button>
                </form>
            @endif
        @endif

        <div style="margin-top:1rem"><a href="{{ route('announcements.index') }}" class="btn btn-light">عودة لكل الإعلانات</a></div>
    </div></div>
</section>
@endsection
