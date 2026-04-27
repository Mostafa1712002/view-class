@extends('layouts.app')
@section('title', __('users.add_teacher'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.add_teacher')</h2></div>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.users.teachers.store') }}" method="POST">
        @include('admin.users.teachers._form')
    </form>
</div></div>
@endsection
