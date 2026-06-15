@extends('layouts.app')

@section('title', 'الإعلانات')
@section('page-title', 'الإعلانات')

@section('content')
<section class="vc-user-ann">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @if($announcements->isEmpty())
        <div class="card"><div class="card-body" style="text-align:center;padding:3rem 1rem">
            <x-svg-icon name="megaphone" :size="40" class="ic-muted" />
            <h4 style="margin-top:.5rem">لا توجد إعلانات</h4>
            <p class="text-muted">لا توجد إعلانات موجهة إليك حالياً.</p>
        </div></div>
    @else
        @foreach($announcements as $a)
            <div class="card" style="margin-bottom:.8rem"><div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem">
                    <h5 style="margin:0">
                        {{ $a->title }}
                        @if($a->type === 'important')<span class="badge badge-warning">مهم</span>@endif
                    </h5>
                    <small class="text-muted">{{ $a->published_at?->format('Y-m-d') }}</small>
                </div>
                <div class="ann-body" style="margin-top:.5rem">{!! \App\Support\HtmlSanitizer::clean($a->body) !!}</div>
                <a href="{{ route('announcements.show', $a->id) }}" class="btn btn-sm btn-outline-primary" style="margin-top:.5rem">عرض التفاصيل</a>
            </div></div>
        @endforeach
    @endif
</section>
@endsection
