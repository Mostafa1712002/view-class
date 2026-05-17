@extends('layouts.app')

@section('body_class','theme-light')
@section('title', __('libraries.labs.add'))

@section('content')
<div class="content-header row">
    <div class="content-header-left col-md-12 col-12 mb-2">
        <h2 class="content-header-title mb-0">@lang('libraries.labs.add')</h2>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ route('admin.libraries.labs.store') }}" enctype="multipart/form-data">
            @include('admin.libraries.labs._form')
        </form>
    </div></div>
</div>
@endsection
