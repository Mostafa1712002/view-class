@extends('layouts.app')
@section('title', __('users.edit'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.edit'): {{ $admin->name }}</h2></div>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.users.admins.update', $admin->id) }}" method="POST">
        @method('PUT')
        @include('admin.users.admins._form')
    </form>
</div></div>
@endsection
