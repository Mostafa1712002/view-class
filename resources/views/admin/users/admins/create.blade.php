@extends('layouts.app')
@section('title', __('users.add_admin'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.add_admin')</h2></div>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.users.admins.store') }}" method="POST">
        @include('admin.users.admins._form')
    </form>
</div></div>
@endsection
