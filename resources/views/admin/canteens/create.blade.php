@extends('layouts.app')
@section('body_class','theme-light')
@section('title', __('canteen.add'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('canteen.add')</h2>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('common.home')</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.canteens.index') }}">@lang('canteen.title')</a></li>
            <li class="breadcrumb-item active">@lang('canteen.add')</li>
        </ol>
    </div>
</div>
<div class="content-body"><div class="card"><div class="card-body">
    <div class="alert alert-info py-2"><i class="la la-info-circle"></i> @lang('canteen.activation.hint')</div>
    <form method="POST" action="{{ route('admin.canteens.store') }}">
        @include('admin.canteens._form')
    </form>
</div></div></div>
@endsection
