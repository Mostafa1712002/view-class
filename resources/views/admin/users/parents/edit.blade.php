@extends('layouts.app')
@section('title', __('users.edit'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.edit'): {{ $parent->name }}</h2></div>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.users.parents.update', $parent->id) }}" method="POST">
        @method('PUT')
        @include('admin.users.parents._form')
    </form>
</div></div>
@endsection
