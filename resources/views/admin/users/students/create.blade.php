@extends('layouts.app')
@section('title', __('users.add_student'))
@section('content')
<div class="content-header row">
    <div class="content-header-left col-12">
        <h2 class="content-header-title mb-0">@lang('users.add_student')</h2>
    </div>
</div>
<div class="content-body">
    <div class="card"><div class="card-body">
        <form action="{{ route('admin.users.students.store') }}" method="POST">
            @include('admin.users.students._form')
        </form>
    </div></div>
</div>
@endsection
