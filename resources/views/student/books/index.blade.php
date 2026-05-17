@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('books_admin.student.page_title'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('books_admin.student.page_title')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item active">@lang('books_admin.student.page_title')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    @if($books->isEmpty())
        <div class="card"><div class="card-body text-center py-5 text-muted">
            <i class="la la-book-open la-3x d-block mb-2"></i>
            @lang('books_admin.student.empty')
        </div></div>
    @else
        <div class="row g-3">
            @foreach($books as $book)
                <div class="col-md-4 col-lg-3">
                    <div class="card h-100">
                        <div class="text-center bg-light pt-3" style="min-height:160px">
                            @if($book->cover_url)
                                <img src="{{ $book->cover_url }}" alt="" style="max-height:140px" class="img-fluid" />
                            @else
                                <i class="la la-book-open" style="font-size:80px;color:#94a3b8"></i>
                            @endif
                        </div>
                        <div class="card-body">
                            <h6 class="mb-1">{{ $book->title }}</h6>
                            <small class="text-muted d-block">{{ optional($book->subject)->name }}</small>
                            @if($book->grade_level)
                                <small class="text-muted">@lang('books_admin.grade_label', ['n' => $book->grade_level])</small>
                            @endif
                            @if($book->is_ministry)
                                <span class="badge bg-warning text-dark mt-1">@lang('books_admin.ministry_yes')</span>
                            @endif
                        </div>
                        <div class="card-footer text-center">
                            @if($book->read_url)
                                <a href="{{ $book->read_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary w-100">
                                    <i class="la la-eye"></i> @lang('books_admin.student.open')
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
