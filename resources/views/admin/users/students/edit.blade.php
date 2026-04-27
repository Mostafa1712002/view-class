@extends('layouts.app')
@section('title', __('users.edit'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-12">
        <h2 class="content-header-title mb-0">@lang('users.edit'): {{ $student->name }}</h2>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.users.students.update', $student->id) }}" method="POST">
            @method('PUT')
            @include('admin.users.students._form')
        </form>
    </div></div>
</div>
@endsection
