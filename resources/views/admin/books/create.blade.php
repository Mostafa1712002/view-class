@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('books_admin.add_book'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('books_admin.add_book')</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
                <li class="breadcrumb-item"><a href="{{ route('manage.books.index') }}">@lang('books_admin.page_title')</a></li>
                <li class="breadcrumb-item active">@lang('books_admin.add_book')</li>
            </ol>
        </div>
    </div>
</div>

<div class="content-body">
    <div class="card"><div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('manage.books.store') }}" enctype="multipart/form-data">
            @include('admin.books._form')
        </form>
    </div></div>
</div>
@endsection
