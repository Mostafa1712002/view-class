@extends('layouts.app')
@section('title', __('users.add_parent'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.add_parent')</h2></div>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.users.parents.store') }}" method="POST">
        @include('admin.users.parents._form')
    </form>
</div></div>
@endsection
