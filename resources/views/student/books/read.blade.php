@extends('layouts.app')
@section('body_class','theme-light')
@section('title', $book->title)
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-8 col-12 mb-2">
        <h2 class="content-header-title mb-0">{{ $book->title }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('student.dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('student.books.index') }}">@lang('books_admin.student.page_title')</a></li>
            <li class="breadcrumb-item active">{{ $book->title }}</li>
        </ol>
    </div>
    <div class="content-header-right col-md-4 col-12 text-md-left">
        <a href="{{ route('student.books.index') }}" class="btn btn-soft btn-sm"><i class="la la-arrow-right"></i> @lang('books_admin.student.back_to_books')</a>
        @if($book->read_url)
            <a href="{{ $book->read_url }}" download class="btn btn-outline-primary btn-sm"><i class="la la-download"></i> @lang('books_admin.student.download')</a>
        @endif
    </div>
</div>

<div class="content-body">
    <div class="card">
        <div class="card-body p-0">
            @if($book->read_url)
                @php $isPdf = \Illuminate\Support\Str::endsWith(strtolower($book->file_path ?? ''), '.pdf'); @endphp
                @if($isPdf)
                    <object data="{{ $book->read_url }}#toolbar=1&view=FitH" type="application/pdf"
                            style="width:100%;height:80vh;border:0;border-radius:.5rem;">
                        <iframe src="{{ $book->read_url }}" style="width:100%;height:80vh;border:0;"></iframe>
                    </object>
                @else
                    <iframe src="{{ $book->read_url }}" style="width:100%;height:80vh;border:0;border-radius:.5rem;"
                            sandbox="allow-same-origin allow-scripts allow-popups"></iframe>
                @endif
                <div class="p-2 text-center">
                    <a href="{{ $book->read_url }}" target="_blank" rel="noopener" class="btn btn-link btn-sm">
                        <i class="la la-external-link-alt"></i> @lang('books_admin.student.open_new_tab')
                    </a>
                </div>
            @else
                <div class="text-center text-muted p-5">
                    <i class="la la-book" style="font-size:2.5rem;"></i>
                    <p class="mb-0 mt-2">@lang('books_admin.student.no_file')</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
