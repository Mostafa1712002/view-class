@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.products.add'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.products.add') — {{ $canteen->name_ar }}</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteens.products.index', $canteen->id) }}">@lang('canteen.products.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.products.add')</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="card-body">
    <form method="POST" action="{{ route('admin.canteens.products.store', $canteen->id) }}" enctype="multipart/form-data">
        @include('admin.canteens.products._form')
    </form>
</div></div></div>
@endsection
