@extends('layouts.app')
@section('title', $admin->name)
@section('content')
<div class="content-header"><h2 class="mb-2">{{ $admin->name }} — @lang('users.'.($type === 'student' ? 'students' : 'teachers'))</h2></div>
<div class="card"><div class="card-body">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <form action="{{ route('admin.users.admins.supervisees.sync', $admin->id) }}" method="POST">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}" />
        <table class="table table-sm table-hover">
            <thead><tr>
                <th>✓</th>
                <th>@lang('users.name')</th>
                <th>@lang('users.username')</th>
            </tr></thead>
            <tbody>
            @foreach($candidates as $c)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $c->id }}" @checked(in_array($c->id, $assigned))></td>
                    <td>{{ $c->name }}</td>
                    <td>{{ $c->username }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <button class="btn btn-primary"><i class="la la-save"></i> @lang('users.save')</button>
        <a href="{{ route('admin.users.admins.index') }}" class="btn btn-outline-secondary">@lang('users.cancel')</a>
    </form>
</div></div>
@endsection
