@extends('layouts.app')
@section('title', __('users.edit'))
@section('content')
<div class="content-header"><h2 class="mb-2">@lang('users.edit'): {{ $teacher->name }}</h2></div>
<div class="card"><div class="card-body">
    <form action="{{ route('admin.users.teachers.update', $teacher->id) }}" method="POST">
        @method('PUT')
        @include('admin.users.teachers._form')
    </form>
</div></div>
@endsection
