@extends('layouts.app')
@section('title', $item->title)
@section('body_class', 'theme-light')
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $item->title }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.libraries.public.index') }}">@lang('shell.nav_library_public')</a></li>
            <li class="breadcrumb-item active">{{ $item->title }}</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="{{ route('admin.libraries.public.index') }}" class="btn btn-soft btn-sm"><i class="la la-arrow-right"></i> @lang('libraries.show.back')</a>
    </div>
</div>

<div class="content-body">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0 pr-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="row">
        {{-- Details + rating --}}
        <div class="col-lg-5 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0">@lang('libraries.show.details')</h5></div>
                <div class="card-body">
                    <p>
                        <span class="lib-type-chip">@lang('libraries.types.'.$item->content_type)</span>
                    </p>
                    @if($item->description)<p class="text-muted">{{ $item->description }}</p>@endif
                    <ul class="list-unstyled mb-3">
                        @if($item->subject)<li><strong>@lang('libraries.show.subject'):</strong> {{ $item->subject->name }}</li>@endif
                        @if($item->teacher)<li><strong>@lang('libraries.show.teacher'):</strong> {{ $item->teacher->name }}</li>@endif
                    </ul>
                    @if($item->file_path)
                        <a href="{{ asset('storage/'.$item->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="la la-download"></i> @lang('libraries.show.download')</a>
                    @endif
                    @if($item->external_url)
                        <a href="{{ $item->external_url }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="la la-external-link-alt"></i> @lang('libraries.show.open')</a>
                    @endif

                    <hr>
                    <h6>@lang('libraries.show.rating')</h6>
                    <div class="mb-2">
                        <span style="font-size:1.4rem;font-weight:700;color:#f59e0b;">{{ number_format($avg, 1) }}</span>
                        <span class="text-muted">/ 5</span>
                        <span class="text-muted small">({{ $count }} @lang('libraries.show.count'))</span>
                    </div>
                    <form method="POST" action="{{ route('admin.libraries.public.rate', $item->id) }}" class="lib-stars m-0">
                        @csrf
                        <small class="text-muted d-block mb-1">@lang('libraries.show.your_rating'): {{ $userRating ? $userRating.'/5' : '—' }}</small>
                        @for($s = 1; $s <= 5; $s++)
                            <button type="submit" name="rating" value="{{ $s }}"
                                class="btn btn-link p-0 lib-star {{ $userRating && $s <= $userRating ? 'is-on' : '' }}"
                                style="font-size:1.6rem;line-height:1;color:{{ $userRating && $s <= $userRating ? '#f59e0b' : '#cbd5e1' }};text-decoration:none;"
                                title="{{ $s }}/5">★</button>
                        @endfor
                        <small class="text-muted d-block mt-1">@lang('libraries.show.rate_hint')</small>
                    </form>
                </div>
            </div>
        </div>

        {{-- Comments --}}
        <div class="col-lg-7 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="la la-comments"></i> @lang('libraries.show.comments') ({{ $item->comments->count() }})</h5></div>
                <div class="card-body">
                    @if($item->allow_comments)
                        <form method="POST" action="{{ route('admin.libraries.public.comments.store', $item->id) }}" class="mb-3">
                            @csrf
                            <textarea name="body" rows="2" maxlength="1000" class="form-control mb-2" placeholder="@lang('libraries.show.comment_placeholder')" required></textarea>
                            <button type="submit" class="btn btn-sm btn-primary"><i class="la la-paper-plane"></i> @lang('libraries.show.post_comment')</button>
                        </form>
                    @else
                        <div class="alert alert-secondary py-2">@lang('libraries.show.comments_disabled')</div>
                    @endif

                    @forelse($item->comments as $comment)
                        <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                            <div>
                                <strong>{{ optional($comment->user)->name ?? '—' }}</strong>
                                <small class="text-muted">· {{ $comment->created_at?->diffForHumans() }}</small>
                                <div>{{ $comment->body }}</div>
                            </div>
                            @php $u = auth()->user(); @endphp
                            @if($comment->user_id === $u->id || $u->isSuperAdmin() || $u->isSchoolAdmin())
                                <form method="POST" action="{{ route('admin.libraries.public.comments.destroy', [$item->id, $comment->id]) }}" onsubmit="return confirm('@lang('libraries.confirm_delete')')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="@lang('libraries.show.delete_comment')"><i class="la la-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted text-center py-3 mb-0">@lang('libraries.show.no_comments')</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
